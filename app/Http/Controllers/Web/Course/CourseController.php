<?php

namespace App\Http\Controllers\Web\Course;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Course\SaveCourseRequest;
use App\Models\Branch;
use App\Models\ClassHall;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\Teacher;
use App\Services\Course\CourseFeeService;
use App\Services\Course\CourseService;
use App\Services\Course\CourseTeacherEarningService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ChecksCourseBranch;

    public function __construct(private CourseService $courses)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $courses = Course::query()
            ->with(['branch:id,name', 'teachers:id,full_name'])
            ->withCount(['enrollments as students_count' => fn ($q) => $q->where('status', 'active')])
            ->withSum(['payments as collected' => fn ($q) => $q->where('status', 'completed')], 'amount')
            ->whereIn('branch_id', $this->branchIds())
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', (int) $request->input('branch_id')))
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::whereIn('id', $this->branchIds())->orderBy('name')->get(['id', 'name']);

        return view('courses.index', compact('courses', 'branches'));
    }

    public function create()
    {
        return view('courses.create', $this->formData());
    }

    public function store(SaveCourseRequest $request)
    {
        $this->checkBranch((int) $request->input('branch_id'));

        $course = $this->courses->create($request->courseData(), $request->teachers(), auth()->id());

        return redirect()->route('courses.show', $course)->with('success', 'Course created. Class days were added to the schedule.');
    }

    public function show(Course $course, CourseFeeService $fees, CourseTeacherEarningService $earnings)
    {
        $this->checkCourse($course);

        $course->load(['branch:id,name', 'hall:id,hall_name', 'courseTeachers.teacher:id,custom_id,full_name']);

        $enrollments = CourseEnrollment::query()
            ->with(['student:id,custom_id,initial_name,full_name,mobile,guardian_mobile', 'installments'])
            ->withCount('attendances')
            ->where('course_id', $course->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get()
            ->map(fn (CourseEnrollment $enrollment) => ['enrollment' => $enrollment, 'fees' => $fees->summary($enrollment)]);

        $sessions = $course->sessions()->withCount('attendances')->with('hall:id,hall_name')->get();

        $payments = CoursePayment::query()
            ->with(['student:id,custom_id,initial_name', 'takenBy:id,name'])
            ->where('course_id', $course->id)
            ->latest('paid_at')
            ->limit(100)
            ->get();

        $teachers = $earnings->forCourse($course);
        $instituteEarned = $earnings->instituteEarned($course);
        $payouts = $course->payouts()->with(['teacher:id,full_name', 'paidBy:id,name'])->latest('id')->get();

        $active = $enrollments->filter(fn ($row) => $row['enrollment']->isActive());
        $stats = [
            'students' => $active->count(),
            'collected' => round((float) $payments->where('status', 'completed')->sum('amount'), 2),
            'balance' => round($active->sum(fn ($row) => $row['fees']['balance']), 2),
            'overdue' => round($active->sum(fn ($row) => $row['fees']['overdue']), 2),
            'sessions' => $sessions->where('status', '!=', 'cancelled')->count(),
            'held' => $sessions->whereIn('status', ['ongoing', 'completed'])->count(),
        ];

        return view('courses.show', compact('course', 'enrollments', 'sessions', 'payments', 'teachers', 'instituteEarned', 'payouts', 'stats'));
    }

    public function edit(Course $course)
    {
        $this->checkCourse($course);
        $course->load('courseTeachers');

        return view('courses.edit', $this->formData() + compact('course'));
    }

    public function update(SaveCourseRequest $request, Course $course)
    {
        $this->checkCourse($course);
        $this->checkBranch((int) $request->input('branch_id'));

        $this->courses->update($course, $request->courseData(), $request->teachers());

        return redirect()->route('courses.show', $course)->with('success', 'Course updated.');
    }

    public function toggleActive(Course $course)
    {
        $this->checkCourse($course);
        $this->courses->toggleActive($course);

        return back()->with('success', $course->fresh()->is_active ? 'Course activated.' : 'Course deactivated.');
    }

    protected function formData(): array
    {
        $branchIds = $this->branchIds();

        return [
            'branches' => Branch::whereIn('id', $branchIds)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'halls' => ClassHall::whereIn('branch_id', $branchIds)->where('is_active', true)->orderBy('hall_name')->get(['id', 'branch_id', 'hall_name']),
            'allTeachers' => Teacher::where('is_active', true)->orderBy('full_name')->get(['id', 'custom_id', 'full_name']),
        ];
    }
}
