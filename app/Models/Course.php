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
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'full_fee' => 'decimal:2',
        'one_time_fee' => 'decimal:2',
        'installment_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** Weekly class days (each with its own time and hall), Monday first. */
    public function days(): HasMany
    {
        return $this->hasMany(CourseDay::class)
            ->orderByRaw("CASE day WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 WHEN 'saturday' THEN 6 ELSE 7 END");
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

    /** "Sun 08:00 AM - 12:00 PM (Hall 2) · Fri 12:00 PM - 05:00 PM (Hall 3)" */
    public function scheduleLabel(): string
    {
        $this->loadMissing('days.hall:id,hall_name');

        return $this->days->map(fn (CourseDay $day) => $day->label())->implode(' · ');
    }

    /** "Sun, Fri" */
    public function daysLabel(): string
    {
        $this->loadMissing('days');

        return $this->days->map(fn (CourseDay $day) => ucfirst(substr($day->day, 0, 3)))->implode(', ');
    }
}
