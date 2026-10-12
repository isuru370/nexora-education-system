<?php

namespace App\Http\Controllers\Admin;

use App\Exports\institute\InstituteFinancialReportExport;
use App\Http\Controllers\Controller;
use App\Models\ExtraIncome;
use App\Models\InstitutePayment;
use App\Models\PaymentSplitSnapshot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class InstituteReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $snapshotQuery = $this->buildSnapshotQuery($filters);
        $extraIncomeQuery = $this->buildExtraIncomeQuery($filters);
        $expenseQuery = $this->buildExpenseQuery($filters);

        $snapshotPayments = $snapshotQuery
            ->with([
                'studentClass',
                'teacher',
                'organizer',
                'createdBy',
            ])
            ->orderBy('payment_date', 'asc')
            ->get();

        $extraIncomes = $extraIncomeQuery
            ->with('createdBy')
            ->orderBy('income_date', 'asc')
            ->get();

        $expenses = $expenseQuery
            ->with('createdBy')
            ->orderBy('payment_date', 'asc')
            ->get();

        $summary = $this->buildSummary(
            $snapshotPayments,
            $extraIncomes,
            $expenses
        );

        return view('admin.institute_report.index', compact(
            'filters',
            'summary',
            'snapshotPayments',
            'extraIncomes',
            'expenses'
        ));
    }

    public function institutePaymentReportExcel(Request $request)
    {
        try {
            $filters = $this->getFilters($request);

            $fileName = 'institute-financial-report-'
                . ($filters['start_date'] ?? now()->format('Y-m-d'))
                . '-to-'
                . ($filters['end_date'] ?? now()->format('Y-m-d'))
                . '.xlsx';

            return Excel::download(
                new InstituteFinancialReportExport($filters),
                $fileName
            );
        } catch (\Throwable $e) {
            Log::error('Error generating institute financial Excel report: ' . $e->getMessage(), [
                'exception' => $e,
                'filters' => $filters ?? [],
            ]);

            return response()->json([
                'error' => 'Failed to generate Excel report',
            ], 500);
        }
    }


    public function institutePaymentReportPdf(Request $request)
    {
        try {
            $filters = $this->getFilters($request);

            $snapshotPayments = $this->buildSnapshotQuery($filters)
                ->with([
                    'studentClass',
                    'teacher',
                    'organizer',
                    'createdBy',
                ])
                ->orderBy('payment_date', 'asc')
                ->get();

            $extraIncomes = $this->buildExtraIncomeQuery($filters)
                ->with('createdBy')
                ->orderBy('income_date', 'asc')
                ->get();

            $expenses = $this->buildExpenseQuery($filters)
                ->with('createdBy')
                ->orderBy('payment_date', 'asc')
                ->get();

            $summary = $this->buildSummary(
                $snapshotPayments,
                $extraIncomes,
                $expenses
            );

            $pdf = Pdf::loadView('admin.institute_report.pdf', [
                'filters' => $filters,
                'summary' => $summary,
                'snapshotPayments' => $snapshotPayments,
                'extraIncomes' => $extraIncomes,
                'expenses' => $expenses,
            ])->setPaper('a4', 'portrait');

            $fileName = 'institute-report-'
                . ($filters['start_date'] ?? now()->format('Y-m-d'))
                . '-to-'
                . ($filters['end_date'] ?? now()->format('Y-m-d'))
                . '.pdf';

            return $pdf->download($fileName);
        } catch (\Throwable $e) {
            Log::error('Error generating PDF report: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'error' => 'Failed to generate PDF report',
            ], 500);
        }
    }

    /**
     * Build the report summary from payment split snapshots,
     * extra incomes and institute expenses.
     *
     * Important:
     * - institution_amount already includes the institution's class-fee
     *   share + the hall fee.
     * - hall_fee must NOT be added again to total institute income.
     * - teacher/organizer percentages apply only to class_fee.
     */
    private function buildSummary($snapshotPayments, $extraIncomes, $expenses): array
    {
        $classFeeIncome = (float) $snapshotPayments->sum('class_fee');
        $hallFeeIncome = (float) $snapshotPayments->sum('hall_fee');
        $totalFeeIncome = (float) $snapshotPayments->sum('total_fee');

        $teacherIncome = (float) $snapshotPayments->sum('teacher_amount');
        $organizerIncome = (float) $snapshotPayments->sum('organizer_amount');
        $institutionIncome = (float) $snapshotPayments->sum('institution_amount');

        $extraIncomeTotal = (float) $extraIncomes->sum('amount');
        $expenseTotal = (float) $expenses->sum('amount');

        /*
         * institution_amount already contains:
         *
         * institution class share + hall fee
         *
         * Therefore hall_fee is NOT added again here.
         */
        $totalIncome = $institutionIncome + $extraIncomeTotal;

        $netTotal = $totalIncome - $expenseTotal;

        return [
            // Payment snapshot totals
            'snapshot_income_total' => $institutionIncome,
            'snapshot_count' => $snapshotPayments->count(),

            // Fee breakdown
            'class_fee_income' => $classFeeIncome,
            'hall_fee_income' => $hallFeeIncome,
            'total_fee_income' => $totalFeeIncome,

            // Split breakdown
            'teacher_income' => $teacherIncome,
            'organizer_income' => $organizerIncome,
            'institution_income' => $institutionIncome,

            // Other income
            'extra_income_total' => $extraIncomeTotal,
            'extra_income_count' => $extraIncomes->count(),

            // Overall income
            'total_income' => $totalIncome,

            // Expenses
            'total_expense' => $expenseTotal,
            'expense_count' => $expenses->count(),

            // Net
            'net_total' => $netTotal,

            // Record count
            'total_records' =>
                $snapshotPayments->count()
                + $extraIncomes->count()
                + $expenses->count(),
        ];
    }

    private function getFilters(Request $request): array
    {
        $period = $request->input('period', 'custom');

        $startDate = null;
        $endDate = null;

        if ($period === 'daily') {
            $date = $request->input('date', now()->toDateString());

            $parsedDate = Carbon::parse($date);

            $startDate = $parsedDate->copy()
                ->startOfDay()
                ->toDateString();

            $endDate = $parsedDate->copy()
                ->endOfDay()
                ->toDateString();
        } elseif ($period === 'monthly') {
            $month = $request->input(
                'month',
                now()->format('Y-m')
            );

            $parsedMonth = Carbon::parse($month . '-01');

            $startDate = $parsedMonth->copy()
                ->startOfMonth()
                ->toDateString();

            $endDate = $parsedMonth->copy()
                ->endOfMonth()
                ->toDateString();
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        }

        return [
            'period' => $period,
            'date' => $request->input('date'),
            'month' => $request->input('month'),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function buildSnapshotQuery(array $filters)
    {
        $query = PaymentSplitSnapshot::query();

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('payment_date', [
                $filters['start_date'],
                $filters['end_date'],
            ]);
        }

        return $query;
    }

    private function buildExtraIncomeQuery(array $filters)
    {
        $query = ExtraIncome::query()
            ->where('status', 'received');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('income_date', [
                $filters['start_date'],
                $filters['end_date'],
            ]);
        }

        return $query;
    }

    private function buildExpenseQuery(array $filters)
    {
        $query = InstitutePayment::query()
            ->where('payment_type', 'expense')
            ->where('status', 'paid');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('payment_date', [
                $filters['start_date'],
                $filters['end_date'],
            ]);
        }

        return $query;
    }
}
