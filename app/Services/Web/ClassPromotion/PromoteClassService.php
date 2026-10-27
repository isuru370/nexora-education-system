<?php

namespace App\Services\Web\ClassPromotion;

use App\Models\ClassPromotion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Promote Class": at the end of the year, e.g. "Aa class - Grade 9" goes on
 * as "Aa class - Grade 10".
 *
 * The old class is NOT renamed (so old reports / receipts still say Grade 9).
 * Instead, from a start date:
 *
 *  New class (made now)
 *   - same branch, subject, medium, type; new name, grade, teacher
 *   - fee categories + options and the teacher % (payment config) copied
 *   - schedule rows (day, time, hall, category) from start date to
 *     "schedule until", and their sessions
 *   - the ticked students enrolled from the start date (same category,
 *     fee option and free card as before)
 *
 *  Old class
 *   - schedule ends the day before the start date; its sessions from the
 *     start date are cancelled
 *   - every active enrollment ends the day before the start date
 *   - ON the start date (now if it is today or before; else the daily
 *     command nexora:apply-class-promotions or opening the page):
 *     the old class and its enrollments become inactive and every moved
 *     student gets the new grade
 *
 * Nothing is deleted. Saved in class_promotions, so it can be undone while
 * the new class has no payments or attendance.
 */
class PromoteClassService
{
    public const CANCEL_REASON = 'Class promoted';
    private const MAX_SCHEDULE_DAYS = 550;

    // ---------------------------------------------------------------------
    // Data for the page
    // ---------------------------------------------------------------------

    /** Active classes for the "which class" list. */
    public function classes(): Collection
    {
        return DB::table('student_classes as c')
            ->leftJoin('grades as g', 'g.id', '=', 'c.grade_id')
            ->leftJoin('teachers as t', 't.id', '=', 'c.teacher_id')
            ->leftJoin('branches as b', 'b.id', '=', 'c.branch_id')
            ->whereNull('c.deleted_at')
            ->where('c.is_active', true)
            ->select('c.id', 'c.class_name', 'g.grade_name', 't.full_name as teacher', 'b.name as branch')
            ->selectSub(
                DB::table('student_class_enrollments')->whereColumn('student_class_id', 'c.id')
                    ->whereNull('deleted_at')->where('is_active', true)
                    ->selectRaw('count(distinct student_id)'),
                'students'
            )
            ->orderBy('g.grade_name')->orderBy('c.class_name')
            ->get();
    }

    /**
     * Everything the promote form needs for one class.
     */
    public function formData(int $classId, ?string $startDate = null): array
    {
        $class = $this->oldClass($classId);
        $start = $this->startDate($startDate);

        $grades = DB::table('grades')->orderBy('grade_name')->get(['id', 'grade_name']);
        $nextGrade = $this->nextGrade($class->grade_name, $grades);

        return [
            'class' => $class,
            'start_date' => $start->toDateString(),
            'schedule_until' => $start->copy()->addYear()->subDay()->toDateString(),
            'suggested_name' => $this->renameForYear(
                $nextGrade
                    ? $this->renameForGrade($class->class_name, (string) $class->grade_name, $nextGrade->grade_name)
                    : $class->class_name,
                $start->year
            ),
            'suggested_grade_id' => $nextGrade->id ?? $class->grade_id,
            'grades' => $grades,
            'teachers' => DB::table('teachers')->whereNull('deleted_at')->where('is_active', true)
                ->orderBy('full_name')->get(['id', 'full_name']),
            'halls' => DB::table('class_halls')->whereNull('deleted_at')->where('is_active', true)
                ->where('branch_id', $class->branch_id)->orderBy('hall_name')->get(['id', 'hall_name']),
            'categories' => $this->categories($classId),
            'students' => $this->students($classId, $start),
            'schedules' => $this->currentSchedule($classId),
            'has_payment_config' => (bool) $this->paymentConfig($classId),
        ];
    }

    // ---------------------------------------------------------------------
    // Promote
    // ---------------------------------------------------------------------

