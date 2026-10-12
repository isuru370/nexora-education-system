<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassHall extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'code',
        'hall_name',
        'hall_type',
        'hall_price',
        'is_active',
    ];

    protected $casts = [
        'hall_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Branch this hall belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Class schedules using this hall.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    /**
     * Schedule patterns using this hall.
     */
    public function classSchedulePatterns(): HasMany
    {
        return $this->hasMany(ClassSchedulePattern::class);
    }

    /**
     * Exams using this hall.
     */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Scope: active halls only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: halls belonging to given branches.
     */
    public function scopeForBranches($query, $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }
}