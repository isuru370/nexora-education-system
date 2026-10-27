<?php

namespace App\Http\Controllers\Web\Receipt;

use App\Exports\Web\Receipts\ReceiptsExport;
use App\Http\Controllers\Controller;
use App\Services\Web\Receipt\ReceiptListService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    public function __construct(
        protected ReceiptListService $receiptListService
    ) {}

    public const PER_PAGE = 100;

    public function index(Request $request): View
    {
        $receipts = $this->receiptListService->list($this->filters($request));

        // Totals for all the receipts in the filter; the table shows one page
        $page = max((int) $request->query('page', 1), 1);
        $paginated = new LengthAwarePaginator(
            $receipts->forPage($page, self::PER_PAGE)->values(),
            $receipts->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('receipts.index', array_merge(
            ['receipts' => $paginated],
            $this->receiptListService->summary($receipts)
        ));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $receipts = $this->receiptListService->list($this->filters($request));

        return Excel::download(
            new ReceiptsExport($receipts),
            $this->fileName($request, 'xlsx')
        );
    }

    public function exportPdf(Request $request): Response
    {
        $filters = $this->filters($request);
        $receipts = $this->receiptListService->list($filters);

        return Pdf::loadView('pdf.receipts.receipts_pdf', array_merge(
            [
                'receipts' => $receipts,
                'filters' => $filters,
            ],
            $this->receiptListService->summary($receipts)
        ))
            ->setPaper('a4', 'landscape')
            ->download($this->fileName($request, 'pdf'));
    }

    /**
     * Without a date or receipt number: this month (thousands of receipts
     * after moving from an old system would make the page very slow).
     */
    private function filters(Request $request): array
    {
        if (!$request->filled('from_date') && !$request->filled('to_date') && !$request->filled('receipt_number')) {
            $request->merge([
                'from_date' => now()->startOfMonth()->toDateString(),
                'to_date' => now()->toDateString(),
            ]);
        }

        return $request->only([
            'receipt_number',
            'type',
            'from_date',
            'to_date',
            'status',
        ]);
    }

    /**
     * receipts_2026-10-13.xlsx  /  receipts_2026-10-01_to_2026-10-13.pdf
     */
    private function fileName(Request $request, string $extension): string
    {
        $from = $request->query('from_date');
        $to = $request->query('to_date');

        $period = $from || $to
            ? ($from ?: 'start') . '_to_' . ($to ?: now()->toDateString())
            : now()->toDateString();

        return "receipts_{$period}.{$extension}";
    }
}