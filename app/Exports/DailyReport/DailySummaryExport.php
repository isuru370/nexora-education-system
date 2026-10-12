<?php

namespace App\Exports\DailyReport;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailySummaryExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $summary;
    protected $date;

    public function __construct(array $summary, string $date)
    {
        $this->summary = $summary;
        $this->date = $date;
    }

    public function array(): array
    {
        $paymentTotal = $this->summary['payment_total'] ?? 0;
        $admissionTotal = $this->summary['admission_total'] ?? 0;
        $extraIncomeTotal = $this->summary['extra_income_total'] ?? 0;

        $teacherExpenseTotal = $this->summary['teacher_expense_total'] ?? 0;
        $organizerExpenseTotal = $this->summary['organizer_expense_total'] ?? 0;
        $instituteExpensesTotal = $this->summary['instituteExpencesTotal'] ?? 0;

        $totalIncome = $paymentTotal + $admissionTotal + $extraIncomeTotal;
        $totalExpenses = $teacherExpenseTotal
            + $organizerExpenseTotal
            + $instituteExpensesTotal;

        return [
            ['Daily Financial Summary Report'],
            ['Date', Carbon::parse($this->date)->format('d F Y')],
            [''],
            ['Income Summary'],
            ['Student Payments', number_format($paymentTotal, 2)],
            ['Admission Fees', number_format($admissionTotal, 2)],
            ['Extra Income', number_format($extraIncomeTotal, 2)],
            ['Total Income', number_format($totalIncome, 2)],
            [''],
            ['Expense Summary'],
            ['Teacher Payments', number_format($teacherExpenseTotal, 2)],
            ['Organizer Payments', number_format($organizerExpenseTotal, 2)],
            ['Institute Expenses', number_format($instituteExpensesTotal, 2)],
            ['Total Expenses', number_format($totalExpenses, 2)],
            [''],
            ['NET BALANCE', number_format($this->summary['net_total'] ?? 0, 2)],
            [''],
            ['Generated on: ' . now()->format('d M Y, h:i A')],
        ];
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Daily Summary Report';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true, 'size' => 12]],
            10 => ['font' => ['bold' => true, 'size' => 12]],
            16 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
