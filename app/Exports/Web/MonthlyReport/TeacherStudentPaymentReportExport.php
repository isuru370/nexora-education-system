<?php

namespace App\Exports\Web\MonthlyReport;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
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
 * Teacher's students payment report.
 * Used for both "Fee Month" and "Collection Date" reports
 * (the old TeacherWithStudentPaymentReportExport and
 * TeacherWithStudentPaymentDateReportExport were the same code).
 *
 * Data comes from MonthlyReportService::teacherStudentPaymentReport().
 */
class TeacherStudentPaymentReportExport implements
    FromCollection,
    WithHeadings,
    WithColumnFormatting,
    WithStyles,
    WithEvents,
    WithTitle,
    WithStrictNullComparison,
    ShouldAutoSize
{
    protected int $rowCount = 0;

    public function __construct(
        protected array $report,
        protected int $year,
        protected int $month
    ) {}

    public function collection(): Collection
    {
        $rows = [];

        foreach ($this->report['classes'] as $class) {
            foreach ($class['categories'] as $category) {
                foreach (['paid', 'unpaid', 'freecard', 'nofee'] as $group) {
                    foreach ($category['students'][$group] as $student) {
                        $rows[] = $this->makeRow($class, $category, $student);
                    }
                }
            }
        }

        $this->rowCount = count($rows);

        return new Collection($rows);
    }

    protected function makeRow(array $class, array $category, array $student): array
    {
        $feeOption = $student['fee_option'] ?? null;

        return [
            $class['class_name'] . (($class['is_active'] ?? true) ? '' : ' (Inactive)'),
            $class['grade_name'],
            $category['category_name'],
            $feeOption ? $feeOption['label'] : '',
            $student['paid_for'] ?? '',
            $student['paid_on'] ?? '',
            $student['student_code'],
            $student['initial_name'],
            $student['guardian_mobile'],
            ['paid' => 'Paid', 'unpaid' => 'Unpaid', 'freecard' => 'Free card', 'nofee' => 'No fee'][$student['status']] ?? $student['status'],
            (float) $student['final_fee'],
            (float) $student['paid_amount'],
            (float) $student['balance'],
        ];
    }

    public function headings(): array
    {
        return [
            'Class',
            'Grade',
            'Category',
            'Fee Option',
            'Paid For',
            'Paid On',
            'Student Code',
            'Student Name',
            'Guardian Mobile',
            'Status',
            'Final Fee',
            'Paid Amount',
            'Balance',
        ];
    }

    public function columnFormats(): array
    {
        $money = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

        return [
            'K' => $money,
            'L' => $money,
            'M' => $money,
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
        return Carbon::create($this->year, $this->month, 1)->format('M Y');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = $this->rowCount + 1;
                $infoRow = $lastDataRow + 2;

                $sheet->freezePane('A2');

                if ($this->rowCount > 0) {
                    $sheet->setAutoFilter('A1:M' . $lastDataRow);

                    $sheet->setCellValue('J' . $infoRow, 'Total');
                    foreach (['K', 'L', 'M'] as $column) {
                        $sheet->setCellValue(
                            $column . $infoRow,
                            '=SUM(' . $column . '2:' . $column . $lastDataRow . ')'
                        );
                        $sheet->getStyle($column . $infoRow)
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    }
                    $sheet->getStyle('J' . $infoRow . ':M' . $infoRow)->getFont()->setBold(true);
                }

                $teacher = $this->report['teacher'];
                $basis = $this->report['filter']['basis_label'] ?? '';

                $sheet->setCellValue(
                    'A' . ($infoRow + 1),
                    'Teacher: ' . $teacher['custom_id'] . ' - ' . ($teacher['full_name'] ?? $teacher['initials'])
                    . ' | ' . Carbon::create($this->year, $this->month, 1)->format('F Y')
                    . ($basis ? ' | By ' . $basis : '')
                );
                $sheet->getStyle('A' . ($infoRow + 1))->getFont()->setItalic(true);
            },
        ];
    }
}