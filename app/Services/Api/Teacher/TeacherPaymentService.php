<?php

namespace App\Services\Api\Teacher;

use App\Models\Course;
use App\Models\CourseTeacherPayout;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use App\Models\WelfareDeduction;
use App\Services\Course\CourseTeacherEarningService;
use App\Services\Web\MonthlyReport\MonthlyReportService;
use App\Services\Web\TeacherSalary\TeacherSalaryService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Staff app, teacher: own money only. Earnings by class, salary, advances /
 * deductions / welfare, salary history, which students paid (no phone
 * numbers) and course earnings. Uses the same services as the web pages,
 * so the numbers match. (Settings > Teacher App > payments)
 */
class TeacherPaymentService
{
    public function __construct(
        private TeacherSalaryService $salaries,
        private MonthlyReportService $reports,
        private CourseTeacherEarningService $courseEarnings,
    ) {}

    /** Branches of this teacher (most teachers have one). */
    public function branches(Teacher $teacher): array
    {
        return $this->salaries->branchOptions($teacher->id)
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
            ->values()
            ->all();
    }

    /** One month: earnings by class, deductions, net salary and status. */
    public function month(Teacher $teacher, ?int $branchId, int $year, int $month): array
    {
        $branchId = $this->branch($teacher, $branchId);

        $details = $this->salaries->getTeacherSalaryDetails($teacher->id, $branchId, $year, $month);
        $slip = $this->salaries->salarySlip($teacher->id, $branchId, $year, $month);

        return [
            'branch_id' => $branchId,
            'branch' => $details['branch']->name ?? null,
            'period' => $details['period'],
            'month_name' => Carbon::create($year, $month, 1)->format('F Y'),

            'class_income' => $details['total_class_income'],
            'teacher_earnings' => $details['teacher_earnings'],
            'carried' => $details['carried'],
            'carried_months' => $details['carried_months'],
            'paid_after_salary' => $details['paid_after_salary'],

            'advance' => $details['advance'],
            'deduction' => $details['deduction'],
            'other' => $details['other'],
            'welfare' => $details['welfare'],
            'total_deductions' => $details['total_deductions'],
            'net_payable' => $details['net_payable'],

            'salary_status' => $details['salary_status'],
            'salary_paid' => $details['salary_paid'],
            'paid_at' => $details['paid_at'] ? Carbon::parse($details['paid_at'])->toISOString() : null,

            'earnings' => $slip['earnings'],
            'deductions' => $slip['deductions'],
        ];
    }

    /** Advance / deduction / other payments and welfare of one month. */
    public function deductions(Teacher $teacher, ?int $branchId, int $year, int $month): array
    {
        $branchId = $this->branch($teacher, $branchId);

        $all = $this->salaries->teacherAllPayments($teacher->id, $branchId, $year, $month);

        $welfare = feature('welfare')
            ? WelfareDeduction::query()
                ->where('teacher_id', $teacher->id)
                ->where('branch_id', $branchId)
                ->where('salary_year', $year)
                ->where('salary_month', $month)
                ->orderBy('id')
                ->get()
                ->map(fn ($w) => [
                    'id' => $w->id,
                    'amount' => round((float) $w->amount, 2),
                    'note' => $w->note,
                    'date' => $w->created_at?->toDateString(),
                ])
                ->values()
            : collect();

        return [
            'branch_id' => $branchId,
            'totals' => $all['totals'],
            'payments' => $all['payments']->map(fn ($p) => [
                'id' => $p->id,
                'type' => $p->payment_type,
                'amount' => round((float) $p->amount, 2),
                'date' => $p->payment_date?->toDateString(),
                'reason' => $p->reason,
                'note' => $p->note,
            ])->values(),
            'welfare' => $welfare,
            'welfare_enabled' => feature('welfare'),
        ];
    }

