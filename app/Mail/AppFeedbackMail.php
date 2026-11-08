<?php

namespace App\Mail;

use App\Models\AppFeedback;
use App\Support\InstituteInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/** Staff app feedback, sent to Nexora (services.nexora.feedback_email) */
class AppFeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AppFeedback $feedback)
    {
    }

    public function build()
    {
        $institute = InstituteInfo::name();
        $type = AppFeedback::TYPES[$this->feedback->type] ?? $this->feedback->type;

        $mail = $this->subject("[App feedback] {$type} - {$institute}")
            ->view('emails.app_feedback', [
                'feedback' => $this->feedback,
                'institute' => $institute,
                'typeLabel' => $type,
                'url' => route('app-feedback.index'),
            ]);

        if ($this->feedback->screenshot_path && Storage::disk('public')->exists($this->feedback->screenshot_path)) {
            $mail->attachFromStorageDisk('public', $this->feedback->screenshot_path, 'screenshot.' . pathinfo($this->feedback->screenshot_path, PATHINFO_EXTENSION));
        }

        return $mail;
    }
}
