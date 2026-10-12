<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'address1' => [
                'nullable',
                'string',
                'max:150',
            ],

            'address2' => [
                'nullable',
                'string',
                'max:150',
            ],

            'address3' => [
                'nullable',
                'string',
                'max:150',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name')
                ? trim($this->name)
                : $this->name,

            'address1' => $this->filled('address1')
                ? trim($this->address1)
                : $this->address1,

            'address2' => $this->filled('address2')
                ? trim($this->address2)
                : $this->address2,

            'address3' => $this->filled('address3')
                ? trim($this->address3)
                : $this->address3,

            'mobile' => $this->filled('mobile')
                ? trim($this->mobile)
                : $this->mobile,

            'email' => $this->filled('email')
                ? strtolower(trim($this->email))
                : $this->email,
        ]);
    }
}