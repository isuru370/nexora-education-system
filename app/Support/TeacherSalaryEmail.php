<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Settings > Notifications: "Email salary slip to teacher" (per institute).
 * Off by default. When on, paying a teacher salary emails the teacher the
 * salary slip and the Student Payments (by collection date) report as PDFs.
 *
 * Stored in system_settings (teacher_salary_email).
 */
class TeacherSalaryEmail
{
    public const KEY = 'teacher_salary_email';

    protected const CACHE_KEY = 'nexora.teacher_salary_email';

    /** Shown at the bottom of the email and the PDFs. */
    public const POWERED_BY = 'Powered by NexoraIT · 0768971213';

    public static function enabled(): bool
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return SystemSetting::query()->where('key', self::KEY)->value('value') === '1';
        });
    }

    public static function save(bool $enabled): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => $enabled ? '1' : '0']
        );

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** The teacher's email when it can be used, otherwise null. */
    public static function addressOf(?string $email): ?string
    {
        $email = trim((string) $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
