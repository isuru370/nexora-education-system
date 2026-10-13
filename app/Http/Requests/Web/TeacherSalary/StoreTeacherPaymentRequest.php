<?php

namespace App\Http\Requests\Web\TeacherSalary;

use App\Services\Web\TeacherSalary\TeacherSalaryCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add an advance / deduction / other payment for a teacher.
 */
class StoreTeacherPaymentRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) handles access.
     * Branch access and the amount limit are checked in TeacherSalaryPaymentService.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => $this->filled('reason') ? trim((string) $this->input('reason')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'payment_type' => ['required', Rule::in(TeacherSalaryCalculator::PAYMENT_TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_id.required' => 'Branch is required.',
            'payment_type.in' => 'Payment type must be advance, deduction or other.',
            'amount.min' => 'Amount must be greater than zero.',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
        ];
    }
}