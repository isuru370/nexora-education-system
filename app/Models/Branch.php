<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address1',
        'address2',
        'address3',
        'mobile',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Users assigned to this branch.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'branch_users',
            'branch_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Teachers assigned to this branch.
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(
            Teacher::class,
            'teacher_branches',
            'branch_id',
            'teacher_id'
        )->withTimestamps();
    }

    /**
     * Organizers assigned to this branch.
     */
    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(
            Organizer::class,
            'organizer_branches',
            'branch_id',
            'organizer_id'
        )->withTimestamps();
    }

    /**
     * Students belonging to this branch.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Classes belonging to this branch.
     */
    public function studentClasses(): HasMany
    {
        return $this->hasMany(StudentClass::class);
    }
}