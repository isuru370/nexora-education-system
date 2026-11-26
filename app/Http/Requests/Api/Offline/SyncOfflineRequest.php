<?php

namespace App\Http\Requests\Api\Offline;

use Illuminate\Foundation\Http\FormRequest;

class SyncOfflineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:64'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.local_uuid' => ['required', 'uuid', 'distinct'],
            'items.*.type' => ['required', 'in:attendance,payment'],
            'items.*.happened_at' => ['required', 'date'],
            'items.*.student_id' => ['required', 'integer'],
            'items.*.enrollment_id' => ['required', 'integer'],
            'items.*.mark_method' => ['nullable', 'in:qr_mobile,manual_mobile'],
            // attendance
            'items.*.schedule_id' => ['required_if:items.*.type,attendance', 'nullable', 'integer'],
            // payment
            'items.*.amount' => ['required_if:items.*.type,payment', 'nullable', 'numeric', 'gt:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.payment_month' => ['required_if:items.*.type,payment', 'nullable', 'date_format:Y-m'],
            'items.*.fee_option_id' => ['nullable', 'integer'],
            'items.*.receipt_number' => ['nullable', 'string', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:200'],
        ];
    }
}
