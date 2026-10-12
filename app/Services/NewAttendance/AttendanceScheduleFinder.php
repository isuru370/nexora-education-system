<?php

namespace App\Services\NewAttendance;

use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceScheduleFinder
{
    /**
     * Find the class that is currently available
     * for attendance scanning for the student.
     *
     * Attendance scanning is allowed:
     * - 1 hour before class starts
     * - Until the class ends
     *
     * Matching is done using:
     * - student_class_id
     * - class_category_fee_id
     */
    public function findCurrentSchedule(
        Collection $enrollments
    ): ?array {
        if ($enrollments->isEmpty()) {
            return null;
        }

        $today = Carbon::today();
        $now = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | Find today's active schedules
        |--------------------------------------------------------------------------
        |
        | Only scheduled and ongoing classes are allowed.
        |
        */

        $query = ClassSchedule::query()
            ->whereDate('class_date', $today)
            ->where('is_active', true)
            ->whereIn('status', [
                'scheduled',
                'ongoing',
            ])
            ->with([
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
            | Attendance scanning starts 1 hour before class
            |------------------------------------------------------------------
            |
            | Example:
            |
            | Class       : 09:00 - 11:00
            | Scan allowed: 08:00 - 11:00
            |
            */

            $scanStart = $start->copy()->subHour();

            /*
            |------------------------------------------------------------------
            | Check current time
            |------------------------------------------------------------------
            */

            if (!$now->betweenIncluded($scanStart, $end)) {
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

            return [
                'schedule' => $schedule,
                'enrollment' => $enrollment,
            ];
        }

        return null;
    }
}