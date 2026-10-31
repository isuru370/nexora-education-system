<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Money paid out of a branch's welfare fund (cancelled = soft deleted) */
class WelfareExpense extends Model
{
    use SoftDeletes;

    protected $fillable = ['branch_id', 'expense_date', 'description', 'paid_to', 'amount', 'note', 'user_id', 'cancelled_by'];

    protected $casts = ['amount' => 'decimal:2', 'expense_date' => 'date'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
