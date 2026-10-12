<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class StudentPortalLogin extends Model
{
    use SoftDeletes;

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