    /**
     * @param array $input class_name, grade_id, teacher_id, start_date, schedule_until,
     *                     student_ids[], schedules[] (class_day, start_time, end_time,
     *                     class_hall_id, class_category_fee_id = the OLD class's category)
     */
    public function promote(int $fromClassId, array $input, ?int $userId = null): ClassPromotion
    {
        $old = $this->oldClass($fromClassId);

        $start = Carbon::parse($input['start_date'])->startOfDay();
        $until = Carbon::parse($input['schedule_until'])->startOfDay();
        if ($until->lt($start)) {
            throw ValidationException::withMessages(['schedule_until' => 'Schedule until must be on or after the start date.']);
        }
        if ($start->diffInDays($until) > self::MAX_SCHEDULE_DAYS) {
            throw ValidationException::withMessages(['schedule_until' => 'Schedule until can be at most about 18 months after the start date.']);
        }

        $dayBefore = $start->copy()->subDay()->toDateString();
        $today = now()->toDateString();
        $now = now();

        $oldEnrollments = DB::table('student_class_enrollments')
            ->whereNull('deleted_at')->where('student_class_id', $old->id)->where('is_active', true)
            ->get(['id', 'student_id', 'class_category_fee_id', 'class_category_fee_option_id', 'is_free_card', 'is_active', 'left_at', 'branch_id']);

        $movedIds = collect($input['student_ids'] ?? [])->map(fn ($id) => (int) $id)
            ->intersect($oldEnrollments->pluck('student_id'))->unique()->values();

        if ($movedIds->isEmpty()) {
            throw ValidationException::withMessages(['student_ids' => 'Tick at least one student to move to the new class.']);
        }

        $categoryIds = $this->categories($old->id)->pluck('id')->map(fn ($id) => (int) $id);
        $rows = collect($input['schedules'] ?? [])
            ->filter(fn ($r) => ! empty($r['include']) || ! array_key_exists('include', $r))
            ->values();
        foreach ($rows as $r) {
            if (! $categoryIds->contains((int) ($r['class_category_fee_id'] ?? 0))) {
                throw ValidationException::withMessages(['schedules' => 'A schedule row has a category that is not in this class.']);
            }
        }

        return DB::transaction(function () use ($old, $input, $start, $until, $dayBefore, $today, $now, $oldEnrollments, $movedIds, $rows, $userId) {
            $changes = [];

            // ---- new class
            $newClassId = DB::table('student_classes')->insertGetId([
                'branch_id' => $old->branch_id,
                'class_name' => trim($input['class_name']),
                'class_type' => $old->class_type,
                'medium' => $old->medium,
                'teacher_id' => (int) $input['teacher_id'],
                'subject_id' => $old->subject_id,
                'grade_id' => (int) $input['grade_id'],
                'is_active' => true,
                'is_ongoing' => (bool) $old->is_ongoing,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // ---- fee categories + options
            $feeMap = [];
            $optionMap = [];
            foreach (DB::table('class_category_fees')->whereNull('deleted_at')->where('student_class_id', $old->id)->get() as $fee) {
                $feeMap[$fee->id] = DB::table('class_category_fees')->insertGetId([
                    'student_class_id' => $newClassId,
                    'class_category_id' => $fee->class_category_id,
                    'is_active' => $fee->is_active,
                    'note' => $fee->note,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach (DB::table('class_category_fee_options')->whereNull('deleted_at')->where('class_category_fee_id', $fee->id)->get() as $option) {
                    $optionMap[$option->id] = DB::table('class_category_fee_options')->insertGetId([
                        'class_category_fee_id' => $feeMap[$fee->id],
                        'label' => $option->label,
                        'fee' => $option->fee,
                        'is_default' => $option->is_default,
                        'is_active' => $option->is_active,
                        'note' => $option->note,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
            $changes['new_fees'] = array_values($feeMap);
            $changes['new_options'] = array_values($optionMap);

            // ---- teacher % (from today, so payments taken before the start date work too)
            $changes['new_configs'] = [];
            if ($config = $this->paymentConfig($old->id)) {
                $changes['new_configs'][] = DB::table('class_payment_configs')->insertGetId([
                    'branch_id' => $old->branch_id,
                    'student_class_id' => $newClassId,
                    'teacher_id' => (int) $input['teacher_id'],
                    'organizer_id' => $config->organizer_id,
                    'teacher_percentage' => $config->teacher_percentage,
                    'organizer_percentage' => $config->organizer_percentage,
                    'institution_percentage' => $config->institution_percentage,
                    'effective_from' => min($today, $start->toDateString()),
                    'effective_to' => null,
                    'is_active' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // ---- new schedule + sessions
            $changes['new_patterns'] = [];
            $sessions = 0;
            foreach ($rows as $r) {
                $pattern = [
                    'branch_id' => $old->branch_id,
                    'student_class_id' => $newClassId,
                    'class_category_fee_id' => $feeMap[(int) $r['class_category_fee_id']],
                    'class_hall_id' => ! empty($r['class_hall_id']) ? (int) $r['class_hall_id'] : null,
                    'start_date' => $start->toDateString(),
                    'end_date' => $until->toDateString(),
                    'start_time' => $this->time($r['start_time']),
                    'end_time' => $this->time($r['end_time']),
                    'class_day' => strtolower($r['class_day']),
                    'is_active' => true,
                    'note' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $pattern['id'] = DB::table('class_schedule_patterns')->insertGetId($pattern);
                $changes['new_patterns'][] = $pattern['id'];
                $sessions += $this->makeSessions($pattern, $now);
            }

            // ---- old schedule ends the day before
            $changes['old_patterns'] = [];
            foreach (DB::table('class_schedule_patterns')->whereNull('deleted_at')->where('student_class_id', $old->id)
                ->where('is_active', true)->where('end_date', '>=', $start->toDateString())
                ->get(['id', 'start_date', 'end_date', 'is_active']) as $p) {
                $changes['old_patterns'][] = ['id' => $p->id, 'end_date' => $p->end_date, 'is_active' => (bool) $p->is_active];

                $update = substr((string) $p->start_date, 0, 10) >= $start->toDateString()
                    ? ['is_active' => false]
                    : ['end_date' => $dayBefore];
                DB::table('class_schedule_patterns')->where('id', $p->id)->update($update + ['updated_at' => $now]);
            }

            $oldSessions = DB::table('class_schedules')->whereNull('deleted_at')->where('student_class_id', $old->id)
                ->where('status', 'scheduled')->where('class_date', '>=', $start->toDateString())
                ->get(['id', 'status', 'cancel_reason', 'cancelled_at']);
            $changes['old_sessions'] = $oldSessions->map(fn ($s) => (array) $s)->values()->all();
            foreach ($oldSessions->pluck('id')->chunk(500) as $ids) {
                DB::table('class_schedules')->whereIn('id', $ids)->update([
                    'status' => 'cancelled',
                    'cancel_reason' => self::CANCEL_REASON,
                    'cancelled_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // ---- enrollments: old ones end the day before; moved students join the new class
            $changes['old_enrollments'] = $oldEnrollments
                ->map(fn ($e) => ['id' => $e->id, 'is_active' => (bool) $e->is_active, 'left_at' => $e->left_at])
                ->values()->all();
            foreach ($oldEnrollments as $e) {
                if ($e->left_at === null || substr((string) $e->left_at, 0, 10) > $dayBefore) {
                    DB::table('student_class_enrollments')->where('id', $e->id)->update(['left_at' => $dayBefore, 'updated_at' => $now]);
                }
            }

            $changes['new_enrollments'] = [];
            foreach ($oldEnrollments->whereIn('student_id', $movedIds->all()) as $e) {
                $changes['new_enrollments'][] = DB::table('student_class_enrollments')->insertGetId([
                    'branch_id' => $e->branch_id,
                    'student_id' => $e->student_id,
                    'student_class_id' => $newClassId,
                    'class_category_fee_id' => $feeMap[$e->class_category_fee_id] ?? null,
                    'class_category_fee_option_id' => $optionMap[$e->class_category_fee_option_id] ?? null,
                    'is_active' => true,
                    'is_free_card' => $e->is_free_card,
                    'enrolled_at' => $start->toDateString(),
                    'left_at' => null,
                    'note' => 'Promoted from ' . $old->class_name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // ---- moved students get the new grade on the start date (also those
            //      whose grade was not up to date, e.g. still 9 in a grade 10 class)
            $newGrade = (int) $input['grade_id'];
            $changes['students'] = $newGrade === (int) $old->grade_id ? [] : DB::table('students')
                ->whereIn('id', $movedIds)
                ->where(fn ($q) => $q->where('grade_id', '!=', $newGrade)->orWhereNull('grade_id'))
                ->get(['id', 'grade_id'])->map(fn ($s) => ['id' => $s->id, 'grade_id' => $s->grade_id])->values()->all();
            $changes['new_grade_id'] = $newGrade;
            $changes['old_class_active'] = (bool) $old->is_active;

            $promotion = ClassPromotion::create([
                'from_class_id' => $old->id,
                'to_class_id' => $newClassId,
                'from_class_name' => $old->class_name,
                'to_class_name' => trim($input['class_name']),
                'start_date' => $start->toDateString(),
                'schedule_until' => $until->toDateString(),
                'status' => ClassPromotion::SCHEDULED,
                'counts' => [
                    'moved' => $movedIds->count(),
                    'left' => $oldEnrollments->pluck('student_id')->unique()->count() - $movedIds->count(),
                    'enrollments' => count($changes['new_enrollments']),
                    'schedules' => count($changes['new_patterns']),
                    'sessions' => $sessions,
                    'cancelled' => count($changes['old_sessions']),
                    'grade_changes' => count($changes['students']),
                ],
                'changes' => $changes,
                'promoted_by' => $userId,
            ]);

            if ($start->toDateString() <= $today) {
                $this->apply($promotion);
            }

            return $promotion->fresh();
        });
    }

    // ---------------------------------------------------------------------
    // On the start date
    // ---------------------------------------------------------------------

    /** Promotions whose start date has come (daily command + page open). */
    public function applyDue(): int
    {
        $due = ClassPromotion::where('status', ClassPromotion::SCHEDULED)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->orderBy('id')->get();

        foreach ($due as $promotion) {
            $this->apply($promotion);
        }

        return $due->count();
    }

    public function apply(ClassPromotion $promotion): void
    {
        if ($promotion->status !== ClassPromotion::SCHEDULED) {
            return;
        }

        $c = $promotion->changes;
        $now = now();

        DB::transaction(function () use ($promotion, $c, $now) {
            DB::table('student_classes')->where('id', $promotion->from_class_id)
                ->update(['is_active' => false, 'updated_at' => $now]);

            foreach (collect($c['old_enrollments'] ?? [])->pluck('id')->chunk(500) as $ids) {
                DB::table('student_class_enrollments')->whereIn('id', $ids)
                    ->update(['is_active' => false, 'updated_at' => $now]);
            }

            foreach (collect($c['students'] ?? [])->pluck('id')->chunk(500) as $ids) {
                DB::table('students')->whereIn('id', $ids)
                    ->update(['grade_id' => $c['new_grade_id'], 'updated_at' => $now]);
            }

            $promotion->update(['status' => ClassPromotion::DONE, 'applied_at' => $now]);
        });
    }

    // ---------------------------------------------------------------------
    // Undo
    // ---------------------------------------------------------------------

    /** Why it cannot be undone, or null. */
    public function undoBlocker(ClassPromotion $promotion): ?string
    {
        if ($promotion->status === ClassPromotion::UNDONE) {
            return 'This promotion was already undone.';
        }

        $newer = ClassPromotion::where('from_class_id', $promotion->to_class_id)
            ->where('status', '!=', ClassPromotion::UNDONE)->exists();
        if ($newer) {
            return 'The new class was promoted again: undo that one first.';
        }

        $enrollments = $promotion->changes['new_enrollments'] ?? [];
        if ($enrollments && DB::table('payments')->whereIn('student_class_enrollment_id', $enrollments)->exists()) {
            return 'The new class already has payments: it cannot be undone.';
        }

        $hasAttendance = DB::table('student_attendances as a')
            ->join('class_schedules as s', 's.id', '=', 'a.class_schedule_id')
            ->where('s.student_class_id', $promotion->to_class_id)
            ->exists();
        if ($hasAttendance) {
            return 'The new class already has attendance: it cannot be undone.';
        }

        return null;
    }

    public function undo(ClassPromotion $promotion, ?int $userId = null): ClassPromotion
    {
        if ($reason = $this->undoBlocker($promotion)) {
            throw ValidationException::withMessages(['promotion' => $reason]);
        }

        $c = $promotion->changes;
        $now = now();

        DB::transaction(function () use ($promotion, $c, $now, $userId) {
            // ---- the new class goes away (soft delete, nothing used it yet)
            $gone = ['is_active' => false, 'deleted_at' => $now, 'updated_at' => $now];
            DB::table('class_schedules')->where('student_class_id', $promotion->to_class_id)->whereNull('deleted_at')->update($gone);
            DB::table('class_schedule_patterns')->whereIn('id', $c['new_patterns'] ?? [])->update($gone);
            DB::table('student_class_enrollments')->whereIn('id', $c['new_enrollments'] ?? [])->update($gone);
            DB::table('class_payment_configs')->whereIn('id', $c['new_configs'] ?? [])->update($gone);
            DB::table('class_category_fee_options')->whereIn('id', $c['new_options'] ?? [])->update($gone);
            DB::table('class_category_fees')->whereIn('id', $c['new_fees'] ?? [])->update($gone);
            DB::table('student_classes')->where('id', $promotion->to_class_id)->update($gone);

            // ---- the old class as it was
            DB::table('student_classes')->where('id', $promotion->from_class_id)
                ->update(['is_active' => $c['old_class_active'] ?? true, 'updated_at' => $now]);

            foreach ($c['old_enrollments'] ?? [] as $e) {
                DB::table('student_class_enrollments')->where('id', $e['id'])
                    ->update(['is_active' => $e['is_active'], 'left_at' => $e['left_at'], 'updated_at' => $now]);
            }

            foreach ($c['old_patterns'] ?? [] as $p) {
                DB::table('class_schedule_patterns')->where('id', $p['id'])
                    ->update(['end_date' => $p['end_date'], 'is_active' => $p['is_active'], 'updated_at' => $now]);
            }

            foreach ($c['old_sessions'] ?? [] as $s) {
                DB::table('class_schedules')->where('id', $s['id'])
                    ->where('status', 'cancelled')->where('cancel_reason', self::CANCEL_REASON)
                    ->update([
                        'status' => $s['status'],
                        'cancel_reason' => $s['cancel_reason'],
                        'cancelled_at' => $s['cancelled_at'],
                        'updated_at' => $now,
                    ]);
            }

            foreach ($c['students'] ?? [] as $s) {
                DB::table('students')->where('id', $s['id'])->where('grade_id', $c['new_grade_id'])
                    ->update(['grade_id' => $s['grade_id'], 'updated_at' => $now]);
            }

            $promotion->update([
                'status' => ClassPromotion::UNDONE,
                'undone_at' => $now,
                'undone_by' => $userId,
            ]);
        });

        return $promotion->fresh();
    }

    // ---------------------------------------------------------------------
    // Details page
    // ---------------------------------------------------------------------

    public function details(ClassPromotion $promotion): array
    {
        $c = $promotion->changes;

        $students = DB::table('student_class_enrollments as e')
            ->join('students as s', 's.id', '=', 'e.student_id')
            ->leftJoin('grades as g', 'g.id', '=', 's.grade_id')
            ->whereIn('e.id', collect($c['old_enrollments'] ?? [])->pluck('id'))
            ->select('s.id', 's.custom_id', 's.initial_name', 'g.grade_name')
            ->distinct()->orderBy('s.custom_id')->get();

        $movedIds = DB::table('student_class_enrollments')->whereIn('id', $c['new_enrollments'] ?? [])
            ->pluck('student_id')->unique()->all();

        $schedules = DB::table('class_schedule_patterns as p')
            ->leftJoin('class_halls as h', 'h.id', '=', 'p.class_hall_id')
            ->leftJoin('class_category_fees as f', 'f.id', '=', 'p.class_category_fee_id')
            ->leftJoin('class_categories as cat', 'cat.id', '=', 'f.class_category_id')
            ->whereIn('p.id', $c['new_patterns'] ?? [])
            ->get(['p.class_day', 'p.start_time', 'p.end_time', 'p.start_date', 'p.end_date', 'h.hall_name', 'cat.category_name']);

        return [
            'moved' => $students->filter(fn ($s) => in_array($s->id, $movedIds)),
            'left' => $students->reject(fn ($s) => in_array($s->id, $movedIds)),
            'schedules' => $schedules,
            'undo_blocker' => $this->undoBlocker($promotion),
        ];
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function oldClass(int $classId): object
    {
        $class = DB::table('student_classes as c')
            ->leftJoin('grades as g', 'g.id', '=', 'c.grade_id')
            ->leftJoin('teachers as t', 't.id', '=', 'c.teacher_id')
            ->whereNull('c.deleted_at')->where('c.id', $classId)
            ->first(['c.*', 'g.grade_name', 't.full_name as teacher']);

        if (! $class) {
            throw ValidationException::withMessages(['class_id' => 'Class not found.']);
        }
        if (! $class->is_active) {
            throw ValidationException::withMessages(['class_id' => 'This class is not active.']);
        }

        return $class;
    }

    private function startDate(?string $value): Carbon
    {
        if ($value) {
            return Carbon::parse($value)->startOfDay();
        }

        // after mid-year: the 1st of next January, else the 1st of next month
        $now = now();

        return $now->month >= 7
            ? $now->copy()->addYear()->startOfYear()
            : $now->copy()->addMonth()->startOfMonth();
    }

    /** "9" -> grade "10" (when it exists). */
    private function nextGrade(?string $gradeName, Collection $grades): ?object
    {
        if ($gradeName === null || ! preg_match('/^\d{1,2}$/', trim($gradeName))) {
            return null;
        }

        $next = (string) ((int) $gradeName + 1);

        return $grades->first(fn ($g) => trim($g->grade_name) === $next);
    }

    /** "Aa class - Grade 9" -> "Aa class - Grade 10". */
    private function renameForGrade(string $name, string $oldGrade, string $newGrade): string
    {
        $renamed = preg_replace('/(?<!\d)' . preg_quote(trim($oldGrade), '/') . '(?!\d)/', $newGrade, $name, 1);

        return $renamed ?: $name;
    }

    /** "2026 - English - Grade 10" -> "2027 - English - Grade 11" when the class starts in 2027. */
    private function renameForYear(string $name, int $startYear): string
    {
        $renamed = preg_replace('/(?<!\d)' . ($startYear - 1) . '(?!\d)/', (string) $startYear, $name, 1);

        return $renamed ?: $name;
    }

    /** Fee categories of a class: id (class_category_fees), name. */
    private function categories(int $classId): Collection
    {
        return DB::table('class_category_fees as f')
            ->join('class_categories as c', 'c.id', '=', 'f.class_category_id')
            ->whereNull('f.deleted_at')->where('f.student_class_id', $classId)
            ->orderBy('c.category_name')
            ->get(['f.id', 'c.category_name']);
    }

    /** Students in the class now, with a warning for fees paid from the start month. */
    private function students(int $classId, Carbon $start): Collection
    {
        $rows = DB::table('student_class_enrollments as e')
            ->join('students as s', 's.id', '=', 'e.student_id')
            ->leftJoin('grades as g', 'g.id', '=', 's.grade_id')
            ->leftJoin('class_category_fees as f', 'f.id', '=', 'e.class_category_fee_id')
            ->leftJoin('class_categories as cat', 'cat.id', '=', 'f.class_category_id')
            ->leftJoin('class_category_fee_options as o', 'o.id', '=', 'e.class_category_fee_option_id')
            ->whereNull('e.deleted_at')->whereNull('s.deleted_at')
            ->where('e.student_class_id', $classId)->where('e.is_active', true)
            ->orderBy('s.custom_id')
            ->get(['e.id as enrollment_id', 's.id', 's.custom_id', 's.initial_name', 's.is_active',
                'g.grade_name', 'cat.category_name', 'o.label as option_label', 'o.fee', 'e.is_free_card']);

        $paidAhead = DB::table('payments')
            ->whereIn('student_class_enrollment_id', $rows->pluck('enrollment_id'))
            ->where('status', 'completed')
            ->whereDate('payment_month', '>=', $start->copy()->startOfMonth()->toDateString())
            ->get(['student_class_enrollment_id', 'payment_month'])
            ->groupBy('student_class_enrollment_id');

        return $rows->groupBy('id')->map(function (Collection $group) use ($paidAhead) {
            $first = $group->first();
            $months = $group->flatMap(fn ($e) => ($paidAhead[$e->enrollment_id] ?? collect())
                ->map(fn ($p) => Carbon::parse($p->payment_month)->format('M Y')))->unique()->values();

            return (object) [
                'id' => $first->id,
                'custom_id' => $first->custom_id,
                'name' => $first->initial_name,
                'grade_name' => $first->grade_name,
                'is_active' => (bool) $first->is_active,
                'enrollments' => $group->map(fn ($e) => trim($e->category_name . ' · ' . $e->option_label)
                    . ($e->is_free_card ? ' (Free card)' : ''))->implode(', '),
                'paid_ahead' => $months->implode(', '),
            ];
        })->values();
    }

    /** Current weekly schedule of the class (one row per day / time / category). */
    private function currentSchedule(int $classId): Collection
    {
        return DB::table('class_schedule_patterns')
            ->whereNull('deleted_at')->where('student_class_id', $classId)->where('is_active', true)
            ->orderByDesc('end_date')
            ->get(['class_category_fee_id', 'class_hall_id', 'class_day', 'start_time', 'end_time'])
            ->unique(fn ($p) => $p->class_category_fee_id . '|' . $p->class_day . '|' . $p->start_time . '|' . $p->end_time)
            ->values();
    }

    private function paymentConfig(int $classId): ?object
    {
        $today = now()->toDateString();

        return DB::table('class_payment_configs')
            ->whereNull('deleted_at')->where('student_class_id', $classId)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $today))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today))
            ->orderByDesc('effective_from')->orderByDesc('id')
            ->first()
            ?? DB::table('class_payment_configs')
                ->whereNull('deleted_at')->where('student_class_id', $classId)->where('is_active', true)
                ->orderByDesc('id')->first();
    }

    /** Sessions of a new schedule row, from the start date (or today) to its end. */
    private function makeSessions(array $pattern, Carbon $now): int
    {
        $date = Carbon::parse(max($pattern['start_date'], now()->toDateString()));
        $end = Carbon::parse($pattern['end_date']);
        $made = 0;
        $rows = [];

        while ($date->lte($end)) {
            if (strtolower($date->format('l')) === $pattern['class_day']) {
                $rows[] = [
                    'branch_id' => $pattern['branch_id'],
                    'class_schedule_pattern_id' => $pattern['id'],
                    'student_class_id' => $pattern['student_class_id'],
                    'class_category_fee_id' => $pattern['class_category_fee_id'],
                    'class_hall_id' => $pattern['class_hall_id'],
                    'class_date' => $date->toDateString(),
                    'start_time' => $pattern['start_time'],
                    'end_time' => $pattern['end_time'],
                    'day_of_week' => $pattern['class_day'],
                    'status' => 'scheduled',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $made++;
                $date->addWeek();
                continue;
            }
            $date->addDay();
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('class_schedules')->insertOrIgnore($chunk);
        }

        return $made;
    }

    private function time(string $value): string
    {
        return Carbon::parse($value)->format('H:i:s');
    }
}
