<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student made inactive by disable:expired-students (see the migration) */
class StudentAutoDisable extends Model
{
    protected $fillable = [
        'student_id', 'branch_id', 'disabled_at', 'classes',
        'last_attendance_at', 'last_attendance_class',
        'last_payment_at', 'last_payment_amount', 'last_payment_month', 'last_payment_class',
        'reactivated_at', 'reactivated_by',
    ];

    protected $casts = [
        'disabled_at' => 'datetime',
        'classes' => 'array',
        'last_attendance_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'last_payment_amount' => 'decimal:2',
        'last_payment_month' => 'date',
        'reactivated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reactivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reactivated_by');
    }
}
