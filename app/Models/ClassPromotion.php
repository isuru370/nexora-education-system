<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One "Promote Class" run (see App\Services\Web\ClassPromotion\PromoteClassService).
 */
class ClassPromotion extends Model
{
    public const SCHEDULED = 'scheduled';
    public const DONE = 'done';
    public const UNDONE = 'undone';

    protected $fillable = [
        'from_class_id',
        'to_class_id',
        'from_class_name',
        'to_class_name',
        'start_date',
        'schedule_until',
        'status',
        'counts',
        'changes',
        'promoted_by',
        'applied_at',
        'undone_by',
        'undone_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'schedule_until' => 'date',
        'counts' => 'array',
        'changes' => 'array',
        'applied_at' => 'datetime',
        'undone_at' => 'datetime',
    ];

    public function fromClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class, 'from_class_id')->withTrashed();
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(StudentClass::class, 'to_class_id')->withTrashed();
    }

    public function promotedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }

    public function undoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'undone_by');
    }

    public function total(string $key): int
    {
        return (int) ($this->counts[$key] ?? 0);
    }
}
