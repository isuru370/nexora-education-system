<?php

namespace App\Services\Web\Student;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentAutoDisable;
use App\Models\StudentClassEnrollment;
use App\Services\Branch\BranchAccessService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Students made inactive by the nightly job disable:expired-students:
 * the record made at that moment, the list / PDF and "Re-activate".
 */
class StudentAutoDisableService
{
    public function __construct(protected BranchAccessService $branchAccess) {}

    /* ------------------------------------------------------------------ */
    /* Used by the job                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Disable one student, take them out of their active classes and keep a
     * record of the classes, last attendance and last payment.
     */
    public function disable(Student $student, CarbonInterface $now): StudentAutoDisable
    {
        return DB::transaction(function () use ($student, $now) {
            $enrollments = StudentClassEnrollment::query()
                ->with(['studentClass' => fn ($q) => $q->withTrashed()->with(['grade', 'teacher' => fn ($t) => $t->withTrashed()]), 'classCategoryFee.category'])
                ->where('student_id', $student->id)
                ->where('is_active', true)
                ->whereNull('left_at')
                ->get();

            $attendance = StudentAttendance::query()
                ->with(['enrollment.studentClass' => fn ($q) => $q->withTrashed()])
                ->where('student_id', $student->id)
                ->latest('attended_at')
                ->first();

            $payment = Payment::query()
                ->with(['enrollment.studentClass' => fn ($q) => $q->withTrashed()])
                ->where('student_id', $student->id)
                ->where('status', '!=', 'cancelled')
                ->latest('paid_at')
                ->first();

            $record = StudentAutoDisable::create([
                'student_id' => $student->id,
                'branch_id' => $student->branch_id,
                'disabled_at' => $now,
                'classes' => $enrollments->map(fn (StudentClassEnrollment $e) => [
                    'enrollment_id' => $e->id,
                    'class_name' => optional($e->studentClass)->class_name,
                    'grade' => optional(optional($e->studentClass)->grade)->grade_name,
                    'teacher' => optional(optional($e->studentClass)->teacher)->full_name,
                    'category' => optional(optional($e->classCategoryFee)->category)->name,
                    'enrolled_at' => optional($e->enrolled_at)->format('Y-m-d'),
                ])->values()->all(),
                'last_attendance_at' => optional($attendance)->attended_at,
                'last_attendance_class' => optional(optional(optional($attendance)->enrollment)->studentClass)->class_name,
                'last_payment_at' => optional($payment)->paid_at,
                'last_payment_amount' => optional($payment)->amount,
                'last_payment_month' => optional($payment)->payment_month,
                'last_payment_class' => optional(optional(optional($payment)->enrollment)->studentClass)->class_name,
            ]);

            $student->update(['student_disable' => true, 'is_active' => false]);

            StudentClassEnrollment::query()
                ->whereIn('id', $enrollments->pluck('id'))
                ->update(['is_active' => false, 'left_at' => $now->toDateString()]);

            return $record;
        });
    }

    /* ------------------------------------------------------------------ */
    /* List                                                                */
    /* ------------------------------------------------------------------ */

    /**
     * @param  array{from?: string, to?: string, search?: string, status?: string, branch_id?: int}  $filters
     */
    public function query(array $filters): Builder
    {
        $branchIds = $this->branchAccess->getBranchIds()->map(fn ($id) => (int) $id);

        [$from, $to] = $this->range($filters);
        $search = trim((string) ($filters['search'] ?? ''));

        return StudentAutoDisable::query()
            ->with(['student.grade', 'student.branch', 'reactivatedBy:id,name'])
            ->where(fn ($q) => $q->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))
            ->when(!empty($filters['branch_id']), fn ($q) => $q->where('branch_id', (int) $filters['branch_id']))
            ->whereBetween('disabled_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when(($filters['status'] ?? '') === 'inactive', fn ($q) => $q->whereNull('reactivated_at'))
            ->when(($filters['status'] ?? '') === 'reactivated', fn ($q) => $q->whereNotNull('reactivated_at'))
            ->when($search !== '', fn ($q) => $q->whereHas('student', fn ($s) => $s->withTrashed()->where(function ($w) use ($search) {
                $w->where('custom_id', 'like', "%{$search}%")
                    ->orWhere('initial_name', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('guardian_mobile', 'like', "%{$search}%");
            })))
            ->orderByDesc('disabled_at')
            ->orderBy('id');
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    public function all(array $filters): Collection
    {
        return $this->query($filters)->get();
    }

    /**
     * Default range: the day of the last run (where the user can see students).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function range(array $filters): array
    {
        $from = !empty($filters['from']) ? Carbon::parse($filters['from']) : null;
        $to = !empty($filters['to']) ? Carbon::parse($filters['to']) : null;

        if (!$from && !$to) {
            $last = $this->lastRun();
            $day = $last ? Carbon::parse($last['date']) : now();

            return [$day->copy(), $day->copy()];
        }

        return [$from ?? $to->copy(), $to ?? now()];
    }

    /**
     * The last night that disabled students the user can see.
     *
     * @return array{date: string, count: int, still_inactive: int}|null
     */
    public function lastRun(): ?array
    {
        $branchIds = $this->branchAccess->getBranchIds()->map(fn ($id) => (int) $id);

        $last = StudentAutoDisable::query()
            ->where(fn ($q) => $q->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))
            ->max('disabled_at');

        if (!$last) {
            return null;
        }

        $day = Carbon::parse($last)->toDateString();
        $rows = StudentAutoDisable::query()
            ->where(fn ($q) => $q->whereIn('branch_id', $branchIds)->orWhereNull('branch_id'))
            ->whereDate('disabled_at', $day);

        return [
            'date' => $day,
            'count' => (clone $rows)->count(),
            'still_inactive' => (clone $rows)->whereNull('reactivated_at')->count(),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Re-activate                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Make the student active again and put them back in the classes the job
     * took them out of (only those still left on that day).
     *
     * @return array{student: Student, classes: int}
     */
    public function reactivate(int $id, bool $checkAccess = true): array
    {
        return DB::transaction(function () use ($id, $checkAccess) {
            /** @var StudentAutoDisable $record */
            $record = StudentAutoDisable::query()->lockForUpdate()->findOrFail($id);

            if ($checkAccess && $record->branch_id && !$this->branchAccess->canAccessBranch((int) $record->branch_id)) {
                throw new DomainException('You do not have access to this branch.');
            }
            if ($record->reactivated_at) {
                throw new DomainException('This student was already re-activated on ' . $record->reactivated_at->format('d M Y') . '.');
            }

            $student = Student::query()->findOrFail($record->student_id);
            $student->update(['student_disable' => false, 'is_active' => true]);

            $ids = collect($record->classes ?? [])->pluck('enrollment_id')->filter()->all();
            $classes = StudentClassEnrollment::query()
                ->whereIn('id', $ids)
                ->where('is_active', false)
                ->whereDate('left_at', $record->disabled_at->toDateString())
                ->update(['is_active' => true, 'left_at' => null]);

            $record->update(['reactivated_at' => now(), 'reactivated_by' => auth()->id()]);

            return ['student' => $student, 'classes' => $classes];
        });
    }
}
