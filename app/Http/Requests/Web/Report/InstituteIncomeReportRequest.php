<?php

namespace App\Http\Requests\Web\Report;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ?from=2026-10-01&to=2026-10-31&branch_id=   (default: this month, all branches)
 */
class InstituteIncomeReportRequest extends FormRequest
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
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format' => 'From date is not valid.',
            'to.date_format' => 'To date is not valid.',
        ];
    }
}
