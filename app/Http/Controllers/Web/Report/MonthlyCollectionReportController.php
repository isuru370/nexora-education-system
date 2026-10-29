<?php

namespace App\Http\Controllers\Web\Report;

use App\Exports\Web\Report\TableReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Report\MonthlyCollectionReportRequest;
use App\Models\Branch;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Report\MonthlyCollectionReportService;
use App\Services\Web\Report\PaymentReportQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DomainException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class MonthlyCollectionReportController extends Controller
{
    public function __construct(
        protected MonthlyCollectionReportService $reportService,
        protected PaymentReportQuery $reportQuery,
        protected BranchAccessService $branchAccessService
    ) {}

    public function index(MonthlyCollectionReportRequest $request)
    {
        try {
            [$filter, $month] = $this->makeFilter($request);
        } catch (DomainException $e) {
            return redirect()->route('reports.monthly-collection.index')->with('error', \App\Support\ErrorDetail::safe($e));
        }

        return view('reports.monthly-collection', [
            'report' => $this->reportService->generate($filter, $month),
            'table' => $this->reportService->table(),
            'daily' => $this->reportService->dailyBreakdown($filter),
            'filter' => $filter,
            'month' => $month,
            'branches' => $this->branchAccessService->getAccessibleBranches(),
        ]);
    }

    /**
     * ?table=classes (default) - expected vs collected per class (by fee month)
     * ?table=daily             - day by day income / expenses (by date paid)
     */
    public function export(MonthlyCollectionReportRequest $request, string $format)
    {
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        try {
            [$filter, $month] = $this->makeFilter($request);

            $branchName = $filter->branchId ? optional(Branch::query()->find($filter->branchId))->name : 'All branches';
            $monthLabel = $month->format('F Y');

            if (($request->validated()['table'] ?? 'classes') === 'daily') {
                // Day by day: money received / paid out on each day of the month
                $daily = $this->reportService->dailyBreakdown($filter);
                $rows = $daily['rows'];
                $table = $this->reportService->dailyTable();
                $title = 'Day-by-day Collection - ' . $monthLabel;
                $fileName = 'monthly-daily-' . $month->format('Y-m');

                $info = [
                    'Month: ' . $monthLabel . ' (by date paid)',
                    'Branch: ' . $branchName,
                    'Total income: Rs. ' . number_format($daily['totals']['total_income'], 2),
                    'Total expenses: Rs. ' . number_format($daily['totals']['total_expenses'], 2),
                    'Net: Rs. ' . number_format($daily['totals']['net'], 2),
                ];
            } else {
                // Expected vs collected per class (by fee month)
                $report = $this->reportService->generate($filter, $month);
                $rows = $report['rows'];
                $table = $this->reportService->table();
                $title = 'Monthly Collection Report - ' . $monthLabel;
                $fileName = 'monthly-collection-' . $month->format('Y-m');

                $info = [
                    'Fee month: ' . $monthLabel,
                    'Branch: ' . $branchName,
                    'Expected: Rs. ' . number_format($report['summary']['expected'], 2),
                    'Collected: Rs. ' . number_format($report['summary']['collected'], 2)
                        . ' (' . $report['summary']['collection_percentage'] . '%)',
                    'Due: Rs. ' . number_format($report['summary']['due'], 2),
                ];
            }

            if ($format === 'excel') {
                return Excel::download(
                    new TableReportExport($title, $info, $table, $rows, $monthLabel),
                    $fileName . '.xlsx'
                );
            }

            return Pdf::loadView('pdf.reports.table', [
                'title' => $title,
                'infoLines' => $info,
                'table' => $table,
                'rows' => $rows,
            ])
                ->setPaper('a4', 'landscape')
                ->download($fileName . '.pdf');
        } catch (DomainException $e) {
            return back()->with('error', \App\Support\ErrorDetail::safe($e));
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to generate the report file.');
        }
    }

    /**
     * @return array{0: \App\Services\Web\Report\ReportFilter, 1: Carbon}
     *
     * @throws DomainException
     */
    protected function makeFilter(MonthlyCollectionReportRequest $request): array
    {
        $validated = $request->validated();

        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m-d', $validated['month'] . '-01')->startOfDay()
            : now()->startOfMonth();

        $filter = $this->reportQuery->filter(
            $month->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
            isset($validated['branch_id']) ? (int) $validated['branch_id'] : null
        );

        return [$filter, $month];
    }
}