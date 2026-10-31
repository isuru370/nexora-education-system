<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * One SMS through the NexoraIT account (SmsService::system()):
 * student registration with the parent portal login.
 */
class SendSystemSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = [10, 30, 60];

    public function __construct(protected string $mobile, protected string $message)
    {
        $this->onQueue('sms');
    }

    public function handle(): void
    {
        $response = SmsService::system()->sendSms($this->mobile, $this->message);

        if (!($response['success'] ?? false)) {
            Log::warning('System SMS sending failed', [
                'mobile' => $this->mobile,
                'attempt' => $this->attempts(),
                'error' => $response['error'] ?? null,
            ]);

            throw new RuntimeException($response['error'] ?? 'SMS sending failed');
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('System SMS permanently failed', [
            'mobile' => $this->mobile,
            'error' => $exception->getMessage(),
        ]);
    }
}
