<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sri Lankan bank branches (database/seeders/data/bank_branches.csv:
 * bank_code, branch_code, branch_name). Branches are matched to banks by
 * bank code, and only the missing ones are added - safe to run on every
 * database, every deploy. Run BanksTableSeeder first.
 */
class BankBranchesSeeder extends Seeder
{
    public const CSV = 'data/bank_branches.csv';

    /** @return array{added: int, total: int, unknown_banks: string[]} */
    public function run(): array
    {
        $bankIds = DB::table('banks')->pluck('id', 'bank_code')
            ->mapWithKeys(fn ($id, $code) => [(string) $code => (int) $id])
            ->all();

        $existing = [];
        foreach (DB::table('bank_branches')->whereNull('deleted_at')->get(['bank_id', 'branch_code', 'branch_name']) as $b) {
            $existing[$this->key($b->bank_id, $b->branch_code, $b->branch_name)] = true;
        }

        $now = now();
        $rows = [];
        $unknown = [];

        foreach ($this->csvRows() as [$bankCode, $branchCode, $branchName]) {
            $bankId = $bankIds[$bankCode] ?? null;

            if ($bankId === null) {
                $unknown[$bankCode] = true;
                continue;
            }

            $key = $this->key($bankId, $branchCode, $branchName);
            if (isset($existing[$key])) {
                continue;
            }

            $existing[$key] = true;
            $rows[] = [
                'bank_id' => $bankId,
                'branch_name' => $branchName,
                'branch_code' => $branchCode,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('bank_branches')->insert($chunk);
        }

        $result = [
            'added' => count($rows),
            'total' => DB::table('bank_branches')->whereNull('deleted_at')->count(),
            'unknown_banks' => array_keys($unknown),
        ];

        $this->command?->info("Bank branches: {$result['added']} added, {$result['total']} in total.");

        return $result;
    }

    /** @return \Generator<array{0: string, 1: string, 2: string}> */
    protected function csvRows(): \Generator
    {
        $handle = fopen(__DIR__ . '/' . self::CSV, 'r');
        fgetcsv($handle); // header

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 3 && trim($row[0]) !== '') {
                yield [trim($row[0]), trim($row[1]), trim($row[2])];
            }
        }

        fclose($handle);
    }

    protected function key(int $bankId, string $code, string $name): string
    {
        return $bankId . '|' . trim($code) . '|' . mb_strtolower(trim($name));
    }
}
