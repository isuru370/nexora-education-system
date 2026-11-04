<?php

namespace App\Services\Notification;

use App\Support\SmsTemplates;
use App\Jobs\SendSystemSms;
use App\Models\AdmissionPayment;
use App\Models\NotificationSetting;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One SMS to the parent when a student is registered (NexoraIT SMS account):
 * admission fee (paid amount or NOT PAID) + parent portal login.
 *
 * On / off: Settings > Notifications > Student registration (always SMS).
 */
class RegistrationSmsService
{
    public function send(
        Student $student,
        ?AdmissionPayment $payment,
        string $portalUsername,
        string $portalPassword
    ): void {
        if (!NotificationSetting::isEnabled(NotificationSetting::REGISTRATION)) {
            return;
        }

        $mobile = trim((string) $student->guardian_mobile);

        if ($mobile === '') {
            return;
        }

        $message = $this->message($student, $payment, $portalUsername, $portalPassword);

        // Queue only after the registration is saved.
        // An SMS problem must never fail the registration.
        DB::afterCommit(function () use ($mobile, $message, $student) {
            try {
                SendSystemSms::dispatch($mobile, $message);
            } catch (Throwable $e) {
                Log::warning('Registration SMS not sent', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function message(
        Student $student,
        ?AdmissionPayment $payment,
        string $portalUsername,
        string $portalPassword
    ): string {
        if ($payment && $payment->status === AdmissionPayment::STATUS_PAID) {
            $admissionFee = 'Rs. ' . number_format((float) $payment->amount, 2) . ' PAID'
                . ($payment->receipt_number ? " (Receipt {$payment->receipt_number})" : '');
        } else {
            $admissionFee = 'NOT PAID. Please pay at the office.';
        }

        return SmsTemplates::render(SmsTemplates::REGISTRATION, [
            '[Student Name]' => $student->initial_name ?: $student->full_name,
            '[Student ID]' => (string) $student->custom_id,
            '[Institute]' => (string) (config('institution.name') ?: config('app.name')),
            '[Admission Fee]' => $admissionFee,
            '[Username]' => $portalUsername,
            '[Password]' => $portalPassword,
            '[App Link]' => (string) (config('services.parent_hub.app_link') ?: ''),
        ]);
    }
}
