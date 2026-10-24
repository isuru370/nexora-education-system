<?php

namespace App\Http\Controllers\Web\Course;

use App\Exports\Web\Course\CourseReportExport;
use App\Http\Controllers\Controller;
use App\Services\Course\CourseReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/** Course reports: daily / monthly / yearly / all courses, as page, PDF or Excel. */
class CourseReportController extends Controller
{
    use ChecksCourseBranch;

    public const TYPES = ['daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'courses' => 'All Courses'];

    public function __construct(private CourseReportService $reports)
    {
    }

    public function show(Request $request, string $type)
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'export' => ['nullable', 'in:pdf,excel'],
        ]);

        $branchIds = $this->branchIds();

        $filters = [
            'date' => $request->input('date', today()->toDateString()),
            'month' => $request->input('month', today()->format('Y-m')),
            'year' => (int) $request->input('year', today()->year),
            'active' => $request->boolean('active'),
        ];

        $report = match ($type) {
            'daily' => $this->reports->daily(Carbon::parse($filters['date']), $branchIds),
            'monthly' => $this->reports->monthly((int) substr($filters['month'], 0, 4), (int) substr($filters['month'], 5, 2), $branchIds),
            'yearly' => $this->reports->yearly($filters['year'], $branchIds),
            'courses' => $this->reports->courses($branchIds, $filters['active']),
        };

        $file = Str::slug($report['title'] . ' ' . $report['subtitle']);

        if ($request->input('export') === 'excel') {
            return Excel::download(new CourseReportExport($report), $file . '.xlsx');
        }

        if ($request->input('export') === 'pdf') {
            return Pdf::loadView('courses.reports.pdf', compact('report'))
                ->setPaper('a4', $type === 'courses' ? 'landscape' : 'portrait')
                ->download($file . '.pdf');
        }

        return view('courses.reports.show', [
            'report' => $report,
            'type' => $type,
            'types' => self::TYPES,
            'filters' => $filters,
        ]);
    }
}
