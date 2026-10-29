<?php

namespace App\Http\Controllers\Web\Report;

use App\Exports\Web\Report\TableReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Report\InstituteIncomeReportRequest;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Report\InstituteIncomeReportService;
use App\Services\Web\Report\PaymentReportQuery;
use App\Services\Web\Report\ReportFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class InstituteIncomeReportController extends Controller
{
    public function __construct(
        protected InstituteIncomeReportService $reportService,
        protected PaymentReportQuery $reportQuery,
        protected BranchAccessService $branchAccessService
    ) {}

    public function index(InstituteIncomeReportRequest $request, string $tab = 'summary')
    {
        abort_unless(array_key_exists($tab, InstituteIncomeReportService::tabs()), 404);

        try {
            $filter = $this->makeFilter($request);
        } catch (DomainException $e) {
            return redirect()->route('reports.institute-income.index')->with('error', \App\Support\ErrorDetail::safe($e));
        }

        return view('reports.institute-income', [
            'tab' => $tab,
            'tabs' => InstituteIncomeReportService::tabs(),
            'filter' => $filter,
            'summary' => $this->reportService->summary($filter),
            'table' => $this->reportService->table($tab),
            'rows' => $tab === 'summary' ? collect() : $this->reportService->rows($tab, $filter),
            'branches' => $this->branchAccessService->getAccessibleBranches(),
            'branchName' => $this->reportService->branchName($filter),
        ]);
    }

    public function export(InstituteIncomeReportRequest $request, string $tab, string $format)
    {
        abort_unless(array_key_exists($tab, InstituteIncomeReportService::tabs()), 404);
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        try {
            $filter = $this->makeFilter($request);
            $rows = $this->reportService->rows($tab, $filter);
            $table = $this->reportService->table($tab);
            $title = 'Institute Income - ' . InstituteIncomeReportService::tabs()[$tab];
            $fileName = 'institute-income-' . $tab . '-' . $filter->from->format('Ymd') . '-' . $filter->to->format('Ymd');
            $info = [
                'Date: ' . $filter->dateLabel(),
                'Branch: ' . $this->reportService->branchName($filter),
                'Generated: ' . now()->format('d M Y h:i A'),
            ];

            if ($format === 'excel') {
                return Excel::download(
                    new TableReportExport($title, $info, $table, $rows, InstituteIncomeReportService::tabs()[$tab]),
                    $fileName . '.xlsx'
                );
            }

            return Pdf::loadView('pdf.reports.table', [
                'title' => $title,
                'infoLines' => $info,
                'table' => $table,
                'rows' => $rows,
            ])
                ->setPaper('a4', count($table['columns']) > 6 ? 'landscape' : 'portrait')
                ->download($fileName . '.pdf');
        } catch (DomainException $e) {
            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to generate the report file.');
        }
    }

    /**
     * Dashboard chart: class fees and institute share per month of a year.
     */
    public function yearly(InstituteIncomeReportRequest $request): JsonResponse
    {
        $year = (int) ($request->validated()['year'] ?? now()->year);

        return response()->json(['success' => true] + $this->reportService->yearly(
            $year,
            $this->branchAccessService->getBranchIds()->map(fn ($id) => (int) $id)
        ));
    }

    /**
     * Default range: this month.
     *
     * @throws DomainException
     */
    protected function makeFilter(InstituteIncomeReportRequest $request): ReportFilter
    {
        $validated = $request->validated();

        return $this->reportQuery->filter(
            $validated['from'] ?? now()->startOfMonth()->toDateString(),
            $validated['to'] ?? (isset($validated['from']) ? null : now()->toDateString()),
            isset($validated['branch_id']) ? (int) $validated['branch_id'] : null
        );
    }
}
