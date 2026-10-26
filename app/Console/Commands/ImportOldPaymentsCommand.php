<?php

namespace App\Console\Commands;

use App\Models\ClassCategoryFeeOption;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Services\Import\OldValue;
use App\Services\Web\Payment\PaymentSplitSnapshotService;
use App\Services\Web\Receipt\ReceiptNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Bring the old system's class fee payments into payments (+ the teacher /
 * institute split of each, as when paying from the web).
 *
 *   php artisan nexora:import-payments class_payments.csv --dry-run
 *   php artisan nexora:import-payments class_payments.csv
 *
 * Run after nexora:import-classes (with --enrollments). CSV: old_id,
 * custom_id, old_enrollment_id, old_fee_id, payment_month (2025-02 or
 * "2025 Feb"), amount, paid_at, status (1 = paid, 0 = cancelled - skipped).
 *
 * The payment goes to the student's enrollment imported from
 * old_enrollment_id (or, when that one was a double entry, the student's
 * enrollment in the same class fee option). One payment per enrollment and
 * month (the system rule): a second one is skipped and listed. Receipts get
 * REC numbers in date order. No SMS. "Imported from old system #<id>" in the
 * note, so it is safe to run again - with a newer backup only the new
 * payments are added.
 *
 * At the end the totals per month in the file and in the database are
 * compared; they must be the same.
 */
class ImportOldPaymentsCommand extends Command
{
    protected $signature = 'nexora:import-payments
        {file : CSV - old_id, custom_id, old_enrollment_id, old_fee_id, payment_month, amount, paid_at, status}
        {--dry-run : only report, change nothing}';

    protected $description = 'Import the old system class fee payments (with the teacher / institute split)';

    public const REQUIRED = ['old_id', 'custom_id', 'old_enrollment_id', 'old_fee_id', 'payment_month', 'amount', 'paid_at'];
    public const NOTE = 'Imported from old system #';
    public const ENROLLMENT_NOTE = ImportOldClassesCommand::ENROLLMENT_NOTE;

