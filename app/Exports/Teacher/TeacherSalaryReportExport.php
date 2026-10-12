<?php

namespace App\Exports\Teacher;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeacherSalaryReportExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected $report;
    protected $year;
    protected $month;

    public function __construct(array $report, int $year, int $month)
    {
        $this->report = $report;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return [
                $row['teacher_id'] ?? '',
                $row['custom_id'] ?? '',
                $row['initials'] ?? '',
                $row['gross_income'] ?? 0,
                $row['advance_deduction'] ?? 0,
                $row['salary_paid_status'] ?? '',
            ];
        }, $this->report);
    }

    public function headings(): array
    {
        return [
            'Teacher ID',
            'Custom ID',
            'Initials',
            'Gross Income',
            'Advance Deduction',
            'Salary Status',
        ];
    }
}