<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * What teachers can do in the staff app (Settings > Teacher App,
 * Admin and Super Admin). All off until the institute turns them on.
 *
 *   attendance     mark attendance in their own classes / courses (QR + list)
 *   schedule       change their own class days (edit / extra day / cancel)
 *   schedule_auto  on: changes apply at once; off: an admin approves them
 *   payments       see their earnings, salary, advances / welfare and
 *                  which students paid
 */
class TeacherAppRules
{
    public const KEYS = [
        'attendance' => 'teacher_app_attendance',
        'schedule' => 'teacher_app_schedule',
        'schedule_auto' => 'teacher_app_schedule_auto',
        'payments' => 'teacher_app_payments',
    ];

    protected const CACHE_KEY = 'teacher_app_rules';

    public static function allows(string $name): bool
    {
        return self::all()[$name] ?? false;
    }

    /** @return array{attendance: bool, schedule: bool, schedule_auto: bool, payments: bool} */
    public static function all(): array
    {
        $values = Cache::rememberForever(self::CACHE_KEY, fn () => SystemSetting::query()
            ->whereIn('key', array_values(self::KEYS))
            ->pluck('value', 'key')
            ->all());

        $rules = [];
        foreach (self::KEYS as $name => $key) {
            $rules[$name] = ($values[$key] ?? '0') === '1';
        }

        return $rules;
    }

    /** @param  array<string, bool>  $rules */
    public static function save(array $rules): void
    {
        foreach (self::KEYS as $name => $key) {
            SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => !empty($rules[$name]) ? '1' : '0']);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /** For the app: which teacher screens to show. */
    public static function features(): array
    {
        $rules = self::all();

        return $rules + ['courses' => feature('courses')];
    }
}
