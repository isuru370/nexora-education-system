<?php

namespace App\Services\Web\Report;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Annual report: one row per month (Jan - Dec) for a year.
 *
 *   Income   = class fees + admission fees + extra income
 *   Expenses = teacher salaries + teacher advances/other + organizer payments + institute expenses
 *   Net      = income - expenses
 * plus the class fee split (teacher / organizer / institute) per month.
 *
 * Every number comes from PaymentReportQuery, so a month here matches the
 * Collection report and the Branch-wise report for the same month.
 */
class AnnualReportService
{
    protected const MONEY = [
        'class_fees',
        'admission_fees',
        'extra_income',
        'total_income',
        'teacher_share',
        'organizer_share',
        'institute_share',
        'teacher_salaries',
        'teacher_advances',
        'organizer_payments',
        'institute_expenses',
        'total_expenses',
        'net',
    ];

    public function __construct(
        protected PaymentReportQuery $query
    ) {}

    /**
     * The filter for a whole year (Jan 1 - Dec 31, or up to today for the current year).
     *
     * @throws \DomainException
     */
    public function yearFilter(int $year, ?int $branchId = null): ReportFilter
    {
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = $year === (int) now()->year
            ? now()->toDateString()
            : Carbon::create($year, 12, 31)->toDateString();

        return $this->query->filter($from, $to, $branchId);
    }

    /**
     * The same period one year earlier (for the "vs last year" comparison).
     * For the current year this is Jan 1 - same day last year, so a part year
     * is never compared with a full year.
     *
     * @throws \DomainException
     */
    public function previousYearFilter(ReportFilter $filter): ReportFilter
    {
        return $this->query->filter(
            $filter->from->copy()->subYearNoOverflow()->toDateString(),
            $filter->to->copy()->subYearNoOverflow()->toDateString(),
            $filter->branchId
        );
    }

    /**
     * @return array{rows: Collection, totals: array, best: ?array, active_months: int}
     */
    public function generate(ReportFilter $filter): array
    {
        $fees = $this->query->studentPayments($filter)
            ->tap(fn(Builder $query) => $this->groupByMonth($query, 'paid_at'))
            ->selectRaw('COUNT(*) as payments, COUNT(DISTINCT student_id) as students, COALESCE(SUM(amount), 0) as amount')
            ->get()
            ->keyBy('month');

        $split = $this->query->splitSnapshots($filter)
            ->tap(fn(Builder $query) => $this->groupByMonth($query, 'payment_date'))
            ->selectRaw('
                COALESCE(SUM(teacher_amount), 0) as teacher,
                COALESCE(SUM(organizer_amount), 0) as organizer,
                COALESCE(SUM(institution_amount), 0) as institute
            ')
            ->get()
            ->keyBy('month');

        $admissions = $this->sumByMonth($this->query->admissionPayments($filter), 'paid_at');
        $extraIncome = $this->sumByMonth($this->query->extraIncomes($filter), 'income_date');
        $teacherSalaries = $this->sumByMonth($this->query->teacherSalaries($filter), 'paid_at', 'net_amount');
        $teacherAdvances = $this->sumByMonth($this->query->teacherCashPayments($filter), 'payment_date');
        $organizerPayments = $this->sumByMonth($this->query->organizerPayments($filter), 'payment_date');
        $instituteExpenses = $this->sumByMonth($this->query->instituteExpenses($filter), 'payment_date');

        $year = (int) $filter->from->year;
        $lastMonth = (int) $filter->to->month;

        $rows = collect(range(1, 12))->map(function (int $month) use ($year, $lastMonth, $fees, $split, $admissions, $extraIncome, $teacherSalaries, $teacherAdvances, $organizerPayments, $instituteExpenses) {
            $fee = $fees->get($month);
            $share = $split->get($month);

            $row = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('F'),
                'short_name' => Carbon::create($year, $month, 1)->format('M'),
                'future' => $month > $lastMonth,
                'payments' => (int) optional($fee)->payments,
                'students' => (int) optional($fee)->students,
                'class_fees' => round((float) optional($fee)->amount, 2),
                'admission_fees' => round((float) $admissions->get($month, 0), 2),
                'extra_income' => round((float) $extraIncome->get($month, 0), 2),
                'teacher_share' => round((float) optional($share)->teacher, 2),
                'organizer_share' => round((float) optional($share)->organizer, 2),
                'institute_share' => round((float) optional($share)->institute, 2),
                'teacher_salaries' => round((float) $teacherSalaries->get($month, 0), 2),
                'teacher_advances' => round((float) $teacherAdvances->get($month, 0), 2),
                'organizer_payments' => round((float) $organizerPayments->get($month, 0), 2),
                'institute_expenses' => round((float) $instituteExpenses->get($month, 0), 2),
            ];

            $row['total_income'] = round($row['class_fees'] + $row['admission_fees'] + $row['extra_income'], 2);
            $row['total_expenses'] = round($row['teacher_salaries'] + $row['teacher_advances'] + $row['organizer_payments'] + $row['institute_expenses'], 2);
            $row['net'] = round($row['total_income'] - $row['total_expenses'], 2);

            return $row;
        });

        $totals = ['month_name' => 'Total', 'payments' => (int) $rows->sum('payments')];

        foreach (self::MONEY as $column) {
            $totals[$column] = round($rows->sum($column), 2);
        }

        // Unique students for the whole year (not the sum of each month)
        $totals['students'] = (int) $this->query->studentPayments($filter)->distinct()->count('student_id');

        $activeMonths = $rows->filter(fn($row) => $row['total_income'] > 0 || $row['total_expenses'] > 0)->count();
        $best = $rows->where('total_income', '>', 0)->sortByDesc('total_income')->first();

        $totals['average_income'] = $activeMonths > 0 ? round($totals['total_income'] / $activeMonths, 2) : 0.0;

        return [
            'rows' => $rows,
            'totals' => $totals,
            'best' => $best,
            'active_months' => $activeMonths,
        ];
    }

