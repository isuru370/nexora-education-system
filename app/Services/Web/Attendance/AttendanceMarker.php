<?php

namespace App\Services\Web\Attendance;

use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentClassEnrollment;
use App\Services\Branch\BranchAccessService;
use Illuminate\Support\Facades\DB;

class AttendanceMarker
{
    /**
     * Allowed attendance marking methods.
     */
    /** Set by class-schedules:complete-finished on a class nobody attended. */
    public const AUTO_CANCEL_REASON = 'Class ended without attendance.';

    protected $allowedMethods = [
        'qr_mobile',
        'qr_web',
        'manual_mobile',
        'manual_web',
    ];

    /**
     * Branch access checker.
     *
     * @var BranchAccessService
     */
    protected $branchAccess;

    /**
     * AttendanceMarker constructor.
     */
    public function __construct(
        BranchAccessService $branchAccess
    ) {
        $this->branchAccess = $branchAccess;
    }

    /**
     * Mark attendance.
     */
    public function mark(
        Student $student,
        StudentClassEnrollment $enrollment,
        ClassSchedule $schedule,
        string $markMethod,
        ?\Carbon\CarbonInterface $attendedAt = null,
        ?string $localUuid = null
    ): array {

        /*
        |--------------------------------------------------------------------------
        | 1. Validate mark method
        |--------------------------------------------------------------------------
        */

        if (!in_array($markMethod, $this->allowedMethods, true)) {
            return $this->failed(
                'Invalid attendance mark method.',
                422
            );
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
            $markMethod,
            $attendedAt,
            $localUuid
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
                return $this->failed(
                    'Class schedule not found.',
                    404
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 3a. Resolve branch of the class
            |--------------------------------------------------------------------------
            |
            | student_attendances.branch_id is required (NOT NULL).
            | It always comes from the class, never from user input:
            |
            | 1. class_schedules.branch_id
            | 2. student_classes.branch_id (fallback)
            |
            */

            $branchId = $lockedSchedule->branch_id
                ?? optional($lockedSchedule->studentClass)->branch_id;

            if (empty($branchId)) {
                return $this->failed(
                    'Branch not found for this class.',
                    422
                );
            }

            $branchId = (int) $branchId;

            /*
            |--------------------------------------------------------------------------
            | 3b. Check the user can work in this branch
            |--------------------------------------------------------------------------
            |
            | SUPER_ADMIN / ADMIN : all active branches
            | BRANCH_ADMIN / USER : only branches assigned to the user
            |
            */

            if (!$this->branchAccess->canAccessBranch($branchId)) {
                return $this->failed(
                    'You do not have access to mark attendance for this branch.',
                    403
                );
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
                    'status_code' => 200,
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
                'branch_id' => $branchId,
                'student_id' => $student->id,
                'class_schedule_id' => $lockedSchedule->id,
                'student_class_enrollment_id' => $enrollment->id,
                // offline (staff app): the time it was scanned, not the sync time
                'attended_at' => $attendedAt ?? now(),
                'local_uuid' => $localUuid,
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
            } elseif (
                // offline attendance synced after the class was closed as
                // "ended without attendance" (class-schedules:complete-finished):
                // it did have students, so it was held
                $lockedSchedule->status === 'cancelled'
                && $lockedSchedule->cancel_reason === self::AUTO_CANCEL_REASON
            ) {
                $lockedSchedule->status = 'completed';
                $lockedSchedule->cancel_reason = null;
                $lockedSchedule->cancelled_at = null;
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
                'status_code' => 201,
            ];
        });
    }

    /**
     * Failed mark result.
     */
    protected function failed(
        string $message,
        int $statusCode
    ): array {
        return [
            'marked' => false,
            'already_marked' => false,
            'attendance' => null,
            'message' => $message,
            'status_code' => $statusCode,
        ];
    }
}