<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryIdCard extends Model
{
    protected $table = 'temporary_id_cards';

    protected $fillable = [
        'temporary_id_number',
        'card_number',
        'student_id',
        'activated_at',
        'status',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /*
    |--------------------------------------------------------------------------
    | QR Code
    |--------------------------------------------------------------------------
    */

    public function getQrBase64Attribute()
    {
        return 'data:image/svg+xml;base64,' . base64_encode(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(220)
                ->margin(0)
                ->backgroundColor(255, 255, 255, 0)
                ->generate($this->temporary_id_number)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDownloaded(): bool
    {
        return $this->status === 'downloaded';
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDownloaded($query)
    {
        return $query->where('status', 'downloaded');
    }

    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }
}
