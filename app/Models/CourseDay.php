<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A weekly class day of a course with its own time and hall. */
class CourseDay extends Model
{
    protected $fillable = ['course_id', 'day', 'start_time', 'end_time', 'class_hall_id'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(ClassHall::class, 'class_hall_id');
    }

    /** "Sun 08:00 AM - 12:00 PM (Hall 2)" */
    public function label(): string
    {
        return ucfirst(substr($this->day, 0, 3)) . ' '
            . Carbon::parse($this->start_time)->format('h:i A') . ' - ' . Carbon::parse($this->end_time)->format('h:i A')
            . ($this->hall ? ' (' . $this->hall->hall_name . ')' : '');
    }
}
