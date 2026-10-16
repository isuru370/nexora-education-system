<?php

namespace App\Services\Api\StudentClass;

use App\Models\Grade;
use App\Models\StudentClass;
use App\Services\Branch\BranchAccessService;
use Illuminate\Support\Collection;

class StudentClassService
{
    public function __construct(
        protected BranchAccessService $branchAccessService
    ) {}

    /**
     * Branch IDs to list classes from.
     *
     * @return Collection|null  null = the user cannot access $branchId
     */
    public function resolveBranchIds(?int $branchId): ?Collection
    {
        $allowed = $this->branchAccessService->getBranchIds()->map(fn ($id) => (int) $id)->values();

        if ($branchId === null) {
            return $allowed;
        }

        return $allowed->contains($branchId) ? collect([$branchId]) : null;
    }

    /**
     * Fetch active student classes for a grade.
     *
     * Only classes in the user's branches are returned (a student can only be
     * enrolled in those - StudentClassEnrollmentService checks the same).
     * $branchIds narrows it further (e.g. ?branch_id=3).
     */
    public function fetchStudentClasses(int $gradeId, ?Collection $branchIds = null): array
    {
        $grade = Grade::query()
            ->select([
                'id',
                'grade_name',
            ])
            ->findOrFail($gradeId);

        $classes = StudentClass::query()
            ->select([
                'id',
                'class_name',
                'class_type',
                'medium',
                'teacher_id',
                'subject_id',
                'grade_id',
                'is_active',
                'is_ongoing',
                'branch_id',
            ])
            ->with([
                'teacher:id,full_name',

                'branch:id,name,code',

                'grade:id,grade_name',

                'categoryFees' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'student_class_id',
                            'class_category_id',
                            'is_active',
                        ])
                        ->where('is_active', true)
                        ->with([
                            'category:id,category_name',

                            'feeOptions' => function ($query) {
                                $query
                                    ->select([
                                        'id',
                                        'class_category_fee_id',
                                        'label',
                                        'fee',
                                        'is_default',
                                        'is_active',
                                    ])
                                    ->where('is_active', true)
                                    ->orderByDesc('is_default')
                                    ->orderBy('id');
                            },
                        ]);
                },
            ])
            ->where('grade_id', $gradeId)
            ->where('is_active', true)
            ->whereIn('branch_id', $branchIds ?? $this->resolveBranchIds(null))
            ->orderBy('class_name')
            ->get();

        return [
            'grade' => [
                'id' => $grade->id,
                'grade_name' => $grade->grade_name,
            ],

            'data' => $classes
                ->map(fn ($class) => $this->transformClass($class))
                ->values()
                ->all(),
        ];
    }

    /**
     * Transform student class response.
     */
    private function transformClass(StudentClass $class): array
    {
        return [
            'class_id' => $class->id,

            'class_name' => $class->class_name,

            'class_type' => $class->class_type,

            'medium' => $class->medium,

            'grade_id' => $class->grade_id,

            'grade_name' => $class->grade?->grade_name,

            'branch_id' => $class->branch_id,

            'branch_name' => $class->branch?->name,

            'branch_code' => $class->branch?->code,

            'teacher_id' => $class->teacher?->id,

            'teacher_name' => $class->teacher?->full_name,

            'is_active' => (bool) $class->is_active,

            'is_ongoing' => (bool) $class->is_ongoing,

            'category_fees' => $class->categoryFees
                ->map(function ($categoryFee) {

                    return [
                        'class_category_fee_id' =>
                            $categoryFee->id,

                        'class_category_id' =>
                            $categoryFee->class_category_id,

                        'category_name' =>
                            $categoryFee->category?->category_name,

                        'is_active' =>
                            (bool) $categoryFee->is_active,

                        'fee_options' =>
                            $categoryFee->feeOptions
                                ->map(function ($option) {

                                    return [
                                        'id' =>
                                            $option->id,

                                        'label' =>
                                            $option->label,

                                        'fee' =>
                                            (float) $option->fee,

                                        'is_default' =>
                                            (bool) $option->is_default,

                                        'is_active' =>
                                            (bool) $option->is_active,
                                    ];
                                })
                                ->values()
                                ->all(),
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}