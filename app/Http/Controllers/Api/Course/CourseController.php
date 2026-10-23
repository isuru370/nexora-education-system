<?php

namespace App\Http\Controllers\Api\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\CourseSession;
use App\Services\Branch\BranchAccessService;
use App\Services\Course\CourseApiPresenter;
use App\Services\Course\CourseAttendanceService;
use App\Services\Course\CourseEnrollmentService;
use App\Services\Course\CoursePaymentService;
use App\Services\Course\CourseStudentLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Staff app: courses (feature "courses").
 *
 * Course Attendance button:
 *   GET  /api/courses/sessions?date=           the day's class days
 *   POST /api/courses/sessions/{id}/attendance  scan a student (code)
 *   GET  /api/courses/sessions/{id}/report      present / absent + WhatsApp text
 *
 * Course Payment button (and enrolling):
 *   POST /api/courses/student                   scan a student: their courses + courses to join
 *   POST /api/courses/{course}/enroll           enroll (plan, optional special fee)
 *   POST /api/courses/enrollments/{id}/pay      take a payment -> receipt to print
 *   GET  /api/courses/payments/{id}/receipt     receipt again
 *
 *   GET  /api/courses                           active courses
 *
 * Errors: 404 for records of other branches, 422 with "errors" for rule breaks.
 */
class CourseController extends Controller
{
    public function __construct(
        private CourseApiPresenter $present,
        private BranchAccessService $branchAccess
    ) {
    }

    public function index(): JsonResponse
    {
        $courses = Course::query()
            ->with(['days.hall:id,hall_name', 'branch:id,name'])
            ->withCount(['enrollments as students_count' => fn ($q) => $q->where('status', 'active')])
            ->whereIn('branch_id', $this->branchIds())
            ->where('is_active', true)
            ->whereDate('end_date', '>=', today())
            ->orderBy('start_date')
            ->get()
            ->map(fn (Course $c) => $this->present->course($c) + ['students_count' => $c->students_count]);

        return $this->ok('Courses loaded.', ['courses' => $courses]);
    }

    public function sessions(Request $request, CourseAttendanceService $attendance): JsonResponse
    {
        $request->validate(['date' => ['nullable', 'date']]);
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : today();

        $sessions = $attendance->sessionsOn($date->toDateString(), $this->branchIds())
            ->load(['course' => fn ($q) => $q->withCount(['enrollments as students_count' => fn ($q) => $q->where('status', 'active')])])
            ->map(fn (CourseSession $s) => $this->present->session($s) + ['students_count' => $s->course->students_count]);

        return $this->ok('Class days loaded.', ['date' => $date->toDateString(), 'sessions' => $sessions]);
    }

    public function markAttendance(Request $request, CourseSession $courseSession, CourseAttendanceService $attendance, CourseStudentLookup $lookup): JsonResponse
    {
        $this->checkBranch($courseSession->branch_id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:150'],
            'mark_method' => ['nullable', Rule::in(['qr_mobile', 'manual_mobile'])],
        ]);

        $student = $lookup->find($data['code'], $this->branchIds(), 'code');
        $result = $attendance->mark($courseSession, $student, $data['mark_method'] ?? 'qr_mobile', $request->user()->id);
        $enrollment = $this->present->enrollment($result['enrollment']);

        return $this->ok(
            $result['already_marked'] ? 'Already marked for this class day.' : 'Attendance marked.',
            [
                'already_marked' => $result['already_marked'],
                'attended_at' => $result['attendance']->attended_at->format('Y-m-d H:i:s'),
                'student' => $this->present->student($student),
                'session' => $this->present->session($courseSession->fresh()),
                'fees' => $enrollment['fees'], // to warn about unpaid fees at the door
                'attendance' => $attendance->summary($result['enrollment']),
            ],
            $result['already_marked'] ? 200 : 201
        );
    }

    public function sessionReport(CourseSession $courseSession): JsonResponse
    {
        $this->checkBranch($courseSession->branch_id);

        return $this->ok('Class day report loaded.', $this->present->sessionReport($courseSession));
    }

    /** Scan a student: their courses (with fees) and the courses they can join. */
    public function student(Request $request, CourseStudentLookup $lookup): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:150']]);
        $student = $lookup->find($data['code'], $this->branchIds(), 'code');

        $enrollments = CourseEnrollment::query()
            ->with(['course', 'installments'])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereHas('course', fn ($q) => $q->whereIn('branch_id', $this->branchIds()))
            ->latest('id')
            ->get();

        $available = Course::query()
            ->with(['days.hall:id,hall_name', 'branch:id,name'])
            ->whereIn('branch_id', $this->branchIds())
            ->where('is_active', true)
            ->whereDate('end_date', '>=', today())
            ->whereNotIn('id', $enrollments->pluck('course_id'))
            ->orderBy('start_date')
            ->get();

        return $this->ok('Student loaded.', [
            'student' => $this->present->student($student),
            'enrollments' => $enrollments->map(fn ($e) => $this->present->enrollment($e))->values(),
            'available_courses' => $available->map(fn ($c) => $this->present->course($c))->values(),
            'payment_methods' => CoursePayment::METHODS,
        ]);
    }

    public function enroll(Request $request, Course $course, CourseEnrollmentService $enrollments, CourseStudentLookup $lookup): JsonResponse
    {
        $this->checkBranch($course->branch_id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:150'],
            'payment_plan' => ['required', 'in:full,installment'],
            'special_fee' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'special_fee_reason' => ['nullable', 'required_with:special_fee', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ], ['special_fee_reason.required_with' => 'Give a reason for the special fee.']);

        $student = $lookup->find($data['code'], $this->branchIds(), 'code');

        $enrollment = $enrollments->enroll(
            $course,
            $student,
            $data['payment_plan'],
            $request->user()->id,
            $data['note'] ?? null,
            isset($data['special_fee']) ? (float) $data['special_fee'] : null,
            $data['special_fee_reason'] ?? null
        );

        return $this->ok(
            ($student->initial_name ?: $student->custom_id) . ' enrolled in ' . $course->name . '.',
            ['student' => $this->present->student($student), 'enrollment' => $this->present->enrollment($enrollment)],
            201
        );
    }

    public function pay(Request $request, CourseEnrollment $courseEnrollment, CoursePaymentService $payments): JsonResponse
    {
        $this->checkBranch($courseEnrollment->branch_id);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', Rule::in(CoursePayment::METHODS)],
            'mark_method' => ['nullable', Rule::in(['qr_mobile', 'manual_mobile'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = $payments->pay(
            $courseEnrollment,
            (float) $data['amount'],
            $data['payment_method'] ?? 'cash',
            $data['mark_method'] ?? 'qr_mobile',
            $request->user()->id,
            $data['note'] ?? null
        );

        return $this->ok('Payment saved. Receipt ' . $payment->receipt_number . '.', [
            'receipt' => $this->present->receipt($payment),
            'enrollment' => $this->present->enrollment($courseEnrollment->fresh()),
        ], 201);
    }

    public function receipt(CoursePayment $coursePayment): JsonResponse
    {
        $this->checkBranch($coursePayment->branch_id);

        return $this->ok('Receipt loaded.', ['receipt' => $this->present->receipt($coursePayment)]);
    }

    /* ------------------------------------------------------------------ */

    protected function branchIds(): array
    {
        return $this->branchAccess->getBranchIds()->map(fn ($id) => (int) $id)->all();
    }

    protected function checkBranch($branchId): void
    {
        abort_unless(in_array((int) $branchId, $this->branchIds(), true), 404);
    }

    protected function ok(string $message, array $data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }
}
