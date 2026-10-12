<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'branch_id',
        'student_id',
        'title',
        'body',
        'type',
        'data',
        'status',
        'sent_at',
        'read_at',
        'scheduled_at',
        'error_message',
        'retry_count',
        'created_by',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    protected $attributes = [
        'retry_count' => 0,
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::SENT);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::PENDING);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::PROCESSING);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::FAILED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::CANCELLED);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now());
    }

    public function scopeForBranches(
        Builder $query,
        $branchIds
    ): Builder {
        return $query->whereIn('branch_id', $branchIds);
    }

    public function scopeForBranch(
        Builder $query,
        int $branchId
    ): Builder {
        return $query->where('branch_id', $branchId);
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    public function markAsRead(): self
    {
        if (is_null($this->read_at)) {
            $this->update([
                'read_at' => now(),
            ]);
        }

        return $this;
    }

    public function markAsSent(): self
    {
        $this->update([
            'status' => NotificationStatus::SENT,
            'sent_at' => now(),
            'error_message' => null,
        ]);

        return $this;
    }

    public function markAsFailed(string $message): self
    {
        $this->update([
            'status' => NotificationStatus::FAILED,
            'error_message' => $message,
        ]);

        return $this;
    }

    public function markAsProcessing(): self
    {
        $this->update([
            'status' => NotificationStatus::PROCESSING,
        ]);

        return $this;
    }

    public function markAsPending(): self
    {
        $this->update([
            'status' => NotificationStatus::PENDING,
            'error_message' => null,
        ]);

        return $this;
    }

    public function markAsCancelled(): self
    {
        $this->update([
            'status' => NotificationStatus::CANCELLED,
        ]);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function wasSent(): bool
    {
        return $this->status === NotificationStatus::SENT;
    }

    public function wasFailed(): bool
    {
        return $this->status === NotificationStatus::FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === NotificationStatus::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === NotificationStatus::PROCESSING;
    }

    public function isCancelled(): bool
    {
        return $this->status === NotificationStatus::CANCELLED;
    }

    public function isScheduled(): bool
    {
        return !is_null($this->scheduled_at)
            && $this->scheduled_at->isFuture();
    }

    public function getStatusLabelAttribute(): string
    {
        return NotificationStatus::label($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return NotificationType::label($this->type);
    }

    public function getTypeIconAttribute(): string
    {
        return NotificationType::icon($this->type);
    }

    public function canRetry(): bool
    {
        return $this->wasFailed() && $this->retry_count < 3;
    }
}