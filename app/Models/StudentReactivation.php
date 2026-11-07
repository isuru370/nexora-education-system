<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A disabled student enabled again (Disabled Students page) */
class StudentReactivation extends Model
{
    protected $fillable = ['student_id', 'branch_id', 'reason', 'classes_restored', 'enabled_by'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function enabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enabled_by');
    }
}
