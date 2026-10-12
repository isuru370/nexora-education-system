<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassCategoryFee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_class_id',
        'class_category_id',
        'is_active',
        'note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Student class this fee belongs to.
     */
    public function studentClass()
    {
        return $this->belongsTo(
            StudentClass::class,
            'student_class_id'
        );
    }

    /**
     * Category associated with this fee.
     */
    public function category()
    {
        return $this->belongsTo(
            ClassCategory::class,
            'class_category_id'
        );
    }

    /**
     * Class schedules using this fee.
     */
    public function schedules()
    {
        return $this->hasMany(
            ClassSchedule::class,
            'class_category_fee_id'
        );
    }

    /**
     * Fee options.
     */
    public function feeOptions()
    {
        return $this->hasMany(
            ClassCategoryFeeOption::class,
            'class_category_fee_id'
        );
    }

    /**
     * Active fee options.
     */
    public function activeFeeOptions()
    {
        return $this->hasMany(
            ClassCategoryFeeOption::class,
            'class_category_fee_id'
        )->where('is_active', true);
    }
}