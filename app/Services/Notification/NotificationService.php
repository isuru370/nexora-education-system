<?php

namespace App\Services\Notification;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Jobs\SendNotificationJob;
use App\Models\FcmToken;
use App\Models\Notification;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class NotificationService
{
    /**
     * Maximum manual retry attempts.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default notification pagination.
     */
    private const DEFAULT_PER_PAGE = 20;

    /**
     * Maximum notification pagination.
     */
    private const MAX_PER_PAGE = 100;

    /**
     * Maximum student IDs processed in one bulk operation.
     */
    private const MAX_BULK_STUDENTS = 5000;

    /*
    |--------------------------------------------------------------------------
    | SEND
    |--------------------------------------------------------------------------
    */

    /**
     * Create and queue a notification for one student.
     */
    public function send(array $data): Notification
    {
        $this->validateNotificationData($data);

        $notification = DB::transaction(function () use ($data) {
            $student = Student::query()
                ->findOrFail($data['student_id']);

            /*
             * Only active students should receive notifications.
             */
            if (!$student->is_active) {
                throw new RuntimeException(
                    'Cannot send notification to an inactive student.'
                );
            }

            /*
             * Check whether at least one active token exists.
             */
            $hasTokens = $this->studentHasActiveTokens(
                $student->id
            );

            $notification = Notification::create([
                'student_id' => $student->id,

                'title' => trim($data['title']),

                'body' => trim($data['message']),

                'type' => $data['type']
                    ?? NotificationType::GENERAL,

                'status' => $hasTokens
                    ? NotificationStatus::PENDING
                    : NotificationStatus::FAILED,

                'data' => $data['data'] ?? null,

                'created_by' => auth()->id(),

                'scheduled_at' =>
                    $data['scheduled_at'] ?? null,

                'error_message' => $hasTokens
                    ? null
                    : 'No active FCM token found.',
            ]);

            /*
             * IMPORTANT:
             *
             * Queue only AFTER DB transaction commits.
             */
            if ($hasTokens) {
                $this->dispatchNotification(
                    $notification
                );
            }

            return $notification;
        });

        Log::info('Notification created.', [
            'notification_id' => $notification->id,
            'student_id' => $notification->student_id,
            'status' => $notification->status,
            'scheduled_at' => $notification->scheduled_at,
        ]);

        return $notification;
    }

    /**
     * Dispatch notification to the notifications queue.
     */
    protected function dispatchNotification(
        Notification $notification
    ): void {
        $job = SendNotificationJob::dispatch(
            $notification->id
        )->onQueue('notifications');

        /*
         * Laravel queue job is delayed only for scheduled
         * notifications.
         */
        if (
            $notification->scheduled_at
            && $notification->scheduled_at->isFuture()
        ) {
            $job->delay(
                $notification->scheduled_at
            );
        }

        /*
         * IMPORTANT:
         *
         * Prevent queue worker from receiving the job before
         * the database transaction has committed.
         */
        $job->afterCommit();
    }

    /*
    |--------------------------------------------------------------------------
    | SEND NOW
    |--------------------------------------------------------------------------
    */

    /**
     * Create notification and process it synchronously.
     *
     * Useful for admin "Send Now" actions and testing.
     */
    public function sendNow(array $data): Notification
    {
        /*
         * Scheduled notifications do not make sense with sendNow().
         */
        if (!empty($data['scheduled_at'])) {
            throw new InvalidArgumentException(
                'sendNow() cannot be used with scheduled notifications.'
            );
        }

        $this->validateNotificationData($data);

        /*
         * Create as PENDING.
         *
         * The Job itself is responsible for changing:
         *
         * PENDING → PROCESSING → SENT/FAILED
         */
        $notification = DB::transaction(function () use ($data) {
            $student = Student::query()
                ->findOrFail($data['student_id']);

            if (!$student->is_active) {
                throw new RuntimeException(
                    'Cannot send notification to an inactive student.'
                );
            }

            return Notification::create([
                'student_id' => $student->id,

                'title' => trim($data['title']),

                'body' => trim($data['message']),

                'type' => $data['type']
                    ?? NotificationType::GENERAL,

                'status' => NotificationStatus::PENDING,

                'data' => $data['data'] ?? null,

                'created_by' => auth()->id(),

                'scheduled_at' => null,

                'error_message' => null,
            ]);
        });

        /*
         * Execute the same production Job synchronously.
         *
         * This keeps the sending logic in one place.
         */
        dispatch_sync(
            new SendNotificationJob($notification->id)
        );

        return $notification->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | SEND TO MANY
    |--------------------------------------------------------------------------
    */

    /**
     * Send notification to multiple students.
     *
     * The returned "success" list means:
     *
     * notification successfully created and queued.
     *
     * It does NOT mean Firebase delivery has already completed.
     */
    public function sendToMany(
        array $studentIds,
        array $data
    ): array {
        $studentIds = $this->normalizeStudentIds(
            $studentIds
        );

        $this->validateBulkData(
            $studentIds,
            $data
        );

        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($studentIds),
            'queued' => 0,
        ];

        /*
         * Fetch only active students.
         */
        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->where('is_active', true)
            ->get();

        $foundStudentIds = $students
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        /*
         * Students that do not exist or are inactive.
         */
        $missingStudentIds = array_diff(
            $studentIds,
            $foundStudentIds
        );

        foreach ($missingStudentIds as $studentId) {
            $results['failed'][] = [
                'student_id' => $studentId,
                'student_name' => 'Unknown',
                'reason' => 'Student not found or inactive.',
            ];
        }

        /*
         * Process each student independently.
         *
         * A failure for one student should not rollback
         * notifications already created for other students.
         */
        foreach ($students as $student) {
            try {
                $notification = DB::transaction(
                    function () use ($student, $data) {
                        $hasTokens =
                            $this->studentHasActiveTokens(
                                $student->id
                            );

                        $notification =
                            Notification::create([
                                'student_id' => $student->id,

                                'title' =>
                                    trim($data['title']),

                                'body' =>
                                    trim($data['message']),

                                'type' =>
                                    $data['type']
                                    ?? NotificationType::GENERAL,

                                'status' => $hasTokens
                                    ? NotificationStatus::PENDING
                                    : NotificationStatus::FAILED,

                                'data' =>
                                    $data['data'] ?? null,

                                'created_by' =>
                                    auth()->id(),

                                'scheduled_at' =>
                                    $data['scheduled_at']
                                    ?? null,

                                'error_message' => $hasTokens
                                    ? null
                                    : 'No active FCM token found.',
                            ]);

                        if ($hasTokens) {
                            $this->dispatchNotification(
                                $notification
                            );
                        }

                        return [
                            'notification' =>
                                $notification,

                            'has_tokens' =>
                                $hasTokens,
                        ];
                    }
                );

                $notification =
                    $notification['notification'];

                $hasTokens =
                    $notification['has_tokens'];

                if ($hasTokens) {
                    $results['success'][] = [
                        'id' =>
                            $notification->id,

                        'student_id' =>
                            $student->id,

                        'student_name' =>
                            $this->studentName($student),

                        'status' =>
                            'queued',
                    ];

                    $results['queued']++;
                } else {
                    $results['failed'][] = [
                        'student_id' =>
                            $student->id,

                        'student_name' =>
                            $this->studentName($student),

                        'reason' =>
                            'No active FCM token.',
                    ];
                }
            } catch (\Throwable $e) {
                Log::error(
                    'Failed to create bulk notification.',
                    [
                        'student_id' =>
                            $student->id,

                        'error' =>
                            $e->getMessage(),
                    ]
                );

                $results['failed'][] = [
                    'student_id' =>
                        $student->id,

                    'student_name' =>
                        $this->studentName($student),

                    'reason' =>
                        'Unable to create notification.',
                ];
            }
        }

        Log::info('Bulk notifications processed.', [
            'total' =>
                $results['total'],

            'queued' =>
                $results['queued'],

            'failed' =>
                count($results['failed']),
        ]);

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | SEND TO ALL
    |--------------------------------------------------------------------------
    */

    /**
     * Send notification to all active students.
     */
    public function sendToAll(array $data): array
    {
        $studentIds = Student::query()
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (empty($studentIds)) {
            throw new RuntimeException(
                'No active students found.'
            );
        }

        return $this->sendToMany(
            $studentIds,
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SEND TO GRADE
    |--------------------------------------------------------------------------
    */

    /**
     * Send notification to students in a grade.
     *
     * IMPORTANT:
     * This expects the real students.grade_id column.
     */
    public function sendToGrade(
        int $gradeId,
        array $data
    ): array {
        $studentIds = Student::query()
            ->where('grade_id', $gradeId)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (empty($studentIds)) {
            throw new RuntimeException(
                "No active students found in grade: {$gradeId}"
            );
        }

        return $this->sendToMany(
            $studentIds,
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SEND TO CLASS
    |--------------------------------------------------------------------------
    */

    /**
     * Send notification to students enrolled in a class.
     *
     * This uses student_class_enrollments.
     */
    public function sendToClass(
        int $studentClassId,
        array $data
    ): array {
        $studentIds = DB::table(
            'student_class_enrollments'
        )
            ->join(
                'students',
                'students.id',
                '=',
                'student_class_enrollments.student_id'
            )
            ->where(
                'student_class_enrollments.student_class_id',
                $studentClassId
            )
            ->where(
                'student_class_enrollments.is_active',
                true
            )
            ->where(
                'students.is_active',
                true
            )
            ->distinct()
            ->pluck(
                'student_class_enrollments.student_id'
            )
            ->map(fn ($id) => (int) $id)
            ->toArray();

        if (empty($studentIds)) {
            throw new RuntimeException(
                "No active students found in class: {$studentClassId}"
            );
        }

        return $this->sendToMany(
            $studentIds,
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RETRY
    |--------------------------------------------------------------------------
    */

    /**
     * Retry a failed notification manually.
     */
    public function retry(
        Notification $notification
    ): void {
        $notification->refresh();

        if (
            $notification->status
            !== NotificationStatus::FAILED
        ) {
            throw new InvalidArgumentException(
                'Only failed notifications can be retried.'
            );
        }

        if (
            $notification->retry_count
            >= self::MAX_RETRIES
        ) {
            throw new RuntimeException(
                'Maximum retry attempts exceeded.'
            );
        }

        /*
         * Check whether student still has an active token.
         */
        if (
            !$this->studentHasActiveTokens(
                $notification->student_id
            )
        ) {
            throw new RuntimeException(
                'Student has no active FCM tokens.'
            );
        }

        DB::transaction(function () use ($notification) {
            /*
             * Atomic state update.
             *
             * Prevents two admins/workers from retrying the
             * same notification at the same time.
             */
            $updated = Notification::query()
                ->whereKey($notification->id)
                ->where(
                    'status',
                    NotificationStatus::FAILED
                )
                ->update([
                    'status' =>
                        NotificationStatus::PENDING,

                    'error_message' => null,

                    'retry_count' =>
                        $notification->retry_count + 1,

                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new RuntimeException(
                    'Notification state changed before retry.'
                );
            }

            /*
             * Queue only after transaction commit.
             */
            SendNotificationJob::dispatch(
                $notification->id
            )
                ->onQueue('notifications')
                ->afterCommit();
        });

        Log::info('Notification retry queued.', [
            'notification_id' =>
                $notification->id,

            'retry_count' =>
                $notification->retry_count + 1,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

    /**
     * Cancel a pending notification.
     */
    public function cancel(
        Notification $notification
    ): void {
        $notification->refresh();

        if (
            $notification->status
            !== NotificationStatus::PENDING
        ) {
            throw new InvalidArgumentException(
                'Only pending notifications can be cancelled.'
            );
        }

        $updated = Notification::query()
            ->whereKey($notification->id)
            ->where(
                'status',
                NotificationStatus::PENDING
            )
            ->update([
                'status' =>
                    NotificationStatus::CANCELLED,

                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new RuntimeException(
                'Notification could not be cancelled because its state changed.'
            );
        }

        Log::info('Notification cancelled.', [
            'notification_id' =>
                $notification->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    /**
     * Get detailed notification status.
     */
    public function getStatus(
        int $notificationId
    ): array {
        $notification = Notification::query()
            ->with([
                'student:id,name',
            ])
            ->findOrFail($notificationId);

        return [
            'id' =>
                $notification->id,

            'status' =>
                $notification->status,

            'status_label' =>
                NotificationStatus::label(
                    $notification->status
                ),

            'title' =>
                $notification->title,

            'body' =>
                $notification->body,

            'type' =>
                $notification->type,

            'type_label' =>
                NotificationType::label(
                    $notification->type
                ),

            'type_icon' =>
                NotificationType::icon(
                    $notification->type
                ),

            'sent_at' =>
                $notification->sent_at,

            'read_at' =>
                $notification->read_at,

            'scheduled_at' =>
                $notification->scheduled_at,

            'error_message' =>
                $notification->error_message,

            'retry_count' =>
                $notification->retry_count,

            'created_at' =>
                $notification->created_at,

            'student' => [
                'id' =>
                    $notification->student?->id,

                'name' =>
                    $notification->student
                    ? $this->studentName(
                        $notification->student
                    )
                    : 'Unknown',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    /**
     * Validate single notification data.
     */
    protected function validateNotificationData(
        array $data
    ): void {
        $validator = Validator::make(
            $data,
            [
                'student_id' =>
                    'required|integer|exists:students,id',

                'title' =>
                    'required|string|max:150',

                'message' =>
                    'required|string|max:1000',

                'type' =>
                    'nullable|string|in:'
                    . implode(
                        ',',
                        NotificationType::all()
                    ),

                'data' =>
                    'nullable|array',

                'scheduled_at' =>
                    'nullable|date|after:now',
            ],
            [
                'student_id.exists' =>
                    'Selected student does not exist.',

                'title.required' =>
                    'Notification title is required.',

                'message.required' =>
                    'Notification message is required.',

                'scheduled_at.after' =>
                    'Scheduled time must be in the future.',
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException(
                $validator
            );
        }
    }

    /**
     * Validate bulk notification data.
     */
    protected function validateBulkData(
        array $studentIds,
        array $data
    ): void {
        if (empty($studentIds)) {
            throw new InvalidArgumentException(
                'Student IDs cannot be empty.'
            );
        }

        if (
            count($studentIds)
            > self::MAX_BULK_STUDENTS
        ) {
            throw new InvalidArgumentException(
                'Maximum '
                . self::MAX_BULK_STUDENTS
                . ' students can be processed at once.'
            );
        }

        $validator = Validator::make(
            $data,
            [
                'title' =>
                    'required|string|max:150',

                'message' =>
                    'required|string|max:1000',

                'type' =>
                    'nullable|string|in:'
                    . implode(
                        ',',
                        NotificationType::all()
                    ),

                'data' =>
                    'nullable|array',

                'scheduled_at' =>
                    'nullable|date|after:now',
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException(
                $validator
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COUNTS
    |--------------------------------------------------------------------------
    */

    /**
     * Get pending notifications count.
     */
    public function getPendingCount(): int
    {
        return Notification::query()
            ->where(
                'status',
                NotificationStatus::PENDING
            )
            ->count();
    }

    /**
     * Get processing notifications count.
     */
    public function getProcessingCount(): int
    {
        return Notification::query()
            ->where(
                'status',
                NotificationStatus::PROCESSING
            )
            ->count();
    }

    /**
     * Get failed notifications count.
     */
    public function getFailedCount(): int
    {
        return Notification::query()
            ->where(
                'status',
                NotificationStatus::FAILED
            )
            ->count();
    }

    /**
     * Get sent notifications count.
     */
    public function getSentCount(): int
    {
        return Notification::query()
            ->where(
                'status',
                NotificationStatus::SENT
            )
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | STUDENT NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get notifications for one student.
     */
    public function getStudentNotifications(
        int $studentId,
        int $limit = self::DEFAULT_PER_PAGE
    ): Collection {
        $limit = max(
            1,
            min($limit, self::MAX_PER_PAGE)
        );

        return Notification::query()
            ->where('student_id', $studentId)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION LIST
    |--------------------------------------------------------------------------
    */

    /**
     * Get notifications with filters.
     */
    public function getNotifications(
        array $filters = [],
        int $perPage = self::DEFAULT_PER_PAGE
    ): LengthAwarePaginator {
        $perPage = max(
            1,
            min($perPage, self::MAX_PER_PAGE)
        );

        $query = Notification::query()
            ->with([
                'student:id,name',
            ]);

        if (!empty($filters['student_id'])) {
            $query->where(
                'student_id',
                $filters['student_id']
            );
        }

        if (
            isset($filters['status'])
            && $filters['status'] !== ''
        ) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        if (
            isset($filters['type'])
            && $filters['type'] !== ''
        ) {
            $query->where(
                'type',
                $filters['type']
            );
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['date_from']
            );
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['date_to']
            );
        }

        if (
            isset($filters['search'])
            && trim($filters['search']) !== ''
        ) {
            $search = trim(
                $filters['search']
            );

            $query->where(function ($q) use ($search) {
                $q->where(
                    'title',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'body',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        return $query
            ->latest('created_at')
            ->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE OLD
    |--------------------------------------------------------------------------
    */

    /**
     * Delete old completed notifications.
     */
    public function deleteOldNotifications(
        int $days = 30
    ): int {
        if ($days < 1) {
            throw new InvalidArgumentException(
                'Days must be greater than zero.'
            );
        }

        $deleted = Notification::query()
            ->where(
                'created_at',
                '<',
                now()->subDays($days)
            )
            ->whereIn(
                'status',
                [
                    NotificationStatus::SENT,
                    NotificationStatus::FAILED,
                    NotificationStatus::CANCELLED,
                ]
            )
            ->delete();

        Log::info('Old notifications deleted.', [
            'days' => $days,
            'deleted_count' => $deleted,
        ]);

        return $deleted;
    }

    /*
    |--------------------------------------------------------------------------
    | STATISTICS
    |--------------------------------------------------------------------------
    */

    /**
     * Get notification statistics.
     */
    public function getStats(): array
    {
        $total = Notification::query()->count();

        $pending =
            $this->getPendingCount();

        $processing =
            $this->getProcessingCount();

        $sent =
            $this->getSentCount();

        $failed =
            $this->getFailedCount();

        $cancelled =
            Notification::query()
                ->where(
                    'status',
                    NotificationStatus::CANCELLED
                )
                ->count();

        return [
            'total' =>
                $total,

            'pending' =>
                $pending,

            'processing' =>
                $processing,

            'sent' =>
                $sent,

            'failed' =>
                $failed,

            'cancelled' =>
                $cancelled,

            'success_rate' =>
                $total > 0
                    ? round(
                        ($sent / $total) * 100,
                        2
                    )
                    : 0,

            'by_type' =>
                $this->getStatsByType(),

            'by_date' =>
                $this->getStatsByDate(),
        ];
    }

    /**
     * Get statistics grouped by notification type.
     */
    protected function getStatsByType(): array
    {
        $stats = [];

        foreach (
            NotificationType::all()
            as $type
        ) {
            $stats[$type] =
                Notification::query()
                    ->where('type', $type)
                    ->count();
        }

        return $stats;
    }

    /**
     * Get statistics for the last 7 days.
     */
    protected function getStatsByDate(): array
    {
        $stats = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $stats[
                $date->format('Y-m-d')
            ] = Notification::query()
                ->whereDate(
                    'created_at',
                    $date
                )
                ->count();
        }

        return $stats;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Check whether a student currently has an active FCM token.
     */
    protected function studentHasActiveTokens(
        int $studentId
    ): bool {
        return FcmToken::query()
            ->where(
                'student_id',
                $studentId
            )
            ->where('is_active', true)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->exists();
    }

    /**
     * Normalize and deduplicate student IDs.
     */
    protected function normalizeStudentIds(
        array $studentIds
    ): array {
        return collect($studentIds)
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
    }

    /**
     * Safely resolve student display name.
     */
    protected function studentName(
        Student $student
    ): string {
        /*
         * Your project currently uses full_name in the
         * student model/schema.
         *
         * Fallbacks are kept for compatibility.
         */
        return $student->full_name
            ?? $student->name
            ?? $student->initial_name
            ?? 'Unknown';
    }
}