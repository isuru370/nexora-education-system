<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'grade_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Students belonging to this grade.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Classes belonging to this grade.
     */
    public function classes()
    {
        return $this->hasMany(StudentClass::class);
    }
}