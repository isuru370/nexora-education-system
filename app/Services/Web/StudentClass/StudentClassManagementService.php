<?php

namespace App\Services\Web\StudentClass;

use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Models\ClassSchedule;
use App\Models\StudentTute;
use App\Services\Branch\BranchAccessService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentClassManagementService
{
    public function __construct(
        protected BranchAccessService $branchAccessService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Get Student Classes
    |--------------------------------------------------------------------------
    */

    /**
     * Get all classes for a student (branch-scoped).
     *
     * @param int $studentId
     * @return Collection<int, array<string, mixed>>
     */
    public function getStudentClasses(int $studentId): Collection
    {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $student = Student::with([
            'enrollments' => function ($query) use ($accessibleBranchIds) {
                $query->whereIn('branch_id', $accessibleBranchIds);
            },
            'enrollments.branch',
            'enrollments.studentClass.teacher',
            'enrollments.studentClass.grade',
            'enrollments.classCategoryFee.category',
            'enrollments.classCategoryFeeOption',
        ])->findOrFail($studentId);

        return $student->enrollments->map(function ($enrollment) {
            return [
                'enrollment_id' => $enrollment->id,
                'branch_id' => $enrollment->branch_id,
                'branch_name' => $enrollment->branch?->name ?? 'N/A',
                'class_id' => $enrollment->student_class_id,

                'class_name' =>
                $enrollment->studentClass?->class_name ?? 'N/A',

                'class_type' =>
                $enrollment->studentClass?->class_type ?? 'N/A',

                'medium' =>
                $enrollment->studentClass?->medium ?? 'N/A',

                'grade_name' =>
                $enrollment->studentClass?->grade?->grade_name ?? 'N/A',

                'teacher_initials' =>
                $enrollment->studentClass?->teacher?->initials ?? 'N/A',

                'category_name' =>
                $enrollment->classCategoryFee?->category?->category_name ?? 'N/A',

                'class_category_fee_id' =>
                $enrollment->class_category_fee_id,

                'class_category_fee_option_id' =>
                $enrollment->class_category_fee_option_id,

                'fee_option_label' =>
                $enrollment->classCategoryFeeOption?->label ?? 'N/A',

                'fee' =>
                (float) ($enrollment->classCategoryFeeOption?->fee ?? 0),

                'is_active' =>
                (bool) $enrollment->is_active,

                'is_free_card' =>
                (bool) $enrollment->is_free_card,

                'final_fee' =>
                (float) $enrollment->final_fee,

                'payment_status' =>
                $enrollment->payment_status,

                'enrolled_at' =>
                $enrollment->enrolled_at?->format('Y-m-d'),

                'left_at' =>
                $enrollment->left_at?->format('Y-m-d'),

                'note' =>
                $enrollment->note,
            ];
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Get Class Details
    |--------------------------------------------------------------------------
    */

    /**
     * Get enrollment details (branch-scoped).
     *
     * @param int $studentId
     * @param int $studentClassId
     * @param int $enrollmentId
     * @return array<string, mixed>
     */
    public function getClassDetails(
        int $studentId,
        int $studentClassId,
        int $enrollmentId
    ): array {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $enrollment = StudentClassEnrollment::with([
            'student',
            'branch',
            'studentClass.teacher',
            'studentClass.grade',
            'studentClass.subject',
            'classCategoryFee.category',
            'classCategoryFeeOption',
        ])
            ->where('id', $enrollmentId)
            ->where('student_id', $studentId)
            ->where('student_class_id', $studentClassId)
            ->whereIn('branch_id', $accessibleBranchIds)
            ->firstOrFail();

        return [
            'student' => $enrollment->student,
            'enrollment' => $enrollment,

            'branch_id' => $enrollment->branch_id,
            'branch' => $enrollment->branch,

            'class' => $enrollment->studentClass,
            'category' => $enrollment->classCategoryFee?->category,

            'class_category_fee_id' =>
            $enrollment->class_category_fee_id,

            'class_category_fee_option_id' =>
            $enrollment->class_category_fee_option_id,

            'fee_option' => $enrollment->classCategoryFeeOption,

            'teacher' => $enrollment->studentClass?->teacher,
            'grade' => $enrollment->studentClass?->grade,

            'fee' => (float) ($enrollment->classCategoryFeeOption?->fee ?? 0),
            'final_fee' => (float) $enrollment->final_fee,

            'payment_status' => $enrollment->payment_status,
            'is_free_card' => (bool) $enrollment->is_free_card,

            'enrolled_at' => $enrollment->enrolled_at,
            'left_at' => $enrollment->left_at,
            'is_active' => (bool) $enrollment->is_active,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get Payment Details
    |--------------------------------------------------------------------------
    */

    /**
     * Get month-wise payment details (branch-scoped).
     *
     * Payments before the enrollment month are ignored.
     *
     * @param int $studentId
     * @param int $studentClassId
     * @param int $enrollmentId
     * @return array<string, mixed>
     */
    public function getPaymentDetails(
        int $studentId,
        int $studentClassId,
        int $enrollmentId
    ): array {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $enrollment = StudentClassEnrollment::with([
            'student',
            'branch',
            'studentClass.teacher',
            'studentClass.grade',
            'studentClass.subject',
            'classCategoryFee.category',
            'classCategoryFeeOption',

            'payments' => function ($query) {
                $query
                    ->orderByDesc('payment_month')
                    ->orderByDesc('paid_at')
                    ->orderByDesc('id');
            },
        ])
            ->where('id', $enrollmentId)
            ->where('student_id', $studentId)
            ->where('student_class_id', $studentClassId)
            ->whereIn('branch_id', $accessibleBranchIds)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Enrollment Date
        |--------------------------------------------------------------------------
        */

        $enrollmentDate = $enrollment->enrolled_at;

        $enrollmentMonth = $enrollmentDate
            ? $enrollmentDate->copy()->startOfMonth()
            : null;

        /*
        |--------------------------------------------------------------------------
        | Payment Records
        |--------------------------------------------------------------------------
        */

        $payments = $enrollment->payments
            ->filter(function ($payment) use ($enrollmentMonth) {

                if (!$payment->payment_month) {
                    return false;
                }

                if ($enrollmentMonth) {
                    return $payment->payment_month
                        ->copy()
                        ->startOfMonth()
                        ->greaterThanOrEqualTo($enrollmentMonth);
                }

                return true;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Monthly Fee (from final_fee)
        |--------------------------------------------------------------------------
        */

        $monthlyFee = (float) $enrollment->final_fee;

        /*
        |--------------------------------------------------------------------------
        | Month-wise Payment Data
        |--------------------------------------------------------------------------
        */

        $monthlyPayments = collect();

        if ($enrollmentMonth) {

            $endMonth = now()->startOfMonth();

            if ($enrollment->left_at) {
                $leftMonth = $enrollment->left_at
                    ->copy()
                    ->startOfMonth();

                if ($leftMonth->lessThan($endMonth)) {
                    $endMonth = $leftMonth;
                }
            }

            $currentMonth = $enrollmentMonth->copy();

            while ($currentMonth->lessThanOrEqualTo($endMonth)) {

                $monthKey = $currentMonth->format('Y-m');

                $monthPayments = $payments
                    ->filter(function ($payment) use ($monthKey) {
                        return $payment->payment_month
                            ->copy()
                            ->format('Y-m') === $monthKey;
                    })
                    ->values();

                $paidAmount = (float) $monthPayments->sum('amount');
                $discountAmount = (float) $monthPayments->sum('discount_amount');

                $balance = max($monthlyFee - $paidAmount, 0);

                $paymentStatus = $paidAmount >= $monthlyFee ? 'paid' : 'unpaid';

                $monthlyPayments->push([
                    'month' => $currentMonth->copy(),
                    'month_key' => $monthKey,
                    'month_name' => $currentMonth->format('F Y'),
                    'expected_fee' => $monthlyFee,
                    'paid_amount' => $paidAmount,
                    'discount_amount' => $discountAmount,
                    'balance' => $balance,
                    'payment_status' => $paymentStatus,
                    'payments' => $monthPayments,
                ]);

                $currentMonth->addMonth();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Overall Summary
        |--------------------------------------------------------------------------
        */

        $totalPaidAmount = (float) $payments->sum('amount');
        $totalDiscountAmount = (float) $payments->sum('discount_amount');
        $totalExpectedAmount = (float) $monthlyPayments->sum('expected_fee');

        $totalBalance = max($totalExpectedAmount - $totalPaidAmount, 0);

        if ($totalPaidAmount <= 0) {
            $paymentStatus = 'unpaid';
        } elseif ($totalBalance > 0) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'paid';
        }

        /*
        |--------------------------------------------------------------------------
        | Return Data
        |--------------------------------------------------------------------------
        */

        return [
            'student' => $enrollment->student,
            'enrollment' => $enrollment,

            'branch_id' => $enrollment->branch_id,
            'branch' => $enrollment->branch,

            'class' => $enrollment->studentClass,
            'category' => $enrollment->classCategoryFee?->category,
            'teacher' => $enrollment->studentClass?->teacher,
            'grade' => $enrollment->studentClass?->grade,

            'class_category_fee_id' =>
            $enrollment->class_category_fee_id,

            'class_category_fee_option_id' =>
            $enrollment->class_category_fee_option_id,

            'fee_option' => $enrollment->classCategoryFeeOption,

            'enrolled_at' => $enrollment->enrolled_at,
            'left_at' => $enrollment->left_at,

            'monthly_fee' => $monthlyFee,
            'payments' => $payments,
            'monthly_payments' => $monthlyPayments,

            'expected_fee' => $totalExpectedAmount,
            'paid_amount' => $totalPaidAmount,
            'discount_amount' => $totalDiscountAmount,
            'balance' => $totalBalance,
            'payment_status' => $paymentStatus,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get Attendance Details
    |--------------------------------------------------------------------------
    */

    /**
     * Get month-wise attendance details (branch-scoped).
     *
     * @param int $studentId
     * @param int $studentClassId
     * @param int $enrollmentId
     * @return array<string, mixed>
     */
    public function getAttendanceDetails(
        int $studentId,
        int $studentClassId,
        int $enrollmentId
    ): array {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $enrollment = StudentClassEnrollment::with([
            'student',
            'branch',
            'studentClass.teacher',
            'studentClass.grade',
            'studentClass.subject',
            'classCategoryFee.category',
            'classCategoryFeeOption',
        ])
            ->where('id', $enrollmentId)
            ->where('student_id', $studentId)
            ->where('student_class_id', $studentClassId)
            ->whereIn('branch_id', $accessibleBranchIds)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Enrollment Period
        |--------------------------------------------------------------------------
        */

        $enrolledAt = $enrollment->enrolled_at
            ? $enrollment->enrolled_at->copy()->startOfDay()
            : null;

        $leftAt = $enrollment->left_at
            ? $enrollment->left_at->copy()->endOfDay()
            : null;

        /*
        |--------------------------------------------------------------------------
        | Get Completed Class Schedules
        |--------------------------------------------------------------------------
        */

        $schedules = ClassSchedule::query()
            ->where('student_class_id', $studentClassId)
            ->where('class_category_fee_id', $enrollment->class_category_fee_id)
            ->where('branch_id', $enrollment->branch_id)
            ->where('status', 'completed')
            ->where('is_active', true)

            ->when($enrolledAt, function ($query) use ($enrolledAt) {
                $query->whereDate('class_date', '>=', $enrolledAt->toDateString());
            })

            ->when($leftAt, function ($query) use ($leftAt) {
                $query->whereDate('class_date', '<=', $leftAt->toDateString());
            })

            ->with([
                'studentAttendances' => function ($query) use ($studentId, $enrollmentId) {
                    $query
                        ->where('student_id', $studentId)
                        ->where('student_class_enrollment_id', $enrollmentId);
                },
            ])
            ->orderBy('class_date')
            ->orderBy('start_time')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Daily Attendance Data
        |--------------------------------------------------------------------------
        */

        $attendanceData = $schedules
            ->map(function ($schedule) {
                $attended = $schedule->studentAttendances->isNotEmpty();

                return [
                    'schedule_id' => $schedule->id,
                    'date' => $schedule->class_date
                        ? $schedule->class_date->format('Y-m-d')
                        : null,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'status' => $attended ? 'attended' : 'absent',
                    'attended' => $attended,
                ];
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Monthly Attendance
        |--------------------------------------------------------------------------
        */

        $monthlyAttendance = $attendanceData
            ->groupBy(function ($attendance) {
                return Carbon::parse($attendance['date'])->format('Y-m');
            })
            ->map(function ($monthData, $month) {
                $total = $monthData->count();
                $attended = $monthData->where('attended', true)->count();
                $absent = $monthData->where('attended', false)->count();

                $percentage = $total > 0
                    ? round(($attended / $total) * 100, 2)
                    : 0;

                return [
                    'month' => $month,
                    'month_name' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                    'total_days' => $total,
                    'attended_days' => $attended,
                    'absent_days' => $absent,
                    'attendance_percentage' => $percentage,
                    'attendance_data' => $monthData->values(),
                ];
            })
            ->sortBy('month')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Overall Attendance Summary
        |--------------------------------------------------------------------------
        */

        $totalDays = $attendanceData->count();
        $attendedDays = $attendanceData->where('attended', true)->count();
        $absentDays = $attendanceData->where('attended', false)->count();

        $attendancePercentage = $totalDays > 0
            ? round(($attendedDays / $totalDays) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Return Data
        |--------------------------------------------------------------------------
        */

        return [
            'student' => $enrollment->student,
            'enrollment' => $enrollment,

            'branch_id' => $enrollment->branch_id,
            'branch' => $enrollment->branch,

            'class' => $enrollment->studentClass,
            'category' => $enrollment->classCategoryFee?->category,
            'teacher' => $enrollment->studentClass?->teacher,
            'grade' => $enrollment->studentClass?->grade,

            'class_category_fee_id' =>
            $enrollment->class_category_fee_id,

            'class_category_fee_option_id' =>
            $enrollment->class_category_fee_option_id,

            'fee_option' => $enrollment->classCategoryFeeOption,

            'enrolled_at' => $enrollment->enrolled_at,
            'left_at' => $enrollment->left_at,

            'attendance_data' => $attendanceData,
            'monthly_attendance' => $monthlyAttendance,

            'total_days' => $totalDays,
            'attended_days' => $attendedDays,
            'absent_days' => $absentDays,
            'attendance_percentage' => $attendancePercentage,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get Tute Details
    |--------------------------------------------------------------------------
    */

    /**
     * Get class + category-wise tutes (branch-scoped).
     *
     * @param int $studentId
     * @param int $studentClassId
     * @param int $enrollmentId
     * @return array<string, mixed>
     */
    public function getTuteDetails(
        int $studentId,
        int $studentClassId,
        int $enrollmentId
    ): array {
        /** @var Collection<int, int> $accessibleBranchIds */
        $accessibleBranchIds = $this->branchAccessService->getBranchIds();

        $enrollment = StudentClassEnrollment::with([
            'student',
            'branch',
            'studentClass.teacher',
            'studentClass.grade',
            'studentClass.subject',
            'classCategoryFee.category',
            'classCategoryFeeOption',
        ])
            ->where('id', $enrollmentId)
            ->where('student_id', $studentId)
            ->where('student_class_id', $studentClassId)
            ->whereIn('branch_id', $accessibleBranchIds)
            ->firstOrFail();

        $classCategoryFeeId = $enrollment->class_category_fee_id;

        /*
        |--------------------------------------------------------------------------
        | Get Class + Category-wise Tutes
        |--------------------------------------------------------------------------
        */

        $tutes = StudentTute::query()
            ->where('student_id', $studentId)
            ->where('student_class_enrollment_id', $enrollmentId)

            ->whereHas(
                'enrollment',
                function ($query) use ($studentClassId, $classCategoryFeeId) {
                    $query
                        ->where('student_class_id', $studentClassId)
                        ->where('class_category_fee_id', $classCategoryFeeId);
                }
            )

            ->with(['issuedBy'])
            ->orderByDesc('issued_month')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Tute Summary
        |--------------------------------------------------------------------------
        */

        $totalTutes = $tutes->count();
        $issuedTutes = $tutes->where('is_issued', true)->count();
        $pendingTutes = $tutes->where('is_issued', false)->count();

        return [
            'student' => $enrollment->student,
            'enrollment' => $enrollment,

            'branch_id' => $enrollment->branch_id,
            'branch' => $enrollment->branch,

            'class' => $enrollment->studentClass,
            'category' => $enrollment->classCategoryFee?->category,
            'teacher' => $enrollment->studentClass?->teacher,
            'grade' => $enrollment->studentClass?->grade,

            'class_category_fee_id' => $classCategoryFeeId,

            'class_category_fee_option_id' =>
            $enrollment->class_category_fee_option_id,

            'fee_option' => $enrollment->classCategoryFeeOption,

            'enrolled_at' => $enrollment->enrolled_at,

            'tutes' => $tutes,
            'total_tutes' => $totalTutes,
            'issued_tutes' => $issuedTutes,
            'pending_tutes' => $pendingTutes,
        ];
    }
}