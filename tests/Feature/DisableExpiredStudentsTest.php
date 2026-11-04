<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/**
 * disable:expired-students (every night): no attendance for 2 months ->
 * student disabled and out of the classes. Students imported from an old
 * system (old enrolled_at, no attendance imported) must not be disabled
 * the first night.
 */
class DisableExpiredStudentsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_imported_enrollments_are_counted_from_the_import_day(): void
    {
        Carbon::setTestNow('2026-11-04 00:00:00');
        $this->buildInstitute();

        // S2: enrolled in 2026-01 (old system), row made here yesterday (import)
        DB::table('student_class_enrollments')->where('id', $this->inst['e2'])
            ->update(['created_at' => '2026-11-03 23:10:00']);
        // S3: same, but the row is old in this system too and has no attendance
        DB::table('student_class_enrollments')->where('id', $this->inst['e3'])
            ->update(['created_at' => '2026-05-01 10:00:00']);

        $this->artisan('disable:expired-students')->assertExitCode(0);

        $this->assertSame(1, (int) DB::table('students')->where('id', $this->inst['s2'])->value('is_active'));
        $this->assertSame(1, (int) DB::table('student_class_enrollments')->where('id', $this->inst['e2'])->value('is_active'));

        $this->assertSame(0, (int) DB::table('students')->where('id', $this->inst['s3'])->value('is_active'));
        $this->assertSame(0, (int) DB::table('student_class_enrollments')->where('id', $this->inst['e3'])->value('is_active'));

        // S1 attended on 2026-10-06: stays
        $this->assertSame(1, (int) DB::table('students')->where('id', $this->inst['s1'])->value('is_active'));
    }

    public function test_after_two_months_without_attendance_an_imported_student_is_disabled(): void
    {
        Carbon::setTestNow('2027-01-05 00:00:00');
        $this->buildInstitute();
        DB::table('student_class_enrollments')->where('id', $this->inst['e2'])
            ->update(['created_at' => '2026-11-03 23:10:00']);

        $this->artisan('disable:expired-students')->assertExitCode(0);

        $this->assertSame(0, (int) DB::table('students')->where('id', $this->inst['s2'])->value('is_active'));
    }

    private function runOldEnrollmentWithoutAttendance(): void
    {
        Carbon::setTestNow('2026-11-04 00:00:00');
        $this->buildInstitute();
        DB::table('student_class_enrollments')->where('id', $this->inst['e2'])
            ->update(['created_at' => '2026-05-01 10:00:00']);
        $this->artisan('disable:expired-students')->assertExitCode(0);
    }

    public function test_the_job_keeps_a_record_with_classes_last_attendance_and_payment(): void
    {
        $this->runOldEnrollmentWithoutAttendance();

        $record = DB::table('student_auto_disables')->where('student_id', $this->inst['s2'])->first();
        $this->assertNotNull($record);
        $classes = json_decode($record->classes, true);
        $this->assertSame($this->inst['e2'], $classes[0]['enrollment_id']);
        $this->assertNull($record->last_attendance_at);   // S2 never came
    }

    public function test_staff_see_the_list_pdf_and_dashboard_button_and_can_reactivate(): void
    {
        $this->runOldEnrollmentWithoutAttendance();
        Carbon::setTestNow('2026-11-04 08:00:00');

        $name = DB::table('students')->where('id', $this->inst['s2'])->value('initial_name');

        $this->actingAs($this->inst['cashier'])->get('/auto-disabled-students')
            ->assertOk()->assertSee($name)->assertSee('Re-activate');
        $this->actingAs($this->inst['cashier'])->get('/auto-disabled-students?search=zzz')
            ->assertOk()->assertDontSee($name);
        $this->actingAs($this->inst['cashier'])->get('/auto-disabled-students/pdf')
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->inst['admin'])->get('/dashboard')
            ->assertOk()->assertSee('Auto-disabled students');

        $id = DB::table('student_auto_disables')->value('id');
        $this->actingAs($this->inst['cashier'])->post('/auto-disabled-students/' . $id . '/reactivate')
            ->assertSessionHas('success');

        $this->assertSame(1, (int) DB::table('students')->where('id', $this->inst['s2'])->value('is_active'));
        $this->assertSame(0, (int) DB::table('students')->where('id', $this->inst['s2'])->value('student_disable'));
        $this->assertSame(1, (int) DB::table('student_class_enrollments')->where('id', $this->inst['e2'])->value('is_active'));
        $this->assertNull(DB::table('student_class_enrollments')->where('id', $this->inst['e2'])->value('left_at'));
        $this->assertNotNull(DB::table('student_auto_disables')->where('id', $id)->value('reactivated_at'));

        // twice: refused
        $this->actingAs($this->inst['cashier'])->post('/auto-disabled-students/' . $id . '/reactivate')
            ->assertSessionHas('error');
    }
}
