<?php

namespace App\Http\Controllers\Web\Report;

use App\Exports\Web\Report\TableReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Report\AnnualReportRequest;
use App\Models\Branch;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Report\AnnualReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AnnualReportController extends Controller
{
    public function __construct(
        protected AnnualReportService $reportService,
        protected BranchAccessService $branchAccessService
    ) {}

    public function index(AnnualReportRequest $request)
    {
        [$year, $branchId] = $this->params($request);

        try {
            $filter = $this->reportService->yearFilter($year, $branchId);
            $previousFilter = $this->reportService->previousYearFilter($filter);
        } catch (DomainException $e) {
            return redirect()->route('reports.annual.index')->with('error', $e->getMessage());
        }

        $report = $this->reportService->generate($filter);
        $previous = $this->reportService->generate($previousFilter)['totals'];

        return view('reports.annual', [
            'year' => $year,
            'filter' => $filter,
            'report' => $report,
            'previous' => $previous,
            'growth' => [
                'income' => $this->reportService->growth($report['totals']['total_income'], $previous['total_income']),
                'expenses' => $this->reportService->growth($report['totals']['total_expenses'], $previous['total_expenses']),
                'net' => $this->reportService->growth($report['totals']['net'], $previous['net']),
            ],
            'chart' => $this->reportService->chart($report['rows']),
            'years' => $this->reportService->yearOptions(),
            'branches' => $this->branchAccessService->getAccessibleBranches(),
        ]);
    }

    public function export(AnnualReportRequest $request, string $format)
    {
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        [$year, $branchId] = $this->params($request);

        try {
            $filter = $this->reportService->yearFilter($year, $branchId);
            $report = $this->reportService->generate($filter);
            $table = $this->reportService->table();
            $totals = $report['totals'];
            $branchName = $branchId ? (string) optional(Branch::query()->find($branchId))->name : 'All branches';

            $title = 'Annual Report ' . $year;
            $fileName = 'annual-report-' . $year . ($branchId ? '-branch-' . $branchId : '');

            $info = [
                'Year: ' . $year . ' (' . $filter->dateLabel() . ')',
                'Branch: ' . $branchName,
                'Total income: Rs. ' . number_format($totals['total_income'], 2),
                'Total expenses: Rs. ' . number_format($totals['total_expenses'], 2),
                'Net: Rs. ' . number_format($totals['net'], 2),
                'Generated: ' . now()->format('d M Y h:i A'),
            ];

            if ($format === 'excel') {
                return Excel::download(
                    new TableReportExport($title, $info, $table, $report['rows'], 'Annual ' . $year),
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
     * @return array{0: int, 1: ?int}
     */
    protected function params(AnnualReportRequest $request): array
    {
        $validated = $request->validated();

        return [
            (int) ($validated['year'] ?? now()->year),
            isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
        ];
    }
}