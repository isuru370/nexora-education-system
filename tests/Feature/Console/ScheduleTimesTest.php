<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * Classes are closed 3 hours after each class's end time (checked every
 * 15 minutes); course days once a day at 8.45 PM (Sri Lanka).
 */
class ScheduleTimesTest extends TestCase
{
    private function event(string $command): Event
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $e) => str_contains((string) $e->command, $command));

        $this->assertNotNull($event, "$command is not scheduled");

        return $event;
    }

    public function test_class_schedules_are_checked_every_15_minutes(): void
    {
        $this->assertSame('*/15 * * * *', $this->event('class-schedules:complete-finished')->expression);
    }

    public function test_course_days_are_completed_daily_at_845_pm_colombo(): void
    {
        $event = $this->event('course-sessions:complete-finished');

        $this->assertSame('45 20 * * *', $event->expression);
        $this->assertSame('Asia/Colombo', (string) $event->timezone);
    }
}
