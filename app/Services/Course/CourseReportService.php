<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\CourseAttendance;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\CoursePaymentSplit;
use App\Models\CourseSession;
use App\Models\CourseTeacherPayout;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Course reports: daily, monthly, yearly and all courses.
 *
 * Every report has the same shape, so the page, the PDF and the Excel file
 * are built from one array:
 *   title, subtitle, summary [label => value], tables [
 *     key => [title, columns [key => heading], money [keys], total bool, rows Collection]
 *   ]
 */
class CourseReportService
{
    public function __construct(
        private CourseFeeService $fees,
        private CourseTeacherEarningService $earnings
    ) {
    }

    /** @param  int[]  $branchIds */
    public function daily(Carbon $date, array $branchIds): array
    {
        [$from, $to] = [$date->copy()->startOfDay(), $date->copy()->endOfDay()];

        $payments = CoursePayment::query()
            ->with(['course:id,name', 'student:id,custom_id,initial_name', 'takenBy:id,name', 'splits'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('paid_at', [$from, $to])
            ->orderBy('paid_at')
            ->get();

        $completed = $payments->where('status', 'completed');
        $split = $this->splitTotals($completed);

        $sessions = CourseSession::query()
            ->with('course:id,name')
            ->withCount('attendances')
            ->whereIn('branch_id', $branchIds)
            ->whereDate('session_date', $date->toDateString())
            ->orderBy('start_time')
            ->get();

        $enrollments = CourseEnrollment::query()
            ->with(['course:id,name', 'student:id,custom_id,initial_name'])
            ->whereIn('branch_id', $branchIds)
            ->whereDate('enrolled_at', $date->toDateString())
            ->get();

        $payouts = $this->payouts($branchIds, $from, $to);

        return [
            'title' => 'Course Daily Report',
            'subtitle' => $date->format('l, d M Y'),
            'summary' => [
                'Collected' => $this->money($completed->sum('amount')),
                'Payments' => $completed->count(),
                'Teachers\' share' => $this->money($split['teachers']),
                'Institute share' => $this->money($split['institute']),
                'Paid to teachers' => $this->money($payouts->sum('amount')),
                'Cancelled payments' => $payments->where('status', 'cancelled')->count(),
                'New enrollments' => $enrollments->count(),
                'Class days' => $sessions->whereIn('status', ['ongoing', 'completed'])->count() . ' held / ' . $sessions->count(),
            ],
            'tables' => [
                'by_course' => $this->byCourseTable($completed),
                'by_method' => $this->byMethodTable($completed),
                'payments' => [
                    'title' => 'Payments',
                    'columns' => ['time' => 'Time', 'receipt' => 'Receipt', 'student' => 'Student', 'course' => 'Course', 'method' => 'Method', 'by' => 'By', 'status' => 'Status', 'amount' => 'Amount'],
                    'money' => ['amount'],
                    'total' => false,
                    'rows' => $payments->map(fn (CoursePayment $p) => [
                        'time' => $p->paid_at->format('h:i A'),
                        'receipt' => $p->receipt_number,
                        'student' => trim(($p->student->custom_id ?? '') . ' ' . ($p->student->initial_name ?? '')),
                        'course' => $p->course->name ?? '',
                        'method' => $this->label($p->payment_method),
                        'by' => $p->takenBy->name ?? '',
                        'status' => ucfirst($p->status),
                        'amount' => (float) $p->amount,
                    ])->values(),
                ],
                'sessions' => [
                    'title' => 'Class days',
                    'columns' => ['course' => 'Course', 'time' => 'Time', 'status' => 'Status', 'present' => 'Present'],
                    'money' => [],
                    'total' => false,
                    'rows' => $sessions->map(fn (CourseSession $s) => [
                        'course' => $s->course->name ?? '',
                        'time' => Carbon::parse($s->start_time)->format('h:i A') . ' - ' . Carbon::parse($s->end_time)->format('h:i A'),
                        'status' => ucfirst($s->status),
                        'present' => $s->attendances_count,
                    ])->values(),
                ],
                'enrollments' => [
                    'title' => 'New enrollments',
                    'columns' => ['student' => 'Student', 'course' => 'Course', 'plan' => 'Plan', 'special' => 'Special fee', 'fee' => 'Fee'],
                    'money' => ['fee'],
                    'total' => true,
                    'rows' => $enrollments->map(fn (CourseEnrollment $e) => [
                        'student' => trim(($e->student->custom_id ?? '') . ' ' . ($e->student->initial_name ?? '')),
                        'course' => $e->course->name ?? '',
                        'plan' => $e->payment_plan === 'full' ? 'At once' : 'Installments',
                        'special' => $e->hasSpecialFee() ? $e->fee_reason : '',
                        'fee' => (float) $e->total_fee,
                    ])->values(),
                ],
                'payouts' => $this->payoutTable($payouts),
            ],
        ];
    }

    /** @param  int[]  $branchIds */
    public function monthly(int $year, int $month, array $branchIds): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $completed = $this->completedPayments($branchIds, $start, $end);
        $split = $this->splitTotals($completed);
        $payouts = $this->payouts($branchIds, $start, $end);

        $days = collect(CarbonPeriod::create($start, $end))->map(function (Carbon $day) use ($completed) {
            $rows = $completed->filter(fn ($p) => $p->paid_at->isSameDay($day));
            $split = $this->splitTotals($rows);

            return [
                'date' => $day->format('Y-m-d D'),
                'payments' => $rows->count(),
                'collected' => round((float) $rows->sum('amount'), 2),
                'teachers' => $split['teachers'],
                'institute' => $split['institute'],
            ];
        })->filter(fn ($row) => $row['payments'] > 0)->values();

        return [
            'title' => 'Course Monthly Report',
            'subtitle' => $start->format('F Y'),
            'summary' => $this->periodSummary($completed, $split, $payouts, $branchIds, $start, $end),
            'tables' => [
                'by_day' => [
                    'title' => 'By day',
                    'columns' => ['date' => 'Date', 'payments' => 'Payments', 'collected' => 'Collected', 'teachers' => 'Teachers\' share', 'institute' => 'Institute share'],
                    'money' => ['collected', 'teachers', 'institute'],
                    'total' => true,
                    'rows' => $days,
                ],
                'by_course' => $this->byCourseTable($completed),
                'by_method' => $this->byMethodTable($completed),
                'payouts' => $this->payoutTable($payouts),
            ],
        ];
    }

    /** @param  int[]  $branchIds */
    public function yearly(int $year, array $branchIds): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = $start->copy()->endOfYear();

        $completed = $this->completedPayments($branchIds, $start, $end);
        $split = $this->splitTotals($completed);
        $payouts = $this->payouts($branchIds, $start, $end);

        $months = collect(range(1, 12))->map(function (int $month) use ($completed, $payouts, $year) {
            $rows = $completed->filter(fn ($p) => $p->paid_at->month === $month);
            $split = $this->splitTotals($rows);

            return [
                'month' => Carbon::create($year, $month, 1)->format('F'),
                'payments' => $rows->count(),
                'collected' => round((float) $rows->sum('amount'), 2),
                'teachers' => $split['teachers'],
                'institute' => $split['institute'],
                'paid_teachers' => round((float) $payouts->filter(fn ($p) => $p->paid_on->month === $month)->sum('amount'), 2),
            ];
        });

        return [
            'title' => 'Course Yearly Report',
            'subtitle' => (string) $year,
            'summary' => $this->periodSummary($completed, $split, $payouts, $branchIds, $start, $end),
            'tables' => [
                'by_month' => [
                    'title' => 'By month',
                    'columns' => ['month' => 'Month', 'payments' => 'Payments', 'collected' => 'Collected', 'teachers' => 'Teachers\' share', 'institute' => 'Institute share', 'paid_teachers' => 'Paid to teachers'],
                    'money' => ['collected', 'teachers', 'institute', 'paid_teachers'],
                    'total' => true,
                    'rows' => $months,
                ],
                'by_course' => $this->byCourseTable($completed),
                'by_method' => $this->byMethodTable($completed),
            ],
        ];
    }

