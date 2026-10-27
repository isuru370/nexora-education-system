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

        // Once a day at 8.45 PM (not when each class ends), so late students
        // can still be marked: the day's classes -> completed, past ones
        // nobody attended -> cancelled. A class ending after 8.45 PM is
        // completed the next evening.
        $schedule->command('class-schedules:complete-finished')
            ->dailyAt('20:45')
            ->timezone('Asia/Colombo');

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
