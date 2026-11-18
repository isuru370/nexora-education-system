<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * What the class payment receipt shows (staff app print + web New Payment).
 * Settings > Receipt (Admin / Super Admin). Everything is ON by default, so
 * the receipt does not change until the institute turns something off.
 * The institute name, "PAYMENT RECEIPT", class name and TOTAL always print.
 *
 * Stored as rows in system_settings (receipt_*).
 */
class ReceiptRules
{
    public const CACHE_KEY = 'nexora.receipt_rules';

    /** name => [system_settings key, label on the settings page] */
    public const FIELDS = [
        'date' => ['receipt_show_date', 'Date & time'],
        'month' => ['receipt_show_month', 'Month paid for'],
        'student_name' => ['receipt_show_student_name', 'Student name'],
        'student_id' => ['receipt_show_student_id', 'Student ID'],
        'grade' => ['receipt_show_grade', 'Grade'],
        'teacher' => ['receipt_show_teacher', 'Teacher name'],
        'category' => ['receipt_show_category', 'Category'],
        'fee_option' => ['receipt_show_fee_option', 'Fee option (Normal / Half ...)'],
        'receipt_number' => ['receipt_show_receipt_number', 'Receipt number'],
        'fees' => ['receipt_show_fees', 'Class fee / Hall fee lines'],
        'classes_count' => ['receipt_show_classes_count', 'Number of classes paid'],
        'payment_method' => ['receipt_show_payment_method', 'Payment method (web receipt)'],
        'keep_note' => ['receipt_show_keep_note', '"Please keep this receipt"'],
    ];

    public const FOOTER_KEY = 'receipt_footer';

    public const FOOTER_MAX = 60;

    /**
     * @return array<string, bool|string>  every field => bool, plus footer => string
     */
    public static function all(): array
    {
        $values = Cache::rememberForever(self::CACHE_KEY, function () {
            return SystemSetting::query()
                ->whereIn('key', [...array_column(self::FIELDS, 0), self::FOOTER_KEY])
                ->pluck('value', 'key')
                ->all();
        });

        $rules = [];
        foreach (self::FIELDS as $name => [$key]) {
            $rules[$name] = ($values[$key] ?? '1') === '1';
        }

        $rules['footer'] = (string) ($values[self::FOOTER_KEY] ?? '');

        return $rules;
    }

    public static function shows(string $field): bool
    {
        return (bool) (self::all()[$field] ?? true);
    }

    /**
     * @param  array<string, bool>  $fields
     */
    public static function save(array $fields, ?string $footer): void
    {
        foreach (self::FIELDS as $name => [$key]) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => !empty($fields[$name]) ? '1' : '0']
            );
        }

        SystemSetting::query()->updateOrCreate(
            ['key' => self::FOOTER_KEY],
            ['value' => trim((string) $footer)]
        );

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
