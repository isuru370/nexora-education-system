<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Str;

/**
 * Staff app setup by QR code.
 *
 * The web page shows a QR with this server's address and a secret setup key.
 * The app accepts only https *.nexorait.lk addresses, then sends the key to
 * POST /api/app-setup/verify on that server; only a matching key lets the
 * app save the institute. Users still log in with their own email/password.
 * A new key (Regenerate) stops old QR prints working; phones already set up
 * are not affected.
 */
class AppSetup
{
    public const SETTING = 'app_setup_key';

    public static function key(): string
    {
        $setting = SystemSetting::query()->where('key', self::SETTING)->first();

        if ($setting && $setting->value) {
            return $setting->value;
        }

        return self::regenerate();
    }

    public static function regenerate(): string
    {
        $key = Str::random(40);

        SystemSetting::query()->updateOrCreate(['key' => self::SETTING], ['value' => $key]);

        return $key;
    }

    public static function verify(?string $key): bool
    {
        if (!is_string($key) || strlen($key) !== 40) {
            return false;
        }

        $current = SystemSetting::query()->where('key', self::SETTING)->value('value');

        return is_string($current) && hash_equals($current, $key);
    }

    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * What the QR holds. A URL, so a phone camera opens a help page
     * instead of showing random text.
     */
    public static function qrContent(): string
    {
        return self::baseUrl() . '/app-setup?key=' . self::key();
    }
}