    /**
     * Every course: students, fees, collected, to collect, teachers.
     *
     * @param  int[]  $branchIds
     */
    public function courses(array $branchIds, bool $activeOnly = false): array
    {
        $courses = Course::query()
            ->with(['branch:id,name', 'enrollments' => fn ($q) => $q->where('status', 'active')->with('installments')])
            ->whereIn('branch_id', $branchIds)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderByDesc('start_date')
            ->get();

        $rows = $courses->map(function (Course $course) {
            $fees = $course->enrollments->map(fn ($e) => $this->fees->summary($e));
            $teachers = $this->earnings->forCourse($course);
            $sessions = $course->sessions()->get(['id', 'status']);
            $held = $sessions->whereIn('status', ['ongoing', 'completed'])->pluck('id');
            $present = $held->isEmpty() ? 0 : CourseAttendance::whereIn('course_session_id', $held)->count();
            $students = $course->enrollments->count();

            return [
                'course' => $course->name,
                'branch' => $course->branch->name ?? '',
                'dates' => $course->start_date->format('Y-m-d') . ' - ' . $course->end_date->format('Y-m-d'),
                'status' => ucfirst($course->status()),
                'students' => $students,
                'expected' => round($fees->sum('total_fee'), 2),
                'discounts' => round($course->enrollments->sum(fn ($e) => $e->discount()), 2),
                'collected' => round($fees->sum('paid'), 2),
                'balance' => round($fees->sum('balance'), 2),
                'overdue' => round($fees->sum('overdue'), 2),
                'teachers_earned' => round($teachers->sum('earned'), 2),
                'teachers_paid' => round($teachers->sum('paid'), 2),
                'teachers_balance' => round($teachers->sum('balance'), 2),
                'institute' => $this->earnings->instituteEarned($course),
                'class_days' => $held->count() . ' / ' . $sessions->where('status', '!=', 'cancelled')->count(),
                'attendance' => ($held->count() && $students) ? round($present / ($held->count() * $students) * 100) . '%' : '-',
            ];
        })->values();

        return [
            'title' => 'All Courses Report',
            'subtitle' => $activeOnly ? 'Active courses' : 'All courses',
            'summary' => [
                'Courses' => $rows->count(),
                'Students' => $rows->sum('students'),
                'Expected fees' => $this->money($rows->sum('expected')),
                'Special fee discounts' => $this->money($rows->sum('discounts')),
                'Collected' => $this->money($rows->sum('collected')),
                'To collect' => $this->money($rows->sum('balance')),
                'Overdue' => $this->money($rows->sum('overdue')),
                'Teachers to pay' => $this->money($rows->sum('teachers_balance')),
                'Institute share' => $this->money($rows->sum('institute')),
            ],
            'tables' => [
                'courses' => [
                    'title' => 'Courses',
                    'columns' => [
                        'course' => 'Course', 'branch' => 'Branch', 'dates' => 'Dates', 'status' => 'Status', 'students' => 'Students',
                        'expected' => 'Expected', 'discounts' => 'Special fee discounts', 'collected' => 'Collected', 'balance' => 'To collect', 'overdue' => 'Overdue',
                        'teachers_earned' => 'Teachers earned', 'teachers_paid' => 'Teachers paid', 'teachers_balance' => 'Teachers to pay',
                        'institute' => 'Institute', 'class_days' => 'Class days', 'attendance' => 'Attendance',
                    ],
                    'money' => ['expected', 'discounts', 'collected', 'balance', 'overdue', 'teachers_earned', 'teachers_paid', 'teachers_balance', 'institute'],
                    'total' => true,
                    'rows' => $rows,
                ],
            ],
        ];
    }

