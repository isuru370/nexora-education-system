<?php

namespace App\Http\Requests\Web\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:150',
            'initials' => 'required|string|max:20',

            'email' => 'required|email|max:150|unique:teachers,email',
            'mobile' => 'required|string|max:20',
            'nic' => 'required|string|max:20|unique:teachers,nic',
            'bday' => 'required|date',
            'gender' => 'required|in:male,female,other',

            /*
             * Education Branches
             */
            'branch_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'branch_ids.*' => [
                'integer',
                'exists:branches,id',
            ],

            'address1' => 'required|string|max:150',
            'address2' => 'nullable|string|max:150',
            'address3' => 'nullable|string|max:150',

            'graduation_details' => 'nullable|string',
            'experience' => 'nullable|string',

            'account_number' => 'nullable|string|max:50',

            'bank_branch_id' => [
                'nullable',
                'exists:bank_branches,id',
            ],

            'is_active' => 'nullable|boolean',
        ];
    }
}