<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassPaymentConfig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'student_class_id',
        'teacher_id',
        'organizer_id',
        'teacher_percentage',
        'organizer_percentage',
        'institution_percentage',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'teacher_percentage' => 'decimal:2',
        'organizer_percentage' => 'decimal:2',
        'institution_percentage' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
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

    public function studentClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}