<?php

namespace App\Http\Requests\Web\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;

class CategorySchedulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_class_id' => ['required', 'integer', 'exists:student_classes,id'],
            'class_category_fee_id' => ['required', 'integer', 'exists:class_category_fees,id'],
            'search' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
