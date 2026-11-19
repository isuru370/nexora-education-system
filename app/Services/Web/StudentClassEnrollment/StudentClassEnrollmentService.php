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
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();
        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $class = StudentClass::query()
            ->withTrashed()
            ->with(['grade:id,grade_name', 'teacher:id,full_name,initials'])
            ->whereIn('branch_id', $accessibleBranchIds)
            ->findOrFail($classId);

        $categoryFee = ClassCategoryFee::query()
            ->with('category:id,category_name')
            ->where('student_class_id', $class->id)
            ->findOrFail($classCategoryFeeId);

        // every student of the category (the totals are for all of them, not one page)
        $enrollments = StudentClassEnrollment::query()
            ->with([
                'student:id,custom_id,temporary_qr_code,permanent_qr_active,initial_name,full_name,mobile,guardian_mobile',
                'classCategoryFeeOption:id,class_category_fee_id,label,fee',
                // completed payments FOR this month (cancelled ones are not money)
                'payments' => fn ($query) => $query->where('status', 'completed')
                    ->whereBetween('payment_month', [$start->toDateString(), $end->toDateString()])
                    ->with('splitSnapshot:id,payment_id,hall_fee')
                    ->orderBy('paid_at'),
            ])
            ->where('student_class_id', $class->id)
            ->where('class_category_fee_id', $categoryFee->id)
            ->whereIn('branch_id', $accessibleBranchIds)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $hallFeeNow = \App\Support\MonthFee::hallFeeCache();

        $rows = $enrollments->map(function (StudentClassEnrollment $enrollment) use ($hallFeeNow) {
            $student = $enrollment->student;
            $fee = \App\Support\MonthFee::forEnrollment($enrollment, $enrollment->payments, $hallFeeNow($enrollment));

            return $fee + [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'student_code' => $student
                    ? ($student->permanent_qr_active ? $student->custom_id : ($student->temporary_qr_code ?: $student->custom_id))
                    : '-',
                'student_name' => optional($student)->initial_name ?: optional($student)->full_name ?: '-',
                'mobile' => optional($student)->guardian_mobile ?: optional($student)->mobile,
                'is_free_card' => (bool) $enrollment->is_free_card,
                'fee_option' => $enrollment->classCategoryFeeOption
                    ? ['label' => $enrollment->classCategoryFeeOption->label, 'fee' => (float) $enrollment->classCategoryFeeOption->fee]
                    : null,
                'paid_on' => $enrollment->payments->pluck('paid_at')->filter()
                    ->map(fn ($date) => \Carbon\Carbon::parse($date)->format('d M Y'))->unique()->implode(', '),
                'receipts' => $enrollment->payments->pluck('receipt_number')->filter()->implode(', '),
            ];
        });

        $count = fn (string $status) => $rows->where('status', $status)->count();

        $summary = [
            'students' => $rows->count(),
            'paid' => $count(\App\Support\MonthFee::PAID),
            'partial' => $count(\App\Support\MonthFee::PARTIAL),
            'unpaid' => $count(\App\Support\MonthFee::UNPAID),
            'freecard' => $count(\App\Support\MonthFee::FREE_CARD),
            'class_fee' => round($rows->sum('class_fee'), 2),
            'hall_fee' => round($rows->sum('hall_fee'), 2),
            'payable' => round($rows->sum('payable'), 2),
            'collected' => round($rows->sum('paid'), 2),
            'hall_paid' => round($rows->sum('hall_paid'), 2),
            'class_paid' => round($rows->sum('class_paid'), 2),
            'discount' => round($rows->sum('discount'), 2),
            'balance' => round($rows->sum('balance'), 2),
        ];

        $page = max((int) request()->get('page', 1), 1);
        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'class' => [
                'id' => $class->id,
                'class_name' => $class->class_name,
                'grade_name' => optional($class->grade)->grade_name,
                'teacher_name' => optional($class->teacher)->full_name,
                'branch_id' => $class->branch_id,
                'teacher_id' => $class->teacher_id,
            ],
            'category_name' => optional($categoryFee->category)->category_name ?? 'Category',
            'summary' => $summary,
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