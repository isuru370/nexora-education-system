<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAttendance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'local_uuid',
        'student_id',
        'class_schedule_id',
        'student_class_enrollment_id',
        'attended_at',
        'mark_method',
        'marked_by',
        'is_synced',
        'note',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
        'is_synced' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(
            StudentClassEnrollment::class,
            'student_class_enrollment_id'
        );
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function scopeForBranches($query, $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}