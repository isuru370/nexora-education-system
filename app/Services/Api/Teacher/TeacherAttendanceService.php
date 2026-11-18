<?php

namespace App\Services\Api\Teacher;

use App\Models\ClassSchedule;
use App\Models\CourseAttendance;
use App\Models\CourseEnrollment;
use App\Models\CourseSession;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentClassEnrollment;
use App\Models\Teacher;
use App\Services\Api\Student\StudentQRService;
use App\Services\Course\CourseAttendanceService;
use App\Services\Notification\AttendanceNotificationService;
use App\Services\Web\Attendance\AttendanceMarker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * A teacher marks attendance in their own classes and courses - today's
 * class days only - by QR scan or from the student list.
 * (Settings > Teacher App > attendance)
 */
class TeacherAttendanceService
{
    public function __construct(
        private AttendanceMarker $marker,
        private AttendanceNotificationService $notifications,
        private CourseAttendanceService $courseAttendance,
    ) {}

    /** Today's class days of this teacher (classes + courses). */
    public function today(Teacher $teacher): array
    {
        $today = now()->toDateString();

        $classes = ClassSchedule::query()
            ->whereHas('studentClass', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->whereDate('class_date', $today)
            ->where('is_active', true)
            ->where('status', '!=', 'cancelled')
            ->with(['studentClass:id,class_name', 'classCategoryFee.category:id,category_name', 'hall:id,hall_name'])
            ->withCount('studentAttendances')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSchedule $s) => [
                'type' => 'class',
                'id' => $s->id,
                'title' => $s->studentClass->class_name ?? 'Class',
                'category' => $s->classCategoryFee->category->category_name ?? null,
                'hall' => $s->hall->hall_name ?? null,
                'start_time' => substr((string) $s->start_time, 0, 5),
                'end_time' => substr((string) $s->end_time, 0, 5),
                'status' => $s->status,
                'present' => (int) $s->student_attendances_count,
                'enrolled' => $this->classEnrollments($s)->count(),
            ]);

        $courses = collect();

        if (feature('courses')) {
            $courses = CourseSession::query()
                ->whereHas('course.courseTeachers', fn ($q) => $q->where('teacher_id', $teacher->id))
                ->whereDate('session_date', $today)
                ->where('status', '!=', 'cancelled')
                ->with(['course:id,name', 'hall:id,hall_name'])
                ->orderBy('start_time')
                ->get()
                ->map(fn (CourseSession $s) => [
                    'type' => 'course',
                    'id' => $s->id,
                    'title' => $s->course->name ?? 'Course',
                    'category' => 'Course',
                    'hall' => $s->hall->hall_name ?? null,
                    'start_time' => substr((string) $s->start_time, 0, 5),
                    'end_time' => substr((string) $s->end_time, 0, 5),
                    'status' => $s->status,
                    'present' => CourseAttendance::query()->where('course_session_id', $s->id)->count(),
                    'enrolled' => CourseEnrollment::query()->where('course_id', $s->course_id)->where('status', 'active')->count(),
                ]);
        }

