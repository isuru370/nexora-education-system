<?php

namespace App\Models;

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
        'status',
        'cancel_reason',
    ];

    protected $casts = ['session_date' => 'date'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(ClassHall::class, 'class_hall_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseAttendance::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
