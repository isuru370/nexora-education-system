<?php

namespace App\Http\Requests\Web\MonthlyReport;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Teacher's students payment report (fee month / collection date).
 */
class TeacherStudentPaymentReportRequest extends FormRequest
{
    /**
     * Route middleware (auth + role) handles access.
     * Teacher / branch access is checked in MonthlyReportService.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'teacher_id.required' => 'Please select a teacher.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
        ];
    }
}