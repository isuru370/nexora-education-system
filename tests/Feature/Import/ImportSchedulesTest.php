<?php

namespace Tests\Feature\Import;

use App\Models\Branch;
use App\Models\ClassCategory;
use App\Models\ClassHall;
use App\Models\ClassSchedule;
use App\Models\ClassSchedulePattern;
use App\Models\Grade;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Moving an institute from the old system: class sessions (schedules) and attendance. */
class ImportSchedulesTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-27 10:00:00');

        $branch = Branch::create(['name' => 'Main Branch', 'code' => 'MAIN', 'is_active' => true]);
        $grade = Grade::create(['grade_name' => '10', 'is_active' => true]);
        ClassCategory::create(['category_name' => 'Theory', 'code' => 'theory', 'is_active' => true]);
        ClassHall::create(['branch_id' => $branch->id, 'code' => 'H1', 'hall_name' => 'HALL NO 1', 'is_active' => true]);
        Teacher::create(['custom_id' => 'CT0001', 'full_name' => 'T', 'initials' => 'T', 'email' => 't@t.lk', 'mobile' => '0771234567',
            'nic' => '1', 'bday' => '1980-01-01', 'gender' => 'male', 'address1' => 'x']);
        foreach (['CS0001', 'CS0002'] as $id) {
            Student::create(['custom_id' => $id, 'branch_id' => $branch->id, 'initial_name' => $id, 'guardian_mobile' => '0771234567',
                'grade_id' => $grade->id, 'img_url' => 'uploads/male.png']);
        }

        $this->dir = sys_get_temp_dir() . '/nexora-sch-' . uniqid();
        mkdir($this->dir);

        $this->csv('classes.csv', ['old_class_id', 'old_fee_id', 'class_name', 'teacher_custom_id', 'teacher_percentage', 'subject', 'grade', 'is_active', 'is_ongoing', 'category', 'fee', 'created_at'], [
            [11, 25, 'ENGLISH G10', 'CT0001', 75, 'English', '10', 1, 1, 'Theory', 1000, '2024-09-15 00:00:00'],
        ]);
        $this->csv('enrollments.csv', ['old_id', 'custom_id', 'old_fee_id', 'is_active', 'is_free_card', 'enrolled_at', 'updated_at'], [
            [1, 'CS0001', 25, 1, 0, '2026-09-01 09:00:00', ''],
            [2, 'CS0002', 25, 1, 0, '2026-09-01 09:00:00', ''],
        ]);
        // Saturdays 3:00-5:00 PM in hall H1 (old times in two formats); Oct 3 twice in the old system
        $this->csv('sessions.csv', ['old_id', 'old_fee_id', 'class_date', 'start_time', 'end_time', 'hall_code', 'held'], [
            [100, 25, '2026-10-03', '3:00 PM', '5:00 PM', 'H1', 1],
            [101, 25, '2026-10-03', '15:00', '17:00', 'H1', 0],   // double of #100
            [102, 25, '2026-10-10', '3:00 PM', '5:00 PM', 'H1', 0], // not held
            [103, 25, '2026-10-17', '15:00', '17:00', 'H1', 1],
            [104, 25, '2026-11-14', '15:00', '17:00', 'H1', 0],     // future: the pattern runs to here
        ]);
        $this->csv('attendance.csv', ['old_id', 'custom_id', 'old_enrollment_id', 'old_fee_id', 'old_session_id', 'attended_at'], [
            [1, 'CS0001', 1, 25, 100, '2026-10-03 15:05:00'],
            [2, 'CS0001', 1, 25, 101, '2026-10-03 15:06:00'],   // same session (merged) - twice
            [3, 'CS0002', 2, 25, 101, '2026-10-03 15:07:00'],
            [4, 'CS0002', 2, 25, 103, '2026-10-17 15:01:00'],
            [5, 'CS0002', 2, 25, 999, '2026-10-17 15:01:00'],   // no such session
        ]);

        $this->artisan('nexora:import-classes', ['file' => $this->dir . '/classes.csv', '--enrollments' => $this->dir . '/enrollments.csv'])->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function csv(string $name, array $header, array $rows): void
    {
        $h = fopen($this->dir . '/' . $name, 'w');
        fputcsv($h, $header);
        foreach ($rows as $row) {
            fputcsv($h, $row);
        }
        fclose($h);
    }

    private function import(bool $dry = false)
    {
        return $this->artisan('nexora:import-schedules', array_filter([
            'file' => $this->dir . '/sessions.csv',
            '--attendance' => $this->dir . '/attendance.csv',
            '--dry-run' => $dry,
        ]));
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->import(true)->expectsOutput('DRY RUN - nothing was changed')->assertExitCode(0);

        $this->assertSame(0, ClassSchedulePattern::count());
        $this->assertSame(0, ClassSchedule::count());
        $this->assertSame(0, StudentAttendance::count());
    }

    public function test_sessions_and_attendance_are_imported(): void
    {
        $this->import()->assertExitCode(0);

        // one weekly pattern: Saturday 15:00-17:00, hall H1, Oct 3 - Nov 14
        $pattern = ClassSchedulePattern::sole();
        $this->assertSame('saturday', $pattern->class_day);
        $this->assertSame('15:00:00', $pattern->start_time);
        $this->assertSame('17:00:00', $pattern->end_time);
        $this->assertSame('2026-10-03', $pattern->start_date->toDateString());
        $this->assertSame('2026-11-14', $pattern->end_date->toDateString());
        $this->assertSame('H1', $pattern->hall->code);
        $this->assertTrue($pattern->is_active);

        $status = ClassSchedule::orderBy('class_date')->get()->mapWithKeys(fn ($s) => [$s->class_date->toDateString() => $s->status])->all();
        $this->assertSame([
            '2026-10-03' => 'completed',  // #100 + #101 = one
            '2026-10-10' => 'cancelled',  // not held
            '2026-10-17' => 'completed',
            '2026-10-31' => 'scheduled',  // from the pattern
            '2026-11-07' => 'scheduled',
            '2026-11-14' => 'scheduled',
        ], $status);
        $this->assertSame('Not held (old system)', ClassSchedule::where('status', 'cancelled')->value('cancel_reason'));

        // attendance: 3 (CS0001 twice on Oct 3 = once; session #999 unknown)
        $this->assertSame(3, StudentAttendance::count());
        $oct3 = ClassSchedule::whereDate('class_date', '2026-10-03')->value('id');
        $this->assertSame(2, StudentAttendance::where('class_schedule_id', $oct3)->count());
        $first = StudentAttendance::where('note', 'Imported from old system #1')->first();
        $this->assertSame('2026-10-03 15:05:00', $first->attended_at->format('Y-m-d H:i:s'));
        $this->assertSame('CS0001', $first->student->custom_id);
        $this->assertSame($first->student_id, \App\Models\StudentClassEnrollment::find($first->student_class_enrollment_id)->student_id);

        $problems = file_get_contents(storage_path('logs/import-schedules-problems.txt'));
        $this->assertStringContainsString('attendance #2 CS0001: NOT imported - already marked for this class session', $problems);
        $this->assertStringContainsString('attendance #5 CS0002: NOT imported - session #999 not imported', $problems);

        // again: nothing new
        $this->import()->assertExitCode(0);
        $this->assertSame(1, ClassSchedulePattern::count());
        $this->assertSame(6, ClassSchedule::count());
        $this->assertSame(3, StudentAttendance::count());
    }
}
