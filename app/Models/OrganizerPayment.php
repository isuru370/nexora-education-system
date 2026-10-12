<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizerPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'organizer_id',
        'user_id',
        'payment_type',
        'amount',
        'payment_date',
        'reason_code',
        'reason',
        'status',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paymentReason(): BelongsTo
    {
        return $this->belongsTo(
            PaymentReason::class,
            'reason_code',
            'reason_code'
        );
    }

    public function scopeForBranches($query, $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isSalary(): bool
    {
        return $this->payment_type === 'salary';
    }

    public function isAdvance(): bool
    {
        return $this->payment_type === 'advance';
    }

    public function isDeduction(): bool
    {
        return $this->payment_type === 'deduction';
    }

    public function isOther(): bool
    {
        return $this->payment_type === 'other';
    }
}