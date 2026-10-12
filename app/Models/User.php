<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Hash password automatically when setting a new password.
     */
    public function setPasswordAttribute($value): void
    {
        if (!empty($value) && strlen($value) !== 60) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Check whether the user is a super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return $this->userType
            && $this->userType->code === 'SUPER_ADMIN';
    }

    /**
     * System user profile.
     */
    public function systemUser(): HasOne
    {
        return $this->hasOne(SystemUser::class);
    }

    /**
     * User type.
     */
    public function userType(): BelongsTo
    {
        return $this->belongsTo(
            UserType::class,
            'user_type_id'
        );
    }

    /**
     * Payments created by this user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Teacher profile.
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Branches accessible by this user.
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'branch_users',
            'user_id',
            'branch_id'
        )->withTimestamps();
    }
}