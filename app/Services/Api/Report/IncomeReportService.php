<?php

namespace App\Services\Api\Report;

use App\Models\CoursePayment;
use App\Models\TeacherPayment;
use App\Services\Web\Report\PaymentReportQuery;
use App\Services\Web\Report\ReportFilter;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Staff app "Income Report": the money that came in and went out in a date
 * range, to check the day's collection or a month.
 *
 *   Income   = class fees collected (hall fees are part of it)
 *            + course fees (institutes with courses) + admission fees
 *            + extra income
 *   Expenses = institute expenses (bills, rent ...)
 *            + teacher advances (paid from the day's collection)
 *   Net      = income - expenses
 *
 * Teacher salaries are not expenses here. Same queries as the web reports
 * (PaymentReportQuery), limited to the user's branches.
 */
class IncomeReportService
{
    public function __construct(protected PaymentReportQuery $query) {}

    public function filter(?string $from, ?string $to, ?int $branchId): ReportFilter
    {
        return $this->query->filter($from, $to, $branchId);
    }

    public function build(ReportFilter $filter): array
    {
        $classFees = $this->query->studentPayments($filter);
        $admissions = $this->query->admissionPayments($filter);
        $extra = $this->query->extraIncomes($filter);
        $expenses = $this->query->instituteExpenses($filter);
        $advances = $this->advances($filter);
        $courses = feature('courses') ? $this->coursePayments($filter) : null;

        $hall = $this->query->splitSnapshots($filter)
            ->where('hall_fee', '>', 0)
            ->selectRaw('COUNT(*) as payments, COALESCE(SUM(hall_fee), 0) as amount')
            ->first();

        $income = [
            'class_fees' => $this->total($classFees) + [
                'hall_fee' => $this->money($hall->amount ?? 0),
                'hall_fee_count' => (int) ($hall->payments ?? 0),
                'by_method' => $this->byMethod($classFees),
            ],
            'course_fees' => $courses ? $this->total($courses) : null,
            'admission' => $this->total($admissions),
            'extra_income' => $this->total($extra),
        ];
        $income['total'] = $this->money(
            $income['class_fees']['amount']
            + ($income['course_fees']['amount'] ?? 0)
            + $income['admission']['amount']
            + $income['extra_income']['amount']
        );

        $out = [
            'institute' => $this->total($expenses),
            'teacher_advances' => $this->total($advances),
        ];
        $out['total'] = $this->money($out['institute']['amount'] + $out['teacher_advances']['amount']);

        return [
            'from' => $filter->from->toDateString(),
            'to' => $filter->to->toDateString(),
            'branch' => $this->branchName($filter),
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'income' => $income,
            'expenses' => $out,
            'net' => $this->money($income['total'] - $out['total']),
            'days' => $this->days($filter, $classFees, $courses, $admissions, $extra, $expenses, $advances),
            'expense_items' => (clone $expenses)->orderBy('payment_date')->orderBy('id')->get()
                ->map(fn ($e) => [
                    'date' => optional($e->payment_date)->format('Y-m-d'),
                    'reason' => (string) ($e->reason ?? ''),
                    'note' => (string) ($e->note ?? ''),
                    'amount' => $this->money($e->amount),
                ])->values(),
            'advance_items' => (clone $advances)->with(['teacher' => fn ($q) => $q->withTrashed()])
                ->orderBy('payment_date')->orderBy('id')->get()
                ->map(fn ($a) => [
                    'date' => optional($a->payment_date)->format('Y-m-d'),
                    'teacher' => (string) (optional($a->teacher)->full_name ?? '-'),
                    'note' => (string) ($a->reason ?? $a->note ?? ''),
                    'amount' => $this->money($a->amount),
                ])->values(),
            'extra_items' => (clone $extra)->orderBy('income_date')->orderBy('id')->get()
                ->map(fn ($x) => [
                    'date' => optional($x->income_date)->format('Y-m-d'),
                    'reason' => (string) ($x->reason ?? ''),
                    'note' => (string) ($x->note ?? ''),
                    'amount' => $this->money($x->amount),
                ])->values(),
        ];
    }

    /* ------------------------------------------------------------------ */

    protected function advances(ReportFilter $filter): Builder
    {
        return TeacherPayment::query()
            ->where('payment_type', 'advance')
            ->where('status', '!=', 'cancelled')
            ->whereIn('branch_id', $filter->branchIds)
            ->whereBetween('payment_date', [$filter->from->toDateString(), $filter->to->toDateString()]);
    }

    protected function coursePayments(ReportFilter $filter): Builder
    {
        return CoursePayment::query()
            ->where('status', 'completed')
            ->whereIn('branch_id', $filter->branchIds)
            ->whereBetween('paid_at', [$filter->from, $filter->to]);
    }

    /** @return array{count: int, amount: float} */
    protected function total(Builder $query): array
    {
        $row = (clone $query)->toBase()->selectRaw('COUNT(*) as c, COALESCE(SUM(amount), 0) as a')->first();

        return ['count' => (int) ($row->c ?? 0), 'amount' => $this->money($row->a ?? 0)];
    }

    /** @return array<string, float> cash => 1000.00 ... */
    protected function byMethod(Builder $query): array
    {
        return (clone $query)->toBase()
            ->selectRaw('payment_method as m, COALESCE(SUM(amount), 0) as a')
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) ($row->m ?: 'other') => $this->money($row->a)])
            ->sortKeys()
            ->all();
    }

    /**
     * One row per day of the range (days with nothing are left out).
     */
    protected function days(ReportFilter $filter, Builder $classFees, ?Builder $courses, Builder $admissions, Builder $extra, Builder $expenses, Builder $advances): Collection
    {
        $sum = fn (Builder $query, string $column) => (clone $query)->toBase()
            ->selectRaw("DATE({$column}) as d, COALESCE(SUM(amount), 0) as a")
            ->groupBy('d')
            ->pluck('a', 'd');

        $parts = [
            'class_fees' => $sum($classFees, 'paid_at'),
            'course_fees' => $courses ? $sum($courses, 'paid_at') : collect(),
            'admission' => $sum($admissions, 'paid_at'),
            'extra_income' => $sum($extra, 'income_date'),
            'expenses' => $sum($expenses, 'payment_date'),
            'teacher_advances' => $sum($advances, 'payment_date'),
        ];

        return collect(CarbonPeriod::create($filter->from->copy()->startOfDay(), $filter->to->copy()->startOfDay()))
            ->map(function ($day) use ($parts) {
                $date = $day->toDateString();
                $row = ['date' => $date];
                foreach ($parts as $key => $values) {
                    $row[$key] = $this->money($values[$date] ?? 0);
                }
                $row['income'] = $this->money($row['class_fees'] + $row['course_fees'] + $row['admission'] + $row['extra_income']);
                $row['out'] = $this->money($row['expenses'] + $row['teacher_advances']);
                $row['net'] = $this->money($row['income'] - $row['out']);

                return $row;
            })
            ->filter(fn ($row) => $row['income'] != 0 || $row['out'] != 0)
            ->values();
    }

    protected function branchName(ReportFilter $filter): ?string
    {
        if (!$filter->branchId) {
            return null;
        }

        return \App\Models\Branch::query()->whereKey($filter->branchId)->value('name');
    }

    protected function money($value): float
    {
        return round((float) $value, 2);
    }
}
