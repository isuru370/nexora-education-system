<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'student_id',
        'student_class_enrollment_id',
        'issued_month',
        'is_issued',
        'issued_at',
        'issued_by',
        'note',
    ];

    protected $casts = [
        'issued_month' => 'date',
        'is_issued' => 'boolean',
        'issued_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(
            StudentClassEnrollment::class,
            'student_class_enrollment_id'
        );
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForBranches($query, $branchIds)
    {
        return $query->whereIn('branch_id', $branchIds);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeIssued($query)
    {
        return $query->where('is_issued', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_issued', false);
    }

    public function scopeForMonth($query, $month)
    {
        return $query->where('issued_month', $month);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isIssued(): bool
    {
        return $this->is_issued;
    }

    public function markAsIssued(?int $userId = null): bool
    {
        return $this->update([
            'is_issued' => true,
            'issued_at' => now(),
            'issued_by' => $userId ?? auth()->id(),
        ]);
    }
}