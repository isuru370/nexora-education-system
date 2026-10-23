<?php

use App\Support\Features;

if (!function_exists('feature')) {
    /** Is this feature switched on for this institute? (config/features.php) */
    function feature(string $key): bool
    {
        return Features::enabled($key);
    }
}
