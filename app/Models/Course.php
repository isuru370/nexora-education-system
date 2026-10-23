<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A course (feature "courses"): fixed fee, start / end dates, weekly days,
 * one or more teachers sharing the income by percentage.
 */
class Course extends Model
{
    use SoftDeletes;

    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'description',
        'full_fee',
        'one_time_fee',
        'installment_count',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'class_days',
        'class_hall_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'full_fee' => 'decimal:2',
        'one_time_fee' => 'decimal:2',
        'installment_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'class_days' => 'array',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(ClassHall::class, 'class_hall_id');
    }

    public function courseTeachers(): HasMany
    {
        return $this->hasMany(CourseTeacher::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'course_teachers')
            ->withPivot('percentage')
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseSession::class)->orderBy('session_date');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CoursePayment::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(CourseTeacherPayout::class);
    }

    /** Institute share = 100 - teachers' shares. */
    public function institutePercentage(): float
    {
        $teachers = $this->relationLoaded('courseTeachers')
            ? $this->courseTeachers->sum('percentage')
            : $this->courseTeachers()->sum('percentage');

        return round(100 - (float) $teachers, 2);
    }

    /** upcoming / ongoing / completed (by dates), or inactive */
    public function status(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        $today = now()->startOfDay();

        if ($today->lt($this->start_date)) {
            return 'upcoming';
        }

        return $today->gt($this->end_date) ? 'completed' : 'ongoing';
    }

    public function daysLabel(): string
    {
        return collect($this->class_days ?? [])
            ->map(fn ($day) => ucfirst(substr($day, 0, 3)))
            ->implode(', ');
    }
}