        return $classes->concat($courses)->sortBy('start_time')->values()->all();
    }

    /** Students of one class day with present / absent. */
    public function students(Teacher $teacher, string $type, int $id): array
    {
        if ($type === 'course') {
            $session = $this->courseSession($teacher, $id);
            $present = CourseAttendance::query()->where('course_session_id', $session->id)->pluck('attended_at', 'student_id');

            $rows = CourseEnrollment::query()
                ->where('course_id', $session->course_id)
                ->where('status', 'active')
                ->with('student:id,custom_id,temporary_qr_code,initial_name,full_name,img_url')
                ->get()
                ->pluck('student');
        } else {
            $schedule = $this->classSchedule($teacher, $id);
            $present = StudentAttendance::query()->where('class_schedule_id', $schedule->id)->pluck('attended_at', 'student_id');

            $rows = $this->classEnrollments($schedule)
                ->load('student:id,custom_id,temporary_qr_code,initial_name,full_name,img_url')
                ->pluck('student');
        }

        return $rows->filter()
            ->sortBy(fn ($s) => mb_strtolower((string) ($s->initial_name ?: $s->full_name)))
            ->map(fn (Student $s) => [
                'student_id' => $s->id,
                'student_code' => $s->custom_id ?: $s->temporary_qr_code,
                'name' => $s->initial_name ?: $s->full_name,
                'present' => $present->has($s->id),
                'attended_at' => $present->has($s->id) && $present[$s->id] ? \Illuminate\Support\Carbon::parse($present[$s->id])->toISOString() : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Mark the ticked students present.
     *
     * @param  array<int>  $studentIds
     * @return array{marked: int, already: int, failed: array<int, string>}
     */
    public function markList(Teacher $teacher, string $type, int $id, array $studentIds): array
    {
        $result = ['marked' => 0, 'already' => 0, 'failed' => []];

        foreach (array_unique(array_map('intval', $studentIds)) as $studentId) {
            $student = Student::query()->find($studentId);

            if (!$student) {
                $result['failed'][$studentId] = 'Student not found.';
                continue;
            }

            $one = $this->markOne($teacher, $type, $id, $student, 'manual_mobile');
            if ($one['ok']) {
                $one['already'] ? $result['already']++ : $result['marked']++;
            } else {
                $result['failed'][$studentId] = $one['message'];
            }
        }

        return $result;
    }

    /** QR scan for one class day. */
    public function scan(Teacher $teacher, string $type, int $id, string $code): array
    {
        $qr = StudentQRService::read($code);

        if (!($qr['success'] ?? false)) {
            return ['ok' => false, 'already' => false, 'message' => $qr['message'] ?? 'QR code invalid', 'student' => null];
        }

        $student = Student::query()->findOrFail($qr['student_id']);

        return $this->markOne($teacher, $type, $id, $student, 'qr_mobile');
    }

    /** @return array{ok: bool, already: bool, message: string, student: ?array} */
    protected function markOne(Teacher $teacher, string $type, int $id, Student $student, string $method): array
    {
        $studentRow = ['student_id' => $student->id, 'name' => $student->initial_name ?: $student->full_name, 'student_code' => $student->custom_id ?: $student->temporary_qr_code];

        if ($type === 'course') {
            $session = $this->courseSession($teacher, $id);

            try {
                $done = $this->courseAttendance->mark($session, $student, $method, auth()->id());
            } catch (ValidationException $e) {
                return ['ok' => false, 'already' => false, 'message' => collect($e->errors())->flatten()->first(), 'student' => $studentRow];
            }

            return ['ok' => true, 'already' => (bool) ($done['already_marked'] ?? false), 'message' => !empty($done['already_marked']) ? 'Already marked.' : 'Marked present.', 'student' => $studentRow];
        }

        $schedule = $this->classSchedule($teacher, $id);

        $enrollment = $this->classEnrollments($schedule)->firstWhere('student_id', $student->id);

        if (!$enrollment) {
            return ['ok' => false, 'already' => false, 'message' => $studentRow['name'] . ' is not in this class.', 'student' => $studentRow];
        }

        $done = $this->marker->mark($student, $enrollment, $schedule, $method);

        if (!empty($done['marked'])) {
            try {
                $this->notifications->sendSuccess($student, $done['attendance'], $enrollment);
            } catch (Throwable $e) {
                Log::warning('Teacher attendance notification failed', ['error' => $e->getMessage()]);
            }
        }

        $ok = !empty($done['marked']) || !empty($done['already_marked']);

        return ['ok' => $ok, 'already' => !empty($done['already_marked']), 'message' => $done['message'] ?? '', 'student' => $studentRow];
    }

    /** A class day of this teacher, today, not cancelled. */
    public function classSchedule(Teacher $teacher, int $id): ClassSchedule
    {
        $schedule = ClassSchedule::query()
            ->whereKey($id)
            ->whereHas('studentClass', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->first();

        if (!$schedule) {
            abort(404, 'Class day not found.');
        }

        if (!$schedule->class_date || !$schedule->class_date->isToday() || $schedule->status === 'cancelled') {
            throw ValidationException::withMessages(['schedule' => 'Attendance can be marked only for today\'s classes.']);
        }

        return $schedule;
    }

    public function courseSession(Teacher $teacher, int $id): CourseSession
    {
        abort_unless(feature('courses'), 404);

        $session = CourseSession::query()
            ->whereKey($id)
            ->whereHas('course.courseTeachers', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->first();

        if (!$session) {
            abort(404, 'Class day not found.');
        }

        if (!$session->session_date->isToday()) {
            throw ValidationException::withMessages(['schedule' => 'Attendance can be marked only for today\'s classes.']);
        }

        return $session;
    }

    /** Active enrollments of the class day's class + category. */
    protected function classEnrollments(ClassSchedule $schedule): Collection
    {
        return StudentClassEnrollment::query()
            ->where('student_class_id', $schedule->student_class_id)
            ->where('class_category_fee_id', $schedule->class_category_fee_id)
            ->where('is_active', true)
            ->whereHas('student', fn ($q) => $q->where('is_active', true))
            ->get();
    }
}
