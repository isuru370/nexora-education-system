<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Who may do what with student ID cards (web pages and the staff app).
 *
 *   generate   create new permanent / temporary cards     SUPER_ADMIN
 *   download   PDF / print / preview sheets              SUPER_ADMIN, ADMIN, BRANCH_ADMIN
 *   manage     replace, lost, damaged, deactivate,       SUPER_ADMIN, ADMIN, BRANCH_ADMIN
 *              temporary card status
 *   assign     give a card to a student (registration,   + USER (cashier)
 *              Re Assign Student)
 *   view       card lists / details                      + USER (cashier)
 *
 * Teachers have no access to ID cards.
 *
 * The *_ROLES strings go straight into ->middleware('role:...').
 */
class CardAccess
{
    public const GENERATE_ROLES = 'SUPER_ADMIN';
    public const DOWNLOAD_ROLES = 'SUPER_ADMIN,ADMIN,BRANCH_ADMIN';
    public const MANAGE_ROLES = 'SUPER_ADMIN,ADMIN,BRANCH_ADMIN';
    public const ASSIGN_ROLES = 'SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER';
    public const VIEW_ROLES = 'SUPER_ADMIN,ADMIN,BRANCH_ADMIN,USER';

    public static function canGenerate(): bool
    {
        return self::has(self::GENERATE_ROLES);
    }

    public static function canDownload(): bool
    {
        return self::has(self::DOWNLOAD_ROLES);
    }

    public static function canManage(): bool
    {
        return self::has(self::MANAGE_ROLES);
    }

    public static function canAssign(): bool
    {
        return self::has(self::ASSIGN_ROLES);
    }

    public static function canView(): bool
    {
        return self::has(self::VIEW_ROLES);
    }

    /** @cardaccess('generate') in Blade */
    public static function can(string $action): bool
    {
        return match ($action) {
            'generate' => self::canGenerate(),
            'download' => self::canDownload(),
            'manage' => self::canManage(),
            'assign' => self::canAssign(),
            'view' => self::canView(),
            default => false,
        };
    }

    /** For the app: what this role may do with cards. */
    public static function forRole(string $role): array
    {
        $role = strtoupper($role);
        $in = fn (string $roles) => in_array($role, explode(',', $roles), true);

        return [
            'generate' => $in(self::GENERATE_ROLES),
            'download' => $in(self::DOWNLOAD_ROLES),
            'manage' => $in(self::MANAGE_ROLES),
            'assign' => $in(self::ASSIGN_ROLES),
            'view' => $in(self::VIEW_ROLES),
        ];
    }

    protected static function has(string $roles): bool
    {
        $role = strtoupper((string) optional(optional(Auth::user())->userType)->code);

        return $role !== '' && in_array($role, explode(',', $roles), true);
    }
}
