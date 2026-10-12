<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraIncome extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'amount',
        'income_date',
        'reason',
        'income_type',
        'status',
        'receipt_number',
        'user_id',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'income_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('income_type', $type);
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isHallRent(): bool
    {
        return $this->income_type === 'hall_rent';
    }

    public function isRefund(): bool
    {
        return $this->income_type === 'refund';
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