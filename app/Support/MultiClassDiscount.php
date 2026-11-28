<?php

namespace App\Support;

use App\Models\StudentClassEnrollment;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Multi-class discount (Payment Rules, Super Admin, per institute).
 *
 * A student who pays at least `min_classes` different classes at once (one
 * payment, one month) gets `amount` off the CLASS fee of each of those
 * classes. Counted: classes of the same branch, each class + category once
 * (Theory and Revision are 2), no free cards, no classes without a fee.
 * The hall fee does not change. The teacher bears the discount (split).
 *
 * Off by default. Stored as rows in system_settings (payment_multi_class_*).
 */
class MultiClassDiscount
{
    public const CACHE_KEY = 'nexora.multi_class_discount';

    public const KEYS = [
        'enabled' => 'payment_multi_class_enabled',
        'min_classes' => 'payment_multi_class_min',
        'amount' => 'payment_multi_class_amount',
    ];

    public const DEFAULT_MIN = 4;

    /** @return array{enabled: bool, min_classes: int, amount: float} */
    public static function all(): array
    {
        $values = Cache::rememberForever(self::CACHE_KEY, fn () => SystemSetting::query()
            ->whereIn('key', array_values(self::KEYS))
            ->pluck('value', 'key')
            ->all());

        $min = (int) ($values[self::KEYS['min_classes']] ?? self::DEFAULT_MIN);
        $amount = round((float) ($values[self::KEYS['amount']] ?? 0), 2);

        return [
            'enabled' => ($values[self::KEYS['enabled']] ?? '0') === '1' && $min >= 2 && $amount > 0,
            'min_classes' => max($min, 2),
            'amount' => max($amount, 0),
        ];
    }

    /** @param array{enabled: bool, min_classes: int, amount: float} $rule */
    public static function save(array $rule): void
    {
        $values = [
            'enabled' => !empty($rule['enabled']) ? '1' : '0',
            'min_classes' => (string) max((int) $rule['min_classes'], 2),
            'amount' => (string) round(max((float) $rule['amount'], 0), 2),
        ];

        foreach (self::KEYS as $name => $key) {
            SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $values[$name]]);
        }

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Does this enrollment count as a class for the discount? */
    public static function counts(StudentClassEnrollment $enrollment): bool
    {
        return !$enrollment->is_free_card && (float) $enrollment->final_fee > 0;
    }

    /**
     * The discount of each enrollment paid together (index => Rs).
     *
     * @param  array<int|string, StudentClassEnrollment|null>  $enrollments  index => enrollment
     * @return array<int|string, float>
     */
    public static function forEnrollments(array $enrollments): array
    {
        $rule = self::all();
        $discounts = array_fill_keys(array_keys($enrollments), 0.0);

        if (!$rule['enabled']) {
            return $discounts;
        }

        // different classes per branch
        $classes = [];
        foreach ($enrollments as $enrollment) {
            if ($enrollment && self::counts($enrollment)) {
                $classes[(int) $enrollment->branch_id][$enrollment->student_class_id . '|' . $enrollment->class_category_fee_id] = true;
            }
        }

        foreach ($enrollments as $index => $enrollment) {
            if (!$enrollment || !self::counts($enrollment)) {
                continue;
            }

            if (count($classes[(int) $enrollment->branch_id] ?? []) >= $rule['min_classes']) {
                // never more than the class fee
                $discounts[$index] = round(min($rule['amount'], (float) $enrollment->final_fee), 2);
            }
        }

        return $discounts;
    }
}
