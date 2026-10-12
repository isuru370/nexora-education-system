<?php

namespace App\Services;

use App\Models\StudentClass;
use App\Models\StudentClassEnrollment;
use App\Models\ClassSchedule;
use App\Models\StudentAttendance;
use Carbon\Carbon;

class MonthlyClassAttendanceReportService
{
    /**
     * Generate monthly class attendance report.
     *
     * Structure:
     * Class
     *   └── Category
     *        └── Students
     */
    public function generate(string $month): array
    {
        $selectedMonth = Carbon::parse($month)->startOfMonth();

        $monthStart = $selectedMonth
            ->copy()
            ->startOfMonth()
            ->toDateString();

        $monthEnd = $selectedMonth
            ->copy()
            ->endOfMonth()
            ->toDateString();

        /*
        |--------------------------------------------------------------------------
        | 1. Get all completed class schedules for selected month
        |--------------------------------------------------------------------------
        */

        $schedules = ClassSchedule::query()
            ->whereBetween('class_date', [
                $monthStart,
                $monthEnd
            ])
            ->where('status', 'completed')
            ->where('is_active', true)
            ->with([
                'studentClass',
                'classCategoryFee.category',
            ])
            ->orderBy('class_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 2. Get enrollments valid during selected month
        |--------------------------------------------------------------------------
        |
        | Student must have been enrolled before or during the month,
        | and must not have left before the month started.
        |
        */

        $enrollments = StudentClassEnrollment::query()
            ->whereDate('enrolled_at', '<=', $monthEnd)
            ->where(function ($query) use ($monthStart) {
                $query->whereNull('left_at')
                    ->orWhereDate('left_at', '>=', $monthStart);
            })
            ->with([
                'student',
                'studentClass',
                'classCategoryFee.category',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. Get attendance records for selected month
        |--------------------------------------------------------------------------
        */

        $attendances = StudentAttendance::query()
            ->whereBetween(
                'attended_at',
                [
                    $selectedMonth
                        ->copy()
                        ->startOfMonth()
                        ->startOfDay(),

                    $selectedMonth
                        ->copy()
                        ->endOfMonth()
                        ->endOfDay(),
                ]
            )
            ->with([
                'student',
                'classSchedule',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4. Group attendance by enrollment
        |--------------------------------------------------------------------------
        */

        $attendanceByEnrollment = $attendances
            ->groupBy('student_class_enrollment_id');

        /*
        |--------------------------------------------------------------------------
        | 5. Group schedules by Class + Category Fee
        |--------------------------------------------------------------------------
        */

        $schedulesByClassCategory = $schedules
            ->groupBy(function ($schedule) {
                return $schedule->student_class_id . '_'
                    . $schedule->class_category_fee_id;
            });

        /*
        |--------------------------------------------------------------------------
        | 6. Group enrollments by Class + Category Fee
        |--------------------------------------------------------------------------
        */

        $enrollmentsByClassCategory = $enrollments
            ->groupBy(function ($enrollment) {
                return $enrollment->student_class_id . '_'
                    . $enrollment->class_category_fee_id;
            });

        /*
        |--------------------------------------------------------------------------
        | 7. Build report
        |--------------------------------------------------------------------------
        */

        $classes = StudentClass::query()
            ->whereHas('enrollments', function ($query) use (
                $monthStart,
                $monthEnd
            ) {
                $query
                    ->whereDate('enrolled_at', '<=', $monthEnd)
                    ->where(function ($q) use ($monthStart) {
                        $q->whereNull('left_at')
                            ->orWhereDate(
                                'left_at',
                                '>=',
                                $monthStart
                            );
                    });
            })
            ->with([
                'teacher',
                'grade',

                'enrollments' => function ($query) use (
                    $monthStart,
                    $monthEnd
                ) {
                    $query
                        ->whereDate('enrolled_at', '<=', $monthEnd)
                        ->where(function ($q) use ($monthStart) {
                            $q->whereNull('left_at')
                                ->orWhereDate(
                                    'left_at',
                                    '>=',
                                    $monthStart
                                );
                        })
                        ->with([
                            'student',
                            'classCategoryFee.category',
                        ]);
                },
            ])
            ->orderBy('class_name')
            ->get();

        $reportClasses = [];

        foreach ($classes as $class) {

            /*
            |--------------------------------------------------------------------------
            | Group class enrollments by Category Fee
            |--------------------------------------------------------------------------
            */

            $categoryGroups = $class->enrollments
                ->groupBy('class_category_fee_id');

            $categories = [];

            foreach ($categoryGroups as $categoryFeeId => $categoryEnrollments) {

                $firstEnrollment = $categoryEnrollments->first();

                if (!$firstEnrollment) {
                    continue;
                }

                $categoryFee = $firstEnrollment->classCategoryFee;

                $category = optional($categoryFee)->category;

                if (!$category) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Completed class days for this category
                |--------------------------------------------------------------------------
                */

                $categorySchedules = $schedulesByClassCategory->get(
                    $class->id . '_' . $categoryFeeId,
                    collect()
                );

                /*
                |--------------------------------------------------------------------------
                | Only unique completed class dates
                |--------------------------------------------------------------------------
                */

                $classDays = $categorySchedules
                    ->pluck('class_date')
                    ->map(function ($date) {
                        return Carbon::parse($date)->format('Y-m-d');
                    })
                    ->unique()
                    ->values();

                $classDayCount = $classDays->count();

                /*
                |--------------------------------------------------------------------------
                | Students
                |--------------------------------------------------------------------------
                */

                $students = [];

                foreach ($categoryEnrollments as $enrollment) {

                    $student = $enrollment->student;

                    if (!$student) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Student's valid class days
                    |--------------------------------------------------------------------------
                    |
                    | If student joined during the month, don't count previous
                    | class days as absences.
                    |
                    */

                    $enrollmentStart = Carbon::parse(
                        $enrollment->enrolled_at
                    )->startOfDay();

                    $enrollmentEnd = $enrollment->left_at
                        ? Carbon::parse($enrollment->left_at)->endOfDay()
                        : $selectedMonth
                            ->copy()
                            ->endOfMonth()
                            ->endOfDay();

                    $studentClassDays = $categorySchedules
                        ->filter(function ($schedule) use (
                            $enrollmentStart,
                            $enrollmentEnd
                        ) {
                            $date = Carbon::parse(
                                $schedule->class_date
                            );

                            return $date->between(
                                $enrollmentStart
                                    ->copy()
                                    ->startOfDay(),

                                $enrollmentEnd
                                    ->copy()
                                    ->endOfDay()
                            );
                        })
                        ->pluck('class_date')
                        ->map(function ($date) {
                            return Carbon::parse($date)->format('Y-m-d');
                        })
                        ->unique()
                        ->values();

                    $studentClassDayCount = $studentClassDays->count();

                    /*
                    |--------------------------------------------------------------------------
                    | Attendance
                    |--------------------------------------------------------------------------
                    */

                    $studentAttendance = $attendanceByEnrollment->get(
                        $enrollment->id,
                        collect()
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Count only attendance belonging to this category
                    |--------------------------------------------------------------------------
                    */

                    $attendedDays = $studentAttendance
                        ->filter(function ($attendance) use (
                            $studentClassDays,
                            $categoryFeeId
                        ) {
                            $schedule = $attendance->classSchedule;

                            if (!$schedule) {
                                return false;
                            }

                            if (
                                (int) $schedule->class_category_fee_id
                                !== (int) $categoryFeeId
                            ) {
                                return false;
                            }

                            $date = Carbon::parse(
                                $schedule->class_date
                            )->format('Y-m-d');

                            return $studentClassDays->contains($date);
                        })
                        ->pluck('class_schedule_id')
                        ->unique()
                        ->count();

                    $absentDays = max(
                        $studentClassDayCount - $attendedDays,
                        0
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Attendance Percentage
                    |--------------------------------------------------------------------------
                    */

                    $attendancePercentage =
                        $studentClassDayCount > 0
                            ? round(
                                (
                                    $attendedDays /
                                    $studentClassDayCount
                                ) * 100,
                                2
                            )
                            : 0;

                    /*
                    |--------------------------------------------------------------------------
                    | New Student
                    |--------------------------------------------------------------------------
                    */

                    $enrolledAt = $enrollment->enrolled_at
                        ? Carbon::parse($enrollment->enrolled_at)
                        : null;

                    $isNewStudent = $enrolledAt
                        ? $enrolledAt->between(
                            $selectedMonth
                                ->copy()
                                ->startOfMonth(),

                            $selectedMonth
                                ->copy()
                                ->endOfMonth()
                        )
                        : false;

                    /*
                    |--------------------------------------------------------------------------
                    | Student Report
                    |--------------------------------------------------------------------------
                    */

                    $students[] = [
                        'student_id' => $student->id,

                        'student_custom_id' =>
                            $student->custom_id,

                        'student_name' =>
                            $student->initial_name,

                        'class_days' =>
                            $studentClassDayCount,

                        'attended' =>
                            $attendedDays,

                        'absent' =>
                            $absentDays,

                        'attendance_percentage' =>
                            $attendancePercentage,

                        'is_new_student' =>
                            $isNewStudent,

                        'enrolled_at' =>
                            $enrolledAt
                                ? $enrolledAt->format('Y-m-d')
                                : null,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Category Summary
                |--------------------------------------------------------------------------
                */

                $totalStudents = count($students);

                $newStudents = collect($students)
                    ->where('is_new_student', true)
                    ->count();

                $totalClassDays = collect($students)
                    ->sum('class_days');

                $totalAttended = collect($students)
                    ->sum('attended');

                $totalAbsent = collect($students)
                    ->sum('absent');

                $attendanceRate = $totalClassDays > 0
                    ? round(
                        (
                            $totalAttended /
                            $totalClassDays
                        ) * 100,
                        2
                    )
                    : 0;

                $categories[] = [
                    'category_id' =>
                        $category->id,

                    'category_name' =>
                        $category->category_name,

                    'total_students' =>
                        $totalStudents,

                    'new_students' =>
                        $newStudents,

                    'class_days' =>
                        $classDayCount,

                    'total_attended' =>
                        $totalAttended,

                    'total_absent' =>
                        $totalAbsent,

                    'attendance_rate' =>
                        $attendanceRate,

                    'students' =>
                        $students,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Skip classes without categories/students
            |--------------------------------------------------------------------------
            */

            if (empty($categories)) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Class Summary
            |--------------------------------------------------------------------------
            */

            $classTotalStudents = collect($categories)
                ->sum('total_students');

            $classNewStudents = collect($categories)
                ->sum('new_students');

            $classTotalAttended = collect($categories)
                ->sum('total_attended');

            $classTotalAbsent = collect($categories)
                ->sum('total_absent');

            $classTotalDays = collect($categories)
                ->sum(function ($category) {
                    return $category['class_days']
                        * $category['total_students'];
                });

            $classAttendanceRate = $classTotalDays > 0
                ? round(
                    (
                        $classTotalAttended /
                        $classTotalDays
                    ) * 100,
                    2
                )
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Class Report
            |--------------------------------------------------------------------------
            */

            $reportClasses[] = [
                'class_id' =>
                    $class->id,

                'class_name' =>
                    $class->class_name,

                'grade_name' =>
                    optional($class->grade)->grade_name ?: '',

                'teacher_name' =>
                    optional($class->teacher)->initials ?: '',

                'total_students' =>
                    $classTotalStudents,

                'new_students' =>
                    $classNewStudents,

                'class_days' =>
                    $classTotalDays,

                'total_attended' =>
                    $classTotalAttended,

                'total_absent' =>
                    $classTotalAbsent,

                'attendance_rate' =>
                    $classAttendanceRate,

                'categories' =>
                    $categories,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Overall Summary
        |--------------------------------------------------------------------------
        */

        $summary = [
            'total_classes' =>
                count($reportClasses),

            'total_students' =>
                collect($reportClasses)
                    ->sum('total_students'),

            'new_students' =>
                collect($reportClasses)
                    ->sum('new_students'),

            'total_attended' =>
                collect($reportClasses)
                    ->sum('total_attended'),

            'total_absent' =>
                collect($reportClasses)
                    ->sum('total_absent'),
        ];

        /*
        |--------------------------------------------------------------------------
        | Overall Attendance Rate
        |--------------------------------------------------------------------------
        */

        $overallPossibleAttendance =
            $summary['total_attended']
            + $summary['total_absent'];

        $summary['attendance_rate'] =
            $overallPossibleAttendance > 0
                ? round(
                    (
                        $summary['total_attended'] /
                        $overallPossibleAttendance
                    ) * 100,
                    2
                )
                : 0;

        /*
        |--------------------------------------------------------------------------
        | Final Report
        |--------------------------------------------------------------------------
        */

        return [
            'month' =>
                $selectedMonth->format('Y-m'),

            'month_name' =>
                $selectedMonth->format('F Y'),

            'summary' =>
                $summary,

            'classes' =>
                $reportClasses,
        ];
    }
}