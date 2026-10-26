<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use App\Services\Import\OldValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Bring the old system's teachers into teachers (+ teacher_branches).
 *
 *   php artisan nexora:import-teachers /path/teachers.csv --dry-run
 *   php artisan nexora:import-teachers /path/teachers.csv
 *
 * The CSV has the old `teachers` columns; the bank branch is given by
 * bank_code + branch_code (+ branch_name), because the old ids differ.
 * Email and NIC must be unique: when the same teacher is in the file
 * twice, the active one is imported. Teachers get no login here (add it
 * from the web). Safe to run again.
 */
class ImportOldTeachersCommand extends Command
{
    protected $signature = 'nexora:import-teachers
        {file : CSV - custom_id, fname, lname, email, mobile, nic, bday, gender, address1-3, is_active, graduation_details, experience, account_number, bank_code, branch_code, branch_name, created_at, updated_at}
        {--branch= : branch id / code / name (default: the only branch, or MAIN)}
        {--dry-run : only report, change nothing}';

    protected $description = 'Import the old system teachers (Custom ID and bank details kept)';

    public const REQUIRED = ['custom_id', 'fname', 'lname', 'email', 'nic', 'bday'];

    public const TITLES = ['mr', 'mrs', 'ms', 'miss', 'dr', 'rev', 'prof', 'ven'];

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

        // Active rows first, so a teacher entered twice keeps the active row
        usort($rows, fn ($a, $b) => OldValue::bool($b['is_active'] ?? null, true) <=> OldValue::bool($a['is_active'] ?? null, true));

        $count = ['rows' => count($rows), 'created' => 0, 'exists' => 0, 'duplicate' => 0, 'bad' => 0, 'failed' => 0, 'bank' => 0, 'warnings' => 0];
        $problems = [];
        $warn = function (string $id, string $text) use (&$problems, &$count) {
            $problems[] = "{$id}: {$text}";
            $count['warnings']++;
        };

        $existing = Teacher::withTrashed()->pluck('custom_id')->map(fn ($id) => strtoupper($id))->flip()->all();
        $emails = Teacher::withTrashed()->pluck('custom_id', 'email')->mapWithKeys(fn ($id, $e) => [strtolower($e) => $id])->all();
        $nics = Teacher::withTrashed()->pluck('custom_id', 'nic')->mapWithKeys(fn ($id, $n) => [strtoupper($n) => $id])->all();

        foreach ($rows as $row) {
            $id = (string) OldValue::text($row['custom_id'] ?? '', 50);

            if ($id === '') {
                $count['bad']++;
                $problems[] = '(no custom_id): skipped';
                continue;
            }

            if (isset($existing[strtoupper($id)])) {
                $count['exists']++;
                continue;
            }

            $email = OldValue::email($row['email'] ?? null);
            $nic = OldValue::nic($row['nic'] ?? null);
            $bday = OldValue::date($row['bday'] ?? null);

            $missing = array_keys(array_filter(['email' => $email, 'NIC' => $nic, 'birthday' => $bday], fn ($v) => $v === null));
            if ($missing) {
                $count['bad']++;
                $problems[] = "{$id}: NOT imported - no valid " . implode(' / ', $missing);
                continue;
            }

            $same = $emails[$email] ?? $nics[$nic] ?? null;
            if ($same !== null) {
                $count['duplicate']++;
                $problems[] = "{$id}: NOT imported - same email / NIC as {$same} (entered twice)";
                continue;
            }

            $gender = OldValue::gender($row['gender'] ?? null);
            if ($gender === null) {
                $warn($id, 'no gender - set to male, check it');
                $gender = 'male';
            }

            $mobile = OldValue::phone($row['mobile'] ?? null) ?? '';
            if ($mobile === '' || OldValue::phoneLooksWrong($mobile)) {
                $warn($id, "mobile \"{$mobile}\" - check it");
            }

            [$fullName, $initials] = $this->names($row['fname'] ?? '', $row['lname'] ?? '');

            $bankBranchId = $this->bankBranchId($row);
            if ($bankBranchId === null && OldValue::text($row['branch_code'] ?? null) !== null) {
                $warn($id, 'bank branch ' . trim(($row['bank_code'] ?? '') . ' / ' . $row['branch_code']) . ' not found - left empty');
            }
            $bankBranchId !== null && $count['bank']++;

            $data = [
                'custom_id' => $id,
                'full_name' => $fullName,
                'initials' => $initials,
                'email' => $email,
                'mobile' => $mobile,
                'nic' => $nic,
                'bday' => $bday,
                'gender' => $gender,
                'address1' => OldValue::text($row['address1'] ?? null, 150) ?? '-',
                'address2' => OldValue::text($row['address2'] ?? null, 150),
                'address3' => OldValue::text($row['address3'] ?? null, 150),
                'is_active' => OldValue::bool($row['is_active'] ?? null, true),
                'graduation_details' => OldValue::text($row['graduation_details'] ?? null),
                'experience' => OldValue::text($row['experience'] ?? null),
                'account_number' => OldValue::text($row['account_number'] ?? null, 50),
                'bank_branch_id' => $bankBranchId,
            ];

            if (!$dry) {
                try {
                    DB::transaction(function () use ($data, $row, $branch) {
                        $teacher = new Teacher($data);
                        $teacher->created_at = OldValue::time($row['created_at'] ?? null);
                        $teacher->updated_at = OldValue::time(($row['updated_at'] ?? null) ?: ($row['created_at'] ?? null));
                        $teacher->save();
                        $teacher->branches()->syncWithoutDetaching([$branch->id]);
                    });
                } catch (Throwable $e) {
                    $count['failed']++;
                    $problems[] = "{$id}: NOT imported - " . strtok($e->getMessage(), "\n");
                    continue;
                }
            }

            $existing[strtoupper($id)] = true;
            $emails[$email] = $id;
            $nics[$nic] = $id;
            $count['created']++;
        }

