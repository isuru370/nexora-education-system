<?php

namespace App\Services;

use App\Models\AdmissionPayment;
use App\Models\PaymentSplitSnapshot;
use App\Models\InstitutePayment;
use App\Models\ExtraIncome;

class InstituteIncomeService
{
    public function monthlyInstituteIncome($year, $month): array
    {
        // ============================================================
        // 1. MAIN PAYMENTS FROM PAYMENT SPLIT SNAPSHOTS
        // ============================================================

        $snapshots = PaymentSplitSnapshot::with([
                'teacher',
                'organizer',
                'studentClass.grade',
            ])
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->get();

        // ------------------------------------------------------------
        // MAIN INCOME
        //
        // class_fee  = fee related to teacher / organizer split
        // hall_fee   = 100% institution income
        // total_fee  = class_fee + hall_fee
        // payment_amount = actual amount paid by student
        // ------------------------------------------------------------

        $mainIncome = [
            'total_income' => $snapshots->sum('payment_amount'),

            // Class fee portion
            'class_fee_income' => $snapshots->sum('class_fee'),

            // Hall fee portion - 100% institution income
            'hall_fee_income' => $snapshots->sum('hall_fee'),

            // Total fee represented by snapshots
            'total_fee_income' => $snapshots->sum('total_fee'),

            // Split amounts
            'teacher_income' => $snapshots->sum('teacher_amount'),
            'organizer_income' => $snapshots->sum('organizer_amount'),
            'institution_income' => $snapshots->sum('institution_amount'),
        ];

        // ============================================================
        // 2. ADMISSION PAYMENT INCOME
        // ============================================================

        $admissionPayments = AdmissionPayment::query()
            ->with([
                'student',
                'admission',
            ])
            ->paid()
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $month)
            ->get();

        $totalAdmissionIncome = $admissionPayments->sum('amount');

        $admissionPaymentSummaries = $admissionPayments
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'student_id' => $payment->student_id,

                    'student_name' => optional($payment->student)->initial_name,

                    'admission_id' => $payment->admission_id,

                    'admission_name' => optional($payment->admission)->name,

                    'amount' => $payment->amount,

                    'paid_at' => optional($payment->paid_at)
                        ->format('Y-m-d'),

                    'payment_method' => $payment->payment_method,

                    'receipt_number' => $payment->receipt_number,

