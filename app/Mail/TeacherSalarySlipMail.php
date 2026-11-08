<?php

namespace App\Mail;

use App\Support\InstituteInfo;
use App\Support\TeacherSalaryEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Salary paid: the salary slip and the Student Payments (by collection
 * date) report for the branch, sent to the teacher from the institute.
 */
class TeacherSalarySlipMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array  $slip  TeacherSalaryService::salarySlip()
     */
    public function __construct(
        public array $slip,
        public string $monthShort,
        public string $slipPdf,
        public string $reportPdf,
        public string $fileSuffix
    ) {
    }

    public function build()
    {
        $institute = InstituteInfo::name();

        return $this->from((string) config('mail.from.address'), $institute)
            ->subject("Salary slip - {$this->monthShort} - {$institute}")
            ->view('emails.teacher_salary_slip', [
                'slip' => $this->slip,
                'institute' => $institute,
                'monthShort' => $this->monthShort,
                'poweredBy' => TeacherSalaryEmail::POWERED_BY,
            ])
            ->attachData($this->slipPdf, "salary-slip-{$this->fileSuffix}.pdf", ['mime' => 'application/pdf'])
            ->attachData($this->reportPdf, "student-payments-{$this->fileSuffix}.pdf", ['mime' => 'application/pdf']);
    }
}
