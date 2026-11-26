<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSyncItem extends Model
{
    public const NEEDS_REVIEW = ['conflict', 'failed'];

    protected $fillable = [
        'local_uuid',
        'device_id',
        'user_id',
        'branch_id',
        'type',
        'status',
        'message',
        'happened_at',
        'payload',
        'student_attendance_id',
        'payment_id',
        'student_id',
        'student_class_enrollment_id',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'happened_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class)->withTrashed();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentClassEnrollment::class, 'student_class_enrollment_id');
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(StudentAttendance::class, 'student_attendance_id');
    }
}
