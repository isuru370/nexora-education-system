<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClassSchedule;
use Carbon\Carbon;

class CompleteFinishedClasses extends Command
{
    protected $signature = 'class-schedules:complete-finished';

    protected $description = 'Automatically complete finished classes and cancel expired scheduled classes';

    public function handle(): int
    {
        $now = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | 1. ONGOING → COMPLETED
        |--------------------------------------------------------------------------
        |
        | If the class end time has passed, mark it as completed.
        |
        */

        $completed = ClassSchedule::query()
            ->where('status', 'ongoing')
            ->where('is_active', true)
            ->where(function ($query) use ($now) {

                // Previous dates
                $query->whereDate(
                    'class_date',
                    '<',
                    $now->toDateString()
                )

                // Today + end time passed
                ->orWhere(function ($query) use ($now) {

                    $query
                        ->whereDate(
                            'class_date',
                            $now->toDateString()
                        )
                        ->whereTime(
                            'end_time',
                            '<=',
                            $now->toTimeString()
                        );
                });
            })
            ->update([
                'status' => 'completed',
                'updated_at' => now(),
            ]);


        /*
        |--------------------------------------------------------------------------
        | 2. SCHEDULED → CANCELLED
        |--------------------------------------------------------------------------
        |
        | If a scheduled class date has passed without becoming ongoing,
        | cancel the schedule automatically.
        |
        */

        $cancelled = ClassSchedule::query()
            ->where('status', 'scheduled')
            ->where('is_active', true)
            ->whereDate(
                'class_date',
                '<',
                $now->toDateString()
            )
            ->update([
                'status' => 'cancelled',
                'cancel_reason' => 'Class date has passed without attendance.',
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);


        /*
        |--------------------------------------------------------------------------
        | Console Output
        |--------------------------------------------------------------------------
        */

        $this->info(
            "{$completed} class schedule(s) marked as completed."
        );

        $this->info(
            "{$cancelled} expired scheduled class(es) marked as cancelled."
        );

        return self::SUCCESS;
    }
}