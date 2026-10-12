<?php

namespace App\Http\Requests\Web\ClassCategoryFee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassCategoryFeeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'student_class_id' => [
                'required',
                'integer',
                'exists:student_classes,id',
            ],
            'class_category_id' => [
                'required',
                'integer',
                'exists:class_categories,id',
            ],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'student_class_id.required' => 'Please select a class.',
            'student_class_id.exists' => 'Selected class does not exist.',
            'class_category_id.required' => 'Please select a category.',
            'class_category_id.exists' => 'Selected category does not exist.',
        ];
    }
}
