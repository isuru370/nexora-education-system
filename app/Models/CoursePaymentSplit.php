<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Part of a course payment for a teacher (teacher_id null = institute). */
class CoursePaymentSplit extends Model
{
    protected $fillable = ['course_payment_id', 'course_id', 'teacher_id', 'percentage', 'amount'];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CoursePayment::class, 'course_payment_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
