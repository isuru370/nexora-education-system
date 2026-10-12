<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentClassEnrollment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'student_id',
        'student_class_id',
        'class_category_fee_id',
        'class_category_fee_option_id',
        'is_active',
        'is_free_card',
        'enrolled_at',
        'left_at',
        'note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_free_card' => 'boolean',
        'enrolled_at' => 'date',
        'left_at' => 'date',
    ];

    /**
     * Branch where this enrollment belongs.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Student class.
     */
    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class);
    }

    /**
     * Class category fee.
     */
    public function classCategoryFee(): BelongsTo
    {
        return $this->belongsTo(
            ClassCategoryFee::class,
            'class_category_fee_id'
        );
    }

    /**
     * Selected fee option.
     */
    public function classCategoryFeeOption(): BelongsTo
    {
        return $this->belongsTo(
            ClassCategoryFeeOption::class,
            'class_category_fee_option_id'
        );
    }

    /**
     * Payments made for this enrollment.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope: enrollments belonging to given branches.
     */
    public function scopeForBranches($query, $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Scope: enrollments belonging to a specific branch.
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Final fee for this enrollment.
     */
    public function getFinalFeeAttribute()
    {
        if ($this->is_free_card) {
            return 0;
        }

        return $this->getSelectedFee();
    }

    /**
     * Get the selected fee option amount.
     */
    public function getSelectedFee()
    {
        if (!$this->classCategoryFeeOption) {
            return 0;
        }

        return (float) $this->classCategoryFeeOption->fee;
    }

    /**
     * Total amount paid for this enrollment.
     */
    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Remaining balance.
     */
    public function getBalanceAttribute()
    {
        return max(
            $this->final_fee - $this->paid_amount,
            0
        );
    }

    /**
     * Payment status.
     */
    public function getPaymentStatusAttribute()
    {
        if ($this->paid_amount <= 0) {
            return 'unpaid';
        }

        return 'paid';
    }
}