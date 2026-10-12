<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmToken extends Model
{
    protected $fillable = [
        'student_id',
        'token',
        'device_id',
        'device_name',
        'device_type',
        'app_version',
        'is_active',
        'last_login_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeAndroid($query)
    {
        return $query->where('device_type', 'android');
    }

    public function scopeIos($query)
    {
        return $query->where('device_type', 'ios');
    }

    public function getDeviceTypeLabelAttribute(): string
    {
        return match ($this->device_type) {
            'android' => 'Android',
            'ios' => 'iOS',
            default => 'Unknown',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getMaskedTokenAttribute(): string
    {
        return substr($this->token, 0, 15)
            . '...'
            . substr($this->token, -6);
    }

    public function getDeviceIconAttribute(): string
    {
        return match ($this->device_type) {
            'android' => 'bi-phone',
            'ios' => 'bi-apple',
            default => 'bi-device',
        };
    }
}