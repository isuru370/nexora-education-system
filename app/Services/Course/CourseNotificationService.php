<?php

namespace App\Services\Course;

use App\Enums\NotificationType;
use App\Jobs\SendClassScheduleSms;
use App\Models\CourseEnrollment;
use App\Models\CourseSession;
use App\Models\NotificationSetting;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells parents about a cancelled course class day - by parent app push or
 * SMS, the same channel as class schedules (Settings > Notifications).
 */
class CourseNotificationService
{
    public function __construct(private NotificationService $push)
    {
    }

    /** @return int number of students told */
    public function sessionCancelled(CourseSession $session): int
    {
        $channel = NotificationSetting::channelFor(NotificationSetting::CLASS_SCHEDULE);

        if ($channel === null) {
            return 0;
        }

        $session->loadMissing('course:id,name');

        $date = $session->session_date->format('Y-m-d');
        $time = Carbon::parse($session->start_time)->format('h:i A') . ' - ' . Carbon::parse($session->end_time)->format('h:i A');
        $title = 'Course class cancelled';
        $body = "Course : {$session->course->name}\nDate : {$date}\nTime : {$time}"
            . ($session->cancel_reason ? "\nReason : {$session->cancel_reason}" : '');

        $enrollments = CourseEnrollment::query()
            ->with('student:id,guardian_mobile,is_active')
            ->where('course_id', $session->course_id)
            ->where('status', 'active')
            ->get();

        $sent = 0;

        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;

            if (!$student || !$student->is_active) {
                continue;
            }

            try {
                if ($channel === NotificationSetting::PUSH) {
                    $this->push->send([
                        'student_id' => $student->id,
                        'title' => $title,
                        'message' => $body,
                        'type' => NotificationType::GENERAL,
                        'data' => [
                            'kind' => 'course_session',
                            'action' => 'cancelled',
                            'course_id' => $session->course_id,
                            'class_date' => $date,
                            'start_time' => $session->start_time,
                            'end_time' => $session->end_time,
                        ],
                        'scheduled_at' => null,
                    ]);
                } elseif (!empty($student->guardian_mobile)) {
                    SendClassScheduleSms::dispatch($student->guardian_mobile, "Dear Parent,\n\n{$title}.\n\n{$body}\n\nThank You.");
                } else {
                    continue;
                }

                $sent++;
            } catch (Throwable $e) {
                Log::info('Course cancel notice skipped', ['student_id' => $student->id, 'reason' => $e->getMessage()]);
            }
        }

        return $sent;
    }
}
