<?php

namespace App\Http\Controllers\Web\Report;

use App\Exports\Web\Report\TableReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Report\TeacherCollectionReportRequest;
use App\Models\Branch;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Report\PaymentReportQuery;
use App\Services\Web\Report\ReportFilter;
use App\Services\Web\Report\TeacherCollectionReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class TeacherCollectionReportController extends Controller
{
    public function __construct(
        protected TeacherCollectionReportService $reportService,
        protected PaymentReportQuery $reportQuery,
        protected BranchAccessService $branchAccessService
    ) {}

    public function index(TeacherCollectionReportRequest $request)
    {
        try {
            $filter = $this->makeFilter($request);
            $teacherId = $request->validated()['teacher_id'] ?? null;
            $report = $teacherId ? $this->reportService->generate((int) $teacherId, $filter) : null;
        } catch (DomainException $e) {
            return redirect()->route('reports.teacher-collection.index')->with('error', $e->getMessage());
        }

        return view('reports.teacher-collection', [
            'filter' => $filter,
            'teacherId' => $teacherId ? (int) $teacherId : null,
            'report' => $report,
            'table' => $this->reportService->table(),
            'teachers' => $this->reportService->teacherOptions($filter),
            'branches' => $this->branchAccessService->getAccessibleBranches(),
        ]);
    }

    public function export(TeacherCollectionReportRequest $request, string $format)
    {
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        try {
            $filter = $this->makeFilter($request);
            $report = $this->reportService->generate((int) $request->validated()['teacher_id'], $filter);

            $teacher = $report['teacher'];
            $title = 'Teacher Collection Report - ' . $teacher->custom_id . ' ' . $teacher->full_name;
            $table = $this->reportService->table();
            $fileName = 'teacher-collection-' . $teacher->custom_id . '-' . $filter->from->format('Ymd') . '-' . $filter->to->format('Ymd');

            $info = [
                'Date: ' . $filter->dateLabel(),
                'Branch: ' . ($filter->branchId ? optional(Branch::query()->find($filter->branchId))->name : 'All branches'),
                'Collected: Rs. ' . number_format($report['summary']['amount'], 2),
                'Teacher share: Rs. ' . number_format($report['summary']['teacher_amount'], 2),
            ];

            if ($format === 'excel') {
                return Excel::download(
                    new TableReportExport($title, $info, $table, $report['rows'], 'Teacher Collection'),
                    $fileName . '.xlsx'
                );
            }

            return Pdf::loadView('pdf.reports.table', [
                'title' => $title,
                'infoLines' => $info,
                'table' => $table,
                'rows' => $report['rows'],
            ])
                ->setPaper('a4', 'landscape')
                ->download($fileName . '.pdf');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to generate the report file.');
        }
    }

    /**
     * @throws DomainException
     */
    protected function makeFilter(TeacherCollectionReportRequest $request): ReportFilter
    {
        $validated = $request->validated();

        return $this->reportQuery->filter(
            $validated['from'] ?? null,
            $validated['to'] ?? null,
            isset($validated['branch_id']) ? (int) $validated['branch_id'] : null
        );
    }
}
