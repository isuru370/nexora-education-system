<?php

namespace Tests\Feature\Report;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** Attendance marked by scanning the card vs typing the Student ID. */
class ManualAttendanceReportTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    private function typed(): void
    {
        // S2 marked by typing the Student ID
        $row = (array) DB::table('student_attendances')->first();
        unset($row['id']);
        if (array_key_exists('local_uuid', $row)) {
            $row['local_uuid'] = null;
        }
        DB::table('student_attendances')->insert([
            'student_id' => $this->inst['s2'], 'student_class_enrollment_id' => $this->inst['e2'],
            'mark_method' => 'manual_mobile', 'attended_at' => '2026-10-06 15:20:00',
        ] + $row);
    }

    public function test_report_shows_scanned_and_typed_per_class_and_the_students(): void
    {
        $this->buildInstitute();
        $this->typed();
        $s2 = DB::table('students')->where('id', $this->inst['s2'])->value('initial_name');

        $this->actingAs($this->inst['cashier'])
            ->get('/reports/manual-attendance?from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->assertSee('Card Scan vs Manual Attendance')
            ->assertSeeInOrder(['Card scanned', '1', 'Typed (no card)', '1'])
            ->assertSee($s2);
    }

    public function test_app_attendance_report_tells_scanned_from_typed(): void
    {
        $this->buildInstitute();
        $this->typed();
        Sanctum::actingAs($this->inst['admin']);

        $data = $this->postJson('/api/attendance/report', ['schedule_id' => DB::table('student_attendances')->value('class_schedule_id')])
            ->assertOk()->json('data');

        $this->assertSame(1, $data['summary']['qr_marked']);
        $this->assertSame(1, $data['summary']['manual_marked']);

        $byStudent = collect($data['students'])->keyBy('student.student_code');
        $this->assertTrue($byStudent['S2']['attendance']['is_manual']);
        $this->assertSame('manual_mobile', $byStudent['S2']['attendance']['mark_method']);
        $this->assertFalse($byStudent['S1']['attendance']['is_manual']);
    }
}
