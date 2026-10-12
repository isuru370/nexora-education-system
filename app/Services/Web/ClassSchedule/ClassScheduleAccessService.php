<?php

namespace App\Services\Web\ClassSchedule;

use App\Models\ClassCategoryFee;
use App\Models\ClassSchedule;
use App\Models\ClassSchedulePattern;
use App\Models\StudentClass;
use App\Services\Branch\BranchAccessService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ClassScheduleAccessService
{
    protected BranchAccessService $branchAccessService;

    public function __construct(
        BranchAccessService $branchAccessService
    ) {
        $this->branchAccessService = $branchAccessService;
    }

    /*
    |--------------------------------------------------------------------------
    | Branch Access
    |--------------------------------------------------------------------------
    */

    /**
     * Get all branch IDs accessible to the authenticated user.
     */
    public function accessibleBranchIds(): Collection
    {
        return $this->branchAccessService->getBranchIds();
    }

    /**
     * Restrict an Eloquent query to accessible branches.
     */
    public function applyBranchAccess(
        Builder $query,
        string $column = 'branch_id'
    ): Builder {
        return $this->branchAccessService->applyBranchAccess(
            $query,
            $column
        );
    }

    /**
     * Check whether the authenticated user can access a branch.
     */
    public function assertBranchAccess(int $branchId): void
    {
        abort_unless(
            $this->branchAccessService->canAccessBranch($branchId),
            404
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Class / Schedule / Pattern Access
    |--------------------------------------------------------------------------
    */

    /**
     * Check access to a student class.
     */
    public function assertClassAccess(
        StudentClass $studentClass
    ): void {
        $this->assertBranchAccess(
            (int) $studentClass->branch_id
        );
    }

    /**
     * Check access to a class schedule.
     */
    public function assertScheduleAccess(
        ClassSchedule $schedule
    ): void {
        $this->assertBranchAccess(
            (int) $schedule->branch_id
        );
    }

    /**
     * Check access to a recurring schedule pattern.
     */
    public function assertPatternAccess(
        ClassSchedulePattern $pattern
    ): void {
        $this->assertBranchAccess(
            (int) $pattern->branch_id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Class And Category Fee
    |--------------------------------------------------------------------------
    */

    /**
     * Validate the selected class and category fee.
     *
     * @return array{0: StudentClass, 1: ClassCategoryFee}
     */
    public function validateClassAndCategory(
        int $studentClassId,
        int $categoryFeeId
    ): array {
        /*
         * Only load a class belonging to a branch
         * accessible to the authenticated user.
         */
        $studentClass = StudentClass::query()
            ->whereKey($studentClassId)
            ->whereIn(
                'branch_id',
                $this->accessibleBranchIds()
            )
            ->firstOrFail();

        /*
         * Preserve the existing class availability rules.
         */
        if (
            !$studentClass->is_active ||
            !$studentClass->is_ongoing
        ) {
            throw ValidationException::withMessages([
                'student_class_id' =>
                    'This class is inactive or not ongoing.',
            ]);
        }

        /*
         * The category fee must belong to the selected class.
         */
        $categoryFee = ClassCategoryFee::query()
            ->with('category')
            ->whereKey($categoryFeeId)
            ->where(
                'student_class_id',
                $studentClass->id
            )
            ->where('is_active', true)
            ->firstOrFail();

        /*
         * Validate category availability.
         */
        if (
            !$categoryFee->category ||
            !$categoryFee->category->is_active ||
            !$categoryFee->category->is_schedulable
        ) {
            throw ValidationException::withMessages([
                'class_category_fee_id' =>
                    'This category is not available for scheduling.',
            ]);
        }

        return [
            $studentClass,
            $categoryFee,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Schedule Locking
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether a schedule is locked.
     *
     * Schedules dated today or earlier are locked,
     * preserving the existing service behaviour.
     */
    public function isLocked(
        ClassSchedule $schedule
    ): bool {
        $this->assertScheduleAccess($schedule);

        if (!$schedule->class_date) {
            return false;
        }

        return Carbon::parse(
            $schedule->class_date
        )->lte(today());
    }

    /**
     * Check whether a status is considered locked.
     */
    public function isLockedStatus(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                'ongoing',
                'completed',
                'cancelled',
            ],
            true
        );
    }

    /**
     * Determine whether a schedule date is in the past.
     */
    public function isPastSchedule($schedule): bool
    {
        return Carbon::parse(
            $schedule->class_date
        )->lt(today());
    }

    /*
    |--------------------------------------------------------------------------
    | Recurring Pattern Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get the active pattern for a class,
     * restricted to accessible branches.
     */
    public function getActivePattern(
        int $studentClassId
    ): ?ClassSchedulePattern {
        $studentClass = StudentClass::findOrFail(
            $studentClassId
        );

        $this->assertClassAccess($studentClass);

        return $this->applyBranchAccess(
            ClassSchedulePattern::query()
        )
            ->where(
                'student_class_id',
                $studentClassId
            )
            ->active()
            ->first();
    }

    /**
     * Check whether a date falls within a pattern's
     * configured start and end dates.
     */
    public function validatePatternDateRange(
        $pattern,
        string $classDate
    ): bool {
        $date = Carbon::parse($classDate);

        $startDate = Carbon::parse(
            $pattern->start_date
        );

        $endDate = Carbon::parse(
            $pattern->end_date
        );

        return !$date->lt($startDate)
            && !$date->gt($endDate);
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate Schedule Check
    |--------------------------------------------------------------------------
    */

    /**
     * Check whether a schedule already exists for
     * the selected class and date.
     *
     * This method preserves the existing behaviour:
     * it checks the class and date, not the time.
     */
    public function hasDuplicateSchedule(
        int $studentClassId,
        string $classDate,
        ?int $excludeId = null
    ): bool {
        $studentClass = StudentClass::findOrFail(
            $studentClassId
        );

        $this->assertClassAccess($studentClass);

        $query = $this->applyBranchAccess(
            ClassSchedule::query()
        )
            ->where(
                'student_class_id',
                $studentClassId
            )
            ->whereDate(
                'class_date',
                $classDate
            );

        if ($excludeId !== null) {
            $query->where(
                'id',
                '!=',
                $excludeId
            );
        }

        return $query->exists();
    }
}
