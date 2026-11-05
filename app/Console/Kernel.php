<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SendPortalCredentials::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('disable:expired-students')->dailyAt('00:00');

        $schedule->command('backup:database')
            ->dailyAt('02:15')
            ->timezone('Asia/Colombo');

        // "Promote Class": close the old class on the new class's start date
        $schedule->command('nexora:apply-class-promotions')->hourly();

        // Every 15 minutes: a class is closed 3 hours after its own end time
        // (attended -> completed, nobody attended -> cancelled), so late
        // students can still be marked and evening classes are not closed early.
        $schedule->command('class-schedules:complete-finished')
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        // Courses (does nothing when the "courses" feature is off)
        $schedule->command('course-sessions:complete-finished')
            ->dailyAt('20:45')
            ->timezone('Asia/Colombo');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
