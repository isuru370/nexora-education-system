<?php

namespace Tests\Feature\Staff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    public function test_report_for_a_class_that_has_not_started_is_a_clear_message_not_a_500(): void
    {
        $this->buildInstitute();
        Sanctum::actingAs($this->inst['cashier']);

        // scheduled, not started
        $this->postJson('/api/attendance/report', ['schedule_id' => $this->inst['schedule']])
            ->assertStatus(422)
            ->assertJsonPath('message', 'The attendance report is available once the class has started.');

        DB::table('class_schedules')->where('id', $this->inst['schedule'])->update(['status' => 'ongoing']);

        $this->postJson('/api/attendance/report', ['schedule_id' => $this->inst['schedule']])
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
