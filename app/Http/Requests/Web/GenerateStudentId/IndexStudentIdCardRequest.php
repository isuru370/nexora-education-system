<?php

namespace App\Http\Requests\Web\GenerateStudentId;

use Illuminate\Foundation\Http\FormRequest;

class IndexStudentIdCardRequest extends FormRequest
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
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $this->merge([
                'search' => trim((string) $this->input('search')),
            ]);
        }
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'search.string' => 'Search keyword must be a valid text.',
            'search.max'    => 'Search keyword cannot exceed 100 characters.',
        ];
    }
}