<?php

namespace App\Services\Teacher;

use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class MyTimetableService
{
    /**
     * Get teacher timetable for a specific date.
     *
     * Cancelled schedules are included so the teacher
     * can see cancelled classes and the cancellation reason.
     */
    public function getTimetable(
        int $teacherId,
        ?string $date = null
    ): array {
        try {
            /*
             * Selected date.
             *
             * If date is not provided,
             * today's date will be used.
             */
            $selectedDate = $date
                ? Carbon::parse($date)->startOfDay()
                : Carbon::today();

            /*
             * Get schedules belonging to this teacher.
             *
             * IMPORTANT:
             *
             * - is_active = true only
             * - cancelled schedules are INCLUDED
             * - soft deleted schedules are automatically excluded
             */
            $schedules = ClassSchedule::query()
                ->whereDate('class_date', $selectedDate)
                ->where('is_active', true)

                /*
                 * Make sure the schedule belongs
                 * to a class assigned to this teacher.
                 */
                ->whereHas('studentClass', function ($query) use ($teacherId) {
                    $query->where('teacher_id', $teacherId);
                })

                ->with([

                    /*
                     * Student Class
                     */
                    'studentClass:id,class_name,class_type,medium,teacher_id,subject_id,grade_id',

                    /*
                     * Subject
                     */
                    'studentClass.subject:id,subject_name',

                    /*
                     * Grade
                     */
                    'studentClass.grade:id,grade_name',

                    /*
                     * Schedule's exact category fee association.
                     *
                     * IMPORTANT:
                     * class_category_fees no longer contains fee.
                     *
                     * ClassSchedule
                     *      ↓
                     * classCategoryFee
                     *      ↓
                     * category
                     */
                    'classCategoryFee:id,student_class_id,class_category_id,is_active',

                    /*
                     * Category
                     */
                    'classCategoryFee.category:id,category_name,code',

                    /*
                     * Active student enrollments
                     */
                    'studentClass.enrollments' => function ($query) {
                        $query
                            ->where('is_active', true)
                            ->select([
                                'id',
                                'student_id',
                                'student_class_id',
                                'class_category_fee_id',
                                'class_category_fee_option_id',
                                'is_active',
                                'is_free_card',
                            ]);
                    },

                    /*
                     * Hall
                     */
                    'hall:id,hall_name,hall_type,hall_price',
                ])

                /*
                 * Earliest class first.
                 */
                ->orderBy('start_time')
                ->get();

            /*
             * Convert schedules into timetable response.
             */
            $classes = $schedules
                ->map(function (ClassSchedule $schedule) use ($selectedDate) {

                    $studentClass = $schedule->studentClass;

                    /*
                     * Safety check.
                     */
                    if (!$studentClass) {
                        return null;
                    }

                    /*
                     * Exact category fee association
                     * attached to this schedule.
                     */
                    $categoryFee = $schedule->classCategoryFee;

                    /*
                     * Exact category.
                     */
                    $category = null;

                    if ($categoryFee && $categoryFee->category) {
                        $category = $categoryFee->category;
                    }

                    /*
                     * Cancelled schedules must remain
                     * cancelled regardless of the time.
                     */
                    $status = $schedule->status;

                    /*
                     * Active student count.
                     */
                    $studentCount = $studentClass
                        ->enrollments
                        ->count();

                    return [

                        /*
                         * Schedule ID
                         */
                        'id' => $schedule->id,

                        /*
                         * Student Class ID
                         */
                        'class_id' => $studentClass->id,

                        /*
                         * Main title.
                         *
                         * Example:
                         * English
                         */
                        'title' => $studentClass->subject
                            ? $studentClass->subject->subject_name
                            : $studentClass->class_name,

                        /*
                         * Example:
                         * Grade 9 • Theory
                         */
                        'subtitle' => $this->buildSubtitle(
                            $studentClass->grade
                                ? $studentClass->grade->grade_name
                                : null,
                            $category
                                ? $category->category_name
                                : null
                        ),

                        /*
                         * Class information
                         */
                        'class_name' => $studentClass->class_name,

                        'class_type' => $studentClass->class_type,

                        'medium' => $studentClass->medium,

                        /*
                         * Subject
                         */
                        'subject' => $studentClass->subject
                            ? [
                                'id' => $studentClass->subject->id,
                                'name' => $studentClass->subject->subject_name,
                            ]
                            : null,

                        /*
                         * Grade
                         */
                        'grade' => $studentClass->grade
                            ? [
                                'id' => $studentClass->grade->id,
                                'name' => $studentClass->grade->grade_name,
                            ]
                            : null,

                        /*
                         * Category
                         */
                        'category' => $category
                            ? [
                                'id' => $category->id,
                                'name' => $category->category_name,
                                'code' => $category->code,
                            ]
                            : null,

                        /*
                         * Category Fee Association
                         *
                         * NOTE:
                         * This is NOT the fee amount.
                         *
                         * The fee amount belongs to
                         * class_category_fee_options.
                         */
                        'category_fee' => $categoryFee
                            ? [
                                'id' => $categoryFee->id,
                                'is_active' => (bool) $categoryFee->is_active,
                            ]
                            : null,

                        /*
                         * Date
                         */
                        'date' => $selectedDate->format('Y-m-d'),

                        /*
                         * Time
                         */
                        'start_time' => $schedule->start_time,

                        'end_time' => $schedule->end_time,

                        /*
                         * Hall
                         */
                        'hall' => $schedule->hall
                            ? [
                                'id' => $schedule->hall->id,
                                'name' => $schedule->hall->hall_name,
                                'type' => $schedule->hall->hall_type,
                                'price' => $schedule->hall->hall_price,
                            ]
                            : null,

                        /*
                         * Active student count
                         */
                        'students' => $studentCount,

                        /*
                         * UI status:
                         *
                         * upcoming
                         * current
                         * completed
                         * cancelled
                         */
                        'status' => $status,

                        /*
                         * Actual database schedule status.
                         */
                        'schedule_status' => $schedule->status,

                        /*
                         * Cancellation details
                         */
                        'cancel_reason' => $schedule->cancel_reason,

                        'cancelled_by' => $schedule->cancelled_by,

                        'cancelled_at' => $schedule->cancelled_at,

                        /*
                         * Optional note
                         */
                        'note' => $schedule->note,
                    ];
                })
                ->filter()
                ->values();

            /*
             * Final API response.
             */
            return [
                'date' => $selectedDate->format('Y-m-d'),

                'classes' => $classes->toArray(),

                'summary' => $this->buildSummary($classes),
            ];
        } catch (Throwable $e) {

            Log::error('Teacher timetable fetch failed.', [
                'teacher_id' => $teacherId,
                'date' => $date,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate timetable UI status.
     *
     * Returns:
     *
     * upcoming
     * current
     * completed
     */
    private function calculateStatus(
        Carbon $selectedDate,
        ?string $startTime,
        ?string $endTime
    ): string {

        /*
         * If time information is missing,
         * treat the class as upcoming.
         */
        if (!$startTime || !$endTime) {
            return 'upcoming';
        }

        $now = Carbon::now();

        /*
         * Build class start datetime.
         */
        $start = Carbon::parse(
            $selectedDate->format('Y-m-d') . ' ' . $startTime
        );

        /*
         * Build class end datetime.
         */
        $end = Carbon::parse(
            $selectedDate->format('Y-m-d') . ' ' . $endTime
        );

        /*
         * Selected date is before today.
         */
        if ($selectedDate->lt($now->copy()->startOfDay())) {
            return 'completed';
        }

        /*
         * Selected date is after today.
         */
        if ($selectedDate->gt($now->copy()->startOfDay())) {
            return 'upcoming';
        }

        /*
         * Today:
         *
         * Class hasn't started yet.
         */
        if ($now->lt($start)) {
            return 'upcoming';
        }

        /*
         * Class is currently running.
         */
        if ($now->gte($start) && $now->lt($end)) {
            return 'current';
        }

        /*
         * Class has ended.
         */
        return 'completed';
    }

    /**
     * Build subtitle.
     *
     * Example:
     *
     * Grade 9 • Theory
     */
    private function buildSubtitle(
        ?string $grade,
        ?string $category
    ): string {

        $parts = [];

        if ($grade) {
            $parts[] = 'Grade ' . $grade;
        }

        if ($category) {
            $parts[] = $category;
        }

        return implode(' • ', $parts);
    }

    /**
     * Build timetable summary.
     */
    private function buildSummary(Collection $classes): array
    {
        return [
            'total_classes' => $classes->count(),

            'scheduled' => $classes
                ->where('status', 'scheduled')
                ->count(),

            'ongoing' => $classes
                ->where('status', 'ongoing')
                ->count(),

            'completed' => $classes
                ->where('status', 'completed')
                ->count(),

            'cancelled' => $classes
                ->where('status', 'cancelled')
                ->count(),

            'total_students' => $classes->sum('students'),
        ];
    }
}
