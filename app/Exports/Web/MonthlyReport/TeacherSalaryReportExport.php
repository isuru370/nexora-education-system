<?php

namespace App\Exports\Web\MonthlyReport;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Teacher salary summary for one branch and month.
 * Rows come from MonthlyReportService::teacherSalaryReport().
 */
class TeacherSalaryReportExport implements
    FromArray,
    WithHeadings,
    WithColumnFormatting,
    WithStyles,
    WithEvents,
    WithTitle,
    WithStrictNullComparison,
    ShouldAutoSize
{
    public function __construct(
        protected array $rows,
        protected int $year,
        protected int $month,
        protected ?string $branchName = null
    ) {}

    public function array(): array
    {
        return array_map(fn (array $row) => [
            $row['custom_id'] ?? '',
            $row['full_name'] ?? '',
            $row['initials'] ?? '',
            (float) ($row['gross_income'] ?? 0),
            (float) ($row['advance'] ?? 0),
            (float) ($row['deduction'] ?? 0),
            (float) ($row['other'] ?? 0),
            (float) ($row['advance_deduction'] ?? 0),
            (float) ($row['net_salary'] ?? 0),
            ucfirst($row['salary_paid_status'] ?? 'pending'),
        ], $this->rows);
    }

    public function headings(): array
    {
        return [
            'Teacher Code',
            'Teacher Name',
            'Initials',
            'Gross Income',
            'Advance',
            'Deduction',
            'Other',
            'Total Deductions',
            'Net Salary',
            'Status',
        ];
    }

    public function columnFormats(): array
    {
        $money = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

        return [
            'D' => $money,
            'E' => $money,
            'F' => $money,
            'G' => $money,
            'H' => $money,
            'I' => $money,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('M Y') . ' Salary';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = count($this->rows) + 1;
                $totalRow = $lastDataRow + 2;

                $sheet->freezePane('A2');

                if (count($this->rows) > 0) {
                    $sheet->setAutoFilter('A1:J' . $lastDataRow);

                    $sheet->setCellValue('C' . $totalRow, 'Total');

                    foreach (['D', 'E', 'F', 'G', 'H', 'I'] as $column) {
                        $sheet->setCellValue(
                            $column . $totalRow,
                            '=SUM(' . $column . '2:' . $column . $lastDataRow . ')'
                        );
                        $sheet->getStyle($column . $totalRow)
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    }

                    $sheet->getStyle('C' . $totalRow . ':I' . $totalRow)->getFont()->setBold(true);
                }

                if ($this->branchName) {
                    $infoRow = $totalRow + 1;
                    $sheet->setCellValue('A' . $infoRow, 'Branch: ' . $this->branchName);
                    $sheet->getStyle('A' . $infoRow)->getFont()->setItalic(true);
                }
            },
        ];
    }
}