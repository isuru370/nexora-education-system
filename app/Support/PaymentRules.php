<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Class fee payment rules, chosen per institute on the Payment Rules page
 * (Super Admin). All off by default, so institutes that do not use them see
 * no change.
 *
 *  custom_amount     cashier may take less than the fee; the rest is saved
 *                    as a discount and the month is paid
 *  attendance_rule   the fee for a month follows the student's attendance in
 *                    that class that month, e.g. 0 -> 0 % (no fee),
 *                    1 -> 50 %, more -> 100 % (tiers)
 *  tute              the payment screen shows / gives that month's tute
 *
 * Stored as rows in system_settings (payment_*).
 */
class PaymentRules
{
    public const CACHE_KEY = 'nexora.payment_rules';

    public const KEYS = [
        'custom_amount' => 'payment_custom_amount',
        'attendance_rule' => 'payment_attendance_rule',
        'tiers' => 'payment_attendance_tiers',
        'tute' => 'payment_tute',
    ];

    /** Used when the rule is first switched on. */
    public const DEFAULT_TIERS = [0 => 0, 1 => 50];

    public static function customAmount(): bool
    {
        return (bool) self::all()['custom_amount'];
    }

    public static function attendanceRule(): bool
    {
        return (bool) self::all()['attendance_rule'];
    }

    public static function tute(): bool
    {
        return (bool) self::all()['tute'];
    }

    /**
     * Attendance count => fee percent. Counts above the highest listed one
     * pay 100 %.
     *
     * @return array<int, int>
     */
    public static function tiers(): array
    {
        return self::all()['tiers'];
    }

    /** Fee percent for this many attendances (100 when the rule is off). */
    public static function percentFor(int $attendance): int
    {
        if (!self::attendanceRule()) {
            return 100;
        }

        return self::tiers()[$attendance] ?? 100;
    }

    /**
     * @return array{custom_amount: bool, attendance_rule: bool, tiers: array<int, int>, tute: bool}
     */
    public static function all(): array
    {
        $values = Cache::rememberForever(self::CACHE_KEY, function () {
            return SystemSetting::query()
                ->whereIn('key', array_values(self::KEYS))
                ->pluck('value', 'key')
                ->all();
        });

        $tiers = self::parseTiers($values[self::KEYS['tiers']] ?? null);

        return [
            'custom_amount' => ($values[self::KEYS['custom_amount']] ?? '0') === '1',
            'attendance_rule' => ($values[self::KEYS['attendance_rule']] ?? '0') === '1',
            'tiers' => $tiers ?: self::DEFAULT_TIERS,
            'tute' => ($values[self::KEYS['tute']] ?? '0') === '1',
        ];
    }

    /**
     * @param  array{custom_amount?: bool, attendance_rule?: bool, tiers?: array<int, int>, tute?: bool}  $rules
     */
    public static function save(array $rules): void
    {
        $values = [
            'custom_amount' => !empty($rules['custom_amount']) ? '1' : '0',
            'attendance_rule' => !empty($rules['attendance_rule']) ? '1' : '0',
            'tiers' => self::formatTiers($rules['tiers'] ?? self::DEFAULT_TIERS),
            'tute' => !empty($rules['tute']) ? '1' : '0',
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

    /**
     * "0:0,1:50" -> [0 => 0, 1 => 50]
     *
     * @return array<int, int>
     */
    public static function parseTiers(?string $value): array
    {
        $tiers = [];

        foreach (explode(',', (string) $value) as $pair) {
            if (!preg_match('/^\s*(\d+)\s*:\s*(\d+)\s*$/', $pair, $m)) {
                continue;
            }

            $tiers[(int) $m[1]] = min((int) $m[2], 100);
        }

        ksort($tiers);

        return $tiers;
    }

    /** [0 => 0, 1 => 50] -> "0:0,1:50" */
    public static function formatTiers(array $tiers): string
    {
        ksort($tiers);

        return collect($tiers)
            ->map(fn ($percent, $count) => ((int) $count) . ':' . min(max((int) $percent, 0), 100))
            ->implode(',');
    }

    /** "Attendance 1 → 50%" */
    public static function reason(int $attendance, int $percent): string
    {
        return $percent === 0
            ? "Attendance {$attendance}: no fee"
            : "Attendance {$attendance}: {$percent}% fee";
    }
}
