<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Login details (username / password / link) for a staff member or teacher.
 * The message holds a password, so it is never written to the log.
 */
class SendLoginDetailsSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = [10, 30, 60];

    public function __construct(
        public string $mobile,
        public string $message,
        public int $userId
    ) {
        $this->onQueue('sms');
    }

    public function handle(): void
    {
        // Login / password SMS: NexoraIT account
        $smsService = SmsService::system();

        $response = $smsService->sendSms($this->mobile, $this->message);

        if (!($response['success'] ?? false)) {
            Log::warning('Login details SMS failed', [
                'user_id' => $this->userId,
                'mobile' => $this->mobile,
                'attempt' => $this->attempts(),
                'error' => $response['error'] ?? null,
            ]);

            throw new \Exception($response['error'] ?? 'SMS sending failed');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Login details SMS permanently failed', [
            'user_id' => $this->userId,
            'mobile' => $this->mobile,
            'error' => $exception->getMessage(),
        ]);
    }
}
