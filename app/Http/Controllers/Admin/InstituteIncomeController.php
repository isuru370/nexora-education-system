<?php

namespace App\Http\Controllers\Admin;

use App\Exports\institute\InstituteMonthlyIncomeReportExport;
use App\Http\Controllers\Controller;
use App\Services\InstituteIncomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class InstituteIncomeController extends Controller
{
    protected $instituteIncomeService;

    public function __construct(InstituteIncomeService $instituteIncomeService)
    {
        $this->instituteIncomeService = $instituteIncomeService;
    }

    /**
     * Monthly Institute Income Report
     */
    public function monthlyIncomeReport(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $request->validate([
            'year' => [
                'nullable',
                'integer',
                'min:2000',
                'max:2100',
            ],
            'month' => [
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
        ]);

        $report = $this->instituteIncomeService
            ->monthlyInstituteIncome($year, $month);

        return view('admin.institute-income.monthly-report', [
            'success' => true,

            'year' => $year,
            'month' => $month,

            'summary' => $report['summary'] ?? [],

            'teacher_summaries' => $report['teacher_summaries'] ?? [],

            'organizer_summaries' => $report['organizer_summaries'] ?? [],

            'class_summaries' => $report['class_summaries'] ?? [],

            'admission_payment_list' => $report['admission_payment_list'] ?? [],

            'extra_income_list' => $report['extra_income_list'] ?? [],

            'expense_list' => $report['expense_list'] ?? [],
        ]);
    }

    /**
     * Export Monthly Institute Income Report to Excel
     */
    public function monthlyIncomeReportExcel(Request $request)
    {
        try {
            $year = (int) $request->input('year', now()->year);
            $month = (int) $request->input('month', now()->month);

            $request->validate([
                'year' => [
                    'nullable',
                    'integer',
                    'min:2000',
                    'max:2100',
                ],
                'month' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:12',
                ],
            ]);

            $fileName = 'monthly-institute-income-report-'
                . $year
                . '-'
                . str_pad($month, 2, '0', STR_PAD_LEFT)
                . '.xlsx';

            return Excel::download(
                new InstituteMonthlyIncomeReportExport($year, $month),
                $fileName
            );
        } catch (\Throwable $e) {
            Log::error(
                'Error generating monthly institute income Excel report: '
                . $e->getMessage(),
                [
                    'exception' => $e,
                    'year' => $year ?? null,
                    'month' => $month ?? null,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate monthly institute income Excel report.',
            ], 500);
        }
    }
}
