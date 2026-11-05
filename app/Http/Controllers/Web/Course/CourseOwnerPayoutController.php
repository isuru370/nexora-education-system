<?php

namespace App\Http\Controllers\Web\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseOwnerPayout;
use App\Models\CoursePayment;
use App\Services\Course\CourseTeacherEarningService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The institute share of a course handed to the owner. */
class CourseOwnerPayoutController extends Controller
{
    use ChecksCourseBranch;

    public function __construct(private CourseTeacherEarningService $earnings)
    {
    }

    public function store(Request $request, Course $course)
    {
        $this->checkCourse($course);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(CoursePayment::METHODS)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->earnings->ownerPayout($course, (float) $data['amount'], $data['payment_method'], $data['note'] ?? null, auth()->id());

        return back()->with('success', 'Payment to the owner saved.');
    }

    public function cancel(Course $course, CourseOwnerPayout $courseOwnerPayout)
    {
        $this->checkCourse($course, (int) $courseOwnerPayout->course_id);

        $this->earnings->cancelOwnerPayout($courseOwnerPayout);

        return back()->with('success', 'Payment to the owner cancelled.');
    }
}
