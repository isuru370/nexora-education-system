<?php

namespace App\Console\Commands;

use App\Models\PaymentSplitSnapshot;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use App\Services\Import\OldValue;
use App\Services\Web\TeacherSalary\TeacherSalaryCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Close the teacher salary months that were settled in the old system, so
 * the new system never pays them again.
 *
 *   php artisan nexora:import-teacher-salaries teacher_payments.csv --settled-until=2026-08 --dry-run
 *   php artisan nexora:import-teacher-salaries teacher_payments.csv --settled-until=2026-08
 *
 * Run after nexora:import-payments (the earnings come from the imported
 * payments). CSV (old teacher payments): old_id, teacher_custom_id,
 * reason_code, reason, amount, paid_at, salary_month (2025-02 / "02 2025"),
 * status.
 *
 * - Every month up to --settled-until in which the teacher earned or was
 *   paid becomes a "paid" teacher_salaries row: gross = the earnings in the
 *   new system, net = what the old system paid (salary + advances), with a
 *   note. Months with earnings but no old payment are closed too (listed).
 * - After --settled-until, only the months the old system paid are closed;
 *   the others stay open, to be paid from the new system.
 *
 * A month already in teacher_salaries is left as it is (safe to run again).
 */
class ImportOldTeacherSalariesCommand extends Command
{
    protected $signature = 'nexora:import-teacher-salaries
        {file : CSV - old_id, teacher_custom_id, reason_code, reason, amount, paid_at, salary_month, status}
        {--settled-until= : last month settled in the old system (YYYY-MM)}
        {--branch= : branch id / code / name (default: the only branch, or MAIN)}
        {--dry-run : only report, change nothing}';

    protected $description = 'Close the teacher salary months settled in the old system';

    public const REQUIRED = ['old_id', 'teacher_custom_id', 'amount', 'paid_at', 'salary_month'];

