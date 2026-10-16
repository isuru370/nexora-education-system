<?php

namespace App\Http\Controllers\Api\Student;

use Illuminate\Http\JsonResponse;

/**
 * Same JSON for both mobile registration endpoints
 * (the app prints the admission receipt from "admission_payment").
 */
trait StudentRegistrationResponse
{
    protected function registeredResponse(array $result): JsonResponse
    {
        $payment = $result['admission_payment'] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Student registered successfully.',
            'student' => $result['student'],
            'admission_payment' => $payment ? [
                'id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i:s'),
                'payment_method' => $payment->payment_method,
                'admission_name' => optional($payment->admission)->name,
            ] : null,
            'portal' => [
                'username' => $result['portal_username'] ?? null,
                'password' => $result['portal_password'] ?? null,
            ],
        ], 201);
    }
}