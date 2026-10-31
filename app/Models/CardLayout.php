<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardLayout extends Model
{
    protected $fillable = ['type', 'fields', 'background', 'updated_by'];

    protected $casts = ['fields' => 'array'];
}
