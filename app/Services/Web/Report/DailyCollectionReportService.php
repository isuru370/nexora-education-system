<?php

namespace App\Services\Web\Report;

use App\Models\Branch;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Pagination\Paginator as PaginatorState;
use Illuminate\Support\Collection;

/**
 * Daily / date-range collection report.
 * (Replaces the old Admin\DailyReportController + DailyReportService report pages.)
 *
 * Tabs:
 *  - summary       income, expenses, net, by branch, by payment method, by collector, fee split
 *  - branches      income, expenses and net for each branch
 *  - students      class fee payments
 *  - admissions    admission fees
 *  - fee-split     teacher / organizer / institute share of every class fee payment
 *  - expenses      money paid out (teacher, organizer, institute)
 */
class DailyCollectionReportService
{
    public const TABS = [
        'summary' => 'Summary',
        'branches' => 'By Branch',
        'students' => 'Class Fee Payments',
        'admissions' => 'Admission Fees',
        'fee-split' => 'Fee Split',
        'expenses' => 'Expenses',
    ];

    /** Expense columns of a branch row (hidden while a collector / method filter is used) */
    protected const BRANCH_EXPENSE_COLUMNS = [
        'teacher_salaries',
        'teacher_advances',
        'organizer_payments',
        'institute_expenses',
        'total_expenses',
    ];

