<?php

namespace App\Http\Requests\Web\Report;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ?month=2026-10&branch_id=   (both optional, default: this month, all branches)
 */
class MonthlyCollectionReportRequest extends FormRequest
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
            'month' => ['nullable', 'date_format:Y-m'],
            'branch_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.date_format' => 'Month is not valid.',
        ];
    }
}
