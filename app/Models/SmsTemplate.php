<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An edited SMS text. Read through App\Support\SmsTemplates (cached).
 */
class SmsTemplate extends Model
{
    protected $fillable = [
        'key',
        'body',
        'updated_by',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
