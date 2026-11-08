<?php

namespace App\Jobs;

use App\Mail\AppFeedbackMail;
use App\Models\AppFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Email one app feedback to Nexora. A mail error never loses the feedback. */
class SendAppFeedbackEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300];

    public function __construct(public int $feedbackId)
    {
    }

    public function handle(): void
    {
        $to = trim((string) config('services.nexora.feedback_email'));
        $feedback = AppFeedback::query()->find($this->feedbackId);

        if ($to === '' || !$feedback || $feedback->emailed_at) {
            return;
        }

        Mail::to($to)->send(new AppFeedbackMail($feedback));

        $feedback->forceFill(['emailed_at' => now()])->save();
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('App feedback email failed', ['feedback_id' => $this->feedbackId, 'error' => $e->getMessage()]);
    }
}
