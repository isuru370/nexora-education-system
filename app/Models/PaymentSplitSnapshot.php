<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSplitSnapshot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'payment_id',

        // Fee snapshot
        'class_fee',
        'hall_fee',
        'total_fee',

        // Class / Enrollment
        'student_class_id',
        'student_class_enrollment_id',

        // Payment configuration
        'class_payment_config_id',

        // People involved
        'teacher_id',
        'organizer_id',
        'created_by',

        // Payment
        'payment_amount',

        // Percentage snapshot
        'teacher_percentage',
        'organizer_percentage',
        'institution_percentage',

        // Amount snapshot
        'teacher_amount',
        'organizer_amount',
        'institution_amount',

        // Payment date
        'payment_date',
    ];

    protected $casts = [
        'class_fee' => 'decimal:2',
        'hall_fee' => 'decimal:2',
        'total_fee' => 'decimal:2',

        'payment_amount' => 'decimal:2',

        'teacher_percentage' => 'decimal:2',
        'organizer_percentage' => 'decimal:2',
        'institution_percentage' => 'decimal:2',

        'teacher_amount' => 'decimal:2',
        'organizer_amount' => 'decimal:2',
        'institution_amount' => 'decimal:2',

        'payment_date' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class, 'student_class_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(
            StudentClassEnrollment::class,
            'student_class_enrollment_id'
        );
    }

    public function paymentConfig(): BelongsTo
    {
        return $this->belongsTo(
            ClassPaymentConfig::class,
            'class_payment_config_id'
        );
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class, 'organizer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForBranches($query, $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}