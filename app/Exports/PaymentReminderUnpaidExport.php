<?php

namespace App\Exports;

use App\Services\PaymentReminderService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentReminderUnpaidExport implements FromCollection, WithHeadings
{
    protected $classId;
    protected $categoryFeeId;
    protected $paymentMonth;

    public function __construct(
        int $classId,
        int $categoryFeeId,
        string $paymentMonth
    ) {
        $this->classId = $classId;
        $this->categoryFeeId = $categoryFeeId;
        $this->paymentMonth = $paymentMonth;
    }

    public function collection(): Collection
    {
        $service = app(PaymentReminderService::class);

        $students = $service->getUnpaidEnrollments(
            $this->classId,
            $this->categoryFeeId,
            $this->paymentMonth
        );

        return $students->map(function ($item) {

            $student = $item['student'];

            $feeOption = isset($item['fee_option'])
                ? $item['fee_option']
                : null;

            $finalFee = isset($item['final_fee'])
                ? (float) $item['final_fee']
                : (float) $item['expected_fee'];

            $paidAmount = (float) $item['paid_amount'];

            $balance = isset($item['balance'])
                ? (float) $item['balance']
                : max($finalFee - $paidAmount, 0);

            return [
                'student_name' =>
                    $student->full_name
                    ?: $student->initial_name,

                'student_id' =>
                    $student->custom_id,

                'mobile' =>
                    $student->mobile
                    ?: 'N/A',

                'whatsapp_mobile' =>
                    $student->whatsapp_mobile
                    ?: 'N/A',

                'email' =>
                    $student->email
                    ?: 'N/A',

                'guardian_mobile' =>
                    $student->guardian_mobile
                    ?: 'N/A',

                'fee_option' =>
                    $feeOption
                        ? ($feeOption['label'] ?? 'N/A')
                        : 'N/A',

                'fee_option_fee' =>
                    $feeOption
                        ? (float) ($feeOption['fee'] ?? 0)
                        : 0,

                'final_fee' =>
                    $finalFee,

                'paid_amount' =>
                    $paidAmount,

                'balance' =>
                    $balance,

                'status' =>
                    'NOT PAID',

                'is_free_card' =>
                    !empty($item['is_free_card'])
                        ? 'Yes'
                        : 'No',

                'attendance_count' =>
                    $item['attendance_count'],

                'payment_month' =>
                    $item['payment_month'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Student ID',
            'Mobile',
            'WhatsApp',
            'Email',
            'Guardian Mobile',
            'Fee Option',
            'Fee Option Fee (Rs.)',
            'Final Fee (Rs.)',
            'Paid Amount (Rs.)',
            'Balance (Rs.)',
            'Status',
            'Free Card',
            'Attendance Count',
            'Payment Month',
        ];
    }
}
