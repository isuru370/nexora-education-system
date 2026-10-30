<?php

namespace App\Http\Requests\Web\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:100',
            ],

            'enrollment_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'payment_month' => [
                'required',
                'date',
            ],

            'mark_method' => [
                'required',
                'in:qr_mobile,qr_web,manual_mobile,manual_web',
            ],

            'payment_method' => [
                'nullable',
                'in:cash,card,bank_transfer,online,cheque,other',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // Payment Rules > fee option: option picked for this month
            'fee_option_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            // Payment Rules > tute: also give this month's tute (optional)
            'give_tute' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Student code is required.',
            'enrollment_id.required' => 'Enrollment ID is required.',
            'enrollment_id.min' => 'Enrollment ID must be a positive number.',
            'amount.required' => 'Payment amount is required.',
            'amount.gt' => 'Payment amount must be greater than zero.',
            'payment_month.required' => 'Payment month is required.',
            'payment_month.date' => 'Payment month must be a valid date.',
            'mark_method.required' => 'Payment mark method is required.',
            'mark_method.in' => 'Invalid payment mark method.',
            'payment_method.in' => 'Invalid payment method.',
            'discount_amount.min' => 'Discount amount cannot be negative.',
        ];
    }
}