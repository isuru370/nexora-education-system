<?php

namespace App\Http\Controllers\Web\MonthlyReport;

use App\Exports\Web\MonthlyReport\TeacherSalaryReportExport;
use App\Exports\Web\MonthlyReport\TeacherStudentPaymentReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\MonthlyReport\MonthlyReportFilterRequest;
use App\Http\Requests\Web\MonthlyReport\TeacherStudentPaymentReportRequest;
use App\Services\Web\MonthlyReport\MonthlyReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class MonthlyReportController extends Controller
{
    public function __construct(
        protected MonthlyReportService $reportService
    ) {}

    public function index(MonthlyReportFilterRequest $request)
    {
        try {
            $branchId = $this->reportService->resolveBranchId($request->branchId());
        } catch (DomainException $e) {
            // Not allowed branch in the URL: fall back to the user's first branch
            try {
                $branchId = $this->reportService->resolveBranchId(null);
            } catch (DomainException $e) {
                abort(403, $e->getMessage());
            }
        }

        return view('monthly-report.index', [
            'teachers' => $this->reportService->teacherOptions(),
            'branches' => $this->reportService->branchOptions(),
            'branchId' => $branchId,
            'year' => $request->year(),
            'month' => $request->month(),
            'teacherId' => $request->teacherId(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Teacher salary summary
    |--------------------------------------------------------------------------
    */

    public function teacherSalaryExcel(MonthlyReportFilterRequest $request)
    {
        $year = $request->year();
        $month = $request->month();

        try {
            $branchId = $this->reportService->resolveBranchId($request->branchId());
            $report = $this->reportService->teacherSalaryReport($branchId, $year, $month);

            return Excel::download(
                new TeacherSalaryReportExport($report['rows'], $year, $month, optional($report['branch'])->name),
                "teacher-salary-report-{$branchId}-{$year}-{$month}.xlsx"
            );
        } catch (DomainException $e) {
            return $this->failed($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->failed('Failed to generate the teacher salary Excel file.');
        }
    }

    public function teacherSalaryPdf(MonthlyReportFilterRequest $request)
    {
        $year = $request->year();
        $month = $request->month();

        try {
            $branchId = $this->reportService->resolveBranchId($request->branchId());
            $report = $this->reportService->teacherSalaryReport($branchId, $year, $month);

            return Pdf::loadView('pdf.teacher.teacher_salary_report', [
                'report' => $report['rows'],
                'branch' => $report['branch'],
                'year' => $year,
                'month' => $month,
            ])
                ->setPaper('a4', 'landscape')
                ->download("teacher-salary-report-{$branchId}-{$year}-{$month}.pdf");
        } catch (DomainException $e) {
            return $this->failed($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->failed('Failed to generate the teacher salary PDF.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Teacher's students payment report
    | {basis} = fee-month | paid-date
    |--------------------------------------------------------------------------
    */

    public function studentPaymentExcel(TeacherStudentPaymentReportRequest $request, string $basis)
    {
        [$teacherId, $year, $month] = $this->studentReportInput($request);

        try {
            $report = $this->reportService->teacherStudentPaymentReport($teacherId, $year, $month, $basis);

            return Excel::download(
                new TeacherStudentPaymentReportExport($report, $year, $month),
                "teacher-student-payments-{$basis}-{$teacherId}-{$year}-{$month}.xlsx"
            );
        } catch (DomainException $e) {
            return $this->failed($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->failed('Failed to generate the student payment Excel file.');
        }
    }

    public function studentPaymentPdf(TeacherStudentPaymentReportRequest $request, string $basis)
    {
        [$teacherId, $year, $month] = $this->studentReportInput($request);

        try {
            $report = $this->reportService->teacherStudentPaymentReport($teacherId, $year, $month, $basis);

            return Pdf::loadView('pdf.teacher.teacher_with_student_payment_report', [
                'report' => $report,
                'year' => $year,
                'month' => $month,
            ])
                ->setPaper('a4', 'landscape')
                ->download("teacher-student-payments-{$basis}-{$teacherId}-{$year}-{$month}.pdf");
        } catch (DomainException $e) {
            return $this->failed($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->failed('Failed to generate the student payment PDF.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    protected function studentReportInput(TeacherStudentPaymentReportRequest $request): array
    {
        $validated = $request->validated();

        return [
            (int) $validated['teacher_id'],
            (int) $validated['year'],
            (int) $validated['month'],
        ];
    }

    /**
     * Downloads are opened from the report page, so go back there with the message.
     */
    protected function failed(string $message): RedirectResponse
    {
        return back()->with('error', $message);
    }
}