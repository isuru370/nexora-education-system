<?php

namespace App\Http\Controllers\Web\Payment;

use App\Exports\Web\Report\TableReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Payment\DeletePaymentRequest;
use App\Http\Requests\Web\Payment\PaymentHistoryRequest;
use App\Services\Branch\BranchAccessService;
use App\Services\Web\Payment\PaymentDeleteService;
use App\Services\Web\Payment\PaymentHistoryService;
use App\Services\Web\Payment\ReceiptService;
use App\Services\Web\Report\PaymentReportQuery;
use App\Services\Web\Report\ReportFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Payments History: list, details, receipt reprint and delete of class fee payments.
 */
class PaymentHistoryController extends Controller
{
    protected const PER_PAGE = 50;

    public function __construct(
        protected PaymentHistoryService $historyService,
        protected PaymentReportQuery $reportQuery,
        protected BranchAccessService $branchAccessService
    ) {}

    public function index(PaymentHistoryRequest $request)
    {
        try {
            $filter = $this->makeFilter($request);
        } catch (DomainException $e) {
            return redirect()->route('payments.index')->with('error', $e->getMessage());
        }

        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $status = $this->historyService->statusOf($validated['status'] ?? null);
        $query = $this->historyService->query($filter, $search, $status);

        $payments = $query->paginate(self::PER_PAGE)
            ->appends($filter->toQuery() + array_filter(['search' => $search, 'status' => $status]));

        $payments->setCollection($payments->getCollection()->map(fn ($payment) => $this->historyService->row($payment)));

        return view('payments.index', [
            'filter' => $filter,
            'search' => $search,
            'status' => $status,
            'statuses' => PaymentHistoryService::STATUSES,
            'payments' => $payments,
            'summary' => $this->historyService->summary($query),
            'branches' => $this->branchAccessService->getAccessibleBranches(),
            'collectors' => $this->reportQuery->collectorOptions($filter->branchIds),
            'paymentMethods' => PaymentReportQuery::PAYMENT_METHODS,
            'label' => fn ($value) => $this->historyService->label($value),
        ]);
    }

    public function export(PaymentHistoryRequest $request, string $format)
    {
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        try {
            $filter = $this->makeFilter($request);
            $validated = $request->validated();
            $status = $this->historyService->statusOf($validated['status'] ?? null);
            $query = $this->historyService->query($filter, $validated['search'] ?? null, $status);
            $summary = $this->historyService->summary($query);
            $rows = $this->historyService->exportRows($query);
            $table = $this->historyService->table();
            $title = 'Payments History - ' . PaymentHistoryService::STATUSES[$status];
            $fileName = 'payments-' . $status . '-' . $filter->from->format('Ymd') . '-' . $filter->to->format('Ymd');

            $info = [
                'Date: ' . $filter->dateLabel(),
                'Payments: ' . $summary['total'],
                'Completed (not deleted): ' . $summary['completed'] . ' = Rs. ' . number_format($summary['amount'], 2),
            ];

            if (!empty($validated['search'])) {
                $info[] = 'Search: ' . $validated['search'];
            }

            if ($format === 'excel') {
                return Excel::download(new TableReportExport($title, $info, $table, $rows, 'Payments'), $fileName . '.xlsx');
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
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to generate the file.');
        }
    }

    public function show(int $payment)
    {
        try {
            $model = $this->historyService->find($payment);
        } catch (DomainException $e) {
            abort(404);
        }

        return view('payments.show', [
            'payment' => $model,
            'row' => $this->historyService->row($model),
            'deleteUntil' => $this->historyService->deleteUntil($model),
            'label' => fn ($value) => $this->historyService->label($value),
        ]);
    }

    /**
     * 58 mm receipt for reprinting (opens the print dialog).
     */
    public function receipt(int $payment, ReceiptService $receiptService)
    {
        try {
            $model = $this->historyService->find($payment);
        } catch (DomainException $e) {
            abort(404);
        }

        if ($model->trashed() || $model->status !== 'completed') {
            return redirect()->route('payments.show', $model->id)
                ->with('error', 'A receipt can only be printed for a completed payment.');
        }

        return view('payments.receipt', [
            'payment' => $model,
            'receipt' => $receiptService->single($model),
        ]);
    }

    public function destroy(DeletePaymentRequest $request, int $payment, PaymentDeleteService $deleteService)
    {
        try {
            $deleted = $deleteService->delete($payment, $request->validated()['reason']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Failed to delete the payment.');
        }

        Log::info('Payment deleted.', [
            'payment_id' => $deleted->id,
            'receipt_number' => $deleted->receipt_number,
            'amount' => (float) $deleted->amount,
            'deleted_by' => auth()->id(),
        ]);

        return redirect()->route('payments.show', $deleted->id)
            ->with('success', 'Payment ' . $deleted->receipt_number . ' was deleted. The same class and month can now be paid again.');
    }

    /**
     * @throws DomainException
     */
    protected function makeFilter(PaymentHistoryRequest $request): ReportFilter
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
}
