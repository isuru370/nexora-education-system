<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'branch_id',
        'table_name',
        'record_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForBranches($query, $branchIds)
    {
        return $query->where(function ($q) use ($branchIds) {
            $q->whereIn('branch_id', $branchIds)
              ->orWhereNull('branch_id');
        });
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeSystemWide($query)
    {
        return $query->whereNull('branch_id');
    }

    public function scopeForTable($query, string $table)
    {
        return $query->where('table_name', $table);
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForRecord($query, string $table, int $recordId)
    {
        return $query->where('table_name', $table)
            ->where('record_id', $recordId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where(
            'created_at',
            '>=',
            now()->subDays($days)
        );
    }
}