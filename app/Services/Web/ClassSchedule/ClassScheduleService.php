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

    /*
    |--------------------------------------------------------------------------
    | Create Schedule
    |--------------------------------------------------------------------------
    */

    public function createSchedule(array $validated): void
    {
        /*
         * Recurring patterns do not send an SMS.
         * Only single-day ("Add New Day") schedules notify parents.
         */
        $isRecurring = ($validated['schedule_type'] ?? null) === 'recurring';

        try {
            DB::transaction(function () use ($validated, $isRecurring) {
                // ---------------------------------------------------------
                // STEP 1: Extract IDs
                // ---------------------------------------------------------
                $studentClassId = (int) $validated['student_class_id'];
                $categoryFeeId = (int) $validated['class_category_fee_id'];

                // ---------------------------------------------------------
                // STEP 2: Validate class and category fee
                // ---------------------------------------------------------
                [$studentClass, $categoryFee] = $this->accessService->validateClassAndCategory(
                    $studentClassId,
                    $categoryFeeId
                );

                // ---------------------------------------------------------
                // STEP 3: Branch access check
                // ---------------------------------------------------------
                $branchId = (int) $studentClass->branch_id;

                $this->accessService->assertBranchAccess($branchId);

                // ---------------------------------------------------------
                // STEP 4: Recurring schedule creation (no SMS)
                // ---------------------------------------------------------
                if ($isRecurring) {
                    $this->patternService->createPattern($validated, $branchId);

                    return;
                }

                // ---------------------------------------------------------
                // STEP 5: SINGLE DAY ("Add New Day") SCHEDULE
                // ---------------------------------------------------------
                $classDate = Carbon::parse($validated['class_date'])->toDateString();

                $this->assertNotPastDate($classDate);

                // ---------------------------------------------------------
                // STEP 6: Find matching active recurring pattern
                // ---------------------------------------------------------
                $matchingPattern = $this->findMatchingPattern(
                    $branchId,
                    $studentClassId,
                    (int) $categoryFee->id,
                    $classDate
                );

                // ---------------------------------------------------------
                // STEP 7: Duplicate check (active schedules only)
                // ---------------------------------------------------------
                $this->assertNoDuplicate(
                    $branchId,
                    $studentClassId,
                    $categoryFeeId,
                    $classDate
                );

                // ---------------------------------------------------------
                // STEP 8: Build schedule data
                // ---------------------------------------------------------
                $hallId = $validated['class_hall_id'] ?? $matchingPattern->class_hall_id;
                $startTime = $this->normalizeTime($validated['start_time'] ?? $matchingPattern->start_time);
                $endTime = $this->normalizeTime($validated['end_time'] ?? $matchingPattern->end_time);

                // ---------------------------------------------------------
                // STEP 9: Hall must be free at this time
                // ---------------------------------------------------------
                $this->assertHallAvailable(
                    $branchId,
                    $hallId ? (int) $hallId : null,
                    $classDate,
                    $startTime,
                    $endTime
                );

                // ---------------------------------------------------------
                // STEP 10: Remove deleted schedules in the same slot
                // ---------------------------------------------------------
                $this->purgeDeletedSchedules(
                    $studentClassId,
                    $categoryFeeId,
                    $classDate
                );

                // ---------------------------------------------------------
                // STEP 11: Create
                // ---------------------------------------------------------
                ClassSchedule::create([
                    'branch_id' => $branchId,
                    'class_schedule_pattern_id' => $matchingPattern->id,
                    'student_class_id' => $studentClassId,
                    'class_category_fee_id' => $categoryFeeId,
                    'class_hall_id' => $hallId,
                    'class_date' => $classDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'day_of_week' => strtolower(Carbon::parse($classDate)->format('l')),
                    'status' => $validated['status'] ?? 'scheduled',
                    'is_active' => $validated['is_active'] ?? true,
                    'note' => $validated['note'] ?? null,
                ]);
            });
        } catch (ValidationException $e) {
            Log::warning('[createSchedule] Validation failed', [
                'errors' => $e->errors(),
                'student_class_id' => $validated['student_class_id'] ?? null,
                'class_date' => $validated['class_date'] ?? null,
            ]);

            throw $e;
        } catch (\Throwable $e) {
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
        // SMS dispatch (outside transaction, single-day only)
        // ---------------------------------------------------------
        if (!$isRecurring) {
            $this->notificationService->sendClassScheduleSms(
                $validated,
                'created'
            );
        }
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
                'class_date' => 'Past and current schedules cannot be edited.',
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
                [$studentClass, $categoryFee] = $this->accessService->validateClassAndCategory(
                    $studentClassId,
                    $categoryFeeId
                );

                // ---------------------------------------------------------
                // STEP 3: Branch access check
                // ---------------------------------------------------------
                $branchId = (int) $studentClass->branch_id;

                $this->accessService->assertBranchAccess($branchId);

                // ---------------------------------------------------------
                // STEP 4: New class date cannot be in the past
                // ---------------------------------------------------------
                $classDate = Carbon::parse($validated['class_date'])->toDateString();

                $this->assertNotPastDate($classDate);

                // ---------------------------------------------------------
                // STEP 5: Find matching active recurring pattern
                // ---------------------------------------------------------
                // Any day within the pattern's start_date / end_date is allowed.
                $matchingPattern = $this->findMatchingPattern(
                    $branchId,
                    $studentClassId,
                    (int) $categoryFee->id,
                    $classDate
                );

                // ---------------------------------------------------------
                // STEP 6: Duplicate check (active schedules only)
                // ---------------------------------------------------------
                $this->assertNoDuplicate(
                    $branchId,
                    $studentClassId,
                    $categoryFeeId,
                    $classDate,
                    (int) $schedule->id
                );

                // ---------------------------------------------------------
                // STEP 7: Hall must be free at this time
                // ---------------------------------------------------------
                $hallId = $validated['class_hall_id'] ?? $matchingPattern->class_hall_id;
                $startTime = $this->normalizeTime($validated['start_time'] ?? $matchingPattern->start_time);
                $endTime = $this->normalizeTime($validated['end_time'] ?? $matchingPattern->end_time);

                $this->assertHallAvailable(
                    $branchId,
                    $hallId ? (int) $hallId : null,
                    $classDate,
                    $startTime,
                    $endTime,
                    (int) $schedule->id
                );

                // ---------------------------------------------------------
                // STEP 8: Remove deleted schedules in the target slot
                // ---------------------------------------------------------
                $this->purgeDeletedSchedules(
                    $studentClassId,
                    $categoryFeeId,
                    $classDate
                );

                // ---------------------------------------------------------
                // STEP 9: Update schedule
                // ---------------------------------------------------------
                $schedule->update([
                    'branch_id' => $branchId,
                    'class_schedule_pattern_id' => $matchingPattern->id,
                    'student_class_id' => $studentClassId,
                    'class_category_fee_id' => $categoryFeeId,
                    'class_hall_id' => $hallId,
                    'class_date' => $classDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'day_of_week' => strtolower(Carbon::parse($classDate)->format('l')),
                    'note' => $validated['note'] ?? null,
                    'is_active' => $validated['is_active'] ?? $schedule->is_active,
                ]);
            });
        } catch (ValidationException $e) {
            Log::warning('[updateSchedule] Validation failed', [
                'schedule_id' => $schedule->id,
                'errors' => $e->errors(),
                'student_class_id' => $validated['student_class_id'] ?? null,
                'class_date' => $validated['class_date'] ?? null,
            ]);

            throw $e;
        } catch (\Throwable $e) {
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
        // SMS dispatch (outside transaction)
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
                'class_date' => 'Past and current schedules cannot be deleted.',
            ]);
        }

        if ($schedule->studentAttendances()->exists()) {
            throw ValidationException::withMessages([
                'schedule' => 'This schedule cannot be deleted because attendance records exist.',
            ]);
        }

        /*
         * Soft delete (ClassSchedule uses SoftDeletes).
         * The slot can be used again: create/update remove
         * soft-deleted rows in the same slot before saving.
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
                'class_date' => 'Past and current schedules cannot be changed.',
            ]);
        }

        if (($schedule->status ?? null) === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'A cancelled schedule cannot be activated or deactivated.',
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

        if (($schedule->status ?? null) === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'This schedule is already cancelled.',
            ]);
        }

        /*
         * Future classes, and today's classes that have not started yet,
         * can be cancelled (see ClassSchedule::isCancellable()).
         */
        if (!$schedule->isCancellable()) {
            throw ValidationException::withMessages([
                'class_date' => $this->notCancellableMessage($schedule),
            ]);
        }

        DB::transaction(function () use ($schedule, $reason) {
            $schedule->cancel(Auth::id(), $reason);
        });

        /*
         * Build the SMS data explicitly.
         *
         * $schedule->toArray() serializes class_date as a UTC ISO string
         * (e.g. 2026-10-12T18:30:00.000000Z for a class on 2026-10-13),
         * which shows the wrong date in the SMS.
         */
        $this->notificationService->sendClassScheduleSms(
            [
                'student_class_id' => $schedule->student_class_id,
                'class_category_fee_id' => $schedule->class_category_fee_id,
                'class_hall_id' => $schedule->class_hall_id,
                'class_date' => optional($schedule->class_date)->format('Y-m-d'),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ],
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
                'status' => 'A cancelled schedule cannot be marked as completed.',
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
        if (Carbon::parse($schedule->class_date)->startOfDay()->gt(Carbon::today())) {
            throw ValidationException::withMessages([
                'class_date' => 'A future schedule cannot be marked as completed.',
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

    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Reason a schedule cannot be cancelled.
     */
    private function notCancellableMessage(ClassSchedule $schedule): string
    {
        if (($schedule->status ?? null) === 'completed') {
            return 'A completed class cannot be cancelled.';
        }

        if (
            $schedule->class_date &&
            $schedule->class_date->copy()->startOfDay()->lt(Carbon::today())
        ) {
            return 'Past schedules cannot be cancelled.';
        }

        return 'This class has already started and cannot be cancelled.';
    }

    /**
     * A single-day schedule cannot be created or moved to a past date.
     */
    private function assertNotPastDate(string $classDate): void
    {
        if (Carbon::parse($classDate)->startOfDay()->lt(Carbon::today())) {
            throw ValidationException::withMessages([
                'class_date' => 'The class date cannot be in the past.',
            ]);
        }
    }

    /**
     * Find the active recurring pattern that covers the class date.
     */
    private function findMatchingPattern(
        int $branchId,
        int $studentClassId,
        int $categoryFeeId,
        string $classDate
    ): ClassSchedulePattern {
        $pattern = ClassSchedulePattern::query()
            ->where('branch_id', $branchId)
            ->where('student_class_id', $studentClassId)
            ->where('class_category_fee_id', $categoryFeeId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $classDate)
            ->whereDate('end_date', '>=', $classDate)
            ->first();

        if (!$pattern) {
            throw ValidationException::withMessages([
                'class_date' =>
                    'No active recurring schedule pattern found for this class and category on the selected date. ' .
                    'Please create a recurring schedule pattern first or select a date within an existing pattern range.',
            ]);
        }

        return $pattern;
    }

    /**
     * Only one active (not deleted) schedule per class + category + date.
     */
    private function assertNoDuplicate(
        int $branchId,
        int $studentClassId,
        int $categoryFeeId,
        string $classDate,
        ?int $excludeId = null
    ): void {
        $exists = ClassSchedule::query()
            ->where('branch_id', $branchId)
            ->where('student_class_id', $studentClassId)
            ->where('class_category_fee_id', $categoryFeeId)
            ->whereDate('class_date', $classDate)
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'class_date' => 'A schedule already exists for this class and category on the selected date.',
            ]);
        }
    }

    /**
     * A hall cannot host two classes at overlapping times.
     *
     * Overlap: existing.start < new.end AND existing.end > new.start
     * (back-to-back classes, e.g. 13:00-15:00 and 15:00-17:00, are allowed).
     */
    private function assertHallAvailable(
        int $branchId,
        ?int $hallId,
        string $classDate,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): void {
        if (!$hallId) {
            return;
        }

        $clash = ClassSchedule::query()
            ->with('studentClass:id,class_name')
            ->where('branch_id', $branchId)
            ->where('class_hall_id', $hallId)
            ->whereDate('class_date', $classDate)
            ->where('is_active', true)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->first();

        if ($clash) {
            throw ValidationException::withMessages([
                'class_hall_id' => sprintf(
                    'This hall is already booked for %s from %s to %s.',
                    optional($clash->studentClass)->class_name ?? 'another class',
                    substr((string) $clash->start_time, 0, 5),
                    substr((string) $clash->end_time, 0, 5)
                ),
            ]);
        }
    }

    /**
     * Permanently remove soft-deleted schedules in a slot.
     *
     * The cs_unique_slot index also covers soft-deleted rows, so a deleted
     * schedule would otherwise block a new one on the same date.
     * Deleted schedules never have attendance (deleteSchedule blocks that).
     */
    private function purgeDeletedSchedules(
        int $studentClassId,
        int $categoryFeeId,
        string $classDate
    ): void {
        ClassSchedule::onlyTrashed()
            ->where('student_class_id', $studentClassId)
            ->where('class_category_fee_id', $categoryFeeId)
            ->whereDate('class_date', $classDate)
            ->get()
            ->each(function (ClassSchedule $deleted) {
                $deleted->forceDelete();
            });
    }

    /**
     * Store times as H:i:s so comparisons with DB values are correct
     * ("15:00" < "15:00:00" as strings, which breaks overlap checks).
     */
    private function normalizeTime(?string $time): ?string
    {
        return $time ? Carbon::parse($time)->format('H:i:s') : null;
    }
}