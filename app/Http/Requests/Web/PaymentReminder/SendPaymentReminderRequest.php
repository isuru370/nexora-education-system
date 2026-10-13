<?php

namespace App\Http\Requests\Web\PaymentReminder;

use App\Services\Web\PaymentReminder\PaymentReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST class_id, class_category_fee_id, month (Y-m), student_ids[], channel (sms|notification), message
 */
class SendPaymentReminderRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) handles access.
     * Branch access and "still unpaid" are checked in PaymentReminderService::send().
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer'],
            'class_category_fee_id' => ['required', 'integer'],
            'month' => ['required', 'date_format:Y-m'],
            'student_ids' => ['required', 'array', 'min:1', 'max:500'],
            'student_ids.*' => ['integer', 'distinct'],
            'channel' => ['required', Rule::in([PaymentReminderService::CHANNEL_SMS, PaymentReminderService::CHANNEL_NOTIFICATION])],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_ids.required' => 'Please select at least one student.',
            'student_ids.min' => 'Please select at least one student.',
            'message.max' => 'The message may not be longer than 500 characters.',
        ];
    }

    public function month(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->validated()['month'] . '-01')->startOfDay();
    }
}
