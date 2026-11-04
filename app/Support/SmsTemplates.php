<?php

namespace App\Support;

use App\Models\SmsTemplate;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The SMS texts parents get, edited in Settings > SMS Messages.
 *
 *   SmsTemplates::render(SmsTemplates::PAYMENT, ['[Student Name]' => ..., ...])
 *
 * A line whose placeholder has no value (e.g. hall fee 0, no reason) is left
 * out. Not edited = the default text below. Sent in English only.
 */
class SmsTemplates
{
    public const PAYMENT = 'payment';
    public const ATTENDANCE = 'attendance';
    public const ADMISSION_PAYMENT = 'admission_payment';
    public const REGISTRATION = 'registration';
    public const CLASS_SCHEDULE = 'class_schedule';
    public const PAYMENT_REMINDER = 'payment_reminder';

    public const COST_KEY = 'sms_cost_per_part';
    public const DEFAULT_COST = 0.65;

    protected const CACHE_KEY = 'sms_templates.v1';
    protected const COST_CACHE_KEY = 'sms_cost_per_part.v1';

    /** Longest text that can be saved (about 6 SMS). */
    public const MAX_LENGTH = 900;

    /**
     * label, when it is sent, default text, placeholders (=> sample value for
     * the preview) and placeholders the text must have.
     *
     * @return array<string, array{label: string, when: string, icon: string, default: string, placeholders: array<string, string>, required: array<string, array{after: string, line: string}>}>
     */
    public static function definitions(): array
    {
        $institute = (string) (config('institution.name') ?: config('app.name'));

        return [
            self::PAYMENT => [
                'label' => 'Payment',
                'when' => 'When a class fee is paid (web and staff app).',
                'icon' => 'bi-cash-coin',
                'default' => "[Student Name] - Rs. [Amount] paid.\n[Class] - [Month]\nIncl. hall fee: Rs. [Hall Fee]\nReceipt: [Receipt]\nThank you.",
                'placeholders' => [
                    '[Student Name]' => 'K.M.S. Wickramasinghe',
                    '[Class]' => '2027 A/L Combined Maths',
                    '[Grade]' => '12',
                    '[Category]' => 'Theory',
                    '[Month]' => 'November 2026',
                    '[Amount]' => '2,600.00',
                    '[Class Fee]' => '2,500.00',
                    '[Hall Fee]' => '100.00',
                    '[Receipt]' => 'RCP-000123',
                    '[Date]' => '2026-11-04',
                    '[Institute]' => $institute,
                ],
                // The hall fee always goes to the parent when the hall price is not 0
                'required' => [
                    '[Hall Fee]' => ['after' => '[Amount]', 'line' => 'Incl. hall fee: Rs. [Hall Fee]'],
                ],
            ],
            self::ATTENDANCE => [
                'label' => 'Attendance',
                'when' => 'When attendance is marked (QR scan or manual).',
                'icon' => 'bi-clipboard-check-fill',
                'default' => "Attendance marked.\nStudent: [Student Name]\nClass: [Class]\nCategory: [Category]\nGrade: [Grade]\nDate: [Date]\nTime: [Time]\nThank you.",
                'placeholders' => [
                    '[Student Name]' => 'K.M.S. Wickramasinghe',
                    '[Class]' => '2027 A/L Combined Maths',
                    '[Grade]' => '12',
                    '[Category]' => 'Theory',
                    '[Date]' => '2026-11-04',
                    '[Time]' => '08:15',
                    '[Institute]' => $institute,
                ],
                'required' => [],
            ],
            self::ADMISSION_PAYMENT => [
                'label' => 'Admission payment',
                'when' => 'When an admission fee is paid.',
                'icon' => 'bi-receipt',
                'default' => "Dear Parent, [Admission] fee Rs. [Amount] paid for [Student Name] at [Institute].\nReceipt: [Receipt]\nThank you.",
                'placeholders' => [
                    '[Student Name]' => 'K.M.S. Wickramasinghe',
                    '[Admission]' => 'Admission 2026',
                    '[Amount]' => '1,000.00',
                    '[Receipt]' => 'ADM-000045',
                    '[Date]' => '2026-11-04',
                    '[Institute]' => $institute,
                ],
                'required' => [],
            ],
            self::REGISTRATION => [
                'label' => 'Registration',
                'when' => 'When a new student is registered (has the parent portal login).',
                'icon' => 'bi-person-plus-fill',
                'default' => "Dear Parent, [Student Name] has been registered at [Institute].\n\nAdmission fee: [Admission Fee]\n\nParent portal login\nUsername: [Username]\nPassword: [Password]\nApp: [App Link]\nKeep these details private.",
                'placeholders' => [
                    '[Student Name]' => 'K.M.S. Wickramasinghe',
                    '[Student ID]' => 'SA26001',
                    '[Institute]' => $institute,
                    '[Admission Fee]' => 'Rs. 1,000.00 PAID (Receipt ADM-000045)',
                    '[Username]' => 'SA26001',
                    '[Password]' => '48213907',
                    '[App Link]' => (string) (config('services.parent_hub.app_link') ?: ''),
                ],
                // Without these the parent cannot log in
                'required' => [
                    '[Username]' => ['after' => '', 'line' => 'Username: [Username]'],
                    '[Password]' => ['after' => '[Username]', 'line' => 'Password: [Password]'],
                ],
            ],
            self::CLASS_SCHEDULE => [
                'label' => 'Class schedule',
                'when' => 'When a class is added, changed or cancelled.',
                'icon' => 'bi-calendar-event-fill',
                'default' => "Dear Parent,\n\n[Title]\n\nClass : [Class]\nCategory : [Category]\nHall : [Hall]\nDate : [Date]\nTime : [Time]\nReason : [Reason]\n\nThank You.",
                'placeholders' => [
                    '[Title]' => 'Class has been cancelled.',
                    '[Class]' => '2027 A/L Combined Maths',
                    '[Category]' => 'Theory',
                    '[Hall]' => 'Hall A',
                    '[Date]' => '2026-11-08',
                    '[Time]' => '08:00 - 12:00',
                    '[Reason]' => 'Teacher is sick',
                    '[Institute]' => $institute,
                ],
                'required' => [],
            ],
            self::PAYMENT_REMINDER => [
                'label' => 'Payment reminder',
                'when' => 'Default text on the Payment Reminder page (can still be changed there before sending).',
                'icon' => 'bi-alarm-fill',
                'default' => "Dear Parent/Guardian,\nThe class fee for [Month] is still pending.\nStudent: [Student Name]\nClass: [Class]\nAmount due: Rs. [Amount]\nAttended: [Attendance] day(s)\nPlease settle the payment. Thank you.",
                'placeholders' => [
                    '[Student Name]' => 'K.M.S. Wickramasinghe',
                    '[Class]' => '2027 A/L Combined Maths',
                    '[Month]' => 'November 2026',
                    '[Amount]' => '2,600.00',
                    '[Attendance]' => '3',
                ],
                'required' => [],
            ],
        ];
    }

