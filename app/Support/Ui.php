<?php

namespace App\Support;

/**
 * Bootstrap colour classes for names that views build from data
 * (bg-{{ $color }}), as the Tailwind classes the pages use now.
 * The class names are written out here so the CSS build finds them.
 *
 *   <span class="ui-badge {{ \App\Support\Ui::bg($color) }}">
 *   inside .dashboard-page (nx theme colours for badges): Ui::bg($color, nx: true)
 */
final class Ui
{
    private const BG = [
        'primary' => 'tw:bg-bs-primary!',
        'secondary' => 'tw:bg-bs-secondary!',
        'success' => 'tw:bg-bs-success!',
        'danger' => 'tw:bg-bs-danger!',
        'warning' => 'tw:bg-bs-warning!',
        'info' => 'tw:bg-bs-info!',
        'light' => 'tw:bg-bs-light!',
        'dark' => 'tw:bg-bs-dark!',
        'white' => 'tw:bg-bs-white!',
    ];

    // the old nx theme changed these badge colours inside .dashboard-page
    private const NX_BADGE = [
        'primary' => 'tw:[background:var(--nx-blue)]!',
        'secondary' => 'tw:[background:var(--nx-muted)]!',
        'success' => 'tw:[background:var(--nx-green)]!',
        'danger' => 'tw:[background:var(--nx-red)]!',
        'warning' => 'tw:[background:var(--nx-amber)]! tw:text-white!',
    ];

    /** bg-<color>: Tailwind class + bs-bg-<color> (page CSS may select on it). */
    public static function bg(?string $color, bool $nx = false): string
    {
        $color = (string) $color;
        $class = ($nx ? self::NX_BADGE[$color] ?? null : null) ?? self::BG[$color] ?? '';

        return trim($class . ' bs-bg-' . $color);
    }
}
