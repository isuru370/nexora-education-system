<?php

namespace App\Exports;

use App\Services\MonthlyClassAttendanceReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyClassAttendanceReportExport implements FromArray, WithStyles
{
    protected string $month;
    protected array $report;

    public function __construct(string $month)
    {
        $this->month = $month;

        $service = app(MonthlyClassAttendanceReportService::class);

        $this->report = $service->generate($this->month);
    }

    /**
     * Excel data
     */
    public function array(): array
    {
        $rows = [];

        /*
        |--------------------------------------------------------------------------
        | Report Header
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            'MONTHLY CLASS ATTENDANCE REPORT'
        ];

        $rows[] = [
            'Month',
            Carbon::parse($this->month)->format('F Y')
        ];

        $rows[] = [];

        /*
        |--------------------------------------------------------------------------
        | Overall Summary
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            'INSTITUTE SUMMARY'
        ];

        $rows[] = [
            'Total Classes',
            $this->report['summary']['total_classes'],

            'Total Students',
            $this->report['summary']['total_students'],

            'New Students',
            $this->report['summary']['new_students'],

            'Attendance Rate',
            $this->report['summary']['attendance_rate'] . '%',
        ];

        $rows[] = [
            'Total Attended',
            $this->report['summary']['total_attended'],

            'Total Absent',
            $this->report['summary']['total_absent'],
        ];

        $rows[] = [];

        /*
        |--------------------------------------------------------------------------
        | Student Attendance Report
        |--------------------------------------------------------------------------
        */

        $rows[] = [
            'Grade',
            'Class',
            'Teacher',
            'Category',
            'Student',
            'Student ID',
            'Class Days',
            'Attended',
            'Absent',
            'Attendance %',
            'New Student',
            'Enrolled Date',
        ];

        /*
        |--------------------------------------------------------------------------
        | Classes
        |--------------------------------------------------------------------------
        */

        foreach ($this->report['classes'] as $class) {

            foreach ($class['categories'] as $category) {

                foreach ($category['students'] as $student) {

                    $rows[] = [
                        $class['grade_name'] ?? 'N/A',

                        $class['class_name'] ?? 'N/A',

                        $class['teacher_name'] ?? 'N/A',

                        $category['category_name'] ?? 'N/A',

                        $student['student_name'] ?? 'N/A',

                        $student['student_custom_id'] ?? 'N/A',

                        $student['class_days'] ?? 0,

                        $student['attended'] ?? 0,

                        $student['absent'] ?? 0,

                        ($student['attendance_percentage'] ?? 0) . '%',

                        !empty($student['is_new_student'])
                            ? 'YES'
                            : 'NO',

                        $student['enrolled_at'] ?? '',
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * Excel styles
     */
    public function styles(Worksheet $sheet)
    {
        /*
        |--------------------------------------------------------------------------
        | Title
        |--------------------------------------------------------------------------
        */

        $sheet->mergeCells('A1:L1');

        $sheet->getStyle('A1')
            ->getFont()
            ->setBold(true)
            ->setSize(16);

        /*
        |--------------------------------------------------------------------------
        | Month
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A2:B2')
            ->getFont()
            ->setBold(true);

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $sheet->mergeCells('A4:L4');

        $sheet->getStyle('A4')
            ->getFont()
            ->setBold(true)
            ->setSize(13);

        $sheet->getStyle('A5:L6')
            ->getFont()
            ->setBold(true);

        /*
        |--------------------------------------------------------------------------
        | Table Header
        |--------------------------------------------------------------------------
        |
        | Row 8
        |
        */

        $sheet->getStyle('A8:L8')
            ->getFont()
            ->setBold(true);

        /*
        |--------------------------------------------------------------------------
        | Calculate last row
        |--------------------------------------------------------------------------
        */

        $lastRow = 8;

        foreach ($this->report['classes'] as $class) {
            foreach ($class['categories'] as $category) {
                $lastRow += count($category['students']);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Number Formats
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle("G9:I{$lastRow}")
            ->getNumberFormat()
            ->setFormatCode('0');

        /*
        |--------------------------------------------------------------------------
        | Column Width
        |--------------------------------------------------------------------------
        */

        $widths = [
            'A' => 15,
            'B' => 28,
            'C' => 25,
            'D' => 18,
            'E' => 30,
            'F' => 15,
            'G' => 12,
            'H' => 12,
            'I' => 12,
            'J' => 15,
            'K' => 15,
            'L' => 15,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)
                ->setWidth($width);
        }

        /*
        |--------------------------------------------------------------------------
        | Freeze Header
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane('A9');

        /*
        |--------------------------------------------------------------------------
        | Auto Filter
        |--------------------------------------------------------------------------
        */

        if ($lastRow >= 8) {
            $sheet->setAutoFilter("A8:L{$lastRow}");
        }
    }
}