    /** Percentage change from $previous to $current (null when there is nothing to compare) */
    public function growth(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }

    /** Years shown in the year selector (newest first) */
    public function yearOptions(): array
    {
        $current = (int) now()->year;

        return range($current, $current - 5);
    }

    /** Excel / PDF table */
    public function table(): array
    {
        return [
            'headings' => [
                'Month',
                'Payments',
                'Students',
                'Class Fees (Rs.)',
                'Admission Fees (Rs.)',
                'Extra Income (Rs.)',
                'Total Income (Rs.)',
                'Teacher Share (Rs.)',
                'Organizer Share (Rs.)',
                'Institute Share (Rs.)',
                'Teacher Salaries (Rs.)',
                'Teacher Advances (Rs.)',
                'Organizer Payments (Rs.)',
                'Institute Expenses (Rs.)',
                'Total Expenses (Rs.)',
                'Net (Rs.)',
            ],
            'columns' => [
                'month_name',
                'payments',
                'students',
                'class_fees',
                'admission_fees',
                'extra_income',
                'total_income',
                'teacher_share',
                'organizer_share',
                'institute_share',
                'teacher_salaries',
                'teacher_advances',
                'organizer_payments',
                'institute_expenses',
                'total_expenses',
                'net',
            ],
            'money' => self::MONEY,
            'total' => 'total_income',
        ];
    }

    /** Data for the Chart.js chart on the page */
    public function chart(Collection $rows): array
    {
        return [
            'labels' => $rows->pluck('short_name')->values(),
            'income' => $rows->pluck('total_income')->values(),
            'expenses' => $rows->pluck('total_expenses')->values(),
            'net' => $rows->map(fn($row) => $row['future'] ? null : $row['net'])->values(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /** SUM(column) grouped by month number (1-12) */
    protected function sumByMonth(Builder $query, string $dateColumn, string $column = 'amount'): Collection
    {
        $table = $query->getModel()->getTable();

        return $query->tap(fn(Builder $inner) => $this->groupByMonth($inner, $dateColumn))
            ->selectRaw("COALESCE(SUM({$table}.{$column}), 0) as total")
            ->get()
            ->mapWithKeys(fn($row) => [(int) $row->month => (float) $row->total]);
    }

    /** Adds "<month expression> as month" to the select and groups by it */
    /** Adds "<month expression> as month" to the select and groups by it */
    protected function groupByMonth(Builder $query, string $dateColumn): void
    {
        $model = $query->getModel();
        $column = $model->getTable() . '.' . $dateColumn;

        $expression = $model->getConnection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "MONTH({$column})";

        $query->selectRaw("{$expression} as month")->groupByRaw($expression);
    }
}
