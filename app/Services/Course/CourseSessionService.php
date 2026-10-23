<?php

namespace App\Services\Course;

use App\Models\CourseSession;
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
