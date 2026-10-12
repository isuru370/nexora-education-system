<?php

namespace App\Services\Web\ClassSchedule;

use App\Models\ClassSchedule;
use App\Models\ClassSchedulePattern;
use App\Services\Web\ClassSchedule\ClassScheduleAccessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClassSchedulePatternService
{


    public function __construct(
        private ClassScheduleAccessService $accessService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Create Pattern
    |--------------------------------------------------------------------------
    */

    /**
     * Create a recurring schedule pattern and generate its schedules.
     */
    public function createPattern(
        array $validated,
        int $branchId
    ): ClassSchedulePattern {
        // Verify that the authenticated user can access this branch.
        $this->accessService->assertBranchAccess($branchId);

        return DB::transaction(function () use (
            $validated,
            $branchId
        ) {
            /*
             * Validate the class and category fee again at the
             * service boundary to prevent cross-branch assignment.
             */
            [$studentClass, $categoryFee] =
                $this->accessService->validateClassAndCategory(
                    (int) $validated['student_class_id'],
                    (int) $validated['class_category_fee_id']
                );

            if ((int) $studentClass->branch_id !== $branchId) {
                abort(404);
            }

            $pattern = ClassSchedulePattern::create([
                'branch_id' => $branchId,
                'student_class_id' => $studentClass->id,
                'class_category_fee_id' => $categoryFee->id,
                'class_hall_id' => $validated['class_hall_id'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'class_day' => $validated['class_day'],
                'is_active' => $validated['is_active'] ?? true,
                'note' => $validated['note'] ?? null,
            ]);

            $this->generateRecurringSchedules($pattern);

            return $pattern;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Recurring Schedules
    |--------------------------------------------------------------------------
    */

    /**
     * Generate schedules for matching weekdays.
     *
     * If the start date is in the past, generation begins today.
     * Existing schedules with the same branch, class, category,
     * date and time are not duplicated.
     */
    public function generateRecurringSchedules(
        ClassSchedulePattern $pattern
    ): void {
        $this->accessService->assertPatternAccess($pattern);

        $today = Carbon::today();

        $start = Carbon::parse($pattern->start_date);

        if ($start->lt($today)) {
            $start = $today->copy();
        }

        $end = Carbon::parse($pattern->end_date);

        // No schedules should be generated if the date range has ended.
        if ($start->gt($end)) {
            return;
        }

        $date = $start->copy();

        while ($date->lte($end)) {
            $dayOfWeek = strtolower($date->format('l'));

            if (
                $dayOfWeek ===
                strtolower($pattern->class_day)
            ) {
                $exists = ClassSchedule::query()
                    ->where('branch_id', $pattern->branch_id)
                    ->where(
                        'student_class_id',
                        $pattern->student_class_id
                    )
                    ->where(
                        'class_category_fee_id',
                        $pattern->class_category_fee_id
                    )
                    ->whereDate(
                        'class_date',
                        $date->toDateString()
                    )
                    ->where(
                        'start_time',
                        $pattern->start_time
                    )
                    ->where(
                        'end_time',
                        $pattern->end_time
                    )
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$exists) {
                    ClassSchedule::create([
                        'branch_id' => $pattern->branch_id,
                        'class_schedule_pattern_id' => $pattern->id,
                        'student_class_id' => $pattern->student_class_id,
                        'class_category_fee_id' => $pattern->class_category_fee_id,
                        'class_hall_id' => $pattern->class_hall_id,
                        'class_date' => $date->toDateString(),
                        'start_time' => $pattern->start_time,
                        'end_time' => $pattern->end_time,
                        'day_of_week' => $dayOfWeek,
                        'status' => 'scheduled',
                        'is_active' => $pattern->is_active,
                        'note' => $pattern->note,
                    ]);
                }
            }

            $date->addDay();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Regenerate Future Schedules
    |--------------------------------------------------------------------------
    */

    /**
     * Delete today's and future scheduled records for this pattern,
     * then regenerate them from the updated pattern.
     *
     * Records with statuses other than "scheduled" are preserved.
     */
    public function regenerateFutureSchedules(
        ClassSchedulePattern $pattern
    ): void {
        $this->accessService->assertPatternAccess($pattern);

        DB::transaction(function () use ($pattern) {
            ClassSchedule::query()
                ->where('branch_id', $pattern->branch_id)
                ->where(
                    'class_schedule_pattern_id',
                    $pattern->id
                )
                ->whereDate(
                    'class_date',
                    '>=',
                    Carbon::today()->toDateString()
                )
                ->where('status', 'scheduled')
                ->delete();

            $this->generateRecurringSchedules($pattern);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Pattern
    |--------------------------------------------------------------------------
    */

    /**
     * Update an existing recurring pattern and regenerate schedules.
     */
    public function updatePattern(
        ClassSchedulePattern $pattern,
        array $validated
    ): ClassSchedulePattern {
        $this->accessService->assertPatternAccess($pattern);

        return DB::transaction(function () use (
            $pattern,
            $validated
        ) {
            /*
             * Prevent the pattern from being reassigned to another
             * class or branch through the update payload.
             */
            $this->accessService->validateClassAndCategory(
                (int) $pattern->student_class_id,
                (int) $pattern->class_category_fee_id
            );

            $pattern->update([
                'class_hall_id' => $validated['class_hall_id'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'class_day' => $validated['class_day'],
                'is_active' => $validated['is_active'] ?? true,
                'note' => $validated['note'] ?? null,
            ]);

            /*
             * Delete today's and future scheduled records,
             * then generate the updated schedule set.
             */
            $this->regenerateFutureSchedules($pattern);

            return $pattern->fresh();
        });
    }
}
