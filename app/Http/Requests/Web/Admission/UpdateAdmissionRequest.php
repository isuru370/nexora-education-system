<?php

namespace App\Http\Requests\Web\Admission;

use Illuminate\Validation\Rule;

class UpdateAdmissionRequest extends StoreAdmissionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $admission = $this->route('admission');

        $rules['name'] = [
            'required',
            'string',
            'max:150',
            Rule::unique('admissions', 'name')
                ->ignore($admission)
                ->whereNull('deleted_at'),
        ];

        return $rules;
    }
}