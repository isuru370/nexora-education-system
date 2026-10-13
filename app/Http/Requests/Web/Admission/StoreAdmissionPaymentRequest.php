<?php

namespace App\Http\Requests\Web\Admission;

use App\Models\AdmissionPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdmissionPaymentRequest extends FormRequest
{
    /**
     * Values allowed by the admission_payments.payment_method enum.
     */
    public const PAYMENT_METHODS = [
        'cash',
        'card',
        'bank_transfer',
        'online',
        'other',
    ];

    /**
     * Values allowed by the admission_payments.status enum.
     */
    public const STATUSES = [
        AdmissionPayment::STATUS_PENDING,
        AdmissionPayment::STATUS_PAID,
        AdmissionPayment::STATUS_CANCELLED,
        AdmissionPayment::STATUS_REFUNDED,
    ];

    /**
     * Route middleware (auth + role) already handles access.
     * Branch access is checked in AdmissionPaymentService.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Fill defaults before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_method' => $this->filled('payment_method')
                ? $this->input('payment_method')
                : 'cash',

            'status' => $this->normalizeStatus($this->input('status')),

            'note' => $this->filled('note')
                ? trim((string) $this->input('note'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],

            'admission_id' => [
                'required',
                'integer',
                Rule::exists('admissions', 'id')
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99', // DECIMAL(10,2)
            ],

            'payment_method' => [
                'required',
                Rule::in(self::PAYMENT_METHODS),
            ],

            'status' => [
                'required',
                Rule::in(self::STATUSES),
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Please select a student.',
            'student_id.exists' => 'Selected student was not found.',

            'admission_id.required' => 'Please select an admission type.',
            'admission_id.exists' => 'Selected admission is not active.',

            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount cannot be negative.',
            'amount.max' => 'Amount is too large.',

            'payment_method.in' => 'Invalid payment method.',
            'status.in' => 'Invalid payment status.',
            'note.max' => 'Note must not exceed 1000 characters.',
        ];
    }

    /**
     * Default to "paid".
     *
     * Older forms sent status as a checkbox (1 / 0 / on), so those
     * values are mapped to paid / cancelled to stay compatible.
     */
    protected function normalizeStatus($status): string
    {
        if ($status === null || $status === '') {
            return AdmissionPayment::STATUS_PAID;
        }

        if (in_array($status, [true, 1, '1', 'on', 'true'], true)) {
            return AdmissionPayment::STATUS_PAID;
        }

        if (in_array($status, [false, 0, '0', 'off', 'false'], true)) {
            return AdmissionPayment::STATUS_CANCELLED;
        }

        return (string) $status;
    }
}