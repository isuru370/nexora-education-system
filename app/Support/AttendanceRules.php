<?php

namespace App\Support;

use App\Models\SystemSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * When QR attendance can be marked, chosen per institute on the
 * Payment & Attendance Rules page (Super Admin).
 *
 *  before_minutes  attendance opens this long before the class starts (60)
 *  after_minutes   and stays open this long after it ends (0)
 *  no_time_limit   any of the student's classes today can be marked; with
 *                  more than one the cashier picks the class
 *
 * The defaults are the old fixed rule (1 hour before until the end), so
 * institutes that do not change anything see no difference.
 */
class AttendanceRules
{
    public const CACHE_KEY = 'nexora.attendance_rules';

    public const DEFAULT_BEFORE = 60;

    public const DEFAULT_AFTER = 0;

    public const MAX_MINUTES = 720;

    public const KEYS = [
        'before_minutes' => 'attendance_before_minutes',
        'after_minutes' => 'attendance_after_minutes',
        'no_time_limit' => 'attendance_no_time_limit',
    ];

    /**
     * @return array{before_minutes: int, after_minutes: int, no_time_limit: bool}
     */
    public static function all(): array
    {
        $values = Cache::rememberForever(self::CACHE_KEY, function () {
            return SystemSetting::query()
                ->whereIn('key', array_values(self::KEYS))
                ->pluck('value', 'key')
                ->all();
        });

        return [
            'before_minutes' => self::minutes($values[self::KEYS['before_minutes']] ?? null, self::DEFAULT_BEFORE),
            'after_minutes' => self::minutes($values[self::KEYS['after_minutes']] ?? null, self::DEFAULT_AFTER),
            'no_time_limit' => ($values[self::KEYS['no_time_limit']] ?? '0') === '1',
        ];
    }

    public static function noTimeLimit(): bool
    {
        return self::all()['no_time_limit'];
    }

    /** Is attendance open now for a class from $start to $end (today)? */
    public static function isOpen(CarbonInterface $now, CarbonInterface $start, CarbonInterface $end): bool
    {
        $rules = self::all();

        if ($rules['no_time_limit']) {
            return true;
        }

        return $now->betweenIncluded(
            $start->copy()->subMinutes($rules['before_minutes']),
            $end->copy()->addMinutes($rules['after_minutes'])
        );
    }

    /**
     * @param  array{before_minutes?: int, after_minutes?: int, no_time_limit?: bool}  $rules
     */
    public static function save(array $rules): void
    {
        $values = [
            'before_minutes' => (string) self::minutes($rules['before_minutes'] ?? null, self::DEFAULT_BEFORE),
            'after_minutes' => (string) self::minutes($rules['after_minutes'] ?? null, self::DEFAULT_AFTER),
            'no_time_limit' => !empty($rules['no_time_limit']) ? '1' : '0',
        ];

        foreach ($values as $name => $value) {
            SystemSetting::query()->updateOrCreate(['key' => self::KEYS[$name]], ['value' => $value]);
        }

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function minutes($value, int $default): int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $default;
        }

        return max(0, min((int) $value, self::MAX_MINUTES));
    }
}
