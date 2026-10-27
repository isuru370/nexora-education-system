<?php

namespace App\Services\Import;

use RuntimeException;

/**
 * Reads the old system's quick photos from
 *  - a HeidiSQL / mysqldump .sql file of `quick_photo_details`, or
 *  - a .csv with the columns custom_id, quick_img, is_active (created_at optional).
 *
 * @return list<array{custom_id: string, image_url: string, is_active: bool, created_at: ?string}>
 */
class OldQuickPhotoReader
{
    public function read(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        return str_ends_with(strtolower($path), '.csv')
            ? $this->fromCsv($path)
            : $this->fromSql($path);
    }

    protected function fromSql(string $path): array
    {
        $sql = file_get_contents($path);

        if (!preg_match('/INSERT INTO `?quick_photo_details`?\s*\(([^)]*)\)\s*VALUES/i', $sql, $m, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException('No INSERT INTO `quick_photo_details` found in the file.');
        }

        $columns = array_map(fn ($c) => trim($c, " `\t\r\n"), explode(',', $m[1][0]));
        $body = substr($sql, $m[0][1] + strlen($m[0][0]));

        $rows = [];

        // one (... ) tuple per row; values are numbers, NULL or '...' strings
        preg_match_all("/\\(((?:'(?:[^'\\\\]|\\\\.|'')*'|[^()'])*)\\)/", $body, $tuples);

        foreach ($tuples[1] as $tuple) {
            preg_match_all("/'((?:[^'\\\\]|\\\\.|'')*)'|(NULL)|([^,\\s]+)/", $tuple, $parts, PREG_SET_ORDER);

            $values = array_map(function ($p) {
                if (($p[2] ?? '') === 'NULL') {
                    return null;
                }
                if (isset($p[3]) && $p[3] !== '') {
                    return $p[3];
                }

                return str_replace(["\\'", "''", '\\\\'], ["'", "'", '\\'], $p[1]);
            }, $parts);

            if (count($values) !== count($columns)) {
                continue;
            }

            $rows[] = $this->row(array_combine($columns, $values));
        }

        return $rows;
    }

    protected function fromCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), fgetcsv($handle) ?: []);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if (count($line) === count($header)) {
                $rows[] = $this->row(array_combine($header, $line));
            }
        }

        fclose($handle);

        return $rows;
    }

    protected function row(array $data): array
    {
        return [
            'custom_id' => trim((string) ($data['custom_id'] ?? '')),
            'image_url' => trim((string) ($data['quick_img'] ?? $data['image'] ?? '')),
            'is_active' => (string) ($data['is_active'] ?? '1') === '1',
            'created_at' => $data['created_at'] ?? null,
        ];
    }
}
