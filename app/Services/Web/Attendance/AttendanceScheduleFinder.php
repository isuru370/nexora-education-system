<?php

namespace App\Services\Web\Attendance;

use App\Models\ClassSchedule;
use App\Support\AttendanceRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceScheduleFinder
{
    /**
     * Find the class that is currently available
     * for attendance scanning for the student.
     *
     * Attendance scanning is allowed (Payment & Attendance Rules, per
     * institute; default 1 hour before the start until the end):
     * - before_minutes before the class starts
     * - until after_minutes after it ends
     * - or any class today when the time limit is off
     *
     * Matching is done using:
     * - student_class_id
     * - class_category_fee_id
     */
    public function findCurrentSchedule(
        Collection $enrollments
    ): ?array {
        return $this->findScannableSchedules($enrollments)->first();
    }

    /**
     * Every class of the student that is open for attendance right now,
     * earliest first, as ['schedule' => ClassSchedule, 'enrollment' => ...].
     *
     * Two can be open together, e.g. A 4:30-6:00 PM and B 6:00-8:00 PM at
     * 5:30 PM (B opens one hour early). The caller must then ask which one.
     * Cancelled / inactive classes are never returned.
     */
    public function findScannableSchedules(
        Collection $enrollments
    ): Collection {
        $found = collect();

        if ($enrollments->isEmpty()) {
            return $found;
        }

        $today = Carbon::today();
        $now = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | Find today's active schedules
        |--------------------------------------------------------------------------
        |
        | Only scheduled and ongoing classes are allowed.
        | branch is loaded so the response can show the class branch.
        |
        */

        $query = ClassSchedule::query()
            ->whereDate('class_date', $today)
            ->where('is_active', true)
            // completed: a class can be closed (e.g. at 8:45 PM) while the
            // window after the end, or "no time limit", still allows marking
            ->whereIn('status', [
                'scheduled',
                'ongoing',
                'completed',
            ])
            ->with([
                'branch:id,name,code',
                'studentClass',
                'classCategoryFee.category',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Match student's enrolled classes
        |--------------------------------------------------------------------------
        |
        | An enrollment belongs to:
        |
        | student_class_id
        | +
        | class_category_fee_id
        |
        | Both values must match the SAME enrollment.
        |
        */

        $query->where(function ($query) use ($enrollments) {
            foreach ($enrollments as $enrollment) {
                $query->orWhere(function ($subQuery) use ($enrollment) {
                    $subQuery
                        ->where(
                            'student_class_id',
                            $enrollment->student_class_id
                        )
                        ->where(
                            'class_category_fee_id',
                            $enrollment->class_category_fee_id
                        );
                });
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Get schedules ordered by start time
        |--------------------------------------------------------------------------
        */

        $schedules = $query
            ->orderBy('start_time')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Find the currently scannable class
        |--------------------------------------------------------------------------
        */

        foreach ($schedules as $schedule) {

            /*
            |------------------------------------------------------------------
            | Build today's class start time
            |------------------------------------------------------------------
            */

            $start = Carbon::parse(
                $today->format('Y-m-d') . ' ' . $schedule->start_time
            );

            /*
            |------------------------------------------------------------------
            | Build today's class end time
            |------------------------------------------------------------------
            */

            $end = Carbon::parse(
                $today->format('Y-m-d') . ' ' . $schedule->end_time
            );

            /*
            |------------------------------------------------------------------
            | Attendance window (AttendanceRules)
            |------------------------------------------------------------------
            |
            | Example with the default (60 before, 0 after):
            |
            | Class       : 09:00 - 11:00
            | Scan allowed: 08:00 - 11:00
            |
            */

            if (!AttendanceRules::isOpen($now, $start, $end)) {
                continue;
            }

            /*
            |------------------------------------------------------------------
            | Find the exact enrollment
            |------------------------------------------------------------------
            |
            | We must use both:
            |
            | student_class_id
            | +
            | class_category_fee_id
            |
            */

            $enrollment = $enrollments->first(
                function ($item) use ($schedule) {
                    return
                        (int) $item->student_class_id ===
                        (int) $schedule->student_class_id

                        &&

                        (int) $item->class_category_fee_id ===
                        (int) $schedule->class_category_fee_id;
                }
            );

            if (!$enrollment) {
                continue;
            }

            /*
            |------------------------------------------------------------------
            | Return matched schedule + enrollment
            |------------------------------------------------------------------
            */

            $found->push([
                'schedule' => $schedule,
                'enrollment' => $enrollment,
            ]);
        }

        return $found;
    }
}