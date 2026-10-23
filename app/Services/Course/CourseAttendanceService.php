<?php

namespace App\Services\Course;

use App\Models\CourseAttendance;
use App\Models\CourseEnrollment;
use App\Models\CourseSession;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CourseAttendanceService
{
    public function __construct(private CourseFeeService $fees)
    {
    }

    /**
     * Mark a student present for a course session.
     *
     * @return array{attendance: CourseAttendance, enrollment: CourseEnrollment, already_marked: bool}
     */
    public function mark(CourseSession $session, Student $student, string $markMethod, ?int $userId = null): array
    {
        if ($session->isCancelled()) {
            throw ValidationException::withMessages(['session' => 'This session is cancelled.']);
        }

        if ($session->session_date->toDateString() > now()->toDateString()) {
            throw ValidationException::withMessages(['session' => 'This session has not started yet.']);
        }

        $enrollment = CourseEnrollment::query()
            ->where('course_id', $session->course_id)
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->first();

        if (!$enrollment) {
            throw ValidationException::withMessages([
                'student' => ($student->initial_name ?: $student->custom_id) . ' is not enrolled in this course.',
            ]);
        }

        $existing = CourseAttendance::query()
            ->where('course_session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            return ['attendance' => $existing, 'enrollment' => $enrollment, 'already_marked' => true];
        }

        $attendance = CourseAttendance::create([
            'course_session_id' => $session->id,
            'course_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'branch_id' => $session->branch_id,
            'attended_at' => now(),
            'mark_method' => $markMethod,
            'marked_by' => $userId,
        ]);

        return ['attendance' => $attendance, 'enrollment' => $enrollment, 'already_marked' => false];
    }

    public function remove(CourseAttendance $attendance): void
    {
        $attendance->delete();
    }

    /**
     * Sessions on a day for the given branches, with the course.
     *
     * @param  int[]  $branchIds
     */
    public function sessionsOn(string $date, array $branchIds): Collection
    {
        return CourseSession::query()
            ->with(['course:id,name,code,branch_id', 'hall:id,hall_name'])
            ->withCount('attendances')
            ->whereIn('branch_id', $branchIds)
            ->whereDate('session_date', $date)
            ->whereHas('course', fn ($q) => $q->where('is_active', true))
            ->orderBy('start_time')
            ->get();
    }

    /** Attended / held sessions for an enrollment (cancelled and future ones not counted). */
    public function summary(CourseEnrollment $enrollment): array
    {
        $held = CourseSession::query()
            ->where('course_id', $enrollment->course_id)
            ->where('status', 'scheduled')
            ->whereDate('session_date', '<=', now()->toDateString())
            ->count();

        $attended = $enrollment->attendances()->count();

        return [
            'attended' => $attended,
            'held' => $held,
            'percentage' => $held > 0 ? (int) round($attended / $held * 100) : 0,
        ];
    }
}
