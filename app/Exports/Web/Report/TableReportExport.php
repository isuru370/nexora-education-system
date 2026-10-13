<?php

namespace App\Exports\Web\Report;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * One Excel export for every table report.
 *
 * $table = ['headings' => [...], 'columns' => [...], 'money' => [...], 'total' => 'column'|null,
 *          'no_total' => [...] (optional: money columns left out of the total row)]
 *
 * Adds: title + filter lines on top, bold header, money format, auto filter,
 * frozen header and a total row (SUM formulas) for every money column.
 */
class TableReportExport implements
    FromCollection,
    WithHeadings,
    WithEvents,
    WithTitle,
    WithStrictNullComparison,
    ShouldAutoSize
{
    protected const HEADER_ROW = 4;

    public function __construct(
        protected string $title,
        protected array $infoLines,
        protected array $table,
        protected Collection $rows,
        protected string $sheetTitle = 'Report'
    ) {}

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            return collect($this->table['columns'])
                ->map(fn ($column) => data_get($row, $column))
                ->all();
        });
    }

    /**
     * Rows 1-3 are the title / filter lines, the table header is row 4.
     */
    public function headings(): array
    {
        return [
            [$this->title],
            [implode('   |   ', $this->infoLines)],
            [],
            $this->table['headings'],
        ];
    }

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $columnCount = count($this->table['columns']);
                $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
                $firstDataRow = self::HEADER_ROW + 1;
                $lastDataRow = self::HEADER_ROW + $this->rows->count();

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setItalic(true);
                $sheet->getStyle('A' . self::HEADER_ROW . ':' . $lastColumn . self::HEADER_ROW)
                    ->getFont()->setBold(true);
                $sheet->getStyle('A' . self::HEADER_ROW . ':' . $lastColumn . self::HEADER_ROW)
                    ->getFill()->setFillType('solid')->getStartColor()->setRGB('E2E8F0');

                $sheet->freezePane('A' . $firstDataRow);

                if ($this->rows->isEmpty()) {
                    $sheet->setCellValue('A' . $firstDataRow, 'No records found.');

                    return;
                }

                $sheet->setAutoFilter('A' . self::HEADER_ROW . ':' . $lastColumn . $lastDataRow);

                $moneyFormat = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
                $totalRow = $lastDataRow + 1;
                $hasTotal = false;

                foreach ($this->table['columns'] as $index => $column) {
                    if (!in_array($column, $this->table['money'] ?? [], true)) {
                        continue;
                    }

                    $letter = Coordinate::stringFromColumnIndex($index + 1);

                    $sheet->getStyle($letter . $firstDataRow . ':' . $letter . $totalRow)
                        ->getNumberFormat()->setFormatCode($moneyFormat);

                    if (!empty($this->table['total']) && !in_array($column, $this->table['no_total'] ?? [], true)) {
                        $sheet->setCellValue(
                            $letter . $totalRow,
                            '=SUM(' . $letter . $firstDataRow . ':' . $letter . $lastDataRow . ')'
                        );
                        $hasTotal = true;
                    }
                }

                if ($hasTotal) {
                    $sheet->setCellValue('A' . $totalRow, 'Total (' . $this->rows->count() . ' rows)');
                    $sheet->getStyle('A' . $totalRow . ':' . $lastColumn . $totalRow)->getFont()->setBold(true);
                }
            },
        ];
    }
}
