<?php

namespace App\Services\Notification;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentClassEnrollment;
use App\Enums\NotificationType;
use App\Jobs\SendAttendanceSuccessSmsJob;
use Illuminate\Support\Facades\Log;

class AttendanceNotificationService
{
    public function __construct(
        private  NotificationService $notificationService
    ) {}

    /**
     * Send attendance success notifications.
     *
     * FCM is queued through the central NotificationService.
     * SMS can be enabled separately.
     */
    public function sendSuccess(
        Student $student,
        StudentAttendance $attendance,
        ?StudentClassEnrollment $enrollment = null
    ): void {
        try {
            $data = $this->prepareData(
                $student,
                $attendance,
                $enrollment
            );

            /*
             * ---------------------------------------------------------
             * FCM
             * ---------------------------------------------------------
             */

            if ($this->hasActiveTokens($student)) {
                $this->sendFcmNotification(
                    $student,
                    $attendance,
                    $data
                );
            } else {
                Log::info('Attendance FCM skipped - no active token', [
                    'student_id' => $student->id,
                    'attendance_id' => $attendance->id,
                ]);
            }

            /*
             * ---------------------------------------------------------
             * SMS
             * ---------------------------------------------------------
             *
             * Enable this when SMS integration is required.
             */

            // $this->sendSmsNotification(
            //     $student,
            //     $attendance,
            //     $enrollment,
            //     $data
            // );

        } catch (\Throwable $e) {

            /*
             * Do not hide unexpected notification errors.
             *
             * Attendance itself should NOT fail because notification
             * failed, so we only log the error here.
             */

            Log::error('Attendance notification failed', [
                'student_id' => $student->id ?? null,
                'attendance_id' => $attendance->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Queue FCM attendance notification.
     */
    private function sendFcmNotification(
        Student $student,
        StudentAttendance $attendance,
        array $data
    ): void {

        $message = $this->buildFcmMessage($data);

        $notificationData = [
            'attendance_id' => (string) $attendance->id,
            'student_id' => (string) $student->id,
            'student_name' => $data['student_name'],
            'class_name' => $data['class_name'],
            'grade' => $data['grade'],
            'category' => $data['category'],
            'date' => $data['date'],
            'time' => $data['time'],
            'mark_method' => $data['mark_method'],
        ];

        /*
         * Use the central NotificationService.
         *
         * This keeps:
         * - validation
         * - notification creation
         * - queue handling
         * - retry
         * - status handling
         *
         * in one place.
         */
        $notification = $this->notificationService->send([
            'student_id' => $student->id,
            'title' => 'Attendance Marked!',
            'message' => $message,
            'type' => NotificationType::ATTENDANCE,
            'data' => $notificationData,
            'scheduled_at' => null,
        ]);

        Log::info('Attendance FCM queued', [
            'notification_id' => $notification->id,
            'student_id' => $student->id,
            'attendance_id' => $attendance->id,
        ]);
    }

    /**
     * Send attendance SMS.
     */
    private function sendSmsNotification(
        Student $student,
        StudentAttendance $attendance,
        ?StudentClassEnrollment $enrollment,
        array $data
    ): void {

        $guardianMobile = trim((string) $student->guardian_mobile);

        if ($guardianMobile === '') {
            Log::info('Attendance SMS skipped - no guardian mobile', [
                'student_id' => $student->id,
                'attendance_id' => $attendance->id,
            ]);

            return;
        }

        $message = $this->buildSmsMessage($data);

        SendAttendanceSuccessSmsJob::dispatch(
            $guardianMobile,
            $message
        )->onQueue('sms');

        Log::info('Attendance SMS queued', [
            'student_id' => $student->id,
            'attendance_id' => $attendance->id,
        ]);
    }

    /**
     * Prepare attendance notification data.
     */
    private function prepareData(
        Student $student,
        StudentAttendance $attendance,
        ?StudentClassEnrollment $enrollment
    ): array {

        $attendedAt = $attendance->attended_at ?? now();

        return [
            'student_name' =>
            $student->initial_name
                ?: $student->full_name
                ?: 'Student',

            'class_name' =>
            $enrollment?->studentClass?->class_name
                ?? 'N/A',

            'grade' =>
            $enrollment?->studentClass?->grade?->grade_name
                ?? 'N/A',

            'category' =>
            $enrollment?->classCategoryFee?->category?->category_name
                ?? 'N/A',

            'date' => $attendedAt->format('Y-m-d'),

            'time' => $attendedAt->format('H:i'),

            'mark_method' =>
            $attendance->mark_method
                ?? 'manual',
        ];
    }

    /**
     * Build FCM notification body.
     */
    private function buildFcmMessage(array $data): string
    {
        return sprintf(
            "Dear Parent,\n\n" .
                "Attendance has been marked for %s.\n\n" .
                "Class: %s\n" .
                "Grade: %s\n" .
                "Category: %s\n" .
                "Date: %s\n" .
                "Time: %s\n\n" .
                "Thank you!",
            $data['student_name'],
            $data['class_name'],
            $data['grade'],
            $data['category'],
            $data['date'],
            $data['time']
        );
    }

    /**
     * Build SMS notification body.
     */
    private function buildSmsMessage(array $data): string
    {
        return sprintf(
            'Attendance marked. Student: %s, Class: %s, ' .
                'Category: %s, Grade: %s, Date: %s, Time: %s. Thank you.',
            $data['student_name'],
            $data['class_name'],
            $data['category'],
            $data['grade'],
            $data['date'],
            $data['time']
        );
    }

    /**
     * Check whether student has active FCM tokens.
     *
     * Uses the Student relationship instead of directly querying
     * FcmToken from this service.
     */
    private function hasActiveTokens(Student $student): bool
    {
        return $student->activeFcmTokens()->exists();
    }
}
