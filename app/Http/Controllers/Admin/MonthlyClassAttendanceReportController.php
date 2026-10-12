<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\MonthlyClassAttendanceReportExport;
use App\Services\MonthlyClassAttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyClassAttendanceReportController extends Controller
{
    protected $reportService;

    public function __construct(
        MonthlyClassAttendanceReportService $reportService
    ) {
        $this->reportService = $reportService;
    }

    /**
     * Display monthly class attendance report.
     */
    public function index(Request $request)
    {
        $month = $request->get(
            'month',
            now()->startOfMonth()->format('Y-m-d')
        );

        $request->validate([
            'month' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Normalize selected month
        |--------------------------------------------------------------------------
        */

        $selectedMonth = Carbon::createFromFormat(
            'Y-m-d',
            $month
        )->startOfMonth();

        $month = $selectedMonth->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | Generate Report
        |--------------------------------------------------------------------------
        */

        $report = $this->reportService->generate($month);

        /*
        |--------------------------------------------------------------------------
        | Available Months
        |--------------------------------------------------------------------------
        |
        | Current month + previous 11 months
        |
        */

        $availableMonths = collect();

        for ($i = 0; $i < 12; $i++) {
            $date = now()
                ->startOfMonth()
                ->subMonths($i);

            $availableMonths->push([
                'value' => $date->format('Y-m-d'),
                'label' => $date->format('F Y'),
            ]);
        }

        return view(
            'admin.monthly-class-attendance-report.index',
            compact(
                'report',
                'month',
                'availableMonths'
            )
        );
    }

    /**
     * Export monthly attendance report to Excel.
     */
    public function export(Request $request)
    {
        $month = $request->get(
            'month',
            now()->startOfMonth()->format('Y-m-d')
        );

        $request->validate([
            'month' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        $selectedMonth = Carbon::createFromFormat(
            'Y-m-d',
            $month
        )->startOfMonth();

        $month = $selectedMonth->format('Y-m-d');

        $fileName = 'monthly-class-attendance-report-' .
            $selectedMonth->format('Y-m') .
            '.xlsx';

        return Excel::download(
            new MonthlyClassAttendanceReportExport($month),
            $fileName
        );
    }
}