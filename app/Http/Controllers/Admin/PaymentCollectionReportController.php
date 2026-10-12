<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PaymentCollectionReportExport;
use App\Http\Controllers\Controller;
use App\Services\PaymentCollectionReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PaymentCollectionReportController extends Controller
{
    protected $reportService;

    public function __construct(
        PaymentCollectionReportService $reportService
    ) {
        $this->reportService = $reportService;
    }

    /**
     * Monthly Payment Collection Report
     *
     * Report:
     * - Institute total
     * - Expected amount
     * - Collected amount
     * - Due amount
     * - Collection percentage
     * - Due percentage
     * - Teacher amount
     * - Organizer amount
     * - Institute amount
     * - Class + Grade + Category breakdown
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Selected month
        |--------------------------------------------------------------------------
        */

        $paymentMonth = $request->input(
            'payment_month',
            now()->startOfMonth()->format('Y-m-d')
        );

        /*
        |--------------------------------------------------------------------------
        | Validate month
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'payment_month' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Normalize month
        |--------------------------------------------------------------------------
        */

        $month = Carbon::createFromFormat(
            'Y-m-d',
            $paymentMonth
        )->startOfMonth();

        $paymentMonth = $month->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | Generate report
        |--------------------------------------------------------------------------
        */

        $report = $this->reportService->generate(
            $paymentMonth
        );

        /*
        |--------------------------------------------------------------------------
        | Available months
        |--------------------------------------------------------------------------
        */

        $availableMonths = [];

        for ($i = 0; $i < 12; $i++) {
            $date = now()
                ->subMonths($i)
                ->startOfMonth();

            $availableMonths[$date->format('Y-m-d')] =
                $date->format('F Y');
        }

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'admin.payment-collection-report.index',
            compact(
                'report',
                'paymentMonth',
                'availableMonths'
            )
        );
    }

    /**
     * Export Monthly Payment Collection Report
     */
    public function export(Request $request)
    {
        $paymentMonth = $request->get(
            'payment_month',
            now()->format('Y-m-01')
        );

        $fileName = 'payment-collection-report-' .
            Carbon::parse($paymentMonth)->format('Y-m') .
            '.xlsx';

        return Excel::download(
            new PaymentCollectionReportExport($paymentMonth),
            $fileName
        );
    }
}