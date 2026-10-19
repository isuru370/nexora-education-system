<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Parent notification switches for the whole system.
 *
 *   NotificationSetting::sends('payment', NotificationSetting::SMS)   // true / false
 *   NotificationSetting::isEnabled('registration')
 *
 * Read from cache; the cache is cleared whenever a setting is saved.
 */
class NotificationSetting extends Model
{
    public const PUSH = 'push';
    public const SMS = 'sms';

    public const REGISTRATION = 'registration';
    public const PAYMENT = 'payment';
    public const ADMISSION_PAYMENT = 'admission_payment';
    public const ATTENDANCE = 'attendance';
    public const CLASS_SCHEDULE = 'class_schedule';

    protected const CACHE_KEY = 'notification_settings.v1';

    /**
     * Used when the table is not migrated yet (keeps the old behaviour).
     */
    protected const FALLBACK = [
        self::REGISTRATION => ['is_enabled' => true, 'channel' => self::SMS],
        self::PAYMENT => ['is_enabled' => true, 'channel' => self::PUSH],
        self::ADMISSION_PAYMENT => ['is_enabled' => false, 'channel' => self::PUSH],
        self::ATTENDANCE => ['is_enabled' => true, 'channel' => self::PUSH],
        self::CLASS_SCHEDULE => ['is_enabled' => true, 'channel' => self::SMS],
    ];

    protected $fillable = [
        'key',
        'label',
        'description',
        'is_enabled',
        'channel',
        'channel_locked',
        'sort_order',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'channel_locked' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Lookups
    |--------------------------------------------------------------------------
    */

    /** Is this notification switched on? */
    public static function isEnabled(string $key): bool
    {
        return (bool) (static::settingsMap()[$key]['is_enabled'] ?? false);
    }

    /** push / sms, or null when switched off. */
    public static function channelFor(string $key): ?string
    {
        $setting = static::settingsMap()[$key] ?? null;

        if (!$setting || !$setting['is_enabled']) {
            return null;
        }

        return $setting['channel'];
    }

    /** True when $key is on and uses $channel. */
    public static function sends(string $key, string $channel): bool
    {
        return static::channelFor($key) === $channel;
    }

    /**
     * [key => ['is_enabled' => bool, 'channel' => 'push'|'sms']]
     */
    public static function settingsMap(): array
    {
        try {
            $cached = Cache::get(static::CACHE_KEY);

            if (is_array($cached)) {
                return $cached;
            }

            // Not migrated yet: keep the old behaviour, and do not cache it
            if (!Schema::hasTable('notification_settings')) {
                return static::FALLBACK;
            }

            $map = static::query()
                ->get(['key', 'is_enabled', 'channel'])
                ->mapWithKeys(fn ($row) => [
                    $row->key => [
                        'is_enabled' => (bool) $row->is_enabled,
                        'channel' => $row->channel,
                    ],
                ])
                ->all() + static::FALLBACK;

            Cache::forever(static::CACHE_KEY, $map);

            return $map;
        } catch (Throwable $e) {
            Log::warning('Notification settings could not be read, using defaults.', [
                'error' => $e->getMessage(),
            ]);

            return static::FALLBACK;
        }
    }

    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }
}
