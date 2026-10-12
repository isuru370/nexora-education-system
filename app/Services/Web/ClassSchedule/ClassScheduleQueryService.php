<?php

namespace App\Services\Web\ClassSchedule;

use App\Models\ClassHall;
use App\Models\ClassSchedule;
use App\Models\ClassSchedulePattern;
use App\Models\StudentClass;

class ClassScheduleQueryService
{
    protected ClassScheduleAccessService $accessService;

    public function __construct(
        ClassScheduleAccessService $accessService
    ) {
        $this->accessService = $accessService;
    }

    /*
    |--------------------------------------------------------------------------
    | Index Classes
    |--------------------------------------------------------------------------
    */

    /**
     * Get active and ongoing classes for the schedule index.
     */

    public function getIndexClasses(
        string $search = '',
        int $perPage = 10,
        ?int $branchId = null,
        ?int $studentClassId = null
    ) {
        $perPage = max(1, min($perPage, 100));

        $accessibleBranchIds = $this->accessService
            ->accessibleBranchIds();

        return StudentClass::query()
            ->whereIn('branch_id', $accessibleBranchIds)

            // Branch filter
            ->when($branchId !== null, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })

            // Class filter
            ->when($studentClassId !== null, function ($query) use ($studentClassId) {
                $query->whereKey($studentClassId);
            })

            ->with([
                'grade',
                'subject',
                'teacher',
                'categoryFees' => function ($query) {
                    $query->with('category')
                        ->where('is_active', true);
                },
            ])
            ->where('is_active', true)
            ->where('is_ongoing', true)

