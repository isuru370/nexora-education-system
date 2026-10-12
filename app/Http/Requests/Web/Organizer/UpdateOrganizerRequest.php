<?php

namespace App\Http\Requests\Web\Organizer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizerId = $this->route('organizer')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['required', 'integer', 'distinct', 'exists:branches,id'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'nic' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('organizers', 'nic')->ignore($organizerId),
            ],
            'note' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}