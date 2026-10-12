<?php

namespace App\Exports\institute;

use App\Models\ExtraIncome;
use App\Models\InstitutePayment;
use App\Models\PaymentSplitSnapshot;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InstituteFinancialReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $rows = new Collection();

        $snapshotPayments = $this->buildSnapshotQuery()
            ->with([
                'studentClass',
                'teacher',
                'organizer',
                'createdBy',
            ])
            ->orderBy('payment_date', 'asc')
            ->get();

        $extraIncomes = $this->buildExtraIncomeQuery()
            ->with('createdBy')
            ->orderBy('income_date', 'asc')
            ->get();

        $expenses = $this->buildExpenseQuery()
            ->with('createdBy')
            ->orderBy('payment_date', 'asc')
            ->get();

        /*
         * Student / class payment rows.
         *
         * institution_amount already contains:
         * institution class share + hall fee.
         */
        foreach ($snapshotPayments as $snapshot) {
            $rows->push([
                'record_type' => 'Student Payment',
                'date' => $snapshot->payment_date
                    ? $snapshot->payment_date->format('Y-m-d H:i:s')
                    : '',
                'reference' => $snapshot->payment_id
                    ? 'PAY-' . str_pad($snapshot->payment_id, 6, '0', STR_PAD_LEFT)
                    : '',
                'class' => optional($snapshot->studentClass)->class_name ?: '',
                'teacher' => optional($snapshot->teacher)->full_name ?: '',
                'organizer' => optional($snapshot->organizer)->name ?: '',

                'class_fee' => (float) $snapshot->class_fee,
                'hall_fee' => (float) $snapshot->hall_fee,
                'total_fee' => (float) $snapshot->total_fee,
                'payment_amount' => (float) $snapshot->payment_amount,

                'teacher_percentage' => (float) $snapshot->teacher_percentage,
                'teacher_amount' => (float) $snapshot->teacher_amount,

                'organizer_percentage' => (float) $snapshot->organizer_percentage,
                'organizer_amount' => (float) $snapshot->organizer_amount,

                'institution_percentage' => (float) $snapshot->institution_percentage,
                'institution_amount' => (float) $snapshot->institution_amount,

                'extra_income' => 0,
                'expense' => 0,

                'status' => 'RECEIVED',
                'created_by' => optional($snapshot->createdBy)->name ?: '',
            ]);
        }

        foreach ($extraIncomes as $income) {
            $rows->push([
                'record_type' => 'Extra Income',
                'date' => $income->income_date
                    ? $income->income_date->format('Y-m-d')
                    : '',
                'reference' => 'EXTRA-' . $income->id,
                'class' => '',
                'teacher' => '',
                'organizer' => '',

                'class_fee' => 0,
                'hall_fee' => 0,
                'total_fee' => 0,
                'payment_amount' => 0,

                'teacher_percentage' => 0,
                'teacher_amount' => 0,

                'organizer_percentage' => 0,
                'organizer_amount' => 0,

                'institution_percentage' => 100,
                'institution_amount' => (float) $income->amount,

                'extra_income' => (float) $income->amount,
                'expense' => 0,

                'status' => 'RECEIVED',
                'created_by' => optional($income->createdBy)->name ?: '',
            ]);
        }

        foreach ($expenses as $expense) {
            $rows->push([
                'record_type' => 'Expense',
                'date' => $expense->payment_date
                    ? $expense->payment_date->format('Y-m-d')
                    : '',
                'reference' => 'EXP-' . $expense->id,
                'class' => '',
                'teacher' => '',
                'organizer' => '',

                'class_fee' => 0,
                'hall_fee' => 0,
                'total_fee' => 0,
                'payment_amount' => 0,

                'teacher_percentage' => 0,
                'teacher_amount' => 0,

                'organizer_percentage' => 0,
                'organizer_amount' => 0,

                'institution_percentage' => 0,
                'institution_amount' => 0,

                'extra_income' => 0,
                'expense' => (float) $expense->amount,

                'status' => 'PAID',
                'created_by' => optional($expense->createdBy)->name ?: '',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Record Type',
            'Date',
            'Reference',
            'Class',
            'Teacher',
            'Organizer',

            'Class Fee',
            'Hall Fee',
            'Total Fee',
            'Payment Amount',

            'Teacher %',
            'Teacher Amount',

            'Organizer %',
            'Organizer Amount',

            'Institution %',
            'Institution Amount',

            'Extra Income',
            'Expense',

            'Status',
            'Created By',
        ];
    }

    private function buildSnapshotQuery()
    {
        $query = PaymentSplitSnapshot::query();

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('payment_date', [
                $this->filters['start_date'],
                $this->filters['end_date'],
            ]);
        }

        return $query;
    }

    private function buildExtraIncomeQuery()
    {
        $query = ExtraIncome::query()
            ->where('status', 'received');

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('income_date', [
                $this->filters['start_date'],
                $this->filters['end_date'],
            ]);
        }

        return $query;
    }

    private function buildExpenseQuery()
    {
        $query = InstitutePayment::query()
            ->where('payment_type', 'expense')
            ->where('status', 'paid');

        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $query->whereBetween('payment_date', [
                $this->filters['start_date'],
                $this->filters['end_date'],
            ]);
        }

        return $query;
    }
}
