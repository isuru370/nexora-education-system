<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'custom_id',
        'user_id',
        'full_name',
        'initials',
        'email',
        'mobile',
        'nic',
        'bday',
        'gender',
        'address1',
        'address2',
        'address3',
        'is_active',
        'graduation_details',
        'experience',
        'account_number',
        'bank_branch_id',
    ];

    protected $casts = [
        'bday' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * User account linked to this teacher.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bank branch used for teacher payments.
     */
    public function bankBranch()
    {
        return $this->belongsTo(BankBranch::class);
    }

    /**
     * Classes handled by this teacher.
     */
    public function classes()
    {
        return $this->hasMany(StudentClass::class);
    }

    /**
     * Schedules belonging to this teacher's classes.
     */
    public function schedules()
    {
        return $this->hasManyThrough(
            ClassSchedule::class,
            StudentClass::class
        );
    }

    /**
     * Branches assigned to this teacher.
     */
    public function branches()
    {
        return $this->belongsToMany(
            Branch::class,
            'teacher_branches',
            'teacher_id',
            'branch_id'
        )->withTimestamps();
    }
}