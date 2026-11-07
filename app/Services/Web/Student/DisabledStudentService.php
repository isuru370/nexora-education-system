<?php

namespace App\Services\Web\Student;

use App\Models\GradeClosure;
use App\Models\Student;
use App\Models\StudentAutoDisable;
use App\Models\StudentReactivation;
use App\Services\Branch\BranchAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Disabled Students: every student who cannot be used now
 * (is_active = false or student_disable = true), whatever disabled them:
 *
 *  - auto   : no class for 2 months (disable:expired-students)
 *  - batch  : Close Batch (grade closed)
 *  - manual : made inactive by staff
 *
 * Enable clears both flags (the app blocks student_disable students) and,
 * when asked, puts the student back in the classes they were taken out of
 * (only classes that are still active).
 */
class DisabledStudentService
{
    public const AUTO = 'auto';
    public const BATCH = 'batch';
    public const MANUAL = 'manual';

    public const REASONS = [
        self::AUTO => 'No class for 2 months',
        self::BATCH => 'Batch closed',
        self::MANUAL => 'Made inactive',
    ];

    public function __construct(protected BranchAccessService $branchAccess) {}

    public function query(array $filters): Builder
    {
        $query = Student::query()
            ->with(['grade:id,grade_name', 'branch:id,name'])
            ->where(fn ($q) => $q->where('is_active', false)->orWhere('student_disable', true));

        $this->branchAccess->applyBranchAccess($query);

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $query->where(fn ($q) => $q->where('custom_id', 'like', $term)
                ->orWhere('full_name', 'like', $term)
                ->orWhere('initial_name', 'like', $term)
                ->orWhere('guardian_mobile', 'like', $term)
                ->orWhere('temporary_qr_code', 'like', $term));
        }

        $reason = $filters['reason'] ?? null;
        if ($reason === self::AUTO) {
            $query->whereIn('id', $this->openAutoRecords()->select('student_id'));
        } elseif ($reason === self::BATCH) {
            $query->whereIn('id', $this->batchMap()->keys()->all() ?: [0]);
        } elseif ($reason === self::MANUAL) {
            $query->whereNotIn('id', $this->openAutoRecords()->select('student_id'))
                ->whereNotIn('id', $this->batchMap()->keys()->all() ?: [0]);
        }

