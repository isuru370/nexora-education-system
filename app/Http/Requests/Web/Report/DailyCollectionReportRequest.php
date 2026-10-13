<?php

namespace App\Http\Requests\Web\Report;

use App\Services\Web\Report\PaymentReportQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ?from=&to=&branch_id=&collected_by=&payment_method=   (all optional)
 * Default: today, all branches the user can access.
 */
class DailyCollectionReportRequest extends FormRequest
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
            'collected_by' => ['nullable', 'integer'],
            'payment_method' => ['nullable', Rule::in(PaymentReportQuery::PAYMENT_METHODS)],
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
