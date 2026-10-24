<?php

use App\Support\CardMode;
use App\Support\Features;

if (!function_exists('feature')) {
    /** Is this feature switched on for this institute? (config/features.php) */
    function feature(string $key): bool
    {
        return Features::enabled($key);
    }
}

if (!function_exists('card_mode')) {
    /** "permanent" or "temporary" (php artisan nexora:card-mode) */
    function card_mode(): string
    {
        return CardMode::get();
    }
}