        return $query->orderByDesc('updated_at')->orderBy('custom_id');
    }

    /**
     * One page of students, each with ->disabled_reason / _label / _date / _classes.
     */
    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $page = $this->query($filters)->paginate($perPage)->withQueryString();

        $this->describe($page->getCollection());

        return $page;
    }

    /** Counts per reason (for the filter chips), within the user's branches */
    public function counts(): array
    {
        $all = $this->query([])->pluck('id');
        $auto = $this->openAutoRecords()->whereIn('student_id', $all)->distinct()->count('student_id');
        $batch = $this->batchMap()->keys()->intersect($all)->diff(
            $this->openAutoRecords()->whereIn('student_id', $all)->pluck('student_id')
        )->count();

        return [
            'all' => $all->count(),
            self::AUTO => $auto,
            self::BATCH => $batch,
            self::MANUAL => max(0, $all->count() - $auto - $batch),
        ];
    }

    /**
     * Enable students. Returns [enabled, classes put back].
     *
     * @param int[] $ids
     */
    public function enable(array $ids, bool $restoreClasses, ?int $userId): array
    {
        $enabled = 0;
        $restored = 0;

        $students = $this->query([])->whereIn('id', array_map('intval', $ids))->get();
        $this->describe($students);

        foreach ($students as $student) {
            DB::transaction(function () use ($student, $restoreClasses, $userId, &$enabled, &$restored) {
                $classes = 0;

                // direct update: $student also carries the display fields (disabled_reason ...)
                Student::query()->whereKey($student->id)
                    ->update(['is_active' => true, 'student_disable' => false, 'updated_at' => now()]);

                // auto-disable record -> re-activated (also seen on that page)
                $record = $this->openAutoRecords()->where('student_id', $student->id)->latest('disabled_at')->first();
                if ($record) {
                    if ($restoreClasses) {
                        $classes += $this->restore(
                            $student->id,
                            collect($record->classes ?? [])->pluck('enrollment_id')->filter()->all(),
                            $record->disabled_at->toDateString()
                        );
                    }
                    $record->update(['reactivated_at' => now(), 'reactivated_by' => $userId]);
                } elseif ($restoreClasses && ($closure = $this->batchMap()->get($student->id))) {
                    $classes += $this->restore(
                        $student->id,
                        collect($closure->changes['enrollments'] ?? [])->pluck('id')->all(),
                        $closure->closed_at->toDateString()
                    );
                }

                StudentReactivation::create([
                    'student_id' => $student->id,
                    'branch_id' => $student->branch_id,
                    'reason' => $student->disabled_reason,
                    'classes_restored' => $classes,
                    'enabled_by' => $userId,
                ]);

                $enabled++;
                $restored += $classes;
            });
        }

        return [$enabled, $restored];
    }

    /**
     * Put the student back in these enrollments: only ones of this student,
     * still inactive since that day, in a class that is still active.
     */
    protected function restore(int $studentId, array $enrollmentIds, string $leftOn): int
    {
        if (!$enrollmentIds) {
            return 0;
        }

        $ids = DB::table('student_class_enrollments as e')
            ->join('student_classes as c', 'c.id', '=', 'e.student_class_id')
            ->where('e.student_id', $studentId)
            ->whereIn('e.id', $enrollmentIds)
            ->where('e.is_active', false)
            ->whereDate('e.left_at', $leftOn)
            ->where('c.is_active', true)
            ->whereNull('c.deleted_at')
            ->pluck('e.id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return DB::table('student_class_enrollments')
            ->whereIn('id', $ids)
            ->update(['is_active' => true, 'left_at' => null, 'updated_at' => now()]);
    }

    /** Adds the reason, its date and the classes taken away to each student */
    protected function describe(Collection $students): void
    {
        $ids = $students->pluck('id');

        $auto = $this->openAutoRecords()->whereIn('student_id', $ids)->orderBy('disabled_at')->get()->keyBy('student_id');
        $batch = $this->batchMap();

        foreach ($students as $student) {
            if ($record = $auto->get($student->id)) {
                $student->disabled_reason = self::AUTO;
                $student->disabled_on = $record->disabled_at;
                $student->disabled_classes = collect($record->classes ?? [])->pluck('class')->filter()->values()->all();
            } elseif ($closure = $batch->get($student->id)) {
                $student->disabled_reason = self::BATCH;
                $student->disabled_on = $closure->closed_at;
                $student->disabled_classes = $closure->grade_names ?? [];
            } else {
                $student->disabled_reason = self::MANUAL;
                $student->disabled_on = $student->updated_at;
                $student->disabled_classes = [];
            }

            $student->disabled_label = self::REASONS[$student->disabled_reason];
        }
    }

    protected function openAutoRecords(): Builder
    {
        return StudentAutoDisable::query()->whereNull('reactivated_at');
    }

    /**
     * student_id => the newest Close Batch (not undone) that disabled them,
     * unless the student was enabled again after it.
     */
    protected function batchMap(): Collection
    {
        if ($this->batchMap !== null) {
            return $this->batchMap;
        }

        $map = collect();
        GradeClosure::query()->whereNull('undone_at')->orderBy('closed_at')->get()
            ->each(function (GradeClosure $closure) use ($map) {
                foreach ($closure->changes['students'] ?? [] as $row) {
                    $map->put((int) $row['id'], $closure);
                }
            });

        if ($map->isNotEmpty()) {
            $enabledAt = StudentReactivation::query()
                ->whereIn('student_id', $map->keys())
                ->groupBy('student_id')
                ->selectRaw('student_id, MAX(created_at) as last_enabled')
                ->pluck('last_enabled', 'student_id');

            $map = $map->reject(fn (GradeClosure $closure, $studentId) => isset($enabledAt[$studentId])
                && $enabledAt[$studentId] >= $closure->closed_at->toDateTimeString());
        }

        return $this->batchMap = $map;
    }

    private ?Collection $batchMap = null;
}
