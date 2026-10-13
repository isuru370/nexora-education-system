<?php

namespace App\Http\Requests\Web\Report;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ?teacher_id=&from=&to=&branch_id=
 * teacher_id is optional on the page (shows "select a teacher") and required for exports.
 */
class TeacherCollectionReportRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) handles access.
     * Branch / teacher access is checked in the services.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => [$this->routeIs('*.export') ? 'required' : 'nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_id.required' => 'Please select a teacher.',
        ];
    }
}
