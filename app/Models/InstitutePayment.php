<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutePayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'amount',
        'payment_date',
        'reason',
        'reason_code',
        'payment_type',
        'status',
        'user_id',
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
        return $query->where(function ($q) use ($branchIds) {
            $q->whereIn('branch_id', $branchIds)
              ->orWhereNull('branch_id');
        });
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeInstituteWide($query)
    {
        return $query->whereNull('branch_id');
    }

    public function scopeBranchSpecific($query)
    {
        return $query->whereNotNull('branch_id');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isExpense(): bool
    {
        return $this->payment_type === 'expense';
    }

    public function isInstituteWide(): bool
    {
        return is_null($this->branch_id);
    }

    public function isBranchSpecific(): bool
    {
        return !is_null($this->branch_id);
    }
}