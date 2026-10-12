<?php

namespace App\Services\Web\Payment;

use App\Models\ClassPaymentConfig;
use App\Models\ClassSchedulePattern;
use App\Models\Payment;
use App\Models\PaymentSplitSnapshot;
use App\Models\StudentClassEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentSplitSnapshotService
{
    /*
    |--------------------------------------------------------------------------
    | Create Snapshot
    |--------------------------------------------------------------------------
    */

    /**
     * Create a split snapshot for the given payment.
     *
     * @param Payment $payment
     * @return PaymentSplitSnapshot
     */
    public function createForPayment(Payment $payment): PaymentSplitSnapshot
    {
        return DB::transaction(function () use ($payment) {

            /*
            |--------------------------------------------------------------------------
            | Load Enrollment
            |--------------------------------------------------------------------------
            */
            $enrollment = StudentClassEnrollment::with([
                'studentClass',
                'classCategoryFee',
                'classCategoryFeeOption',
                'studentClass.branch',
            ])
                ->where('id', $payment->student_class_enrollment_id)
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Resolve Payment Config
            |--------------------------------------------------------------------------
            */
            $paymentConfig = $this->resolvePaymentConfig($enrollment);

            if (!$paymentConfig) {
                throw ValidationException::withMessages([
                    'payment_config' =>
                    'No active payment configuration found for this class.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Verify Branch Match
            |--------------------------------------------------------------------------
            */
            if ((int) $paymentConfig->branch_id !== (int) $enrollment->branch_id) {
                throw ValidationException::withMessages([
                    'payment_config' =>
                    'Payment configuration branch mismatch.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Resolve Fees
            |--------------------------------------------------------------------------
            */
            $classFee = (float) $enrollment->final_fee;
            $hallFee = $this->resolveHallFee($enrollment);
            $totalFee = $classFee + $hallFee;

            /*
            |--------------------------------------------------------------------------
            | Payment Amount
            |--------------------------------------------------------------------------
            */
            $paymentAmount = (float) $payment->amount;

            /*
            |--------------------------------------------------------------------------
            | Resolve Percentages
            |--------------------------------------------------------------------------
            */
            $teacherPercentage = (float) $paymentConfig->teacher_percentage;
            $organizerPercentage = (float) $paymentConfig->organizer_percentage;
            $institutionPercentage = (float) $paymentConfig->institution_percentage;

            /*
            |--------------------------------------------------------------------------
            | Compute Split Amounts
            |--------------------------------------------------------------------------
            */
            $teacherAmount = round($paymentAmount * ($teacherPercentage / 100), 2);
            $organizerAmount = round($paymentAmount * ($organizerPercentage / 100), 2);
            $institutionAmount = round($paymentAmount * ($institutionPercentage / 100), 2);

            /*
            |--------------------------------------------------------------------------
            | Resolve Teacher
            |--------------------------------------------------------------------------
            */
            $teacherId = $paymentConfig->teacher_id;

            if (!$teacherId) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'No teacher assigned to this payment configuration.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create Snapshot
            |--------------------------------------------------------------------------
            */
            return PaymentSplitSnapshot::create([
                'branch_id' => $payment->branch_id,
                'payment_id' => $payment->id,

                // Fee snapshot
                'class_fee' => $classFee,
                'hall_fee' => $hallFee,
                'total_fee' => $totalFee,

                // Class / Enrollment
                'student_class_id' => $enrollment->student_class_id,
                'student_class_enrollment_id' => $enrollment->id,

                // Payment config
                'class_payment_config_id' => $paymentConfig->id,

                // People
                'teacher_id' => $teacherId,
                'organizer_id' => $paymentConfig->organizer_id,
                'created_by' => $payment->user_id,

                // Payment
                'payment_amount' => $paymentAmount,

                // Percentage snapshot
                'teacher_percentage' => $teacherPercentage,
                'organizer_percentage' => $organizerPercentage,
                'institution_percentage' => $institutionPercentage,

                // Amount snapshot
                'teacher_amount' => $teacherAmount,
                'organizer_amount' => $organizerAmount,
                'institution_amount' => $institutionAmount,

                // Payment date
                'payment_date' => $payment->paid_at,
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Refresh Snapshot
    |--------------------------------------------------------------------------
    */

    /**
     * Re-create the split snapshot for an existing payment.
     *
     * @param Payment $payment
     * @return PaymentSplitSnapshot
     */
    public function refreshForPayment(Payment $payment): PaymentSplitSnapshot
    {
        return DB::transaction(function () use ($payment) {
            $payment->splitSnapshot?->delete();
            return $this->createForPayment($payment);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Protected Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve the active payment configuration for the enrollment's class.
     */
    protected function resolvePaymentConfig(
        StudentClassEnrollment $enrollment
    ): ?ClassPaymentConfig {
        $studentClassId = $enrollment->student_class_id;
        $today = now()->toDateString();

        return ClassPaymentConfig::query()
            ->where('student_class_id', $studentClassId)
            ->where('branch_id', $enrollment->branch_id)
            ->where('is_active', true)
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }


    /**
     * Resolve the hall fee for the enrollment.
     *
     * Hall is defined on the ClassSchedulePattern (master pattern).
     * Hall fee = hall_price from ClassHall.
     *
     * @param StudentClassEnrollment $enrollment
     * @return float
     */
    protected function resolveHallFee(StudentClassEnrollment $enrollment): float
    {
        /** @var ClassSchedulePattern|null $pattern */
        $pattern = ClassSchedulePattern::query()
            ->with('hall:id,hall_name,hall_price')
            ->where('student_class_id', $enrollment->student_class_id)
            ->where('class_category_fee_id', $enrollment->class_category_fee_id)
            ->where('branch_id', $enrollment->branch_id)
            ->whereNotNull('class_hall_id')
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if (!$pattern || !$pattern->hall) {
            return 0.0;
        }

        return (float) ($pattern->hall->hall_price ?? 0);
    }
}