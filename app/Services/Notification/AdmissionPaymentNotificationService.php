<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Jobs\SendPaymentSms;
use App\Models\AdmissionPayment;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Parent notification when the admission fee is paid AFTER registration.
 * (At registration the admission status is already in the registration SMS.)
 *
 * On / off + Push or SMS: Settings > Notifications > Admission payment.
 */
class AdmissionPaymentNotificationService
{
    public function __construct(
        private NotificationService $pushService
    ) {
    }

    public function sendPaid(AdmissionPayment $payment): void
    {
        $channel = NotificationSetting::channelFor(NotificationSetting::ADMISSION_PAYMENT);

        if ($channel === null || $payment->status !== AdmissionPayment::STATUS_PAID) {
            return;
        }

        // Only after the payment is saved; never fail the payment
        DB::afterCommit(function () use ($payment, $channel) {
            try {
                $payment->loadMissing(['student', 'admission']);
                $student = $payment->student;

                if (!$student) {
                    return;
                }

                $amount = 'Rs. ' . number_format((float) $payment->amount, 2);
                $receipt = $payment->receipt_number ? " Receipt: {$payment->receipt_number}." : '';
                $name = $payment->admission->name ?? 'Admission';

                if ($channel === NotificationSetting::PUSH) {
                    $this->pushService->send([
                        'student_id' => $student->id,
                        'title' => 'Admission fee paid',
                        'message' => "{$name} {$amount} paid for {$student->initial_name}.{$receipt}",
                        'type' => NotificationType::PAYMENT,
                        'data' => [
                            'kind' => 'admission_payment',
                            'admission_payment_id' => $payment->id,
                            'amount' => (float) $payment->amount,
                            'receipt_number' => $payment->receipt_number,
                        ],
                        'scheduled_at' => null,
                    ]);

                    return;
                }

                $mobile = trim((string) $student->guardian_mobile);

                if ($mobile !== '') {
                    SendPaymentSms::dispatch(
                        $mobile,
                        "Dear Parent, {$name} fee {$amount} paid for {$student->initial_name} at "
                            . config('institution.name') . ".{$receipt} Thank you."
                    );
                }
            } catch (Throwable $e) {
                Log::warning('Admission payment notification not sent', [
                    'admission_payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
