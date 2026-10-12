<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassSchedulePattern extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'student_class_id',
        'class_category_fee_id',
        'class_hall_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'class_day',
        'is_active',
        'note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Branch this schedule pattern belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Generated schedules from this pattern.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(
            ClassSchedule::class,
            'class_schedule_pattern_id'
        );
    }

    /**
     * Student class.
     */
    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(
            StudentClass::class,
            'student_class_id'
        );
    }

    /**
     * Class category fee.
     */
    public function categoryFee(): BelongsTo
    {
        return $this->belongsTo(
            ClassCategoryFee::class,
            'class_category_fee_id'
        );
    }

    /**
     * Class hall.
     */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(
            ClassHall::class,
            'class_hall_id'
        );
    }

    /**
     * Scope: active patterns only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: patterns belonging to given branches.
     */
    public function scopeForBranches($query, $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Scope: patterns belonging to a specific branch.
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}