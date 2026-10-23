<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSession extends Model
{
    protected $fillable = [
        'course_id',
        'branch_id',
        'session_date',
        'start_time',
        'end_time',
        'class_hall_id',
        'is_custom',
        'original_date',
        'status',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'original_date' => 'date',
        'is_custom' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public const STATUSES = ['scheduled', 'ongoing', 'completed', 'cancelled'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(ClassHall::class, 'class_hall_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseAttendance::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function startsAt(): Carbon
    {
        return Carbon::parse($this->session_date->toDateString() . ' ' . $this->start_time);
    }

    public function endsAt(): Carbon
    {
        return Carbon::parse($this->session_date->toDateString() . ' ' . $this->end_time);
    }

    /** Attendance can be marked from the class day on, until it is completed / cancelled. */
    public function canMarkAttendance(): bool
    {
        return in_array($this->status, ['scheduled', 'ongoing'], true)
            && $this->session_date->lte(today());
    }

    /** An admin can cancel a class day that has not started yet. */
    public function canCancel(): bool
    {
        return $this->status === 'scheduled' && now()->lt($this->startsAt());
    }

    /** Date, time and hall can be changed until the day starts. */
    public function canEdit(): bool
    {
        return $this->status === 'scheduled' && now()->lt($this->startsAt());
    }

    /** A cancelled day can be brought back until its end time. */
    public function canRestore(): bool
    {
        return $this->status === 'cancelled' && now()->lt($this->endsAt());
    }
}
