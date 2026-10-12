<?php

namespace App\Exports\MonthlyReport;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeacherWithStudentPaymentDateReportExport implements FromCollection, WithHeadings, ShouldAutoSize
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

    public function collection()
    {
        $rows = [];

        foreach ($this->report['classes'] as $class) {

            foreach ($class['categories'] as $category) {

                foreach ($category['students']['paid'] as $student) {
                    $rows[] = $this->makeRow($class, $category, $student);
                }

                foreach ($category['students']['unpaid'] as $student) {
                    $rows[] = $this->makeRow($class, $category, $student);
                }

                foreach ($category['students']['freecard'] as $student) {
                    $rows[] = $this->makeRow($class, $category, $student);
                }
            }
        }

        return new Collection($rows);
    }

    private function makeRow(array $class, array $category, array $student): array
    {
        $feeOption = isset($student['fee_option'])
            ? $student['fee_option']
            : null;

        return [
            'year' => $this->year,

            'month' => $this->month,

            'teacher_id' => $this->report['teacher']['id'],

            'teacher_custom_id' => $this->report['teacher']['custom_id'],

            'teacher_initials' => $this->report['teacher']['initials'],

            'class_name' => $class['class_name'],

            'grade_name' => $class['grade_name'],

            'category_name' => $category['category_name'],

            'fee_option' => $feeOption
                ? $feeOption['label']
                : '',

            'fee_option_fee' => $feeOption
                ? (float) $feeOption['fee']
                : 0,

            'student_code' => $student['student_code'],

            'initial_name' => $student['initial_name'],

            'guardian_mobile' => $student['guardian_mobile'],

            'status' => $student['status'],

            'is_free_card' => !empty($student['is_free_card'])
                ? 'Yes'
                : 'No',

            'final_fee' => (float) $student['final_fee'],

            'paid_amount' => (float) $student['paid_amount'],

            'balance' => (float) $student['balance'],
        ];
    }

    public function headings(): array
    {
        return [
            'Year',
            'Month',
            'Teacher ID',
            'Teacher Code',
            'Teacher Initials',
            'Class',
            'Grade',
            'Category',
            'Fee Option',
            'Fee Option Fee',
            'Student Code',
            'Student Name',
            'Guardian Mobile',
            'Status',
            'Free Card',
            'Final Fee',
            'Paid Amount',
            'Balance',
        ];
    }
}