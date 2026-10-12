<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentReminderBulkSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 30;

    protected $numbers;

    protected $message;

    public function __construct(array $numbers, string $message)
    {
        $this->numbers = $numbers;
        $this->message = $message;
    }

    public function handle(SmsService $smsService)
    {
        $response = $smsService->sendBulkSms(
            $this->numbers,
            $this->message
        );

        Log::info('PAYMENT REMINDER BULK SMS RESULT', [
            'count' => count($this->numbers),
            'success' => $response['success'] ?? false,
            'provider_status_code' => $response['provider_status_code'] ?? null,
            'error' => $response['error'] ?? null,
        ]);

        if (!($response['success'] ?? false)) {
            throw new \RuntimeException(
                $response['error'] ?? 'Bulk SMS sending failed.'
            );
        }
    }
}