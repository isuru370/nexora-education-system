<?php

namespace App\Services\Web\Payment;

use App\Models\Payment;
use App\Services\Notification\PaymentNotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * @var PaymentStudentFinder
     */
    protected $studentFinder;

    /**
     * @var PaymentDataBuilder
     */
    protected $dataBuilder;

    /**
     * @var PaymentMarker
     */
    protected $paymentMarker;

    /**
     * @var PaymentNotificationService
     */
    protected $notificationService;

    /**
     * @var ReceiptService
     */
    protected $receiptService;

    /**
     * PaymentService constructor.
     *
     * PHP 7.4 compatible constructor.
     */
    public function __construct(
        PaymentStudentFinder $studentFinder,
        PaymentDataBuilder $dataBuilder,
        PaymentMarker $paymentMarker,
        PaymentNotificationService $notificationService,
        ReceiptService $receiptService
    ) {
        $this->studentFinder = $studentFinder;
        $this->dataBuilder = $dataBuilder;
        $this->paymentMarker = $paymentMarker;
        $this->notificationService = $notificationService;
        $this->receiptService = $receiptService;
    }

    /**
     * Read student payment information.
     *
     * Used by:
     * - Web
     * - Mobile
     *
     * Returns:
     * - Student details
     * - Active classes
     * - Class-wise fee
     * - Class-wise payment status
     * - Class-wise last payment
     */
    public function read(string $code): array
    {
        $student = $this->studentFinder->findStudent($code);

        if (!$student) {
            throw new \RuntimeException(
                'Student not found.'
            );
        }

        $enrollments = $this->studentFinder
            ->getActiveEnrollments($student);

        return [
            'student' => $this->dataBuilder
                ->student($student),

            'classes' => $this->dataBuilder
                ->classes($enrollments),
        ];
    }

    /**
     * Store ONE payment.
     *
     * Flow:
     *
     * Controller
     *     ↓
     * PaymentService
     *     ↓
     * StudentFinder
     *     ↓
     * PaymentMarker
     *     ↓
     * Payment
     *     ↓
     * Notification
     *     ↓
     * Receipt
     */
    public function pay(array $data): array
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Required Input
        |--------------------------------------------------------------------------
        */

        if (
            !isset($data['code']) ||
            trim((string) $data['code']) === ''
        ) {
            throw new \InvalidArgumentException(
                'Student code is required.'
            );
        }

        if (
            !isset($data['enrollment_id']) ||
            (int) $data['enrollment_id'] <= 0
        ) {
            throw new \InvalidArgumentException(
                'Enrollment ID is required.'
            );
        }

        if (
            !isset($data['amount']) ||
            (float) $data['amount'] <= 0
        ) {
            throw new \InvalidArgumentException(
                'Payment amount is required.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Optional Discount
        |--------------------------------------------------------------------------
        |
        | Discount is NOT required.
        |
        | If UI does not send discount_amount:
        |
        | discount_amount = 0
        |
        */

        if (!isset($data['discount_amount'])) {
            $data['discount_amount'] = 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Create Payment
        |--------------------------------------------------------------------------
        */

        $payment = DB::transaction(
            function () use ($data) {

                /*
                |--------------------------------------------------------------------------
                | Find Student
                |--------------------------------------------------------------------------
                */

                $student = $this->studentFinder
                    ->findStudent($data['code']);

                if (!$student) {
                    throw new \RuntimeException(
                        'Student not found.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Find Enrollment
                |--------------------------------------------------------------------------
                */

                $enrollment = $this->studentFinder
                    ->findEnrollment(
                        $student,
                        (int) $data['enrollment_id']
                    );

                if (!$enrollment) {
                    throw new \RuntimeException(
                        'Student class enrollment not found.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Create Payment
                |--------------------------------------------------------------------------
                |
                | PaymentMarker handles:
                |
                | - fee option
                | - class fee
                | - hall fee
                | - discount
                | - payable amount
                | - duplicate payment
                | - receipt number
                |
                */

                return $this->paymentMarker->mark(
                    $student,
                    $enrollment,
                    $data
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Transaction Committed
        |--------------------------------------------------------------------------
        |
        | Notifications are sent ONLY after successful DB commit.
        |
        */

        // $this->queueNotification($payment);

        /*
        |--------------------------------------------------------------------------
        | Payment Response
        |--------------------------------------------------------------------------
        */

        $paymentData = $this->dataBuilder
            ->payment($payment);

        /*
        |--------------------------------------------------------------------------
        | Single Receipt
        |--------------------------------------------------------------------------
        */

        $receipt = $this->receiptService
            ->single($payment);

        return [
            'payment' => $paymentData,

            'receipt' => $receipt,
        ];
    }

    /**
     * Store MULTIPLE payments in one bulk operation.
     *
     * IMPORTANT REQUIREMENTS:
     *
     * 1. All payments must belong to ONE student.
     * 2. All payments must use ONE payment month.
     * 3. All payments get the SAME paid_at.
     * 4. Each Payment gets its OWN receipt number.
     * 5. One combined 58mm receipt is generated.
     *
     * Discount:
     *
     * - Optional per payment.
     * - If discount_amount is missing, PaymentMarker uses 0.
     */
    public function bulkPay(array $payments): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Empty Validation
        |--------------------------------------------------------------------------
        */

        if (empty($payments)) {
            throw new \InvalidArgumentException(
                'No payments provided.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Resolve First Student
        |--------------------------------------------------------------------------
        */

        $firstCode = trim(
            (string) (
                isset($payments[0]['code'])
                    ? $payments[0]['code']
                    : ''
            )
        );

        if ($firstCode === '') {
            throw new \InvalidArgumentException(
                'Student code is required.'
            );
        }

        $firstStudent = $this->studentFinder
            ->findStudent($firstCode);

        if (!$firstStudent) {
            throw new \RuntimeException(
                'Student not found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Resolve First Payment Month
        |--------------------------------------------------------------------------
        */

        $firstMonth = trim(
            (string) (
                isset($payments[0]['payment_month'])
                    ? $payments[0]['payment_month']
                    : ''
            )
        );

        if ($firstMonth === '') {
            throw new \InvalidArgumentException(
                'Payment month is required.'
            );
        }

        try {
            $firstMonthDate = Carbon::parse(
                $firstMonth
            )->startOfMonth();
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                'Invalid payment month.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. One Timestamp For Entire Bulk Operation
        |--------------------------------------------------------------------------
        */

        $paidAt = now();

        /*
        |--------------------------------------------------------------------------
        | 5. Create All Payments In ONE Transaction
        |--------------------------------------------------------------------------
        */

        $createdPayments = DB::transaction(
            function () use (
                $payments,
                $paidAt,
                $firstStudent,
                $firstMonthDate
            ) {
                $results = [];

                foreach ($payments as $data) {

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Student Code
                    |--------------------------------------------------------------------------
                    */

                    $code = trim(
                        (string) (
                            isset($data['code'])
                                ? $data['code']
                                : ''
                        )
                    );

                    if ($code === '') {
                        throw new \InvalidArgumentException(
                            'Student code is required.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Find Student
                    |--------------------------------------------------------------------------
                    */

                    $student = $this->studentFinder
                        ->findStudent($code);

                    if (!$student) {
                        throw new \RuntimeException(
                            'Student not found.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Every Bulk Payment Must Belong To Same Student
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (int) $student->id !==
                        (int) $firstStudent->id
                    ) {
                        throw new \InvalidArgumentException(
                            'All bulk payments must belong to the same student.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Payment Month
                    |--------------------------------------------------------------------------
                    */

                    $paymentMonth = trim(
                        (string) (
                            isset($data['payment_month'])
                                ? $data['payment_month']
                                : ''
                        )
                    );

                    if ($paymentMonth === '') {
                        throw new \InvalidArgumentException(
                            'Payment month is required.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Compare Month Only
                    |--------------------------------------------------------------------------
                    */

                    try {
                        $currentMonthDate = Carbon::parse(
                            $paymentMonth
                        )->startOfMonth();
                    } catch (\Throwable $e) {
                        throw new \InvalidArgumentException(
                            'Invalid payment month.'
                        );
                    }

                    if (
                        !$firstMonthDate->equalTo(
                            $currentMonthDate
                        )
                    ) {
                        throw new \InvalidArgumentException(
                            'All bulk payments must use the same payment month.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Enrollment ID
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !isset($data['enrollment_id']) ||
                        (int) $data['enrollment_id'] <= 0
                    ) {
                        throw new \InvalidArgumentException(
                            'Enrollment ID is required.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Payment Amount
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !isset($data['amount']) ||
                        (float) $data['amount'] <= 0
                    ) {
                        throw new \InvalidArgumentException(
                            'Payment amount must be greater than zero.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Optional Discount
                    |--------------------------------------------------------------------------
                    |
                    | No discount from UI:
                    |
                    | discount_amount = 0
                    |
                    */

                    if (!isset($data['discount_amount'])) {
                        $data['discount_amount'] = 0;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Force Same Paid At
                    |--------------------------------------------------------------------------
                    */

                    $data['paid_at'] = $paidAt;

                    /*
                    |--------------------------------------------------------------------------
                    | Find Enrollment
                    |--------------------------------------------------------------------------
                    */

                    $enrollment = $this->studentFinder
                        ->findEnrollment(
                            $student,
                            (int) $data['enrollment_id']
                        );

                    if (!$enrollment) {
                        throw new \RuntimeException(
                            'Student class enrollment not found.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create Payment
                    |--------------------------------------------------------------------------
                    */

                    $payment = $this->paymentMarker
                        ->mark(
                            $student,
                            $enrollment,
                            $data
                        );

                    $results[] = $payment;
                }

                return $results;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Transaction Successfully Committed
        |--------------------------------------------------------------------------
        |
        | Notifications are sent only after all payments are successfully
        | created.
        |
        */

        foreach ($createdPayments as $payment) {
            $this->queueNotification($payment);
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Build Payment Data
        |--------------------------------------------------------------------------
        */

        $paymentData = [];

        foreach ($createdPayments as $payment) {
            $paymentData[] = $this->dataBuilder
                ->payment($payment);
        }

        /*
        |--------------------------------------------------------------------------
        | 8. One Bulk Receipt
        |--------------------------------------------------------------------------
        */

        $receipt = $this->receiptService
            ->bulk(
                collect($createdPayments)
            );

        /*
        |--------------------------------------------------------------------------
        | 9. Calculate Summary
        |--------------------------------------------------------------------------
        |
        | Gross fee = Class Fee + Hall Fee
        |
        | Discount is optional and defaults to zero.
        |
        */

        $totalFinalFee = 0;
        $totalHallFee = 0;
        $totalGrossFee = 0;
        $totalDiscount = 0;
        $totalPayable = 0;
        $totalPaid = 0;

        foreach ($createdPayments as $payment) {

            /*
            |--------------------------------------------------------------------------
            | Load Required Relationships
            |--------------------------------------------------------------------------
            */

            $payment->loadMissing([
                'enrollment.studentClass',
                'enrollment.classCategoryFeeOption',
            ]);

            $enrollment = $payment->enrollment;

            /*
            |--------------------------------------------------------------------------
            | Class Fee
            |--------------------------------------------------------------------------
            */

            $classFee = 0;

            if ($enrollment) {
                $classFee = (float) $enrollment->final_fee;
            }

            /*
            |--------------------------------------------------------------------------
            | Hall Fee
            |--------------------------------------------------------------------------
            */

            $hallFee = 0;

            if (
                $enrollment &&
                $enrollment->studentClass
            ) {
                $pattern = $enrollment
                    ->studentClass
                    ->schedulePatterns()
                    ->where('is_active', true)
                    ->where(
                        'class_category_fee_id',
                        $enrollment->class_category_fee_id
                    )
                    ->with('hall')
                    ->latest('start_date')
                    ->first();

                if (
                    $pattern &&
                    $pattern->hall
                ) {
                    $hallFee = (float) $pattern
                        ->hall
                        ->hall_price;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Gross Fee
            |--------------------------------------------------------------------------
            */

            $grossFee = round(
                $classFee + $hallFee,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Discount
            |--------------------------------------------------------------------------
            */

            $discount = (float) $payment->discount_amount;

            /*
            |--------------------------------------------------------------------------
            | Payable
            |--------------------------------------------------------------------------
            */

            $payable = round(
                max(
                    $grossFee - $discount,
                    0
                ),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */

            $totalFinalFee += $classFee;

            $totalHallFee += $hallFee;

            $totalGrossFee += $grossFee;

            $totalDiscount += $discount;

            $totalPayable += $payable;

            $totalPaid += (float) $payment->amount;
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Final Response
        |--------------------------------------------------------------------------
        */

        return [
            'payments' => $paymentData,

            'receipt' => $receipt,

            'count' => count(
                $createdPayments
            ),

            /*
            | Total selected fee option amount.
            */
            'total_final_fee' => round(
                $totalFinalFee,
                2
            ),

            /*
            | Total hall charges.
            */
            'total_hall_fee' => round(
                $totalHallFee,
                2
            ),

            /*
            | Class Fee + Hall Fee.
            */
            'total_gross_fee' => round(
                $totalGrossFee,
                2
            ),

            /*
            | Optional discount.
            */
            'total_discount' => round(
                $totalDiscount,
                2
            ),

            /*
            | Gross Fee - Discount.
            */
            'total_payable' => round(
                $totalPayable,
                2
            ),

            /*
            | Actual amount received.
            */
            'total_paid' => round(
                $totalPaid,
                2
            ),

            /*
            | Remaining balance, if any.
            */
            'total_balance' => round(
                max(
                    $totalPayable - $totalPaid,
                    0
                ),
                2
            ),

            'paid_at' => $paidAt->format(
                'Y-m-d H:i:s'
            ),
        ];
    }

    /**
     * Send payment notifications.
     *
     * PaymentNotificationService handles:
     *
     * FCM:
     *     Active student devices only.
     *
     * SMS:
     *     Guardian mobile.
     */
    private function queueNotification(
        Payment $payment
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Load Required Relationships
        |--------------------------------------------------------------------------
        */

        $payment->load([
            'student',
            'enrollment.studentClass.teacher',
            'enrollment.studentClass.subject',
            'enrollment.studentClass.grade',
            'enrollment.classCategoryFee.category',
            'enrollment.classCategoryFeeOption',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Send Notifications
        |--------------------------------------------------------------------------
        */

        $this->notificationService
            ->sendSuccess($payment);
    }
}