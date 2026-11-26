<?php

namespace App\Http\Requests\Api\Offline;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Items recorded offline by the staff app. A student or enrollment made on
 * the phone has no server id yet: later items point to it with student_ref /
 * enrollment_ref (the local_uuid of that item) instead of student_id /
 * enrollment_id.
 */
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
            'items.*.type' => ['required', 'in:attendance,payment,student,enrollment'],
            'items.*.happened_at' => ['required', 'date'],
            'items.*.student_id' => ['nullable', 'integer'],
            'items.*.student_ref' => ['nullable', 'uuid'],
            'items.*.enrollment_id' => ['nullable', 'integer'],
            'items.*.enrollment_ref' => ['nullable', 'uuid'],
            'items.*.mark_method' => ['nullable', 'in:qr_mobile,manual_mobile'],
            // attendance
            'items.*.schedule_id' => ['required_if:items.*.type,attendance', 'nullable', 'integer'],
            // payment
            'items.*.amount' => ['required_if:items.*.type,payment', 'nullable', 'numeric', 'gt:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.payment_month' => ['required_if:items.*.type,payment', 'nullable', 'date_format:Y-m'],
            'items.*.fee_option_id' => ['nullable', 'integer'],
            'items.*.receipt_number' => ['nullable', 'string', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
            // enrollment
            'items.*.student_class_id' => ['required_if:items.*.type,enrollment', 'nullable', 'integer'],
            'items.*.class_category_fee_id' => ['required_if:items.*.type,enrollment', 'nullable', 'integer'],
            'items.*.class_category_fee_option_id' => ['required_if:items.*.type,enrollment', 'nullable', 'integer'],
            'items.*.is_free_card' => ['nullable', 'boolean'],
            // student: the registration form (checked with the same rules as online)
            'items.*.student' => ['required_if:items.*.type,student', 'nullable', 'array'],
            'items.*.admission_receipt_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('items', []) as $i => $item) {
                $type = $item['type'] ?? null;

                if (in_array($type, ['attendance', 'payment', 'enrollment'], true)
                    && empty($item['student_id']) && empty($item['student_ref'])) {
                    $validator->errors()->add("items.$i.student_id", 'The student is required.');
                }

                if (in_array($type, ['attendance', 'payment'], true)
                    && empty($item['enrollment_id']) && empty($item['enrollment_ref'])) {
                    $validator->errors()->add("items.$i.enrollment_id", 'The class enrollment is required.');
                }
            }
        });
    }
}
