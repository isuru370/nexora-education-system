<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'student_id',
        'student_class_enrollment_id',
        'user_id',
        'mark_method',
        'amount',
        'discount_amount',
        'paid_at',
        'payment_month',
        'payment_method',
        'status',
        'receipt_number',
        'reference_number',
        'is_synced',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_month' => 'date',
        'is_synced' => 'boolean',
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(
            StudentClassEnrollment::class,
            'student_class_enrollment_id'
        );
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function splitSnapshot(): HasOne
    {
        return $this->hasOne(PaymentSplitSnapshot::class);
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

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getExpectedAmountAttribute()
    {
        return $this->enrollment?->final_fee ?? 0;
    }

    public function getTotalExpectedAmountAttribute()
    {
        if ($this->splitSnapshot) {
            return (float) $this->splitSnapshot->total_fee;
        }

        return (float) $this->expected_amount;
    }

    public function getBalanceAttribute()
    {
        return max(
            $this->total_expected_amount - (float) $this->amount,
            0
        );
    }

    public function getIsFullyPaidAttribute()
    {
        return (float) $this->amount >= (float) $this->total_expected_amount;
    }
}