<?php

namespace App\Services\Web\Payment;

use App\Models\Payment;
use Illuminate\Support\Collection;

class ReceiptService
{
    /**
     * @var PaymentDataBuilder
     */
    protected $dataBuilder;

    /**
     * ReceiptService constructor.
     *
     * PHP 7.4 compatible.
     */
    public function __construct(
        PaymentDataBuilder $dataBuilder
    ) {
        $this->dataBuilder = $dataBuilder;
    }

    /**
     * Build receipt data for one payment.
     *
     * This response is only for receipt printing.
     */
    public function single(Payment $payment): array
    {
        $payment->loadMissing([
            'student.grade',

            'enrollment.studentClass.teacher',
            'enrollment.studentClass.subject',
            'enrollment.studentClass.grade',

            'enrollment.classCategoryFee.category',
            'enrollment.classCategoryFeeOption',
        ]);

        $paymentData = $this->dataBuilder
            ->payment($payment);

        $classFee = isset($paymentData['fee']['class_fee'])
            ? (float) $paymentData['fee']['class_fee']
            : 0;

        $classRow = $this->buildClassRow(
            $paymentData,
            $payment
        );

        return [
            'student_details' => $this->buildStudentDetails(
                $payment->student
            ),

            'classes' => [
                $classRow,
            ],

            'total' => round($classFee, 2),

            /*
            | Extra fields for the web receipt (mobile ignores them).
            */
            'payment_date' => $this->formatDate($payment->paid_at),
            'payment_time' => $this->formatTime($payment->paid_at),
            'summary' => $this->buildSummary([$classRow]),

            'payment_method' => $payment->payment_method,
        ];
    }

    /**
     * Build ONE receipt for multiple payments.
     *
     * This is the main response used by mobile bulk payment.
     */
    public function bulk(Collection $payments): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validate Collection
        |--------------------------------------------------------------------------
        */

