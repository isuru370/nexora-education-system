<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentClass extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'class_name',
        'class_type',
        'medium',
        'teacher_id',
        'subject_id',
        'grade_id',
        'is_active',
        'is_ongoing',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_ongoing' => 'boolean',
    ];

    /**
     * Branch this class belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Teacher assigned to this class.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Subject of this class.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Grade of this class.
     */
    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Class category fee associations.
     */
    public function categoryFees()
    {
        return $this->hasMany(
            ClassCategoryFee::class,
            'student_class_id'
        );
    }

    /**
     * Active class categories.
     *
     * Fee amounts are stored in
     * class_category_fee_options.
     */
    public function categories()
    {
        return $this->belongsToMany(
            ClassCategory::class,
            'class_category_fees',
            'student_class_id',
            'class_category_id'
        )
            ->withPivot([
                'id',
                'is_active',
                'deleted_at',
            ])
            ->withTimestamps()
            ->wherePivot('is_active', true)
            ->wherePivotNull('deleted_at');
    }

    /**
     * Master timetable patterns.
     */
    public function schedulePatterns()
    {
        return $this->hasMany(
            ClassSchedulePattern::class,
            'student_class_id'
        );
    }

    /**
     * Generated class schedules.
     */
    public function schedules()
    {
        return $this->hasMany(
            ClassSchedule::class,
            'student_class_id'
        );
    }

    /**
     * Student enrollments.
     */
    public function enrollments()
    {
        return $this->hasMany(
            StudentClassEnrollment::class,
            'student_class_id'
        );
    }

    /**
     * Payment configuration.
     */
    public function paymentConfig()
    {
        return $this->hasOne(
            ClassPaymentConfig::class,
            'student_class_id'
        );
    }
}