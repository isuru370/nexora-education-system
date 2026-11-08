<?php

namespace App\Jobs;

use App\Mail\TeacherSalarySlipMail;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use App\Models\User;
use App\Services\Web\MonthlyReport\MonthlyReportService;
use App\Services\Web\TeacherSalary\TeacherSalaryService;
use App\Support\InstituteInfo;
use App\Support\TeacherSalaryEmail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email a paid salary to the teacher: salary slip + Student Payments
 * (by collection date, that branch only), both as PDF.
 *
 * Runs as the user who paid / pressed "Email slip", so branch access is
 * the same as on the page. A mail error never touches the salary.
 */
class SendTeacherSalarySlip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300];

    public function __construct(public int $salaryId, public int $userId)
    {
    }

    public function handle(TeacherSalaryService $salaryService, MonthlyReportService $reportService): void
    {
        $salary = TeacherSalary::query()->find($this->salaryId);
        $user = User::query()->find($this->userId);

        if (!$salary || $salary->status !== 'paid' || !$user) {
            return;
        }

        $teacher = Teacher::withTrashed()->find($salary->teacher_id);
        $to = TeacherSalaryEmail::addressOf(optional($teacher)->email);

        if (!$to) {
            return;
        }

        Auth::setUser($user);

        $teacherId = (int) $salary->teacher_id;
        $branchId = (int) $salary->branch_id;
        $year = (int) $salary->salary_year;
        $month = (int) $salary->salary_month;

        $institute = InstituteInfo::name();
        $poweredBy = TeacherSalaryEmail::POWERED_BY;

        $slip = $salaryService->salarySlip($teacherId, $branchId, $year, $month);

        $slipPdf = Pdf::loadView('pdf.teacher.salary_slip', [
            'slip' => $slip,
            'institute' => $institute,
            'poweredBy' => $poweredBy,
        ])->setPaper('a4')->output();

        $report = $reportService->teacherStudentPaymentReport(
            $teacherId,
            $year,
            $month,
            MonthlyReportService::BASIS_PAID_DATE,
            $branchId
        );

        $reportPdf = Pdf::loadView('pdf.teacher.teacher_with_student_payment_report', [
            'report' => $report,
            'year' => $year,
            'month' => $month,
            'branchName' => $slip['branch_name'] ?? null,
            'instituteName' => $institute,
            'poweredBy' => $poweredBy,
        ])->setPaper('a4', 'landscape')->output();

        $monthShort = Carbon::create($year, $month, 1)->format('Y M');
        $suffix = ($slip['teacher_custom_id'] ?: $teacherId) . '-' . Carbon::create($year, $month, 1)->format('Y-m');

        Mail::to($to)->send(new TeacherSalarySlipMail($slip, $monthShort, $slipPdf, $reportPdf, $suffix));
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Teacher salary slip email failed', ['salary_id' => $this->salaryId, 'error' => $e->getMessage()]);
    }
}
