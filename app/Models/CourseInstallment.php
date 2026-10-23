<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What is due and when. How much of it is paid is worked out from the
 * enrollment's completed payments (oldest installment first), so a
 * cancelled payment needs no clean-up here.
 */
class CourseInstallment extends Model
{
    protected $fillable = ['course_enrollment_id', 'installment_no', 'amount', 'due_date'];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class, 'course_enrollment_id');
    }
}