                    'note' => $payment->note,
                ];
            })
            ->values();

        // ============================================================
        // 3. EXTRA INCOME
        // ============================================================

        $extraIncomes = ExtraIncome::query()
            ->with('createdBy')
            ->whereYear('income_date', $year)
            ->whereMonth('income_date', $month)
            ->where('status', 'received')
            ->get();

        $totalExtraIncome = $extraIncomes->sum('amount');

        $extraIncomeSummaries = $extraIncomes
            ->map(function ($income) {
                return [
                    'id' => $income->id,
                    'amount' => $income->amount,

                    'income_date' => optional($income->income_date)
                        ->format('Y-m-d'),

                    'reason' => $income->reason,

                    'reason_code' => $income->reason_code,

                    'income_type' => $income->income_type,

                    'note' => $income->note,

                    'created_by' => optional($income->createdBy)->name,
                ];
            })
            ->values();

        // ============================================================
        // 4. EXPENSES
        // ============================================================

        $expenses = InstitutePayment::query()
            ->with('createdBy')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->where('status', 'paid')
            ->get();

        $totalExpenses = $expenses->sum('amount');

        $expenseSummaries = $expenses
            ->map(function ($expense) {
                return [
                    'id' => $expense->id,

                    'amount' => $expense->amount,

                    'payment_date' => optional($expense->payment_date)
                        ->format('Y-m-d'),

                    'reason' => $expense->reason,

                    'reason_code' => $expense->reason_code,

                    'note' => $expense->note,

                    'created_by' => optional($expense->createdBy)->name,
                ];
            })
            ->values();

        // ============================================================
        // 5. GROSS & NET INCOME
        // ============================================================

        /*
         * Institution income already includes:
         *
         * 1. Institution share from class fee
         * 2. 100% hall fee
         *
         * Therefore hall fee must NOT be added again here.
         */

        $grossIncome =
            $mainIncome['institution_income']
            + $totalExtraIncome
            + $totalAdmissionIncome;

        $netIncome = $grossIncome - $totalExpenses;

        $overall = [
            // Main student payments
            'class_income' => $mainIncome['total_income'],

            // Fee breakdown
            'class_fee_income' => $mainIncome['class_fee_income'],
            'hall_fee_income' => $mainIncome['hall_fee_income'],
            'total_fee_income' => $mainIncome['total_fee_income'],

            // Split income
            'teacher_income' => $mainIncome['teacher_income'],
            'organizer_income' => $mainIncome['organizer_income'],
            'institution_income' => $mainIncome['institution_income'],

            // Other income
            'admission_income' => $totalAdmissionIncome,
            'extra_income' => $totalExtraIncome,

            // Expenses
            'total_expenses' => $totalExpenses,

            // Final
            'gross_income' => $grossIncome,
            'net_income' => $netIncome,
        ];

        // ============================================================
        // 6. TEACHER SUMMARIES
        // ============================================================

        $teacherSummaries = $snapshots
            ->groupBy('teacher_id')
            ->map(function ($rows) {

                $teacher = $rows->first()
                    ? $rows->first()->teacher
                    : null;

                return [
                    'teacher_id' => optional($teacher)->id,

                    'teacher_custom_id' => optional($teacher)->custom_id,

                    'teacher_name' => optional($teacher)->full_name
                        ?: 'Unknown',

                    'teacher_initials' => optional($teacher)->initials,

                    'payment_count' => $rows->count(),

                    // Student payment
                    'total_income' => $rows->sum('payment_amount'),

                    // Fee breakdown
                    'class_fee_income' => $rows->sum('class_fee'),

                    'hall_fee_income' => $rows->sum('hall_fee'),

                    'total_fee_income' => $rows->sum('total_fee'),

                    // Split
                    'teacher_income' => $rows->sum('teacher_amount'),

                    'organizer_income' => $rows->sum('organizer_amount'),

                    'institution_income' => $rows->sum('institution_amount'),
                ];
            })
            ->values();

        // ============================================================
        // 7. ORGANIZER SUMMARIES
        // ============================================================

        $organizerSummaries = $snapshots
            ->filter(function ($row) {
                return $row->organizer !== null;
            })
            ->groupBy('organizer_id')
            ->map(function ($rows) {

                $organizer = $rows->first()
                    ? $rows->first()->organizer
                    : null;

                return [
                    'organizer_id' => optional($organizer)->id,

                    'organizer_code' => optional($organizer)->code,

                    'organizer_name' => optional($organizer)->name,

                    'payment_count' => $rows->count(),

                    // Student payment
                    'total_income' => $rows->sum('payment_amount'),

                    // Fee breakdown
                    'class_fee_income' => $rows->sum('class_fee'),

                    'hall_fee_income' => $rows->sum('hall_fee'),

                    'total_fee_income' => $rows->sum('total_fee'),

                    // Split
                    'teacher_income' => $rows->sum('teacher_amount'),

                    'organizer_income' => $rows->sum('organizer_amount'),

                    'institution_income' => $rows->sum('institution_amount'),
                ];
            })
            ->values();

        // ============================================================
        // 8. CLASS SUMMARIES
        // ============================================================

        $classSummaries = $snapshots
            ->groupBy('student_class_id')
            ->map(function ($rows) {

                $class = $rows->first()
                    ? $rows->first()->studentClass
                    : null;

                return [
                    'class_id' => optional($class)->id,

                    'class_name' => optional($class)->class_name
                        ?: 'Unknown',

                    'grade_name' => optional(optional($class)->grade)->grade_name
                        ?: 'Unknown',

                    'payment_count' => $rows->count(),

                    // Student payment
                    'total_income' => $rows->sum('payment_amount'),

                    // Fee breakdown
                    'class_fee_income' => $rows->sum('class_fee'),

                    'hall_fee_income' => $rows->sum('hall_fee'),

                    'total_fee_income' => $rows->sum('total_fee'),

                    // Split
                    'teacher_income' => $rows->sum('teacher_amount'),

                    'organizer_income' => $rows->sum('organizer_amount'),

                    'institution_income' => $rows->sum('institution_amount'),
                ];
            })
            ->values();

        // ============================================================
        // 9. FINAL RESPONSE
        // ============================================================

        return [
            'year' => $year,

            'month' => $month,

            'summary' => $overall,

            'admission_payment_list' => $admissionPaymentSummaries,

            'teacher_summaries' => $teacherSummaries,

            'organizer_summaries' => $organizerSummaries,

            'class_summaries' => $classSummaries,

            'extra_income_list' => $extraIncomeSummaries,

            'expense_list' => $expenseSummaries,
        ];
    }
}