    public function handle(ReceiptNumberService $receipts, PaymentSplitSnapshotService $splits): int
    {
        $dry = (bool) $this->option('dry-run');

        try {
            $rows = OldValue::csv((string) $this->argument('file'), self::REQUIRED);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Oldest first: receipt numbers follow the dates
        usort($rows, fn ($a, $b) => [(string) $a['paid_at'], (int) $a['old_id']] <=> [(string) $b['paid_at'], (int) $b['old_id']]);

        $count = ['rows' => count($rows), 'created' => 0, 'exists' => 0, 'cancelled' => 0, 'duplicate' => 0,
            'no_enrollment' => 0, 'bad' => 0, 'failed' => 0];
        $problems = [];

        // Lookups
        $students = Student::query()->pluck('id', 'custom_id')->mapWithKeys(fn ($id, $c) => [strtoupper($c) => (int) $id])->all();

        $enrollmentByOld = [];
        StudentClassEnrollment::query()->where('note', 'like', self::ENROLLMENT_NOTE . '%')
            ->get(['id', 'note', 'branch_id', 'student_id'])
            ->each(function ($e) use (&$enrollmentByOld) {
                $enrollmentByOld[(int) substr($e->note, strlen(self::ENROLLMENT_NOTE))] = $e;
            });

        // old fee id => fee option id (from the classes import)
        $optionByOldFee = [];
        foreach (ClassCategoryFeeOption::withTrashed()->where('note', 'like', 'Imported from old system - class #%')->get(['id', 'note']) as $option) {
            if (preg_match('/fee #([\d,]+)/', (string) $option->note, $m)) {
                foreach (explode(',', $m[1]) as $oldFee) {
                    $optionByOldFee[(int) $oldFee] = (int) $option->id;
                }
            }
        }

        $enrollmentByStudentOption = [];
        StudentClassEnrollment::query()->orderByDesc('is_active')->orderBy('id')
            ->get(['id', 'branch_id', 'student_id', 'class_category_fee_option_id'])
            ->each(function ($e) use (&$enrollmentByStudentOption) {
                $enrollmentByStudentOption[$e->student_id . '|' . $e->class_category_fee_option_id] ??= $e;
            });

        $imported = Payment::withTrashed()->where('note', 'like', self::NOTE . '%')->pluck('note')
            ->map(fn ($n) => (int) substr($n, strlen(self::NOTE)))->flip()->all();

        // enrollment|month already paid (the database's own unique rule)
        $paidMonths = Payment::query()->where('status', 'completed')
            ->get(['student_class_enrollment_id', 'payment_month'])
            ->mapWithKeys(fn ($p) => [$p->student_class_enrollment_id . '|' . Carbon::parse($p->payment_month)->format('Y-m') => true])
            ->all();

        $fileTotals = [];   // month => amount (paid rows in the file)
        $bar = $this->output->createProgressBar(count($rows));

        foreach ($rows as $row) {
            $bar->advance();
            $oldId = (int) $row['old_id'];
            $id = strtoupper((string) OldValue::text($row['custom_id']));
            $month = $this->month($row['payment_month']);
            $amount = is_numeric(trim($row['amount'])) ? round((float) $row['amount'], 2) : null;
            $paidAt = OldValue::text($row['paid_at']) ? OldValue::time($row['paid_at']) : null;

            if (!OldValue::bool($row['status'] ?? null, true)) {
                $count['cancelled']++;
                $problems[] = "#{$oldId} {$id}: not imported - cancelled in the old system (Rs. {$row['amount']})";
                continue;
            }

            if ($oldId <= 0 || $month === null || $amount === null || $paidAt === null) {
                $count['bad']++;
                $problems[] = "#{$oldId} {$id}: NOT imported - no month / amount / date";
                continue;
            }

            $fileTotals[$month] = ($fileTotals[$month] ?? 0) + $amount;

            if (isset($imported[$oldId])) {
                $count['exists']++;
                continue;
            }

            // The enrollment
            $enrollment = $enrollmentByOld[(int) $row['old_enrollment_id']] ?? null;
            if (!$enrollment && isset($students[$id], $optionByOldFee[(int) $row['old_fee_id']])) {
                $enrollment = $enrollmentByStudentOption[$students[$id] . '|' . $optionByOldFee[(int) $row['old_fee_id']]] ?? null;
            }

            if (!$enrollment || ($students[$id] ?? null) !== (int) $enrollment->student_id) {
                $count['no_enrollment']++;
                $problems[] = "#{$oldId} {$id}: NOT imported - class enrollment not found (import classes first)";
                continue;
            }

            $monthKey = $enrollment->id . '|' . $month;
            if (isset($paidMonths[$monthKey])) {
                $count['duplicate']++;
                $problems[] = "#{$oldId} {$id}: NOT imported - {$month} already paid for this class (double entry, Rs. {$amount})";
                continue;
            }

            if (!$dry) {
                try {
                    DB::transaction(function () use ($enrollment, $amount, $paidAt, $month, $oldId, $receipts, $splits) {
                        $payment = Payment::withoutEvents(function () use ($enrollment, $amount, $paidAt, $month, $oldId, $receipts) {
                            $payment = new Payment([
                                'branch_id' => $enrollment->branch_id,
                                'student_id' => $enrollment->student_id,
                                'student_class_enrollment_id' => $enrollment->id,
                                'user_id' => null,
                                'mark_method' => 'manual_web',
                                'amount' => $amount,
                                'discount_amount' => 0,
                                'paid_at' => $paidAt,
                                'payment_month' => $month . '-01',
                                'payment_method' => 'cash',
                                'status' => 'completed',
                                'receipt_number' => $receipts->generate(),
                                'note' => self::NOTE . $oldId,
                            ]);
                            $payment->created_at = $paidAt;
                            $payment->updated_at = $paidAt;
                            $payment->save();

                            return $payment;
                        });

                        // teacher / institute split, same as a payment from the web
                        $splits->createForPayment($payment);
                    });
                } catch (Throwable $e) {
                    $count['failed']++;
                    $problems[] = "#{$oldId} {$id}: NOT imported - " . strtok($e->getMessage(), "\n");
                    continue;
                }
            }

            $paidMonths[$monthKey] = true;
            $imported[$oldId] = true;
            $count['created']++;
        }

        $bar->finish();
        $this->newLine(2);
        $this->line($dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        $this->table(['', 'Payments'], [
            ['In the file', $count['rows']],
            [$dry ? 'Would be imported' : 'Imported', $count['created']],
            ['Already imported (left as they are)', $count['exists']],
            ['Skipped: cancelled in the old system', $count['cancelled']],
            ['Skipped: same class + month paid twice', $count['duplicate']],
            ['Skipped: enrollment not found', $count['no_enrollment']],
            ['Skipped: no month / amount / date', $count['bad']],
            ['Failed', $count['failed']],
        ]);

        if (!$dry) {
            $this->check($fileTotals, $problems);
        }

        $log = OldValue::writeProblems('import-payments', $problems, fn ($l) => $this->line($l));
        if ($log) {
            $this->warn(count($problems) . ' lines - full list: ' . $log);
        }

        return $count['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * File vs database, per month: imported payments + their splits.
     * Differences are only the skipped double entries.
     */
    protected function check(array $fileTotals, array $problems): void
    {
        $skipped = [];
        foreach ($problems as $p) {
            if (preg_match('/- (\d{4}-\d{2}) already paid .*Rs\. ([\d.]+)\)/', $p, $m)) {
                $skipped[$m[1]] = ($skipped[$m[1]] ?? 0) + (float) $m[2];
            }
        }

        $db = Payment::query()->where('note', 'like', self::NOTE . '%')->where('status', 'completed')
            ->get(['id', 'payment_month', 'amount'])
            ->groupBy(fn ($p) => Carbon::parse($p->payment_month)->format('Y-m'))
            ->map(fn ($g) => round($g->sum('amount'), 2));

        $splitTotals = DB::table('payment_split_snapshots as s')
            ->join('payments as p', 'p.id', '=', 's.payment_id')
            ->where('p.note', 'like', self::NOTE . '%')
            ->selectRaw('SUM(s.payment_amount) as amount, SUM(s.teacher_amount + s.organizer_amount + s.institution_amount) as split, COUNT(*) as n')
            ->first();

        $rows = [];
        $bad = 0;
        foreach (array_keys($fileTotals + $db->all()) as $month) {
            $file = round($fileTotals[$month] ?? 0, 2);
            $in = (float) ($db[$month] ?? 0);
            $skip = round($skipped[$month] ?? 0, 2);
            $ok = abs($file - $skip - $in) < 0.01;
            $bad += $ok ? 0 : 1;
            $rows[$month] = [$month, number_format($file, 2), number_format($in, 2), $skip ? number_format($skip, 2) : '', $ok ? 'OK' : 'CHECK'];
        }
        ksort($rows);

        $this->newLine();
        $this->line('Check - old system vs new database, per month:');
        $this->table(['Month', 'Old system', 'Imported', 'Skipped (double)', ''], array_values($rows));

        $imported = (float) $db->sum();
        $this->line(sprintf('Total imported: Rs. %s - teacher / institute split made for %d payments (Rs. %s)',
            number_format($imported, 2), (int) $splitTotals->n, number_format((float) $splitTotals->amount, 2)));

        if ($bad > 0 || abs((float) $splitTotals->amount - $imported) >= 0.01) {
            $this->error('Totals do not match - check before using the system.');
        } else {
            $this->info('All months match.');
        }
    }

    /** "2025 Feb", "2025-02", "02 2025" -> "2025-02" */
    protected function month(?string $value): ?string
    {
        $value = trim((string) $value);
        $months = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

        if (preg_match('/^(\d{4})[-\/ ](\d{1,2})$/', $value, $m)) {
            [$y, $mo] = [(int) $m[1], (int) $m[2]];
        } elseif (preg_match('/^(\d{1,2})[-\/ ](\d{4})$/', $value, $m)) {
            [$y, $mo] = [(int) $m[2], (int) $m[1]];
        } elseif (preg_match('/^(\d{4})\s+([a-z]{3})/i', $value, $m) && isset($months[strtolower($m[2])])) {
            [$y, $mo] = [(int) $m[1], $months[strtolower($m[2])]];
        } else {
            return null;
        }

        return ($mo >= 1 && $mo <= 12) ? sprintf('%04d-%02d', $y, $mo) : null;
    }
}
