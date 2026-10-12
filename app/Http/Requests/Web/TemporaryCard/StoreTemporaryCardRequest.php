<?php

namespace App\Http\Requests\Web\TemporaryCard;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemporaryCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'start' => ['required', 'string'],
            'end'   => ['required', 'string'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'start.required' => 'Start code is required.',
            'start.string'   => 'Start code must be a string.',
            'end.required'   => 'End code is required.',
            'end.string'     => 'End code must be a string.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'start' => $this->start ? strtoupper(trim($this->start)) : null,
            'end'   => $this->end ? strtoupper(trim($this->end)) : null,
        ]);
    }
}