<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\CourseDay;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseService
{
    public function __construct(
        private CourseScheduleService $schedule,
        private CourseCodeService $codes
    ) {
    }

    /**
     * @param  array  $data      validated course fields
     * @param  array  $teachers  [['teacher_id' => 1, 'percentage' => 35], ...]
     */
    public function create(array $data, array $teachers, ?int $userId = null): Course
    {
        $this->checkTeachers($teachers);
        $days = $this->checkDays($data['days'] ?? []);

        return DB::transaction(function () use ($data, $teachers, $userId, $days) {
            $course = Course::create($this->fields($data) + [
                'code' => $this->codes->next((int) $data['branch_id'], $data['start_date']),
                'created_by' => $userId,
                'is_active' => true,
            ]);

            $this->saveTeachers($course, $teachers);
            $this->saveDays($course, $days);
            $this->schedule->syncSessions($course);

            return $course;
        });
    }

    public function update(Course $course, array $data, array $teachers): Course
    {
        $this->checkTeachers($teachers);
        $days = $this->checkDays($data['days'] ?? []);

        return DB::transaction(function () use ($course, $data, $teachers, $days) {
            $course->update($this->fields($data));

            $this->saveTeachers($course, $teachers);
            $this->saveDays($course, $days);
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
            'description' => $data['description'] ?? null,
            'full_fee' => $data['full_fee'],
            'one_time_fee' => $data['one_time_fee'],
            'installment_count' => $this->schedule->monthCount($data['start_date'], $data['end_date']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
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

    /**
     * At least one week day, each day once, end after start.
     *
     * @return array<int, array{day: string, start_time: string, end_time: string, class_hall_id: ?int}>
     */
    protected function checkDays(array $days): array
    {
        $clean = [];

        foreach ($days as $row) {
            $day = strtolower(trim((string) ($row['day'] ?? '')));

            if (!in_array($day, Course::DAYS, true)) {
                throw ValidationException::withMessages(['days' => 'Choose a day for every class day row.']);
            }

            if (isset($clean[$day])) {
                throw ValidationException::withMessages(['days' => ucfirst($day) . ' is added more than once. One time per day.']);
            }

            $start = substr((string) ($row['start_time'] ?? ''), 0, 5);
            $end = substr((string) ($row['end_time'] ?? ''), 0, 5);

            if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end) || $end <= $start) {
                throw ValidationException::withMessages(['days' => ucfirst($day) . ': the end time must be after the start time.']);
            }

            $clean[$day] = [
                'day' => $day,
                'start_time' => $start,
                'end_time' => $end,
                'class_hall_id' => !empty($row['class_hall_id']) ? (int) $row['class_hall_id'] : null,
            ];
        }

        if (!$clean) {
            throw ValidationException::withMessages(['days' => 'Add at least one class day.']);
        }

        return array_values($clean);
    }

    protected function saveDays(Course $course, array $days): void
    {
        foreach ($days as $day) {
            $course->days()->updateOrCreate(['day' => $day['day']], $day);
        }

        CourseDay::query()->where('course_id', $course->id)->whereNotIn('day', array_column($days, 'day'))->delete();
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
