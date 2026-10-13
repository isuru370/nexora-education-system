<?php

namespace App\Services\Web\Payment;

use App\Models\Payment;
use App\Services\Branch\BranchAccessService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Soft delete a wrong payment so the correct one can be recorded
 * for the same class + month.
 *
 * The payment and its split snapshot are soft deleted together, so the
 * payment disappears from receipts, dashboards and teacher/institute
 * reports, but stays in the database for audit (deleted_by, delete_reason).
 */
class PaymentDeleteService
{
    /**
     * Payments older than this cannot be deleted by staff.
     */
    public const DELETE_WINDOW_DAYS = 14;

    public function __construct(
        protected BranchAccessService $branchAccess
    ) {}

    /**
     * @throws DomainException
     */
    public function delete(int $paymentId, string $reason): Payment
    {
        return DB::transaction(function () use ($paymentId, $reason) {

            $payment = Payment::query()
                ->with('splitSnapshot')
                ->lockForUpdate()
                ->find($paymentId);

            if (!$payment) {
                throw new DomainException('Payment not found or already deleted.');
            }

            if (!$this->branchAccess->canAccessBranch((int) $payment->branch_id)) {
                throw new DomainException('You do not have access to this branch.');
            }

            if ($payment->created_at && $payment->created_at->lt(now()->subDays(self::DELETE_WINDOW_DAYS))) {
                throw new DomainException(
                    'This payment can only be deleted within ' . self::DELETE_WINDOW_DAYS . ' days.'
                );
            }

            // 1. Record who deleted it and why (no "updated" activity log)
            $payment->forceFill([
                'deleted_by' => auth()->id(),
                'delete_reason' => trim($reason),
            ])->saveQuietly();

            // 2. Remove it from teacher / institute split reports
            if ($payment->splitSnapshot) {
                $payment->splitSnapshot->delete();
            }

            // 3. Soft delete the payment.
            //    active_month_lock becomes NULL, so the same class + month
            //    can be paid again.
            $payment->delete();

            return $payment;
        });
    }
}