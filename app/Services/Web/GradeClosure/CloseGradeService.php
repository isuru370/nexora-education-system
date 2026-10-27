<?php

namespace App\Services\Web\GradeClosure;

use App\Models\GradeClosure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * "Close Batch": end of a batch (e.g. 2026 A/L after the exam).
 *
 * For the chosen grades:
 *  - classes                       -> inactive
 *  - their active enrollments      -> inactive, left_at = today
 *  - schedule patterns             -> inactive
 *  - future "scheduled" sessions   -> cancelled ("Grade closed")
 *  - students of the grades        -> is_active = false, student_disable = true
 *
 * A student of these grades who is still in an active class of another grade
 * (e.g. an "ALL" class) stays active, with that class.
 *
 * Nothing is deleted (payments, attendance stay). Each close is saved in
 * grade_closures with the old values, so it can be viewed and undone.
 *
 * Used by the web page (CloseGradeController) and php artisan nexora:close-grade.
 */
class CloseGradeService
{
    public const REASON = 'Grade closed';

    /** Old runs of the command were saved as files here (before the table). */
    private const OLD_FILES = 'close-grade';

    /**
     * Grades with their active classes / active students, for the checkboxes.
     */
    public function grades(): Collection
    {
        return DB::table('grades')
            ->select('grades.id', 'grades.grade_name')
            ->selectSub(
                DB::table('student_classes')->whereColumn('grade_id', 'grades.id')
                    ->whereNull('deleted_at')->where('is_active', true)->selectRaw('count(*)'),
                'active_classes'
            )
            ->selectSub(
                DB::table('students')->whereColumn('grade_id', 'grades.id')
                    ->whereNull('deleted_at')->where('is_active', true)->selectRaw('count(*)'),
                'active_students'
            )
            ->orderBy('grades.grade_name')
            ->get();
    }

    /**
     * Grade ids for grade names; error for a name that is not there.
     */
    public function gradeIdsFromNames(array $names): array
    {
        $names = array_values(array_unique(array_filter(array_map('trim', $names))));
        $grades = DB::table('grades')->whereIn('grade_name', $names)->pluck('id', 'grade_name');
        $missing = array_diff($names, $grades->keys()->all());

        if ($missing) {
            throw ValidationException::withMessages([
                'grades' => 'Grade not found: ' . implode(', ', $missing),
            ]);
        }

        return $grades->values()->all();
    }

    /**
     * What a close would change (nothing is changed).
     *
     * @return array{grades: array, classes: Collection, enrollments: Collection, patterns: Collection,
     *               schedules: Collection, close: Collection, keep: Collection}
     */
    public function preview(array $gradeIds): array
    {
        $gradeIds = array_values(array_unique(array_map('intval', $gradeIds)));
        $gradeNames = DB::table('grades')->whereIn('id', $gradeIds)->orderBy('grade_name')->pluck('grade_name')->all();

        if (! $gradeIds || count($gradeNames) !== count($gradeIds)) {
            throw ValidationException::withMessages(['grade_ids' => 'Select at least one grade.']);
        }

        $today = now()->toDateString();

        $classIds = DB::table('student_classes')->whereNull('deleted_at')
            ->whereIn('grade_id', $gradeIds)->pluck('id')->all();

        $classes = DB::table('student_classes')->whereIn('id', $classIds)
            ->where('is_active', true)->orderBy('class_name')
            ->get(['id', 'class_name', 'is_active']);

        $enrollments = DB::table('student_class_enrollments')->whereNull('deleted_at')
            ->whereIn('student_class_id', $classIds)->where('is_active', true)
            ->get(['id', 'is_active', 'left_at']);

        $patterns = DB::table('class_schedule_patterns')->whereNull('deleted_at')
            ->whereIn('student_class_id', $classIds)->where('is_active', true)
            ->get(['id', 'is_active']);

        $schedules = DB::table('class_schedules')->whereNull('deleted_at')
            ->whereIn('student_class_id', $classIds)
            ->where('status', 'scheduled')->where('class_date', '>=', $today)
            ->get(['id', 'status', 'cancel_reason', 'cancelled_at']);

        $students = DB::table('students')->whereNull('deleted_at')
            ->whereIn('grade_id', $gradeIds)
            ->where(fn ($q) => $q->where('is_active', true)->orWhere('student_disable', false)->orWhereNull('student_disable'))
            ->orderBy('custom_id')
            ->get(['id', 'custom_id', 'initial_name', 'is_active', 'student_disable']);

        // still in an active class of another grade -> stays active
        $otherClasses = DB::table('student_class_enrollments as e')
            ->join('student_classes as c', 'c.id', '=', 'e.student_class_id')
            ->whereNull('e.deleted_at')->whereNull('c.deleted_at')
            ->where('e.is_active', true)->where('c.is_active', true)
            ->whereNotIn('c.id', $classIds)
            ->whereIn('e.student_id', $students->pluck('id'))
            ->get(['e.student_id', 'c.class_name'])
            ->groupBy('student_id');

        $keep = $students->filter(fn ($s) => $otherClasses->has($s->id))
            ->map(fn ($s) => [
                'id' => $s->id,
                'custom_id' => $s->custom_id,
                'name' => $s->initial_name,
                'other_classes' => $otherClasses[$s->id]->pluck('class_name')->unique()->implode(', '),
            ])->values();

        return [
            'grades' => $gradeNames,
            'classes' => $classes,
            'enrollments' => $enrollments,
            'patterns' => $patterns,
            'schedules' => $schedules,
            'close' => $students->reject(fn ($s) => $otherClasses->has($s->id))->values(),
            'keep' => $keep,
        ];
    }

