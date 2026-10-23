<?php

namespace App\Http\Controllers\Web\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\Student;
use App\Services\Course\CourseAttendanceService;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CourseFeeService;
use App\Services\Course\CourseStudentLookup;
use Illuminate\Http\Request;

class CourseEnrollmentController extends Controller
{
    use ChecksCourseBranch;

    public function __construct(private CourseEnrollmentService $enrollments)
    {
    }

    public function store(Request $request, Course $course)
    {
        $this->checkCourse($course);

        $data = $request->validate([
            'student_code' => ['required', 'string', 'max:150'],
            'payment_plan' => ['required', 'in:full,installment'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $student = app(CourseStudentLookup::class)->find($data['student_code'], $this->branchIds());

        $enrollment = $this->enrollments->enroll($course, $student, $data['payment_plan'], auth()->id(), $data['note'] ?? null);

        return redirect()
            ->route('courses.enrollments.show', [$course, $enrollment])
            ->with('success', ($student->initial_name ?: $student->custom_id) . ' enrolled in ' . $course->name . '.');
    }

    public function show(Course $course, CourseEnrollment $courseEnrollment, CourseFeeService $fees, CourseAttendanceService $attendance)
    {
        $this->checkCourse($course, (int) $courseEnrollment->course_id);

        $courseEnrollment->load(['student', 'installments']);

        return view('courses.enrollment', [
            'course' => $course,
            'enrollment' => $courseEnrollment,
            'fees' => $fees->summary($courseEnrollment),
            'attendance' => $attendance->summary($courseEnrollment),
            'payments' => CoursePayment::with('takenBy:id,name')->where('course_enrollment_id', $courseEnrollment->id)->latest('paid_at')->get(),
            'attended' => $courseEnrollment->attendances()->with('session:id,session_date')->latest('attended_at')->get(),
            'methods' => CoursePayment::METHODS,
        ]);
    }

    public function cancel(Course $course, CourseEnrollment $courseEnrollment)
    {
        $this->checkCourse($course, (int) $courseEnrollment->course_id);

        $this->enrollments->cancel($courseEnrollment);

        return redirect()->route('courses.show', $course)->with('success', 'Enrollment cancelled.');
    }
}
