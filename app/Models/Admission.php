<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'amount',
        'is_active',
        'for_card_replacement',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'for_card_replacement' => 'boolean',
    ];

    public function payments()
    {
        return $this->hasMany(AdmissionPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Items for new registrations (not the card replacement fees) */
    public function scopeForRegistration($query)
    {
        return $query->where(fn ($q) => $q->where('for_card_replacement', false)->orWhereNull('for_card_replacement'));
    }

    /** Items offered when a lost card is replaced / downloaded again */
    public function scopeForCardReplacement($query)
    {
        return $query->where('for_card_replacement', true);
    }
}