<?php

namespace App\Exports;

use App\Services\PaymentCollectionReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentCollectionReportExport implements FromArray, WithStyles
{
    protected $paymentMonth;

    protected $report;

    public function __construct($paymentMonth)
    {
        $this->paymentMonth = $paymentMonth;

        $service = app(PaymentCollectionReportService::class);

        $this->report = $service->generate(
            $this->paymentMonth
        );
    }

    /**
     * Build Excel data
     */
    public function array(): array
    {
        $rows = [];

        /*
        |--------------------------------------------------------------------------
        | Report Title
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            'PAYMENT COLLECTION REPORT'
        ];


        /*
        |--------------------------------------------------------------------------
        | Year / Month
        |--------------------------------------------------------------------------
        */

        $month = Carbon::parse($this->paymentMonth);

        $rows[] = [
            'Year',
            $month->format('Y'),
            'Month',
            $month->format('F'),
        ];


        /*
        |--------------------------------------------------------------------------
        | Institute Summary
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            'INSTITUTE SUMMARY'
        ];

        $rows[] = [
            'Students',
            $this->report['summary']['student_count'],

            'Expected',
            $this->report['summary']['expected'],

            'Collected',
            $this->report['summary']['collected'],

            'Due',
            $this->report['summary']['due'],

            'Collection %',
            $this->report['summary']['collection_percentage'],

            'Due %',
            $this->report['summary']['due_percentage'],
        ];


        /*
        |--------------------------------------------------------------------------
        | Financial Split
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            'Teacher',
            $this->report['summary']['teacher_amount'],

            'Organizer',
            $this->report['summary']['organizer_amount'],

            'Institute',
            $this->report['summary']['institution_amount'],
        ];


        /*
        |--------------------------------------------------------------------------
        | Empty Row
        |--------------------------------------------------------------------------
        */

        $rows[] = [];


        /*
        |--------------------------------------------------------------------------
        | Table Heading
        |--------------------------------------------------------------------------
        |
        | Class
        | Grade
        | Category
        | Fee Options
        | Students
        | Expected
        | Collected
        | Due
        | Collection %
        | Due %
        | Teacher
        | Organizer
        | Institute
        |
        */

        $rows[] = [
            'Class',
            'Grade',
            'Category',
            'Fee Options',
            'Students',
            'Expected (Rs.)',
            'Collected (Rs.)',
            'Due (Rs.)',
            'Collection %',
            'Due %',
            'Teacher (Rs.)',
            'Organizer (Rs.)',
            'Institute (Rs.)',
        ];


        /*
        |--------------------------------------------------------------------------
        | Report Rows
        |--------------------------------------------------------------------------
        */

        foreach ($this->report['rows'] as $row) {

            /*
            |--------------------------------------------------------------------------
            | Format Fee Options
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | Theory Only - Rs. 2500.00
            | Special - Rs. 2000.00
            |
            */

            $feeOptions = [];

            if (
                isset($row['fee_options']) &&
                is_array($row['fee_options'])
            ) {
                foreach ($row['fee_options'] as $option) {

                    $label = isset($option['label'])
                        ? $option['label']
                        : 'Unknown Option';

                    $fee = isset($option['fee'])
                        ? (float) $option['fee']
                        : 0;

                    $feeOptions[] =
                        $label .
                        ' - Rs. ' .
                        number_format($fee, 2);
                }
            }

            $feeOptionsText = implode(
                "\n",
                $feeOptions
            );


            $rows[] = [
                $row['class_name'],

                isset($row['grade_name'])
                    ? $row['grade_name']
                    : 'Unknown Grade',

                $row['category_name'],

                $feeOptionsText,

                $row['student_count'],

                $row['expected'],
                $row['collected'],
                $row['due'],

                $row['collection_percentage'],
                $row['due_percentage'],

                $row['teacher_amount'],
                $row['organizer_amount'],
                $row['institution_amount'],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Total
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            'TOTAL',
            '',
            '',
            '',

            $this->report['summary']['student_count'],

            $this->report['summary']['expected'],
            $this->report['summary']['collected'],
            $this->report['summary']['due'],

            $this->report['summary']['collection_percentage'],
            $this->report['summary']['due_percentage'],

            $this->report['summary']['teacher_amount'],
            $this->report['summary']['organizer_amount'],
            $this->report['summary']['institution_amount'],
        ];

        return $rows;
    }


    /**
     * Excel styling
     */
    public function styles(Worksheet $sheet)
    {
        /*
        |--------------------------------------------------------------------------
        | Title
        |--------------------------------------------------------------------------
        */

        $sheet->mergeCells('A1:M1');

        $sheet->getStyle('A1')
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A1')
            ->getFont()
            ->setSize(16);


        /*
        |--------------------------------------------------------------------------
        | Year / Month
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A2:D2')
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | Summary Title
        |--------------------------------------------------------------------------
        */

        $sheet->mergeCells('A3:M3');

        $sheet->getStyle('A3')
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A4:M5')
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | Table Header
        |--------------------------------------------------------------------------
        |
        | Row 7
        |
        */

        $sheet->getStyle('A7:M7')
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | Total Row
        |--------------------------------------------------------------------------
        */

        $totalRow = count($this->report['rows']) + 8;

        $sheet->getStyle(
            "A{$totalRow}:M{$totalRow}"
        )
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | Number Formatting
        |--------------------------------------------------------------------------
        */

        $dataStartRow = 8;
        $dataEndRow = $totalRow;


        /*
        | Money
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle(
            "F{$dataStartRow}:H{$dataEndRow}"
        )
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle(
            "K{$dataStartRow}:M{$dataEndRow}"
        )
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');


        /*
        |--------------------------------------------------------------------------
        | Percentages
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle(
            "I{$dataStartRow}:J{$dataEndRow}"
        )
            ->getNumberFormat()
            ->setFormatCode('0.00');


        /*
        |--------------------------------------------------------------------------
        | Fee Options
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle(
            "D{$dataStartRow}:D{$dataEndRow}"
        )
            ->getAlignment()
            ->setWrapText(true);


        /*
        |--------------------------------------------------------------------------
        | Column Width
        |--------------------------------------------------------------------------
        */

        foreach (range('A', 'M') as $column) {

            $sheet
                ->getColumnDimension($column)
                ->setAutoSize(true);
        }


        /*
        |--------------------------------------------------------------------------
        | Better widths
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getColumnDimension('A')
            ->setWidth(25);

        $sheet
            ->getColumnDimension('B')
            ->setWidth(18);

        $sheet
            ->getColumnDimension('C')
            ->setWidth(18);

        $sheet
            ->getColumnDimension('D')
            ->setWidth(32);


        /*
        |--------------------------------------------------------------------------
        | Freeze Header
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane('A8');
    }
}