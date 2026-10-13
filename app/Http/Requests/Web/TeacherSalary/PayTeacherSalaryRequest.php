<?php

namespace App\Http\Requests\Web\TeacherSalary;

use Illuminate\Foundation\Http\FormRequest;

class PayTeacherSalaryRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) handles access.
     * Branch access and pay rules are checked in TeacherSalaryPaymentService.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('note')) {
            $this->merge([
                'note' => trim((string) $this->input('note')) ?: null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
            'month' => ['required', 'integer', 'min:1', 'max:12'],

            // Net amount shown on the page (used to detect a changed amount)
            'amount' => ['nullable', 'numeric'],

            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Branch is required.',
            'branch_id.exists' => 'Selected branch does not exist.',
        ];
    }
}