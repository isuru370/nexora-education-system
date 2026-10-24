<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * How this institute gives student ID cards. Chosen by the developer with
 * `php artisan nexora:card-mode` (there is no web page for it).
 *
 *   permanent  register the student with a permanent ID card straight away
 *   temporary  register with a temporary card (TMP001...), give the
 *              permanent card later ("Re Assign Student")
 *
 * Default: permanent.
 */
class CardMode
{
    public const PERMANENT = 'permanent';
    public const TEMPORARY = 'temporary';

    public const MODES = [self::PERMANENT, self::TEMPORARY];

    public const DEFAULT = self::PERMANENT;

    public const KEY = 'card_mode';
    public const CACHE_KEY = 'nexora.card_mode';

    public static function get(): string
    {
        try {
            $value = Cache::rememberForever(self::CACHE_KEY, fn () => SystemSetting::query()
                ->where('key', self::KEY)
                ->value('value'));
        } catch (Throwable $e) {
            // e.g. before the first migration
            Log::warning('Card mode could not be read: ' . $e->getMessage());
            $value = null;
        }

        return in_array($value, self::MODES, true) ? $value : self::DEFAULT;
    }

    public static function is(string $mode): bool
    {
        return self::get() === $mode;
    }

    public static function permanent(): bool
    {
        return self::is(self::PERMANENT);
    }

    public static function temporary(): bool
    {
        return self::is(self::TEMPORARY);
    }

    /** True when the mode was set with the command (not just the default). */
    public static function isSet(): bool
    {
        try {
            return SystemSetting::query()->where('key', self::KEY)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public static function set(string $mode): void
    {
        if (!in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException('Card mode must be one of: ' . implode(', ', self::MODES));
        }

        SystemSetting::query()->updateOrCreate(['key' => self::KEY], ['value' => $mode]);

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** "This institute gives temporary cards first." */
    public static function label(?string $mode = null): string
    {
        return match ($mode ?? self::get()) {
            self::TEMPORARY => 'Temporary card first (permanent card later)',
            default => 'Permanent card at registration',
        };
    }
}
