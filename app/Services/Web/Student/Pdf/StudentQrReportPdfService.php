<?php

namespace App\Services\Web\Student\Pdf;

use App\Models\Student;
use App\Services\Branch\BranchAccessService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class StudentQrReportPdfService
{
    public function __construct(
        private BranchAccessService $branchAccessService
    ) {
    }

    /**
     * Download QR report for accessible students.
     */
    public function download(): Response
    {
        $branchIds = $this->branchAccessService
            ->getBranchIdArray();

        $students = Student::query()
            ->with([
                'branch',
                'grade',
            ])
            ->whereIn('branch_id', $branchIds)
            ->orderBy('branch_id')
            ->orderBy('grade_id')
            ->orderBy('full_name')
            ->get();

        $pdf = Pdf::loadView(
            'pdf.student.qr-report',
            [
                'students' => $students,
                'generatedAt' => now(),
            ]
        );

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download(
            'student-qr-report-' . now()->format('Y-m-d') . '.pdf'
        );
    }
}