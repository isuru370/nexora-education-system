<?php

namespace App\Http\Requests\Web\Course;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'full_fee' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'one_time_fee' => ['required', 'numeric', 'min:0', 'lte:full_fee'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'class_days' => ['required', 'array', 'min:1'],
            'class_days.*' => [Rule::in(Course::DAYS)],
            'class_hall_id' => ['nullable', 'integer', 'exists:class_halls,id'],
            'teachers' => ['required', 'array', 'min:1'],
            'teachers.*.teacher_id' => ['required', 'integer', 'distinct', 'exists:teachers,id'],
            'teachers.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'one_time_fee.lte' => 'The one-time fee cannot be more than the full fee.',
            'class_days.required' => 'Choose at least one class day.',
            'teachers.required' => 'Add at least one teacher.',
            'teachers.*.teacher_id.distinct' => 'A teacher is added more than once.',
            'end_time.after' => 'End time must be after the start time.',
        ];
    }

    public function courseData(): array
    {
        return $this->safe()->except('teachers');
    }

    public function teachers(): array
    {
        return array_values($this->validated()['teachers']);
    }

    /** Only rows where a teacher was picked (the form may send empty rows). */
    protected function prepareForValidation(): void
    {
        $teachers = collect($this->input('teachers', []))
            ->filter(fn ($row) => !empty($row['teacher_id']))
            ->values()
            ->all();

        $this->merge(['teachers' => $teachers]);
    }
}
