<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Welfare taken from a teacher's salary for one branch + month (cancelled = soft deleted) */
class WelfareDeduction extends Model
{
    use SoftDeletes;

    protected $fillable = ['branch_id', 'teacher_id', 'salary_year', 'salary_month', 'amount', 'note', 'user_id', 'cancelled_by'];

    protected $casts = ['amount' => 'decimal:2', 'salary_year' => 'integer', 'salary_month' => 'integer'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class)->withTrashed();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
