<?php

namespace App\Services\Web\Organizer;

use App\Models\Branch;
use App\Models\Organizer;
use App\Models\OrganizerPayment;
use App\Models\PaymentSplitSnapshot;
use App\Services\Branch\BranchAccessService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Organizer salary, per branch + month (as teacher salaries).
 *
 * Earned  = organizer share (organizer_amount) of class fees, by the date
 *           the fee was paid (payment_split_snapshots.payment_date)
 * Given   = advance / deduction / other (by their date) and salaries paid
 *
 * The salary of a month pays what is still owed for everything up to the
 * end of that month:
 *
 *   net = earned up to month end
 *         - advance / deduction / other up to month end
 *         - every salary already paid (any month)
 *
 * So a fee paid after a month's salary goes into the next salary, an
 * earlier month that was not paid is paid with the next one, and nothing is
 * paid twice (paying an older month after a newer one finds nothing owed).
 */
class OrganizerSalaryService
{
    public const ADJUSTMENT_TYPES = ['advance', 'deduction', 'other'];

    /** The current month's salary can be paid in its last days (as teachers). */
    public const PAY_WINDOW_DAYS = 5;

    public function __construct(
        protected BranchAccessService $branchAccess
    ) {}

    /* ------------------------------------------------------------ branches */

    /** Branches this user can open (ADMIN: all). */
    public function branchOptions(): Collection
    {
        return Branch::query()
            ->whereIn('id', $this->branchAccess->getBranchIds())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** @throws DomainException */
    public function resolveBranchId(?int $branchId): int
    {
        $options = $this->branchOptions();

        if ($options->isEmpty()) {
            throw new DomainException('You do not have access to any branch.');
        }

        if ($branchId === null) {
            return (int) $options->first()->id;
        }

        if (!$options->contains('id', $branchId)) {
            throw new DomainException('You do not have access to this branch.');
        }

        return $branchId;
    }

    /* --------------------------------------------------------------- money */

    /** @return array{0: Carbon, 1: Carbon} */
    public function monthRange(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    /**
     * One organizer, one branch, one month.
     *
     * @return array{earned: float, advance: float, deduction: float, other: float, earlier_balance: float, net: float, salary: OrganizerPayment|null}
     */
    public function forOrganizer(int $organizerId, int $branchId, int $year, int $month): array
    {
        [$start, $end] = $this->monthRange($year, $month);

        $earned = fn (?Carbon $from = null, ?Carbon $to = null) => round((float) PaymentSplitSnapshot::query()
            ->where('branch_id', $branchId)
            ->where('organizer_id', $organizerId)
            ->when($from, fn ($q) => $q->where('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('payment_date', '<=', $to))
            ->sum('organizer_amount'), 2);

        $adjustments = OrganizerPayment::query()
            ->where('branch_id', $branchId)
            ->where('organizer_id', $organizerId)
            ->where('status', 'paid')
            ->whereIn('payment_type', self::ADJUSTMENT_TYPES)
            ->whereDate('payment_date', '<=', $end->toDateString())
            ->get(['payment_type', 'amount', 'payment_date']);

        $thisMonth = $adjustments->filter(fn ($a) => $a->payment_date->greaterThanOrEqualTo($start));
        $before = $adjustments->filter(fn ($a) => $a->payment_date->lessThan($start));

        $salariesPaid = round((float) OrganizerPayment::query()
            ->where('branch_id', $branchId)
            ->where('organizer_id', $organizerId)
            ->where('status', 'paid')
            ->where('payment_type', 'salary')
            ->sum('amount'), 2);

        $salary = $this->salaryRecord($organizerId, $branchId, $year, $month);

        $monthEarned = $earned($start, $end);
        $earlierEarned = $earned(null, $start->copy()->subSecond());
        $sum = fn (Collection $rows, string $type) => round((float) $rows->where('payment_type', $type)->sum('amount'), 2);

        $advance = $sum($thisMonth, 'advance');
        $deduction = $sum($thisMonth, 'deduction');
        $other = $sum($thisMonth, 'other');

        // owed from earlier months (not paid yet, or fees that came after their salary);
        // the salary of THIS month (when paid) is not taken off here
        $salariesBefore = round($salariesPaid - (float) ($salary->amount ?? 0), 2);
        $earlierBalance = round($earlierEarned - (float) $before->sum('amount') - $salariesBefore, 2);

        $net = round($earlierBalance + $monthEarned - $advance - $deduction - $other, 2);

        return [
            'earned' => $monthEarned,
            'advance' => $advance,
            'deduction' => $deduction,
            'other' => $other,
            'earlier_balance' => $earlierBalance,
            // what the salary is (or was) for this month
            'net' => $salary ? round((float) $salary->amount, 2) : $net,
            // what is still owed now (a paid month: fees that came after it)
            'still_owed' => $salary ? round($net - (float) $salary->amount, 2) : $net,
            'salary' => $salary,
        ];
    }

    public function salaryRecord(int $organizerId, int $branchId, int $year, int $month, bool $lock = false): ?OrganizerPayment
    {
        return OrganizerPayment::query()
            ->where('branch_id', $branchId)
            ->where('organizer_id', $organizerId)
            ->where('payment_type', 'salary')
            ->where('status', 'paid')
            ->where('salary_year', $year)
            ->where('salary_month', $month)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();
    }

    /** Organizers of a branch (linked, or with money there this month). */
    public function monthRows(int $branchId, int $year, int $month): Collection
    {
        [$start, $end] = $this->monthRange($year, $month);

        $withMoney = PaymentSplitSnapshot::query()
            ->where('branch_id', $branchId)
            ->whereNotNull('organizer_id')
            ->where('payment_date', '<=', $end)
            ->distinct()
            ->pluck('organizer_id')
            ->merge(OrganizerPayment::query()->where('branch_id', $branchId)->distinct()->pluck('organizer_id'));

        return Organizer::query()
            ->where(function ($q) use ($branchId, $withMoney) {
                $q->where(fn ($q) => $q->where('is_active', true)->whereHas('branches', fn ($b) => $b->where('branches.id', $branchId)))
                    ->orWhereIn('id', $withMoney->unique()->values());
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'mobile'])
            ->map(function (Organizer $organizer) use ($branchId, $year, $month) {
                $totals = $this->forOrganizer($organizer->id, $branchId, $year, $month);

                return [
                    'organizer_id' => $organizer->id,
                    'code' => $organizer->code,
                    'name' => $organizer->name,
                    'mobile' => $organizer->mobile,
                    'total_income' => $totals['earned'],
                    'earlier_balance' => $totals['earlier_balance'],
                    'advance_amount' => $totals['advance'],
                    'deduction_amount' => $totals['deduction'],
                    'other_amount' => $totals['other'],
                    'salary_paid' => $totals['salary'] ? round((float) $totals['salary']->amount, 2) : 0.0,
                    'net_total' => $totals['net'],
                    'is_salary_paid' => (bool) $totals['salary'],
                ];
            });
    }

    /* -------------------------------------------------------------- rules */

    public function payBlockReason(int $year, int $month, array $totals): ?string
    {
        if ($totals['salary']) {
            return 'Organizer salary for this month is already paid.';
        }

        [$start, $end] = $this->monthRange($year, $month);

        if ($start->greaterThan(now())) {
            return 'Salary cannot be paid for a future month.';
        }

        $opens = $end->copy()->subDays(self::PAY_WINDOW_DAYS)->startOfDay();
        if (now()->between($start, $end) && now()->lessThan($opens)) {
            return 'Salary can be paid from ' . $opens->format('d M Y')
                . ' (last ' . self::PAY_WINDOW_DAYS . ' days of the month).';
        }

        if ($totals['net'] <= 0) {
            return 'No payable salary balance.';
        }

        return null;
    }

    /* -------------------------------------------------------------- write */

    /** @throws DomainException */
    public function paySalary(int $organizerId, int $branchId, int $year, int $month, ?float $expectedNet = null): OrganizerPayment
    {
        $this->resolveBranchId($branchId);

        return DB::transaction(function () use ($organizerId, $branchId, $year, $month, $expectedNet) {
            Organizer::withTrashed()->whereKey($organizerId)->lockForUpdate()->firstOrFail();

            $totals = $this->forOrganizer($organizerId, $branchId, $year, $month);

            if ($reason = $this->payBlockReason($year, $month, $totals)) {
                throw new DomainException($reason);
            }

            if ($expectedNet !== null && abs($expectedNet - $totals['net']) >= 0.01) {
                throw new DomainException('The salary amount changed after this page was opened. New net amount: Rs. '
                    . number_format($totals['net'], 2) . '. Please check and pay again.');
            }

            return OrganizerPayment::create([
                'branch_id' => $branchId,
                'organizer_id' => $organizerId,
                'user_id' => auth()->id(),
                'payment_type' => 'salary',
                'amount' => $totals['net'],
                'payment_date' => now()->toDateString(),
                'salary_year' => $year,
                'salary_month' => $month,
                'reason' => 'Organizer salary ' . Carbon::create($year, $month, 1)->format('F Y'),
                'status' => 'paid',
            ]);
        });
    }

    /**
     * Advance / deduction / other, in the month of its date.
     *
     * @throws DomainException
     */
    public function storeAdjustment(int $organizerId, int $branchId, array $data): OrganizerPayment
    {
        $this->resolveBranchId($branchId);

        if (!in_array($data['payment_type'], self::ADJUSTMENT_TYPES, true)) {
            throw new DomainException('Invalid payment type.');
        }

        $amount = round((float) $data['amount'], 2);
        $date = Carbon::parse($data['payment_date'])->startOfDay();

        if ($amount <= 0) {
            throw new DomainException('Amount must be greater than zero.');
        }

        if ($date->greaterThan(now()->endOfDay())) {
            throw new DomainException('Payment date cannot be in the future.');
        }

        return DB::transaction(function () use ($organizerId, $branchId, $data, $amount, $date) {
            Organizer::withTrashed()->whereKey($organizerId)->lockForUpdate()->firstOrFail();

            $totals = $this->forOrganizer($organizerId, $branchId, $date->year, $date->month);

            if ($totals['salary']) {
                throw new DomainException('Organizer salary for ' . $date->format('F Y') . ' is already paid.');
            }

            if ($amount > max($totals['net'], 0)) {
                throw new DomainException('Amount is more than the organizer can get for ' . $date->format('F Y')
                    . ' (Rs. ' . number_format(max($totals['net'], 0), 2) . ').');
            }

            return OrganizerPayment::create([
                'branch_id' => $branchId,
                'organizer_id' => $organizerId,
                'user_id' => auth()->id(),
                'payment_type' => $data['payment_type'],
                'amount' => $amount,
                'payment_date' => $date->toDateString(),
                'reason' => $data['reason'] ?? null,
                'status' => 'paid',
                'note' => $data['note'] ?? null,
            ]);
        });
    }

    /** @throws DomainException */
    public function delete(OrganizerPayment $payment): void
    {
        if (!$this->branchAccess->canAccessBranch((int) $payment->branch_id)) {
            throw new DomainException('You do not have access to this branch.');
        }

        DB::transaction(function () use ($payment) {
            if ($payment->payment_type === 'salary') {
                // a salary can be taken back while it is in this month
                if (!$payment->payment_date->isSameMonth(now())) {
                    throw new DomainException('Only a salary paid this month can be deleted.');
                }
            } else {
                $date = $payment->payment_date;
                if ($this->salaryRecord((int) $payment->organizer_id, (int) $payment->branch_id, $date->year, $date->month)) {
                    throw new DomainException('Cannot delete this payment because the organizer salary for '
                        . $date->format('F Y') . ' is already paid.');
                }
            }

            $payment->delete();
        });
    }
}
