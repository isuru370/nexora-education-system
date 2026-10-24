<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A setting chosen by the developer for this institute (key => value).
 * Read it through App\Support classes (e.g. CardMode) - they are cached.
 */
class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];
}