    /** Salary records of the last 12 months (paid and pending). */
    public function history(Teacher $teacher, ?int $branchId): array
    {
        $branchId = $this->branch($teacher, $branchId);
        $from = now()->startOfMonth()->subMonths(11);

        $records = TeacherSalary::query()
            ->where('teacher_id', $teacher->id)
            ->where('branch_id', $branchId)
            ->where(function ($q) use ($from) {
                $q->where('salary_year', '>', $from->year)
                    ->orWhere(fn ($q) => $q->where('salary_year', $from->year)->where('salary_month', '>=', $from->month));
            })
            ->get()
            ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->salary_year, $r->salary_month));

        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $m = now()->startOfMonth()->subMonths($i);
            $key = $m->format('Y-m');
            $r = $records->get($key);

            $months[] = [
                'period' => $key,
                'year' => $m->year,
                'month' => $m->month,
                'month_name' => $m->format('M Y'),
                'status' => $r->status ?? 'pending',
                'gross' => $r ? round((float) $r->gross_amount + (float) $r->carried_amount, 2) : null,
                'deductions' => $r ? round((float) $r->advance_deduction + (float) $r->other_deduction + (float) $r->welfare_deduction, 2) : null,
                'net' => $r ? round((float) $r->net_amount, 2) : null,
                'paid_at' => $r && $r->paid_at ? Carbon::parse($r->paid_at)->toISOString() : null,
            ];
        }

        return ['branch_id' => $branchId, 'months' => $months];
    }

    /**
     * Students of each class: paid / unpaid / free card / no attendance.
     * Phone numbers are removed.
     */
    public function students(Teacher $teacher, ?int $branchId, int $year, int $month, string $basis): array
    {
        $branchId = $branchId ? $this->branch($teacher, $branchId) : null;

        $report = $this->reports->teacherStudentPaymentReport($teacher->id, $year, $month, $basis, $branchId);

        foreach ($report['classes'] as &$class) {
            foreach ($class['categories'] as &$category) {
                foreach ($category['students'] as &$group) {
                    $group = array_map(function (array $row) {
                        unset($row['guardian_mobile']);

                        return $row;
                    }, $group);
                }
                unset($group);
            }
            unset($category);
        }
        unset($class);

        return [
            'filter' => $report['filter'],
            'summary' => $report['summary'],
            'classes' => $report['classes'],
        ];
    }

    /** Course earnings: share of fees, paid to the teacher, balance. */
    public function courses(Teacher $teacher): array
    {
        if (!feature('courses')) {
            return ['enabled' => false, 'courses' => [], 'payouts' => [], 'totals' => ['earned' => 0, 'paid' => 0, 'balance' => 0]];
        }

        $courses = Course::query()
            ->whereHas('courseTeachers', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->orderByDesc('start_date')
            ->get();

        $rows = $courses->map(function (Course $course) use ($teacher) {
            $row = $this->courseEarnings->forCourse($course)->first(fn ($r) => (int) $r['teacher']->id === (int) $teacher->id);

            return [
                'course_id' => $course->id,
                'name' => $course->name,
                'code' => $course->code,
                'status' => $course->status(),
                'percentage' => $row['percentage'] ?? null,
                'earned' => round((float) ($row['earned'] ?? 0), 2),
                'paid' => round((float) ($row['paid'] ?? 0), 2),
                'balance' => round((float) ($row['balance'] ?? 0), 2),
            ];
        })->values();

        $payouts = CourseTeacherPayout::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', 'paid')
            ->with('course:id,name')
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'course' => $p->course->name ?? null,
                'amount' => round((float) $p->amount, 2),
                'paid_on' => $p->paid_on ? Carbon::parse($p->paid_on)->toDateString() : null,
                'payment_method' => $p->payment_method,
            ])
            ->values();

        return [
            'enabled' => true,
            'courses' => $rows,
            'payouts' => $payouts,
            'totals' => [
                'earned' => round($rows->sum('earned'), 2),
                'paid' => round($rows->sum('paid'), 2),
                'balance' => round($rows->sum('balance'), 2),
            ],
        ];
    }

    /** The teacher's branch: the one asked for (must be theirs) or the first. */
    protected function branch(Teacher $teacher, ?int $branchId): int
    {
        $own = DB::table('teacher_branches')->where('teacher_id', $teacher->id)->pluck('branch_id')->map(fn ($id) => (int) $id);

        if ($branchId !== null && !$own->contains($branchId)) {
            throw new DomainException('This is not your branch.');
        }

        return $this->salaries->resolveBranchId($branchId, $teacher->id);
    }
}
