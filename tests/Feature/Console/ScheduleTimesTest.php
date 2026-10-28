<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * Classes / course days are completed once a day at 8.45 PM (Sri Lanka),
 * not when each class ends, so late students can still be marked.
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

    public function test_complete_commands_run_daily_at_845_pm_colombo(): void
    {
        foreach (['class-schedules:complete-finished', 'course-sessions:complete-finished'] as $command) {
            $event = $this->event($command);

            $this->assertSame('45 20 * * *', $event->expression, $command);
            $this->assertSame('Asia/Colombo', (string) $event->timezone, $command);
        }
    }
}
