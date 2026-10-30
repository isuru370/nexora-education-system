<?php

namespace App\Services\Web\Payment;

use App\Models\Payment;
use App\Models\StudentClassEnrollment;
use App\Models\StudentTute;
use App\Support\PaymentRules;
use Carbon\CarbonInterface as Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What the payment screen shows after a month is chosen for one class:
 * attendance that month, the fee after the Payment Rules, whether it is
 * already paid, and that month's tute.
 */
class PaymentMonthInfoService
{
    public function __construct(
        protected PaymentMarker $paymentMarker
    ) {}

    /** Times the student attended this class in the month. */
    public static function attendanceCount(int $enrollmentId, Carbon $month): int
    {
        $start = $month->copy()->startOfMonth();

        return DB::table('student_attendances')
            ->where('student_class_enrollment_id', $enrollmentId)
            ->whereBetween('attended_at', [$start, $start->copy()->endOfMonth()])
            ->count();
    }

    public function info(StudentClassEnrollment $enrollment, Carbon $month): array
    {
        $month = $month->copy()->startOfMonth();
        $rules = PaymentRules::all();

        $grossFee = $this->paymentMarker->grossFee($enrollment);
        $attendance = self::attendanceCount($enrollment->id, $month);
        $percent = PaymentRules::percentFor($attendance);
        $suggested = round($grossFee * $percent / 100, 2);

        $payment = Payment::query()
            ->where('student_class_enrollment_id', $enrollment->id)
            ->whereDate('payment_month', $month)
            ->where('status', 'completed')
            ->first();

        $tute = StudentTute::query()
            ->where('student_class_enrollment_id', $enrollment->id)
            ->whereDate('issued_month', $month)
            ->first();

        return [
            'enrollment_id' => $enrollment->id,
            'payment_month' => $month->toDateString(),
            'payment_month_name' => $month->format('F Y'),

            'attendance_count' => $attendance,

            'gross_fee' => $grossFee,
            'rule' => [
                'enabled' => $rules['attendance_rule'],
                'percent' => $percent,
                'reason' => $rules['attendance_rule'] ? PaymentRules::reason($attendance, $percent) : null,
                'no_fee' => $rules['attendance_rule'] && $percent === 0,
            ],
            'suggested_amount' => $suggested,
            'suggested_discount' => round($grossFee - $suggested, 2),

            // Cashier may type an amount (the rest is saved as a discount)
            'custom_amount' => $rules['custom_amount'],

            'already_paid' => $payment !== null,
            'paid' => $payment ? [
                'amount' => (float) $payment->amount,
                'discount_amount' => (float) $payment->discount_amount,
                'receipt_number' => $payment->receipt_number,
                'paid_at' => optional($payment->paid_at)->format('Y-m-d H:i'),
            ] : null,

            'tute' => [
                'enabled' => $rules['tute'],
                'issued' => $tute !== null,
                'issued_at' => $tute ? optional($tute->issued_at ?? $tute->created_at)->format('Y-m-d') : null,
            ],
        ];
    }

    /**
     * Give this month's tute with a payment (optional "Give tute" tick).
     * Nothing happens when tutes are off or it was already given.
     */
    public function giveTute(StudentClassEnrollment $enrollment, Carbon $month): bool
    {
        if (!PaymentRules::tute()) {
            return false;
        }

        $month = $month->copy()->startOfMonth();

        $exists = StudentTute::query()
            ->where('student_class_enrollment_id', $enrollment->id)
            ->whereDate('issued_month', $month)
            ->exists();

        if ($exists) {
            return false;
        }

        StudentTute::create([
            'branch_id' => $enrollment->branch_id,
            'student_id' => $enrollment->student_id,
            'student_class_enrollment_id' => $enrollment->id,
            'issued_month' => $month->toDateString(),
            'is_issued' => true,
            'issued_at' => now(),
            'issued_by' => auth()->id(),
            'note' => 'Given with the payment',
        ]);

        return true;
    }
}
