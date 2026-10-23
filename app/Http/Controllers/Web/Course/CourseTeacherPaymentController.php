<?php

namespace App\Http\Controllers\Web\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\CourseTeacherPayout;
use App\Models\Teacher;
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

    /** Every course teacher with their totals. */
    public function index()
    {
        $rows = $this->earnings->summary($this->branchIds());

        $teachers = $rows->groupBy(fn ($row) => $row['teacher']->id)->map(fn ($group) => [
            'teacher' => $group->first()['teacher'],
            'courses' => $group->count(),
            'earned' => round($group->sum('earned'), 2),
            'paid' => round($group->sum('paid'), 2),
            'balance' => round($group->sum('balance'), 2),
        ])->sortBy(fn ($row) => $row['teacher']->full_name)->values();

        return view('courses.teacher-payments', [
            'teachers' => $teachers,
            'totals' => [
                'earned' => round($rows->sum('earned'), 2),
                'paid' => round($rows->sum('paid'), 2),
                'balance' => round($rows->sum('balance'), 2),
            ],
        ]);
    }

    /** One teacher: each course's earned / paid / balance, pay, history. */
    public function show(Teacher $teacher)
    {
        $rows = $this->earnings->summary($this->branchIds())
            ->filter(fn ($row) => $row['teacher']->id === $teacher->id)
            ->values();

        abort_if($rows->isEmpty(), 404);

        $payouts = CourseTeacherPayout::query()
            ->with(['course:id,name', 'paidBy:id,name'])
            ->where('teacher_id', $teacher->id)
            ->whereIn('branch_id', $this->branchIds())
            ->latest('id')
            ->get();

        return view('courses.teacher-payment', [
            'teacher' => $teacher,
            'rows' => $rows,
            'payouts' => $payouts,
            'methods' => CoursePayment::METHODS,
            'totals' => [
                'earned' => round($rows->sum('earned'), 2),
                'paid' => round($rows->sum('paid'), 2),
                'balance' => round($rows->sum('balance'), 2),
            ],
        ]);
    }

    /** Printable slip for a teacher payment. */
    public function slip(CourseTeacherPayout $courseTeacherPayout)
    {
        $this->checkBranch((int) $courseTeacherPayout->branch_id);

        $courseTeacherPayout->load(['teacher', 'course', 'paidBy:id,name']);
        $row = $this->earnings->forCourse($courseTeacherPayout->course)
            ->first(fn ($r) => $r['teacher']->id === $courseTeacherPayout->teacher_id);

        return view('courses.payout-slip', ['payout' => $courseTeacherPayout, 'row' => $row]);
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

        return back()->with('success', 'Teacher payment saved.');
    }

    public function cancel(Course $course, CourseTeacherPayout $courseTeacherPayout)
    {
        $this->checkCourse($course, (int) $courseTeacherPayout->course_id);

        $this->earnings->cancelPayout($courseTeacherPayout);

        return back()->with('success', 'Teacher payment cancelled.');
    }
}
