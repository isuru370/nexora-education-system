<?php

namespace App\Http\Requests\Web\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_type' => ['required', Rule::in(['single', 'recurring'])],
            'student_class_id' => ['required', 'integer', 'exists:student_classes,id'],
            'class_category_fee_id' => ['required', 'integer', 'exists:class_category_fees,id'],
            'class_hall_id' => ['nullable', 'integer', 'exists:class_halls,id'],

            'class_date' => ['required_if:schedule_type,single', 'nullable', 'date'],

            'start_date' => ['required_if:schedule_type,recurring', 'nullable', 'date'],
            'end_date' => [
                'required_if:schedule_type,recurring',
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'class_day' => [
                'required_if:schedule_type,recurring',
                'nullable',
                Rule::in([
                    'monday', 'tuesday', 'wednesday', 'thursday',
                    'friday', 'saturday', 'sunday',
                ]),
            ],

            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ];
    }
}
