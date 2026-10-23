<?php

namespace App\Services\Course;

use App\Models\CourseEnrollment;
use App\Models\CourseInstallment;

/**
 * Paid / balance for an enrollment. Completed payments fill the installments
 * oldest first, so cancelling a payment simply re-opens the latest ones.
 */
class CourseFeeService
{
    public function summary(CourseEnrollment $enrollment): array
    {
        $enrollment->loadMissing('installments');

        $total = round((float) $enrollment->total_fee, 2);
        $paid = round((float) $enrollment->payments()->where('status', 'completed')->sum('amount'), 2);
        $left = $paid;
        $today = now()->toDateString();

        $installments = $enrollment->installments->map(function (CourseInstallment $installment) use (&$left, $today) {
            $amount = round((float) $installment->amount, 2);
            $covered = round(min($amount, max(0, $left)), 2);
            $left = round($left - $covered, 2);
            $remaining = round($amount - $covered, 2);

            return [
                'id' => $installment->id,
                'installment_no' => $installment->installment_no,
                'amount' => $amount,
                'paid' => $covered,
                'remaining' => $remaining,
                'due_date' => $installment->due_date->toDateString(),
                'status' => $remaining <= 0 ? 'paid' : ($covered > 0 ? 'partly_paid' : ($installment->due_date->toDateString() < $today ? 'overdue' : 'due')),
            ];
        })->values();

        $next = $installments->firstWhere('remaining', '>', 0);

        return [
            'payment_plan' => $enrollment->payment_plan,
            'total_fee' => $total,
            'paid' => $paid,
            'balance' => max(0, round($total - $paid, 2)),
            'overdue' => round($installments->where('status', 'overdue')->sum('remaining'), 2),
            'next_installment' => $next,
            'installments' => $installments->all(),
        ];
    }
}
