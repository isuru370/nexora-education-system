<?php

namespace App\Services\NewAttendance;

use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentClassEnrollment;
use Illuminate\Support\Facades\DB;

class AttendanceMarker
{
    /**
     * Allowed attendance marking methods.
     */
    protected $allowedMethods = [
        'qr_mobile',
        'qr_web',
        'manual_mobile',
        'manual_web',
    ];

    /**
     * Mark attendance.
     */
    public function mark(
        Student $student,
        StudentClassEnrollment $enrollment,
        ClassSchedule $schedule,
        string $markMethod
    ): array {

        /*
        |--------------------------------------------------------------------------
        | 1. Validate mark method
        |--------------------------------------------------------------------------
        */

        if (!in_array($markMethod, $this->allowedMethods, true)) {
            return [
                'marked' => false,
                'already_marked' => false,
                'attendance' => null,
                'message' => 'Invalid attendance mark method.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Mark attendance inside transaction
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $student,
            $enrollment,
            $schedule,
            $markMethod
        ) {

            /*
            |--------------------------------------------------------------------------
            | 3. Lock the schedule
            |--------------------------------------------------------------------------
            |
            | This prevents two students being scanned at exactly the same
            | moment from both trying to update the schedule status.
            |
            */

            $lockedSchedule = ClassSchedule::query()
                ->where('id', $schedule->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedSchedule) {
                return [
                    'marked' => false,
                    'already_marked' => false,
                    'attendance' => null,
                    'message' => 'Class schedule not found.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Check duplicate attendance
            |--------------------------------------------------------------------------
            */

            $existingAttendance = StudentAttendance::query()
                ->where('student_id', $student->id)
                ->where('class_schedule_id', $lockedSchedule->id)
                ->first();

            if ($existingAttendance) {
                return [
                    'marked' => false,
                    'already_marked' => true,
                    'attendance' => $existingAttendance,
                    'message' => 'Attendance already marked for this class.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Build attendance note
            |--------------------------------------------------------------------------
            */

            $studentName = $student->initial_name;

            if (empty($studentName)) {
                $studentName = $student->full_name;
            }

            if (empty($studentName)) {
                $studentName = '-';
            }

            $gradeName = optional($student->grade)->grade_name;

            if (empty($gradeName)) {
                $gradeName = '-';
            }

            $className = optional(
                $lockedSchedule->studentClass
            )->class_name;

            if (empty($className)) {
                $className = '-';
            }

            $category = optional(
                optional($lockedSchedule->classCategoryFee)->category
            );

            $categoryName = $category->category_name;

            if (empty($categoryName)) {
                $categoryName = '-';
            }

            $note = sprintf(
                'Student: %s | Grade: %s | Class: %s | Category: %s',
                $studentName,
                $gradeName,
                $className,
                $categoryName
            );

            /*
            |--------------------------------------------------------------------------
            | 6. Create attendance
            |--------------------------------------------------------------------------
            */

            $attendance = StudentAttendance::create([
                'student_id' => $student->id,
                'class_schedule_id' => $lockedSchedule->id,
                'student_class_enrollment_id' => $enrollment->id,
                'attended_at' => now(),
                'mark_method' => $markMethod,
                'marked_by' => auth()->id(),
                'is_synced' => true,
                'note' => $note,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 7. Change class status to ONGOING
            |--------------------------------------------------------------------------
            |
            | Only the first successful attendance should start the class.
            |
            | scheduled -> ongoing
            |
            | If the class is already ongoing, leave it as it is.
            |
            */

            if ($lockedSchedule->status === 'scheduled') {
                $lockedSchedule->status = 'ongoing';
                $lockedSchedule->save();
            }

            /*
            |--------------------------------------------------------------------------
            | 8. Return result
            |--------------------------------------------------------------------------
            */

            return [
                'marked' => true,
                'already_marked' => false,
                'attendance' => $attendance,
                'message' => 'Attendance marked successfully.',
            ];
        });
    }
}