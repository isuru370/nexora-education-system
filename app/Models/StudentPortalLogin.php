<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class StudentPortalLogin extends Model
{
    use HasApiTokens;
    use SoftDeletes;

    /**
     * Token kept when the password changes (the one used to change it).
     * Every other parent API token is revoked.
     */
    public ?int $keepTokenId = null;

    protected static function booted(): void
    {
        static::updated(function (StudentPortalLogin $login) {
            if ($login->wasChanged('password') || ($login->wasChanged('is_active') && !$login->is_active)) {
                $login->tokens()
                    ->when($login->keepTokenId && $login->is_active, fn ($q) => $q->where('id', '!=', $login->keepTokenId))
                    ->delete();
            }
        });

        static::deleted(fn (StudentPortalLogin $login) => $login->tokens()->delete());
    }

    protected $fillable = [
        'student_id',
        'username',
        'password',
        'is_verified',
        'is_active',
        'otp',
        'otp_expires_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'otp',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'otp_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * Hash password automatically.
     */
    public function setPasswordAttribute($value): void
    {
        if (!empty($value) && strlen($value) !== 60) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Student who owns this portal login.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}