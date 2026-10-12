<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentReminderSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 30;

    protected $mobile;

    protected $message;

    protected $studentId;

    protected $studentName;

    public function __construct(
        $mobile,
        $message,
        $studentId,
        $studentName
    ) {
        $this->mobile = $mobile;
        $this->message = $message;
        $this->studentId = $studentId;
        $this->studentName = $studentName;
    }

    public function handle(SmsService $smsService)
    {
        $result = $smsService->sendSms(
            $this->mobile,
            $this->message
        );

        Log::info('PAYMENT REMINDER SMS RESULT', [
            'student_id' => $this->studentId,
            'student_name' => $this->studentName,
            'success' => $result['success'] ?? false,
            'provider_status_code' => $result['provider_status_code'] ?? null,
            'message_id' => $result['message_id'] ?? null,
            'error' => $result['error'] ?? null,
        ]);

        if (!($result['success'] ?? false)) {
            throw new \RuntimeException(
                $result['error'] ?? 'SMS sending failed.'
            );
        }
    }
}