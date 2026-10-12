<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizerBranch extends Model
{
    protected $table = 'organizer_branches';

    protected $fillable = [
        'organizer_id',
        'branch_id',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(
            Organizer::class,
            'organizer_id'
        );
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class,
            'branch_id'
        );
    }
}