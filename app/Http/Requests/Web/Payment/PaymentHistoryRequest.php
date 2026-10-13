<?php

namespace App\Http\Requests\Web\Payment;

use App\Services\Web\Payment\PaymentHistoryService;
use App\Services\Web\Report\PaymentReportQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ?from=&to=&branch_id=&collected_by=&payment_method=&search=&status=active|deleted|all
 * Default: today, active payments, all of the user's branches.
 */
class PaymentHistoryRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(PaymentHistoryService::STATUSES))],
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
