<?php

namespace App\Services\Web\Welfare;

use App\Models\Branch;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use App\Models\WelfareDeduction;
use App\Models\WelfareExpense;
use App\Services\Web\TeacherSalary\TeacherSalaryCalculator;
use App\Services\Web\TeacherSalary\TeacherSalaryService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Teacher welfare (feature "welfare"), per branch:
 *
 *  - each teacher has a monthly welfare amount in a branch (teacher_branches.welfare_amount)
 *  - the admin takes it from the teacher's salary for a month (welfare_deductions);
 *    the salary net goes down and the slip shows "Welfare"
 *  - the money is the branch's welfare fund (not institute income); the admin
 *    records what is paid out of it (welfare_expenses)
 *
 * Same rules as advances: not for a future month, not after that month's
 * salary is paid, not more than the net salary. Throws DomainException with a
 * message for the user.
 */
class WelfareService
{
    public function __construct(
        protected TeacherSalaryCalculator $calculator,
        protected TeacherSalaryService $salaryService
    ) {}

    public function branchOptions(): Collection
    {
        return $this->salaryService->branchOptions();
    }

    /** Branch the user can open; the only / first branch when none is given */
    public function resolveBranchId(?int $branchId): int
    {
        return $this->salaryService->resolveBranchId($branchId);
    }

    /* ------------------------------------------------------------------ */
    /* Monthly list                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Teachers of a branch for a month: welfare amount, earnings, what was taken.
     */
    public function month(int $branchId, int $year, int $month): array
    {
        $this->salaryService->assertBranchAccess($branchId);

        $gross = $this->calculator->grossByTeacher($branchId, $year, $month);
        $deductions = $this->calculator->deductionsByTeacher($branchId, $year, $month);

        $taken = WelfareDeduction::query()
            ->where('branch_id', $branchId)
            ->where('salary_year', $year)
            ->where('salary_month', $month)
            ->get()
            ->keyBy('teacher_id');

        $salaries = TeacherSalary::query()
            ->where('branch_id', $branchId)
            ->where('salary_year', $year)
            ->where('salary_month', $month)
            ->pluck('status', 'teacher_id');

        $amounts = DB::table('teacher_branches')
            ->where('branch_id', $branchId)
            ->pluck('welfare_amount', 'teacher_id');

        $teacherIds = DB::table('teacher_branches')
            ->join('teachers', 'teachers.id', '=', 'teacher_branches.teacher_id')
            ->where('teacher_branches.branch_id', $branchId)
            ->where('teachers.is_active', true)
            ->whereNull('teachers.deleted_at')
            ->pluck('teacher_branches.teacher_id')
            ->merge($taken->keys())
            ->map(fn ($id) => (int) $id)
            ->unique();

        $teachers = Teacher::withTrashed()
            ->whereIn('id', $teacherIds)
            ->orderBy('full_name')
            ->get(['id', 'custom_id', 'full_name']);

        $monthBlock = $this->monthBlockReason($year, $month);

        $rows = $teachers->map(function (Teacher $teacher) use ($gross, $deductions, $taken, $salaries, $amounts, $monthBlock) {
            $totals = $this->calculator->buildTotals(
                (float) $gross->get($teacher->id, 0.0),
                $deductions->get($teacher->id, $this->calculator->emptyDeductions())
            );
            /** @var WelfareDeduction|null $deduction */
            $deduction = $taken->get($teacher->id);
            $paid = $salaries->get($teacher->id) === 'paid';

            $block = $monthBlock
                ?? ($paid ? 'Salary already paid.' : null)
                ?? ($deduction ? 'Already taken.' : null);

            return [
                'teacher_id' => $teacher->id,
                'custom_id' => $teacher->custom_id,
                'name' => $teacher->full_name,
                'welfare_amount' => $amounts->get($teacher->id) !== null ? round((float) $amounts->get($teacher->id), 2) : null,
                'gross' => $totals['gross'],
                'net' => $totals['net'],
                'deduction_id' => $deduction?->id,
                'taken' => $deduction ? round((float) $deduction->amount, 2) : 0.0,
                'salary_paid' => $paid,
                'can_take' => $block === null,
                'block_reason' => $block,
                'can_cancel' => $deduction !== null && !$paid,
            ];
        })->values();

        return [
            'rows' => $rows,
            'total_taken' => round($rows->sum('taken'), 2),
            'month_block' => $monthBlock,
        ];
    }

