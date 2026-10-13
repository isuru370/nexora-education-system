<?php

namespace App\Services\Web\Attendance;

use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use Illuminate\Support\Collection;

/**
 * Plain data object passed between attendance services.
 *
 * Not a model and not stored in the database.
 */
class AttendanceContext
{
    /**
     * Student
     *
     * @var Student
     */
    public Student $student;

    /**
     * Student Enrollment
     *
     * @var StudentClassEnrollment
     */
    public StudentClassEnrollment $enrollment;

    /**
     * Current Attendance Schedule
     *
     * @var ClassSchedule
     */
    public ClassSchedule $schedule;

    /**
     * Today's Schedules
     *
     * @var Collection
     */
    public Collection $todaySchedules;

    /**
     * Branch of the current class.
     *
     * Taken from the schedule (class_schedules.branch_id).
     * Falls back to the enrollment's branch if the schedule
     * was loaded without the branch_id column.
     *
     * @var int|null
     */
    public ?int $branchId;


    /**
     * Create Context
     *
     * @param Student $student
     * @param StudentClassEnrollment $enrollment
     * @param ClassSchedule $schedule
     * @param Collection $todaySchedules
     */
    public function __construct(
        Student $student,
        StudentClassEnrollment $enrollment,
        ClassSchedule $schedule,
        Collection $todaySchedules,
    ) {
        $this->student = $student;
        $this->enrollment = $enrollment;
        $this->schedule = $schedule;
        $this->todaySchedules = $todaySchedules;

        $branchId = $schedule->branch_id ?? $enrollment->branch_id;

        $this->branchId = $branchId !== null
            ? (int) $branchId
            : null;
    }
}