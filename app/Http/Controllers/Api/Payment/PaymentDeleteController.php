<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\Web\Payment\PaymentDeleteService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * DELETE /api/payments/{paymentId}  {reason}
 * Staff app: an Admin deletes a wrong class fee payment (soft delete with
 * the reason, same as the web). The class + month can then be paid again.
 */
class PaymentDeleteController extends Controller
{
    public function destroy(Request $request, int $paymentId, PaymentDeleteService $deleteService): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Please enter why this payment is being deleted.',
            'reason.min' => 'The reason must be at least 5 characters.',
            'reason.max' => 'The reason must not exceed 500 characters.',
        ]);

        try {
            $payment = $deleteService->delete($paymentId, $validated['reason']);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Failed to delete the payment.'], 500);
        }

        Log::info('Payment deleted (app).', [
            'payment_id' => $payment->id,
            'receipt_number' => $payment->receipt_number,
            'amount' => (float) $payment->amount,
            'deleted_by' => auth()->id(),
            'reason' => $payment->delete_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment ' . $payment->receipt_number . ' was deleted. The same class and month can now be paid again.',
            'data' => [
                'payment_id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'amount' => (float) $payment->amount,
                'delete_reason' => $payment->delete_reason,
            ],
        ]);
    }
}
