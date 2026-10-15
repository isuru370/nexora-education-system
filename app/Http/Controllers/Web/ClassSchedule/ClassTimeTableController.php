<?php

namespace App\Http\Controllers\Web\ClassSchedule;

use App\Exports\Web\ClassSchedule\WeeklyTimeTableExport;
use App\Http\Controllers\Controller;
use App\Services\Web\ClassSchedule\ClassTimeTableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ClassTimeTableController extends Controller
{
    public function __construct(
        protected ClassTimeTableService $timeTableService,
    ) {}


    /*
    |--------------------------------------------------------------------------
    | Weekly Time Table — Page
    |--------------------------------------------------------------------------
    */

    /**
     * Weekly timetable page.
     */
    public function weeklyTimeTable(Request $request)
    {
        try {
            $validated = $request->validate([
                'week_date' => ['nullable', 'date'],
            ]);

            $dateData = $this->timeTableService->resolveWeekDate(
                $validated['week_date'] ?? null
            );

            $schedules = $this->timeTableService->getWeeklySchedules(
                $dateData['startOfWeek'],
                $dateData['endOfWeek']
            );

            $stats = $this->timeTableService->getWeeklyScheduleStats(
                $dateData['startOfWeek'],
                $dateData['endOfWeek']
            );

            return view(
                'class-time-table.weekly',
                [
                    'schedules' => $schedules,
                    'stats' => $stats,
                    'selectedDate' => $dateData['selectedDate'],
                    'startOfWeek' => $dateData['startOfWeek'],
                    'endOfWeek' => $dateData['endOfWeek'],
                ]
            );
        } catch (Throwable $e) {
            Log::error('Weekly timetable load failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to load weekly timetable. Please try again later.');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Download PDF
    |--------------------------------------------------------------------------
    */

    /**
     * Download weekly timetable as PDF (branch-scoped).
     */
    public function downloadPdf(Request $request)
    {
        try {
            $dateData = $this->timeTableService->resolveWeekDate(
                $request->get('week_date')
            );

            $schedules = $this->timeTableService->getWeeklySchedules(
                $dateData['startOfWeek'],
                $dateData['endOfWeek']
            );

            $pdf = Pdf::loadView(
                'pdf.class-schedule.weekly-time-table',
                [
                    'schedules' => $schedules,
                    'startOfWeek' => $dateData['startOfWeek'],
                    'endOfWeek' => $dateData['endOfWeek'],
                    'selectedDate' => $dateData['selectedDate'],
                    'weekNumber' => $dateData['weekNumber'],
                    'year' => $dateData['year'],
                    'generatedAt' => now(),
                ]
            );

            $pdf->setPaper('A4', 'landscape');

            return $pdf->download(
                "weekly_timetable_week_{$dateData['weekNumber']}_{$dateData['year']}.pdf"
            );
        } catch (Throwable $e) {
            Log::error('PDF download failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with(
                'error',
                'Failed to download PDF. Please try again later.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Download Excel
    |--------------------------------------------------------------------------
    */

    /**
     * Download weekly timetable as Excel (branch-scoped).
     */
    public function downloadExcel(Request $request)
    {
        try {
            $dateData = $this->timeTableService->resolveWeekDate(
                $request->get('week_date')
            );

            $schedules = $this->timeTableService->getWeeklySchedules(
                $dateData['startOfWeek'],
                $dateData['endOfWeek']
            );

            $export = new WeeklyTimeTableExport(
                $this->timeTableService->buildExportDataset(
                    $schedules,
                    $dateData['startOfWeek'],
                    $dateData['endOfWeek']
                )
            );

            return Excel::download(
                $export,
                "weekly_timetable_week_{$dateData['weekNumber']}_{$dateData['year']}.xlsx"
            );
        } catch (Throwable $e) {
            Log::error('Excel download failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with(
                'error',
                'Failed to download Excel. Please try again later.'
            );
        }
    }
}
