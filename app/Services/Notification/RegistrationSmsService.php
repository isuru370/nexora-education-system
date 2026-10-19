<?php

namespace App\Services\Notification;

use App\Jobs\SendPaymentSms;
use App\Models\AdmissionPayment;
use App\Models\NotificationSetting;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * One SMS to the parent when a student is registered:
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
                SendPaymentSms::dispatch($mobile, $message);
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
        $institute = config('institution.name');

        $lines = [
            "Dear Parent, {$student->initial_name} has been registered at {$institute}.",
            '',
        ];

        if ($payment && $payment->status === AdmissionPayment::STATUS_PAID) {
            $lines[] = 'Admission fee: Rs. ' . number_format((float) $payment->amount, 2) . ' PAID'
                . ($payment->receipt_number ? " (Receipt {$payment->receipt_number})" : '');
        } else {
            $lines[] = 'Admission fee: NOT PAID. Please pay at the office.';
        }

        $lines[] = '';
        $lines[] = 'Parent portal login';
        $lines[] = "Username: {$portalUsername}";
        $lines[] = "Password: {$portalPassword}";

        if ($appLink = config('services.parent_hub.app_link')) {
            $lines[] = '';
            $lines[] = "App: {$appLink}";
        }

        $lines[] = 'Keep these details private.';

        return implode("\n", $lines);
    }
}