    /**
     * Close the grades; returns the saved run.
     */
    public function close(array $gradeIds, ?int $userId = null, string $source = 'web'): GradeClosure
    {
        $p = $this->preview($gradeIds);
        $now = now();
        $today = $now->toDateString();

        return DB::transaction(function () use ($p, $now, $today, $userId, $source) {
            DB::table('student_classes')->whereIn('id', $p['classes']->pluck('id'))
                ->update(['is_active' => false, 'updated_at' => $now]);

            foreach ($p['enrollments']->chunk(500) as $chunk) {
                $ids = $chunk->pluck('id');
                DB::table('student_class_enrollments')->whereIn('id', $ids)->update(['is_active' => false, 'updated_at' => $now]);
                DB::table('student_class_enrollments')->whereIn('id', $ids)->whereNull('left_at')->update(['left_at' => $today]);
            }

            DB::table('class_schedule_patterns')->whereIn('id', $p['patterns']->pluck('id'))
                ->update(['is_active' => false, 'updated_at' => $now]);

            foreach ($p['schedules']->chunk(500) as $chunk) {
                DB::table('class_schedules')->whereIn('id', $chunk->pluck('id'))->update([
                    'status' => 'cancelled', 'cancel_reason' => self::REASON,
                    'cancelled_at' => $now, 'updated_at' => $now,
                ]);
            }

            foreach ($p['close']->chunk(500) as $chunk) {
                DB::table('students')->whereIn('id', $chunk->pluck('id'))
                    ->update(['is_active' => false, 'student_disable' => true, 'updated_at' => $now]);
            }

            $rows = fn (Collection $c, array $keys) => $c->map(fn ($r) => array_intersect_key((array) $r, array_flip($keys)))->values()->all();

            return GradeClosure::create([
                'grade_names' => $p['grades'],
                'counts' => [
                    'classes' => $p['classes']->count(),
                    'enrollments' => $p['enrollments']->count(),
                    'patterns' => $p['patterns']->count(),
                    'sessions' => $p['schedules']->count(),
                    'students' => $p['close']->count(),
                    'kept' => $p['keep']->count(),
                ],
                'changes' => [
                    'classes' => $rows($p['classes'], ['id', 'is_active']),
                    'enrollments' => $rows($p['enrollments'], ['id', 'is_active', 'left_at']),
                    'patterns' => $rows($p['patterns'], ['id', 'is_active']),
                    'schedules' => $rows($p['schedules'], ['id', 'status', 'cancel_reason', 'cancelled_at']),
                    'students' => $rows($p['close'], ['id', 'is_active', 'student_disable']),
                ],
                'kept_students' => $p['keep']->all(),
                'source' => $source,
                'closed_by' => $userId,
                'closed_at' => $now,
            ]);
        });
    }

