<?php

namespace App\Services\Web\Payment;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Carbon\Carbon;

class PaymentDataBuilder
{
    /**
     * Build student information.
     */
    public function student(Student $student): array
    {
        $gradeName = null;

        if ($student->grade) {
            $gradeName = $student->grade->grade_name;
        }

        return [
            'id' => $student->id,
            'custom_id' => $student->custom_id,
            'full_name' => $student->full_name,
            'initial_name' => $student->initial_name,

            'image_url' => $student->img_url
                ? asset('storage/' . $student->img_url)
                : null,

            'mobile' => $student->mobile,
            'guardian_mobile' => $student->guardian_mobile,
            'guardian_name' => $student->guardian_name,
            'grade' => $gradeName,
        ];
    }

    /**
     * Build all active student classes.
     */
    public function classes($enrollments): array
    {
        return $enrollments
            ->map(function (StudentClassEnrollment $enrollment) {
                return $this->class($enrollment);
            })
            ->values()
            ->toArray();
    }

    /**
     * Build one class/enrollment.
     */
    public function class(
        StudentClassEnrollment $enrollment
    ): array {

        $studentClass = $enrollment->studentClass;
        $categoryFee = $enrollment->classCategoryFee;
        $category = null;
        $feeOption = $enrollment->classCategoryFeeOption;

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if ($categoryFee && $categoryFee->category) {
            $category = $categoryFee->category;
        }

        /*
        |--------------------------------------------------------------------------
        | Hall Fee
        |--------------------------------------------------------------------------
        */

        $pattern = null;

        if ($studentClass && $studentClass->schedulePatterns) {
            $pattern = $studentClass->schedulePatterns
                ->where(
                    'class_category_fee_id',
                    $enrollment->class_category_fee_id
                )
                ->sortByDesc('start_date')
                ->first();
        }

        $hallFee = 0;

        if ($pattern && $pattern->hall) {
            $hallFee = (float) ($pattern->hall->hall_price ?: 0);
        }

        /*
        |--------------------------------------------------------------------------
        | Selected Fee Option
        |--------------------------------------------------------------------------
        */

        $classFee = 0;

        if ($enrollment->is_free_card) {
            $classFee = 0;
        } elseif ($feeOption) {
            $classFee = (float) $feeOption->fee;
        } else {
            /*
            |--------------------------------------------------------------------------
            | Fallback
            |--------------------------------------------------------------------------
            |
            | Keep compatibility with enrollment final_fee accessor.
            |
            */

            $classFee = (float) $enrollment->final_fee;
        }

        /*
        |--------------------------------------------------------------------------
        | Total Fee
        |--------------------------------------------------------------------------
        */

        $totalFee = round(
            $classFee + $hallFee,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Student Class Information
        |--------------------------------------------------------------------------
        */

        $className = null;
        $subjectName = null;
        $gradeName = null;
        $teacher = null;

        if ($studentClass) {
            $className = $studentClass->class_name;

            if ($studentClass->subject) {
                $subjectName = $studentClass->subject->subject_name;
            }

            if ($studentClass->grade) {
                $gradeName = $studentClass->grade->grade_name;
            }

            $teacher = $studentClass->teacher;
        }

        /*
        |--------------------------------------------------------------------------
        | Category Information
        |--------------------------------------------------------------------------
        */

        $categoryName = null;
        $categoryId = null;
        $feeId = null;

        if ($category) {
            $categoryName = $category->category_name;
            $categoryId = $category->id;
        }

        if ($categoryFee) {
            $feeId = $categoryFee->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Fee Option Information
        |--------------------------------------------------------------------------
        */

        $feeOptionData = null;

        if ($feeOption) {
            $feeOptionData = [
                'id' => $feeOption->id,
                'class_category_fee_id' =>
                    $feeOption->class_category_fee_id,
                'label' => $feeOption->label,
                'fee' => (float) $feeOption->fee,
                'is_default' => (bool) $feeOption->is_default,
                'is_active' => (bool) $feeOption->is_active,
                'note' => $feeOption->note,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            /*
            |--------------------------------------------------------------------------
            | Enrollment
            |--------------------------------------------------------------------------
            */

            'enrollment_id' => $enrollment->id,

            'is_free_card' => (bool) $enrollment->is_free_card,

            /*
            |--------------------------------------------------------------------------
            | Class Information
            |--------------------------------------------------------------------------
            */

            'class_name' => $className,

            'subject' => $subjectName,

            'grade' => $gradeName,

            'teacher_initials' => $this->teacherInitials(
                $teacher
            ),

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category_name' => $categoryName,

            'category_id' => $categoryId,

            'fee_id' => $feeId,

            /*
            |--------------------------------------------------------------------------
            | Selected Fee Option
            |--------------------------------------------------------------------------
            */

            'fee_option' => $feeOptionData,

            /*
            |--------------------------------------------------------------------------
            | Fee
            |--------------------------------------------------------------------------
            */

            'class_fee' => $classFee,

            'hall_fee' => $hallFee,

            'total_fee' => $totalFee,

            /*
            |--------------------------------------------------------------------------
            | Keep old field for frontend compatibility
            |--------------------------------------------------------------------------
            */

            'final_fee' => $classFee,

            /*
            |--------------------------------------------------------------------------
            | Balance
            |--------------------------------------------------------------------------
            */

            'balance' => (float) $enrollment->balance,

            /*
            |--------------------------------------------------------------------------
            | Payment Status
            |--------------------------------------------------------------------------
            */

            'payment_status' => $enrollment->payment_status,

            /*
            |--------------------------------------------------------------------------
            | Attendance
            |--------------------------------------------------------------------------
            */

            'attendance' => $this->attendance(
                $enrollment
            ),

            /*
            |--------------------------------------------------------------------------
            | Last Payment
            |--------------------------------------------------------------------------
            */

            'last_payment' => $this->lastPayment(
                $enrollment
            ),
        ];
    }

    /**
     * Build payment information.
     *
     * Used by:
     * - Single payment response
     * - Bulk payment response
     * - ReceiptService
     */
    public function payment(Payment $payment): array
    {
        $payment->loadMissing([
            'student.grade',

            'enrollment.studentClass.teacher',
            'enrollment.studentClass.subject',
            'enrollment.studentClass.grade',

            'enrollment.classCategoryFee.category',

            'enrollment.classCategoryFeeOption',

            /*
            |--------------------------------------------------------------------------
            | Historical fee snapshot
            |--------------------------------------------------------------------------
            */

            'splitSnapshot',
        ]);

        $student = $payment->student;
        $enrollment = $payment->enrollment;

        if (!$enrollment) {
            throw new \RuntimeException(
                'Payment enrollment not found.'
            );
        }

        $studentClass = $enrollment->studentClass;
        $categoryFee = $enrollment->classCategoryFee;
        $feeOption = $enrollment->classCategoryFeeOption;

        $category = null;

        if ($categoryFee && $categoryFee->category) {
            $category = $categoryFee->category;
        }

        /*
        |--------------------------------------------------------------------------
        | Fee Snapshot
        |--------------------------------------------------------------------------
        |
        | For completed payments, use historical snapshot.
        |
        */

        $snapshot = $payment->splitSnapshot;

        /*
        |--------------------------------------------------------------------------
        | Class Fee
        |--------------------------------------------------------------------------
        */

        if ($snapshot) {
            $classFee = (float) $snapshot->class_fee;
        } elseif ($enrollment->is_free_card) {
            $classFee = 0;
        } elseif ($feeOption) {
            $classFee = (float) $feeOption->fee;
        } else {
            $classFee = (float) $enrollment->final_fee;
        }

        /*
        |--------------------------------------------------------------------------
        | Hall Fee
        |--------------------------------------------------------------------------
        */

        if ($snapshot) {
            $hallFee = (float) $snapshot->hall_fee;
        } else {
            $hallFee = $this->getCurrentHallFee(
                $enrollment
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Total Fee
        |--------------------------------------------------------------------------
        */

        if ($snapshot) {
            $totalFee = (float) $snapshot->total_fee;
        } else {
            $totalFee = round(
                $classFee + $hallFee,
                2
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Paid Amount
        |--------------------------------------------------------------------------
        */

        $paidAmount = (float) $payment->amount;

        /*
        |--------------------------------------------------------------------------
        | Balance
        |--------------------------------------------------------------------------
        */

        $balance = max(
            $totalFee - $paidAmount,
            0
        );

        /*
        |--------------------------------------------------------------------------
        | Student Information
        |--------------------------------------------------------------------------
        */

        $studentData = null;

        if ($student) {
            $studentGrade = null;

            if ($student->grade) {
                $studentGrade = $student->grade->grade_name;
            }

            $studentData = [
                'id' => $student->id,
                'custom_id' => $student->custom_id,

                'name' => $student->initial_name
                    ?: $student->full_name,

                'full_name' => $student->full_name,
                'initial_name' => $student->initial_name,

                'mobile' => $student->mobile,
                'guardian_mobile' => $student->guardian_mobile,
                'guardian_name' => $student->guardian_name,

                'grade' => $studentGrade,

                'image_url' => $student->img_url
                    ? asset(
                        'storage/' . $student->img_url
                    )
                    : null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Class Information
        |--------------------------------------------------------------------------
        */

        $classData = [
            'enrollment_id' => $enrollment->id,
            'class_name' => null,
            'subject' => null,
            'grade' => null,
            'teacher_initials' => null,
        ];

        if ($studentClass) {
            $classGrade = null;
            $subject = null;

            if ($studentClass->grade) {
                $classGrade = $studentClass
                    ->grade
                    ->grade_name;
            }

            if ($studentClass->subject) {
                $subject = $studentClass
                    ->subject
                    ->subject_name;
            }

            $classData = [
                'enrollment_id' => $enrollment->id,

                'class_name' => $studentClass->class_name,

                'subject' => $subject,

                'grade' => $classGrade,

                'teacher_initials' => $this->teacherInitials(
                    $studentClass->teacher
                ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Category Information
        |--------------------------------------------------------------------------
        */

        $categoryData = [
            'id' => null,
            'name' => null,
            'fee_id' => null,
        ];

        if ($category) {
            $categoryData['id'] = $category->id;
            $categoryData['name'] = $category->category_name;
        }

        if ($categoryFee) {
            $categoryData['fee_id'] = $categoryFee->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Fee Option Information
        |--------------------------------------------------------------------------
        */

        $feeOptionData = null;

        if ($feeOption) {
            $feeOptionData = [
                'id' => $feeOption->id,

                'class_category_fee_id' =>
                    $feeOption->class_category_fee_id,

                'label' => $feeOption->label,

                'fee' => (float) $feeOption->fee,

                'is_default' =>
                    (bool) $feeOption->is_default,

                'is_active' =>
                    (bool) $feeOption->is_active,

                'note' => $feeOption->note,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Payment Month
        |--------------------------------------------------------------------------
        */

        $paymentMonth = null;

        if ($payment->payment_month) {
            $paymentMonth = Carbon::parse(
                $payment->payment_month
            )->format('Y-m-d');
        }

        /*
        |--------------------------------------------------------------------------
        | Payment Status
        |--------------------------------------------------------------------------
        */

        $paymentStatus = $paidAmount >= $totalFee
            ? 'paid'
            : 'unpaid';

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return [
            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment_id' => $payment->id,

            'receipt_number' => $payment->receipt_number,

            'payment_month' => $paymentMonth,

            'payment_method' => $payment->payment_method,

            'mark_method' => $payment->mark_method,

            'paid_at' => $payment->paid_at
                ? $payment->paid_at->format(
                    'Y-m-d H:i:s'
                )
                : null,

            /*
            |--------------------------------------------------------------------------
            | Student
            |--------------------------------------------------------------------------
            */

            'student' => $studentData,

            /*
            |--------------------------------------------------------------------------
            | Class
            |--------------------------------------------------------------------------
            */

            'class' => $classData,

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category' => $categoryData,

            /*
            |--------------------------------------------------------------------------
            | Selected Fee Option
            |--------------------------------------------------------------------------
            */

            'fee_option' => $feeOptionData,

            /*
            |--------------------------------------------------------------------------
            | Fee
            |--------------------------------------------------------------------------
            */

            'fee' => [
                'class_fee' => $classFee,

                'hall_fee' => $hallFee,

                'total_fee' => $totalFee,

                'final_fee' => $classFee,

                'discount_amount' =>
                    (float) $payment->discount_amount,

                'paid_amount' => $paidAmount,

                'balance' => $balance,

                'payment_status' => $paymentStatus,
            ],
        ];
    }

    /**
     * Get current hall fee.
     *
     * Used when payment does not have snapshot data.
     */
    private function getCurrentHallFee(
        StudentClassEnrollment $enrollment
    ): float {

        $studentClass = $enrollment->studentClass;

        if (!$studentClass) {
            return 0;
        }

        $pattern = $studentClass
            ->schedulePatterns()
            ->where('is_active', true)
            ->where(
                'class_category_fee_id',
                $enrollment->class_category_fee_id
            )
            ->with('hall')
            ->latest('start_date')
            ->first();

        if (!$pattern || !$pattern->hall) {
            return 0;
        }

        return (float) (
            $pattern->hall->hall_price ?: 0
        );
    }

    /**
     * Get teacher initials.
     */
    private function teacherInitials($teacher): ?string
    {
        if (!$teacher) {
            return null;
        }

        if (!empty($teacher->initials)) {
            return $teacher->initials;
        }

        if (!empty($teacher->initial_name)) {
            return $teacher->initial_name;
        }

        return null;
    }

    /**
     * Attendance.
     */
    private function attendance(
        StudentClassEnrollment $enrollment
    ): array {
        return [
            'class_days' => 0,
            'attended_days' => 0,
        ];
    }

    /**
     * Get latest completed payment for this enrollment.
     */
    private function lastPayment(
        StudentClassEnrollment $enrollment
    ): ?array {

        $payment = $enrollment
            ->payments()
            ->where('status', 'completed')
            ->orderByDesc('payment_month')
            ->orderByDesc('paid_at')
            ->first();

        if (!$payment) {
            return null;
        }

        return [
            'id' => $payment->id,

            'receipt_number' => $payment->receipt_number,

            'amount' => (float) $payment->amount,

            'payment_month' => $payment->payment_month
                ? $payment->payment_month->format('Y-m-d')
                : null,

            'payment_month_name' => $payment->payment_month
                ? $payment->payment_month->format('F Y')
                : null,

            'paid_at' => $payment->paid_at
                ? $payment->paid_at->format(
                    'Y-m-d H:i:s'
                )
                : null,

            'payment_method' => $payment->payment_method,
        ];
    }
}