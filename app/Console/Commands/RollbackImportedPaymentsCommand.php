<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentSplitSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Take out the payments one nexora:import-payments run (batch) added, with
 * their teacher / institute split - as if that import never ran. Payments
 * made in this system are never touched. The batch can be imported again.
 *
 *   php artisan nexora:import-payments-rollback 20261105-oct-nov --dry-run
 *   php artisan nexora:import-payments-rollback 20261105-oct-nov
 *   php artisan nexora:import-payments-rollback --list
 */
class RollbackImportedPaymentsCommand extends Command
{
    protected $signature = 'nexora:import-payments-rollback
        {batch? : the batch name the import printed}
        {--list : show the imported batches}
        {--dry-run : only count, change nothing}';

    protected $description = 'Remove the payments of one old-system payment import (batch)';

    public function handle(): int
    {
        $prefix = ImportOldPaymentsCommand::BATCH_PREFIX;

        $imported = fn () => Payment::withTrashed()
            ->where('note', 'like', ImportOldPaymentsCommand::NOTE . '%')
            ->where('reference_number', 'like', $prefix . '%');

        if ($this->option('list') || !$this->argument('batch')) {
            $rows = $imported()
                ->selectRaw('reference_number, COUNT(*) as n, SUM(amount) as total, MIN(paid_at) as first, MAX(paid_at) as last')
                ->groupBy('reference_number')
                ->get()
                ->map(fn ($r) => [substr($r->reference_number, strlen($prefix)), $r->n, number_format((float) $r->total, 2), $r->first, $r->last]);

            $this->table(['Batch', 'Payments', 'Rs.', 'First paid', 'Last paid'], $rows->all());

            return self::SUCCESS;
        }

        $batch = (string) $this->argument('batch');
        $query = $imported()->where('reference_number', $prefix . $batch);
        $ids = (clone $query)->pluck('id');
        $total = (float) (clone $query)->sum('amount');

        $this->info(sprintf('Batch %s: %d payments, Rs. %s', $batch, $ids->count(), number_format($total, 2)));

        if ($ids->isEmpty() || $this->option('dry-run')) {
            if ($this->option('dry-run')) {
                $this->line('DRY RUN - nothing was changed');
            }

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids->chunk(500) as $chunk) {
                PaymentSplitSnapshot::withTrashed()->whereIn('payment_id', $chunk)->forceDelete();
                Payment::withTrashed()->whereIn('id', $chunk)->forceDelete();
            }
        });

        $this->info("Removed {$ids->count()} payments and their splits. The batch can be imported again.");

        return self::SUCCESS;
    }
}
