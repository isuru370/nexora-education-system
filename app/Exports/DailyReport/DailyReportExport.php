<?php

namespace App\Exports\DailyReport;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DailyReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected $title;
    protected $headings;
    protected $columns;
    protected $rows;

    public function __construct($title, array $headings, array $columns, array $rows)
    {
        $this->title = $title;
        $this->headings = $headings;
        $this->columns = $columns;
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        return array_map(function ($column) use ($row) {
            return data_get($row, $column);
        }, $this->columns);
    }

    public function title(): string
    {
        return substr($this->title, 0, 31);
    }
}
