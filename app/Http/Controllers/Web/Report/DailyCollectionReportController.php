<?php

namespace App\Http\Controllers\Web\Report;

use App\Exports\Web\Report\TableReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Report\DailyCollectionReportRequest;
use App\Models\User;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Report\DailyCollectionReportService;
use App\Services\Web\Report\PaymentReportQuery;
use App\Services\Web\Report\ReportFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use DomainException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class DailyCollectionReportController extends Controller
{
    protected const PER_PAGE = 50;

    public function __construct(
        protected DailyCollectionReportService $reportService,
        protected PaymentReportQuery $reportQuery,
        protected BranchAccessService $branchAccessService
    ) {}

    public function index(DailyCollectionReportRequest $request, string $tab = 'summary')
    {
        abort_unless(array_key_exists($tab, DailyCollectionReportService::TABS), 404);

        try {
            $filter = $this->makeFilter($request);
        } catch (DomainException $e) {
            return redirect()->route('reports.daily-collection.index')->with('error', \App\Support\ErrorDetail::safe($e));
        }

        $rows = $this->reportService->rows($tab, $filter, $tab === 'summary' ? null : self::PER_PAGE);

        // Only the detail tabs are paginated (summary and branches are short lists)
        if ($rows instanceof LengthAwarePaginator) {
            $rows->appends($filter->toQuery());
        }

        return view('reports.daily-collection', [
            'tab' => $tab,
            'tabs' => DailyCollectionReportService::TABS,
            'filter' => $filter,
            'summary' => $this->reportService->summary($filter),
                        'table' => $this->reportService->table($tab, $filter),
            'rows' => $rows,
            'branches' => $this->branchAccessService->getAccessibleBranches(),
            'collectors' => $this->reportQuery->collectorOptions($filter->branchIds),
            'paymentMethods' => PaymentReportQuery::PAYMENT_METHODS,
            'branchName' => $this->reportService->branchName($filter),
            'methodLabel' => fn($method) => $this->reportService->methodLabel($method),
        ]);
    }

    public function export(DailyCollectionReportRequest $request, string $tab, string $format)
    {
        abort_unless(array_key_exists($tab, DailyCollectionReportService::TABS), 404);
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        try {
            $filter = $this->makeFilter($request);
            $rows = $this->reportService->rows($tab, $filter);
                        $table = $this->reportService->table($tab, $filter);
            $title = 'Collection Report - ' . DailyCollectionReportService::TABS[$tab];
            $info = $this->infoLines($filter);
            $fileName = 'collection-' . $tab . '-' . $filter->from->format('Ymd') . '-' . $filter->to->format('Ymd');

            if ($format === 'excel') {
                return Excel::download(
                    new TableReportExport($title, $info, $table, $rows, DailyCollectionReportService::TABS[$tab]),
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
     * @throws DomainException
     */
    protected function makeFilter(DailyCollectionReportRequest $request): ReportFilter
    {
        $validated = $request->validated();

        return $this->reportQuery->filter(
            $validated['from'] ?? null,
            $validated['to'] ?? null,
            isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            isset($validated['collected_by']) ? (int) $validated['collected_by'] : null,
            $validated['payment_method'] ?? null
        );
    }

    protected function infoLines(ReportFilter $filter): array
    {
        $lines = [
            'Date: ' . $filter->dateLabel(),
            'Branch: ' . $this->reportService->branchName($filter),
        ];

        if ($filter->collectedBy) {
            $lines[] = 'Collected by: ' . optional(User::query()->find($filter->collectedBy))->name;
        }

        if ($filter->paymentMethod) {
            $lines[] = 'Method: ' . $this->reportService->methodLabel($filter->paymentMethod);
        }

        $lines[] = 'Generated: ' . now()->format('d M Y h:i A');

        return $lines;
    }
}
