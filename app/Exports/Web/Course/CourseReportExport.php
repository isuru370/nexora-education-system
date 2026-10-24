<?php

namespace App\Exports\Web\Course;

use App\Exports\Web\Report\TableReportExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** A course report as an Excel file: summary + one sheet per table. */
class CourseReportExport implements WithMultipleSheets
{
    public function __construct(private array $report)
    {
    }

    public function sheets(): array
    {
        $info = [$this->report['subtitle'], 'Generated ' . now()->format('Y-m-d H:i')];

        $summary = collect($this->report['summary'])
            ->map(fn ($value, $label) => ['label' => $label, 'value' => $value])
            ->values();

        $sheets = [new TableReportExport(
            $this->report['title'],
            $info,
            ['headings' => ['Item', 'Value'], 'columns' => ['label', 'value'], 'money' => [], 'total' => null],
            $summary,
            'Summary'
        )];

        foreach ($this->report['tables'] as $table) {
            $sheets[] = new TableReportExport(
                $this->report['title'] . ' - ' . $table['title'],
                $info,
                [
                    'headings' => array_values($table['columns']),
                    'columns' => array_keys($table['columns']),
                    'money' => $table['money'],
                    'total' => $table['total'] ? 'yes' : null,
                ],
                $table['rows'] instanceof Collection ? $table['rows'] : collect($table['rows']),
                $table['title']
            );
        }

        return $sheets;
    }
}
