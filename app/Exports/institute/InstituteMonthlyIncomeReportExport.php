<?php

namespace App\Exports\institute;

use App\Services\InstituteIncomeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstituteMonthlyIncomeReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $year;
    protected $month;

    public function __construct($year, $month)
    {
        $this->year = (int) $year;
        $this->month = (int) $month;
    }

    public function collection()
    {
        $service = app(InstituteIncomeService::class);

        $report = $service->monthlyInstituteIncome(
            $this->year,
            $this->month
        );

        $rows = new Collection();

        /*
         * Main Summary
         */
        $summary = $report['summary'] ?? [];

        $rows->push([
            'section' => 'SUMMARY',
            'name' => 'Monthly Institute Income Report',
            'class' => '',
            'grade' => '',
            'payments' => '',
            'class_fee' => '',
            'hall_fee' => '',
            'total_fee' => '',
            'teacher_income' => '',
            'organizer_income' => '',
            'institution_income' => '',
            'admission_income' => '',
            'extra_income' => '',
            'expense' => '',
            'net_income' => '',
        ]);

        $rows->push([
            'section' => 'SUMMARY',
            'name' => $this->year . '-' . str_pad($this->month, 2, '0', STR_PAD_LEFT),
            'class' => '',
            'grade' => '',
            'payments' => '',
            'class_fee' => $this->number($summary, 'class_fee_income'),
            'hall_fee' => $this->number($summary, 'hall_fee_income'),
            'total_fee' => $this->number($summary, 'total_fee_income'),
            'teacher_income' => $this->number($summary, 'teacher_income'),
            'organizer_income' => $this->number($summary, 'organizer_income'),
            'institution_income' => $this->number($summary, 'institution_income'),
            'admission_income' => $this->number($summary, 'admission_income'),
            'extra_income' => $this->number($summary, 'extra_income'),
            'expense' => $this->number($summary, 'total_expenses'),
            'net_income' => $this->number($summary, 'net_income'),
        ]);

        /*
         * Teacher summaries
         */
        foreach (($report['teacher_summaries'] ?? []) as $teacher) {
            $rows->push([
                'section' => 'TEACHER',
                'name' => $this->value($teacher, 'teacher_name', $this->value($teacher, 'name', '')),
                'class' => '',
                'grade' => '',
                'payments' => $this->number($teacher, 'total_income'),
                'class_fee' => $this->number($teacher, 'class_fee_income'),
                'hall_fee' => $this->number($teacher, 'hall_fee_income'),
                'total_fee' => $this->number($teacher, 'total_fee_income'),
                'teacher_income' => $this->number($teacher, 'teacher_income'),
                'organizer_income' => $this->number($teacher, 'organizer_income'),
                'institution_income' => $this->number($teacher, 'institution_income'),
                'admission_income' => 0,
                'extra_income' => 0,
                'expense' => 0,
                'net_income' => 0,
            ]);
        }

        /*
         * Organizer summaries
         */
        foreach (($report['organizer_summaries'] ?? []) as $organizer) {
            $rows->push([
                'section' => 'ORGANIZER',
                'name' => $this->value($organizer, 'organizer_name', $this->value($organizer, 'name', '')),
                'class' => '',
                'grade' => '',
                'payments' => $this->number($organizer, 'total_income'),
                'class_fee' => $this->number($organizer, 'class_fee_income'),
                'hall_fee' => $this->number($organizer, 'hall_fee_income'),
                'total_fee' => $this->number($organizer, 'total_fee_income'),
                'teacher_income' => $this->number($organizer, 'teacher_income'),
                'organizer_income' => $this->number($organizer, 'organizer_income'),
                'institution_income' => $this->number($organizer, 'institution_income'),
                'admission_income' => 0,
                'extra_income' => 0,
                'expense' => 0,
                'net_income' => 0,
            ]);
        }

        /*
         * Class summaries
         */
        foreach (($report['class_summaries'] ?? []) as $class) {
            $rows->push([
                'section' => 'CLASS',
                'name' => $this->value($class, 'class_name', $this->value($class, 'name', '')),
                'class' => $this->value($class, 'class_name', ''),
                'grade' => $this->value($class, 'grade_name', ''),
                'payments' => $this->number($class, 'total_income'),
                'class_fee' => $this->number($class, 'class_fee_income'),
                'hall_fee' => $this->number($class, 'hall_fee_income'),
                'total_fee' => $this->number($class, 'total_fee_income'),
                'teacher_income' => $this->number($class, 'teacher_income'),
                'organizer_income' => $this->number($class, 'organizer_income'),
                'institution_income' => $this->number($class, 'institution_income'),
                'admission_income' => 0,
                'extra_income' => 0,
                'expense' => 0,
                'net_income' => 0,
            ]);
        }

        /*
         * Admission payments
         */
        foreach (($report['admission_payment_list'] ?? []) as $admission) {
            $amount = $this->number($admission, 'amount');

            $rows->push([
                'section' => 'ADMISSION',
                'name' => $this->value($admission, 'student_name', $this->value($admission, 'name', '')),
                'class' => $this->value($admission, 'class_name', ''),
                'grade' => $this->value($admission, 'grade_name', ''),
                'payments' => $amount,
                'class_fee' => 0,
                'hall_fee' => 0,
                'total_fee' => 0,
                'teacher_income' => 0,
                'organizer_income' => 0,
                'institution_income' => 0,
                'admission_income' => $amount,
                'extra_income' => 0,
                'expense' => 0,
                'net_income' => $amount,
            ]);
        }

        /*
         * Extra incomes
         */
        foreach (($report['extra_income_list'] ?? []) as $income) {
            $amount = $this->number($income, 'amount');

            $rows->push([
                'section' => 'EXTRA INCOME',
                'name' => $this->value($income, 'reason', $this->value($income, 'income_type', '')),
                'class' => '',
                'grade' => '',
                'payments' => $amount,
                'class_fee' => 0,
                'hall_fee' => 0,
                'total_fee' => 0,
                'teacher_income' => 0,
                'organizer_income' => 0,
                'institution_income' => 0,
                'admission_income' => 0,
                'extra_income' => $amount,
                'expense' => 0,
                'net_income' => $amount,
            ]);
        }

        /*
         * Expenses
         */
        foreach (($report['expense_list'] ?? []) as $expense) {
            $amount = $this->number($expense, 'amount');

            $rows->push([
                'section' => 'EXPENSE',
                'name' => $this->value($expense, 'reason', ''),
                'class' => '',
                'grade' => '',
                'payments' => 0,
                'class_fee' => 0,
                'hall_fee' => 0,
                'total_fee' => 0,
                'teacher_income' => 0,
                'organizer_income' => 0,
                'institution_income' => 0,
                'admission_income' => 0,
                'extra_income' => 0,
                'expense' => $amount,
                'net_income' => -$amount,
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Section',
            'Name / Description',
            'Class',
            'Grade',
            'Payments',
            'Class Fee',
            'Hall Fee',
            'Total Fee',
            'Teacher Income',
            'Organizer Income',
            'Institute Income',
            'Admission Income',
            'Extra Income',
            'Expense',
            'Net Income',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }

    private function value($data, $key, $default = '')
    {
        if (is_array($data)) {
            return isset($data[$key]) ? $data[$key] : $default;
        }

        if (is_object($data)) {
            return isset($data->{$key}) ? $data->{$key} : $default;
        }

        return $default;
    }

    private function number($data, $key)
    {
        return (float) $this->value($data, $key, 0);
    }
}
