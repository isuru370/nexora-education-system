<?php

namespace App\Http\Controllers\Web\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\Student;
use App\Services\Course\CourseAttendanceService;
use App\Services\Course\CourseFeeService;
use App\Services\Course\CourseStudentLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Front-desk pages: scan a student, then enroll / take a payment; and today's
 * class days for attendance.
 */
class CourseDeskController extends Controller
{
    use ChecksCourseBranch;

    public function __construct(private CourseStudentLookup $lookup, private CourseFeeService $fees)
    {
    }

    /** Scan a student -> their courses + courses they can join. */
    public function enroll(Request $request)
    {
        [$student, $error] = $this->student($request);

        $enrolled = collect();
        $available = collect();

        if ($student) {
            $enrolled = $this->enrollments($student);
            $taken = $enrolled->filter(fn ($row) => $row['enrollment']->isActive())->pluck('enrollment.course_id')->all();

            $available = Course::query()
                ->with('branch:id,name')
                ->whereIn('branch_id', $this->branchIds())
                ->where('is_active', true)
                ->whereDate('end_date', '>=', today())
                ->whereNotIn('id', $taken)
                ->orderBy('start_date')
                ->get();
        }

        return view('courses.desk.enroll', compact('student', 'error', 'enrolled', 'available'));
    }

    /** Scan a student -> each course with balance and a pay form. */
    public function payment(Request $request)
    {
        [$student, $error] = $this->student($request);

        $enrolled = $student
            ? $this->enrollments($student)->filter(fn ($row) => $row['enrollment']->isActive())->values()
            : collect();

        $recent = $student
            ? CoursePayment::with('course:id,name')->where('student_id', $student->id)->latest('paid_at')->limit(5)->get()
            : collect();

        return view('courses.desk.payment', [
            'student' => $student,
            'error' => $error,
            'enrolled' => $enrolled,
            'recent' => $recent,
            'methods' => CoursePayment::METHODS,
        ]);
    }

    /** A day's course class days, to open attendance. */
    public function attendance(Request $request, CourseAttendanceService $attendance)
    {
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : today();

        $sessions = $attendance->sessionsOn($date->toDateString(), $this->branchIds())
            ->load(['course' => fn ($q) => $q->withCount(['enrollments as students_count' => fn ($q) => $q->where('status', 'active')])]);

        return view('courses.desk.attendance', compact('sessions', 'date'));
    }

    /** @return array{0: ?Student, 1: ?string} */
    protected function student(Request $request): array
    {
        if (!$request->filled('code')) {
            return [null, null];
        }

        try {
            return [$this->lookup->find((string) $request->input('code'), $this->branchIds(), 'code'), null];
        } catch (ValidationException $e) {
            return [null, collect($e->errors())->flatten()->first()];
        }
    }

    protected function enrollments(Student $student)
    {
        return CourseEnrollment::query()
            ->with(['course:id,name,code,branch_id,start_date,end_date,installment_count', 'installments'])
            ->where('student_id', $student->id)
            ->whereHas('course', fn ($q) => $q->whereIn('branch_id', $this->branchIds()))
            ->latest('id')
            ->get()
            ->map(fn (CourseEnrollment $e) => ['enrollment' => $e, 'fees' => $this->fees->summary($e)]);
    }
}