    public function __construct(
        protected PaymentReportQuery $query,
        protected BranchCollectionReportService $branchReport
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    public function summary(ReportFilter $filter): array
    {
        $studentTotal = $this->sum($this->query->studentPayments($filter));
        $admissionTotal = $this->sum($this->query->admissionPayments($filter));

        // Collector / method filters only apply to fee collections
        $extraIncomeTotal = $this->collectorFilterActive($filter) ? 0.0 : $this->sum($this->query->extraIncomes($filter));

        $teacherCash = $this->sum($this->query->teacherCashPayments($filter));
        $teacherSalaries = $this->sum($this->query->teacherSalaries($filter), 'net_amount');
        $organizerPayments = $this->sum($this->query->organizerPayments($filter));
        $instituteExpenses = $this->sum($this->query->instituteExpenses($filter));

        $showExpenses = !$this->collectorFilterActive($filter);

        $income = [
            'Class fee payments' => $studentTotal,
            'Admission fees' => $admissionTotal,
            'Extra income' => $extraIncomeTotal,
        ];

        $expenses = $showExpenses ? [
            'Teacher salaries' => $teacherSalaries,
            'Teacher advances / other' => $teacherCash,
            'Organizer payments' => $organizerPayments,
            'Institute expenses' => $instituteExpenses,
        ] : [];

        $totalIncome = round(array_sum($income), 2);
        $totalExpenses = round(array_sum($expenses), 2);

        $split = $this->query->splitSnapshots($filter)
            ->selectRaw('
                COALESCE(SUM(teacher_amount), 0) as teacher,
                COALESCE(SUM(organizer_amount), 0) as organizer,
                COALESCE(SUM(institution_amount), 0) as institute
            ')
            ->first();

        return [
            'income' => $income,
            'expenses' => $expenses,
            'show_expenses' => $showExpenses,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net' => round($totalIncome - $totalExpenses, 2),

            'student_payment_count' => $this->query->studentPayments($filter)->count(),
            'admission_count' => $this->query->admissionPayments($filter)->count(),

            'by_branch' => $this->branchRows($filter),
            'by_method' => $this->byMethod($filter),
            'by_collector' => $this->byCollector($filter),

            'split' => [
                'Teacher share' => round((float) $split->teacher, 2),
                'Organizer share' => round((float) $split->organizer, 2),
                'Institute share' => round((float) $split->institute, 2),
            ],
        ];
    }

    /**
     * Rows for the summary tab export.
     */
    public function summaryRows(ReportFilter $filter): array
    {
        $summary = $this->summary($filter);
        $rows = [];

        foreach ($summary['income'] as $label => $amount) {
            $rows[] = ['section' => 'Income', 'item' => $label, 'amount' => $amount];
        }

        $rows[] = ['section' => 'Income', 'item' => 'Total income', 'amount' => $summary['total_income']];

        foreach ($summary['expenses'] as $label => $amount) {
            $rows[] = ['section' => 'Expenses', 'item' => $label, 'amount' => $amount];
        }

        if ($summary['show_expenses']) {
            $rows[] = ['section' => 'Expenses', 'item' => 'Total expenses', 'amount' => $summary['total_expenses']];
            $rows[] = ['section' => 'Net', 'item' => 'Net balance', 'amount' => $summary['net']];
        }

        if ($summary['by_branch']->count() > 1) {
            foreach ($summary['by_branch'] as $row) {
                $rows[] = ['section' => 'By branch (income)', 'item' => $row['branch'], 'amount' => $row['total_income']];
            }
        }

        foreach ($summary['by_method'] as $row) {
            $rows[] = ['section' => 'By payment method', 'item' => $row['label'], 'amount' => $row['amount']];
        }

        foreach ($summary['by_collector'] as $row) {
            $rows[] = ['section' => 'By collector', 'item' => $row['name'], 'amount' => $row['amount']];
        }

        foreach ($summary['split'] as $label => $amount) {
            $rows[] = ['section' => 'Fee split', 'item' => $label, 'amount' => $amount];
        }

        return $rows;
    }

    /*
    |--------------------------------------------------------------------------
    | Detail tabs
    |--------------------------------------------------------------------------
    */

    /**
     * Table definition for a tab: headings, money columns and a row builder.
     */
    public function table(string $tab, ?ReportFilter $filter = null): array
    {
        if ($tab === 'branches') {
            return $this->branchTable($filter !== null && $this->collectorFilterActive($filter));
        }

        return match ($tab) {
            'summary' => [
                'headings' => ['Section', 'Item', 'Amount (Rs.)'],
                'columns' => ['section', 'item', 'amount'],
                'money' => ['amount'],
                'total' => null,
            ],
            'students' => [
                'headings' => ['Receipt No', 'Paid At', 'Student Code', 'Student', 'Class', 'Grade', 'Category', 'Fee Month', 'Method', 'Collected By', 'Amount (Rs.)'],
                'columns' => ['receipt_number', 'paid_at', 'student_code', 'student_name', 'class_name', 'grade_name', 'category_name', 'fee_month', 'payment_method', 'collected_by', 'amount'],
                'money' => ['amount'],
                'total' => 'amount',
            ],
            'admissions' => [
                'headings' => ['Receipt No', 'Paid At', 'Student Code', 'Student', 'Method', 'Collected By', 'Amount (Rs.)'],
                'columns' => ['receipt_number', 'paid_at', 'student_code', 'student_name', 'payment_method', 'collected_by', 'amount'],
                'money' => ['amount'],
                'total' => 'amount',
            ],
            'fee-split' => [
                'headings' => ['Paid At', 'Student', 'Class', 'Teacher', 'Organizer', 'Payment (Rs.)', 'Teacher (Rs.)', 'Organizer (Rs.)', 'Institute (Rs.)'],
                'columns' => ['paid_at', 'student_name', 'class_name', 'teacher_name', 'organizer_name', 'payment_amount', 'teacher_amount', 'organizer_amount', 'institution_amount'],
                'money' => ['payment_amount', 'teacher_amount', 'organizer_amount', 'institution_amount'],
                'total' => 'payment_amount',
            ],
            'expenses' => [
                'headings' => ['Date', 'Type', 'Paid To', 'Reason', 'Amount (Rs.)'],
                'columns' => ['date', 'type', 'paid_to', 'reason', 'amount'],
                'money' => ['amount'],
                'total' => 'amount',
            ],
        };
    }

    /**
     * Rows for a detail tab. Pass $perPage to get a paginator (screen),
     * or null to get every row (Excel / PDF).
     *
     * @return LengthAwarePaginator|Collection
     */
    public function rows(string $tab, ReportFilter $filter, ?int $perPage = null)
    {
        if ($tab === 'summary') {
            return collect($this->summaryRows($filter));
        }

        if ($tab === 'branches') {
            return $this->branchRows($filter);
        }

        if ($tab === 'expenses') {
            $rows = $this->collectorFilterActive($filter) ? collect() : $this->expenseRows($filter);

            return $perPage ? $this->paginateCollection($rows, $perPage) : $rows;
        }

        [$query, $mapper] = match ($tab) {
            'students' => [$this->studentPaymentQuery($filter), fn($payment) => $this->studentPaymentRow($payment)],
            'admissions' => [$this->admissionQuery($filter), fn($payment) => $this->admissionRow($payment)],
            'fee-split' => [$this->splitQuery($filter), fn($snapshot) => $this->splitRow($snapshot)],
        };

        if ($perPage) {
            /** @var Paginator $paginator */
            $paginator = $query->paginate($perPage);

            // Map each model on this page to a report row (keeps the page links)
            return $paginator->through($mapper);
        }

        return $query->get()->map($mapper);
    }

    /*
    |--------------------------------------------------------------------------
    | By branch
    |--------------------------------------------------------------------------
    */

    /**
     * One row per branch (same numbers as the Branch-wise Collection report).
     * While a collector / payment method filter is used, only fee collections
     * are counted, so extra income and expenses are set to 0 - the same rule
     * as the summary.
     */
    public function branchRows(ReportFilter $filter): Collection
    {
        $rows = $this->branchReport->generate($filter)['rows'];

        if (!$this->collectorFilterActive($filter)) {
            return $rows;
        }

        return $rows
            ->map(function (array $row) {
                $row['extra_income'] = 0.0;

                foreach (self::BRANCH_EXPENSE_COLUMNS as $column) {
                    $row[$column] = 0.0;
                }

                $row['total_income'] = round($row['class_fees'] + $row['admission_fees'], 2);
                $row['net'] = $row['total_income'];

                return $row;
            })
            // A shared (no branch) row with nothing left in it is dropped
            ->reject(fn(array $row) => $row['key'] === BranchCollectionReportService::SHARED && $row['total_income'] == 0)
            ->values();
    }

    protected function branchTable(bool $feesOnly): array
    {
        $columns = [
            'branch' => 'Branch',
            'payments' => 'Payments',
            'students' => 'Students',
            'class_fees' => 'Class Fees (Rs.)',
            'admission_fees' => 'Admission Fees (Rs.)',
            'extra_income' => 'Extra Income (Rs.)',
            'total_income' => 'Total Income (Rs.)',
            'teacher_salaries' => 'Teacher Salaries (Rs.)',
            'teacher_advances' => 'Teacher Advances (Rs.)',
            'organizer_payments' => 'Organizer Payments (Rs.)',
            'institute_expenses' => 'Institute Expenses (Rs.)',
            'total_expenses' => 'Total Expenses (Rs.)',
            'net' => 'Net (Rs.)',
        ];

        if ($feesOnly) {
            $hidden = array_merge(['extra_income', 'net'], self::BRANCH_EXPENSE_COLUMNS);
            $columns = array_diff_key($columns, array_flip($hidden));
        }

        return [
            'headings' => array_values($columns),
            'columns' => array_keys($columns),
            'money' => array_values(array_diff(array_keys($columns), ['branch', 'payments', 'students'])),
            'total' => 'total_income',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Queries + row mappers
    |--------------------------------------------------------------------------
    */

    protected function studentPaymentQuery(ReportFilter $filter): Builder
    {
        return $this->query->studentPayments($filter)
            ->with([
                'student' => fn($query) => $query->withTrashed()->select('id', 'custom_id', 'permanent_qr_active', 'temporary_qr_code', 'initial_name'),
                'collectedBy:id,name',
                'enrollment' => fn($query) => $query->withTrashed()->select('id', 'student_class_id', 'class_category_fee_id'),
                'enrollment.studentClass' => fn($query) => $query->withTrashed()->select('id', 'class_name', 'grade_id'),
                'enrollment.studentClass.grade:id,grade_name',
                'enrollment.classCategoryFee:id,class_category_id',
                'enrollment.classCategoryFee.category:id,category_name',
            ])
            ->orderBy('paid_at');
    }

    protected function studentPaymentRow($payment): array
    {
        $class = optional($payment->enrollment)->studentClass;

        return [
            'receipt_number' => $payment->receipt_number,
            'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i'),
            'student_code' => PaymentReportQuery::studentCode($payment->student),
            'student_name' => optional($payment->student)->initial_name,
            'class_name' => optional($class)->class_name,
            'grade_name' => optional(optional($class)->grade)->grade_name,
            'category_name' => optional(optional(optional($payment->enrollment)->classCategoryFee)->category)->category_name,
            'fee_month' => $payment->payment_month ? Carbon::parse($payment->payment_month)->format('M Y') : null,
            'payment_method' => $this->methodLabel($payment->payment_method),
            'collected_by' => optional($payment->collectedBy)->name,
            'amount' => (float) $payment->amount,
        ];
    }

    protected function admissionQuery(ReportFilter $filter): Builder
    {
        return $this->query->admissionPayments($filter)
            ->with([
                'student' => fn($query) => $query->withTrashed()->select('id', 'custom_id', 'permanent_qr_active', 'temporary_qr_code', 'initial_name'),
                'collectedBy:id,name',
            ])
            ->orderBy('paid_at');
    }

    protected function admissionRow($payment): array
    {
        return [
            'receipt_number' => $payment->receipt_number,
            'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i'),
            'student_code' => PaymentReportQuery::studentCode($payment->student),
            'student_name' => optional($payment->student)->initial_name,
            'payment_method' => $this->methodLabel($payment->payment_method),
            'collected_by' => optional($payment->collectedBy)->name,
            'amount' => (float) $payment->amount,
        ];
    }

    protected function splitQuery(ReportFilter $filter): Builder
    {
        return $this->query->splitSnapshots($filter)
            ->with([
                'teacher' => fn($query) => $query->withTrashed()->select('id', 'full_name'),
                'organizer' => fn($query) => $query->withTrashed()->select('id', 'name'),
                'studentClass' => fn($query) => $query->withTrashed()->select('id', 'class_name'),
                'enrollment' => fn($query) => $query->withTrashed()->select('id', 'student_id'),
                'enrollment.student' => fn($query) => $query->withTrashed()->select('id', 'initial_name'),
            ])
            ->orderBy('payment_date');
    }

    protected function splitRow($snapshot): array
    {
        return [
            'paid_at' => optional($snapshot->payment_date)->format('Y-m-d H:i'),
            'student_name' => optional(optional($snapshot->enrollment)->student)->initial_name,
            'class_name' => optional($snapshot->studentClass)->class_name,
            'teacher_name' => optional($snapshot->teacher)->full_name,
            'organizer_name' => optional($snapshot->organizer)->name,
            'payment_amount' => (float) $snapshot->payment_amount,
            'teacher_amount' => (float) $snapshot->teacher_amount,
            'organizer_amount' => (float) $snapshot->organizer_amount,
            'institution_amount' => (float) $snapshot->institution_amount,
        ];
    }

    protected function expenseRows(ReportFilter $filter): Collection
    {
        $teacherNames = fn(Collection $ids) => Teacher::withTrashed()->whereIn('id', $ids)->pluck('full_name', 'id');

        $teacherCash = $this->query->teacherCashPayments($filter)->get();
        $salaries = $this->query->teacherSalaries($filter)->get();
        $teachers = $teacherNames($teacherCash->pluck('teacher_id')->merge($salaries->pluck('teacher_id'))->unique());

        $rows = collect();

        foreach ($teacherCash as $payment) {
            $rows->push([
                'date' => optional($payment->payment_date)->format('Y-m-d'),
                'type' => 'Teacher ' . $payment->payment_type,
                'paid_to' => $teachers->get($payment->teacher_id),
                'reason' => $payment->reason,
                'amount' => (float) $payment->amount,
            ]);
        }

        foreach ($salaries as $salary) {
            $rows->push([
                'date' => optional($salary->paid_at)->format('Y-m-d'),
                'type' => 'Teacher salary',
                'paid_to' => $teachers->get($salary->teacher_id),
                'reason' => sprintf('Salary %04d-%02d', $salary->salary_year, $salary->salary_month),
                'amount' => (float) $salary->net_amount,
            ]);
        }

        foreach ($this->query->organizerPayments($filter)->with(['organizer' => fn($query) => $query->withTrashed()->select('id', 'name')])->get() as $payment) {
            $rows->push([
                'date' => optional($payment->payment_date)->format('Y-m-d'),
                'type' => 'Organizer ' . $payment->payment_type,
                'paid_to' => optional($payment->organizer)->name,
                'reason' => $payment->reason,
                'amount' => (float) $payment->amount,
            ]);
        }

        foreach ($this->query->instituteExpenses($filter)->get() as $expense) {
            $rows->push([
                'date' => optional($expense->payment_date)->format('Y-m-d'),
                'type' => 'Institute expense',
                'paid_to' => null,
                'reason' => $expense->reason,
                'amount' => (float) $expense->amount,
            ]);
        }

        return $rows->sortBy('date')->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function byMethod(ReportFilter $filter): array
    {
        $totals = [];

        foreach ([$this->query->studentPayments($filter), $this->query->admissionPayments($filter)] as $query) {
            $rows = $query->groupBy('payment_method')
                ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
                ->get();

            foreach ($rows as $row) {
                $key = $row->payment_method ?: 'other';
                $totals[$key]['amount'] = round(($totals[$key]['amount'] ?? 0) + (float) $row->total, 2);
                $totals[$key]['count'] = ($totals[$key]['count'] ?? 0) + (int) $row->count;
            }
        }

        return collect($totals)
            ->map(fn($value, $method) => ['label' => $this->methodLabel($method)] + $value)
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    protected function byCollector(ReportFilter $filter): array
    {
        $totals = [];

        foreach ([$this->query->studentPayments($filter), $this->query->admissionPayments($filter)] as $query) {
            $rows = $query->groupBy('user_id')
                ->selectRaw('user_id, SUM(amount) as total, COUNT(*) as count')
                ->get();

            foreach ($rows as $row) {
                $key = (int) $row->user_id;
                $totals[$key]['amount'] = round(($totals[$key]['amount'] ?? 0) + (float) $row->total, 2);
                $totals[$key]['count'] = ($totals[$key]['count'] ?? 0) + (int) $row->count;
            }
        }

        $names = User::query()->whereIn('id', array_keys($totals))->pluck('name', 'id');

        return collect($totals)
            ->map(fn($value, $userId) => ['name' => $names->get($userId, 'Unknown')] + $value)
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    protected function sum(Builder $query, string $column = 'amount'): float
    {
        return round((float) $query->sum($column), 2);
    }

    /**
     * Collector / payment method only exist on fee payments, so other income
     * and expenses are hidden when one of these filters is used.
     */
    public function collectorFilterActive(ReportFilter $filter): bool
    {
        return $filter->collectedBy !== null || $filter->paymentMethod !== null;
    }

    public function methodLabel(?string $method): string
    {
        return $method ? ucwords(str_replace('_', ' ', $method)) : 'Other';
    }

    public function branchName(ReportFilter $filter): string
    {
        return $filter->branchId
            ? (string) optional(Branch::query()->find($filter->branchId))->name
            : 'All branches';
    }

    protected function paginateCollection(Collection $rows, int $perPage): LengthAwarePaginator
    {
        $page = PaginatorState::resolveCurrentPage();

        return new Paginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => PaginatorState::resolveCurrentPath()]
        );
    }
}
