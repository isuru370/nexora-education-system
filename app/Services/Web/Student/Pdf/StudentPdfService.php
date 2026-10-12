<?php

namespace App\Services\Web\Student\Pdf;

use App\Models\Student;
use App\Services\Branch\BranchAccessService;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentPdfService
{
    public function __construct(
        private BranchAccessService $branchAccessService
    ) {
    }

    /**
     * Generate and download students PDF.
     *
     * Only students belonging to branches
     * accessible by the current user are included.
     */
    public function download()
    {
        $branchIds = $this->branchAccessService
            ->getBranchIdArray();

        $students = Student::query()
            ->with([
                'grade',
                'branch',
            ])
            ->whereIn('branch_id', $branchIds)
            ->orderBy('branch_id')
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView(
            'pdf.student.index',
            compact('students')
        );

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download(
            'students-report-' . now()->format('Y-m-d') . '.pdf'
        );
    }
}