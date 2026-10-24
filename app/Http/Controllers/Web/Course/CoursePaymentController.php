<?php

namespace App\Http\Controllers\Web\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Services\Course\CourseFeeService;
use App\Services\Course\CoursePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class CoursePaymentController extends Controller
{
    use ChecksCourseBranch;

    public function __construct(private CoursePaymentService $payments)
    {
    }

    /** All course payments, by date (for the day's / month's collection). */
    public function index(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : today()->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : today()->endOfDay();

        $query = CoursePayment::query()
            ->with(['course:id,name', 'student:id,custom_id,initial_name', 'takenBy:id,name'])
            ->whereIn('branch_id', $this->branchIds())
            ->whereBetween('paid_at', [$from, $to])
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', (int) $request->input('course_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')));

        $totals = (clone $query)->where('status', 'completed')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        $payments = $query->latest('paid_at')->paginate(25)->withQueryString();
        $courses = Course::whereIn('branch_id', $this->branchIds())->orderBy('name')->get(['id', 'name']);

        return view('courses.payments', compact('payments', 'totals', 'courses', 'from', 'to'));
    }

    public function store(Request $request, Course $course, CourseEnrollment $courseEnrollment, CourseFeeService $fees)
    {
        $this->checkCourse($course, (int) $courseEnrollment->course_id);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(CoursePayment::METHODS)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = $this->payments->pay(
            $courseEnrollment,
            (float) $data['amount'],
            $data['payment_method'],
            'manual_web',
            auth()->id(),
            $data['note'] ?? null
        );

        return redirect()
            ->route('course-payments.receipt', $payment)
            ->with('success', 'Payment saved. Receipt ' . $payment->receipt_number . '.');
    }

    public function receipt(CoursePayment $coursePayment, CourseFeeService $fees)
    {
        $this->checkBranch((int) $coursePayment->branch_id);

        $coursePayment->load(['course', 'student', 'branch', 'takenBy:id,name', 'enrollment']);

        return view('courses.receipt', [
            'payment' => $coursePayment,
            'fees' => $fees->summary($coursePayment->enrollment),
        ]);
    }

    public function cancel(Request $request, CoursePayment $coursePayment)
    {
        $this->checkBranch((int) $coursePayment->branch_id);

        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:255']]);

        $this->payments->cancel($coursePayment, $data['cancel_reason'], auth()->id());

        return back()->with('success', 'Payment ' . $coursePayment->receipt_number . ' cancelled.');
    }
}
