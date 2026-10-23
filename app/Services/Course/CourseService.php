<?php

namespace App\Services\Course;

use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseService
{
    public function __construct(private CourseScheduleService $schedule)
    {
    }

    /**
     * @param  array  $data      validated course fields
     * @param  array  $teachers  [['teacher_id' => 1, 'percentage' => 35], ...]
     */
    public function create(array $data, array $teachers, ?int $userId = null): Course
    {
        $this->checkTeachers($teachers);

        return DB::transaction(function () use ($data, $teachers, $userId) {
            $course = Course::create($this->fields($data) + [
                'created_by' => $userId,
                'is_active' => true,
            ]);

            $this->saveTeachers($course, $teachers);
            $this->schedule->syncSessions($course);

            return $course;
        });
    }

    public function update(Course $course, array $data, array $teachers): Course
    {
        $this->checkTeachers($teachers);

        return DB::transaction(function () use ($course, $data, $teachers) {
            $course->update($this->fields($data));

            $this->saveTeachers($course, $teachers);
            $this->schedule->syncSessions($course->fresh());

            return $course->fresh();
        });
    }

    public function toggleActive(Course $course): void
    {
        $course->update(['is_active' => !$course->is_active]);
    }

    protected function fields(array $data): array
    {
        return [
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'full_fee' => $data['full_fee'],
            'one_time_fee' => $data['one_time_fee'],
            'installment_count' => $this->schedule->monthCount($data['start_date'], $data['end_date']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'class_days' => array_values(array_unique(array_map('strtolower', $data['class_days']))),
            'class_hall_id' => $data['class_hall_id'] ?? null,
        ];
    }

    /** At least one teacher, no teacher twice, shares add up to 100% or less. */
    protected function checkTeachers(array $teachers): void
    {
        $ids = array_column($teachers, 'teacher_id');

        if (!$ids) {
            throw ValidationException::withMessages(['teachers' => 'Add at least one teacher.']);
        }

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['teachers' => 'A teacher is added more than once.']);
        }

        $total = round(array_sum(array_map(fn ($t) => (float) $t['percentage'], $teachers)), 2);

        if ($total > 100) {
            throw ValidationException::withMessages([
                'teachers' => "Teachers' shares add up to {$total}%. They can be 100% at most (the rest goes to the institute).",
            ]);
        }
    }

    protected function saveTeachers(Course $course, array $teachers): void
    {
        $keep = [];

        foreach ($teachers as $teacher) {
            $row = $course->courseTeachers()->updateOrCreate(
                ['teacher_id' => (int) $teacher['teacher_id']],
                ['percentage' => round((float) $teacher['percentage'], 2)]
            );
            $keep[] = $row->id;
        }

        $course->courseTeachers()->whereNotIn('id', $keep)->delete();
    }
}
