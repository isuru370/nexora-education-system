<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Institute share of a course handed to the owner. */
class CourseOwnerPayout extends Model
{
    protected $fillable = [
        'branch_id',
        'course_id',
        'amount',
        'paid_on',
        'payment_method',
        'status',
        'user_id',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
