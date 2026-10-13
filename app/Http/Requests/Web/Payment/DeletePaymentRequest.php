<?php

namespace App\Http\Requests\Web\Payment;

use Illuminate\Foundation\Http\FormRequest;

class DeletePaymentRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) already handles access.
     * Branch access is checked in PaymentDeleteService.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reason')) {
            $this->merge([
                'reason' => trim((string) $this->input('reason')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please enter why this payment is being deleted.',
            'reason.min' => 'The reason must be at least 5 characters.',
            'reason.max' => 'The reason must not exceed 500 characters.',
        ];
    }
}