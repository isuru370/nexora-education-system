<?php

namespace App\Http\Requests\Web\ClassHall;

use App\Services\Branch\BranchAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassHallRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'hall_name' => trim((string) $this->input('hall_name')),
        ]);
    }

    public function rules(BranchAccessService $branchAccessService)
    {
        $branchIds = $branchAccessService
            ->getBranchIds()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
                Rule::in($branchIds),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('class_halls', 'code')
                    ->where(fn ($query) => $query->where(
                        'branch_id',
                        $this->input('branch_id')
                    )),
            ],
            'hall_name' => ['required', 'string', 'max:150'],
            'hall_type' => ['nullable', 'string', 'max:50'],
            'hall_price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'branch_id.required' => 'Please select a branch.',
            'branch_id.in' => 'You do not have access to the selected branch.',
            'code.unique' => 'This hall code already exists in the selected branch.',
            'hall_name.required' => 'Hall name is required.',
        ];
    }
}