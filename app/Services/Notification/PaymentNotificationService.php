<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Jobs\SendPaymentSms;
use App\Models\NotificationSetting;
use App\Models\Payment;
use App\Services\Web\Payment\PaymentMarker;
use App\Support\SmsTemplates;
use Illuminate\Support\Facades\Log;

class PaymentNotificationService
{
    public function __construct(
        private  NotificationService $notificationService
    ) {
    }

    /**
     * Send payment success notifications.
     *
     * FCM is handled through the central NotificationService.
     * SMS is handled through SendPaymentSms job.
     */
    public function sendSuccess(Payment $payment): void
    {
        try {
            $student = $payment->student;

            if (!$this->validateStudent($student, $payment)) {
                return;
            }

            /*
             * ---------------------------------------------------------
             * Push (parent app) or SMS - chosen in Settings > Notifications
             * ---------------------------------------------------------
             */

            $channel = NotificationSetting::channelFor(NotificationSetting::PAYMENT);

            if ($channel === NotificationSetting::PUSH) {
                $this->sendFcmNotification($payment);
            } elseif ($channel === NotificationSetting::SMS) {
                $this->sendSmsNotification($payment);
            }

            Log::info('Payment notification processing completed.', [
                'payment_id' => $payment->id,
                'channel' => $channel ?? 'off',
                'student_id' => $student->id,
            ]);

        } catch (\Throwable $e) {

            /*
             * Payment itself should NOT fail because notification
             * failed.
             */
            Log::error('Payment notification failed.', [
                'payment_id' => $payment->id ?? null,
                'student_id' => $payment->student?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Queue FCM payment notification.
     */
    private function sendFcmNotification(Payment $payment): void
    {
        $student = $payment->student;

        if (!$student) {
            return;
        }

        /*
         * No need to manually create Notification or dispatch
         * SendNotificationJob here.
         *
         * Central NotificationService handles:
         *
         * - active student validation
         * - FCM token validation
         * - notification creation
         * - queue dispatch
         * - retry
         * - status handling
         */
        $notification = $this->notificationService->send([
            'student_id' => $student->id,

            'title' => $this->getTitle(),

            'message' => $this->formatMessage($payment),

            'type' => NotificationType::PAYMENT,

            'data' => $this->buildPayload($payment),

            'scheduled_at' => null,
        ]);

        Log::info('Payment FCM queued.', [
            'payment_id' => $payment->id,
            'notification_id' => $notification->id,
            'student_id' => $student->id,
        ]);
    }

    /**
     * Queue payment SMS.
     */
    private function sendSmsNotification(Payment $payment): void
    {
        $mobile = trim(
            (string) $payment->student?->guardian_mobile
        );

        if ($mobile === '') {
            Log::info('Payment SMS skipped - no guardian mobile.', [
                'payment_id' => $payment->id,
                'student_id' => $payment->student?->id,
            ]);

            return;
        }

        $message = $this->formatSmsMessage($payment);

        SendPaymentSms::dispatch(
            $mobile,
            $message
        )->onQueue('sms');

        Log::info('Payment SMS queued.', [
            'payment_id' => $payment->id,
            'student_id' => $payment->student?->id,
        ]);
    }

    /**
     * Validate student.
     */
    private function validateStudent(
        $student,
        Payment $payment
    ): bool {
        if (!$student) {
            Log::warning('Payment notification skipped - student not found.', [
                'payment_id' => $payment->id,
            ]);

            return false;
        }

        if (!$student->is_active) {
            Log::warning(
                'Payment notification skipped - student inactive.',
                [
                    'payment_id' => $payment->id,
                    'student_id' => $student->id,
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * Notification title.
     */
    private function getTitle(): string
    {
        return 'Payment Confirmed!';
    }

    /**
     * Build FCM message.
     */
    private function formatMessage(Payment $payment): string
    {
        $data = $this->getPaymentData($payment);

        return sprintf(
            "Dear %s,\n\n" .
            "Payment of Rs. %s for %s has been received for %s.\n\n" .
            "Class: %s\n" .
            "Grade: %s\n" .
            "Category: %s\n" .
            "Receipt: %s\n\n" .
            "Thank you!",
            $data['parent_name'],
            $data['amount'],
            $data['month'],
            $data['student_name'],
            $data['class_name'],
            $data['grade'],
            $data['category'],
            $data['receipt']
        );
    }

    /**
     * Build SMS message.
     */
    private function formatSmsMessage(Payment $payment): string
    {
        $data = $this->getPaymentData($payment);
        $hallFee = $this->hallFee($payment);
        $total = (float) ($payment->splitSnapshot?->total_fee ?? $payment->amount);

        return SmsTemplates::render(SmsTemplates::PAYMENT, [
            '[Student Name]' => $data['student_name'],
            '[Class]' => $data['class_name'],
            '[Grade]' => $data['grade'],
            '[Category]' => $data['category'],
            '[Month]' => $data['month'],
            '[Amount]' => $data['amount'],
            '[Class Fee]' => number_format(max(0, $total - $hallFee), 2),
            // empty = the hall fee line is left out (hall price 0)
            '[Hall Fee]' => $hallFee > 0 ? number_format($hallFee, 2) : '',
            '[Receipt]' => $data['receipt'],
            '[Date]' => ($payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at) : now())->format('Y-m-d'),
            '[Institute]' => (string) (config('institution.name') ?: config('app.name')),
        ]);
    }

    /**
     * Hall fee in this payment: from the payment split, or the class hall price.
     */
    private function hallFee(Payment $payment): float
    {
        try {
            if ($payment->splitSnapshot) {
                return round((float) $payment->splitSnapshot->hall_fee, 2);
            }

            return $payment->enrollment
                ? app(PaymentMarker::class)->hallFee($payment->enrollment)
                : 0.0;
        } catch (\Throwable $e) {
            Log::warning('Payment SMS: hall fee not found.', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    /**
     * Build FCM payload.
     */
    private function buildPayload(Payment $payment): array
    {
        $data = $this->getPaymentData($payment);

        return [
            'payment_id' => (string) $payment->id,
            'receipt_number' => $data['receipt'],
            'amount' => (string) $data['amount_raw'],
            'payment_month' => $data['month_raw'],
            'student_name' => $data['student_name'],
            'class_name' => $data['class_name'],
            'grade' => $data['grade'],
            'category' => $data['category'],
        ];
    }

    /**
     * Get payment data.
     */
    private function getPaymentData(Payment $payment): array
    {
        $paymentMonth = $payment->payment_month
            ? \Carbon\Carbon::parse($payment->payment_month)
            : now();

        return [
            'student_name' =>
                $payment->student?->initial_name
                ?: $payment->student?->full_name
                ?: 'Student',

            'parent_name' =>
                $payment->student?->guardian_name
                ?: 'Parent',

            'amount' =>
                number_format(
                    (float) $payment->amount,
                    2
                ),

            'amount_raw' =>
                $payment->amount,

            'month' =>
                $paymentMonth->format('F Y'),

            'month_raw' =>
                $paymentMonth->format('Y-m'),

            'class_name' =>
                $payment->enrollment
                    ?->studentClass
                    ?->class_name
                ?? 'N/A',

            'grade' =>
                $payment->enrollment
                    ?->studentClass
                    ?->grade
                    ?->grade_name
                ?? 'N/A',

            'category' =>
                $payment->enrollment
                    ?->classCategoryFee
                    ?->category
                    ?->category_name
                ?? 'N/A',

            'receipt' =>
                $payment->receipt_number
                ?? 'N/A',
        ];
    }
}