    /**
     * Put a close back. The newest close must be undone first.
     */
    public function undo(GradeClosure $closure, ?int $userId = null): GradeClosure
    {
        if ($closure->isUndone()) {
            throw ValidationException::withMessages(['closure' => 'This close was already undone.']);
        }

        $newer = GradeClosure::whereNull('undone_at')->where('id', '>', $closure->id)->exists();
        if ($newer) {
            throw ValidationException::withMessages(['closure' => 'Undo the newer close first.']);
        }

        $c = $closure->changes;
        $now = now();

        DB::transaction(function () use ($c, $now, $closure, $userId) {
            foreach ($c['classes'] ?? [] as $r) {
                DB::table('student_classes')->where('id', $r['id'])
                    ->update(['is_active' => (bool) $r['is_active'], 'updated_at' => $now]);
            }
            foreach ($c['enrollments'] ?? [] as $r) {
                DB::table('student_class_enrollments')->where('id', $r['id'])
                    ->update(['is_active' => (bool) $r['is_active'], 'left_at' => $r['left_at'], 'updated_at' => $now]);
            }
            foreach ($c['patterns'] ?? [] as $r) {
                DB::table('class_schedule_patterns')->where('id', $r['id'])
                    ->update(['is_active' => (bool) $r['is_active'], 'updated_at' => $now]);
            }
            foreach ($c['schedules'] ?? [] as $r) {
                // only sessions still cancelled by the close
                DB::table('class_schedules')->where('id', $r['id'])
                    ->where('status', 'cancelled')->where('cancel_reason', self::REASON)
                    ->update([
                        'status' => $r['status'], 'cancel_reason' => $r['cancel_reason'],
                        'cancelled_at' => $r['cancelled_at'], 'updated_at' => $now,
                    ]);
            }
            foreach ($c['students'] ?? [] as $r) {
                DB::table('students')->where('id', $r['id'])->update([
                    'is_active' => (bool) $r['is_active'],
                    'student_disable' => (bool) $r['student_disable'],
                    'updated_at' => $now,
                ]);
            }

            $closure->update(['undone_at' => $now, 'undone_by' => $userId]);
        });

        return $closure->fresh();
    }

    /**
     * Classes and students of a close, for its details page.
     */
    public function details(GradeClosure $closure): array
    {
        $c = $closure->changes;

        $classes = DB::table('student_classes as c')
            ->leftJoin('teachers as t', 't.id', '=', 'c.teacher_id')
            ->whereIn('c.id', collect($c['classes'] ?? [])->pluck('id'))
            ->orderBy('c.class_name')
            ->get(['c.id', 'c.class_name', 'c.is_active', 't.full_name as teacher']);

        $students = collect();
        foreach (collect($c['students'] ?? [])->pluck('id')->chunk(1000) as $ids) {
            $students = $students->concat(
                DB::table('students as s')
                    ->leftJoin('grades as g', 'g.id', '=', 's.grade_id')
                    ->whereIn('s.id', $ids)
                    ->get(['s.id', 's.custom_id', 's.initial_name', 's.mobile', 's.guardian_mobile', 's.is_active', 'g.grade_name'])
            );
        }

        return [
            'classes' => $classes,
            'students' => $students->sortBy('custom_id')->values(),
            'kept' => collect($closure->kept_students ?? []),
        ];
    }

    /**
     * Runs of the command saved as files (storage/app/close-grade/*.json,
     * before this table) are added to the history once.
     */
    public function importOldFiles(): int
    {
        $added = 0;

        foreach (collect(Storage::files(self::OLD_FILES))->sort() as $file) {
            if (! str_ends_with($file, '.json')) {
                continue;
            }

            $run = json_decode((string) Storage::get($file), true);
            if (! is_array($run) || ! isset($run['grades'], $run['at'])) {
                continue;
            }

            GradeClosure::create([
                'grade_names' => $run['grades'],
                'counts' => [
                    'classes' => count($run['classes'] ?? []),
                    'enrollments' => count($run['enrollments'] ?? []),
                    'patterns' => count($run['patterns'] ?? []),
                    'sessions' => count($run['schedules'] ?? []),
                    'students' => count($run['students'] ?? []),
                    'kept' => 0,
                ],
                'changes' => [
                    'classes' => $run['classes'] ?? [],
                    'enrollments' => $run['enrollments'] ?? [],
                    'patterns' => $run['patterns'] ?? [],
                    'schedules' => $run['schedules'] ?? [],
                    'students' => $run['students'] ?? [],
                ],
                'kept_students' => [],
                'source' => 'file',
                'closed_at' => $run['at'],
            ]);

            Storage::move($file, $file . '.imported');
            $added++;
        }

        return $added;
    }
}
