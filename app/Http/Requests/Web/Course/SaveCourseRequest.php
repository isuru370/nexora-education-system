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
            'description' => ['nullable', 'string', 'max:2000'],
            'full_fee' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'one_time_fee' => ['required', 'numeric', 'min:0', 'lte:full_fee'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*.day' => ['required', 'distinct', Rule::in(Course::DAYS)],
            'days.*.start_time' => ['required', 'date_format:H:i'],
            'days.*.end_time' => ['required', 'date_format:H:i'],
            'days.*.class_hall_id' => ['nullable', 'integer', 'exists:class_halls,id'],
            'teachers' => ['required', 'array', 'min:1'],
            'teachers.*.teacher_id' => ['required', 'integer', 'distinct', 'exists:teachers,id'],
            'teachers.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'one_time_fee.lte' => 'The one-time fee cannot be more than the full fee.',
            'days.required' => 'Add at least one class day.',
            'days.*.day.distinct' => 'A day is added more than once. One time per day.',
            'days.*.day.required' => 'Choose a day for every class day row.',
            'teachers.required' => 'Add at least one teacher.',
            'teachers.*.teacher_id.distinct' => 'A teacher is added more than once.',
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

    /** Drop empty rows the form may send (no teacher / no day picked). */
    protected function prepareForValidation(): void
    {
        $teachers = collect($this->input('teachers', []))
            ->filter(fn ($row) => !empty($row['teacher_id']))
            ->values()
            ->all();

        $days = collect($this->input('days', []))
            ->filter(fn ($row) => !empty($row['day']))
            ->values()
            ->all();

        $this->merge(['teachers' => $teachers, 'days' => $days]);
    }
}
