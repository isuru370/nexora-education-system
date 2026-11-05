<?php

namespace App\Support;

/**
 * What a scanned student card QR means.
 *
 * Old cards (Success Academy's previous system) hold JSON, e.g.
 *   {"std_ID":"SA11045"}
 * Newer cards hold the Student ID / card code itself. clean() turns both
 * into the plain code ("SA11045"); anything else is returned unchanged.
 */
class ScannedCode
{
    /** JSON keys the old cards use for the Student ID (case does not matter) */
    public const KEYS = ['std_id', 'student_id', 'stdid', 'custom_id', 'id', 'code', 'qr_code'];

    public static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim($value);

        if ($text === '' || $text[0] !== '{') {
            return $value;
        }

        $data = json_decode($text, true);

        if (is_array($data)) {
            $lower = array_change_key_case($data, CASE_LOWER);

            foreach (self::KEYS as $key) {
                if (isset($lower[$key]) && is_scalar($lower[$key]) && trim((string) $lower[$key]) !== '') {
                    return trim((string) $lower[$key]);
                }
            }

            return $value;
        }

        // Not valid JSON (e.g. cut or with odd quotes): {"std_ID":"SA11045"
        if (preg_match('/std_?id"?\s*:\s*"?([A-Za-z0-9_\-\/]+)/i', $text, $m)) {
            return $m[1];
        }

        return $value;
    }
}
