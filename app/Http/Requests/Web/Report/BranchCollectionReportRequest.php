<?php

namespace App\Http\Requests\Web\Report;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ?from=2026-10-01&to=2026-10-31   (default: this month up to today)
 */
class BranchCollectionReportRequest extends FormRequest
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