<?php

namespace App\Services\Parent\Course;

use App\Models\CourseAttendance;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\CourseSession;
use App\Services\Course\CourseFeeService;
use App\Support\Features;
use Illuminate\Support\Collection;

/**
 * The student's courses for the parent app: fee, installments, payments,
 * class days and attendance. Read only.
 */
class StudentCourseService
{
    public function __construct(private CourseFeeService $fees)
    {
    }

    /** POST /api/parent/v1/courses */
    public function fetch(int $studentId): array
    {
        if (!Features::enabled('courses')) {
            return [
                'status' => true,
                'message' => 'Courses are not used by this institute.',
                'data' => ['enabled' => false, 'courses' => []],
            ];
        }

        $courses = $this->enrollments($studentId)
            ->map(fn (CourseEnrollment $e) => $this->course($e))
            ->values();

        return [
            'status' => true,
            'message' => 'Courses fetched successfully',
            'data' => ['enabled' => true, 'courses' => $courses],
        ];
    }

    /**
     * Short list for the dashboard card (empty when the feature is off or
     * the student has no course).
     */
    public function summary(int $studentId): array
    {
        if (!Features::enabled('courses')) {
            return [];
        }

        return $this->enrollments($studentId)->map(function (CourseEnrollment $e) {
            $fees = $this->fees->summary($e);

            return [
                'course_id' => $e->course_id,
                'name' => $e->course->name,
                'code' => $e->course->code,
                'status' => $e->course->status(),
                'balance' => $fees['balance'],
                'overdue' => $fees['overdue'],
                'next_due_date' => $fees['next_installment']['due_date'] ?? null,
                'next_due_amount' => $fees['next_installment']['remaining'] ?? null,
            ];
        })->values()->all();
    }

    /** @return Collection<int, CourseEnrollment> */
    protected function enrollments(int $studentId): Collection
    {
        return CourseEnrollment::query()
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->whereHas('course')
            ->with(['course.branch:id,name', 'course.days.hall:id,hall_name', 'installments'])
            ->orderByDesc('enrolled_at')
            ->get();
    }

    protected function course(CourseEnrollment $e): array
    {
        $course = $e->course;
        $fees = $this->fees->summary($e);
        $today = now()->toDateString();

        $payments = CoursePayment::query()
            ->where('course_enrollment_id', $e->id)
            ->where('status', 'completed')
            ->orderByDesc('paid_at')
            ->get(['id', 'amount', 'receipt_number', 'payment_method', 'paid_at'])
            ->map(fn (CoursePayment $p) => [
                'id' => $p->id,
                'amount' => round((float) $p->amount, 2),
                'receipt_number' => $p->receipt_number,
                'payment_method' => $p->payment_method,
                'paid_at' => $p->paid_at?->toISOString(),
            ])
            ->values();

        $sessions = CourseSession::query()
            ->where('course_id', $course->id)
            ->with('hall:id,hall_name')
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        $attendedIds = CourseAttendance::query()
            ->where('course_enrollment_id', $e->id)
            ->pluck('attended_at', 'course_session_id');

        // Class days held since the student joined
        $held = $sessions->filter(fn (CourseSession $s) => in_array($s->status, ['completed', 'ongoing'], true)
            && $s->session_date->toDateString() >= $e->enrolled_at->toDateString()
            && $s->session_date->toDateString() <= $today);

        $attended = $held->filter(fn (CourseSession $s) => $attendedIds->has($s->id))->count();

        $upcoming = $sessions
            ->filter(fn (CourseSession $s) => $s->session_date->toDateString() >= $today
                && in_array($s->status, ['scheduled', 'ongoing'], true))
            ->take(6)
            ->map(fn (CourseSession $s) => $this->session($s))
            ->values();

        // Cancelled days from the last 30 days and ahead
        $cancelled = $sessions
            ->filter(fn (CourseSession $s) => $s->status === 'cancelled'
                && $s->session_date->toDateString() >= now()->subDays(30)->toDateString())
            ->take(10)
            ->map(fn (CourseSession $s) => $this->session($s) + ['cancel_reason' => $s->cancel_reason])
            ->values();

        $history = $held
            ->sortByDesc(fn (CourseSession $s) => $s->session_date->toDateString() . $s->start_time)
            ->take(20)
            ->map(fn (CourseSession $s) => [
                'date' => $s->session_date->toDateString(),
                'start_time' => substr((string) $s->start_time, 0, 5),
                'end_time' => substr((string) $s->end_time, 0, 5),
                'present' => $attendedIds->has($s->id),
                'attended_at' => $attendedIds->has($s->id) && $attendedIds[$s->id]
                    ? \Illuminate\Support\Carbon::parse($attendedIds[$s->id])->toISOString()
                    : null,
            ])
            ->values();

        return [
            'enrollment_id' => $e->id,
            'course_id' => $course->id,
            'name' => $course->name,
            'code' => $course->code,
            'branch' => $course->branch->name ?? null,
            'status' => $course->status(),
            'start_date' => $course->start_date->toDateString(),
            'end_date' => $course->end_date->toDateString(),
            'schedule' => $course->scheduleLabel(),
            'enrolled_at' => $e->enrolled_at->toDateString(),

            'fees' => [
                'payment_plan' => $fees['payment_plan'],
                'total_fee' => $fees['total_fee'],
                'standard_fee' => $fees['standard_fee'],
                'special_fee' => $fees['special_fee'],
                'discount' => $fees['discount'],
                'paid' => $fees['paid'],
                'balance' => $fees['balance'],
                'overdue' => $fees['overdue'],
                'next_installment' => $fees['next_installment'],
                'installments' => $fees['installments'],
            ],

            'payments' => $payments,

            'attendance' => [
                'held' => $held->count(),
                'attended' => $attended,
                'percentage' => $held->count() > 0 ? round($attended / $held->count() * 100, 1) : 0,
                'history' => $history,
            ],

            'upcoming_sessions' => $upcoming,
            'cancelled_sessions' => $cancelled,
        ];
    }

    protected function session(CourseSession $s): array
    {
        return [
            'id' => $s->id,
            'date' => $s->session_date->toDateString(),
            'start_time' => substr((string) $s->start_time, 0, 5),
            'end_time' => substr((string) $s->end_time, 0, 5),
            'hall' => $s->hall->hall_name ?? null,
            'status' => $s->status,
            'is_extra' => (bool) $s->is_custom && !$s->original_date,
        ];
    }
}
