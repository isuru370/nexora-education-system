<?php

namespace App\Http\Requests\Web\Admission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdmissionRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) already handles access.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize input before validation.
     *
     * - Checkbox sends "on" (or nothing), so convert it to true/false.
     * - Trim the name so "New " and "New" are treated as the same.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name'))
                ? trim($this->input('name'))
                : $this->input('name'),

            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150', // admissions.name is VARCHAR(150)
                Rule::unique('admissions', 'name')->whereNull('deleted_at'),
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99', // admissions.amount is DECIMAL(10,2)
            ],

            'is_active' => [
                'boolean',
            ],

            'note' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Admission name is required.',
            'name.max' => 'Admission name must not exceed 150 characters.',
            'name.unique' => 'An admission with this name already exists.',

            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount cannot be negative.',
            'amount.max' => 'Amount is too large.',

            'note.max' => 'Note must not exceed 2000 characters.',
        ];
    }
}