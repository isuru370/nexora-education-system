<?php

namespace App\Http\Requests\Web\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;

class BulkEditClassScheduleRequest extends FormRequest
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
        ];
    }
}
