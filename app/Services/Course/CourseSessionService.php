<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\CourseSession;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Class day status: scheduled -> ongoing (first attendance) -> completed
 * (after the end time, course-sessions:complete-finished). An admin can
 * cancel a day that has not started; parents are told.
 */
class CourseSessionService
{
    public function __construct(private CourseNotificationService $notifications)
    {
    }

    /** @return int parents told */
    public function cancel(CourseSession $session, ?string $reason, ?int $userId = null): int
    {
        if (!$session->canCancel()) {
            throw ValidationException::withMessages([
                'status' => $session->status === 'scheduled'
                    ? 'This class day has already started. Only class days that have not started can be cancelled.'
                    : 'Only a scheduled class day can be cancelled.',
            ]);
        }

        $session->update([
            'status' => 'cancelled',
            'cancel_reason' => $reason ?: null,
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
        ]);

        return $this->notifications->sessionCancelled($session);
    }

    /**
     * An extra class day (outside the weekly days). Parents are told.
     *
     * @return array{session: CourseSession, told: int}
     */
    public function add(Course $course, array $data): array
    {
        $fields = $this->checkDay($course, $data);

        $session = $course->sessions()->create($fields + [
            'branch_id' => $course->branch_id,
            'status' => 'scheduled',
            'is_custom' => true,
        ]);

        return ['session' => $session, 'told' => $this->notifications->sessionAdded($session)];
    }

    /**
     * Change one class day's date, time or hall (before it starts). It is then
     * kept as it is when the weekly schedule changes. Parents are told.
     *
     * @return int parents told
     */
    public function edit(CourseSession $session, array $data): int
    {
        if (!$session->canEdit()) {
            throw ValidationException::withMessages(['session_date' => 'Only a class day that has not started can be changed.']);
        }

        $fields = $this->checkDay($session->course, $data, $session->id);

        $session->update($fields + [
            'is_custom' => true,
            'original_date' => $session->original_date ?? ($session->is_custom ? null : $session->session_date),
        ]);

        return $this->notifications->sessionChanged($session->fresh());
    }

    /** Date not in the past and free for this course, end after start. */
    protected function checkDay(Course $course, array $data, ?int $ignoreId = null): array
    {
        $date = Carbon::parse($data['session_date'] ?? null)->toDateString();
        $start = substr((string) ($data['start_time'] ?? ''), 0, 5);
        $end = substr((string) ($data['end_time'] ?? ''), 0, 5);

        if ($end <= $start) {
            throw ValidationException::withMessages(['end_time' => 'The end time must be after the start time.']);
        }

        if (Carbon::parse("{$date} {$start}")->lte(now())) {
            throw ValidationException::withMessages(['session_date' => 'Choose a date and time that has not passed.']);
        }

        $taken = CourseSession::query()
            ->where('course_id', $course->id)
            ->whereDate('session_date', $date)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['session_date' => 'This course already has a class on ' . Carbon::parse($date)->format('d M Y') . '. One class per day.']);
        }

        return [
            'session_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'class_hall_id' => !empty($data['class_hall_id']) ? (int) $data['class_hall_id'] : null,
        ];
    }

    public function restore(CourseSession $session): void
    {
        if (!$session->canRestore()) {
            throw ValidationException::withMessages(['status' => 'This class day is over and cannot be restored.']);
        }

        $session->update([
            'status' => 'scheduled',
            'cancel_reason' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
        ]);
    }

    /** First attendance of the day. */
    public function start(CourseSession $session): void
    {
        if ($session->status === 'scheduled') {
            $session->update(['status' => 'ongoing']);
        }
    }

    /**
     * Ongoing days past their end time -> completed; scheduled days past their
     * end time with nobody marked -> cancelled.
     *
     * @return array{completed: int, cancelled: int}
     */
    public function closeFinished(): array
    {
        $now = now();
        $finished = fn ($query) => $query->where(fn ($q) => $q
            ->whereDate('session_date', '<', $now->toDateString())
            ->orWhere(fn ($q) => $q
                ->whereDate('session_date', $now->toDateString())
                ->where('end_time', '<=', $now->format('H:i:s'))));

        $completed = CourseSession::query()
            ->where('status', 'ongoing')
            ->where($finished)
            ->update(['status' => 'completed', 'updated_at' => $now]);

        $cancelled = CourseSession::query()
            ->where('status', 'scheduled')
            ->where($finished)
            ->update([
                'status' => 'cancelled',
                'cancel_reason' => 'No attendance was marked.',
                'cancelled_at' => $now,
                'updated_at' => $now,
            ]);

        return ['completed' => $completed, 'cancelled' => $cancelled];
    }
}
