<?php

namespace App\Services\Notification;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class AdminNotificationService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE SINGLE
    |--------------------------------------------------------------------------
    */

    /**
     * Create and queue a notification for one student.
     *
     * Actual notification logic is handled by NotificationService.
     */
    public function create(array $data): Notification
    {
        /*
         * Admin service accepts "body".
         *
         * NotificationService expects "message".
         */
        $payload = [
            'student_id' => $data['student_id'],

            'title' => $data['title'],

            'message' => $data['body'],

            'type' => $data['type'] ?? null,

            'data' => $data['data'] ?? null,

            'scheduled_at' =>
                $data['scheduled_at'] ?? null,
        ];

        $notification =
            $this->notificationService->send(
                $payload
            );

        Log::info(
            'Admin notification created.',
            [
                'notification_id' =>
                    $notification->id,

                'student_id' =>
                    $notification->student_id,

                'admin_id' =>
                    auth()->id(),

                'status' =>
                    $notification->status,
            ]
        );

        return $notification;
    }

    /*
    |--------------------------------------------------------------------------
    | BULK
    |--------------------------------------------------------------------------
    */

    /**
     * Create and queue notifications for multiple students.
     *
     * Returns number of successfully queued notifications.
     */
    public function sendBulk(array $data): int
    {
        if (
            !isset($data['student_ids'])
            || !is_array($data['student_ids'])
        ) {
            throw new InvalidArgumentException(
                'student_ids must be an array.'
            );
        }

        /*
         * Normalize IDs.
         */
        $studentIds = collect($data['student_ids'])
            ->filter(
                fn ($id) =>
                    is_numeric($id)
                    && (int) $id > 0
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values()
            ->toArray();

        if (empty($studentIds)) {
            throw new InvalidArgumentException(
                'At least one valid student ID is required.'
            );
        }

        /*
         * Convert body → message.
         */
        $payload = [
            'title' =>
                $data['title'],

            'message' =>
                $data['body'],

            'type' =>
                $data['type'] ?? null,

            'data' =>
                $data['data'] ?? null,

            'scheduled_at' =>
                $data['scheduled_at'] ?? null,
        ];

        $results =
            $this->notificationService->sendToMany(
                $studentIds,
                $payload
            );

        $successCount =
            count($results['success']);

        Log::info(
            'Admin bulk notifications processed.',
            [
                'total' =>
                    count($studentIds),

                'queued' =>
                    $successCount,

                'failed' =>
                    count($results['failed']),

                'admin_id' =>
                    auth()->id(),
            ]
        );

        return $successCount;
    }

    /*
    |--------------------------------------------------------------------------
    | RETRY
    |--------------------------------------------------------------------------
    */

    /**
     * Retry a failed notification.
     */
    public function retry(
        Notification $notification
    ): void {
        /*
         * Delegate retry rules to the main service.
         *
         * This includes:
         *
         * - FAILED status check
         * - retry limit
         * - active token check
         * - atomic status update
         * - afterCommit queue dispatch
         */
        $this->notificationService->retry(
            $notification
        );

        Log::info(
            'Admin notification retry requested.',
            [
                'notification_id' =>
                    $notification->id,

                'admin_id' =>
                    auth()->id(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STATISTICS
    |--------------------------------------------------------------------------
    */

    /**
     * Get notification dashboard statistics.
     */
    public function getStats(): array
    {
        return $this->notificationService->getStats()
            + [
                'unread' =>
                    Notification::query()
                        ->unread()
                        ->count(),

                'today' =>
                    Notification::query()
                        ->whereDate(
                            'created_at',
                            today()
                        )
                        ->count(),

                'this_week' =>
                    Notification::query()
                        ->whereBetween(
                            'created_at',
                            [
                                now()->startOfWeek(),
                                now()->endOfWeek(),
                            ]
                        )
                        ->count(),

                'this_month' =>
                    Notification::query()
                        ->whereBetween(
                            'created_at',
                            [
                                now()->startOfMonth(),
                                now()->endOfMonth(),
                            ]
                        )
                        ->count(),
            ];
    }

    /*
    |--------------------------------------------------------------------------
    | RECENT
    |--------------------------------------------------------------------------
    */

    /**
     * Get recent notifications.
     */
    public function getRecent(
        int $limit = 10
    ): Collection {
        /*
         * Prevent unreasonable queries.
         */
        $limit = max(
            1,
            min($limit, 100)
        );

        return Notification::query()
            ->with([
                'student',
                'creator',
            ])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | BY STUDENT
    |--------------------------------------------------------------------------
    */

    /**
     * Get notifications belonging to a student.
     */
    public function getByStudent(
        int $studentId,
        int $limit = 20
    ): Collection {
        if ($studentId <= 0) {
            throw new InvalidArgumentException(
                'Invalid student ID.'
            );
        }

        $limit = max(
            1,
            min($limit, 100)
        );

        return Notification::query()
            ->where(
                'student_id',
                $studentId
            )
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}