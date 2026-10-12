<?php

namespace App\Http\Requests\Web\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBulkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_day' => [
                'required',
                Rule::in([
                    'monday', 'tuesday', 'wednesday', 'thursday',
                    'friday', 'saturday', 'sunday',
                ]),
            ],
            'class_hall_id' => ['nullable', 'integer', 'exists:class_halls,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ];
    }
}
