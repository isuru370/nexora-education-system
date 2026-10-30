<?php

namespace App\Http\Controllers\Web\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Payment\BulkPayPaymentRequest;
use App\Http\Requests\Web\Payment\PaymentMonthInfoRequest;
use App\Http\Requests\Web\Payment\ReadPaymentRequest;
use App\Http\Requests\Web\Payment\StorePaymentRequest;
use App\Services\Web\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}


    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    /**
     * Show New Payment page.
     *
     * GET /admin/new-payment
     */
    public function index()
    {
        return view('new-payment.index');
    }


    /*
    |--------------------------------------------------------------------------
    | Read
    |--------------------------------------------------------------------------
    */

    /**
     * Read student payment information.
     *
     * POST /admin/new-payment/read
     */
    public function read(ReadPaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $data = $this->paymentService->read(
                $validated['code']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Student payment information loaded successfully.',
                'data' => $data,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => \App\Support\ErrorDetail::safe($e),
            ], 404);
        } catch (Throwable $e) {
            Log::error('Admin new payment read failed.', [
                'code' => $validated['code'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to load student payment information.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Pay
    |--------------------------------------------------------------------------
    */

    /**
     * Store one payment.
     *
     * POST /admin/new-payment/pay
     */
    public function pay(StorePaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $data = $this->paymentService->pay($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment completed successfully.',
                'data' => $data,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => \App\Support\ErrorDetail::safe($e),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => \App\Support\ErrorDetail::safe($e),
            ], 409);
        } catch (Throwable $e) {
            Log::error('Admin new payment failed.', [
                'code' => $validated['code'] ?? null,
                'enrollment_id' => $validated['enrollment_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Payment could not be completed.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Bulk Pay
    |--------------------------------------------------------------------------
    */

    /**
     * Store multiple payments.
     *
     * POST /admin/new-payment/bulk-pay
     */
    public function bulkPay(BulkPayPaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $data = $this->paymentService->bulkPay(
                $validated['payments']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk payment completed successfully.',
                'data' => $data,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => \App\Support\ErrorDetail::safe($e),
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => \App\Support\ErrorDetail::safe($e),
            ], 409);
        } catch (Throwable $e) {
            Log::error('Admin new bulk payment failed.', [
                'payment_count' => count($validated['payments'] ?? []),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Bulk payment could not be completed.',
            ], 500);
        }
    }

    /**
     * One class + one month: attendance, fee after the Payment Rules,
     * paid or not, tute.
     *
     * POST /new-payment/month-info
     */
    public function monthInfo(PaymentMonthInfoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            return response()->json([
                'status' => 'success',
                'data' => $this->paymentService->monthInfo(
                    $validated['code'],
                    (int) $validated['enrollment_id'],
                    $validated['payment_month']
                ),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => \App\Support\ErrorDetail::safe($e)], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => \App\Support\ErrorDetail::safe($e)], 404);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json(['status' => 'error', 'message' => 'Could not load the month details.'], 500);
        }
    }
}
