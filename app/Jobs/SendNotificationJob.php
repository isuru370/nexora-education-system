<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\Firebase\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum number of attempts.
     */
    public int $tries = 3;

    /**
     * Retry delays in seconds.
     *
     * Attempt 1 fails → 10 seconds
     * Attempt 2 fails → 30 seconds
     * Attempt 3 fails → 60 seconds
     */
    public array $backoff = [10, 30, 60];

    /**
     * Maximum execution time.
     */
    public int $timeout = 120;

    /**
     * Maximum unhandled exceptions.
     */
    public int $maxExceptions = 3;

    /**
     * Notification ID.
     */
    protected int $notificationId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;

        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(
        FirebaseNotificationService $firebaseService
    ): void {
        Log::info('Notification job started.', [
            'notification_id' => $this->notificationId,
            'attempt' => $this->attempts(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find notification
        |--------------------------------------------------------------------------
        */

        $notification = Notification::find($this->notificationId);

        if (!$notification) {
            /*
             * Notification was deleted.
             *
             * There is nothing useful to retry.
             */
            Log::warning(
                'Notification no longer exists. Job skipped.',
                [
                    'notification_id' => $this->notificationId,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Refresh latest database state
        |--------------------------------------------------------------------------
        */

        $notification->refresh();

        Log::info('Notification loaded.', [
            'notification_id' => $notification->id,
            'student_id' => $notification->student_id,
            'status' => $notification->status,
            'attempt' => $this->attempts(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Stop already completed notifications
        |--------------------------------------------------------------------------
        */

        if ($notification->wasSent()) {
            Log::info(
                'Notification already sent. Job skipped.',
                [
                    'notification_id' => $notification->id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Stop cancelled notifications
        |--------------------------------------------------------------------------
        */

        if ($notification->isCancelled()) {
            Log::info(
                'Notification cancelled. Job skipped.',
                [
                    'notification_id' => $notification->id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Scheduled notification
        |--------------------------------------------------------------------------
        |
        | Normally NotificationService should already apply the queue delay.
        |
        | This is only a safety check in case the job reaches the worker
        | before scheduled_at.
        |--------------------------------------------------------------------------
        */

        if ($notification->isScheduled()) {
            $scheduledAt = $notification->scheduled_at;

            if ($scheduledAt && $scheduledAt->isFuture()) {
                $delay = now()->diffInSeconds(
                    $scheduledAt,
                    false
                );

                Log::info(
                    'Notification is scheduled for the future.',
                    [
                        'notification_id' => $notification->id,
                        'scheduled_at' => $scheduledAt,
                        'delay_seconds' => $delay,
                    ]
                );

                /*
                 * Re-dispatch once for the correct time.
                 */
                self::dispatch($notification->id)
                    ->onQueue('notifications')
                    ->delay($scheduledAt);

                return;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Check student
        |--------------------------------------------------------------------------
        */

        $student = $notification->student;

        if (!$student) {
            $notification->markAsFailed(
                'Student not found.'
            );

            Log::error(
                'Student not found for notification.',
                [
                    'notification_id' => $notification->id,
                    'student_id' => $notification->student_id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Check active FCM tokens
        |--------------------------------------------------------------------------
        |
        | We check here as a fast-fail optimization.
        |
        | FirebaseNotificationService performs its own token query as well,
        | because tokens can change between these two operations.
        |--------------------------------------------------------------------------
        */

        $tokensCount = $student
            ->activeFcmTokens()
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->count();

        Log::info('Active FCM tokens checked.', [
            'notification_id' => $notification->id,
            'student_id' => $student->id,
            'tokens_count' => $tokensCount,
        ]);

        if ($tokensCount === 0) {
            $notification->markAsFailed(
                'No active FCM tokens available.'
            );

            Log::warning(
                'No active FCM tokens available.',
                [
                    'notification_id' => $notification->id,
                    'student_id' => $student->id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate processing
        |--------------------------------------------------------------------------
        |
        | Only move PENDING → PROCESSING.
        |
        | If another worker already moved the notification to PROCESSING,
        | do not send it again.
        |--------------------------------------------------------------------------
        */

        $updated = Notification::query()
            ->whereKey($notification->id)
            ->whereIn('status', [
                NotificationStatus::PENDING,
            ])
            ->update([
                'status' => NotificationStatus::PROCESSING,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            /*
             * Another worker may already be processing this notification,
             * or the status may have changed since we loaded it.
             */

            $notification->refresh();

            if (
                $notification->wasSent()
                || $notification->isCancelled()
            ) {
                Log::info(
                    'Notification was processed by another worker.',
                    [
                        'notification_id' => $notification->id,
                        'status' => $notification->status,
                    ]
                );

                return;
            }

            if (
                $notification->status
                === NotificationStatus::PROCESSING
            ) {
                Log::warning(
                    'Notification is already being processed.',
                    [
                        'notification_id' => $notification->id,
                    ]
                );

                return;
            }

            /*
             * If status is unexpected, stop rather than risk duplicate send.
             */
            Log::warning(
                'Notification status changed unexpectedly. Job skipped.',
                [
                    'notification_id' => $notification->id,
                    'status' => $notification->status,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Reload notification after state transition
        |--------------------------------------------------------------------------
        */

        $notification->refresh();

        /*
        |--------------------------------------------------------------------------
        | Send through Firebase
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | FirebaseNotificationService throws exceptions for retryable
        | errors.
        |
        | Those exceptions MUST reach Laravel's queue worker.
        |--------------------------------------------------------------------------
        */

        try {
            $firebaseService->send($notification);
        } catch (Throwable $e) {
            Log::error(
                'Notification job encountered a send error.',
                [
                    'notification_id' => $notification->id,
                    'student_id' => $notification->student_id,
                    'attempt' => $this->attempts(),
                    'error' => $e->getMessage(),
                ]
            );

            /*
             * Do NOT mark FAILED here.
             *
             * Laravel needs the exception so that the queue can retry.
             */
            throw $e;
        }

        /*
        |--------------------------------------------------------------------------
        | Final state logging
        |--------------------------------------------------------------------------
        */

        $notification->refresh();

        Log::info(
            'Notification job completed.',
            [
                'notification_id' => $notification->id,
                'student_id' => $notification->student_id,
                'status' => $notification->status,
                'attempt' => $this->attempts(),
            ]
        );
    }

    /**
     * Handle a job that has failed permanently.
     */
    public function failed(Throwable $exception): void
    {
        try {
            $notification = Notification::find(
                $this->notificationId
            );

            if (!$notification) {
                Log::error(
                    'Failed job notification record not found.',
                    [
                        'notification_id' => $this->notificationId,
                        'error' => $exception->getMessage(),
                    ]
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Do not overwrite SENT state
            |--------------------------------------------------------------------------
            |
            | This is important for edge cases where Firebase successfully
            | sent the message but the worker failed afterwards.
            |--------------------------------------------------------------------------
            */

            if ($notification->wasSent()) {
                Log::warning(
                    'Job failed but notification was already marked as sent.',
                    [
                        'notification_id' => $notification->id,
                        'error' => $exception->getMessage(),
                    ]
                );

                return;
            }

            $notification->update([
                'status' => NotificationStatus::FAILED,
                'error_message' =>
                    'Job failed after '
                    . $this->attempts()
                    . ' attempts: '
                    . $exception->getMessage(),
                'updated_at' => now(),
            ]);

            Log::error(
                'Notification job failed permanently.',
                [
                    'notification_id' => $notification->id,
                    'student_id' => $notification->student_id,
                    'attempts' => $this->attempts(),
                    'error' => $exception->getMessage(),
                ]
            );
        } catch (Throwable $e) {
            /*
             * Never allow failure handling itself to cause another
             * unhandled exception.
             */

            Log::critical(
                'Failed to process notification job failure.',
                [
                    'notification_id' => $this->notificationId,
                    'original_error' => $exception->getMessage(),
                    'failure_handler_error' => $e->getMessage(),
                ]
            );
        }
    }
}