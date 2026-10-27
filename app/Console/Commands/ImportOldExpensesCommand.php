<?php

namespace App\Console\Commands;

use App\Models\InstitutePayment;
use App\Models\PaymentReason;
use App\Services\Import\OldValue;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

/**
 * Bring the old system's institute expenses (bills, rent, salaries of staff
 * ...) into institute_payments.
 *
 *   php artisan nexora:import-expenses expenses.csv --dry-run
 *   php artisan nexora:import-expenses expenses.csv
 *
 * CSV: old_id, reason (name), amount, payment_date, status (1 = paid,
 * 0 = cancelled). The reasons are added to payment_reasons when missing
 * (matched by name). Cancelled rows come in as "cancelled", so they are
 * listed but not counted. "Imported from old system #<id>" in the note, so
 * it is safe to run again. The paid totals per month are shown at the end.
 */
class ImportOldExpensesCommand extends Command
{
    protected $signature = 'nexora:import-expenses
        {file : CSV - old_id, reason, amount, payment_date, status}
        {--branch= : branch id / code / name (default: the only branch, or MAIN)}
        {--dry-run : only report, change nothing}';

    protected $description = 'Import the old system institute expenses';

    public const REQUIRED = ['old_id', 'amount', 'payment_date'];
    public const NOTE = 'Imported from old system #';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        try {
            $rows = OldValue::csv((string) $this->argument('file'), self::REQUIRED);
            $branch = OldValue::branch($this->option('branch'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        usort($rows, fn ($a, $b) => [(string) $a['payment_date'], (int) $a['old_id']] <=> [(string) $b['payment_date'], (int) $b['old_id']]);

        $imported = InstitutePayment::withTrashed()->where('note', 'like', self::NOTE . '%')->pluck('note')
            ->map(fn ($n) => (int) substr($n, strlen(self::NOTE)))->flip()->all();

        $reasons = PaymentReason::withTrashed()->get()->keyBy(fn ($r) => mb_strtolower(trim($r->name)))->all();
        $newReasons = [];

        $count = ['rows' => count($rows), 'created' => 0, 'cancelled' => 0, 'exists' => 0, 'bad' => 0, 'failed' => 0];
        $months = [];
        $problems = [];

        foreach ($rows as $row) {
            $oldId = (int) $row['old_id'];
            $amount = is_numeric(trim($row['amount'])) ? round((float) $row['amount'], 2) : null;
            $date = OldValue::text($row['payment_date']) ? OldValue::time($row['payment_date']) : null;
            $paid = OldValue::bool($row['status'] ?? null, true);
            $reasonName = OldValue::text($row['reason'] ?? null, 150);

            if ($oldId <= 0 || $amount === null || $amount <= 0 || !$date) {
                $count['bad']++;
                $problems[] = "#{$oldId}: NOT imported - no amount / date";
                continue;
            }

            if (isset($imported[$oldId])) {
                $count['exists']++;
                continue;
            }

            try {
                // The reason (payment_reasons), by name
                $reason = null;
                if ($reasonName !== null) {
                    $key = mb_strtolower($reasonName);
                    $reason = $reasons[$key] ?? null;
                    if (!$reason) {
                        $reason = $dry
                            ? new PaymentReason(['reason_code' => $this->reasonCode($reasonName), 'name' => $reasonName])
                            : PaymentReason::create(['reason_code' => $this->reasonCode($reasonName), 'name' => $reasonName, 'is_active' => true]);
                        $reasons[$key] = $reason;
                        $newReasons[] = $reasonName;
                    }
                }

                if (!$dry) {
                    $expense = new InstitutePayment([
                        'branch_id' => $branch->id,
                        'amount' => $amount,
                        'payment_date' => $date->toDateString(),
                        'reason' => $reasonName,
                        'reason_code' => $reason?->reason_code,
                        'payment_type' => 'expense',
                        'status' => $paid ? 'paid' : 'cancelled',
                        'user_id' => null,
                        'note' => self::NOTE . $oldId,
                    ]);
                    $expense->created_at = $date;
                    $expense->updated_at = $date;
                    $expense->save();
                }
            } catch (Throwable $e) {
                $count['failed']++;
                $problems[] = "#{$oldId}: NOT imported - " . strtok($e->getMessage(), "\n");
                continue;
            }

            $imported[$oldId] = true;

            if (!$paid) {
                $count['cancelled']++;
                $problems[] = "#{$oldId}: cancelled in the old system (Rs. " . number_format($amount, 2) . ', ' . $date->toDateString() . ') - imported as cancelled, not counted';
                continue;
            }

            $count['created']++;
            $month = $date->format('Y-m');
            $months[$month] = ($months[$month] ?? 0) + $amount;
        }

        ksort($months);

        $this->newLine();
        $this->line($dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        $this->line("Branch: {$branch->name}");
        if ($newReasons) {
            $this->line(($dry ? 'Reasons to add: ' : 'Reasons added: ') . implode(', ', $newReasons));
        }
        $this->table(['', 'Expenses'], [
            ['In the file', $count['rows']],
            [$dry ? 'Would be imported (paid)' : 'Imported (paid)', $count['created'] . ' (Rs. ' . number_format(array_sum($months), 2) . ')'],
            [$dry ? 'Would be imported as cancelled' : 'Imported as cancelled', $count['cancelled']],
            ['Already imported (left as they are)', $count['exists']],
            ['Skipped: no amount / date', $count['bad']],
            ['Failed', $count['failed']],
        ]);

        if ($months) {
            $this->table(['Month', 'Paid'], collect($months)->map(fn ($a, $m) => [$m, number_format($a, 2)])->values()->all());
        }

        $log = OldValue::writeProblems('import-expenses', $problems, fn ($l) => $this->line($l));
        if ($log) {
            $this->warn(count($problems) . ' lines - full list: ' . $log);
        }

        return $count['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function reasonCode(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_'), 40, '') ?: 'reason';
        $code = $base;
        for ($i = 2; PaymentReason::withTrashed()->where('reason_code', $code)->exists(); $i++) {
            $code = $base . '_' . $i;
        }

        return $code;
    }
}
