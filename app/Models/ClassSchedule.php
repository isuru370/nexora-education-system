<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'class_schedule_pattern_id',
        'student_class_id',
        'class_category_fee_id',
        'class_hall_id',
        'class_date',
        'start_time',
        'end_time',
        'day_of_week',
        'status',
        'is_active',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
        'note',
    ];

    protected $casts = [
        'class_date' => 'date',
        'cancelled_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Branch this schedule belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Schedule pattern.
     */
    public function pattern(): BelongsTo
    {
        return $this->belongsTo(
            ClassSchedulePattern::class,
            'class_schedule_pattern_id'
        );
    }

    /**
     * Student class.
     */
    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class);
    }

    /**
     * Class category fee.
     */
    public function classCategoryFee(): BelongsTo
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
     * User who cancelled the schedule.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    /**
     * Student attendances for this schedule.
     */
    public function studentAttendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    /**
     * Scope: active schedules only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: exclude cancelled schedules.
     */
    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }

    /**
     * Scope: schedules on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('class_date', $date);
    }

    /**
     * Scope: schedules belonging to given branches.
     */
    public function scopeForBranches($query, $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Scope: schedules belonging to a specific branch.
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Cancel this schedule.
     */
    public function cancel($userId, $reason = null)
    {
        return $this->update([
            'status' => 'cancelled',
            'cancel_reason' => $reason,
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
        ]);
    }
}