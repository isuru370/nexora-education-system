<?php

namespace App\Services\NewAttendance;

use App\Models\ClassSchedule;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentClassEnrollment;
use Carbon\Carbon;
use Throwable;

class AttendanceService
{
    protected $studentFinder;

    protected $scheduleFinder;

    protected $attendanceMarker;

    /**
     * AttendanceService constructor.
     */
    public function __construct(
        AttendanceStudentFinder $studentFinder,
        AttendanceScheduleFinder $scheduleFinder,
        AttendanceMarker $attendanceMarker
    ) {
        $this->studentFinder = $studentFinder;
        $this->scheduleFinder = $scheduleFinder;
        $this->attendanceMarker = $attendanceMarker;
    }

    /**
     * Main attendance flow.
     *
     * Student ID / QR
     *      ↓
     * Find Student
     *      ↓
     * Find Active Enrollment
     *      ↓
     * Find Current Class
     *      ↓
     * Check Duplicate
     *      ↓
     * Mark Attendance
     *      ↓
     * Monthly Summary
     *      ↓
     * Final Response
     */
    public function scan(
        string $code,
        string $markMethod
    ): array {
        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Find Student
            |--------------------------------------------------------------------------
            */

            $student = $this->studentFinder->findStudent($code);

            if (!$student) {
                return $this->error(
                    'Student not found.',
                    404
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Find Active Enrollments
            |--------------------------------------------------------------------------
            */

            $enrollments = $this->studentFinder
                ->getActiveEnrollments($student);

            if ($enrollments->isEmpty()) {
                return $this->error(
                    'Student has no active class enrollment.',
                    422,
                    [
                        'student' => $this->studentData($student),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Find Current Schedule
            |--------------------------------------------------------------------------
            */

            $current = $this->scheduleFinder
                ->findCurrentSchedule($enrollments);

            if (!$current) {
                return $this->error(
                    'No active class found for this student at this time.',
                    422,
                    [
                        'student' => $this->studentData($student),
                    ]
                );
            }

            $schedule = $current['schedule'];

            $enrollment = $current['enrollment'];

            /*
            |--------------------------------------------------------------------------
            | 4. Mark Attendance
            |--------------------------------------------------------------------------
            */

            $markResult = $this->attendanceMarker->mark(
                $student,
                $enrollment,
                $schedule,
                $markMethod
            );

            /*
            |--------------------------------------------------------------------------
            | 5. Monthly Summary
            |--------------------------------------------------------------------------
            */

            $monthlySummary = $this->getMonthlySummary(
                $student,
                $enrollment
            );

            /*
            |--------------------------------------------------------------------------
            | 6. Build Final Response
            |--------------------------------------------------------------------------
            */

            $data = $this->buildResponse(
                $student,
                $enrollment,
                $schedule,
                $markResult['attendance'],
                $markResult['already_marked'],
                $monthlySummary
            );

            /*
            |--------------------------------------------------------------------------
            | 7. Already Marked
            |--------------------------------------------------------------------------
            */

            if ($markResult['already_marked']) {
                return [
                    'success' => true,
                    'message' => 'Attendance already marked for this class.',
                    'data' => $data,
                    'status_code' => 200,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 8. Successfully Marked
            |--------------------------------------------------------------------------
            */

            /*
 * =========================================================================
 * Attendance Success SMS
 * =========================================================================
 *
 * SMS sending currently disabled.
 *
 * Uncomment this block when you want to enable attendance SMS.
 */

            // if (!empty($student->guardian_mobile)) {
            //     SendAttendanceSuccessSmsJob::dispatch(
            //         $student->guardian_mobile,
            //         'Attendance marked successfully for '
            //             . $student->initial_name
            //             . '.'
            //     );
            // }


            /*
 * =========================================================================
 * Attendance Success Notification
 * =========================================================================
 *
 * Notification sending currently disabled.
 *
 * SendNotificationJob requires an existing Notification ID.
 * Therefore, uncomment this only after creating the Notification record.
 */

            // SendNotificationJob::dispatch($notification->id);

            return [
                'success' => true,
                'message' => 'Attendance marked successfully.',
                'data' => $data,
                'status_code' => 201,
            ];
        } catch (Throwable $e) {

            report($e);

            return [
                'success' => false,
                'message' => 'Something went wrong while processing attendance.',
                'data' => null,
                'status_code' => 500,
            ];
        }
    }

    /**
     * Monthly attendance + payment summary.
     */
    protected function getMonthlySummary(
        Student $student,
        StudentClassEnrollment $enrollment
    ): array {
        $monthStart = Carbon::now()->startOfMonth();

        $monthEnd = Carbon::now()->endOfMonth();

        /*
        |--------------------------------------------------------------------------
        | 1. Class Days
        |--------------------------------------------------------------------------
        |
        | Only completed schedules are counted.
        |
        */

        $classDays = ClassSchedule::query()
            ->where(
                'student_class_id',
                $enrollment->student_class_id
            )
            ->where(
                'class_category_fee_id',
                $enrollment->class_category_fee_id
            )
            ->whereBetween('class_date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->where('status', 'completed')
            ->where('is_active', true)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 2. Attended Days
        |--------------------------------------------------------------------------
        */

        $attendedDays = StudentAttendance::query()
            ->where('student_id', $student->id)
            ->where(
                'student_class_enrollment_id',
                $enrollment->id
            )
            ->whereHas('classSchedule', function ($query) use (
                $enrollment,
                $monthStart,
                $monthEnd
            ) {
                $query
                    ->where(
                        'student_class_id',
                        $enrollment->student_class_id
                    )
                    ->where(
                        'class_category_fee_id',
                        $enrollment->class_category_fee_id
                    )
                    ->whereBetween('class_date', [
                        $monthStart->toDateString(),
                        $monthEnd->toDateString(),
                    ])
                    ->where('status', 'completed')
                    ->where('is_active', true);
            })
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 3. Absent Days
        |--------------------------------------------------------------------------
        */

        $absentDays = max(
            $classDays - $attendedDays,
            0
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Attendance Percentage
        |--------------------------------------------------------------------------
        */

        $attendancePercentage = $classDays > 0
            ? round(
                ($attendedDays / $classDays) * 100,
                2
            )
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 5. Last Payment
        |--------------------------------------------------------------------------
        */

        $lastPayment = Payment::query()
            ->where('student_id', $student->id)
            ->where(
                'student_class_enrollment_id',
                $enrollment->id
            )
            ->latest('paid_at')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | 6. Return Summary
        |--------------------------------------------------------------------------
        */

        return [
            'month' => Carbon::now()->format('Y-m'),

            'class_days' => $classDays,

            'attended_days' => $attendedDays,

            'absent_days' => $absentDays,

            'attendance_percentage' => $attendancePercentage,

            'last_payment' => $lastPayment
                ? [
                    'payment_id' => $lastPayment->id,

                    'amount' => (float) $lastPayment->amount,

                    'discount_amount' => (float) (
                        $lastPayment->discount_amount ?? 0
                    ),

                    'paid_at' => optional(
                        $lastPayment->paid_at
                    )->format('Y-m-d H:i:s'),

                    'payment_month' => optional(
                        $lastPayment->payment_month
                    )->format('Y-m'),

                    'payment_method' =>
                    $lastPayment->payment_method,

                    'receipt_number' =>
                    $lastPayment->receipt_number,

                    'status' =>
                    $lastPayment->status,
                ]
                : null,
        ];
    }

    /**
     * Build final API response.
     */
    protected function buildResponse(
        Student $student,
        StudentClassEnrollment $enrollment,
        ClassSchedule $schedule,
        ?StudentAttendance $attendance,
        bool $alreadyMarked,
        array $monthlySummary
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Selected Fee Option
        |--------------------------------------------------------------------------
        */

        $feeOption = $enrollment->classCategoryFeeOption;

        /*
        |--------------------------------------------------------------------------
        | Final Fee
        |--------------------------------------------------------------------------
        */

        $finalFee = $enrollment->is_free_card
            ? 0
            : (float) optional($feeOption)->fee;

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */

        $lastPayment = $monthlySummary['last_payment'];

        /*
        |--------------------------------------------------------------------------
        | Attendance Summary
        |--------------------------------------------------------------------------
        */

        $attendanceSummary = [
            'month' => $monthlySummary['month'],

            'total_count' => $monthlySummary['class_days'],

            'present_count' => $monthlySummary['attended_days'],

            'absent_count' => $monthlySummary['absent_days'],

            'attendance_percentage' =>
            (int) round(
                $monthlySummary['attendance_percentage']
            ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Tute
        |--------------------------------------------------------------------------
        |
        | Tute issue system is not connected to this new attendance
        | service yet.
        |
        */

        $tute = [
            'month' => $monthlySummary['month'],
            'is_issued' => false,
            'issued_at' => null,
            'note' => null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return [
            /*
            |--------------------------------------------------------------------------
            | Student
            |--------------------------------------------------------------------------
            */

            'student' => $this->studentData($student),

            /*
            |--------------------------------------------------------------------------
            | Schedule
            |--------------------------------------------------------------------------
            */

            'schedule' => $this->scheduleData($schedule),

            /*
            |--------------------------------------------------------------------------
            | Enrollment
            |--------------------------------------------------------------------------
            */

            'enrollment' => [
                'id' => $enrollment->id,

                'student_class_id' =>
                $enrollment->student_class_id,

                'class_category_fee_id' =>
                $enrollment->class_category_fee_id,

                'class_category_fee_option_id' =>
                $enrollment->class_category_fee_option_id,

                'final_fee' => $finalFee,

                'is_free_card' =>
                (bool) $enrollment->is_free_card,

                'class_category_fee_option' =>
                $feeOption
                    ? [
                        'id' => $feeOption->id,

                        'class_category_fee_id' =>
                        $feeOption->class_category_fee_id,

                        'label' =>
                        $feeOption->label,

                        'fee' =>
                        (float) $feeOption->fee,

                        'is_default' =>
                        (bool) $feeOption->is_default,

                        'is_active' =>
                        (bool) $feeOption->is_active,
                    ]
                    : null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Last Payment
            |--------------------------------------------------------------------------
            */

            'last_payment' => $lastPayment,

            /*
            |--------------------------------------------------------------------------
            | Attendance
            |--------------------------------------------------------------------------
            */

            'attendance' => [
                'id' => $attendance
                    ? $attendance->id
                    : null,

                'status' => 'present',

                'marked_at' => $attendance
                    ? optional(
                        $attendance->attended_at
                    )->format('Y-m-d H:i:s')
                    : null,

                'mark_method' => $attendance
                    ? $attendance->mark_method
                    : null,

                'already_marked' => $alreadyMarked,
            ],

            /*
            |--------------------------------------------------------------------------
            | Tute
            |--------------------------------------------------------------------------
            */

            'tute' => $tute,
        ];
    }

    /**
     * Build schedule response.
     */
    protected function scheduleData(
        ClassSchedule $schedule
    ): array {
        $studentClass = $schedule->studentClass;

        $classCategoryFee = $schedule->classCategoryFee;

        $category = $classCategoryFee
            ? $classCategoryFee->category
            : null;

        return [
            'id' => $schedule->id,

            'class_date' => optional(
                $schedule->class_date
            )->format('Y-m-d'),

            'start_time' => $schedule->start_time,

            'end_time' => $schedule->end_time,

            'hall' => $this->hallName($schedule),

            'class' => [
                'id' => $studentClass
                    ? $studentClass->id
                    : null,

                'class_name' => $studentClass
                    ? $studentClass->class_name
                    : '',

                'teacher' => $this->teacherName(
                    $studentClass
                ),

                'subject' => $this->subjectName(
                    $studentClass
                ),

                'grade' => $this->gradeName(
                    $studentClass
                ),

                'category' => $category
                    ? $category->category_name
                    : '',
            ],
        ];
    }

    /**
     * Get hall name.
     */
    protected function hallName(
        ClassSchedule $schedule
    ): string {
        if (!$schedule->relationLoaded('hall')) {
            $schedule->load('hall');
        }

        return optional($schedule->hall)->hall_name ?? '';
    }

    /**
     * Get teacher name.
     */
    protected function teacherName($studentClass): string
    {
        if (!$studentClass) {
            return '';
        }

        if (!$studentClass->relationLoaded('teacher')) {
            $studentClass->load('teacher');
        }

        $teacher = $studentClass->teacher;

        if (!$teacher) {
            return '';
        }

        return $teacher->teacher_name
            ?? $teacher->name
            ?? '';
    }

    /**
     * Get subject name.
     */
    protected function subjectName($studentClass): string
    {
        if (!$studentClass) {
            return '';
        }

        if (!$studentClass->relationLoaded('subject')) {
            $studentClass->load('subject');
        }

        return optional(
            $studentClass->subject
        )->subject_name ?? '';
    }

    /**
     * Get grade name.
     */
    protected function gradeName($studentClass): string
    {
        if (!$studentClass) {
            return '';
        }

        if (!$studentClass->relationLoaded('grade')) {
            $studentClass->load('grade');
        }

        return optional(
            $studentClass->grade
        )->grade_name ?? '';
    }

    /**
     * Student data for response.
     */
    protected function studentData(
        Student $student
    ): array {
        return [
            'id' => $student->id,

            'student_code' =>
            $student->custom_id,

            'custom_id' =>
            $student->custom_id,

            'full_name' =>
            $student->full_name,

            'initial_name' =>
            $student->initial_name,

            'guardian_mobile' =>
            $student->guardian_mobile,

            'img_url' => $student->img_url
                ? asset(
                    'storage/' . $student->img_url
                )
                : '',

            'grade' => $student->grade
                ? [
                    'id' => $student->grade->id,
                    'grade_name' =>
                    $student->grade->grade_name,
                ]
                : [
                    'id' => 0,
                    'grade_name' => '',
                ],
        ];
    }

    /**
     * Standard error response.
     */
    protected function error(
        string $message,
        int $statusCode,
        ?array $data = null
    ): array {
        return [
            'success' => false,

            'message' => $message,

            'data' => $data,

            'status_code' => $statusCode,
        ];
    }
}
