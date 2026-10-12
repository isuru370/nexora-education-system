<?php

namespace App\Http\Requests\Web\ClassSchedule;

use Illuminate\Foundation\Http\FormRequest;

class CancelClassScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
