<?php

namespace App\Services\Web\Report;

use App\Models\Payment;
use App\Models\PaymentSplitSnapshot;
use App\Models\StudentClassEnrollment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Monthly collection report: expected vs collected class fees for one fee month,
 * grouped by Class + Category.
 *
 * Expected  = final fee of every enrollment that was active in that month (free card = 0)
 * Collected = completed payments whose payment_month is that month
 * Split     = teacher / organizer / institute share of the collected payments
 */
class MonthlyCollectionReportService
{
    /**
     * Money columns used by the day-by-day breakdown.
     */
    protected const DAILY_MONEY = [
        'class_fees', 'admission_fees', 'extra_income', 'total_income',
        'teacher_salaries', 'teacher_advances', 'organizer_payments', 'institute_expenses',
        'total_expenses', 'net',
    ];

    public function __construct(
        protected PaymentReportQuery $query
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Monthly breakdown
    |--------------------------------------------------------------------------
    */

    public function generate(ReportFilter $filter, Carbon $month): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd   = $month->copy()->endOfMonth();

        /*
        |----------------------------------------------------------------------
        | Collected per enrollment (one grouped query)
        |----------------------------------------------------------------------
        */
        $paymentsQuery = Payment::query()
            ->where('status', 'completed')
            ->whereIn('branch_id', $filter->branchIds)
            ->whereBetween('payment_month', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $collectedByEnrollment = (clone $paymentsQuery)
            ->groupBy('student_class_enrollment_id')
            ->selectRaw('student_class_enrollment_id, SUM(amount) as total')
            ->pluck('total', 'student_class_enrollment_id');

        $splitByEnrollment = PaymentSplitSnapshot::query()
            ->whereIn('payment_id', (clone $paymentsQuery)->select('id'))
            ->groupBy('student_class_enrollment_id')
            ->selectRaw('
                student_class_enrollment_id,
                SUM(teacher_amount) as teacher,
                SUM(organizer_amount) as organizer,
                SUM(institution_amount) as institute
            ')
            ->get()
            ->keyBy('student_class_enrollment_id');

        /*
        |----------------------------------------------------------------------
        | Enrollments for the month
        |----------------------------------------------------------------------
        | Active during the month (enrolled before month end, not left before
        | month start) OR paid for this month (so no collected money is lost
        | when a student left early).
        */
        $enrollments = StudentClassEnrollment::withTrashed()
            ->whereIn('branch_id', $filter->branchIds)
            ->where(function ($query) use ($monthStart, $monthEnd, $collectedByEnrollment) {
                $query->where(function ($active) use ($monthStart, $monthEnd) {
                    $active->whereNull('deleted_at')
                        ->where(function ($enrolled) use ($monthEnd) {
                            $enrolled->whereNull('enrolled_at')
                                ->orWhereDate('enrolled_at', '<=', $monthEnd->toDateString());
                        })
                        ->where(function ($left) use ($monthStart) {
                            $left->whereNull('left_at')
                                ->orWhereDate('left_at', '>=', $monthStart->toDateString());
                        });
                })
                    ->orWhereIn('id', $collectedByEnrollment->keys());
            })
            ->with([
                'studentClass' => fn ($query) => $query->withTrashed()->select('id', 'class_name', 'grade_id'),
                'studentClass.grade:id,grade_name',
                'classCategoryFee' => fn ($query) => $query->withTrashed()->select('id', 'class_category_id'),
                'classCategoryFee.category:id,category_name',
                'classCategoryFeeOption:id,class_category_fee_id,label,fee',
            ])
            ->get();

        /*
        |----------------------------------------------------------------------
        | Rows: Class + Category
        |----------------------------------------------------------------------
        */
        $rows = $enrollments
            ->groupBy(fn ($enrollment) => $enrollment->student_class_id . '_' . $enrollment->class_category_fee_id)
            ->map(function (Collection $group) use ($collectedByEnrollment, $splitByEnrollment) {
                $first = $group->first();
                $class = $first->studentClass;
                $ids   = $group->pluck('id');

                $expected  = round($group->sum(fn ($enrollment) => (float) $enrollment->final_fee), 2);
                $collected = round($ids->sum(fn ($id) => (float) $collectedByEnrollment->get($id, 0)), 2);
                $due       = round(max($expected - $collected, 0), 2);

                $split = fn (string $key) => round(
                    $ids->sum(fn ($id) => (float) optional($splitByEnrollment->get($id))->{$key}),
                    2
                );

                return [
                    'class_name'            => optional($class)->class_name ?: 'Unknown class',
                    'grade_name'            => optional(optional($class)->grade)->grade_name ?: '-',
                    'category_name'         => optional(optional($first->classCategoryFee)->category)->category_name ?: 'Unknown category',
                    'fee_options'           => $group
                        ->map(fn ($enrollment) => $enrollment->classCategoryFeeOption)
                        ->filter()
                        ->unique('id')
                        ->map(fn ($option) => $option->label . ' (' . number_format((float) $option->fee, 2) . ')')
                        ->implode(', '),
                    'student_count'         => $group->count(),
                    'free_cards'            => $group->where('is_free_card', true)->count(),
                    'paid_students'         => $ids->filter(fn ($id) => (float) $collectedByEnrollment->get($id, 0) > 0)->count(),
                    'expected'              => $expected,
                    'collected'             => $collected,
                    'due'                   => $due,
                    'collection_percentage' => $this->percent($collected, $expected),
                    'teacher_amount'        => $split('teacher'),
                    'organizer_amount'      => $split('organizer'),
                    'institution_amount'    => $split('institute'),
                ];
            })
            ->sortBy([['class_name', 'asc'], ['category_name', 'asc']])
            ->values();

        $expected  = round($rows->sum('expected'), 2);
        $collected = round($rows->sum('collected'), 2);
        $due       = round(max($expected - $collected, 0), 2);

        return [
            'month_label' => $monthStart->format('F Y'),
            'month'       => $monthStart->format('Y-m'),
            'summary'     => [
                'student_count'         => $enrollments->count(),
                'paid_students'         => $rows->sum('paid_students'),
                'expected'              => $expected,
                'collected'             => $collected,
                'due'                   => $due,
                'collection_percentage' => $this->percent($collected, $expected),
                'due_percentage'        => $this->percent($due, $expected),
                'teacher_amount'        => round($rows->sum('teacher_amount'), 2),
                'organizer_amount'      => round($rows->sum('organizer_amount'), 2),
                'institution_amount'    => round($rows->sum('institution_amount'), 2),
            ],
            'rows'        => $rows,
        ];
    }

    public function table(): array
    {
        return [
            'headings' => [
                'Class', 'Grade', 'Category', 'Fee Options', 'Students', 'Paid',
                'Expected (Rs.)', 'Collected (Rs.)', 'Due (Rs.)', 'Collected %',
                'Teacher (Rs.)', 'Organizer (Rs.)', 'Institute (Rs.)',
            ],
            'columns' => [
                'class_name', 'grade_name', 'category_name', 'fee_options', 'student_count', 'paid_students',
                'expected', 'collected', 'due', 'collection_percentage',
                'teacher_amount', 'organizer_amount', 'institution_amount',
            ],
            'money' => ['expected', 'collected', 'due', 'teacher_amount', 'organizer_amount', 'institution_amount'],
            'total' => 'collected',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Day by day
    |--------------------------------------------------------------------------
    */

    /**
     * One row per day of the month (up to today for the current month).
     * By paid date (any fee month) - same numbers as the Collection report.
     *
     * @return array{rows: Collection, totals: array, best: ?array}
     */
    public function dailyBreakdown(ReportFilter $filter): array
    {
        $fees = $this->query->studentPayments($filter)
            ->tap(fn (Builder $query) => $this->groupByDay($query, 'paid_at'))
            ->selectRaw('COUNT(*) as payments, COALESCE(SUM(amount), 0) as amount')
            ->get()
            ->keyBy('day');

        $admissions        = $this->sumByDay($this->query->admissionPayments($filter), 'paid_at');
        $extraIncome       = $this->sumByDay($this->query->extraIncomes($filter), 'income_date');
        $teacherSalaries   = $this->sumByDay($this->query->teacherSalaries($filter), 'paid_at', 'net_amount');
        $teacherAdvances   = $this->sumByDay($this->query->teacherCashPayments($filter), 'payment_date');
        $organizerPayments = $this->sumByDay($this->query->organizerPayments($filter), 'payment_date');
        $instituteExpenses = $this->sumByDay($this->query->instituteExpenses($filter), 'payment_date');

        $lastDay = $filter->to->copy()->min(now())->startOfDay();
        $days    = $lastDay->lt($filter->from)
            ? []
            : CarbonPeriod::create($filter->from->copy()->startOfDay(), $lastDay);

        $rows = collect($days)->map(function (Carbon $date) use (
            $fees,
            $admissions,
            $extraIncome,
            $teacherSalaries,
            $teacherAdvances,
            $organizerPayments,
            $instituteExpenses
        ) {
            $key = $date->toDateString();
            $fee = $fees->get($key);

            $row = [
                'date'                => $key,
                'date_label'          => $date->format('d M Y'),
                'day_name'            => $date->format('D'),
                'payments'            => (int) optional($fee)->payments,
                'class_fees'          => round((float) optional($fee)->amount, 2),
                'admission_fees'      => round((float) $admissions->get($key, 0), 2),
                'extra_income'        => round((float) $extraIncome->get($key, 0), 2),
                'teacher_salaries'    => round((float) $teacherSalaries->get($key, 0), 2),
                'teacher_advances'    => round((float) $teacherAdvances->get($key, 0), 2),
                'organizer_payments'  => round((float) $organizerPayments->get($key, 0), 2),
                'institute_expenses'  => round((float) $instituteExpenses->get($key, 0), 2),
            ];

            $row['total_income']   = round($row['class_fees'] + $row['admission_fees'] + $row['extra_income'], 2);
            $row['total_expenses'] = round($row['teacher_salaries'] + $row['teacher_advances'] + $row['organizer_payments'] + $row['institute_expenses'], 2);
            $row['net']            = round($row['total_income'] - $row['total_expenses'], 2);
            $row['empty']          = $row['total_income'] == 0 && $row['total_expenses'] == 0;

            return $row;
        })->values();

        $totals = ['payments' => (int) $rows->sum('payments')];

        foreach (self::DAILY_MONEY as $column) {
            $totals[$column] = round($rows->sum($column), 2);
        }

        $activeDays = $rows->where('total_income', '>', 0)->count();
        $totals['active_days']    = $activeDays;
        $totals['average_income'] = $activeDays > 0 ? round($totals['total_income'] / $activeDays, 2) : 0.0;

        return [
            'rows'   => $rows,
            'totals' => $totals,
            'best'   => $rows->where('total_income', '>', 0)->sortByDesc('total_income')->first(),
        ];
    }

    /**
     * Excel / PDF table for the day-by-day breakdown.
     */
    public function dailyTable(): array
    {
        return [
            'headings' => [
                'Date', 'Day', 'Payments',
                'Class Fees (Rs.)', 'Admission Fees (Rs.)', 'Extra Income (Rs.)', 'Total Income (Rs.)',
                'Teacher Salaries (Rs.)', 'Teacher Advances (Rs.)', 'Organizer Payments (Rs.)', 'Institute Expenses (Rs.)',
                'Total Expenses (Rs.)', 'Net (Rs.)',
            ],
            'columns' => [
                'date_label', 'day_name', 'payments',
                'class_fees', 'admission_fees', 'extra_income', 'total_income',
                'teacher_salaries', 'teacher_advances', 'organizer_payments', 'institute_expenses',
                'total_expenses', 'net',
            ],
            'money' => self::DAILY_MONEY,
            'total' => 'total_income',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /** SUM(column) grouped by day (Y-m-d). */
    protected function sumByDay(Builder $query, string $dateColumn, string $column = 'amount'): Collection
    {
        $table = $query->getModel()->getTable();

        return $query->tap(fn (Builder $inner) => $this->groupByDay($inner, $dateColumn))
            ->selectRaw("COALESCE(SUM({$table}.{$column}), 0) as total")
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->day => (float) $row->total]);
    }

    /** Adds "DATE(column) as day" to the select and groups by it (MySQL + SQLite). */
    protected function groupByDay(Builder $query, string $dateColumn): void
    {
        $expression = 'DATE(' . $query->getModel()->getTable() . '.' . $dateColumn . ')';

        $query->selectRaw("{$expression} as day")->groupByRaw($expression);
    }

    protected function percent(float $part, float $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }
}