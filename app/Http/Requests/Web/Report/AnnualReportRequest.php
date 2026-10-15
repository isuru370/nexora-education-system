<?php

namespace App\Http\Requests\Web\Report;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ?year=2026&branch_id=1   (default: this year, all branches)
 */
class AnnualReportRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) handles access.
     * Branch access is checked in PaymentReportQuery::filter().
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . now()->year],
            'branch_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.integer' => 'Year is not valid.',
            'year.min' => 'Year is not valid.',
            'year.max' => 'You cannot select a future year.',
            'branch_id.integer' => 'Branch is not valid.',
        ];
    }
}