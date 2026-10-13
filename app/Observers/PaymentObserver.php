<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Payment;

class PaymentObserver
{
    /**
     * CREATE
     */
    public function created(Payment $payment): void
    {
        ActivityLog::create([
            'branch_id' => $payment->branch_id,
            'table_name' => $payment->getTable(),
            'record_id' => $payment->id,
            'action' => 'created',
            'new_values' => $payment->toArray(),
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * UPDATE
     */
    public function updated(Payment $payment): void
    {
        ActivityLog::create([
            'branch_id' => $payment->branch_id,
            'table_name' => $payment->getTable(),
            'record_id' => $payment->id,
            'action' => 'updated',
            'old_values' => $payment->getOriginal(),
            'new_values' => $payment->getChanges(),
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * SOFT DELETE (wrong payment removed so it can be paid again)
     */
    public function deleted(Payment $payment): void
    {
        // Permanent deletes are logged in forceDeleted()
        if ($payment->isForceDeleting()) {
            return;
        }

        ActivityLog::create([
            'branch_id' => $payment->branch_id,
            'table_name' => $payment->getTable(),
            'record_id' => $payment->id,
            'action' => 'deleted',
            'old_values' => [
                'receipt_number' => $payment->receipt_number,
                'student_id' => $payment->student_id,
                'student_class_enrollment_id' => $payment->student_class_enrollment_id,
                'payment_month' => optional($payment->payment_month)->format('Y-m-d'),
                'amount' => $payment->amount,
            ],
            'new_values' => [
                'delete_reason' => $payment->delete_reason,
            ],
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * RESTORE
     */
    public function restored(Payment $payment): void
    {
        ActivityLog::create([
            'branch_id' => $payment->branch_id,
            'table_name' => $payment->getTable(),
            'record_id' => $payment->id,
            'action' => 'restored',
            'old_values' => null,
            'new_values' => [
                'receipt_number' => $payment->receipt_number,
            ],
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * FORCE DELETE
     */
    public function forceDeleted(Payment $payment): void
    {
        ActivityLog::create([
            'branch_id' => $payment->branch_id,
            'table_name' => $payment->getTable(),
            'record_id' => $payment->id,
            'action' => 'force_deleted',
            'old_values' => [
                'payment' => $payment->toArray(),
            ],
            'new_values' => null,
            'user_id' => auth()->id(),
        ]);
    }
}