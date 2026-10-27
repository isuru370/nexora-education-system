<?php

namespace App\Console\Commands;

use App\Models\ExtraIncome;
use App\Services\Import\OldValue;
use App\Services\Web\Receipt\ReceiptNumberService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Bring the old system's extra incomes into extra_incomes.
 *
 *   php artisan nexora:import-extra-incomes extra_incomes.csv --dry-run
 *   php artisan nexora:import-extra-incomes extra_incomes.csv
 *
 * CSV: old_id, reason, amount, income_date. Received, type "extra", the
 * date and reason kept, REC receipts in date order. "Imported from old
 * system #<id>" in the note, so it is safe to run again. The totals per
 * month are shown at the end.
 */
class ImportOldExtraIncomesCommand extends Command
{
    protected $signature = 'nexora:import-extra-incomes
        {file : CSV - old_id, reason, amount, income_date}
        {--branch= : branch id / code / name (default: the only branch, or MAIN)}
        {--dry-run : only report, change nothing}';

    protected $description = 'Import the old system extra incomes';

    public const REQUIRED = ['old_id', 'amount', 'income_date'];
    public const NOTE = 'Imported from old system #';

    public function handle(ReceiptNumberService $receipts): int
    {
        $dry = (bool) $this->option('dry-run');

        try {
            $rows = OldValue::csv((string) $this->argument('file'), self::REQUIRED);
            $branch = OldValue::branch($this->option('branch'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        usort($rows, fn ($a, $b) => [(string) $a['income_date'], (int) $a['old_id']] <=> [(string) $b['income_date'], (int) $b['old_id']]);

        $imported = ExtraIncome::withTrashed()->where('note', 'like', self::NOTE . '%')->pluck('note')
            ->map(fn ($n) => (int) substr($n, strlen(self::NOTE)))->flip()->all();

        $count = ['rows' => count($rows), 'created' => 0, 'exists' => 0, 'bad' => 0, 'failed' => 0];
        $months = [];
        $problems = [];

        foreach ($rows as $row) {
            $oldId = (int) $row['old_id'];
            $amount = is_numeric(trim($row['amount'])) ? round((float) $row['amount'], 2) : null;
            $date = OldValue::text($row['income_date']) ? OldValue::time($row['income_date']) : null;

            if ($oldId <= 0 || $amount === null || $amount <= 0 || !$date) {
                $count['bad']++;
                $problems[] = "#{$oldId}: NOT imported - no amount / date";
                continue;
            }

            if (isset($imported[$oldId])) {
                $count['exists']++;
                continue;
            }

            if (!$dry) {
                try {
                    $income = new ExtraIncome([
                        'branch_id' => $branch->id,
                        'amount' => $amount,
                        'income_date' => $date->toDateString(),
                        'reason' => OldValue::text($row['reason'] ?? null, 150) ?? 'Extra income',
                        'income_type' => 'extra',
                        'status' => 'received',
                        'receipt_number' => $receipts->generate(),
                        'user_id' => null,
                        'note' => self::NOTE . $oldId,
                    ]);
                    $income->created_at = $date;
                    $income->updated_at = $date;
                    $income->save();
                } catch (Throwable $e) {
                    $count['failed']++;
                    $problems[] = "#{$oldId}: NOT imported - " . strtok($e->getMessage(), "\n");
                    continue;
                }
            }

            $imported[$oldId] = true;
            $count['created']++;
            $month = $date->format('Y-m');
            $months[$month] = ($months[$month] ?? 0) + $amount;
        }

        ksort($months);

        $this->newLine();
        $this->line($dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        $this->line("Branch: {$branch->name}");
        $this->table(['', 'Extra incomes'], [
            ['In the file', $count['rows']],
            [$dry ? 'Would be imported' : 'Imported', $count['created'] . ' (Rs. ' . number_format(array_sum($months), 2) . ')'],
            ['Already imported (left as they are)', $count['exists']],
            ['Skipped: no amount / date', $count['bad']],
            ['Failed', $count['failed']],
        ]);

        if ($months) {
            $this->table(['Month', 'Amount'], collect($months)->map(fn ($a, $m) => [$m, number_format($a, 2)])->values()->all());
        }

        $log = OldValue::writeProblems('import-extra-incomes', $problems, fn ($l) => $this->line($l));
        if ($log) {
            $this->warn(count($problems) . ' lines - full list: ' . $log);
        }

        return $count['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
