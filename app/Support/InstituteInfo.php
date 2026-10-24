<?php

namespace App\Support;

/**
 * This institute's name / tagline / logo, from .env (INSTITUTE_*).
 * Used by the staff app (GET /api/institute) and the Parent Hub.
 */
class InstituteInfo
{
    public static function code(): ?string
    {
        return config('services.institute.code') ?: null;
    }

    public static function name(): string
    {
        return (string) (config('services.institute.name') ?: config('app.name'));
    }

    public static function tagline(): ?string
    {
        return config('services.institute.tagline') ?: null;
    }

    /**
     * "Mihisara Education Institute" -> "MihisaraEdu"
     * (INSTITUTE_SHORT_NAME wins when it is set).
     */
    public static function shortName(): string
    {
        $set = trim((string) config('services.institute.short_name'));

        if ($set !== '') {
            return $set;
        }

        $first = preg_split('/\s+/', trim(self::name()))[0] ?? '';
        $first = preg_replace('/[^\pL\pN]/u', '', $first);

        return $first === '' ? 'NexoraEdu' : ucfirst(mb_strtolower($first)) . 'Edu';
    }

    /** Full URL of the logo, or null. */
    public static function logoUrl(): ?string
    {
        $logo = trim((string) config('services.institute.logo'));

        if ($logo === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $logo)) {
            return $logo;
        }

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($logo, '/');
    }

    public static function toArray(): array
    {
        return [
            'code' => self::code(),
            'name' => self::name(),
            'short_name' => self::shortName(),
            'tagline' => self::tagline(),
            'logo_url' => self::logoUrl(),
            'app_url' => rtrim((string) config('app.url'), '/'),
        ];
    }
}
