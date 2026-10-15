<?php

namespace App\Http\Requests\Web\Report;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ?month=2026-10&branch_id=&table=classes|daily
 * (all optional; defaults: this month, all branches, classes table)
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
            'month'     => ['nullable', 'date_format:Y-m'],
            'branch_id' => ['nullable', 'integer'],
            'table'     => ['nullable', 'in:classes,daily'],   // ✅ එකතු කරා
        ];
    }

    public function messages(): array
    {
        return [
            'month.date_format' => 'Month is not valid.',
            'table.in'          => 'Table must be either "classes" or "daily".',
        ];
    }
}