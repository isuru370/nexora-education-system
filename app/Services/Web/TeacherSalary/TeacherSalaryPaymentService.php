<?php

namespace App\Services\Web\TeacherSalary;

use App\Models\Teacher;
use App\Models\TeacherPayment;
use App\Models\TeacherSalary;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Write side of teacher salaries:
 *  - pay a month's salary
 *  - add / delete an advance, deduction or other payment
 *
 * Every write locks the teacher row first, so two admins (or web + mobile)
 * cannot pay twice or go over the advance limit at the same time.
 *
 * Throws DomainException with a user-friendly message when a rule is broken.
 */
class TeacherSalaryPaymentService
{
    public function __construct(
        protected TeacherSalaryCalculator $calculator,
        protected TeacherSalaryService $salaryService
    ) {}

    /**
     * Pay the salary of one teacher for one branch and month.
     *
     * @param  float|null  $expectedNet  Net amount shown on the page. If the amount changed
     *                                   after the page was opened, payment is stopped.
     *
     * @throws DomainException
     */
    public function paySalary(
        int $teacherId,
        int $branchId,
        int $year,
        int $month,
        ?float $expectedNet = null,
        ?string $note = null
    ): TeacherSalary {
        $this->salaryService->assertBranchAccess($branchId);

        return DB::transaction(function () use ($teacherId, $branchId, $year, $month, $expectedNet, $note) {
            $this->lockTeacher($teacherId);

            $record = TeacherSalary::withTrashed()
                ->where('branch_id', $branchId)
                ->where('teacher_id', $teacherId)
                ->where('salary_year', $year)
                ->where('salary_month', $month)
                ->lockForUpdate()
                ->first();

            $activeRecord = ($record && !$record->trashed()) ? $record : null;

            $totals = $this->calculator->forTeacher($teacherId, $branchId, $year, $month);

            $blockReason = $this->calculator->payBlockReason($year, $month, $activeRecord, $totals);

            if ($blockReason !== null) {
                throw new DomainException($blockReason);
            }

            if ($expectedNet !== null && abs($expectedNet - $totals['net']) >= 0.01) {
                throw new DomainException(
                    'The salary amount changed after this page was opened. New net amount: LKR '
                    . number_format($totals['net'], 2) . '. Please check and pay again.'
                );
            }

            $monthLabel = Carbon::create($year, $month, 1)->format('F Y');

            $values = [
                'user_id' => auth()->id(),
                'gross_amount' => $totals['gross'],
                'advance_deduction' => $totals['advance'],
                'other_deduction' => round($totals['deduction'] + $totals['other'], 2),
                'welfare_deduction' => $totals['welfare'],
                'bonus_amount' => 0,
                'net_amount' => $totals['net'],
                'status' => 'paid',
                'paid_at' => now(),
                'note' => 'Salary paid for ' . $monthLabel . ($note ? ' | ' . $note : ''),
            ];

            if ($record) {
                // Re-use the row (also a soft-deleted one) because of the unique key
                if ($record->trashed()) {
                    $record->restore();
                }

                $record->fill($values)->save();

                return $record->refresh();
            }

            return TeacherSalary::create($values + [
                'branch_id' => $branchId,
                'teacher_id' => $teacherId,
                'salary_year' => $year,
                'salary_month' => $month,
            ]);
        });
    }

    /**
     * Add an advance / deduction / other payment.
     *
     * @param  array{payment_type: string, amount: float|string, payment_date: string, reason?: string|null, note?: string|null}  $data
     *
     * @throws DomainException
     */
    public function storePayment(int $teacherId, int $branchId, array $data): TeacherPayment
    {
        $this->salaryService->assertBranchAccess($branchId);

        if (!in_array($data['payment_type'], TeacherSalaryCalculator::PAYMENT_TYPES, true)) {
            throw new DomainException('Invalid payment type.');
        }

        $amount = round((float) $data['amount'], 2);

        if ($amount <= 0) {
            throw new DomainException('Amount must be greater than zero.');
        }

        $date = Carbon::parse($data['payment_date'])->startOfDay();

        if ($date->greaterThan(now()->endOfDay())) {
            throw new DomainException('Payment date cannot be in the future.');
        }

        return DB::transaction(function () use ($teacherId, $branchId, $data, $amount, $date) {
            $this->lockTeacher($teacherId);

            $year = $date->year;
            $month = $date->month;

            $record = $this->calculator->salaryRecord($teacherId, $branchId, $year, $month, true);

            $blockReason = $this->calculator->paymentBlockReason($year, $month, $record);

            if ($blockReason !== null) {
                throw new DomainException($blockReason);
            }

            $totals = $this->calculator->forTeacher($teacherId, $branchId, $year, $month);

            if ($amount > $totals['available_limit']) {
                throw new DomainException(
                    'Amount exceeds the available limit for ' . $date->format('F Y')
                    . '. Available: LKR ' . number_format($totals['available_limit'], 2)
                );
            }

            return TeacherPayment::create([
                'branch_id' => $branchId,
                'teacher_id' => $teacherId,
                'user_id' => auth()->id(),
                'payment_type' => $data['payment_type'],
                'amount' => $amount,
                'payment_date' => $date->toDateString(),
                'reason' => $data['reason'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => 'paid',
            ]);
        });
    }

    /**
     * Soft delete an advance / deduction / other payment.
     * Not allowed after that month's salary is paid.
     *
     * @throws DomainException
     */
    public function deletePayment(int $paymentId): TeacherPayment
    {
        return DB::transaction(function () use ($paymentId) {
            /** @var TeacherPayment $payment */
            $payment = TeacherPayment::query()
                ->lockForUpdate()
                ->findOrFail($paymentId);

            $this->salaryService->assertBranchAccess((int) $payment->branch_id);

            $this->lockTeacher((int) $payment->teacher_id);

            $date = Carbon::parse($payment->payment_date);

            $record = $this->calculator->salaryRecord(
                (int) $payment->teacher_id,
                (int) $payment->branch_id,
                $date->year,
                $date->month,
                true
            );

            if ($record && $record->status === 'paid') {
                throw new DomainException(
                    'Cannot delete this record. Salary for ' . $date->format('F Y') . ' is already paid.'
                );
            }

            $payment->delete();

            return $payment;
        });
    }

    protected function lockTeacher(int $teacherId): void
    {
        Teacher::withTrashed()
            ->whereKey($teacherId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}