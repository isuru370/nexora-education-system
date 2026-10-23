<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\CourseSession;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Course months (= installments) and the class days between start and end.
 */
class CourseScheduleService
{
    /** Number of course months, e.g. 1 Jan - 30 Jun = 6. At least 1. */
    public function monthCount(Carbon|string $start, Carbon|string $end): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay()->addDay();

        $months = ($end->year - $start->year) * 12 + ($end->month - $start->month);

        // A part of a month counts as a month (1 Jan - 15 Jun = 6).
        if ($start->copy()->addMonthsNoOverflow($months)->lt($end)) {
            $months++;
        }

        return max(1, $months);
    }

    /**
     * Class dates between start and end on the given week days.
     *
     * @param  string[]  $days  e.g. ['saturday', 'sunday']
     * @return Collection<int, string> Y-m-d
     */
    public function sessionDates(Carbon|string $start, Carbon|string $end, array $days): Collection
    {
        $days = array_map('strtolower', $days);

        return collect(CarbonPeriod::create(Carbon::parse($start), Carbon::parse($end)))
            ->filter(fn (Carbon $date) => in_array(strtolower($date->englishDayOfWeek), $days, true))
            ->map(fn (Carbon $date) => $date->toDateString())
            ->values();
    }

    /**
     * Make the course's sessions match its dates / days / time / hall.
     * Sessions that already have attendance are never deleted.
     */
    public function syncSessions(Course $course): void
    {
        $dates = $this->sessionDates($course->start_date, $course->end_date, $course->class_days ?? []);
        $existing = $course->sessions()->withCount('attendances')->get()
            ->keyBy(fn (CourseSession $session) => $session->session_date->toDateString());

        // Remove dates that are no longer class days (only if nobody attended)
        foreach ($existing as $date => $session) {
            if (!$dates->contains($date) && $session->attendances_count === 0) {
                $session->delete();
            }
        }

        $today = now()->toDateString();

        foreach ($dates as $date) {
            $session = $existing->get($date);

            if (!$session) {
                $course->sessions()->create([
                    'branch_id' => $course->branch_id,
                    'session_date' => $date,
                    'start_time' => $course->start_time,
                    'end_time' => $course->end_time,
                    'class_hall_id' => $course->class_hall_id,
                    'status' => 'scheduled',
                ]);
                continue;
            }

            // New time / hall for sessions from today on
            if ($date >= $today) {
                $session->update([
                    'branch_id' => $course->branch_id,
                    'start_time' => $course->start_time,
                    'end_time' => $course->end_time,
                    'class_hall_id' => $course->class_hall_id,
                ]);
            }
        }
    }
}
