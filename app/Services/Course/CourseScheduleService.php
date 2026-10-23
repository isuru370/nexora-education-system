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
     * Make the course's class days match its dates and weekly days (each
     * with its own time and hall). Never touched: days with attendance, and
     * days added or changed by hand (is_custom). Time / hall changes apply
     * from today on.
     */
    public function syncSessions(Course $course): void
    {
        $slots = $course->days()->get()->keyBy('day');
        $dates = $this->sessionDates($course->start_date, $course->end_date, $slots->keys()->all());
        $sessions = $course->sessions()->withCount('attendances')->get();

        $weekly = $sessions->where('is_custom', false)
            ->keyBy(fn (CourseSession $session) => $session->session_date->toDateString());

        // Dates already used by a hand-made day (its own date, or the weekly date it replaced)
        $custom = $sessions->where('is_custom', true);
        $taken = $custom->map(fn ($s) => $s->session_date->toDateString())
            ->merge($custom->filter(fn ($s) => $s->original_date)->map(fn ($s) => $s->original_date->toDateString()))
            ->unique()->all();

        // Remove weekly dates that are no longer class days (only if nobody attended)
        foreach ($weekly as $date => $session) {
            if ((!$dates->contains($date) || in_array($date, $taken, true)) && $session->attendances_count === 0) {
                $session->delete();
            }
        }

        $today = now()->toDateString();

        foreach ($dates as $date) {
            if (in_array($date, $taken, true)) {
                continue;
            }

            $slot = $slots->get(strtolower(Carbon::parse($date)->englishDayOfWeek));
            $fields = [
                'branch_id' => $course->branch_id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'class_hall_id' => $slot->class_hall_id,
            ];
            $session = $weekly->get($date);

            if (!$session) {
                $course->sessions()->create($fields + ['session_date' => $date, 'status' => 'scheduled']);
            } elseif ($date >= $today && $session->status === 'scheduled') {
                $session->update($fields);
            }
        }
    }
}
