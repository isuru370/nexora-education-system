<?php

namespace App\Services\Web\StudentClassEnrollment;

use App\Models\ClassCategoryFee;
use App\Models\ClassCategoryFeeOption;
use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Services\Branch\BranchAccessService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentClassEnrollmentService
{
    public function __construct(
        protected BranchAccessService $branchAccessService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Create Enrollment
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new enrollment.
     *
     * @param array<string, mixed> $data
     * @return StudentClassEnrollment
     */
    public function create(array $data): StudentClassEnrollment
    {
        return DB::transaction(function () use ($data) {

            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            $studentClassId = (int) $data['student_class_id'];
            $categoryFeeId = (int) $data['class_category_fee_id'];
            $feeOptionId = (int) $data['class_category_fee_option_id'];

            /*
            |--------------------------------------------------------------------------
            | Validate Class (with branch access)
            |--------------------------------------------------------------------------
            */

            $studentClass = StudentClass::query()
                ->where('id', $studentClassId)
                ->where('is_active', true)
                ->whereIn('branch_id', $accessibleBranchIds)
                ->first();

            if (!$studentClass) {
                throw ValidationException::withMessages([
                    'student_class_id' => [
                        'Selected class is invalid or inactive.'
                    ],
                ]);
            }

            // ✅ Class එකෙන් branch_id ගන්න (user input එකෙන් නෙවෙයි)
            $branchId = (int) $studentClass->branch_id;

            /*
            |--------------------------------------------------------------------------
            | Validate Category Fee
            |--------------------------------------------------------------------------
            */

            $categoryFee = ClassCategoryFee::query()
                ->where('id', $categoryFeeId)
                ->where('student_class_id', $studentClassId)
                ->where('is_active', true)
                ->first();

            if (!$categoryFee) {
                throw ValidationException::withMessages([
                    'class_category_fee_id' => [
                        'Selected category fee is not assigned to this class or is inactive.'
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Fee Option
            |--------------------------------------------------------------------------
            */

            $feeOption = ClassCategoryFeeOption::query()
                ->where('id', $feeOptionId)
                ->where('class_category_fee_id', $categoryFee->id)
                ->where('is_active', true)
                ->first();

            if (!$feeOption) {
                throw ValidationException::withMessages([
                    'class_category_fee_option_id' => [
                        'Selected fee option is invalid or inactive.'
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Check Existing Active Enrollment
            |--------------------------------------------------------------------------
            */

            $existingActiveEnrollment = StudentClassEnrollment::query()
                ->where('student_id', $data['student_id'])
                ->where('student_class_id', $studentClassId)
                ->where('class_category_fee_id', $categoryFeeId)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Deactivate Existing Active Enrollment
            |--------------------------------------------------------------------------
            */

            if ($existingActiveEnrollment) {

                if (
                    (int) $existingActiveEnrollment->class_category_fee_option_id
                    === (int) $feeOption->id
                ) {
                    throw ValidationException::withMessages([
                        'class_category_fee_option_id' => [
                            'Student is already enrolled with this class category and fee option.'
                        ],
                    ]);
                }

                $existingActiveEnrollment->update([
                    'is_active' => false,
                    'left_at' => !empty($data['enrolled_at'])
                        ? $data['enrolled_at']
                        : now()->toDateString(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create New Enrollment (or bring back an old one)
            |--------------------------------------------------------------------------
            |
            | student + class + category fee + fee option is unique in the table
            | (sce_student_class_fee_unique), active or not. A student who left
            | this class (or used this fee option before) already has a row, so
            | that row is re-activated instead of inserting a duplicate.
            |
            */

            $values = [
                'branch_id' => $branchId,
                'is_active' => true,
                'is_free_card' => !empty($data['is_free_card']) ? true : false,
                'enrolled_at' => !empty($data['enrolled_at'])
                    ? $data['enrolled_at']
                    : now()->toDateString(),
                'left_at' => null,
                'note' => $data['note'] ?? null,
            ];

            $previousEnrollment = StudentClassEnrollment::withTrashed()
                ->where('student_id', $data['student_id'])
                ->where('student_class_id', $studentClassId)
                ->where('class_category_fee_id', $categoryFeeId)
                ->where('class_category_fee_option_id', $feeOption->id)
                ->lockForUpdate()
                ->first();

            if ($previousEnrollment) {
                if ($previousEnrollment->trashed()) {
                    $previousEnrollment->restore();
                }

                $previousEnrollment->update($values);

                $enrollment = $previousEnrollment;
            } else {
                $enrollment = StudentClassEnrollment::create($values + [
                    'student_id' => $data['student_id'],
                    'student_class_id' => $studentClassId,
                    'class_category_fee_id' => $categoryFeeId,
                    'class_category_fee_option_id' => $feeOption->id,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Return New Enrollment
            |--------------------------------------------------------------------------
            */

            return $enrollment->load([
                'branch',
                'student',
                'studentClass',
                'classCategoryFee.category',
                'classCategoryFeeOption',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Update Enrollment
    |--------------------------------------------------------------------------
    */

    /**
     * Update an existing enrollment.
     *
     * @param StudentClassEnrollment $enrollment
     * @param array<string, mixed> $data
     * @return StudentClassEnrollment
     */
    public function update(
        StudentClassEnrollment $enrollment,
        array $data
    ): StudentClassEnrollment {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        if (!$accessibleBranchIds->contains($enrollment->branch_id)) {
            abort(404);
        }

        return DB::transaction(function () use (
            $enrollment,
            $data
        ) {

            $studentClassId = (int) $enrollment->student_class_id;

            $studentClass = StudentClass::query()
                ->where('id', $studentClassId)
                ->whereIn('branch_id', $this->branchAccessService->getBranchIds())
                ->firstOrFail();

            $branchId = (int) $studentClass->branch_id;

            /*
            |--------------------------------------------------------------------------
            | Category Fee
            |--------------------------------------------------------------------------
            */

            $categoryFeeId = isset($data['class_category_fee_id'])
                ? (int) $data['class_category_fee_id']
                : (int) $enrollment->class_category_fee_id;

            $categoryFee = ClassCategoryFee::query()
                ->where('id', $categoryFeeId)
                ->where('student_class_id', $studentClassId)
                ->where('is_active', true)
                ->first();

            if (!$categoryFee) {
                throw ValidationException::withMessages([
                    'class_category_fee_id' => [
                        'Selected category fee is not assigned to this class or is inactive.'
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Fee Option
            |--------------------------------------------------------------------------
            */

            $feeOptionId = isset($data['class_category_fee_option_id'])
                ? (int) $data['class_category_fee_option_id']
                : (int) $enrollment->class_category_fee_option_id;

            $feeOption = ClassCategoryFeeOption::query()
                ->where('id', $feeOptionId)
                ->where('class_category_fee_id', $categoryFee->id)
                ->where('is_active', true)
                ->first();

            if (!$feeOption) {
                throw ValidationException::withMessages([
                    'class_category_fee_option_id' => [
                        'Selected fee option is invalid or inactive.'
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate Enrollment Check (branch-scoped)
            |--------------------------------------------------------------------------
            */

            $existing = StudentClassEnrollment::withTrashed()
                ->where('student_id', $enrollment->student_id)
                ->where('student_class_id', $studentClassId)
                ->where('class_category_fee_id', $categoryFeeId)
                ->where('branch_id', $branchId)
                ->where('id', '!=', $enrollment->id)
                ->first();

            if (
                $existing &&
                !$existing->trashed() &&
                $existing->is_active
            ) {
                throw ValidationException::withMessages([
                    'class_category_fee_id' => [
                        'Student is already enrolled in this class category.'
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Update
            |--------------------------------------------------------------------------
            */

            $isActive = array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : (bool) $enrollment->is_active;

            $isFreeCard = array_key_exists('is_free_card', $data)
                ? (bool) $data['is_free_card']
                : (bool) $enrollment->is_free_card;

            $enrolledAt = array_key_exists('enrolled_at', $data)
                ? $data['enrolled_at']
                : $enrollment->enrolled_at;

            $leftAt = array_key_exists('left_at', $data)
                ? $data['left_at']
                : $enrollment->left_at;

            if ($isActive) {
                $leftAt = null;
            }

            if (!$isActive && empty($leftAt)) {
                $leftAt = now()->toDateString();
            }

            $enrollment->update([
                'branch_id' => $branchId,
                'class_category_fee_id' => $categoryFee->id,
                'class_category_fee_option_id' => $feeOption->id,
                'is_active' => $isActive,
                'is_free_card' => $isFreeCard,
                'enrolled_at' => $enrolledAt,
                'left_at' => $leftAt,
                'note' => isset($data['note'])
                    ? $data['note']
                    : $enrollment->note,
            ]);

            return $enrollment->fresh([
                'branch',
                'student',
                'studentClass',
                'classCategoryFee.category',
                'classCategoryFeeOption',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Activate
    |--------------------------------------------------------------------------
    */

    /**
     * Activate an enrollment.
     *
     * @param StudentClassEnrollment $enrollment
     * @return StudentClassEnrollment
     */
    public function activate(
        StudentClassEnrollment $enrollment
    ): StudentClassEnrollment {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        if (!$accessibleBranchIds->contains($enrollment->branch_id)) {
            abort(404);
        }

        if ($enrollment->is_active) {
            throw ValidationException::withMessages([
                'enrollment' => [
                    'Student enrollment is already active.'
                ],
            ]);
        }

        $enrollment->update([
            'is_active' => true,
            'left_at' => null,
        ]);

        return $enrollment->fresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Deactivate
    |--------------------------------------------------------------------------
    */

    /**
     * Deactivate an enrollment.
     *
     * @param StudentClassEnrollment $enrollment
     * @return StudentClassEnrollment
     */
    public function deactivate(
        StudentClassEnrollment $enrollment
    ): StudentClassEnrollment {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        if (!$accessibleBranchIds->contains($enrollment->branch_id)) {
            abort(404);
        }

        if (!$enrollment->is_active) {
            throw ValidationException::withMessages([
                'enrollment' => [
                    'Student enrollment is already inactive.'
                ],
            ]);
        }

        $enrollment->update([
            'is_active' => false,
            'left_at' => now()->toDateString(),
        ]);

        return $enrollment->fresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Toggle Status
    |--------------------------------------------------------------------------
    */

    /**
     * Toggle enrollment active status.
     *
     * @param StudentClassEnrollment $enrollment
     * @return StudentClassEnrollment
     */
    public function toggleStatus(
        StudentClassEnrollment $enrollment
    ): StudentClassEnrollment {
        if ($enrollment->is_active) {
            return $this->deactivate($enrollment);
        }

        return $this->activate($enrollment);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    /**
     * Delete an enrollment.
     *
     * @param StudentClassEnrollment $enrollment
     * @return bool
     */
    public function delete(
        StudentClassEnrollment $enrollment
    ): bool {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        if (!$accessibleBranchIds->contains($enrollment->branch_id)) {
            abort(404);
        }

        if ($enrollment->payments()->exists()) {
            throw ValidationException::withMessages([
                'enrollment' => [
                    'Cannot delete enrollment because payments already exist.'
                ],
            ]);
        }

        $enrollment->delete();

        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Restore
    |--------------------------------------------------------------------------
    */

    /**
     * Restore a soft-deleted enrollment.
     *
     * @param int $id
     * @return StudentClassEnrollment
     */
    public function restore(int $id): StudentClassEnrollment
    {
        return DB::transaction(function () use ($id) {

            /** @var Collection<int, int> $accessibleBranchIds */
            $accessibleBranchIds = $this->branchAccessService->getBranchIds();

            $enrollment = StudentClassEnrollment::withTrashed()
                ->whereIn('branch_id', $accessibleBranchIds)
                ->findOrFail($id);

            if (!$enrollment->trashed()) {
                throw ValidationException::withMessages([
                    'enrollment' => [
                        'Enrollment is not deleted.'
                    ],
                ]);
            }

            $existing = StudentClassEnrollment::query()
                ->where('student_id', $enrollment->student_id)
                ->where('student_class_id', $enrollment->student_class_id)
                ->where('class_category_fee_id', $enrollment->class_category_fee_id)
                ->where('branch_id', $enrollment->branch_id)
                ->where('id', '!=', $enrollment->id)
                ->where('is_active', true)
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'enrollment' => [
                        'An active enrollment already exists for this class category.'
                    ],
                ]);
            }

            $this->validateFeeOption(
                (int) $enrollment->student_class_id,
                (int) $enrollment->class_category_fee_id,
                (int) $enrollment->class_category_fee_option_id
            );

            $enrollment->restore();

            $enrollment->update([
                'is_active' => true,
                'left_at' => null,
            ]);

            return $enrollment->fresh([
                'branch',
                'student',
                'studentClass',
                'classCategoryFee.category',
                'classCategoryFeeOption',
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Find Enrollment (branch-scoped)
    |--------------------------------------------------------------------------
    */

    /**
     * Find an enrollment by ID.
     *
     * @param int $id
     * @return StudentClassEnrollment
     */
    public function find(int $id): StudentClassEnrollment
    {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        return StudentClassEnrollment::with([
            'branch',
            'student',
            'studentClass.grade',
            'studentClass.subject',
            'studentClass.teacher',
            'classCategoryFee.category',
            'classCategoryFeeOption',
            'payments',
        ])
            ->whereIn('branch_id', $accessibleBranchIds)
            ->findOrFail($id);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Student Enrollments (branch-scoped)
    |--------------------------------------------------------------------------
    */

    /**
     * Get all enrollments for a student.
     *
     * @param int $studentId
     * @param bool $activeOnly
     * @return Collection<int, StudentClassEnrollment>
     */
    public function getStudentEnrollments(
        int $studentId,
        bool $activeOnly = false
    ): Collection {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $query = StudentClassEnrollment::query()
            ->with([
                'branch',
                'studentClass.teacher',
                'studentClass.grade',
                'studentClass.subject',
                'classCategoryFee.category',
                'classCategoryFeeOption',
            ])
            ->where('student_id', $studentId)
            ->whereIn('branch_id', $accessibleBranchIds);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query
            ->orderByDesc('is_active')
            ->latest()
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Get Fee Options For Enrollment (branch-scoped)
    |--------------------------------------------------------------------------
    */

    /**
     * Get active fee options for a class and category fee.
     *
     * @param int $studentClassId
     * @param int $classCategoryFeeId
     * @return Collection<int, ClassCategoryFeeOption>
     */
    public function getFeeOptions(
        int $studentClassId,
        int $classCategoryFeeId
    ): Collection {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $studentClass = StudentClass::query()
            ->where('id', $studentClassId)
            ->whereIn('branch_id', $accessibleBranchIds)
            ->first();

        if (!$studentClass) {
            throw ValidationException::withMessages([
                'student_class_id' => [
                    'Selected class is invalid or you do not have access to it.'
                ],
            ]);
        }

        $categoryFee = ClassCategoryFee::query()
            ->where('id', $classCategoryFeeId)
            ->where('student_class_id', $studentClassId)
            ->where('is_active', true)
            ->first();

        if (!$categoryFee) {
            throw ValidationException::withMessages([
                'class_category_fee_id' => [
                    'Selected category fee is invalid or inactive.'
                ],
            ]);
        }

        return ClassCategoryFeeOption::query()
            ->where('class_category_fee_id', $categoryFee->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Get Student Classes (branch-scoped)
    |--------------------------------------------------------------------------
    */

    /**
     * Get all classes for a student.
     *
     * @param int $studentId
     * @return Collection<int, StudentClassEnrollment>
     */
    public function getStudentClasses(int $studentId): Collection
    {
        return $this->getStudentEnrollments($studentId);
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Fee Option
    |--------------------------------------------------------------------------
    */

    /**
     * Validate that a fee option belongs to a class and category fee.
     *
     * @param int $studentClassId
     * @param int $classCategoryFeeId
     * @param int $feeOptionId
     * @return ClassCategoryFeeOption
     */
    protected function validateFeeOption(
        int $studentClassId,
        int $classCategoryFeeId,
        int $feeOptionId
    ): ClassCategoryFeeOption {
        $categoryFee = ClassCategoryFee::query()
            ->where('id', $classCategoryFeeId)
            ->where('student_class_id', $studentClassId)
            ->where('is_active', true)
            ->first();

        if (!$categoryFee) {
            throw ValidationException::withMessages([
                'class_category_fee_id' => [
                    'Selected category fee is not assigned to this class or is inactive.'
                ],
            ]);
        }

        $option = ClassCategoryFeeOption::query()
            ->where('id', $feeOptionId)
            ->where('class_category_fee_id', $categoryFee->id)
            ->where('is_active', true)
            ->first();

        if (!$option) {
            throw ValidationException::withMessages([
                'class_category_fee_option_id' => [
                    'Selected fee option is invalid or inactive.'
                ],
            ]);
        }

        return $option;
    }


    /*
    |--------------------------------------------------------------------------
    | Category Wise Payment Students (branch-scoped)
    |--------------------------------------------------------------------------
    */

    /**
     * Get category-wise payment students with pagination.
     *
     * @param int $classId
     * @param int $classCategoryFeeId
     * @param int $year
     * @param int $month
     * @param int $perPage
     * @return array{students: LengthAwarePaginator, pagination: array<string, int>}
     */
    public function classCategoryWisePaymentStudent(
        int $classId,
        int $classCategoryFeeId,
        int $year,
        int $month,
        int $perPage = 50
    ): array {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $query = StudentClassEnrollment::query()
            ->with([
                'branch',
                'student',
                'classCategoryFee.category',
                'classCategoryFeeOption',
                'payments' => function ($query) use ($year, $month) {
                    $query->whereYear('payment_month', $year)
                        ->whereMonth('payment_month', $month);
                },
            ])
            ->where('student_class_id', $classId)
            ->where('class_category_fee_id', $classCategoryFeeId)
            ->whereIn('branch_id', $accessibleBranchIds)
            ->where('is_active', true);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->orderBy('id')->paginate($perPage);

        $transformedItems = collect($paginator->items())->map(function ($enrollment) {

            $paidAmount = $enrollment->payments->sum('amount');

            $finalFee = $enrollment->is_free_card
                ? 0
                : (float) $enrollment->getSelectedFee();

            return [
                'enrollment_id' => $enrollment->id,
                'branch_id' => $enrollment->branch_id,
                'branch_name' => $enrollment->branch?->name ?? null,
                'student_id' => $enrollment->student_id,

                'student' => $enrollment->student
                    ? [
                        'id' => $enrollment->student->id,
                        'custom_id' => $enrollment->student->custom_id,
                        'initial_name' => $enrollment->student->initial_name,
                        'full_name' => $enrollment->student->full_name,
                        'mobile' => $enrollment->student->mobile,
                    ]
                    : null,

                'class_category_fee_id' =>
                $enrollment->class_category_fee_id,

                'class_category_fee_option_id' =>
                $enrollment->class_category_fee_option_id,

                'category_name' =>
                optional($enrollment->classCategoryFee)->category
                    ? $enrollment->classCategoryFee->category->category_name
                    : null,

                'fee_option' =>
                $enrollment->classCategoryFeeOption
                    ? [
                        'id' => $enrollment->classCategoryFeeOption->id,
                        'label' => $enrollment->classCategoryFeeOption->label,
                        'fee' => (float) $enrollment->classCategoryFeeOption->fee,
                    ]
                    : null,

                'is_free_card' => (bool) $enrollment->is_free_card,
                'final_fee' => $finalFee,
                'paid_amount' => (float) $paidAmount,
                'balance' => max($finalFee - $paidAmount, 0),

                'payment_status' =>
                $paidAmount <= 0
                    ? 'unpaid'
                    : ($paidAmount < $finalFee ? 'partial' : 'paid'),

                'registered_date' =>
                $enrollment->enrolled_at
                    ? $enrollment->enrolled_at->format('Y-m-d')
                    : null,
            ];
        })->values();

        $paginator->setCollection($transformedItems);

        return [
            'students' => $paginator,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}