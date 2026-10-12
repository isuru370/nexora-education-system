<?php

namespace App\Http\Requests\Web\StudentClass;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],

            'class_name' => [
                'required',
                'string',
                'max:150',
            ],

            'class_type' => [
                'required',
                Rule::in(['online', 'offline', 'hybrid']),
            ],

            'medium' => [
                'required',
                'string',
                'max:50',
            ],

            'teacher_id' => [
                'required',
                'integer',
                'exists:teachers,id',
            ],

            'grade_id' => [
                'nullable',
                'required_without:new_grade',
                'integer',
                'exists:grades,id',
            ],

            'new_grade' => [
                'nullable',
                'required_without:grade_id',
                'string',
                'max:100',
            ],

            'subject_id' => [
                'nullable',
                'required_without:new_subject',
                'integer',
                'exists:subjects,id',
            ],

            'new_subject' => [
                'nullable',
                'required_without:subject_id',
                'string',
                'max:100',
            ],

            'organizer_id' => [
                'nullable',
                'integer',
                'exists:organizers,id',
            ],

            'teacher_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'organizer_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'institution_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'effective_from' => [
                'nullable',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'is_ongoing' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $teacherPercentage = (float) $this->input('teacher_percentage', 0);
            $organizerPercentage = (float) $this->input('organizer_percentage', 0);
            $institutionPercentage = (float) $this->input('institution_percentage', 0);

            if ($organizerPercentage > 0 && !$this->filled('organizer_id')) {
                $validator->errors()->add(
                    'organizer_id',
                    'Organizer is required when organizer percentage is greater than 0.'
                );
            }

            if (
                $this->has('teacher_percentage') &&
                $this->has('institution_percentage') &&
                round(
                    $teacherPercentage + $organizerPercentage + $institutionPercentage,
                    2
                ) !== 100.00
            ) {
                $validator->errors()->add(
                    'institution_percentage',
                    'Teacher, organizer and institution percentages must total 100%.'
                );
            }
        });
    }
}
