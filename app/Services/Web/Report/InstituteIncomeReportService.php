<?php

namespace App\Services\Web\Report;

use App\Models\Branch;
use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\CoursePaymentSplit;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\StudentClass;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The institute's own income and expenses for a date range.
 * (Replaces Admin\InstituteReportController, Admin\InstituteIncomeController
 *  and Admin\InstitutePaymentReportController.)
 *
 *   Institute income  = institute share of class fees (includes hall fee)
 *                     + institute share of course fees (institutes with courses)
 *                     + admission fees + extra income
 *   Institute expense = institute expenses (bills, rent ...)
 *   Net               = income - expenses
 *
 * Teacher and organizer shares are not institute money, so their payouts
 * (salaries, advances) are not institute expenses here. They are shown in the
 * Collection report.
 *
 * Tabs:
 *  - summary       the numbers above + class fee split
 *  - classes       class fee split per class
 *  - teachers      class fee split per teacher
 *  - organizers    class fee split per organizer
 *  - extra-income  received extra income
 *  - expenses      paid institute expenses
 *  - courses       course fee split per course (institutes with courses)
 */
class InstituteIncomeReportService
{
    public const TABS = [
        'summary' => 'Summary',
        'classes' => 'By Class',
        'teachers' => 'By Teacher',
        'organizers' => 'By Organizer',
        'extra-income' => 'Extra Income',
        'expenses' => 'Institute Expenses',
    ];

    /** TABS + "By Course" when the institute has the courses feature. */
    public static function tabs(): array
    {
        return feature('courses')
            ? array_slice(self::TABS, 0, 4, true) + ['courses' => 'By Course'] + array_slice(self::TABS, 4, null, true)
            : self::TABS;
    }

    protected const SPLIT_COLUMNS = ['payments', 'payment_amount', 'hall_fee', 'teacher_amount', 'organizer_amount', 'institution_amount'];

