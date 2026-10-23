<?php

namespace App\Services\Course;

use App\Enums\NotificationType;
use App\Jobs\SendAttendanceSuccessSmsJob;
use App\Jobs\SendClassScheduleSms;
use App\Jobs\SendPaymentSms;
use App\Models\CourseAttendance;
use App\Models\CourseEnrollment;
use App\Models\CoursePayment;
use App\Models\CourseSession;
use App\Models\NotificationSetting;
use App\Models\Student;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Parent notices for courses - parent app push or SMS, each switched on /
 * off with its own channel in Settings > Notifications:
 *   course_schedule    class day cancelled / moved / extra day
 *   course_payment     payment received (amount, receipt, balance)
 *   course_attendance  marked present
 * A notice never stops the payment / attendance itself.
 */
class CourseNotificationService
{
    public function __construct(
        private NotificationService $push,
        private CourseFeeService $fees
    ) {
    }

    /** @return int number of students told */
    public function sessionCancelled(CourseSession $session): int
    {
        return $this->tellClass($session, 'cancelled', 'Course class cancelled');
    }

    /** A class day moved to another date / time / hall. */
    public function sessionChanged(CourseSession $session): int
    {
        return $this->tellClass($session, 'updated', 'Course class changed');
    }

    /** An extra class day was added. */
    public function sessionAdded(CourseSession $session): int
    {
        return $this->tellClass($session, 'created', 'Extra course class');
    }

    /** Payment received. @return bool told */
    public function paymentReceived(CoursePayment $payment): bool
    {
        $payment->loadMissing(['course', 'student', 'enrollment']);
        $fees = $this->fees->summary($payment->enrollment);

        $body = "Course : {$payment->course->name}\n"
            . 'Amount : Rs. ' . number_format((float) $payment->amount, 2) . "\n"
            . "Receipt : {$payment->receipt_number}\n"
            . 'Balance : Rs. ' . number_format($fees['balance'], 2)
            . ($fees['next_installment'] ? "\nNext due : " . $fees['next_installment']['due_date'] : '');

        return $this->tell(
            NotificationSetting::COURSE_PAYMENT,
            $payment->student,
            'Course payment received',
            $body,
            NotificationType::PAYMENT,
            ['kind' => 'course_payment', 'course_payment_id' => $payment->id, 'course_id' => $payment->course_id, 'receipt_number' => $payment->receipt_number],
            SendPaymentSms::class
        );
    }

    /** Marked present. @return bool told */
    public function attendanceMarked(CourseAttendance $attendance): bool
    {
        $attendance->loadMissing(['student', 'session.course']);
        $session = $attendance->session;

        $body = "{$attendance->student->initial_name} attended\n"
            . "Course : {$session->course->name}\n"
            . 'Date : ' . $session->session_date->format('Y-m-d') . "\n"
            . 'Time : ' . $attendance->attended_at->format('h:i A');

        return $this->tell(
            NotificationSetting::COURSE_ATTENDANCE,
            $attendance->student,
            'Course attendance marked',
            $body,
            NotificationType::ATTENDANCE,
            ['kind' => 'course_attendance', 'course_attendance_id' => $attendance->id, 'course_id' => $session->course_id, 'date' => $session->session_date->format('Y-m-d')],
            SendAttendanceSuccessSmsJob::class
        );
    }

    protected function tellClass(CourseSession $session, string $action, string $title): int
    {
        $session->loadMissing(['course', 'hall']);

        $date = $session->session_date->format('Y-m-d');
        $time = Carbon::parse($session->start_time)->format('h:i A') . ' - ' . Carbon::parse($session->end_time)->format('h:i A');
        $body = "Course : {$session->course->name}\nDate : {$date}\nTime : {$time}"
            . ($action !== 'cancelled' && $session->hall ? "\nHall : {$session->hall->hall_name}" : '')
            . ($action === 'cancelled' && $session->cancel_reason ? "\nReason : {$session->cancel_reason}" : '');

        $enrollments = CourseEnrollment::query()
            ->with('student')
            ->where('course_id', $session->course_id)
            ->where('status', 'active')
            ->get();

        $told = 0;

        foreach ($enrollments as $enrollment) {
            $sent = $this->tell(
                NotificationSetting::COURSE_SCHEDULE,
                $enrollment->student,
                $title,
                $body,
                NotificationType::GENERAL,
                [
                    'kind' => 'course_session',
                    'action' => $action,
                    'course_id' => $session->course_id,
                    'class_date' => $date,
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                ],
                SendClassScheduleSms::class
            );

            $told += $sent ? 1 : 0;
        }

        return $told;
    }

    /**
     * Push or SMS by the setting; false when switched off or nobody to tell.
     *
     * @param  class-string  $smsJob  job taking (mobile, message)
     */
    protected function tell(string $setting, ?Student $student, string $title, string $body, string $type, array $data, string $smsJob): bool
    {
        $channel = NotificationSetting::channelFor($setting);

        if ($channel === null || !$student || !$student->is_active) {
            return false;
        }

        try {
            if ($channel === NotificationSetting::PUSH) {
                $this->push->send([
                    'student_id' => $student->id,
                    'title' => $title,
                    'message' => $body,
                    'type' => $type,
                    'data' => $data,
                    'scheduled_at' => null,
                ]);

                return true;
            }

            if (trim((string) $student->guardian_mobile) === '') {
                return false;
            }

            $smsJob::dispatch(trim($student->guardian_mobile), "Dear Parent,\n\n{$title}.\n\n{$body}\n\nThank You.");

            return true;
        } catch (Throwable $e) {
            Log::info('Course notice skipped', ['setting' => $setting, 'student_id' => $student->id, 'reason' => $e->getMessage()]);

            return false;
        }
    }
}
