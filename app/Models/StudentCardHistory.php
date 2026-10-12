<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCardHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'card_id',
        'old_card_id',
        'new_card_id',
        'action',
        'reason',
        'remarks',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    /**
     * Student related to this history.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Card related to the action.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(StudentCard::class, 'card_id');
    }

    /**
     * Previous card.
     */
    public function oldCard(): BelongsTo
    {
        return $this->belongsTo(StudentCard::class, 'old_card_id');
    }

    /**
     * New card.
     */
    public function newCard(): BelongsTo
    {
        return $this->belongsTo(StudentCard::class, 'new_card_id');
    }

    /**
     * User who performed the action.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}