    public function handle(TeacherSalaryCalculator $calculator): int
    {
        $dry = (bool) $this->option('dry-run');
        $until = (string) $this->option('settled-until');

        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $until)) {
            $this->error('Give the last settled month: --settled-until=YYYY-MM');

            return self::FAILURE;
        }

        try {
            $rows = OldValue::csv((string) $this->argument('file'), self::REQUIRED);
            $branch = OldValue::branch($this->option('branch'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $teachers = Teacher::query()->get(['id', 'custom_id'])->keyBy(fn ($t) => strtoupper($t->custom_id));
        $problems = [];

        // Old payments per teacher + month
        $old = [];   // "teacherId|YYYY-MM" => [salary, advance, other, last date, lines]
        foreach ($rows as $row) {
            $teacher = $teachers[strtoupper((string) OldValue::text($row['teacher_custom_id']))] ?? null;
            $month = $this->month($row['salary_month']);
            $amount = is_numeric(trim($row['amount'])) ? round((float) $row['amount'], 2) : null;

            if (!OldValue::bool($row['status'] ?? null, true)) {
                continue;
            }
            if (!$teacher || !$month || $amount === null) {
                $problems[] = "old payment #{$row['old_id']}: skipped - " . (!$teacher ? 'teacher ' . $row['teacher_custom_id'] . ' not found' : 'no month / amount');
                continue;
            }

            $key = $teacher->id . '|' . $month;
            $old[$key] ??= ['salary' => 0.0, 'advance' => 0.0, 'paid_at' => null, 'lines' => []];
            $type = ($row['reason_code'] ?? '') === 'salary' ? 'salary' : 'advance';
            $old[$key][$type] += $amount;
            $paidAt = OldValue::time($row['paid_at']);
            if (!$old[$key]['paid_at'] || $paidAt->gt($old[$key]['paid_at'])) {
                $old[$key]['paid_at'] = $paidAt;
            }
            $label = trim((string) ($row['reason'] ?? '')) ?: ($type === 'salary' ? 'Salary' : 'Advance');
            $old[$key]['lines'][] = "{$label} " . number_format($amount, 2) . ' on ' . $paidAt->format('Y-m-d');
        }

        // Months: from the first earning / old payment to the settled month
        $oldMonths = collect(array_keys($old))->map(fn ($k) => explode('|', $k)[1]);
        $firstPayment = PaymentSplitSnapshot::query()->where('branch_id', $branch->id)->min('payment_date');
        $first = $oldMonths->merge($firstPayment ? [Carbon::parse($firstPayment)->format('Y-m')] : [])->min();
        $last = max($until, (string) $oldMonths->max());

        if (!$first) {
            $this->warn('Nothing to do: no old payments and no imported class payments.');

            return self::SUCCESS;
        }

        $months = [];
        for ($m = Carbon::createFromFormat('Y-m-d', $first . '-01'); $m->format('Y-m') <= $last; $m->addMonth()) {
            $months[] = $m->format('Y-m');
        }

        $existing = TeacherSalary::withTrashed()->where('branch_id', $branch->id)->get(['teacher_id', 'salary_year', 'salary_month'])
            ->mapWithKeys(fn ($s) => [$s->teacher_id . '|' . sprintf('%04d-%02d', $s->salary_year, $s->salary_month) => true])->all();

        $count = ['closed' => 0, 'matched' => 0, 'different' => 0, 'not_paid_closed' => 0, 'open' => 0, 'exists' => 0];
        $open = [];

        foreach ($months as $month) {
            [$y, $mo] = array_map('intval', explode('-', $month));
            $gross = $calculator->grossByTeacher($branch->id, $y, $mo);
            $teacherIds = collect($gross->keys())
                ->merge(collect(array_keys($old))->filter(fn ($k) => str_ends_with($k, '|' . $month))->map(fn ($k) => (int) explode('|', $k)[0]))
                ->unique();

            foreach ($teacherIds as $teacherId) {
                $key = $teacherId . '|' . $month;
                $earned = round((float) $gross->get($teacherId, 0), 2);
                $paid = $old[$key] ?? null;
                $custom = optional($teachers->first(fn ($t) => $t->id === (int) $teacherId))->custom_id;

                if ($earned <= 0 && !$paid) {
                    continue;
                }
                if (isset($existing[$key])) {
                    $count['exists']++;
                    continue;
                }

                $settled = $month <= $until;
                if (!$settled && !$paid) {
                    $count['open']++;
                    $open[] = "{$custom} {$month}: open - pay from the new system (LKR " . number_format($earned, 2) . ')';
                    continue;
                }

                $paidTotal = $paid ? round($paid['salary'] + $paid['advance'], 2) : 0.0;
                if (!$paid) {
                    $count['not_paid_closed']++;
                    $problems[] = "{$custom} {$month}: closed with no payment in the old system (earned LKR " . number_format($earned, 2) . ')';
                    $note = 'Settled in the old system (no salary payment recorded there). Earned LKR ' . number_format($earned, 2) . '.';
                } else {
                    abs($paidTotal - $earned) < 1 ? $count['matched']++ : $count['different']++;
                    if (abs($paidTotal - $earned) >= 1) {
                        $problems[] = "{$custom} {$month}: old system paid LKR " . number_format($paidTotal, 2) . ', earned LKR ' . number_format($earned, 2);
                    }
                    $note = 'Paid in the old system: ' . implode('; ', $paid['lines']) . '.';
                }

                if (!$dry) {
                    $salary = new TeacherSalary([
                        'branch_id' => $branch->id,
                        'teacher_id' => $teacherId,
                        'user_id' => null,
                        'salary_year' => $y,
                        'salary_month' => $mo,
                        'gross_amount' => $earned,
                        'advance_deduction' => $paid ? round($paid['advance'], 2) : 0,
                        'other_deduction' => 0,
                        'bonus_amount' => 0,
                        'net_amount' => $paidTotal,
                        'status' => 'paid',
                        'paid_at' => $paid['paid_at'] ?? Carbon::create($y, $mo, 1)->endOfMonth(),
                        'note' => $note,
                    ]);
                    $salary->save();
                }

                $existing[$key] = true;
                $count['closed']++;
            }
        }

        $this->newLine();
        $this->line($dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        $this->line("Branch: {$branch->name} - settled in the old system until {$until}");
        $this->table(['', 'Teacher months'], [
            [$dry ? 'To close as paid' : 'Closed as paid', $count['closed']],
            ['  old payment = earnings', $count['matched']],
            ['  old payment different (listed)', $count['different']],
            ['  no payment in the old system (listed)', $count['not_paid_closed']],
            ['Left open (pay from the new system)', $count['open']],
            ['Already in teacher salaries', $count['exists']],
        ]);

        $log = OldValue::writeProblems('import-teacher-salaries', array_merge($problems, $open), fn ($l) => $this->line($l));
        if ($log) {
            $this->warn(count($problems) + count($open) . ' lines - full list: ' . $log);
        }

        return self::SUCCESS;
    }

    protected function month(?string $value): ?string
    {
        $value = trim((string) $value);
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $m)) {
            [$y, $mo] = [(int) $m[1], (int) $m[2]];
        } elseif (preg_match('/^(\d{1,2})[ \/-](\d{4})$/', $value, $m)) {
            [$y, $mo] = [(int) $m[2], (int) $m[1]];
        } else {
            return null;
        }

        return ($mo >= 1 && $mo <= 12) ? sprintf('%04d-%02d', $y, $mo) : null;
    }
}
