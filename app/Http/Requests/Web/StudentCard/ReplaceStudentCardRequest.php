<?php

namespace App\Http\Requests\Web\StudentCard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceStudentCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_qr_code' => [
                'required',
                'string',
                'exists:student_cards,qr_code',
            ],
            'reason' => [
                'required',
                Rule::in(['lost', 'damaged', 'worn_out', 'other']),
            ],
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}
