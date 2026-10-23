<?php

namespace App\Support;

use App\Models\Feature;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Features switched on for this institute.
 *
 * The list of features is config/features.php; whether each one is on is in
 * the "features" table (default off). Read once and cached until changed.
 */
class Features
{
    public const CACHE_KEY = 'nexora.features';

    /** Is the feature on for this institute? Unknown features are off. */
    public static function enabled(string $key): bool
    {
        return self::all()[$key] ?? false;
    }

    /**
     * Every known feature => on / off.
     *
     * @return array<string, bool>
     */
    public static function all(): array
    {
        $enabled = self::enabledKeys();

        $features = [];
        foreach (array_keys(self::known()) as $key) {
            $features[$key] = in_array($key, $enabled, true);
        }

        return $features;
    }

    /** @return array<string, array{name: string, description: string}> */
    public static function known(): array
    {
        return config('features', []);
    }

    public static function isKnown(string $key): bool
    {
        return array_key_exists($key, self::known());
    }

    public static function set(string $key, bool $enabled): void
    {
        if (!self::isKnown($key)) {
            throw new InvalidArgumentException("Unknown feature [{$key}]. Add it to config/features.php first.");
        }

        Feature::query()->updateOrCreate(['key' => $key], ['is_enabled' => $enabled]);

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return string[] */
    protected static function enabledKeys(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => Feature::query()
                ->where('is_enabled', true)
                ->pluck('key')
                ->all());
        } catch (Throwable $e) {
            // e.g. before the first migration: every feature is off.
            Log::warning('Features could not be read: ' . $e->getMessage());

            return [];
        }
    }
}
