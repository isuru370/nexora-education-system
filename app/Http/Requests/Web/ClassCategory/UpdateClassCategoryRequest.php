<?php

namespace App\Http\Requests\Web\ClassCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $classCategory = $this->route('classCategory');

        return [
            'category_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('class_categories', 'category_name')
                    ->ignore($classCategory->id),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('class_categories', 'code')
                    ->ignore($classCategory->id),
            ],
            'is_schedulable' => [
                'nullable',
                'boolean',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Category name is required.',
            'category_name.unique' => 'This category name already exists.',
            'code.required' => 'Category code is required.',
            'code.unique' => 'This category code already exists.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('category_name')) {
            $this->merge([
                'category_name' => trim($this->category_name),
            ]);
        }

        if ($this->filled('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->code)),
            ]);
        }
    }
}
