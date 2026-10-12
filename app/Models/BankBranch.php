<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankBranch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_id',
        'branch_name',
        'branch_code',
    ];

    /**
     * Bank this branch belongs to.
     */
    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Teachers using this bank branch.
     */
    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}