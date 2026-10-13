<?php

namespace App\Exports\Web\Receipts;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReceiptsExport implements
    FromCollection,
    WithMapping,
    WithHeadings,
    WithColumnFormatting,
    WithStyles,
    WithEvents,
    WithTitle,
    ShouldAutoSize
{
    /**
     * Column letter of "Amount" (used for number format and the total row).
     */
    private const AMOUNT_COLUMN = 'K';

    protected Collection $receipts;

    public function __construct(Collection $receipts)
    {
        $this->receipts = $receipts->values();
    }

    public function collection(): Collection
    {
        return $this->receipts;
    }

    /**
     * One receipt -> one Excel row.
     *
     * @param array $receipt
     */
    public function map($receipt): array
    {
        return [
            $receipt['receipt_number'] ?? '-',
            $receipt['type'] ?? '-',
            $receipt['student_id'] ?? 'N/A',
            $receipt['student_name'] ?? 'N/A',
            $receipt['grade'] ?? 'N/A',
            $receipt['category'] ?? 'N/A',
            $receipt['class_name'] ?? 'N/A',
            $receipt['teacher_name'] ?? 'N/A',
            $this->formatDate($receipt['payment_month'] ?? null, 'F Y'),
            $this->formatDate($receipt['paid_at'] ?? null, 'd-m-Y h:i A'),

            // Keep as a number so Excel can SUM / filter it
            round((float) ($receipt['amount'] ?? 0), 2),

            $this->formatDate($receipt['date'] ?? null, 'd-m-Y h:i A'),
            $receipt['status'] ?? 'Active',
            $receipt['deleted_by'] ?? '',
            $receipt['delete_reason'] ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Receipt Number',
            'Receipt Type',
            'Student ID',
            'Student Name',
            'Grade',
            'Category',
            'Class',
            'Teacher',
            'Payment Month',
            'Paid At',
            'Amount (Rs.)',
            'Receipt Date',
            'Status',
            'Deleted By',
            'Delete Reason',
        ];
    }

    public function columnFormats(): array
    {
        return [
            self::AMOUNT_COLUMN => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // 1,234.00
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '0F172A'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Receipts';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastDataRow = $this->receipts->count() + 1;
                $totalRow = $lastDataRow + 2;

                // Keep the header visible while scrolling + filter buttons
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:O' . max($lastDataRow, 1));

                // Highlight deleted rows
                foreach ($this->receipts->values() as $index => $receipt) {
                    if (($receipt['status'] ?? 'Active') !== 'Active') {
                        $row = $index + 2;

                        $sheet->getStyle("A{$row}:O{$row}")->applyFromArray([
                            'font' => ['color' => ['rgb' => '991B1B']],
                            'fill' => [
                                'fillType' => 'solid',
                                'startColor' => ['rgb' => 'FEF2F2'],
                            ],
                        ]);
                    }
                }

                // Active total (deleted receipts are not counted)
                $activeTotal = round(
                    (float) $this->receipts->where('status', 'Active')->sum('amount'),
                    2
                );

                $sheet->setCellValue('J' . $totalRow, 'Active Total');
                $sheet->setCellValue(self::AMOUNT_COLUMN . $totalRow, $activeTotal);

                $sheet->getStyle('J' . $totalRow . ':' . self::AMOUNT_COLUMN . $totalRow)
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle(self::AMOUNT_COLUMN . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            },
        ];
    }

    /**
     * Carbon / DateTime / string / null -> formatted string.
     */
    private function formatDate($value, string $format): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}