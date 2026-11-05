<?php

namespace App\Console\Commands;

use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Close the classes that are over: 3 hours after a class's end time
 * (runs every 15 minutes).
 *
 *   ongoing   (attendance was marked) -> completed
 *   scheduled (no attendance)         -> cancelled
 *
 * Each class is closed by its own end time, so an evening class (e.g.
 * 6.30 - 9.30 PM) is not closed while it is still going on, and late
 * students can be marked until 3 hours after it ends.
 */
class CompleteFinishedClasses extends Command
{
    /** Hours after a class's end time before it is closed */
    public const GRACE_HOURS = 3;

    protected $signature = 'class-schedules:complete-finished';

    protected $description = 'Complete / cancel classes 3 hours after their end time';

    public function handle(): int
    {
        $now = Carbon::now();

        // Only today and earlier can be over; the exact time is checked below
        $open = ClassSchedule::query()
            ->whereIn('status', ['ongoing', 'scheduled'])
            ->where('is_active', true)
            ->whereDate('class_date', '<=', $now->toDateString())
            ->get(['id', 'class_date', 'start_time', 'end_time', 'status']);

        $finished = $open->filter(fn (ClassSchedule $s) => $this->closesAt($s)->lte($now));

        $ongoing = $finished->where('status', 'ongoing')->pluck('id');
        $scheduled = $finished->where('status', 'scheduled')->pluck('id');

        $completed = $ongoing->isEmpty() ? 0 : ClassSchedule::query()
            ->whereIn('id', $ongoing)
            ->where('status', 'ongoing')
            ->update([
                'status' => 'completed',
                'updated_at' => now(),
            ]);

        $cancelled = $scheduled->isEmpty() ? 0 : ClassSchedule::query()
            ->whereIn('id', $scheduled)
            ->where('status', 'scheduled')
            ->update([
                'status' => 'cancelled',
                'cancel_reason' => 'Class ended without attendance.',
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);

        $this->info("{$completed} class schedule(s) marked as completed.");
        $this->info("{$cancelled} class schedule(s) without attendance marked as cancelled.");

        return self::SUCCESS;
    }

    /** End of the class + 3 hours (a class ending after midnight ends the next day). */
    public function closesAt(ClassSchedule $schedule): Carbon
    {
        $date = Carbon::parse($schedule->class_date)->toDateString();
        $start = Carbon::parse($date . ' ' . Carbon::parse($schedule->start_time ?: '00:00')->format('H:i:s'));
        $end = Carbon::parse($date . ' ' . Carbon::parse($schedule->end_time ?: '23:59')->format('H:i:s'));

        if ($end->lte($start)) {
            $end->addDay();
        }

        return $end->addHours(self::GRACE_HOURS);
    }
}
