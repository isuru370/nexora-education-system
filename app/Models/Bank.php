<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_name',
        'bank_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Bank has many bank branches.
     */
    public function branches()
    {
        return $this->hasMany(BankBranch::class);
    }

    /**
     * Scope: Get active banks only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}