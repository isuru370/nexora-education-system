<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseEnrollment extends Model
{
    protected $fillable = [
        'course_id',
        'student_id',
        'branch_id',
        'payment_plan',
        'total_fee',
        'standard_fee',
        'fee_reason',
        'enrolled_at',
        'status',
        'enrolled_by',
        'note',
    ];

    protected $casts = [
        'total_fee' => 'decimal:2',
        'standard_fee' => 'decimal:2',
        'enrolled_at' => 'date',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(CourseInstallment::class)->orderBy('installment_no');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CoursePayment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseAttendance::class);
    }

    /** Pays a fee other than the course fee for their plan. */
    public function hasSpecialFee(): bool
    {
        return $this->standard_fee !== null && round((float) $this->standard_fee, 2) !== round((float) $this->total_fee, 2);
    }

    /** Course fee - special fee (negative if they pay more). */
    public function discount(): float
    {
        return $this->standard_fee === null ? 0.0 : round((float) $this->standard_fee - (float) $this->total_fee, 2);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
