<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSalary extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'teacher_id',
        'user_id',
        'salary_year',
        'salary_month',
        'gross_amount',
        'advance_deduction',
        'other_deduction',
        'welfare_deduction',
        'bonus_amount',
        'net_amount',
        'status',
        'paid_at',
        'note',
    ];

    protected $casts = [
        'salary_year' => 'integer',
        'salary_month' => 'integer',
        'gross_amount' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'other_deduction' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->where('salary_year', $year)
            ->where('salary_month', $month);
    }

    public function getFinalNetAmountAttribute(): float
    {
        return max(
            (float) $this->gross_amount
            + (float) $this->bonus_amount
            - (float) $this->advance_deduction
            - (float) $this->other_deduction,
            0
        );
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}