            // Search
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('class_name', 'like', "%{$search}%")
                        ->orWhere('class_type', 'like', "%{$search}%")
                        ->orWhere('medium', 'like', "%{$search}%")
                        ->orWhereHas('grade', function ($grade) use ($search) {
                            $grade->where(
                                'grade_name',
                                'like',
                                "%{$search}%"
                            );
                        })
                        ->orWhereHas('subject', function ($subject) use ($search) {
                            $subject->where(
                                'subject_name',
                                'like',
                                "%{$search}%"
                            );
                        })
                        ->orWhereHas('teacher', function ($teacher) use ($search) {
                            $teacher->where(
                                'initials',
                                'like',
                                "%{$search}%"
                            );
                        })
                        ->orWhereHas(
                            'categoryFees.category',
                            function ($category) use ($search) {
                                $category->where(
                                    'category_name',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        );
                });
            })
            ->orderBy('class_name')
            ->paginate($perPage)
            ->appends(request()->query());
    }


    /*
    |--------------------------------------------------------------------------
    | Create Page Data
    |--------------------------------------------------------------------------
    */

    /**
     * Get the selected class, category fee and halls
     * required by the create schedule page.
     */
    public function getCreateData(
        int $studentClassId,
        int $categoryFeeId
    ): array {
        [$studentClass, $categoryFee] =
            $this->accessService->validateClassAndCategory(
                $studentClassId,
                $categoryFeeId
            );

        $studentClass->load([
            'grade',
            'subject',
            'teacher',
        ]);

        $categoryFee->load('category');

        // Get halls belonging only to the selected class's branch.
        $halls = ClassHall::query()
            ->where('branch_id', $studentClass->branch_id)
            ->where('is_active', true)
            ->orderBy('hall_name')
            ->get();

        return [
            'selectedClass' => $studentClass,
            'selectedCategory' => $categoryFee->category,
            'categoryFee' => $categoryFee,
            'halls' => $halls,
            'selectedClassId' => $studentClassId,
            'selectedCategoryFeeId' => $categoryFeeId,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Show Page Data
    |--------------------------------------------------------------------------
    */

    /**
     * Load all relationships required by the schedule details page.
     */
    public function getShowData(
        ClassSchedule $schedule
    ): array {
        $this->accessService->assertScheduleAccess($schedule);

        $schedule->load([
            'pattern',
            'studentClass.grade',
            'studentClass.subject',
            'studentClass.teacher',
            'classCategoryFee.category',
            'hall',
            'cancelledBy',
        ]);

        $categoryFee = $schedule->classCategoryFee;

        $category = optional($categoryFee)->category;

        /*
         * Preserve compatibility with existing Blade views
         * that access $classSchedule->category.
         */
        $schedule->setRelation('category', $category);

        return [
            'classSchedule' => $schedule,
            'categoryFee' => $categoryFee,
            'category' => $category,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Category Schedules
    |--------------------------------------------------------------------------
    */

    /**
     * Get schedules belonging to a selected class and category fee.
     */
    public function getCategorySchedules(
        int $studentClassId,
        int $categoryFeeId,
        string $search = '',
        int $perPage = 15
    ): array {
        list($studentClass, $categoryFee) =
            $this->accessService->validateClassAndCategory(
                $studentClassId,
                $categoryFeeId
            );

        $studentClass->load([
            'grade',
            'subject',
            'teacher',
        ]);

        $categoryFee->load('category');

        $query = ClassSchedule::query()
            ->with([
                'pattern',
                'hall',
                'classCategoryFee.category',
            ])
            ->where(
                'branch_id',
                $studentClass->branch_id
            )
            ->where(
                'student_class_id',
                $studentClassId
            )
            ->where(
                'class_category_fee_id',
                $categoryFeeId
            );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'status',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'day_of_week',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereDate(
                        'class_date',
                        $search
                    )
                    ->orWhereHas('hall', function ($hallQuery) use ($search) {
                        $hallQuery->where(
                            'hall_name',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        }

        return [
            'studentClass' => $studentClass,
            'categoryFee' => $categoryFee,
            'category' => $categoryFee->category,
            'schedules' => $query
                ->latest('class_date')
                ->paginate(max(1, $perPage)),
            'search' => $search,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Schedule Page Data
    |--------------------------------------------------------------------------
    */

    /**
     * Get schedule details, accessible classes and active halls.
     */
    public function getEditData(
        ClassSchedule $schedule
    ): array {
        $this->accessService->assertScheduleAccess($schedule);

        $schedule->load([
            'studentClass.grade',
            'studentClass.subject',
            'studentClass.teacher',
            'classCategoryFee.category',
            'hall',
        ]);

        $categoryFee = $schedule->classCategoryFee;

        $category = optional($categoryFee)->category;

        $schedule->setRelation('category', $category);

        $classes = StudentClass::query()
            ->with([
                'grade',
                'subject',
                'teacher',
            ])
            ->whereIn(
                'branch_id',
                $this->accessService->accessibleBranchIds()
            )
            ->where('is_active', true)
            ->orderBy('class_name')
            ->get();

        $halls = ClassHall::query()
            ->where('is_active', true)
            ->orderBy('hall_name')
            ->get();

        return [
            'classSchedule' => $schedule,
            'classes' => $classes,
            'halls' => $halls,
            'categoryFee' => $categoryFee,
            'category' => $category,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Edit Page Data
    |--------------------------------------------------------------------------
    */

    /**
     * Get a class, category fee and its recurring patterns
     * for the bulk schedule management page.
     */
    public function getBulkEditData(
        int $studentClassId,
        int $categoryFeeId
    ): array {
        [$studentClass, $categoryFee] =
            $this->accessService->validateClassAndCategory(
                $studentClassId,
                $categoryFeeId
            );

        $studentClass->load([
            'teacher',
            'subject',
            'grade',
        ]);

        // ✅ category + activeFeeOptions දෙකම load කරන්න
        $categoryFee->load([
            'category',
            'activeFeeOptions',
        ]);

        $patterns = ClassSchedulePattern::query()
            ->with([
                'hall',   // ✅ Hall relationship load කරන්න
            ])
            ->where('branch_id', $studentClass->branch_id)
            ->where('student_class_id', $studentClass->id)
            ->where('class_category_fee_id', $categoryFee->id)
            ->orderBy('class_day')
            ->orderBy('start_time')
            ->get();

        return [
            'studentClass' => $studentClass,
            'categoryFee' => $categoryFee,
            'patterns' => $patterns,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Bulk Schedule Page Data
    |--------------------------------------------------------------------------
    */

    /**
     * Get a recurring pattern and related information
     * for the bulk schedule edit page.
     */
    public function getEditBulkData(
        ClassSchedulePattern $pattern
    ): array {
        $this->accessService->assertPatternAccess($pattern);

        $pattern->load([
            'studentClass.teacher',
            'studentClass.subject',
            'studentClass.grade',
            'categoryFee.category',
            'hall',
        ]);

        $halls = ClassHall::query()
            ->where('is_active', true)
            ->orderBy('hall_name')
            ->get();

        return [
            'pattern' => $pattern,
            'studentClass' => $pattern->studentClass,
            'categoryFee' => $pattern->categoryFee,
            'halls' => $halls,
        ];
    }
}
