<?php

namespace App\Services\Web\Report;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Branch-wise collection report.
 *
 * One row per branch for a date range:
 *   Income   = class fees + admission fees + extra income
 *   Expenses = teacher salaries + teacher advances/other + organizer payments + institute expenses
 *   Net      = income - expenses
 * plus the class fee split (teacher / organizer / institute) and a
 * payment-method breakdown of fee collections (cash in hand per branch).
 *
 * Every number comes from PaymentReportQuery, so totals match the
 * Collection, Monthly and Institute Income reports.
 *
 * Records with no branch (shared institute records) are shown as a
 * separate "Shared (no branch)" row, only for ADMIN / SUPER_ADMIN.
 */
class BranchCollectionReportService
{
    /** Key used for records that have no branch */
    public const SHARED = 'shared';

    protected const MONEY = [
        'class_fees', 'admission_fees', 'extra_income', 'total_income',
        'teacher_share', 'organizer_share', 'institute_share',
        'teacher_salaries', 'teacher_advances', 'organizer_payments', 'institute_expenses',
        'total_expenses', 'net',
    ];

    public function __construct(
        protected PaymentReportQuery $query
    ) {}

    /**
     * @return array{rows: Collection, totals: array, methods: Collection, method_names: array}
     */
    public function generate(ReportFilter $filter): array
    {
        $fees = $this->query->studentPayments($filter)
            ->groupBy('branch_id')
            ->selectRaw('branch_id, COUNT(*) as payments, COUNT(DISTINCT student_id) as students, COALESCE(SUM(amount), 0) as amount')
            ->get()
            ->keyBy(fn ($row) => $this->key($row->branch_id));

        $split = $this->query->splitSnapshots($filter)
            ->groupBy('branch_id')
            ->selectRaw('
                branch_id,
                COALESCE(SUM(teacher_amount), 0) as teacher,
                COALESCE(SUM(organizer_amount), 0) as organizer,
                COALESCE(SUM(institution_amount), 0) as institute
            ')
            ->get()
            ->keyBy(fn ($row) => $this->key($row->branch_id));

        $admissions = $this->sumByBranch($this->query->admissionPayments($filter));
        $extraIncome = $this->sumByBranch($this->query->extraIncomes($filter));
        $teacherSalaries = $this->sumByBranch($this->query->teacherSalaries($filter), 'net_amount');
        $teacherAdvances = $this->sumByBranch($this->query->teacherCashPayments($filter));
        $organizerPayments = $this->sumByBranch($this->query->organizerPayments($filter));
        $instituteExpenses = $this->sumByBranch($this->query->instituteExpenses($filter));

        /*
        |----------------------------------------------------------------------
        | One row per branch (every branch in the filter, even with no activity)
        |----------------------------------------------------------------------
        */
        $branchNames = Branch::query()
            ->whereIn('id', $filter->branchIds)
            ->orderBy('name')
            ->pluck('name', 'id');

        $keys = $branchNames->keys()->map(fn ($id) => $this->key($id));

        // Shared (no branch) row only when such records exist in the result
        $sharedKeys = collect([$admissions, $extraIncome, $organizerPayments, $instituteExpenses])
            ->flatMap(fn (Collection $values) => $values->keys())
            ->filter(fn ($key) => $key === self::SHARED);

        if ($sharedKeys->isNotEmpty()) {
            $keys->push(self::SHARED);
        }

        $rows = $keys->map(function ($key) use ($branchNames, $fees, $split, $admissions, $extraIncome, $teacherSalaries, $teacherAdvances, $organizerPayments, $instituteExpenses) {
            $fee = $fees->get($key);
            $share = $split->get($key);

            $row = [
                'key' => $key,
                'branch' => $key === self::SHARED ? 'Shared (no branch)' : $branchNames->get((int) $key),
                'payments' => (int) optional($fee)->payments,
                'students' => (int) optional($fee)->students,
                'class_fees' => round((float) optional($fee)->amount, 2),
                'admission_fees' => round((float) $admissions->get($key, 0), 2),
                'extra_income' => round((float) $extraIncome->get($key, 0), 2),
                'teacher_share' => round((float) optional($share)->teacher, 2),
                'organizer_share' => round((float) optional($share)->organizer, 2),
                'institute_share' => round((float) optional($share)->institute, 2),
                'teacher_salaries' => round((float) $teacherSalaries->get($key, 0), 2),
                'teacher_advances' => round((float) $teacherAdvances->get($key, 0), 2),
                'organizer_payments' => round((float) $organizerPayments->get($key, 0), 2),
                'institute_expenses' => round((float) $instituteExpenses->get($key, 0), 2),
            ];

            $row['total_income'] = round($row['class_fees'] + $row['admission_fees'] + $row['extra_income'], 2);
            $row['total_expenses'] = round($row['teacher_salaries'] + $row['teacher_advances'] + $row['organizer_payments'] + $row['institute_expenses'], 2);
            $row['net'] = round($row['total_income'] - $row['total_expenses'], 2);

            return $row;
        })->values();

        $totals = ['branch' => 'Total', 'payments' => (int) $rows->sum('payments'), 'students' => (int) $rows->sum('students')];

        foreach (self::MONEY as $column) {
            $totals[$column] = round($rows->sum($column), 2);
        }

        $grandIncome = $totals['total_income'];

        $rows = $rows->map(function (array $row) use ($grandIncome) {
            $row['income_percent'] = $grandIncome > 0 ? round($row['total_income'] / $grandIncome * 100, 1) : 0.0;

            return $row;
        });

        [$methods, $methodNames] = $this->methodBreakdown($filter, $branchNames);

        return [
            'rows' => $rows,
            'totals' => $totals,
            'methods' => $methods,
            'method_names' => $methodNames,
        ];
    }

    /**
     * Fee collections (class fees + admission fees) per branch and payment method.
     *
     * @return array{0: Collection, 1: array}
     */
    protected function methodBreakdown(ReportFilter $filter, Collection $branchNames): array
    {
        $amounts = [];

        $queries = [
            $this->query->studentPayments($filter),
            $this->query->admissionPayments($filter),
        ];

        foreach ($queries as $query) {
            $query->groupBy('branch_id', 'payment_method')
                ->selectRaw('branch_id, payment_method, COALESCE(SUM(amount), 0) as amount')
                ->get()
                ->each(function ($row) use (&$amounts) {
                    $key = $this->key($row->branch_id);
                    $method = $row->payment_method ?: 'other';
                    $amounts[$key][$method] = ($amounts[$key][$method] ?? 0) + (float) $row->amount;
                });
        }

        $methodNames = collect($amounts)
            ->flatMap(fn ($methods) => array_keys($methods))
            ->unique()
            ->sortBy(fn ($method) => array_search($method, PaymentReportQuery::PAYMENT_METHODS, true))
            ->values()
            ->all();

        $rows = collect($amounts)
            ->map(function (array $methods, $key) use ($branchNames, $methodNames) {
                $row = ['branch' => $key === self::SHARED ? 'Shared (no branch)' : $branchNames->get((int) $key)];

                foreach ($methodNames as $method) {
                    $row[$method] = round($methods[$method] ?? 0, 2);
                }

                $row['total'] = round(array_sum($methods), 2);

                return $row;
            })
            ->sortBy('branch')
            ->values();

        return [$rows, $methodNames];
    }

    /** Excel / PDF table */
    public function table(): array
    {
        return [
            'headings' => [
                'Branch', 'Payments', 'Students',
                'Class Fees (Rs.)', 'Admission Fees (Rs.)', 'Extra Income (Rs.)', 'Total Income (Rs.)',
                'Teacher Share (Rs.)', 'Organizer Share (Rs.)', 'Institute Share (Rs.)',
                'Teacher Salaries (Rs.)', 'Teacher Advances (Rs.)', 'Organizer Payments (Rs.)', 'Institute Expenses (Rs.)',
                'Total Expenses (Rs.)', 'Net (Rs.)',
            ],
            'columns' => [
                'branch', 'payments', 'students',
                'class_fees', 'admission_fees', 'extra_income', 'total_income',
                'teacher_share', 'organizer_share', 'institute_share',
                'teacher_salaries', 'teacher_advances', 'organizer_payments', 'institute_expenses',
                'total_expenses', 'net',
            ],
            'money' => self::MONEY,
            'total' => 'total_income',
        ];
    }

    public function label(string $method): string
    {
        return ucwords(str_replace('_', ' ', $method));
    }

    /**
     * SUM(column) grouped by branch_id, keyed by branch id ("shared" for NULL).
     */
    protected function sumByBranch(Builder $query, string $column = 'amount'): Collection
    {
        return $query->groupBy('branch_id')
            ->selectRaw("branch_id, COALESCE(SUM({$column}), 0) as total")
            ->get()
            ->mapWithKeys(fn ($row) => [$this->key($row->branch_id) => (float) $row->total]);
    }

    protected function key($branchId): string
    {
        return $branchId === null ? self::SHARED : (string) (int) $branchId;
    }
}