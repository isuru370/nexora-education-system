<?php

namespace App\Http\Requests\Web\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ScanAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Route middleware (auth + role) already handles access.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Clean the input before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => trim((string) $this->input('code')),
            ]);
        }
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
            ],

            'mark_method' => [
                'required',
                'in:qr_web,manual_web',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Student code is required.',
            'code.string' => 'Student code must be a valid text.',
            'code.max' => 'Student code must not exceed 255 characters.',

            'mark_method.required' => 'Attendance mark method is required.',
            'mark_method.in' => 'Invalid attendance mark method.',
        ];
    }
}