    public function __construct(
        protected PaymentReportQuery $query
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    public function summary(ReportFilter $filter): array
    {
        $classFees = $this->query->studentPayments($filter)
            ->selectRaw('COUNT(*) as payments, COALESCE(SUM(amount), 0) as amount')
            ->first();

        $split = $this->query->splitSnapshots($filter)
            ->selectRaw('
                COUNT(*) as payments,
                COALESCE(SUM(payment_amount), 0) as payment_amount,
                COALESCE(SUM(hall_fee), 0) as hall_fee,
                COALESCE(SUM(teacher_amount), 0) as teacher_amount,
                COALESCE(SUM(organizer_amount), 0) as organizer_amount,
                COALESCE(SUM(institution_amount), 0) as institution_amount
            ')
            ->first();

        $instituteShare = round((float) $split->institution_amount, 2);
        $admissionFees = round((float) $this->query->admissionPayments($filter)->sum('amount'), 2);
        $extraIncome = round((float) $this->query->extraIncomes($filter)->sum('amount'), 2);
        $expenses = round((float) $this->query->instituteExpenses($filter)->sum('amount'), 2);

        $courses = $this->courseTotals($filter);

        $income = ['Institute share of class fees' => $instituteShare];

        if ($courses !== null) {
            $income['Institute share of course fees'] = $courses['institute'];
        }

        $income += [
            'Admission fees' => $admissionFees,
            'Extra income' => $extraIncome,
        ];

        $totalIncome = round(array_sum($income), 2);
        $unsplitCount = (int) $classFees->payments - (int) $split->payments;

        return [
            'income' => $income,
            'total_income' => $totalIncome,
            'expenses' => $expenses,
            'net' => round($totalIncome - $expenses, 2),

            'class_fees' => round((float) $classFees->amount, 2),
            'class_fee_count' => (int) $classFees->payments,
            'split' => [
                'Teacher share' => round((float) $split->teacher_amount, 2),
                'Organizer share' => round((float) $split->organizer_amount, 2),
                'Institute share' => $instituteShare,
            ],
            'hall_fee' => round((float) $split->hall_fee, 2),

            // null when the institute has no courses
            'courses' => $courses,

            // Completed payments that have no fee split (their institute share is unknown)
            'unsplit_count' => max($unsplitCount, 0),
            'unsplit_amount' => max(round((float) $classFees->amount - (float) $split->payment_amount, 2), 0),
        ];
    }

    public function summaryRows(ReportFilter $filter): Collection
    {
        $summary = $this->summary($filter);
        $rows = collect();

        foreach ($summary['income'] as $label => $amount) {
            $rows->push(['section' => 'Institute income', 'item' => $label, 'amount' => $amount]);
        }

        $rows->push(['section' => 'Institute income', 'item' => 'Total income', 'amount' => $summary['total_income']]);
        $rows->push(['section' => 'Institute expenses', 'item' => 'Institute expenses', 'amount' => $summary['expenses']]);
        $rows->push(['section' => 'Net', 'item' => 'Net income', 'amount' => $summary['net']]);
        $rows->push(['section' => 'Class fees', 'item' => 'Class fees collected (' . $summary['class_fee_count'] . ' payments)', 'amount' => $summary['class_fees']]);

        foreach ($summary['split'] as $label => $amount) {
            $rows->push(['section' => 'Class fees', 'item' => $label, 'amount' => $amount]);
        }

        $rows->push(['section' => 'Class fees', 'item' => 'Hall fee (part of institute share)', 'amount' => $summary['hall_fee']]);

        if ($summary['courses'] !== null) {
            $rows->push(['section' => 'Course fees', 'item' => 'Course fees collected (' . $summary['courses']['payments'] . ' payments)', 'amount' => $summary['courses']['collected']]);
            $rows->push(['section' => 'Course fees', 'item' => 'Teacher share', 'amount' => $summary['courses']['teachers']]);
            $rows->push(['section' => 'Course fees', 'item' => 'Institute share', 'amount' => $summary['courses']['institute']]);
        }

        if ($summary['unsplit_count'] > 0) {
            $rows->push(['section' => 'Class fees', 'item' => 'Not split (' . $summary['unsplit_count'] . ' payments)', 'amount' => $summary['unsplit_amount']]);
        }

        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    */

    public function table(string $tab): array
    {
        $splitHeadings = ['Payments', 'Collected (Rs.)', 'Hall Fee (Rs.)', 'Teacher (Rs.)', 'Organizer (Rs.)', 'Institute (Rs.)'];
        $splitMoney = ['payment_amount', 'hall_fee', 'teacher_amount', 'organizer_amount', 'institution_amount'];

        return match ($tab) {
            'summary' => [
                'headings' => ['Section', 'Item', 'Amount (Rs.)'],
                'columns' => ['section', 'item', 'amount'],
                'money' => ['amount'],
                'total' => null,
            ],
            'classes' => [
                'headings' => array_merge(['Class', 'Grade', 'Teacher'], $splitHeadings),
                'columns' => array_merge(['name', 'grade_name', 'teacher_name'], self::SPLIT_COLUMNS),
                'money' => $splitMoney,
                'total' => 'payment_amount',
            ],
            'teachers' => [
                'headings' => array_merge(['Teacher Code', 'Teacher'], $splitHeadings),
                'columns' => array_merge(['code', 'name'], self::SPLIT_COLUMNS),
                'money' => $splitMoney,
                'total' => 'payment_amount',
            ],
            'organizers' => [
                'headings' => array_merge(['Organizer'], $splitHeadings),
                'columns' => array_merge(['name'], self::SPLIT_COLUMNS),
                'money' => $splitMoney,
                'total' => 'payment_amount',
            ],
            'courses' => [
                'headings' => ['Course', 'Code', 'Payments', 'Collected (Rs.)', 'Teacher (Rs.)', 'Institute (Rs.)'],
                'columns' => ['name', 'code', 'payments', 'collected', 'teachers', 'institute'],
                'money' => ['collected', 'teachers', 'institute'],
                'total' => 'collected',
            ],
            'extra-income' => [
                'headings' => ['Date', 'Branch', 'Type', 'Reason', 'Receipt No', 'Note', 'Added By', 'Amount (Rs.)'],
                'columns' => ['date', 'branch', 'type', 'reason', 'receipt_number', 'note', 'created_by', 'amount'],
                'money' => ['amount'],
                'total' => 'amount',
            ],
            'expenses' => [
                'headings' => ['Date', 'Branch', 'Reason', 'Note', 'Added By', 'Amount (Rs.)'],
                'columns' => ['date', 'branch', 'reason', 'note', 'created_by', 'amount'],
                'money' => ['amount'],
                'total' => 'amount',
            ],
        };
    }

    public function rows(string $tab, ReportFilter $filter): Collection
    {
        return match ($tab) {
            'summary' => $this->summaryRows($filter),
            'classes' => $this->splitBy($filter, 'student_class_id', fn (Collection $ids) => $this->classNames($ids)),
            'teachers' => $this->splitBy($filter, 'teacher_id', fn (Collection $ids) => $this->teacherNames($ids)),
            'organizers' => $this->splitBy($filter, 'organizer_id', fn (Collection $ids) => $this->organizerNames($ids)),
            'extra-income' => $this->extraIncomeRows($filter),
            'expenses' => $this->expenseRows($filter),
            'courses' => $this->courseRows($filter),
        };
    }

    /**
     * Fee split totals grouped by class / teacher / organizer, biggest first.
     */
    protected function splitBy(ReportFilter $filter, string $column, callable $names): Collection
    {
        $groups = $this->query->splitSnapshots($filter)
            ->groupBy($column)
            ->selectRaw("
                {$column} as group_id,
                COUNT(*) as payments,
                COALESCE(SUM(payment_amount), 0) as payment_amount,
                COALESCE(SUM(hall_fee), 0) as hall_fee,
                COALESCE(SUM(teacher_amount), 0) as teacher_amount,
                COALESCE(SUM(organizer_amount), 0) as organizer_amount,
                COALESCE(SUM(institution_amount), 0) as institution_amount
            ")
            ->get();

        $labels = $names($groups->pluck('group_id')->filter()->unique()->values());

        return $groups
            ->map(function ($group) use ($labels) {
                $label = $group->group_id ? $labels->get($group->group_id, []) : [];

                return array_merge(
                    ['name' => $label['name'] ?? ($group->group_id ? 'Deleted #' . $group->group_id : 'None')],
                    $label,
                    [
                        'payments' => (int) $group->payments,
                        'payment_amount' => round((float) $group->payment_amount, 2),
                        'hall_fee' => round((float) $group->hall_fee, 2),
                        'teacher_amount' => round((float) $group->teacher_amount, 2),
                        'organizer_amount' => round((float) $group->organizer_amount, 2),
                        'institution_amount' => round((float) $group->institution_amount, 2),
                    ]
                );
            })
            ->sortByDesc('payment_amount')
            ->values();
    }

    protected function classNames(Collection $ids): Collection
    {
        return StudentClass::withTrashed()
            ->with(['grade:id,grade_name', 'teacher' => fn ($query) => $query->withTrashed()->select('id', 'full_name')])
            ->whereIn('id', $ids)
            ->get(['id', 'class_name', 'grade_id', 'teacher_id'])
            ->mapWithKeys(fn ($class) => [$class->id => [
                'name' => $class->class_name,
                'grade_name' => optional($class->grade)->grade_name,
                'teacher_name' => optional($class->teacher)->full_name,
            ]]);
    }

    protected function teacherNames(Collection $ids): Collection
    {
        return Teacher::withTrashed()
            ->whereIn('id', $ids)
            ->get(['id', 'custom_id', 'full_name'])
            ->mapWithKeys(fn ($teacher) => [$teacher->id => ['name' => $teacher->full_name, 'code' => $teacher->custom_id]]);
    }

    protected function organizerNames(Collection $ids): Collection
    {
        return Organizer::withTrashed()
            ->whereIn('id', $ids)
            ->get(['id', 'name'])
            ->mapWithKeys(fn ($organizer) => [$organizer->id => ['name' => $organizer->name]]);
    }

    /*
    |--------------------------------------------------------------------------
    | Courses (institutes with the "courses" feature)
    |--------------------------------------------------------------------------
    */

    /** Completed course payments in the filter (same branch / date / cashier / method filters). */
    protected function coursePayments(ReportFilter $filter)
    {
        return CoursePayment::query()
            ->where('status', 'completed')
            ->whereIn('branch_id', $filter->branchIds)
            ->whereBetween('paid_at', [$filter->from, $filter->to])
            ->when($filter->collectedBy, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filter->paymentMethod, fn ($query, $method) => $query->where('payment_method', $method));
    }

    /** @return array{payments: int, collected: float, teachers: float, institute: float}|null */
    protected function courseTotals(ReportFilter $filter): ?array
    {
        if (!feature('courses')) {
            return null;
        }

        $payments = $this->coursePayments($filter);
        $splits = CoursePaymentSplit::query()->whereIn('course_payment_id', (clone $payments)->select('id'));

        return [
            'payments' => (clone $payments)->count(),
            'collected' => round((float) (clone $payments)->sum('amount'), 2),
            'teachers' => round((float) (clone $splits)->whereNotNull('teacher_id')->sum('amount'), 2),
            'institute' => round((float) (clone $splits)->whereNull('teacher_id')->sum('amount'), 2),
        ];
    }

    protected function courseRows(ReportFilter $filter): Collection
    {
        if (!feature('courses')) {
            return collect();
        }

        $payments = $this->coursePayments($filter)->with('splits')->get();
        $courses = Course::withTrashed()->whereIn('id', $payments->pluck('course_id')->unique())->get(['id', 'name', 'code'])->keyBy('id');

        return $payments->groupBy('course_id')->map(function (Collection $rows, $courseId) use ($courses) {
            $splits = $rows->flatMap->splits;

            return [
                'name' => optional($courses->get($courseId))->name ?? '-',
                'code' => optional($courses->get($courseId))->code ?? '-',
                'payments' => $rows->count(),
                'collected' => round((float) $rows->sum('amount'), 2),
                'teachers' => round((float) $splits->whereNotNull('teacher_id')->sum('amount'), 2),
                'institute' => round((float) $splits->whereNull('teacher_id')->sum('amount'), 2),
            ];
        })->sortByDesc('collected')->values();
    }

    protected function extraIncomeRows(ReportFilter $filter): Collection
    {
        return $this->query->extraIncomes($filter)
            ->with(['branch:id,name', 'createdBy:id,name'])
            ->orderBy('income_date')
            ->orderBy('id')
            ->get()
            ->map(fn ($income) => [
                'date' => optional($income->income_date)->format('Y-m-d'),
                'branch' => optional($income->branch)->name ?? 'All branches',
                'type' => ucwords(str_replace('_', ' ', (string) $income->income_type)),
                'reason' => $income->reason,
                'receipt_number' => $income->receipt_number,
                'note' => $income->note,
                'created_by' => optional($income->createdBy)->name,
                'amount' => round((float) $income->amount, 2),
            ]);
    }

    protected function expenseRows(ReportFilter $filter): Collection
    {
        return $this->query->instituteExpenses($filter)
            ->with(['branch:id,name', 'createdBy:id,name'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->map(fn ($expense) => [
                'date' => optional($expense->payment_date)->format('Y-m-d'),
                'branch' => optional($expense->branch)->name ?? 'All branches',
                'reason' => $expense->reason,
                'note' => $expense->note,
                'created_by' => optional($expense->createdBy)->name,
                'amount' => round((float) $expense->amount, 2),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Yearly chart (dashboard)
    |--------------------------------------------------------------------------
    */

    /**
     * Class fees collected and the institute share for every month of a year,
     * by the payment date.
     */
    public function yearly(int $year, Collection $branchIds): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = $start->copy()->endOfYear();
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', payments.paid_at) AS INTEGER)"
            : 'MONTH(payments.paid_at)';

        $totals = Payment::query()
            ->leftJoin('payment_split_snapshots', function ($join) {
                $join->on('payment_split_snapshots.payment_id', '=', 'payments.id')
                    ->whereNull('payment_split_snapshots.deleted_at');
            })
            ->where('payments.status', 'completed')
            ->whereIn('payments.branch_id', $branchIds)
            ->whereBetween('payments.paid_at', [$start, $end])
            ->groupBy(DB::raw($monthExpression))
            ->selectRaw("{$monthExpression} as month, SUM(payments.amount) as total, COALESCE(SUM(payment_split_snapshots.institution_amount), 0) as institute")
            ->get()
            ->keyBy(fn ($row) => (int) $row->month);

        $labels = [];
        $total = [];
        $institute = [];

        for ($month = 1; $month <= 12; $month++) {
            $labels[] = Carbon::create($year, $month, 1)->format('M');
            $total[] = round((float) optional($totals->get($month))->total, 2);
            $institute[] = round((float) optional($totals->get($month))->institute, 2);
        }

        return [
            'year' => $year,
            'labels' => $labels,
            'total_payments' => $total,
            'institution_payments' => $institute,
        ];
    }

    public function branchName(ReportFilter $filter): string
    {
        return $filter->branchId
            ? (string) optional(Branch::query()->find($filter->branchId))->name
            : 'All branches';
    }
}
