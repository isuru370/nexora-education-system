<?php

namespace Tests\Feature\Console;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsInstitute;
use Tests\TestCase;

/** A class is closed 3 hours after its own end time. */
class CompleteFinishedClassesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInstitute;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function schedule(string $date, string $start, string $end, string $status): int
    {
        $row = (array) DB::table('class_schedules')->first();
        unset($row['id']);

        return DB::table('class_schedules')->insertGetId([
            'class_date' => $date, 'start_time' => $start, 'end_time' => $end, 'status' => $status,
            'day_of_week' => strtolower(date('l', strtotime($date))),
        ] + $row);
    }

    private function status(int $id): string
    {
        return DB::table('class_schedules')->where('id', $id)->value('status');
    }

    public function test_evening_class_is_closed_3_hours_after_it_ends(): void
    {
        $this->buildInstitute();
        $evening = $this->schedule('2026-11-04', '18:30:00', '21:30:00', 'scheduled');
        $attended = $this->schedule('2026-11-04', '15:00:00', '17:00:00', 'ongoing');

        // 8.45 PM: the evening class is still going on - left open
        Carbon::setTestNow('2026-11-04 20:45:00');
        $this->artisan('class-schedules:complete-finished')->assertExitCode(0);
        $this->assertSame('scheduled', $this->status($evening));
        $this->assertSame('completed', $this->status($attended));   // 17:00 + 3h = 20:00

        // 11.59 PM: still open (closes at 00:30)
        Carbon::setTestNow('2026-11-04 23:59:00');
        $this->artisan('class-schedules:complete-finished')->assertExitCode(0);
        $this->assertSame('scheduled', $this->status($evening));

        // 00:30: nobody was marked -> cancelled
        Carbon::setTestNow('2026-11-05 00:30:00');
        $this->artisan('class-schedules:complete-finished')->assertExitCode(0);
        $this->assertSame('cancelled', $this->status($evening));
        $this->assertSame('Class ended without attendance.',
            DB::table('class_schedules')->where('id', $evening)->value('cancel_reason'));
    }

    public function test_attended_evening_class_is_completed_not_cancelled(): void
    {
        $this->buildInstitute();
        $evening = $this->schedule('2026-11-04', '18:30:00', '21:30:00', 'ongoing');

        Carbon::setTestNow('2026-11-05 00:15:00');
        $this->artisan('class-schedules:complete-finished')->assertExitCode(0);
        $this->assertSame('ongoing', $this->status($evening));

        Carbon::setTestNow('2026-11-05 00:45:00');
        $this->artisan('class-schedules:complete-finished')->assertExitCode(0);
        $this->assertSame('completed', $this->status($evening));
    }

    public function test_future_classes_are_left_alone(): void
    {
        $this->buildInstitute();
        $tomorrow = $this->schedule('2026-11-06', '08:00:00', '10:00:00', 'scheduled');

        Carbon::setTestNow('2026-11-05 23:00:00');
        $this->artisan('class-schedules:complete-finished')->assertExitCode(0);
        $this->assertSame('scheduled', $this->status($tomorrow));
    }
}
