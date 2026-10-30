<?php

namespace App\Http\Requests\Web\Payment;

use Illuminate\Foundation\Http\FormRequest;

class BulkPayPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payments' => [
                'required',
                'array',
                'min:1',
            ],

            'payments.*.code' => [
                'required',
                'string',
                'max:100',
            ],

            'payments.*.enrollment_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'payments.*.amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'payments.*.payment_month' => [
                'required',
                'date',
            ],

            'payments.*.give_tute' => [
                'nullable',
                'boolean',
            ],

            'payments.*.mark_method' => [
                'required',
                'in:qr_mobile,qr_web,manual_mobile,manual_web',
            ],

            'payments.*.payment_method' => [
                'nullable',
                'in:cash,card,bank_transfer,online,cheque,other',
            ],

            'payments.*.discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'payments.*.reference_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'payments.*.note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payments.required' => 'No payments provided.',
            'payments.array' => 'Payments must be an array.',
            'payments.min' => 'At least one payment is required.',
            'payments.*.code.required' => 'Student code is required for each payment.',
            'payments.*.enrollment_id.required' => 'Enrollment ID is required for each payment.',
            'payments.*.amount.required' => 'Payment amount is required for each payment.',
            'payments.*.amount.gt' => 'Payment amount must be greater than zero.',
            'payments.*.payment_month.required' => 'Payment month is required for each payment.',
            'payments.*.mark_method.required' => 'Payment mark method is required.',
        ];
    }
}