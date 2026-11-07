<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One ID card download on Generate Student ID */
class StudentIdCardDownload extends Model
{
    protected $fillable = ['student_id', 'branch_id', 'user_id', 'admission_payment_id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admissionPayment(): BelongsTo
    {
        return $this->belongsTo(AdmissionPayment::class);
    }
}
