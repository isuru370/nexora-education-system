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
            'special_fee' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'special_fee_reason' => ['nullable', 'required_with:special_fee', 'string', 'max:255'],
        ], [
            'special_fee_reason.required_with' => 'Give a reason for the special fee.',
        ]);

        $student = app(CourseStudentLookup::class)->find($data['student_code'], $this->branchIds());

        $enrollment = $this->enrollments->enroll(
            $course,
            $student,
            $data['payment_plan'],
            auth()->id(),
            $data['note'] ?? null,
            isset($data['special_fee']) && $data['special_fee'] !== '' ? (float) $data['special_fee'] : null,
            $data['special_fee_reason'] ?? null
        );

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

    /** Admin: a new fee for this student (what is paid stays). */
    public function changeFee(Request $request, Course $course, CourseEnrollment $courseEnrollment)
    {
        $this->checkCourse($course, (int) $courseEnrollment->course_id);

        $data = $request->validate([
            'fee' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'fee_reason' => ['required', 'string', 'max:255'],
        ]);

        $this->enrollments->changeFee($courseEnrollment, (float) $data['fee'], $data['fee_reason']);

        return back()->with('success', 'Fee changed to Rs. ' . number_format((float) $data['fee'], 2) . '.');
    }

    public function cancel(Course $course, CourseEnrollment $courseEnrollment)
    {
        $this->checkCourse($course, (int) $courseEnrollment->course_id);

        $this->enrollments->cancel($courseEnrollment);

        return redirect()->route('courses.show', $course)->with('success', 'Enrollment cancelled.');
    }
}
