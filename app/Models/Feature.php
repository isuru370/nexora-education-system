<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A feature switched on / off for this institute.
 * Use App\Support\Features (or feature()) to read it - it is cached.
 */
class Feature extends Model
{
    protected $fillable = [
        'key',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
