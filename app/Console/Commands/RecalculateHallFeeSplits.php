<?php

namespace App\Console\Commands;

use App\Models\PaymentSplitSnapshot;
use App\Models\TeacherSalary;
use App\Services\Web\Payment\PaymentSplitSnapshotService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Payments with a hall fee were split as if the hall fee were class fee
 * (the teacher got their % of it). Re-split them so the hall fee goes to
 * the institute, with each payment's own saved percentages and hall fee.
 *
 *   php artisan nexora:recalculate-hall-fee-splits --dry-run
 *   php artisan nexora:recalculate-hall-fee-splits
 *
 * Months whose teacher salary is already paid are left as they are (the
 * teacher got that money) unless --include-paid-salaries.
 */
class RecalculateHallFeeSplits extends Command
{
    protected $signature = 'nexora:recalculate-hall-fee-splits
        {--from= : only payments paid on / after this date (Y-m-d)}
        {--include-paid-salaries : also change months whose salary is already paid}
        {--dry-run : only show what would change}';

    protected $description = 'Give the hall fee of past payments to the institute (re-split teacher / institute)';

    public function handle(): int
    {
        $snapshots = PaymentSplitSnapshot::query()
            ->where('hall_fee', '>', 0)
            ->when($this->option('from'), fn ($q, $from) => $q->where('payment_date', '>=', Carbon::parse($from)->startOfDay()))
            ->get();

        // teacher | year-month whose salary is paid
        $paidSalaries = TeacherSalary::query()
            ->where('status', 'paid')
            ->get(['teacher_id', 'branch_id', 'salary_year', 'salary_month'])
            ->mapWithKeys(fn ($s) => [$s->branch_id . '|' . $s->teacher_id . '|' . sprintf('%04d-%02d', $s->salary_year, $s->salary_month) => true]);

        $changed = 0;
        $teacherDiff = 0.0;
        $lockedRows = 0;
        $byTeacher = [];

        DB::beginTransaction();

        try {
            foreach ($snapshots as $snapshot) {
                [$teacher, $organizer, $institution] = PaymentSplitSnapshotService::split(
                    (float) $snapshot->payment_amount,
                    (float) $snapshot->hall_fee,
                    (float) $snapshot->teacher_percentage,
                    (float) $snapshot->organizer_percentage
                );

                if (abs($teacher - (float) $snapshot->teacher_amount) < 0.01
                    && abs($organizer - (float) $snapshot->organizer_amount) < 0.01) {
                    continue;   // already right
                }

                $month = Carbon::parse($snapshot->payment_date)->format('Y-m');
                if (!$this->option('include-paid-salaries') && isset($paidSalaries[$snapshot->branch_id . '|' . $snapshot->teacher_id . '|' . $month])) {
                    $lockedRows++;
                    continue;
                }

                $diff = round((float) $snapshot->teacher_amount - $teacher, 2);
                $teacherDiff += $diff;
                $byTeacher[$snapshot->teacher_id] = ($byTeacher[$snapshot->teacher_id] ?? 0) + $diff;
                $changed++;

                if (!$this->option('dry-run')) {
                    $snapshot->forceFill([
                        'teacher_amount' => $teacher,
                        'organizer_amount' => $organizer,
                        'institution_amount' => $institution,
                    ])->save();
                }
            }

            $this->option('dry-run') ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $names = \App\Models\Teacher::withTrashed()->whereIn('id', array_keys($byTeacher))->pluck('full_name', 'id');
        $this->table(['Teacher', 'Moved to institute (Rs.)'], collect($byTeacher)
            ->map(fn ($v, $id) => [$names[$id] ?? '#' . $id, number_format($v, 2)])
            ->values()->all());

        $this->info(($this->option('dry-run') ? 'DRY RUN - would re-split ' : 'Re-split ') . "{$changed} payment(s): Rs. "
            . number_format($teacherDiff, 2) . ' moved from teachers to the institute.');

        if ($lockedRows > 0) {
            $this->warn("{$lockedRows} payment(s) left as they are: that month's teacher salary is already paid (use --include-paid-salaries to change them too).");
        }

        return self::SUCCESS;
    }
}
