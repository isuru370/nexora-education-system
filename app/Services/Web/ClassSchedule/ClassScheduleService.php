<?php

namespace App\Services\Web\ClassSchedule;

use App\Models\ClassSchedule;
use App\Models\ClassSchedulePattern;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClassScheduleService
{


    public function __construct(
        private ClassScheduleAccessService $accessService,
        private ClassSchedulePatternService $patternService,
        private ClassScheduleQueryService $queryService,
        private TodayClassesService $todayClassesService,
        private ClassScheduleNotificationService $notificationService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Access / Validation Helpers
    |--------------------------------------------------------------------------
    */

    public function accessibleBranchIds()
    {
        return $this->accessService->accessibleBranchIds();
    }

    public function getActivePattern(int $studentClassId)
    {
        return $this->accessService->getActivePattern($studentClassId);
    }

    public function validatePatternDateRange(
        $pattern,
        string $classDate
    ): void {
        $this->accessService->validatePatternDateRange(
            $pattern,
            $classDate
        );
    }

    public function hasDuplicateSchedule(
        int $studentClassId,
        string $classDate,
        ?int $excludeId = null
    ): bool {
        return $this->accessService->hasDuplicateSchedule(
            $studentClassId,
            $classDate,
            $excludeId
        );
    }

    public function isLockedStatus(string $status): bool
    {
        return $this->accessService->isLockedStatus($status);
    }

    public function isPastSchedule($schedule): bool
    {
        return $this->accessService->isPastSchedule($schedule);
    }

    public function isLocked(ClassSchedule $schedule): bool
    {
        return $this->accessService->isLocked($schedule);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Methods
    |--------------------------------------------------------------------------
    */

    public function getIndexClasses()
    {
        return $this->queryService->getIndexClasses();
    }

    public function getCreateData(
        int $studentClassId,
        int $categoryFeeId
    ): array {
        return $this->queryService->getCreateData(
            $studentClassId,
            $categoryFeeId
        );
    }

    public function getShowData(ClassSchedule $schedule)
    {
        return $this->queryService->getShowData($schedule);
    }

    public function getCategorySchedules(
        int $studentClassId,
        int $categoryFeeId
    ) {
        return $this->queryService->getCategorySchedules(
            $studentClassId,
            $categoryFeeId
        );
    }

    public function getEditData(ClassSchedule $schedule)
    {
        return $this->queryService->getEditData($schedule);
    }

    public function getTodayClasses(string $search = '')
    {
        return $this->todayClassesService->getTodayClasses($search);
    }

    public function getBulkEditData(
        int $studentClassId,
        int $categoryFeeId
    ): array {
        return $this->queryService->getBulkEditData(
            $studentClassId,
            $categoryFeeId
        );
    }

    public function getEditBulkData(ClassSchedulePattern $pattern)
    {
        return $this->queryService->getEditBulkData($pattern);
    }


    public function createSchedule(array $validated): void
    {
        try {
            DB::transaction(function () use ($validated) {
                // ---------------------------------------------------------
                // STEP 1: Extract IDs
                // ---------------------------------------------------------
                $studentClassId = (int) $validated['student_class_id'];
                $categoryFeeId = (int) $validated['class_category_fee_id'];

                // ---------------------------------------------------------
                // STEP 2: Validate class and category fee
                // ---------------------------------------------------------
                $validatedData = $this->accessService->validateClassAndCategory(
                    $studentClassId,
                    $categoryFeeId
                );

                [$studentClass, $categoryFee] = $validatedData;

                // ---------------------------------------------------------
                // STEP 3: Branch access check
                // ---------------------------------------------------------
                $branchId = (int) $studentClass->branch_id;

                $this->accessService->assertBranchAccess($branchId);

                // ---------------------------------------------------------
                // STEP 4: Check schedule type
                // ---------------------------------------------------------
                $scheduleType = $validated['schedule_type'] ?? null;

                /*
             * Recurring schedule creation.
             */
                if ($scheduleType === 'recurring') {
                    $this->patternService->createPattern($validated, $branchId);

                    return;
                }

                // ---------------------------------------------------------
                // STEP 5: SINGLE DAY ("Add New Day") SCHEDULE
                // ---------------------------------------------------------
                $classDate = $validated['class_date'];
                $classDateCarbon = Carbon::parse($classDate);
                $dayOfWeek = strtolower($classDateCarbon->format('l'));

                // ---------------------------------------------------------
                // STEP 6: Find matching active recurring pattern
                // ---------------------------------------------------------
                $matchingPattern = ClassSchedulePattern::query()
                    ->where('branch_id', $branchId)
                    ->where('student_class_id', $studentClassId)
                    ->where('class_category_fee_id', $categoryFee->id)
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', $classDateCarbon->toDateString())
                    ->whereDate('end_date', '>=', $classDateCarbon->toDateString())
                    ->first();

                // ---------------------------------------------------------
                // STEP 7: If no matching pattern → validation error
                // ---------------------------------------------------------
                if (!$matchingPattern) {
                    throw ValidationException::withMessages([
                        'class_date' =>
                        'No active recurring schedule pattern found for this class and category on the selected date. ' .
                            'Please create a recurring schedule pattern first or select a date within an existing pattern range.',
                    ]);
                }

                // ---------------------------------------------------------
                // STEP 8: Duplicate check
                // ---------------------------------------------------------
                $duplicateQuery = ClassSchedule::query()
                    ->where('branch_id', $branchId)
                    ->where('student_class_id', $studentClassId)
                    ->where('class_category_fee_id', $categoryFee->id)
                    ->whereDate('class_date', $classDate);

                if (method_exists(ClassSchedule::class, 'bootSoftDeletes')) {
                    $duplicateQuery->withTrashed();
                }

                if ($duplicateQuery->exists()) {
                    throw ValidationException::withMessages([
                        'class_date' =>
                        'A schedule already exists for this class and category on the selected date.',
                    ]);
                }

                // ---------------------------------------------------------
                // STEP 9: Build schedule data
                // ---------------------------------------------------------
                $scheduleData = [
                    'branch_id' => $branchId,
                    'class_schedule_pattern_id' => $matchingPattern->id,
                    'student_class_id' => $studentClassId,
                    'class_category_fee_id' => $categoryFeeId,
                    'class_hall_id' => $validated['class_hall_id'] ?? $matchingPattern->class_hall_id,
                    'class_date' => $classDate,
                    'start_time' => $validated['start_time'] ?? $matchingPattern->start_time,
                    'end_time' => $validated['end_time'] ?? $matchingPattern->end_time,
                    'day_of_week' => $dayOfWeek,
                    'status' => $validated['status'] ?? 'scheduled',
                    'is_active' => $validated['is_active'] ?? true,
                    'note' => $validated['note'] ?? null,
                ];

                ClassSchedule::create($scheduleData);
            });
        } catch (ValidationException $e) {
            // Validation errors → log කරලා re-throw කරන්න
            // එතකොට Controller එකට handle කරන්න පුළුවන්
            Log::warning('[createSchedule] Validation failed', [
                'errors' => $e->errors(),
                'student_class_id' => $validated['student_class_id'] ?? null,
                'class_date' => $validated['class_date'] ?? null,
            ]);

            throw $e;
        } catch (\Throwable $e) {
            // වෙනත් errors → log කරලා re-throw කරන්න
            Log::error('[createSchedule] Unexpected error', [
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'student_class_id' => $validated['student_class_id'] ?? null,
            ]);

            throw $e;
        }

        // ---------------------------------------------------------
        // STEP 10: SMS dispatch (outside transaction)
        // ---------------------------------------------------------
        $this->notificationService->sendClassScheduleSms(
            $validated,
            'created'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Schedule
    |--------------------------------------------------------------------------
    */

    public function updateSchedule(
    ClassSchedule $schedule,
    array $validated
): void {
    $this->accessService->assertScheduleAccess($schedule);

    if ($this->isLocked($schedule)) {
        throw ValidationException::withMessages([
            'class_date' =>
            'Past and current schedules cannot be edited.',
        ]);
    }

    if (($schedule->status ?? null) === 'cancelled') {
        throw ValidationException::withMessages([
            'status' => 'A cancelled schedule cannot be edited.',
        ]);
    }

    try {
        DB::transaction(function () use ($schedule, $validated) {
            // ---------------------------------------------------------
            // STEP 1: Extract IDs
            // ---------------------------------------------------------
            $studentClassId = (int) $validated['student_class_id'];
            $categoryFeeId = (int) $validated['class_category_fee_id'];

            // ---------------------------------------------------------
            // STEP 2: Validate class and category fee
            // ---------------------------------------------------------
            $validatedData = $this->accessService->validateClassAndCategory(
                $studentClassId,
                $categoryFeeId
            );

            [$studentClass, $categoryFee] = $validatedData;

            // ---------------------------------------------------------
            // STEP 3: Branch access check
            // ---------------------------------------------------------
            $branchId = (int) $studentClass->branch_id;

            $this->accessService->assertBranchAccess($branchId);

            // ---------------------------------------------------------
            // STEP 4: Class date and day of week
            // ---------------------------------------------------------
            $classDate = $validated['class_date'];
            $classDateCarbon = Carbon::parse($classDate);
            $dayOfWeek = strtolower($classDateCarbon->format('l'));

            // ---------------------------------------------------------
            // STEP 5: Find matching active recurring pattern
            // ---------------------------------------------------------
            // Only check:
            //   - Same branch, class, category fee
            //   - is_active = true
            //   - class_date falls between pattern's start_date/end_date
            //
            // NOT checking class_day — any day within the range is allowed.
            // ---------------------------------------------------------
            $matchingPattern = ClassSchedulePattern::query()
                ->where('branch_id', $branchId)
                ->where('student_class_id', $studentClassId)
                ->where('class_category_fee_id', $categoryFee->id)
                ->where('is_active', true)
                ->whereDate('start_date', '<=', $classDateCarbon->toDateString())
                ->whereDate('end_date', '>=', $classDateCarbon->toDateString())
                ->first();

            // ---------------------------------------------------------
            // STEP 6: If no matching pattern → validation error
            // ---------------------------------------------------------
            if (!$matchingPattern) {
                throw ValidationException::withMessages([
                    'class_date' =>
                    'No active recurring schedule pattern found for this class and category on the selected date. ' .
                        'Please create a recurring schedule pattern first or select a date within an existing pattern range.',
                ]);
            }

            // ---------------------------------------------------------
            // STEP 7: Duplicate check
            // ---------------------------------------------------------
            $duplicateQuery = ClassSchedule::query()
                ->where('branch_id', $branchId)
                ->where('student_class_id', $studentClassId)
                ->where('class_category_fee_id', $categoryFeeId)
                ->whereDate('class_date', $classDate)
                ->where('id', '!=', $schedule->id);

            if (method_exists(ClassSchedule::class, 'bootSoftDeletes')) {
                $duplicateQuery->withTrashed();
            }

            if ($duplicateQuery->exists()) {
                throw ValidationException::withMessages([
                    'class_date' =>
                    'A schedule already exists for this class and category on the selected date.',
                ]);
            }

            // ---------------------------------------------------------
            // STEP 8: Update schedule
            // ---------------------------------------------------------
            $schedule->update([
                'branch_id' => $branchId,
                'class_schedule_pattern_id' => $matchingPattern->id,   // ✅ Pattern ID එක update කරන්න
                'student_class_id' => $studentClassId,
                'class_category_fee_id' => $categoryFeeId,
                'class_hall_id' => $validated['class_hall_id'] ?? $matchingPattern->class_hall_id,
                'class_date' => $classDate,
                'start_time' => $validated['start_time'] ?? $matchingPattern->start_time,
                'end_time' => $validated['end_time'] ?? $matchingPattern->end_time,
                'day_of_week' => $dayOfWeek,                            // ✅ class_date එකෙන් calculate
                'note' => $validated['note'] ?? null,
                'is_active' => $validated['is_active'] ?? $schedule->is_active,
            ]);
        });

    } catch (ValidationException $e) {
        // Validation errors → log කරලා re-throw කරන්න
        Log::warning('[updateSchedule] Validation failed', [
            'schedule_id' => $schedule->id,
            'errors' => $e->errors(),
            'student_class_id' => $validated['student_class_id'] ?? null,
            'class_date' => $validated['class_date'] ?? null,
        ]);

        throw $e;

    } catch (\Throwable $e) {
        // වෙනත් errors → log කරලා re-throw කරන්න
        Log::error('[updateSchedule] Unexpected error', [
            'schedule_id' => $schedule->id,
            'error_message' => $e->getMessage(),
            'error_class' => get_class($e),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'student_class_id' => $validated['student_class_id'] ?? null,
        ]);

        throw $e;
    }

    // ---------------------------------------------------------
    // STEP 9: SMS dispatch (outside transaction)
    // ---------------------------------------------------------
    $this->notificationService->sendClassScheduleSms(
        $validated,
        'updated'
    );
}

    /*
    |--------------------------------------------------------------------------
    | Delete Schedule
    |--------------------------------------------------------------------------
    */

    public function deleteSchedule(ClassSchedule $schedule): void
    {
        $this->accessService->assertScheduleAccess($schedule);

        if ($this->isLocked($schedule)) {
            throw ValidationException::withMessages([
                'class_date' =>
                'Past and current schedules cannot be deleted.',
            ]);
        }

        if ($schedule->studentAttendances()->exists()) {
            throw ValidationException::withMessages([
                'schedule' =>
                'This schedule cannot be deleted because attendance records exist.',
            ]);
        }

        /*
         * This uses the model's normal delete behaviour.
         * If ClassSchedule uses SoftDeletes, this is a soft delete.
         */
        $schedule->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Schedule Active State
    |--------------------------------------------------------------------------
    */

    public function toggleScheduleActive(
        ClassSchedule $schedule
    ): void {
        $this->accessService->assertScheduleAccess($schedule);

        if ($this->isLocked($schedule)) {
            throw ValidationException::withMessages([
                'class_date' =>
                'Past and current schedules cannot be changed.',
            ]);
        }

        if (($schedule->status ?? null) === 'cancelled') {
            throw ValidationException::withMessages([
                'status' =>
                'A cancelled schedule cannot be activated or deactivated.',
            ]);
        }

        $schedule->is_active = !$schedule->is_active;
        $schedule->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Schedule
    |--------------------------------------------------------------------------
    */

    public function cancelSchedule(
        ClassSchedule $schedule,
        string $reason
    ): void {
        $this->accessService->assertScheduleAccess($schedule);

        if ($this->isLocked($schedule)) {
            throw ValidationException::withMessages([
                'class_date' =>
                'Past and current schedules cannot be cancelled.',
            ]);
        }

        if (($schedule->status ?? null) === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'This schedule is already cancelled.',
            ]);
        }

        DB::transaction(function () use ($schedule, $reason) {
            /*
             * Use the existing model method so its cancellation
             * fields and current business logic remain centralized.
             */
            $schedule->cancel(Auth::id(), $reason);
        });

        $this->notificationService->sendClassScheduleSms(
            $schedule->toArray(),
            'cancelled'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Schedule Completed
    |--------------------------------------------------------------------------
    */

    public function markScheduleCompleted(
        ClassSchedule $schedule
    ): void {
        $this->accessService->assertScheduleAccess($schedule);

        if (($schedule->status ?? null) === 'cancelled') {
            throw ValidationException::withMessages([
                'status' =>
                'A cancelled schedule cannot be marked as completed.',
            ]);
        }

        if (($schedule->status ?? null) === 'completed') {
            throw ValidationException::withMessages([
                'status' => 'This schedule is already completed.',
            ]);
        }

        /*
         * Completion is permitted for today's class.
         * Future classes cannot be marked completed early.
         */
        if (
            Carbon::parse($schedule->class_date)
            ->startOfDay()
            ->gt(Carbon::today())
        ) {
            throw ValidationException::withMessages([
                'class_date' =>
                'A future schedule cannot be marked as completed.',
            ]);
        }

        $schedule->status = 'completed';
        $schedule->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk / Pattern Operations
    |--------------------------------------------------------------------------
    */

    public function updateBulkSchedule(
        ClassSchedulePattern $pattern,
        array $validated
    ): void {
        $this->accessService->assertPatternAccess($pattern);

        $this->patternService->updatePattern(
            $pattern,
            $validated
        );
    }

    public function generateRecurringSchedules(
        ClassSchedulePattern $pattern
    ): void {
        $this->accessService->assertPatternAccess($pattern);

        $this->patternService->generateRecurringSchedules($pattern);
    }

    public function regenerateFutureSchedules(
        ClassSchedulePattern $pattern
    ): void {
        $this->accessService->assertPatternAccess($pattern);

        $this->patternService->regenerateFutureSchedules($pattern);
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    public function sendClassScheduleSms(
        array $validated,
        string $action
    ): void {
        $this->notificationService->sendClassScheduleSms(
            $validated,
            $action
        );
    }
}
