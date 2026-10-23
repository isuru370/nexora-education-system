<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoursePayment extends Model
{
    public const METHODS = ['cash', 'card', 'bank_transfer', 'online', 'cheque', 'other'];

    protected $fillable = [
        'branch_id',
        'course_id',
        'course_enrollment_id',
        'student_id',
        'amount',
        'payment_method',
        'mark_method',
        'receipt_number',
        'paid_at',
        'status',
        'user_id',
        'note',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function takenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function splits(): HasMany
    {
        return $this->hasMany(CoursePaymentSplit::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
