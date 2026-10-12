<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Users belonging to this user type.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
    /**
     * Scope: Get active user types only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}