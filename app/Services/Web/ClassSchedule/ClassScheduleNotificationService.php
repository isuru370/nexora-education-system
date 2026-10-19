<?php

namespace App\Services\Web\ClassSchedule;

use App\Enums\NotificationType;
use App\Jobs\SendClassScheduleSms;
use App\Models\ClassHall;
use App\Models\NotificationSetting;
use App\Models\StudentClassEnrollment;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClassScheduleNotificationService
{
    public function __construct(
        private NotificationService $pushService
    ) {
    }
    /*
    |--------------------------------------------------------------------------
    | Send Class Schedule SMS
    |--------------------------------------------------------------------------
    */

    /**
     * Send schedule notifications to active enrolled students'
     * guardians.
     *
     * Supported actions:
     * created, updated, cancelled
     */
    public function sendClassScheduleSms(
        array $validated,
        string $action
    ): void {
        /*
         * Push (parent app) or SMS - chosen in Settings > Notifications.
         */
        $channel = NotificationSetting::channelFor(NotificationSetting::CLASS_SCHEDULE);

        if ($channel === null) {
            return;
        }

        /*
         * Get the selected hall, if provided.
         */
        $hall = !empty($validated['class_hall_id'])
            ? ClassHall::find($validated['class_hall_id'])
            : null;

        /*
         * Get active enrollments and active students.
         */
        $students = StudentClassEnrollment::with([
            'student:id,guardian_mobile,is_active',
            'studentClass:id,class_name',
            'classCategoryFee.category:id,category_name',
        ])
            ->where(
                'class_category_fee_id',
                $validated['class_category_fee_id']
            )
            ->where('is_active', true)
            ->whereHas('student', function ($query) {
                $query->where('is_active', true);
            })
            ->get();

        /*
         * Notification title.
         */
        $title = match ($action) {
            'created' => 'New class scheduled.',
            'updated' => 'Class schedule has been updated.',
            'cancelled' => 'Class has been cancelled.',
            default => 'Class schedule notification.',
        };

        /*
         * Send one SMS job per guardian mobile number.
         */
        foreach ($students as $enrollment) {
            $student = $enrollment->student;

            if (!$student) {
                continue;
            }

            if (
                $channel === NotificationSetting::SMS &&
                empty($student->guardian_mobile)
            ) {
                continue;
            }

            $studentClass = $enrollment->studentClass;

            $category = optional(
                optional(
                    $enrollment->classCategoryFee
                )->category
            );

            $message = "Dear Parent,\n\n"
                . "{$title}\n\n"
                . "Class : "
                . ($studentClass->class_name ?? 'N/A')
                . "\n"
                . "Category : "
                . ($category->category_name ?? 'N/A')
                . "\n";

            /*
             * Hall information.
             */
            if ($hall) {
                $message .= "Hall : {$hall->hall_name}\n";
            }

            /*
             * Schedule date.
             */
            if (!empty($validated['class_date'])) {
                $message .= "Date : "
                    . $validated['class_date']
                    . "\n";
            }

            /*
             * Schedule time.
             */
            if (
                !empty($validated['start_time']) &&
                !empty($validated['end_time'])
            ) {
                $message .= "Time : "
                    . $validated['start_time']
                    . " - "
                    . $validated['end_time']
                    . "\n";
            }

            /*
             * Cancellation reason.
             */
            if (
                $action === 'cancelled' &&
                !empty($validated['cancel_reason'])
            ) {
                $message .= "Reason : "
                    . $validated['cancel_reason']
                    . "\n";
            }

            $message .= "\nThank You.";

            if ($channel === NotificationSetting::PUSH) {
                $this->sendPush($student->id, $title, $message, $action, $validated);

                continue;
            }

            /*
             * Dispatch the existing SMS job.
             */
            SendClassScheduleSms::dispatch(
                $student->guardian_mobile,
                $message
            );
        }
    }

    /**
     * Parent app notification for one student.
     * A failure for one student must not stop the others.
     */
    private function sendPush(
        int $studentId,
        string $title,
        string $message,
        string $action,
        array $validated
    ): void {
        try {
            $this->pushService->send([
                'student_id' => $studentId,
                'title' => rtrim($title, '.'),
                'message' => trim(str_replace(["Dear Parent,\n\n", "\nThank You."], '', $message)),
                'type' => NotificationType::GENERAL,
                'data' => [
                    'kind' => 'class_schedule',
                    'action' => $action,
                    'class_date' => $validated['class_date'] ?? null,
                    'start_time' => $validated['start_time'] ?? null,
                    'end_time' => $validated['end_time'] ?? null,
                ],
                'scheduled_at' => null,
            ]);
        } catch (Throwable $e) {
            Log::info('Class schedule push skipped', [
                'student_id' => $studentId,
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
