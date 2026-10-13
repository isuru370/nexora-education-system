<?php

namespace App\Http\Requests\Web\PaymentReminder;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ?class_id=&class_category_fee_id=&month=2026-10
 *
 * On the page every field is optional (the filter is filled step by step).
 * Exports need the class and the category.
 */
class PaymentReminderFilterRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) handles access.
     * Branch access to the class is checked in PaymentReminderService.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->routeIs('payment-reminder.export') ? 'required' : 'nullable';

        return [
            'class_id' => [$required, 'integer'],
            'class_category_fee_id' => [$required, 'integer'],
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'in:all,unpaid'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_id.required' => 'Please select a class.',
            'class_category_fee_id.required' => 'Please select a category.',
            'month.date_format' => 'Month is not valid.',
        ];
    }

    /** Selected fee month (first day), default: this month */
    public function month(): Carbon
    {
        $month = $this->validated()['month'] ?? null;

        return $month
            ? Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay()
            : now()->startOfMonth();
    }
}
