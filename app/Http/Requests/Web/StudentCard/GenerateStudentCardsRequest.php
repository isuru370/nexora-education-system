<?php

namespace App\Http\Requests\Web\StudentCard;

use Illuminate\Foundation\Http\FormRequest;

class GenerateStudentCardsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
        ];
    }
}
