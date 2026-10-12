<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAttendanceSuccessSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum attempts.
     */
    public int $tries = 3;

    /**
     * Maximum execution time per attempt.
     */
    public int $timeout = 30;

    /**
     * Retry delays in seconds.
     */
    public array $backoff = [10, 30, 60];

    protected string $guardianNumber;
    protected string $message;

    public function __construct(
        string $guardianNumber,
        string $message
    ) {
        $this->guardianNumber = trim($guardianNumber);
        $this->message = trim($message);

        $this->onQueue('sms');
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        try {

            $response = $smsService->sendSms(
                $this->guardianNumber,
                $this->message
            );

            if (($response['success'] ?? false) === true) {

                Log::info('Attendance SMS sent successfully', [
                    'attempt' => $this->attempts(),
                ]);

                return;
            }

            $errorMessage =
                $response['error']
                ?? $response['provider_message']
                ?? 'Attendance SMS sending failed';

            Log::warning('Attendance SMS sending failed', [
                'attempt' => $this->attempts(),
                'error' => $errorMessage,
            ]);

            /*
             * Throw exception so Laravel queue
             * can retry the job.
             */
            throw new \RuntimeException($errorMessage);

        } catch (\Throwable $e) {

            Log::error('Attendance SMS job error', [
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            /*
             * Re-throw so queue retry mechanism works.
             */
            throw $e;
        }
    }

    /**
     * Called after all attempts fail.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error(
            'Attendance SMS job permanently failed',
            [
                'attempts' => $this->tries,
                'error' => $exception->getMessage(),
            ]
        );
    }
}