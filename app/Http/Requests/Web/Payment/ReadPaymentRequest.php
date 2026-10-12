<?php

namespace App\Http\Requests\Web\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ReadPaymentRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Student code is required.',
            'code.string' => 'Student code must be a valid text.',
            'code.max' => 'Student code must not exceed 100 characters.',
        ];
    }
}