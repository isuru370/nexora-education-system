<?php

namespace App\Services\Parent\Payment;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentPaymentService
{
    public function fetchPaymentHistory(
        int $studentId
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Student
        |--------------------------------------------------------------------------
        */

        $student = Student::query()
            ->select([
                'id',
                'initial_name',
                'grade_id',
            ])
            ->with([
                'grade:id,grade_name',
            ])
            ->find($studentId);

        if (!$student) {
            return [
                'status' => false,
                'message' => 'Student not found.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ✅ OPTION 1: Get Enrollments with Payments using whereHas
        |--------------------------------------------------------------------------
        */

        $enrollments = StudentClassEnrollment::query()
            ->select([
                'id',
                'student_class_id',
                'class_category_fee_id',
                'class_category_fee_option_id',
                'is_free_card',
                'enrolled_at',
            ])
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->with([

                // Eager load all relations
                'studentClass:id,class_name,teacher_id,grade_id',
                'studentClass.teacher:id,initials',
                'studentClass.grade:id,grade_name',
                'classCategoryFee:id,class_category_id',
                'classCategoryFee.category:id,category_name',
                'classCategoryFeeOption:id,class_category_fee_id,fee',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | ✅ OPTION 2: Alternative - Get All Payments Separately (Easier to Debug)
        |--------------------------------------------------------------------------
        */

        // Get all payments for this student
        $allPayments = Payment::query()
            ->select([
                'id',
                'student_class_enrollment_id',
                'student_id',
                'user_id',
                'amount',
                'discount_amount',
                'payment_month',
                'paid_at',
                'payment_method',
                'mark_method',
                'status',
                'receipt_number',
                'reference_number',
                'note',
            ])
            ->where('student_id', $studentId)
            // Only real payments - cancelled / refunded ones are not "paid"
            ->where('status', 'completed')
            ->with([
                'collectedBy:id,name',
            ])
            ->orderByDesc('payment_month')
            ->orderByDesc('paid_at')
            ->get();

        // Group payments by enrollment_id
        $paymentsByEnrollment = $allPayments->groupBy('student_class_enrollment_id');

        // Free class payments (no enrollment)
        $freeClassPayments = $allPayments->whereNull('student_class_enrollment_id');

        /*
        |--------------------------------------------------------------------------
        | Process Data
        |--------------------------------------------------------------------------
        */

        $classes = [];
        $totalPayments = 0;
        $totalPaidAmount = 0;
        $totalUnpaidMonths = 0;

        // ✅ Get current month
        $currentMonth = Carbon::now()->startOfMonth();
        $currentMonthString = $currentMonth->format('Y-m');

        foreach ($enrollments as $enrollment) {

            $classId = $enrollment->student_class_id;

            // ✅ Get payments for this enrollment (from grouped collection)
            $classPayments = $paymentsByEnrollment->get($enrollment->id, collect());

            // ✅ Debug: Log payment count
            // \Log::info('Enrollment ID: ' . $enrollment->id . ' - Payments: ' . $classPayments->count());

            // ✅ Get paid months
            $paidMonths = $classPayments
                ->pluck('payment_month')
                ->map(function ($date) {
                    return $date?->format('Y-m');
                })
                ->filter()
                ->toArray();

            // ✅ Check if current month is paid
            $isCurrentMonthPaid = in_array($currentMonthString, $paidMonths);

            // ✅ Calculate monthly fee
            $monthlyFee = $this->calculateMonthlyFee($enrollment);

            // ✅ Get enrollment start month
            $startMonth = $enrollment->enrolled_at?->startOfMonth() ?? $currentMonth;

            // ✅ Get all months from enrollment to current month
            $allMonths = $this->getMonthsBetween($startMonth, $currentMonth);

            // ✅ Calculate unpaid months
            $unpaidMonths = array_diff($allMonths, $paidMonths);
            $totalUnpaidMonths += count($unpaidMonths);

            // ✅ Build month status array
            $monthStatus = [];
            foreach ($allMonths as $month) {
                $monthStatus[] = [
                    'month' => $month,
                    'month_name' => $this->getMonthName($month),
                    'is_paid' => in_array($month, $paidMonths),
                    'status' => in_array($month, $paidMonths) ? 'paid' : 'unpaid',
                    'is_current' => $month === $currentMonthString,
                ];
            }

            // ✅ Build class data
            if (!isset($classes[$classId])) {

                $classes[$classId] = [
                    'class_id' => $classId,
                    'class_name' => $enrollment->studentClass?->class_name,
                    'category_name' => $enrollment->classCategoryFee?->category?->category_name,
                    'grade_name' => $enrollment->studentClass?->grade?->grade_name,
                    'teacher' => $enrollment->studentClass?->teacher?->initials,
                    'monthly_fee' => $monthlyFee,
                    'is_current_month_paid' => $isCurrentMonthPaid,
                    'current_month' => $currentMonthString,
                    'payment_count' => $classPayments->count(),
                    'total_paid_amount' => $classPayments->sum('amount'),
                    'unpaid_months_count' => count($unpaidMonths),
                    'unpaid_months' => array_values($unpaidMonths),
                    'paid_months' => $paidMonths,
                    'all_months' => $allMonths,
                    'month_status' => $monthStatus,
                    'payments' => [],
                ];
            }

            // ✅ Add payment details
            foreach ($classPayments as $payment) {

                $classes[$classId]['payments'][] = [
                    'payment_id' => $payment->id,
                    'receipt_number' => $payment->receipt_number,
                    'payment_month' => $payment->payment_month?->format('Y-m'),
                    'payment_month_name' => $payment->payment_month ? $this->getMonthName($payment->payment_month->format('Y-m')) : null,
                    'amount' => (float) $payment->amount,
                    'discount_amount' => (float) $payment->discount_amount,
                    'paid_at' => $payment->paid_at,
                    'payment_method' => $payment->payment_method,
                    'mark_method' => $payment->mark_method,
                    'status' => $payment->status,
                    'reference_number' => $payment->reference_number,
                    'note' => $payment->note,
                    'collected_by' => [
                        'id' => $payment->collectedBy?->id,
                        'name' => $payment->collectedBy?->name,
                    ],
                ];

                $totalPayments++;
                $totalPaidAmount += $payment->amount;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ✅ Handle Free Class Payments (No Enrollment)
        |--------------------------------------------------------------------------
        */

        if ($freeClassPayments->isNotEmpty()) {
            $freeClassData = [
                'class_id' => null,
                'class_name' => 'Free Classes / Demo',
                'category_name' => 'Free',
                'grade_name' => null,
                'teacher' => null,
                'monthly_fee' => 0,
                'is_current_month_paid' => false,
                'current_month' => $currentMonthString,
                'payment_count' => $freeClassPayments->count(),
                'total_paid_amount' => $freeClassPayments->sum('amount'),
                'unpaid_months_count' => 0,
                'unpaid_months' => [],
                'paid_months' => [],
                'all_months' => [],
                'month_status' => [],
                'payments' => [],
            ];

            foreach ($freeClassPayments as $payment) {
                $freeClassData['payments'][] = [
                    'payment_id' => $payment->id,
                    'receipt_number' => $payment->receipt_number,
                    'payment_month' => $payment->payment_month?->format('Y-m'),
                    'payment_month_name' => $payment->payment_month ? $this->getMonthName($payment->payment_month->format('Y-m')) : null,
                    'amount' => (float) $payment->amount,
                    'discount_amount' => (float) $payment->discount_amount,
                    'paid_at' => $payment->paid_at,
                    'payment_method' => $payment->payment_method,
                    'mark_method' => $payment->mark_method,
                    'status' => $payment->status,
                    'reference_number' => $payment->reference_number,
                    'note' => $payment->note,
                    'collected_by' => [
                        'id' => $payment->collectedBy?->id,
                        'name' => $payment->collectedBy?->name,
                    ],
                ];

                $totalPayments++;
                $totalPaidAmount += $payment->amount;
            }

            $classes['free'] = $freeClassData;
        }

        /*
        |--------------------------------------------------------------------------
        | Sort Classes
        |--------------------------------------------------------------------------
        */

        $classes = collect($classes)
            ->sortBy('class_name')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return [
            'status' => true,
            'message' => 'Payment history fetched successfully.',
            'data' => [
                'summary' => [
                    'total_classes' => $classes->count(),
                    'total_payments' => $totalPayments,
                    'total_paid_amount' => (float) $totalPaidAmount,
                    'total_unpaid_months' => $totalUnpaidMonths,
                    'current_month' => $currentMonthString,
                ],
                'classes' => $classes,
            ],
        ];
    }

    /**
     * ✅ Calculate monthly fee for an enrollment
     */
    private function calculateMonthlyFee($enrollment): float
    {
        // Free card - no fee
        if ($enrollment->is_free_card) {
            return 0;
        }

        // The fee is on the fee option chosen for this enrollment
        return round((float) ($enrollment->classCategoryFeeOption?->fee ?? 0), 2);
    }

    /**
     * ✅ Get all months between two dates
     */
    private function getMonthsBetween(Carbon $startDate, Carbon $endDate): array
    {
        $months = [];
        $current = $startDate->copy()->startOfMonth();

        while ($current <= $endDate) {
            $months[] = $current->format('Y-m');
            $current->addMonth();
        }

        return $months;
    }

    /**
     * ✅ Get month name from Y-m format
     */
    private function getMonthName(string $yearMonth): string
    {
        $months = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];

        $parts = explode('-', $yearMonth);
        if (count($parts) === 2) {
            return $months[$parts[1]] . ' ' . $parts[0];
        }

        return $yearMonth;
    }
}
