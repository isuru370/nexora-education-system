<?php

namespace App\Http\Controllers\Web\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAttendance;
use App\Models\CourseEnrollment;
use App\Models\CourseSession;
use App\Models\Student;
use App\Services\Course\CourseAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CourseSessionController extends Controller
{
    use ChecksCourseBranch;

    public function __construct(private CourseAttendanceService $attendance)
    {
    }

    /** One class day: who came, who did not, and a box to mark by student ID / QR. */
    public function show(Course $course, CourseSession $courseSession)
    {
        $this->checkCourse($course, (int) $courseSession->course_id);

        $marked = $courseSession->attendances()->with(['student:id,custom_id,initial_name', 'markedBy:id,name'])->get()->keyBy('student_id');

        $students = CourseEnrollment::query()
            ->with('student:id,custom_id,initial_name,mobile')
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->get()
            ->sortBy(fn ($e) => $e->student->initial_name ?? '')
            ->values();

        return view('courses.session', [
            'course' => $course,
            'session' => $courseSession->load('hall:id,hall_name'),
            'students' => $students,
            'marked' => $marked,
            'canMark' => !$courseSession->isCancelled() && $courseSession->session_date->lte(today()),
        ]);
    }

    public function mark(Request $request, Course $course, CourseSession $courseSession)
    {
        $this->checkCourse($course, (int) $courseSession->course_id);

        $data = $request->validate([
            'student_code' => ['required_without:student_id', 'nullable', 'string', 'max:150'],
            'student_id' => ['nullable', 'integer'],
        ]);

        $student = !empty($data['student_id'])
            ? Student::find($data['student_id'])
            : Student::query()
                ->where(fn ($q) => $q->where('custom_id', trim($data['student_code']))->orWhere('temporary_qr_code', trim($data['student_code'])))
                ->first();

        if (!$student) {
            throw ValidationException::withMessages(['student_code' => 'No student found for this ID / QR.']);
        }

        $result = $this->attendance->mark(
            $courseSession,
            $student,
            !empty($data['student_id']) ? 'manual_web' : 'qr_web',
            auth()->id()
        );

        $name = $student->initial_name ?: $student->custom_id;

        return back()->with(
            $result['already_marked'] ? 'warning' : 'success',
            $result['already_marked'] ? "{$name} was already marked." : "{$name} marked present."
        );
    }

    public function unmark(Course $course, CourseSession $courseSession, CourseAttendance $courseAttendance)
    {
        $this->checkCourse($course, (int) $courseSession->course_id);
        abort_unless((int) $courseAttendance->course_session_id === (int) $courseSession->id, 404);

        $this->attendance->remove($courseAttendance);

        return back()->with('success', 'Attendance removed.');
    }

    /** Cancel a class day (e.g. holiday) or bring it back. */
    public function status(Request $request, Course $course, CourseSession $courseSession)
    {
        $this->checkCourse($course, (int) $courseSession->course_id);

        $data = $request->validate([
            'status' => ['required', 'in:scheduled,cancelled'],
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['status'] === 'cancelled' && $courseSession->attendances()->exists()) {
            throw ValidationException::withMessages(['status' => 'Students are already marked for this day. Remove them first.']);
        }

        $courseSession->update([
            'status' => $data['status'],
            'cancel_reason' => $data['status'] === 'cancelled' ? ($data['cancel_reason'] ?? null) : null,
        ]);

        return back()->with('success', $data['status'] === 'cancelled' ? 'Class day cancelled.' : 'Class day restored.');
    }
}
