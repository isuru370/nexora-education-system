<?php

namespace App\Services\Web\Payment;

use App\Models\ClassCategoryFeeOption;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentClassEnrollment;
use App\Services\Web\Receipt\ReceiptNumberService;
use App\Support\PaymentRules;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

class PaymentMarker
{
    public function __construct(
        protected PaymentSplitSnapshotService $splitSnapshotService,
        protected ReceiptNumberService $receiptNumberService,
    ) {}

    private const ALLOWED_MARK_METHODS = [
        'qr_mobile', 'qr_web', 'manual_mobile', 'manual_web',
    ];

    private const ALLOWED_PAYMENT_METHODS = [
        'cash', 'card', 'bank_transfer', 'online', 'cheque', 'other',
    ];

    /**
     * Create one completed payment.
     */
    public function mark(
        Student $student,
        StudentClassEnrollment $enrollment,
        array $data
    ): Payment {
        // 1. Verify enrollment belongs to student
        if ((int) $enrollment->student_id !== (int) $student->id) {
            throw new \RuntimeException(
                'The selected class enrollment does not belong to this student.'
            );
        }

        // 2. Branch ID — from enrollment (never trust user input)
        $branchId = (int) $enrollment->branch_id;

        // 3. Mark method
        $markMethod = $data['mark_method'] ?? null;
        if (!in_array($markMethod, self::ALLOWED_MARK_METHODS, true)) {
            throw new \InvalidArgumentException('Invalid payment mark method.');
        }

        // 4. Payment method
        $paymentMethod = $data['payment_method'] ?? 'cash';
        if (!in_array($paymentMethod, self::ALLOWED_PAYMENT_METHODS, true)) {
            throw new \InvalidArgumentException('Invalid payment method.');
        }

        // 5. Payment month
        if (!isset($data['payment_month']) || trim((string) $data['payment_month']) === '') {
            throw new \InvalidArgumentException('Payment month is required.');
        }

        try {
            $paymentMonth = Carbon::parse($data['payment_month'])->startOfMonth();
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Invalid payment month.');
        }

        // 6. Amount
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        // 7. Discount
        $discountAmount = isset($data['discount_amount']) ? (float) $data['discount_amount'] : 0;
        if ($discountAmount < 0) {
            throw new \InvalidArgumentException('Discount amount cannot be negative.');
        }

        // 8-10. Gross fee (class fee + hall fee)
        $grossFee = $this->grossFee($enrollment);

        // 11. Discount validation
        if ($discountAmount > $grossFee) {
            throw new \InvalidArgumentException(sprintf(
                'Discount amount cannot exceed the total fee of Rs. %.2f.',
                $grossFee
            ));
        }

        // 12. Payable
        $payableAmount = round(max($grossFee - $discountAmount, 0), 2);

        // 13. Payment amount validation
        //     Payment Rules (per institute, off by default) may allow less:
        //     - fee option: the cashier picked another fee option of the
        //       class category for this month (e.g. Half)
        //     - custom amount: any amount
        //     The part not paid is saved as a discount, so the month is paid.
        if ($amount < $payableAmount) {
            $minimum = $payableAmount;
            $reason = null;

            $option = $this->chosenFeeOption($enrollment, $data['fee_option_id'] ?? null);
            if ($option) {
                $optionFee = round((float) $option->fee + $this->hallFee($enrollment), 2);

                if ($optionFee < $minimum) {
                    $minimum = $optionFee;
                    $reason = 'Fee option: ' . $option->label;
                }
            }

            if (PaymentRules::customAmount()) {
                $minimum = 0;
                $reason = $reason ?? 'Custom amount';
            }

            if ($amount < $minimum || $reason === null) {
                throw new \InvalidArgumentException(sprintf(
                    'Payment amount must be at least Rs. %.2f.',
                    $minimum < $payableAmount ? $minimum : $payableAmount
                ));
            }

            $discountAmount = round($grossFee - $amount, 2);
            $data['note'] = trim(($data['note'] ?? '') . ' [' . $reason . ': discount Rs. '
                . number_format($discountAmount, 2) . ']');
        }

        // 14. Duplicate check
        $alreadyPaid = Payment::query()
            ->where('student_class_enrollment_id', $enrollment->id)
            ->whereDate('payment_month', $paymentMonth)
            ->where('status', 'completed')
            ->exists();

        if ($alreadyPaid) {
            throw new \RuntimeException(
                'Payment already completed for this class and month.'
            );
        }

        // 15. Paid at
        $paidAt = $data['paid_at'] ?? now();
        if (!$paidAt instanceof Carbon) {
            try {
                $paidAt = Carbon::parse($paidAt);
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException('Invalid payment date/time.');
            }
        }

        // 16. Receipt number
        $receiptNumber = $this->receiptNumberService->generate();

        // 17. Create payment
        try {
            $payment = Payment::create([
                'branch_id' => $branchId,   // ✅ Branch ID
                'student_id' => $student->id,
                'student_class_enrollment_id' => $enrollment->id,
                'user_id' => auth()->id(),
                'mark_method' => $markMethod,
                'amount' => round($amount, 2),
                'discount_amount' => round($discountAmount, 2),
                'paid_at' => $paidAt,
                'payment_month' => $paymentMonth,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'receipt_number' => $receiptNumber,
                'reference_number' => $data['reference_number'] ?? null,
                'is_synced' => true,
                'note' => $data['note'] ?? null,
            ]);

            // ✅ Create split snapshot
            $this->splitSnapshotService->createForPayment($payment);

            return $payment;
        } catch (QueryException $e) {
            $message = strtolower($e->getMessage());

            if (strpos($message, 'unique_enrollment_month_payment') !== false) {
                throw new \RuntimeException(
                    'Payment already completed for this class and month.'
                );
            }

            throw $e;
        }
    }

    /**
     * Class fee (selected fee option, 0 for a free card) + hall fee of the
     * active schedule pattern.
     */
    public function grossFee(StudentClassEnrollment $enrollment): float
    {
        $classFee = (float) $enrollment->final_fee;
        if ($classFee < 0) {
            throw new \InvalidArgumentException('Invalid enrollment fee.');
        }

        return round($classFee + $this->hallFee($enrollment), 2);
    }

    public function hallFee(StudentClassEnrollment $enrollment): float
    {
        $hallFee = 0;
        $studentClass = $enrollment->studentClass;

        if ($studentClass) {
            $pattern = $studentClass
                ->schedulePatterns()
                ->where('is_active', true)
                ->where('class_category_fee_id', $enrollment->class_category_fee_id)
                ->with('hall')
                ->latest('start_date')
                ->first();

            if ($pattern && $pattern->hall) {
                $hallFee = (float) $pattern->hall->hall_price;
            }
        }

        if ($hallFee < 0) {
            throw new \InvalidArgumentException('Invalid hall fee.');
        }

        return round($hallFee, 2);
    }

    /**
     * The fee option the cashier picked for this payment (Payment Rules >
     * fee option). Only an active option of the student's own class category.
     */
    public function chosenFeeOption(StudentClassEnrollment $enrollment, $optionId): ?ClassCategoryFeeOption
    {
        if (!PaymentRules::feeOption() || !$optionId || $enrollment->is_free_card) {
            return null;
        }

        $option = ClassCategoryFeeOption::query()
            ->where('id', (int) $optionId)
            ->where('class_category_fee_id', $enrollment->class_category_fee_id)
            ->where('is_active', true)
            ->first();

        if (!$option) {
            throw new \InvalidArgumentException('The selected fee option is not available for this class.');
        }

        return $option;
    }
}
