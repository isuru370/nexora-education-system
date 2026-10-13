<?php

namespace App\Services\Web\Common;

/**
 * Converts a money amount to words for slips and receipts.
 *
 *   AmountInWords::rupees(125430.50)
 *   => "One Lakh Twenty Five Thousand Four Hundred and Thirty Rupees and Fifty Cents Only"
 *
 * Uses the Lakh / Crore system (same as the old salary slip).
 */
class AmountInWords
{
    protected const ONES = [
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen',
    ];

    protected const TENS = [
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
    ];

    /**
     * "... Rupees [and ... Cents] Only"
     */
    public static function rupees(float|int|string|null $amount): string
    {
        $amount = round((float) $amount, 2);
        $negative = $amount < 0;
        $amount = abs($amount);

        // Work in cents to avoid float problems (e.g. 0.1 + 0.2)
        $totalCents = (int) round($amount * 100);
        $rupees = intdiv($totalCents, 100);
        $cents = $totalCents % 100;

        $words = self::number($rupees) . ' Rupees';

        if ($cents > 0) {
            $words .= ' and ' . self::number($cents) . ' Cents';
        }

        return ($negative ? 'Minus ' : '') . $words . ' Only';
    }

    /**
     * Whole number to words (0 and above).
     */
    public static function number(int $number): string
    {
        if ($number < 0) {
            return 'Minus ' . self::number(abs($number));
        }

        if ($number < 20) {
            return self::ONES[$number];
        }

        if ($number < 100) {
            $units = $number % 10;

            return self::TENS[intdiv($number, 10)] . ($units ? ' ' . self::ONES[$units] : '');
        }

        if ($number < 1000) {
            $remainder = $number % 100;

            return self::ONES[intdiv($number, 100)] . ' Hundred'
                . ($remainder ? ' and ' . self::number($remainder) : '');
        }

        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand'] as $size => $label) {
            if ($number >= $size) {
                $remainder = $number % $size;

                return self::number(intdiv($number, $size)) . ' ' . $label
                    . ($remainder ? ' ' . self::number($remainder) : '');
            }
        }

        return '';
    }
}