    /** Monthly amounts: [teacher_id => amount|null] for teachers of the branch */
    public function saveAmounts(int $branchId, array $amounts): int
    {
        $this->salaryService->assertBranchAccess($branchId);

        $count = 0;
        foreach ($amounts as $teacherId => $amount) {
            $value = ($amount === null || $amount === '') ? null : round(max(0, (float) $amount), 2);
            $count += DB::table('teacher_branches')
                ->where('branch_id', $branchId)
                ->where('teacher_id', (int) $teacherId)
                ->update(['welfare_amount' => $value, 'updated_at' => now()]);
        }

        return $count;
    }

    /**
     * Take welfare from the salaries of the given teachers for a month.
     *
     * @param  array<int, float|string>  $amounts  [teacher_id => amount]
     * @return array{done: array<int, string>, skipped: array<int, string>}
     */
    public function deduct(int $branchId, int $year, int $month, array $amounts, ?string $note = null): array
    {
        $this->salaryService->assertBranchAccess($branchId);

        if ($reason = $this->monthBlockReason($year, $month)) {
            throw new DomainException($reason);
        }

        $done = [];
        $skipped = [];

        foreach ($amounts as $teacherId => $value) {
            $teacherId = (int) $teacherId;
            $amount = round((float) $value, 2);
            $name = optional(Teacher::withTrashed()->find($teacherId))->full_name ?? ('#' . $teacherId);

            try {
                if ($amount <= 0) {
                    throw new DomainException('amount must be more than 0');
                }
                $this->deductOne($branchId, $teacherId, $year, $month, $amount, $note);
                $done[] = $name . ' (LKR ' . number_format($amount, 2) . ')';
            } catch (DomainException $e) {
                $skipped[] = $name . ': ' . $e->getMessage();
            }
        }

        return ['done' => $done, 'skipped' => $skipped];
    }

    protected function deductOne(int $branchId, int $teacherId, int $year, int $month, float $amount, ?string $note): WelfareDeduction
    {
        return DB::transaction(function () use ($branchId, $teacherId, $year, $month, $amount, $note) {
            Teacher::withTrashed()->whereKey($teacherId)->lockForUpdate()->firstOrFail();

            $inBranch = DB::table('teacher_branches')
                ->where('branch_id', $branchId)->where('teacher_id', $teacherId)->exists();
            if (!$inBranch) {
                throw new DomainException('not a teacher of this branch');
            }

            $record = $this->calculator->salaryRecord($teacherId, $branchId, $year, $month, true);
            if ($record && $record->status === 'paid') {
                throw new DomainException('salary already paid');
            }

            $exists = WelfareDeduction::query()
                ->where('branch_id', $branchId)->where('teacher_id', $teacherId)
                ->where('salary_year', $year)->where('salary_month', $month)
                ->lockForUpdate()
                ->exists();
            if ($exists) {
                throw new DomainException('already taken for this month');
            }

            $totals = $this->calculator->forTeacher($teacherId, $branchId, $year, $month);
            if ($amount > $totals['available_limit']) {
                throw new DomainException('more than the salary (available LKR ' . number_format($totals['available_limit'], 2) . ')');
            }

            return WelfareDeduction::create([
                'branch_id' => $branchId,
                'teacher_id' => $teacherId,
                'salary_year' => $year,
                'salary_month' => $month,
                'amount' => $amount,
                'note' => $note,
                'user_id' => auth()->id(),
            ]);
        });
    }

    /** Give a welfare deduction back (before the salary is paid) */
    public function cancelDeduction(int $id): WelfareDeduction
    {
        return DB::transaction(function () use ($id) {
            $deduction = WelfareDeduction::query()->lockForUpdate()->findOrFail($id);
            $this->salaryService->assertBranchAccess((int) $deduction->branch_id);

            $record = $this->calculator->salaryRecord(
                (int) $deduction->teacher_id, (int) $deduction->branch_id,
                (int) $deduction->salary_year, (int) $deduction->salary_month, true
            );
            if ($record && $record->status === 'paid') {
                throw new DomainException('The salary for this month is already paid. This welfare cannot be cancelled.');
            }

            Branch::query()->whereKey($deduction->branch_id)->lockForUpdate()->first();
            if ($this->balance((int) $deduction->branch_id) < (float) $deduction->amount) {
                throw new DomainException('This money is already spent from the welfare fund, so it cannot be given back.');
            }

            $deduction->forceFill(['cancelled_by' => auth()->id()])->save();
            $deduction->delete();

            return $deduction;
        });
    }

