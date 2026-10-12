<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\StudentClass;
use App\Models\StudentAttendance;
use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardService
{
    /**
     * Get complete teacher dashboard
     */
    public function getDashboard(int $teacherId): array
    {
        try {

            $today = Carbon::today();

            /*
            |--------------------------------------------------------------------------
            | Teacher
            |--------------------------------------------------------------------------
            */

            $teacher = Teacher::query()
                ->select([
                    'id',
                    'custom_id',
                    'user_id',
                    'full_name',
                    'initials',
                    'email',
                    'mobile',
                    'nic',
                    'bday',
                    'gender',
                    'is_active',
                ])
                ->findOrFail($teacherId);


            /*
            |--------------------------------------------------------------------------
            | Teacher Classes
            |--------------------------------------------------------------------------
            */

            $classes = StudentClass::query()
                ->where('teacher_id', $teacherId)
                ->where('is_active', true)
                ->with([

                    'subject:id,subject_name',

                    'grade:id,grade_name',

                    /*
                    |--------------------------------------------------------------------------
                    | Class Categories
                    |--------------------------------------------------------------------------
                    |
                    | Categories only.
                    | Fee amount does NOT belong here anymore.
                    |
                    */

                    'categories' => function ($query) {

                        $query->select(
                            'class_categories.id',
                            'class_categories.category_name',
                            'class_categories.code'
                        );

                    },


                    /*
                    |--------------------------------------------------------------------------
                    | Payment Config
                    |--------------------------------------------------------------------------
                    */

                    'paymentConfig:id,student_class_id,teacher_id,teacher_percentage',


                    /*
                    |--------------------------------------------------------------------------
                    | Enrollments
                    |--------------------------------------------------------------------------
                    */

                    'enrollments' => function ($query) {

                        $query
                            ->where('is_active', true)

                            ->select([
                                'id',
                                'student_id',
                                'student_class_id',
                                'class_category_fee_id',
                                'class_category_fee_option_id',
                                'is_active',
                            ])

                            ->with([
                                'student:id,custom_id,full_name,initial_name,img_url',
                            ]);

                    },

                ])
                ->orderBy('class_name')
                ->get();


            /*
            |--------------------------------------------------------------------------
            | Class IDs
            |--------------------------------------------------------------------------
            */

            $classIds = $classes->pluck('id');


            /*
            |--------------------------------------------------------------------------
            | Today's Schedules
            |--------------------------------------------------------------------------
            */

            $todaySchedules = collect();


            if ($classIds->isNotEmpty()) {

                $todaySchedules = ClassSchedule::query()

                    ->whereIn(
                        'student_class_id',
                        $classIds
                    )

                    ->whereDate(
                        'class_date',
                        $today
                    )

                    ->where(
                        'is_active',
                        true
                    )

                    ->where(
                        'status',
                        '!=',
                        'cancelled'
                    )

                    ->with([

                        /*
                        |--------------------------------------------------------------------------
                        | Student Class
                        |--------------------------------------------------------------------------
                        */

                        'studentClass:id,class_name,class_type,medium,subject_id,grade_id',

                        'studentClass.subject:id,subject_name',

                        'studentClass.grade:id,grade_name',


                        /*
                        |--------------------------------------------------------------------------
                        | Hall
                        |--------------------------------------------------------------------------
                        */

                        'hall:id,hall_name',


                        /*
                        |--------------------------------------------------------------------------
                        | Category Fee Association
                        |--------------------------------------------------------------------------
                        |
                        | IMPORTANT:
                        | class_category_fees no longer has "fee".
                        |
                        */

                        'classCategoryFee:id,student_class_id,class_category_id',

                        'classCategoryFee.category:id,category_name,code',

                    ])

                    ->orderBy('start_time')

                    ->get();

            }


            /*
            |--------------------------------------------------------------------------
            | Today's Schedule IDs
            |--------------------------------------------------------------------------
            */

            $todayScheduleIds = $todaySchedules->pluck('id');


            /*
            |--------------------------------------------------------------------------
            | Today's Attendance
            |--------------------------------------------------------------------------
            */

            $todayAttendance = 0;


            if ($todayScheduleIds->isNotEmpty()) {

                $todayAttendance = StudentAttendance::query()
                    ->whereIn(
                        'class_schedule_id',
                        $todayScheduleIds
                    )
                    ->count();

            }


            /*
            |--------------------------------------------------------------------------
            | Total Unique Students
            |--------------------------------------------------------------------------
            */

            $totalStudents = $classes

                ->flatMap(function ($class) {

                    return $class->enrollments;

                })

                ->pluck('student_id')

                ->unique()

                ->count();


            /*
            |--------------------------------------------------------------------------
            | Today's Classes
            |--------------------------------------------------------------------------
            */

            $todayClasses = $todaySchedules

                ->map(function ($schedule) {

                    $class = $schedule->studentClass;


                    /*
                    |--------------------------------------------------------------------------
                    | Subject
                    |--------------------------------------------------------------------------
                    */

                    $subject = null;

                    if ($class && $class->subject) {

                        $subject = [
                            'id' => $class->subject->id,
                            'name' => $class->subject->subject_name,
                        ];

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Grade
                    |--------------------------------------------------------------------------
                    */

                    $grade = null;

                    if ($class && $class->grade) {

                        $grade = [
                            'id' => $class->grade->id,
                            'name' => $class->grade->grade_name,
                        ];

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Category
                    |--------------------------------------------------------------------------
                    */

                    $category = null;

                    if (
                        $schedule->classCategoryFee
                        && $schedule->classCategoryFee->category
                    ) {

                        $category = [
                            'id' => $schedule
                                ->classCategoryFee
                                ->category
                                ->id,

                            'name' => $schedule
                                ->classCategoryFee
                                ->category
                                ->category_name,

                            'code' => $schedule
                                ->classCategoryFee
                                ->category
                                ->code,
                        ];

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Hall
                    |--------------------------------------------------------------------------
                    */

                    $hall = null;

                    if ($schedule->hall) {

                        $hall = [
                            'id' => $schedule->hall->id,
                            'name' => $schedule->hall->hall_name,
                        ];

                    }


                    return [

                        'id' => $schedule->id,

                        'class_id' => $class
                            ? $class->id
                            : null,

                        'class_name' => $class
                            ? $class->class_name
                            : null,

                        'class_type' => $class
                            ? $class->class_type
                            : null,

                        'medium' => $class
                            ? $class->medium
                            : null,


                        /*
                        |--------------------------------------------------------------------------
                        | Student Count
                        |--------------------------------------------------------------------------
                        */

                        'student_count' => $class
                            ? $class->enrollments->count()
                            : 0,


                        'subject' => $subject,

                        'grade' => $grade,

                        'category' => $category,


                        /*
                        |--------------------------------------------------------------------------
                        | Schedule
                        |--------------------------------------------------------------------------
                        */

                        'date' => $schedule->class_date
                            ? $schedule->class_date->format('Y-m-d')
                            : null,

                        'start_time' => $schedule->start_time,

                        'end_time' => $schedule->end_time,

                        'status' => $schedule->status,

                        'hall' => $hall,

                        'note' => $schedule->note,

                    ];

                })

                ->values();


            /*
            |--------------------------------------------------------------------------
            | Today's Expected Students
            |--------------------------------------------------------------------------
            |
            | Count students for each scheduled class.
            |
            */

            $todayExpectedStudents = 0;


            foreach ($todaySchedules as $schedule) {

                $class = $classes->firstWhere(
                    'id',
                    $schedule->student_class_id
                );


                if ($class) {

                    $todayExpectedStudents +=
                        $class->enrollments->count();

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Attendance Percentage
            |--------------------------------------------------------------------------
            */

            $attendancePercentage = 0;


            if ($todayExpectedStudents > 0) {

                $attendancePercentage = round(
                    (
                        $todayAttendance
                        / $todayExpectedStudents
                    ) * 100,
                    1
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Class Summary
            |--------------------------------------------------------------------------
            */

            $classSummary = $classes

                ->map(function ($class) {

                    /*
                    |--------------------------------------------------------------------------
                    | Subject
                    |--------------------------------------------------------------------------
                    */

                    $subject = null;

                    if ($class->subject) {

                        $subject = [
                            'id' => $class->subject->id,
                            'name' => $class->subject->subject_name,
                        ];

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Grade
                    |--------------------------------------------------------------------------
                    */

                    $grade = null;

                    if ($class->grade) {

                        $grade = [
                            'id' => $class->grade->id,
                            'name' => $class->grade->grade_name,
                        ];

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Categories
                    |--------------------------------------------------------------------------
                    */

                    $categories = $class->categories

                        ->map(function ($category) {

                            return [
                                'id' => $category->id,
                                'name' => $category->category_name,
                                'code' => $category->code,
                            ];

                        })

                        ->values();


                    /*
                    |--------------------------------------------------------------------------
                    | Teacher Percentage
                    |--------------------------------------------------------------------------
                    */

                    $teacherPercentage = 0;


                    if (
                        $class->paymentConfig
                        && $class->paymentConfig->teacher_percentage !== null
                    ) {

                        $teacherPercentage =
                            (float) $class
                                ->paymentConfig
                                ->teacher_percentage;

                    }


                    return [

                        'id' => $class->id,

                        'class_name' => $class->class_name,

                        'class_type' => $class->class_type,

                        'medium' => $class->medium,

                        'is_active' => $class->is_active,

                        'is_ongoing' => $class->is_ongoing,

                        'subject' => $subject,

                        'grade' => $grade,

                        'categories' => $categories,

                        'student_count' =>
                            $class->enrollments->count(),

                        'teacher_percentage' =>
                            $teacherPercentage,

                    ];

                })

                ->values();


            /*
            |--------------------------------------------------------------------------
            | Final Response
            |--------------------------------------------------------------------------
            */

            return [

                'teacher' => [

                    'id' => $teacher->id,

                    'custom_id' => $teacher->custom_id,

                    'full_name' => $teacher->full_name,

                    'initials' => $teacher->initials,

                    'email' => $teacher->email,

                    'mobile' => $teacher->mobile,

                    'nic' => $teacher->nic,

                    'bday' => $teacher->bday
                        ? $teacher->bday->format('Y-m-d')
                        : null,

                    'gender' => $teacher->gender,

                    'is_active' => $teacher->is_active,

                ],


                'summary' => [

                    'total_classes' =>
                        $classes->count(),

                    'active_classes' =>
                        $classes
                            ->where('is_active', true)
                            ->count(),

                    'ongoing_classes' =>
                        $classes
                            ->where('is_ongoing', true)
                            ->count(),

                    'total_students' =>
                        $totalStudents,

                    'today_classes' =>
                        $todayClasses->count(),

                    'today_attendance' =>
                        $todayAttendance,

                    'today_expected_students' =>
                        $todayExpectedStudents,

                    'attendance_percentage' =>
                        $attendancePercentage,

                ],


                'today_classes' =>
                    $todayClasses,

                'classes' =>
                    $classSummary,

            ];

        } catch (Throwable $e) {

            Log::error(
                'Teacher dashboard failed',
                [
                    'teacher_id' => $teacherId,

                    'message' => $e->getMessage(),

                    'file' => $e->getFile(),

                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }


    /**
     * Get all classes assigned to teacher
     */
    public function getMyClasses(int $teacherId)
    {
        try {

            return StudentClass::query()

                ->where(
                    'teacher_id',
                    $teacherId
                )

                ->where(
                    'is_active',
                    true
                )

                ->with([

                    'subject:id,subject_name',

                    'grade:id,grade_name',


                    /*
                    |--------------------------------------------------------------------------
                    | Categories
                    |--------------------------------------------------------------------------
                    */

                    'categories' => function ($query) {

                        $query->select(
                            'class_categories.id',
                            'class_categories.category_name',
                            'class_categories.code'
                        );

                    },


                    /*
                    |--------------------------------------------------------------------------
                    | Payment Config
                    |--------------------------------------------------------------------------
                    */

                    'paymentConfig:id,student_class_id,teacher_id,teacher_percentage',


                    /*
                    |--------------------------------------------------------------------------
                    | Enrollments
                    |--------------------------------------------------------------------------
                    */

                    'enrollments' => function ($query) {

                        $query

                            ->where(
                                'is_active',
                                true
                            )

                            ->select([
                                'id',
                                'student_id',
                                'student_class_id',
                                'class_category_fee_id',
                                'class_category_fee_option_id',
                                'is_active',
                            ])

                            ->with([
                                'student:id,custom_id,full_name,initial_name,img_url',
                            ]);

                    },

                ])

                ->orderBy('class_name')

                ->get();

        } catch (Throwable $e) {

            Log::error(
                'Teacher classes fetch failed',
                [
                    'teacher_id' => $teacherId,

                    'message' => $e->getMessage(),

                    'file' => $e->getFile(),

                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }
}