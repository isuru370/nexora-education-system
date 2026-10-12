<?php

namespace App\Http\Requests\Web\StudentCard;

use Illuminate\Foundation\Http\FormRequest;

class AssignStudentCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'card_id' => ['required', 'integer', 'exists:student_cards,id'],
        ];
    }
}
