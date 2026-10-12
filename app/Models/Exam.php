<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'title',
        'student_class_id',
        'class_category_id',
        'class_hall_id',
        'exam_date',
        'start_time',
        'end_time',
        'status',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
        'note',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Branch where this exam belongs.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Student class for this exam.
     */
    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class);
    }

    /**
     * Class category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(
            ClassCategory::class,
            'class_category_id'
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
     * User who cancelled the exam.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    /**
     * Student results.
     */
    public function results(): HasMany
    {
        return $this->hasMany(StudentResult::class);
    }

    /**
     * Scheduled exams.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Ongoing exams.
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    /**
     * Completed exams.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Cancelled exams.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope: exams belonging to given branches.
     */
    public function scopeForBranches($query, $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Scope: exams belonging to a specific branch.
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Cancel this exam.
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

    /**
     * Check whether this exam has results.
     */
    public function hasResults(): bool
    {
        return $this->results()->exists();
    }

    /**
     * Number of result records for this exam.
     */
    public function totalStudents(): int
    {
        return $this->results()->count();
    }

    /**
     * Average marks.
     */
    public function averageMarks(): float
    {
        return (float) $this->results()->avg('marks');
    }

    /**
     * Highest marks.
     */
    public function highestMarks(): float
    {
        return (float) $this->results()->max('marks');
    }

    /**
     * Lowest marks.
     */
    public function lowestMarks(): float
    {
        return (float) $this->results()->min('marks');
    }
}