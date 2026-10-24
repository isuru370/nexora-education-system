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

        $schedule->command('class-schedules:complete-finished')
            ->everyMinute();

        // Courses (does nothing when the "courses" feature is off)
        $schedule->command('course-sessions:complete-finished')
            ->everyMinute();
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