    /* ------------------------------------------------------------------ */

    protected function completedPayments(array $branchIds, Carbon $from, Carbon $to): Collection
    {
        return CoursePayment::query()
            ->with(['course:id,name', 'splits'])
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'completed')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();
    }

    protected function payouts(array $branchIds, Carbon $from, Carbon $to): Collection
    {
        return CourseTeacherPayout::query()
            ->with(['teacher:id,full_name', 'course:id,name'])
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'paid')
            ->whereDate('paid_on', '>=', $from->toDateString())
            ->whereDate('paid_on', '<=', $to->toDateString())
            ->orderBy('paid_on')
            ->get();
    }

    /** @return array{teachers: float, institute: float} */
    protected function splitTotals(Collection $payments): array
    {
        $splits = $payments->flatMap(fn ($p) => $p->splits);

        return [
            'teachers' => round((float) $splits->whereNotNull('teacher_id')->sum('amount'), 2),
            'institute' => round((float) $splits->whereNull('teacher_id')->sum('amount'), 2),
        ];
    }

    protected function periodSummary(Collection $completed, array $split, Collection $payouts, array $branchIds, Carbon $start, Carbon $end): array
    {
        return [
            'Collected' => $this->money($completed->sum('amount')),
            'Payments' => $completed->count(),
            'Teachers\' share' => $this->money($split['teachers']),
            'Institute share' => $this->money($split['institute']),
            'Paid to teachers' => $this->money($payouts->sum('amount')),
            'New enrollments' => CourseEnrollment::whereIn('branch_id', $branchIds)
                ->whereDate('enrolled_at', '>=', $start->toDateString())
                ->whereDate('enrolled_at', '<=', $end->toDateString())->count(),
        ];
    }

    protected function byCourseTable(Collection $completed): array
    {
        return [
            'title' => 'By course',
            'columns' => ['course' => 'Course', 'payments' => 'Payments', 'collected' => 'Collected', 'teachers' => 'Teachers\' share', 'institute' => 'Institute share'],
            'money' => ['collected', 'teachers', 'institute'],
            'total' => true,
            'rows' => $completed->groupBy('course_id')->map(function (Collection $rows) {
                $split = $this->splitTotals($rows);

                return [
                    'course' => $rows->first()->course->name ?? '',
                    'payments' => $rows->count(),
                    'collected' => round((float) $rows->sum('amount'), 2),
                    'teachers' => $split['teachers'],
                    'institute' => $split['institute'],
                ];
            })->sortByDesc('collected')->values(),
        ];
    }

    protected function byMethodTable(Collection $completed): array
    {
        return [
            'title' => 'By payment method',
            'columns' => ['method' => 'Method', 'payments' => 'Payments', 'collected' => 'Collected'],
            'money' => ['collected'],
            'total' => true,
            'rows' => $completed->groupBy('payment_method')->map(fn (Collection $rows, $method) => [
                'method' => $this->label($method),
                'payments' => $rows->count(),
                'collected' => round((float) $rows->sum('amount'), 2),
            ])->values(),
        ];
    }

    protected function payoutTable(Collection $payouts): array
    {
        return [
            'title' => 'Paid to teachers',
            'columns' => ['date' => 'Date', 'teacher' => 'Teacher', 'course' => 'Course', 'method' => 'Method', 'amount' => 'Amount'],
            'money' => ['amount'],
            'total' => true,
            'rows' => $payouts->map(fn (CourseTeacherPayout $p) => [
                'date' => $p->paid_on->format('Y-m-d'),
                'teacher' => $p->teacher->full_name ?? '',
                'course' => $p->course->name ?? '',
                'method' => $this->label($p->payment_method),
                'amount' => (float) $p->amount,
            ])->values(),
        ];
    }

    protected function money($value): string
    {
        return 'Rs. ' . number_format((float) $value, 2);
    }

    protected function label(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