        $this->newLine();
        $this->line($dry ? '<fg=yellow>DRY RUN - nothing was changed</>' : '<fg=green>Done</>');
        $this->line("Branch: {$branch->name}");
        $this->table(['', 'Teachers'], [
            ['In the file', $count['rows']],
            [$dry ? 'Would be imported' : 'Imported', $count['created']],
            ['  with bank details', $count['bank']],
            ['Already imported (left as they are)', $count['exists']],
            ['Skipped: entered twice (same email / NIC)', $count['duplicate']],
            ['Skipped: no custom_id / email / NIC / birthday', $count['bad']],
            ['Failed', $count['failed']],
            ['Notes to check', $count['warnings']],
        ]);

        $log = OldValue::writeProblems('import-teachers', $problems, fn ($l) => $this->line($l));
        if ($log) {
            $this->warn(count($problems) . ' lines - full list: ' . $log);
        }

        return $count['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * "Mr. Kamal" + "Perera" -> ["Mr. Kamal Perera", "Mr. K. Perera"].
     * Initials fit in 20 characters (the title is dropped first).
     *
     * @return array{0: string, 1: string}
     */
    public function names(string $first, string $last): array
    {
        $first = (string) OldValue::text($first);
        $last = (string) OldValue::text($last);
        $full = mb_substr(trim("{$first} {$last}"), 0, 150);

        $title = '';
        $letters = '';
        foreach (preg_split('/[\s.,]+/', $first, -1, PREG_SPLIT_NO_EMPTY) as $i => $part) {
            if ($i === 0 && in_array(strtolower($part), self::TITLES, true)) {
                $title = ucfirst(strtolower($part)) . '. ';
                continue;
            }
            $letters .= mb_strtoupper(mb_substr($part, 0, 1)) . '. ';
        }

        $short = trim($letters . $last) ?: $full;
        $initials = mb_strlen($title . $short) <= 20 ? $title . $short : $short;

        return [$full, mb_substr($initials, 0, 20)];
    }

    protected function bankBranchId(array $row): ?int
    {
        $bankCode = OldValue::text($row['bank_code'] ?? null);
        $branchCode = OldValue::text($row['branch_code'] ?? null);

        if ($bankCode === null || $branchCode === null) {
            return null;
        }

        $candidates = DB::table('bank_branches')
            ->join('banks', 'banks.id', '=', 'bank_branches.bank_id')
            ->whereNull('bank_branches.deleted_at')
            ->where('banks.bank_code', $bankCode)
            ->where('bank_branches.branch_code', $branchCode)
            ->get(['bank_branches.id', 'bank_branches.branch_name']);

        if ($candidates->count() > 1 && ($name = OldValue::text($row['branch_name'] ?? null))) {
            $match = $candidates->first(fn ($c) => strcasecmp(trim($c->branch_name), $name) === 0);
            if ($match) {
                return (int) $match->id;
            }
        }

        return $candidates->isEmpty() ? null : (int) $candidates->first()->id;
    }
}
