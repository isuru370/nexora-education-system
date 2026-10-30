<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Class fee payment options, chosen per institute on the Payment Rules page
 * (Super Admin). All off by default, so institutes that do not use them see
 * no change.
 *
 *  fee_option     at payment the cashier picks one of the class category's
 *                 fee options (e.g. Normal 2,000 / Half 1,000) for that month;
 *                 the student's own option does not change
 *  custom_amount  cashier may type any amount
 *                 (with either, the part not paid is saved as a discount)
 *  no_attendance  a student with no attendance in a month is not shown as
 *                 unpaid ("No Attendance") and gets no payment reminder;
 *                 the cashier does not take a payment
 *  tute           the payment screen shows / gives that month's tute
 *
 * Stored as rows in system_settings (payment_*).
 */
class PaymentRules
{
    public const CACHE_KEY = 'nexora.payment_rules';

    public const KEYS = [
        'fee_option' => 'payment_fee_option',
        'custom_amount' => 'payment_custom_amount',
        'no_attendance' => 'payment_no_attendance',
        'tute' => 'payment_tute',
    ];

    public static function feeOption(): bool
    {
        return self::all()['fee_option'];
    }

    public static function customAmount(): bool
    {
        return self::all()['custom_amount'];
    }

    public static function noAttendance(): bool
    {
        return self::all()['no_attendance'];
    }

    public static function tute(): bool
    {
        return self::all()['tute'];
    }

    /**
     * @return array{fee_option: bool, custom_amount: bool, no_attendance: bool, tute: bool}
     */
    public static function all(): array
    {
        $values = Cache::rememberForever(self::CACHE_KEY, function () {
            return SystemSetting::query()
                ->whereIn('key', array_values(self::KEYS))
                ->pluck('value', 'key')
                ->all();
        });

        $rules = [];
        foreach (self::KEYS as $name => $key) {
            $rules[$name] = ($values[$key] ?? '0') === '1';
        }

        return $rules;
    }

    /**
     * @param  array<string, bool>  $rules
     */
    public static function save(array $rules): void
    {
        foreach (self::KEYS as $name => $key) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => !empty($rules[$name]) ? '1' : '0']
            );
        }

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
