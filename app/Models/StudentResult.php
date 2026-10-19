<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'student_id',
        'exam_id',
        'user_id',
        'marks',
        'max_marks',
        'percentage',
        'grade',
        'rank',
        'status',
        'reason',
        'remark',
        'is_updated',
        'is_absent',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'max_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
        'rank' => 'integer',
        'is_updated' => 'boolean',
        'is_absent' => 'boolean',
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

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getPercentageAttribute($value)
    {
        if (!is_null($value)) {
            return $value;
        }

        if ($this->is_absent) {
            return null;
        }

        if (is_null($this->marks) || is_null($this->max_marks)) {
            return null;
        }

        if ((float) $this->max_marks <= 0) {
            return null;
        }

        return round(
            ((float) $this->marks / (float) $this->max_marks) * 100,
            2
        );
    }

    public function getGradeAttribute($value)
    {
        if ($this->is_absent) {
            return 'ABS';
        }

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function calculateGrade(): string
    {
        if ($this->is_absent) {
            return 'ABS';
        }

        $percentage = $this->percentage;

        if (is_null($percentage)) {
            return 'N/A';
        }

        return match (true) {
            $percentage >= 75 => 'A',
            $percentage >= 65 => 'B',
            $percentage >= 50 => 'C',
            $percentage >= 35 => 'S',
            default => 'F',
        };
    }

    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isAbsent(): bool
    {
        return $this->is_absent || $this->status === 'absent';
    }

    public function hasMarks(): bool
    {
        return !$this->is_absent && !is_null($this->marks);
    }
}