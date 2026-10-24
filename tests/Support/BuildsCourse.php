<?php

namespace Tests\Support;

use App\Models\Course;
use App\Services\Course\CourseService;
use Illuminate\Support\Facades\DB;

/**
 * A course in the Colombo branch of BuildsInstitute:
 * "Diploma in IT", 1 Nov 2026 - 30 Apr 2027 (6 months), Saturdays and Sundays
 * 08:00-12:00 ("days" can be overridden), fee 12000 (or 10000 paid at once), teacher A 35%, B 35%,
 * institute 30%. Use after buildInstitute(); sets $this->inst['course'], ['teacherB'].
 */
trait BuildsCourse
{
    protected function buildCourse(array $overrides = []): Course
    {
        $teacherB = $this->inst['teacherB'] ?? DB::table('teachers')->insertGetId([
            'custom_id' => 'T2', 'full_name' => 'Sunil Silva', 'initials' => 'S.', 'email' => 't2@test.lk',
            'mobile' => '0770000001', 'nic' => '900000001V', 'bday' => '1990-01-01', 'gender' => 'male',
            'address1' => 'Colombo', 'is_active' => 1,
        ]);

        $course = app(CourseService::class)->create(array_merge([
            'branch_id' => $this->inst['b1'],
            'name' => 'Diploma in IT',
            'full_fee' => 12000,
            'one_time_fee' => 10000,
            'start_date' => '2026-11-01',
            'end_date' => '2027-04-30',
            'days' => [
                ['day' => 'saturday', 'start_time' => '08:00', 'end_time' => '12:00', 'class_hall_id' => null],
                ['day' => 'sunday', 'start_time' => '08:00', 'end_time' => '12:00', 'class_hall_id' => null],
            ],
        ], $overrides), [
            ['teacher_id' => $this->inst['teacher'], 'percentage' => 35],
            ['teacher_id' => $teacherB, 'percentage' => 35],
        ], $this->inst['admin']->id);

        $this->inst['course'] = $course;
        $this->inst['teacherB'] = $teacherB;

        return $course;
    }
}
