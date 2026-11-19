<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\StudentClassEnrollment;
use Illuminate\Support\Collection;

/**
 * One student's fee for one month, with the class fee and the hall fee
 * kept apart (the same rules as the payment and its split):
 *
 *   payable = class fee (fee option, 0 for a free card) + hall fee
 *   a payment pays the hall fee first, the rest is class fee
 *   paid    = amount + discount covers the payable
 *
 * The hall fee of a month that was paid is the one saved with the payment
 * (payment_split_snapshots.hall_fee), so a later hall price change does not
 * change an old month. A month not paid yet uses the class's hall fee now.
 */
final class MonthFee
{
    public const PAID = 'paid';
    public const PARTIAL = 'partial';
    public const UNPAID = 'unpaid';
    public const FREE_CARD = 'freecard';

    /**
     * @param  iterable<Payment>  $payments  completed payments FOR this month (splitSnapshot loaded)
     * @return array{class_fee: float, hall_fee: float, payable: float, paid: float, discount: float, hall_paid: float, class_paid: float, balance: float, status: string}
     */
    public static function forEnrollment(StudentClassEnrollment $enrollment, iterable $payments, float $hallFeeNow): array
    {
        $payments = collect($payments);

        $classFee = $enrollment->is_free_card ? 0.0 : round(max((float) $enrollment->final_fee, 0), 2);

        $savedHallFees = $payments
            ->map(fn (Payment $payment) => $payment->splitSnapshot?->hall_fee)
            ->filter(fn ($fee) => $fee !== null);

        $hallFee = round($savedHallFees->isNotEmpty() ? (float) $savedHallFees->max() : max($hallFeeNow, 0), 2);

        $parts = self::paymentParts($payments, $hallFee);
        $payable = round($classFee + $hallFee, 2);
        $covered = round($parts['paid'] + $parts['discount'], 2);

        if ($enrollment->is_free_card) {
            $status = self::FREE_CARD;
        } elseif ($payable <= 0 || $covered >= $payable - 0.005) {
            $status = self::PAID;
        } elseif ($parts['paid'] > 0) {
            $status = self::PARTIAL;
        } else {
            $status = self::UNPAID;
        }

        return [
            'class_fee' => $classFee,
            'hall_fee' => $hallFee,
            'payable' => $payable,
            'paid' => $parts['paid'],
            'discount' => $parts['discount'],
            'hall_paid' => $parts['hall_paid'],
            'class_paid' => $parts['class_paid'],
            'balance' => $status === self::PAID ? 0.0 : max(round($payable - $covered, 2), 0),
            'status' => $status,
        ];
    }

    /**
     * Money of some payments, split into hall fee and class fee.
     *
     * @param  Collection<int, Payment>  $payments  (splitSnapshot loaded)
     * @return array{paid: float, discount: float, hall_paid: float, class_paid: float}
     */
    public static function paymentParts(Collection $payments, float $hallFeeNow = 0.0): array
    {
        $paid = 0.0;
        $hall = 0.0;
        $discount = 0.0;

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;
            $paid += $amount;
            $discount += (float) ($payment->discount_amount ?? 0);
            $hall += self::hallPart($amount, $payment->splitSnapshot?->hall_fee ?? $hallFeeNow);
        }

        return [
            'paid' => round($paid, 2),
            'discount' => round($discount, 2),
            'hall_paid' => round($hall, 2),
            'class_paid' => round($paid - $hall, 2),
        ];
    }

    /** The hall fee is taken first (as PaymentSplitSnapshotService::split). */
    public static function hallPart(float $amount, float|string|null $hallFee): float
    {
        return round(min(max((float) $hallFee, 0), max($amount, 0)), 2);
    }

    public static function label(string $status): string
    {
        return [
            self::PAID => 'Paid',
            self::PARTIAL => 'Partly paid',
            self::UNPAID => 'Unpaid',
            self::FREE_CARD => 'Free card',
            'noattend' => 'No attendance',
        ][$status] ?? ucfirst($status);
    }

    /** Hall fee now per class + fee category (one query per pair). */
    public static function hallFeeCache(): \Closure
    {
        $cache = [];

        return function (StudentClassEnrollment $enrollment) use (&$cache): float {
            $key = $enrollment->student_class_id . '|' . $enrollment->class_category_fee_id;

            return $cache[$key] ??= HallFee::forEnrollment($enrollment);
        };
    }
}
