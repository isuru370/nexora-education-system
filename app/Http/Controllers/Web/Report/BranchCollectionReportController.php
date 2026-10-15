<?php

namespace App\Http\Controllers\Web\Report;

use App\Exports\Web\Report\TableReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Report\BranchCollectionReportRequest;
use App\Services\Web\Report\BranchCollectionReportService;
use App\Services\Web\Report\PaymentReportQuery;
use App\Services\Web\Report\ReportFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class BranchCollectionReportController extends Controller
{
    public function __construct(
        protected BranchCollectionReportService $reportService,
        protected PaymentReportQuery $reportQuery
    ) {}

    public function index(BranchCollectionReportRequest $request)
    {
        try {
            $filter = $this->makeFilter($request);
        } catch (DomainException $e) {
            return redirect()->route('reports.branch-collection.index')->with('error', $e->getMessage());
        }

        return view('reports.branch-collection', [
            'filter' => $filter,
            'report' => $this->reportService->generate($filter),
            'table' => $this->reportService->table(),
            'label' => fn (string $method) => $this->reportService->label($method),
        ]);
    }

    public function export(BranchCollectionReportRequest $request, string $format)
    {
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        try {
            $filter = $this->makeFilter($request);
            $report = $this->reportService->generate($filter);
            $table = $this->reportService->table();
            $title = 'Branch-wise Collection Report';
            $fileName = 'branch-collection-' . $filter->from->format('Ymd') . '-' . $filter->to->format('Ymd');
            $totals = $report['totals'];

            $info = [
                'Date: ' . $filter->dateLabel(),
                'Total income: Rs. ' . number_format($totals['total_income'], 2),
                'Total expenses: Rs. ' . number_format($totals['total_expenses'], 2),
                'Net: Rs. ' . number_format($totals['net'], 2),
                'Generated: ' . now()->format('d M Y h:i A'),
            ];

            if ($format === 'excel') {
                return Excel::download(
                    new TableReportExport($title, $info, $table, $report['rows'], 'Branch-wise'),
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
     * Default range: this month up to today. Always all branches the user can access.
     *
     * @throws DomainException
     */
    protected function makeFilter(BranchCollectionReportRequest $request): ReportFilter
    {
        $validated = $request->validated();

        return $this->reportQuery->filter(
            $validated['from'] ?? now()->startOfMonth()->toDateString(),
            $validated['to'] ?? (isset($validated['from']) ? null : now()->toDateString())
        );
    }
}