    public static function definition(string $key): array
    {
        $definitions = self::definitions();

        if (!isset($definitions[$key])) {
            throw new \InvalidArgumentException("Unknown SMS template [{$key}].");
        }

        return $definitions[$key];
    }

    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /** Saved text, or the default. */
    public static function body(string $key): string
    {
        $saved = self::saved()[$key] ?? null;

        return is_string($saved) && trim($saved) !== '' ? $saved : self::definition($key)['default'];
    }

    public static function isCustom(string $key): bool
    {
        $saved = self::saved()[$key] ?? null;

        return is_string($saved) && trim($saved) !== '';
    }

    /**
     * Final SMS text for one parent.
     *
     * @param  array<string, string|int|float|null>  $values  '[Placeholder]' => value
     */
    public static function render(string $key, array $values, ?string $template = null): string
    {
        $definition = self::definition($key);
        $template = $template ?? self::body($key);
        $values = array_map(fn ($value) => trim((string) $value), $values);

        foreach ($definition['required'] as $placeholder => $rule) {
            if (!str_contains($template, $placeholder) && ($values[$placeholder] ?? '') !== '') {
                $template = self::insertLine($template, $rule['line'], $rule['after']);
            }
        }

        $lines = [];

        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $template)) as $line) {
            preg_match_all('/\[[A-Za-z ]+\]/', $line, $found);

            $empty = collect($found[0])->contains(
                fn ($placeholder) => array_key_exists($placeholder, $values) && $values[$placeholder] === ''
            );

            if (!$empty) {
                $lines[] = strtr($line, $values);
            }
        }

        return SmsText::english(implode("\n", $lines));
    }

    /** Preview with the sample values. */
    public static function preview(string $key, ?string $template = null): string
    {
        return self::render($key, self::definition($key)['placeholders'], $template);
    }

    /**
     * Problems with a text before it is saved.
     *
     * @return array<int, string>
     */
    public static function problems(string $key, string $template): array
    {
        $definition = self::definition($key);
        $problems = [];

        if (trim($template) === '') {
            $problems[] = 'The text cannot be empty.';

            return $problems;
        }

        if (SmsText::hasNonEnglish($template)) {
            $problems[] = 'Use English letters only (Sinhala / Tamil SMS are not sent).';
        }

        preg_match_all('/\[[^\]\n]*\]/', $template, $found);
        $unknown = array_diff(array_unique($found[0]), array_keys($definition['placeholders']));

        if ($unknown) {
            $problems[] = 'Unknown placeholder(s): ' . implode(', ', $unknown) . '.';
        }

        foreach (array_keys($definition['required']) as $placeholder) {
            if (!str_contains($template, $placeholder)) {
                $problems[] = "{$placeholder} must be in the text.";
            }
        }

        return $problems;
    }

    /* ------------------------------------------------------------------ */
    /* Saving                                                              */
    /* ------------------------------------------------------------------ */

    /** null / default text = use the default. */
    public static function save(string $key, ?string $body, ?int $userId = null): void
    {
        self::definition($key);

        $body = $body === null ? '' : str_replace(["\r\n", "\r"], "\n", trim($body));

        if ($body === '' || $body === self::definition($key)['default']) {
            SmsTemplate::query()->where('key', $key)->delete();
        } else {
            SmsTemplate::query()->updateOrCreate(['key' => $key], ['body' => $body, 'updated_by' => $userId]);
        }

        self::clearCache();
    }

    public static function costPerSms(): float
    {
        try {
            $value = Cache::rememberForever(self::COST_CACHE_KEY, fn () => SystemSetting::query()
                ->where('key', self::COST_KEY)
                ->value('value'));
        } catch (Throwable $e) {
            $value = null;
        }

        return is_numeric($value) && (float) $value >= 0 ? round((float) $value, 2) : self::DEFAULT_COST;
    }

    public static function setCostPerSms(float $cost): void
    {
        SystemSetting::query()->updateOrCreate(['key' => self::COST_KEY], ['value' => (string) round($cost, 2)]);
        Cache::forget(self::COST_CACHE_KEY);
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, string> */
    protected static function saved(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => SmsTemplate::query()->pluck('body', 'key')->all());
        } catch (Throwable $e) {
            // e.g. before the migration: the defaults are used
            Log::warning('SMS templates could not be read: ' . $e->getMessage());

            return [];
        }
    }

    /** Put a required line after the line that has $after (or at the end, before "Thank you"). */
    protected static function insertLine(string $template, string $line, string $after): string
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $template));

        if ($after !== '') {
            foreach ($lines as $i => $text) {
                if (str_contains($text, $after)) {
                    array_splice($lines, $i + 1, 0, [$line]);

                    return implode("\n", $lines);
                }
            }
        }

        $last = count($lines) - 1;
        if ($last > 0 && stripos($lines[$last], 'thank') !== false) {
            array_splice($lines, $last, 0, [$line]);
        } else {
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }
}
