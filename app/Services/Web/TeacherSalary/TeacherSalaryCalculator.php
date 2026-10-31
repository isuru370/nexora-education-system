<?php

namespace App\Services\Web\TeacherSalary;

use App\Models\PaymentSplitSnapshot;
use App\Models\TeacherPayment;
use App\Models\TeacherSalary;
use App\Models\WelfareDeduction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * All salary amounts are calculated here, so every page,
 * the salary payment and the slip use the same numbers.
 *
 * Everything is per branch + teacher + month, because
 * teacher_salaries is unique on (branch_id, teacher_id, salary_year, salary_month).
 */
class TeacherSalaryCalculator
{
    public const PAYMENT_TYPES = ['advance', 'deduction', 'other'];

    /**
     * Number of days before month end when the current month's salary can be paid.
     */
    public const PAY_WINDOW_DAYS = 5;

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function monthRange(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    /**
     * Teacher earnings (teacher_amount) per teacher for one branch and month.
     *
     * @param  array<int>|null  $teacherIds
     * @return Collection<int, float>  [teacher_id => amount]
     */
    public function grossByTeacher(int $branchId, int $year, int $month, ?array $teacherIds = null): Collection
    {
        [$start, $end] = $this->monthRange($year, $month);

        return PaymentSplitSnapshot::query()
            ->where('branch_id', $branchId)
            ->whereBetween('payment_date', [$start, $end])
            ->when($teacherIds !== null, fn ($query) => $query->whereIn('teacher_id', $teacherIds))
            ->groupBy('teacher_id')
            ->selectRaw('teacher_id, SUM(teacher_amount) as total')
            ->pluck('total', 'teacher_id')
            ->map(fn ($total) => round((float) $total, 2));
    }

    /**
     * Advance / deduction / other / welfare totals per teacher for one branch and month.
     * Cancelled records are ignored. Soft-deleted records are ignored automatically.
     * Welfare (feature "welfare") is kept per salary month in welfare_deductions.
     *
     * @param  array<int>|null  $teacherIds
     * @return Collection<int, array{advance: float, deduction: float, other: float, welfare: float}>
     */
    public function deductionsByTeacher(int $branchId, int $year, int $month, ?array $teacherIds = null): Collection
    {
        [$start, $end] = $this->monthRange($year, $month);

        $rows = TeacherPayment::query()
            ->where('branch_id', $branchId)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->when($teacherIds !== null, fn ($query) => $query->whereIn('teacher_id', $teacherIds))
            ->groupBy('teacher_id', 'payment_type')
            ->selectRaw('teacher_id, payment_type, SUM(amount) as total')
            ->get();

        $welfare = WelfareDeduction::query()
            ->where('branch_id', $branchId)
            ->where('salary_year', $year)
            ->where('salary_month', $month)
            ->when($teacherIds !== null, fn ($query) => $query->whereIn('teacher_id', $teacherIds))
            ->groupBy('teacher_id')
            ->selectRaw('teacher_id, SUM(amount) as total')
            ->pluck('total', 'teacher_id');

        $result = $rows
            ->groupBy('teacher_id')
            ->map(function (Collection $teacherRows) {
                $totals = $this->emptyDeductions();

                foreach ($teacherRows as $row) {
                    if (in_array($row->payment_type, self::PAYMENT_TYPES, true)) {
                        $totals[$row->payment_type] = round((float) $row->total, 2);
                    }
                }

                return $totals;
            });

        foreach ($welfare as $teacherId => $total) {
            $totals = $result->get($teacherId, $this->emptyDeductions());
            $totals['welfare'] = round((float) $total, 2);
            $result->put($teacherId, $totals);
        }

        return $result;
    }

    /**
     * Full calculation for one teacher in one branch and month.
     *
     * @return array{gross: float, advance: float, deduction: float, other: float, welfare: float, total_deductions: float, net: float, available_limit: float}
     */
    public function forTeacher(int $teacherId, int $branchId, int $year, int $month): array
    {
        $gross = (float) $this->grossByTeacher($branchId, $year, $month, [$teacherId])
            ->get($teacherId, 0.0);

        $deductions = $this->deductionsByTeacher($branchId, $year, $month, [$teacherId])
            ->get($teacherId, $this->emptyDeductions());

        return $this->buildTotals($gross, $deductions);
    }

    /**
     * @param  array{advance: float, deduction: float, other: float, welfare?: float}  $deductions
     */
    public function buildTotals(float $gross, array $deductions): array
    {
        $deductions += ['welfare' => 0.0];

        $totalDeductions = round(
            $deductions['advance'] + $deductions['deduction'] + $deductions['other'] + $deductions['welfare'],
            2
        );

        $net = round($gross - $totalDeductions, 2);

        return [
            'gross' => round($gross, 2),
            'advance' => $deductions['advance'],
            'deduction' => $deductions['deduction'],
            'other' => $deductions['other'],
            'welfare' => $deductions['welfare'],
            'total_deductions' => $totalDeductions,
            'net' => $net,
            // How much more can still be given as advance / deduction this month
            'available_limit' => max($net, 0),
        ];
    }

    /**
     * @return array{advance: float, deduction: float, other: float, welfare: float}
     */
    public function emptyDeductions(): array
    {
        return ['advance' => 0.0, 'deduction' => 0.0, 'other' => 0.0, 'welfare' => 0.0];
    }

    public function salaryRecord(
        int $teacherId,
        int $branchId,
        int $year,
        int $month,
        bool $lockForUpdate = false
    ): ?TeacherSalary {
        return TeacherSalary::query()
            ->where('branch_id', $branchId)
            ->where('teacher_id', $teacherId)
            ->where('salary_year', $year)
            ->where('salary_month', $month)
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    /**
     * Why the salary cannot be paid right now, or null when it can be paid.
     */
    public function payBlockReason(int $year, int $month, ?TeacherSalary $record, array $totals): ?string
    {
        if ($record && $record->status === 'paid') {
            return 'Salary for this month is already paid.';
        }

        [$start, $end] = $this->monthRange($year, $month);
        $now = now();

        if ($start->greaterThan($now)) {
            return 'Salary cannot be paid for a future month.';
        }

        $isCurrentMonth = $now->between($start, $end);
        $windowOpensAt = $end->copy()->subDays(self::PAY_WINDOW_DAYS)->startOfDay();

        if ($isCurrentMonth && $now->lessThan($windowOpensAt)) {
            return 'Salary can be paid from ' . $windowOpensAt->format('d M Y')
                . ' (last ' . self::PAY_WINDOW_DAYS . ' days of the month).';
        }

        if ($totals['gross'] <= 0) {
            return 'There are no earnings for this month.';
        }

        if ($totals['net'] < 0) {
            return 'Advances and deductions (LKR ' . number_format($totals['total_deductions'], 2)
                . ') are higher than earnings (LKR ' . number_format($totals['gross'], 2)
                . '). Delete or reduce a record first.';
        }

        return null;
    }

    /**
     * Why an advance / deduction cannot be added for this month, or null when it can.
     */
    public function paymentBlockReason(int $year, int $month, ?TeacherSalary $record): ?string
    {
        [$start] = $this->monthRange($year, $month);

        if ($record && $record->status === 'paid') {
            return 'Salary for ' . $start->format('F Y')
                . ' is already paid. Advances and deductions cannot be added.';
        }

        if ($start->greaterThan(now())) {
            return 'Advances and deductions cannot be added for a future month.';
        }

        return null;
    }
}