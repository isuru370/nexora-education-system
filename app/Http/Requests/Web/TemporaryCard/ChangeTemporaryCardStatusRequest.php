<?php

namespace App\Http\Requests\Web\TemporaryCard;

use Illuminate\Foundation\Http\FormRequest;

class ChangeTemporaryCardStatusRequest extends FormRequest
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
            'start'  => ['required', 'string', 'regex:/^TMP\d+$/i'],
            'end'    => ['nullable', 'string', 'regex:/^TMP\d+$/i'],
            'status' => ['required', 'in:pending,downloaded,issued,active,expired'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'start.required' => 'Start TMP number is required.',
            'start.regex'    => 'Start TMP number must be in the format TMP001.',
            'end.regex'      => 'End TMP number must be in the format TMP120.',
            'status.required' => 'Status is required.',
            'status.in'      => 'Invalid status selected. Allowed: pending, downloaded, issued, active, expired.',
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