    /* ------------------------------------------------------------------ */
    /* Fund                                                                */
    /* ------------------------------------------------------------------ */

    /** Money in the fund now (all collected - all spent) */
    public function balance(int $branchId): float
    {
        $in = (float) WelfareDeduction::query()->where('branch_id', $branchId)->sum('amount');
        $out = (float) WelfareExpense::query()->where('branch_id', $branchId)->sum('amount');

        return round($in - $out, 2);
    }

    /**
     * Fund for one month: opening balance, collected (by salary month),
     * spent (by expense date), closing balance.
     */
    public function fund(int $branchId, int $year, int $month): array
    {
        $this->salaryService->assertBranchAccess($branchId);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $monthKey = $year * 100 + $month;

        $collectedBefore = (float) WelfareDeduction::query()
            ->where('branch_id', $branchId)
            ->whereRaw('(salary_year * 100 + salary_month) < ?', [$monthKey])
            ->sum('amount');
        $spentBefore = (float) WelfareExpense::query()
            ->where('branch_id', $branchId)
            ->where('expense_date', '<', $start->toDateString())
            ->sum('amount');

        $collected = WelfareDeduction::query()
            ->with('teacher:id,custom_id,full_name')
            ->where('branch_id', $branchId)
            ->where('salary_year', $year)
            ->where('salary_month', $month)
            ->get()
            ->sortBy(fn (WelfareDeduction $d) => optional($d->teacher)->full_name)
            ->values();

        $expenses = WelfareExpense::query()
            ->with('createdBy:id,name')
            ->where('branch_id', $branchId)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $opening = round($collectedBefore - $spentBefore, 2);
        $in = round((float) $collected->sum('amount'), 2);
        $out = round((float) $expenses->sum('amount'), 2);

        return [
            'branch' => Branch::query()->find($branchId),
            'month_label' => $start->format('F Y'),
            'opening' => $opening,
            'collected' => $collected,
            'collected_total' => $in,
            'expenses' => $expenses,
            'expenses_total' => $out,
            'closing' => round($opening + $in - $out, 2),
            'balance' => $this->balance($branchId),
        ];
    }

    /**
     * Record money paid out of the fund. It cannot be more than the fund has.
     *
     * @param  array{expense_date: string, description: string, paid_to?: ?string, amount: float|string, note?: ?string}  $data
     */
    public function addExpense(int $branchId, array $data): WelfareExpense
    {
        $this->salaryService->assertBranchAccess($branchId);

        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new DomainException('Amount must be greater than zero.');
        }
        if (Carbon::parse($data['expense_date'])->greaterThan(now()->endOfDay())) {
            throw new DomainException('The date cannot be in the future.');
        }

        return DB::transaction(function () use ($branchId, $data, $amount) {
            Branch::query()->whereKey($branchId)->lockForUpdate()->firstOrFail();

            $balance = $this->balance($branchId);
            if ($amount > $balance) {
                throw new DomainException('The welfare fund has only LKR ' . number_format($balance, 2) . '.');
            }

            return WelfareExpense::create([
                'branch_id' => $branchId,
                'expense_date' => Carbon::parse($data['expense_date'])->toDateString(),
                'description' => trim($data['description']),
                'paid_to' => $data['paid_to'] ?? null,
                'amount' => $amount,
                'note' => $data['note'] ?? null,
                'user_id' => auth()->id(),
            ]);
        });
    }

    public function cancelExpense(int $id): WelfareExpense
    {
        $expense = WelfareExpense::query()->findOrFail($id);
        $this->salaryService->assertBranchAccess((int) $expense->branch_id);

        $expense->forceFill(['cancelled_by' => auth()->id()])->save();
        $expense->delete();

        return $expense;
    }

    /* ------------------------------------------------------------------ */

    protected function monthBlockReason(int $year, int $month): ?string
    {
        return Carbon::create($year, $month, 1)->startOfMonth()->greaterThan(now())
            ? 'Welfare cannot be taken for a future month.'
            : null;
    }
}