        if ($payments->isEmpty()) {
            throw new \InvalidArgumentException(
                'No payments available for receipt.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Get Payment IDs
        |--------------------------------------------------------------------------
        */

        $paymentIds = $payments
            ->pluck('id')
            ->filter()
            ->values();

        if ($paymentIds->isEmpty()) {
            throw new \InvalidArgumentException(
                'No valid payment IDs available for receipt.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Reload Payments With Required Relationships
        |--------------------------------------------------------------------------
        */

        $payments = Payment::query()
            ->whereIn('id', $paymentIds)
            ->with([
                'student.grade',

                'enrollment.studentClass.teacher',
                'enrollment.studentClass.subject',
                'enrollment.studentClass.grade',

                'enrollment.classCategoryFee.category',
                'enrollment.classCategoryFeeOption',
            ])
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            throw new \RuntimeException(
                'Payment records not found for receipt.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. First Payment / Student
        |--------------------------------------------------------------------------
        */

        $firstPayment = $payments->first();

        $student = $firstPayment->student;

        if (!$student) {
            throw new \RuntimeException(
                'Student not found for receipt.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Validate Same Student
        |--------------------------------------------------------------------------
        */

        foreach ($payments as $payment) {
            if (
                (int) $payment->student_id !==
                (int) $student->id
            ) {
                throw new \InvalidArgumentException(
                    'All payments in a bulk receipt must belong to the same student.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Build Class Rows
        |--------------------------------------------------------------------------
        */

        $classRows = [];

        $total = 0;

        foreach ($payments as $payment) {

            $paymentData = $this->dataBuilder
                ->payment($payment);

            /*
            |--------------------------------------------------------------------------
            | Class Fee
            |--------------------------------------------------------------------------
            |
            | This is the final fee selected from the Fee Option.
            |
            */

            $classFee = isset($paymentData['fee']['class_fee'])
                ? (float) $paymentData['fee']['class_fee']
                : 0;

            /*
            |--------------------------------------------------------------------------
            | Add Class Row
            |--------------------------------------------------------------------------
            */

            $classRows[] = $this->buildClassRow(
                $paymentData,
                $payment
            );

            /*
            |--------------------------------------------------------------------------
            | Add To Total
            |--------------------------------------------------------------------------
            */

            $total += $classFee;
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Payment Method
        |--------------------------------------------------------------------------
        |
        | Bulk payment uses the same payment method.
        |
        */

        $paymentMethod = $firstPayment->payment_method;

        /*
        |--------------------------------------------------------------------------
        | 8. Final Receipt Response
        |--------------------------------------------------------------------------
        */

        return [
            'student_details' => $this->buildStudentDetails(
                $student
            ),

            'classes' => $classRows,

            'total' => round($total, 2),

            /*
            | Extra fields for the web receipt (mobile ignores them).
            */
            'payment_date' => $this->formatDate($firstPayment->paid_at),
            'payment_time' => $this->formatTime($firstPayment->paid_at),
            'summary' => $this->buildSummary($classRows),

            'payment_method' => $paymentMethod,

            'payment_count' => $payments->count(),
        ];
    }

    /**
     * Build student details for receipt.
     */
    /**
     * Totals for the receipt footer (class fee, hall fee, paid).
     */
    private function buildSummary(array $classRows): array
    {
        $sum = function (string $key) use ($classRows): float {
            return round(array_sum(array_column($classRows, $key)), 2);
        };

        return [
            'total_class_fee' => $sum('class_fee'),
            'total_hall_fee' => $sum('hall_fee'),
            'total_final_fee' => $sum('total_fee'),
            'total_discount' => $sum('discount_amount'),
            'total_paid' => $sum('paid_amount'),
        ];
    }

    private function buildStudentDetails($student): array
    {
        return [
            'student_id' => $student->id,

            'name' => $student->initial_name
                ?: $student->full_name,

            'custom_id' => $student->custom_id,
        ];
    }

    /**
     * Build one class row for receipt.
     */
    private function buildClassRow(
        array $paymentData,
        Payment $payment
    ): array {
        $class = isset($paymentData['class'])
            ? $paymentData['class']
            : [];

        $category = isset($paymentData['category'])
            ? $paymentData['category']
            : [];

        /*
        |--------------------------------------------------------------------------
        | Fee Option
        |--------------------------------------------------------------------------
        */

        $feeOptionName = null;

        if (isset($paymentData['fee_option'])) {

            if (is_array($paymentData['fee_option'])) {

                if (isset($paymentData['fee_option']['name'])) {
                    $feeOptionName = $paymentData['fee_option']['name'];
                }

                if (
                    $feeOptionName === null &&
                    isset($paymentData['fee_option']['label'])
                ) {
                    $feeOptionName = $paymentData['fee_option']['label'];
                }
            } elseif (is_string($paymentData['fee_option'])) {
                $feeOptionName = $paymentData['fee_option'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Class Fee
        |--------------------------------------------------------------------------
        */

        $classFee = isset($paymentData['fee']['class_fee'])
            ? (float) $paymentData['fee']['class_fee']
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Receipt Number
        |--------------------------------------------------------------------------
        */

        $receiptNo = isset($paymentData['receipt_number'])
            ? $paymentData['receipt_number']
            : $payment->receipt_number;

        /*
        |--------------------------------------------------------------------------
        | Teacher
        |--------------------------------------------------------------------------
        */

        $teacherName = isset($class['teacher_name'])
            ? $class['teacher_name']
            : (
                isset($class['teacher_initials'])
                ? $class['teacher_initials']
                : null
            );

        /*
        |--------------------------------------------------------------------------
        | Grade
        |--------------------------------------------------------------------------
        */

        $gradeName = isset($class['grade'])
            ? $class['grade']
            : null;

        /*
        |--------------------------------------------------------------------------
        | Final Class Response
        |--------------------------------------------------------------------------
        */

        return [
            'class_name' => isset($class['class_name'])
                ? $class['class_name']
                : null,

            'grade' => $gradeName,

            'teacher_name' => $teacherName,

            'category_name' => isset($category['name'])
                ? $category['name']
                : null,

            'fee_option_name' => $feeOptionName,

            'class_fee' => round($classFee, 2),

            'receipt_no' => $receiptNo,

            /*
            | Extra fields for the web receipt (mobile ignores them).
            */
            'hall_fee' => round((float) ($paymentData['fee']['hall_fee'] ?? 0), 2),
            'total_fee' => round((float) ($paymentData['fee']['total_fee'] ?? $classFee), 2),
            'discount_amount' => round((float) ($paymentData['fee']['discount_amount'] ?? 0), 2),
            'paid_amount' => round((float) ($paymentData['fee']['paid_amount'] ?? $payment->amount), 2),
            'payment_month' => $payment->payment_month
                ? \Illuminate\Support\Carbon::parse($payment->payment_month)->format('Y F')
                : null,
        ];
    }

    /**
     * Format DateTime.
     *
     * Kept for compatibility if used elsewhere.
     */
    private function formatDateTime(
        $dateTime
    ): string {
        if (!$dateTime) {
            return '';
        }

        return $dateTime->format(
            'Y-m-d H:i:s'
        );
    }

    /**
     * Format Date.
     *
     * Kept for compatibility if used elsewhere.
     */
    private function formatDate(
        $dateTime
    ): string {
        if (!$dateTime) {
            return '';
        }

        return $dateTime->format(
            'd/m/Y'
        );
    }

    /**
     * Format Time.
     *
     * Kept for compatibility if used elsewhere.
     */
    private function formatTime(
        $dateTime
    ): string {
        if (!$dateTime) {
            return '';
        }

        return $dateTime->format(
            'h:i A'
        );
    }
}
