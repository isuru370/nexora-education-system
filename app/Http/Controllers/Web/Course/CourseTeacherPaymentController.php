<?php

namespace App\Http\Controllers\Web\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\CourseTeacherPayout;
use App\Services\Course\CourseTeacherEarningService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Course teachers' earnings and payouts (not part of teacher salaries). */
class CourseTeacherPaymentController extends Controller
{
    use ChecksCourseBranch;

    public function __construct(private CourseTeacherEarningService $earnings)
    {
    }

    public function index()
    {
        $rows = $this->earnings->summary($this->branchIds());

        return view('courses.teacher-payments', [
            'rows' => $rows,
            'totals' => [
                'earned' => round($rows->sum('earned'), 2),
                'paid' => round($rows->sum('paid'), 2),
                'balance' => round($rows->sum('balance'), 2),
            ],
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $this->checkCourse($course);

        $data = $request->validate([
            'teacher_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(CoursePayment::METHODS)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->earnings->payout($course, (int) $data['teacher_id'], (float) $data['amount'], $data['payment_method'], $data['note'] ?? null, auth()->id());

        return redirect()->to(route('courses.show', $course) . '#teachers')->with('success', 'Teacher payment saved.');
    }

    public function cancel(Course $course, CourseTeacherPayout $courseTeacherPayout)
    {
        $this->checkCourse($course, (int) $courseTeacherPayout->course_id);

        $this->earnings->cancelPayout($courseTeacherPayout);

        return redirect()->to(route('courses.show', $course) . '#teachers')->with('success', 'Teacher payment cancelled.');
    }
}
