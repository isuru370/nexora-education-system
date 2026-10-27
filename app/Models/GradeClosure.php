<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One "Close Batch" run (see App\Services\Web\GradeClosure\CloseGradeService).
 */
class GradeClosure extends Model
{
    protected $fillable = [
        'grade_names',
        'counts',
        'changes',
        'kept_students',
        'source',
        'closed_by',
        'closed_at',
        'undone_by',
        'undone_at',
    ];

    protected $casts = [
        'grade_names' => 'array',
        'counts' => 'array',
        'changes' => 'array',
        'kept_students' => 'array',
        'closed_at' => 'datetime',
        'undone_at' => 'datetime',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function undoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by');
    }

    public function isUndone(): bool
    {
        return $this->undone_at !== null;
    }

    public function total(string $key): int
    {
        return (int) ($this->counts[$key] ?? 0);
    }
}
