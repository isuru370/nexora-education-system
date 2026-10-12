<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentCard extends Model
{
    protected $fillable = [
        'branch_id',
        'student_id',
        'card_sequence',
        'global_qr_sequence',
        'card_number',
        'qr_code',
        'status',
        'is_current',
        'issued_at',
        'deactivated_at',
        'remarks',
    ];

    protected $casts = [
        'branch_id'          => 'integer',
        'student_id'         => 'integer',
        'card_sequence'      => 'integer',
        'global_qr_sequence' => 'integer',
        'is_current'         => 'boolean',
        'issued_at'           => 'datetime',
        'deactivated_at'      => 'datetime',
    ];

    /**
     * Get the branch that owns this card inventory.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the student assigned to this card.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the history records for this card.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(StudentCardHistory::class, 'card_id');
    }
}