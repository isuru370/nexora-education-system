<?php

namespace App\Support;

/**
 * SMS text rules. The institute sends English SMS only (one SMS = 160
 * characters; Sinhala / Tamil would make it 70 and cost more).
 *
 *   SmsText::english($text)  text safe to send as a plain (GSM) SMS
 *   SmsText::length($text)   characters the provider counts
 *   SmsText::parts($text)    how many SMS one message costs
 *
 * resources/views/setting/sms-templates.blade.php has the same rules in JS.
 */
class SmsText
{
    public const SINGLE = 160;
    public const PART = 153;

    /** Count as 2 characters in a GSM SMS */
    public const EXTENDED = ['^', '{', '}', '\\', '[', ']', '~', '|'];

    /** Typing / copy-paste characters with a plain English form */
    protected const REPLACE = [
        "\r\n" => "\n",
        "\r" => "\n",
        "\t" => ' ',
        "\u{00A0}" => ' ',
        "\u{2018}" => "'",
        "\u{2019}" => "'",
        "\u{201A}" => "'",
        "\u{201C}" => '"',
        "\u{201D}" => '"',
        "\u{201E}" => '"',
        "\u{2013}" => '-',
        "\u{2014}" => '-',
        "\u{2022}" => '-',
        "\u{2026}" => '...',
        '`' => "'",
    ];

    /**
     * Plain English text: typographic characters become plain ones, other
     * letters (Sinhala, Tamil, emoji ...) are removed.
     */
    public static function english(string $text): string
    {
        $text = strtr($text, self::REPLACE);
        $text = preg_replace('/[^\x20-\x7E\n]/u', '', $text) ?? '';
        $text = preg_replace('/[ ]{2,}/', ' ', $text) ?? $text;
        $text = preg_replace('/ +\n/', "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /** True when the text has letters that are not English (would be removed). */
    public static function hasNonEnglish(string $text): bool
    {
        return (bool) preg_match('/[^\x20-\x7E\n]/u', strtr($text, self::REPLACE));
    }

    public static function length(string $text): int
    {
        $length = strlen($text);

        foreach (self::EXTENDED as $char) {
            $length += substr_count($text, $char);
        }

        return $length;
    }

    public static function parts(string $text): int
    {
        $length = self::length($text);

        if ($length === 0) {
            return 0;
        }

        return $length <= self::SINGLE ? 1 : (int) ceil($length / self::PART);
    }
}
