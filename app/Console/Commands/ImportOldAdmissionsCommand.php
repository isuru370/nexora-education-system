<?php

namespace App\Console\Commands;

use App\Models\Admission;
use App\Models\AdmissionPayment;
use App\Models\Student;
use App\Services\Import\OldValue;
use App\Services\Web\Receipt\ReceiptNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Bring the old system's admission payments into admission_payments.
 *
 *   php artisan nexora:import-admissions /path/admission_payments.csv --dry-run
 *   php artisan nexora:import-admissions /path/admission_payments.csv
 *
 * Run after nexora:import-students. Admission types are matched by name
 * (made when missing). The paid amount and date are kept; receipts get
 * the next REC numbers in date order. The same student paying the same
 * admission twice on one day (double click) is imported once. No SMS.
 * Imported rows carry "Imported from old system #<old id>" in the note,
 * so it is safe to run again.
 */
class ImportOldAdmissionsCommand extends Command
{
    protected $signature = 'nexora:import-admissions
        {file : CSV - old_id, custom_id, admission_name, admission_amount, amount, paid_at}
        {--dry-run : only report, change nothing}';

    protected $description = 'Import the old system admission payments';

    public const REQUIRED = ['old_id', 'custom_id', 'admission_name', 'amount', 'paid_at'];
    public const NOTE = 'Imported from old system #';

    public function handle(ReceiptNumberService $receipts): int
    {
        $dry = (bool) $this->option('dry-run');

        try {
            $rows = OldValue::csv((string) $this->argument('file'), self::REQUIRED);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Oldest first, so receipt numbers follow the dates
        usort($rows, fn ($a, $b) => [OldValue::time($a['paid_at']), (int) $a['old_id']] <=> [OldValue::time($b['paid_at']), (int) $b['old_id']]);

        $count = ['rows' => count($rows), 'created' => 0, 'exists' => 0, 'duplicate' => 0, 'no_student' => 0, 'bad' => 0, 'failed' => 0, 'total' => 0.0];
        $problems = [];
        $newTypes = [];

        // Admission types by name
        $admissions = Admission::query()->get()->keyBy(fn ($a) => mb_strtolower(trim($a->name)));

        $students = Student::query()->get(['id', 'custom_id', 'branch_id'])->keyBy(fn ($s) => strtoupper($s->custom_id));

        $imported = AdmissionPayment::withTrashed()
            ->where('note', 'like', self::NOTE . '%')
            ->pluck('note')
            ->map(fn ($n) => (int) substr($n, strlen(self::NOTE)))
            ->flip()
            ->all();

        // student|admission|day already paid (in the database or earlier in the file)
        $paidDays = AdmissionPayment::query()
            ->where('status', AdmissionPayment::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->get(['student_id', 'admission_id', 'paid_at'])
            ->mapWithKeys(fn ($p) => [$p->student_id . '|' . $p->admission_id . '|' . $p->paid_at->format('Y-m-d') => true])
            ->all();

        foreach ($rows as $row) {
            $oldId = (int) $row['old_id'];
            $id = strtoupper((string) OldValue::text($row['custom_id']));
            $name = OldValue::text($row['admission_name'], 150);
            $amount = is_numeric(trim($row['amount'])) ? round((float) $row['amount'], 2) : null;
            $paidAt = OldValue::text($row['paid_at']) ? OldValue::time($row['paid_at']) : null;

            if ($oldId <= 0 || $name === null || $amount === null || $paidAt === null) {
                $count['bad']++;
                $problems[] = "#{$oldId} {$id}: NOT imported - no admission / amount / date";
                continue;
            }

            if (isset($imported[$oldId])) {
                $count['exists']++;
                continue;
            }

            $student = $students[$id] ?? null;
            if (!$student) {
                $count['no_student']++;
                $problems[] = "#{$oldId} {$id}: NOT imported - student not found (import students first)";
                continue;
            }

            $key = mb_strtolower($name);
            $admission = $admissions[$key] ?? null;
            if (!$admission) {
                $typeAmount = is_numeric(trim($row['admission_amount'] ?? '')) ? (float) $row['admission_amount'] : $amount;
                $admission = $dry
                    ? new Admission(['name' => $name, 'amount' => $typeAmount])
                    : Admission::create(['name' => $name, 'amount' => $typeAmount, 'is_active' => true]);
                $admissions[$key] = $admission;
                $newTypes[] = "{$name} ({$typeAmount})";
            }

            $dayKey = $student->id . '|' . ($admission->id ?? $key) . '|' . $paidAt->format('Y-m-d');
            if (isset($paidDays[$dayKey])) {
                $count['duplicate']++;
                $problems[] = "#{$oldId} {$id}: NOT imported - {$name} already paid that day (double entry, Rs. {$amount})";
                continue;
            }

            if (!$dry) {
                try {
                    DB::transaction(function () use ($student, $admission, $amount, $paidAt, $oldId, $receipts) {
                        $payment = new AdmissionPayment([
                            'branch_id' => $student->branch_id,
                            'student_id' => $student->id,
                            'admission_id' => $admission->id,
                            'user_id' => null,
                            'amount' => $amount,
                            'payment_method' => 'cash',
                            'status' => AdmissionPayment::STATUS_PAID,
                            'paid_at' => $paidAt,
                            'receipt_number' => $receipts->generate(),
                            'note' => self::NOTE . $oldId,
                        ]);
                        $payment->created_at = $paidAt;
                        $payment->updated_at = $paidAt;
                        $payment->save();

                        // same as paying from the web
                        Student::query()->whereKey($student->id)->where('admission', false)->update(['admission' => true]);
                    });
                } catch (Throwable $e) {
                    $count['failed']++;
                    $problems[] = "#{$oldId} {$id}: NOT imported - " . strtok($e->getMessage(), "\n");
                    continue;
                }
            }

            $paidDays[$dayKey] = true;
            $imported[$oldId] = true;
            $count['created']++;
            $count['total'] += $amount;
        }

        $this->newLine();
        $this->line($dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        if ($newTypes) {
            $this->line(($dry ? 'Admission types to add: ' : 'Admission types added: ') . implode(', ', $newTypes));
        }
        $this->table(['', 'Payments'], [
            ['In the file', $count['rows']],
            [$dry ? 'Would be imported' : 'Imported', $count['created']],
            ['  total (Rs.)', number_format($count['total'], 2)],
            ['Already imported (left as they are)', $count['exists']],
            ['Skipped: paid twice the same day', $count['duplicate']],
            ['Skipped: student not found', $count['no_student']],
            ['Skipped: no admission / amount / date', $count['bad']],
            ['Failed', $count['failed']],
        ]);

        $log = OldValue::writeProblems('import-admissions', $problems, fn ($l) => $this->line($l));
        if ($log) {
            $this->warn(count($problems) . ' lines - full list: ' . $log);
        }

        return $count['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
