<?php

namespace App\Http\Requests\Web\ClassCategoryFeeOption;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassCategoryFeeOptionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'label' => [
                'required',
                'string',
                'max:150',
            ],
            'fee' => [
                'required',
                'numeric',
                'min:0',
            ],
            'is_default' => [
                'sometimes',
                'boolean',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'note' => [
                'nullable',
                'string',
            ],
        ];
    }
}
