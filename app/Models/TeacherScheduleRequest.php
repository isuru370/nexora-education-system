<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherScheduleRequest extends Model
{
    protected $fillable = [
        'teacher_id',
        'user_id',
        'branch_id',
        'kind',
        'action',
        'student_class_id',
        'class_schedule_id',
        'course_id',
        'course_session_id',
        'payload',
        'status',
        'error',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'payload' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class);
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class)->withTrashed();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseSession(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Short line for lists: "Maths 10 CMB - change 12 Nov". */
    public function title(): string
    {
        $name = $this->kind === 'course'
            ? ($this->course->name ?? 'Course')
            : ($this->studentClass->class_name ?? 'Class');

        return $name . ' - ' . match ($this->action) {
            'create' => 'extra day',
            'update' => 'change',
            'cancel' => 'cancel',
            default => $this->action,
        };
    }
}
