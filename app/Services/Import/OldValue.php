<?php

namespace App\Services\Import;

use App\Models\Branch;
use App\Models\Grade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Cleaning values that come from the old system (CSV export of its tables).
 */
class OldValue
{
    public const IMAGE_FOLDER = 'uploads';
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Read a CSV with a header row into [column => value] rows.
     *
     * @return array<int, array<string, string>>
     */
    public static function csv(string $file, array $required): array
    {
        if (!is_file($file)) {
            throw new RuntimeException("File not found: {$file}");
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);

        if (!$header) {
            fclose($handle);
            throw new RuntimeException("Empty file: {$file}");
        }

        $header = array_map(
            fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))),
            $header
        );

        $missing = array_diff($required, $header);
        if ($missing) {
            fclose($handle);
            throw new RuntimeException('Missing columns in the file: ' . implode(', ', $missing));
        }

        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null] || trim(implode('', $values)) === '') {
                continue;
            }
            $values = array_pad(array_slice($values, 0, count($header)), count($header), '');
            $rows[] = array_combine($header, array_map(fn ($v) => (string) $v, $values));
        }
        fclose($handle);

        return $rows;
    }

    /** Trimmed text, null for empty / "NULL", cut to $max characters. */
    public static function text(?string $value, ?int $max = null): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        if ($value === '' || strcasecmp($value, 'null') === 0) {
            return null;
        }

        return $max ? mb_substr($value, 0, $max) : $value;
    }

    public static function bool(?string $value, bool $default = false): bool
    {
        $value = strtolower(trim((string) $value));

        if ($value === '' || $value === 'null') {
            return $default;
        }

        return in_array($value, ['1', 'true', 'yes', 'active'], true);
    }

    /**
     * Sri Lankan phone number as 0XXXXXXXXX. Only the first of
     * "07X / 07Y" is kept; 94XXXXXXXXX and 7XXXXXXXX get their 0.
     */
    public static function phone(?string $value): ?string
    {
        $value = self::text($value);
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', preg_split('#[/,|&]#', $value)[0]);

        if (strlen($digits) === 11 && str_starts_with($digits, '94')) {
            $digits = '0' . substr($digits, 2);
        } elseif (strlen($digits) === 9 && $digits[0] !== '0') {
            $digits = '0' . $digits;
        }

        return $digits === '' ? null : substr($digits, 0, 15);
    }

    /** Not a 10 digit 0XXXXXXXXX number - worth a look. */
    public static function phoneLooksWrong(?string $phone): bool
    {
        return $phone !== null && !preg_match('/^0\d{9}$/', $phone);
    }

    /** Y-m-d from "2008-05-17", "05/17/2008" (old system: month first) or "17/05/2008". */
    public static function date(?string $value): ?string
    {
        $value = self::text($value);
        if ($value === null) {
            return null;
        }

        if (preg_match('#^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})#', $value, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('#^(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})$#', $value, $m)) {
            [$mo, $d, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if ($mo > 12 && $d <= 12) {
                [$mo, $d] = [$d, $mo];
            }
        } else {
            return null;
        }

        if ($y < 1900 || $y > (int) date('Y') || !checkdate($mo, $d, $y)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }

    public static function time(?string $value): Carbon
    {
        try {
            $value = self::text($value);

            return $value ? Carbon::parse($value) : now();
        } catch (Throwable) {
            return now();
        }
    }

    public static function gender(?string $value): ?string
    {
        $value = strtolower((string) self::text($value));

        return match ($value) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            'other' => 'other',
            default => null,
        };
    }

    public static function email(?string $value): ?string
    {
        $value = self::text($value);

        return $value && filter_var($value, FILTER_VALIDATE_EMAIL) ? mb_substr(strtolower($value), 0, 150) : null;
    }

    public static function nic(?string $value): ?string
    {
        $value = strtoupper(str_replace(' ', '', (string) self::text($value)));

        return $value === '' ? null : substr($value, 0, 20);
    }

    /**
     * Old grade name -> new grade id. "2027 A/L Grade" matches "2027 A/L",
     * "Grade 10" matches "10". Case and spaces do not matter.
     *
     * @return array<string, int> normalised name => id
     */
    public static function gradeMap(): array
    {
        return Grade::query()->get(['id', 'grade_name'])
            ->mapWithKeys(fn ($g) => [self::gradeKey($g->grade_name) => (int) $g->id])
            ->all();
    }

    public static function gradeKey(?string $name): string
    {
        $name = strtolower(trim(preg_replace('/\s+/', ' ', (string) $name)));
        $name = preg_replace('/^grade\s+/', '', $name);

        return trim(preg_replace('/\s+grade$/', '', $name));
    }

    /** --branch (id, code or name); without it the only / MAIN branch. */
    public static function branch(?string $option): Branch
    {
        if ($option !== null && $option !== '') {
            $branch = Branch::query()
                ->where('id', ctype_digit($option) ? (int) $option : 0)
                ->orWhereRaw('LOWER(code) = ?', [strtolower($option)])
                ->orWhereRaw('LOWER(name) = ?', [strtolower($option)])
                ->first();

            if (!$branch) {
                throw new RuntimeException("Branch not found: {$option}");
            }

            return $branch;
        }

        $branches = Branch::query()->where('is_active', true)->get();

        if ($branches->count() === 1) {
            return $branches->first();
        }

        $main = $branches->firstWhere('code', 'MAIN');
        if ($main) {
            return $main;
        }

        throw new RuntimeException('More than one branch - give it with --branch=<code>');
    }

    /**
     * Put the old picture in storage/app/public/uploads/<file name>,
     * copying it from $imagesDir when it is not there yet.
     *
     * @return array{0: ?string, 1: string} [path or null, 'in_storage' | 'copied' | 'missing' | 'none']
     */
    public static function image(?string $url, ?string $imagesDir, bool $dry): array
    {
        $url = self::text($url);
        $path = $url ? (string) parse_url($url, PHP_URL_PATH) : '';
        $file = basename($path);
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Only the old uploads (not e.g. the institute logo used as a placeholder)
        if ($file === '' || !in_array($ext, self::IMAGE_EXTENSIONS, true)
            || (str_contains($url, '://') && !str_contains($path, '/' . self::IMAGE_FOLDER . '/'))) {
            return [null, 'none'];
        }

        $disk = Storage::disk('public');
        $target = self::IMAGE_FOLDER . '/' . $file;

        if ($disk->exists($target)) {
            return [$target, 'in_storage'];
        }

        $source = $imagesDir !== null ? $imagesDir . DIRECTORY_SEPARATOR . $file : null;

        if ($source !== null && is_file($source)) {
            if (!$dry) {
                $disk->put($target, file_get_contents($source));
            }

            return [$target, 'copied'];
        }

        return [null, 'missing'];
    }

    /** Write the problem list and show the first lines. */
    public static function writeProblems(string $name, array $problems, callable $line): ?string
    {
        if (!$problems) {
            return null;
        }

        $log = storage_path("logs/{$name}-problems.txt");
        file_put_contents($log, implode(PHP_EOL, $problems) . PHP_EOL);

        foreach (array_slice($problems, 0, 15) as $p) {
            $line('  ' . $p);
        }

        return $log;
    }
}
