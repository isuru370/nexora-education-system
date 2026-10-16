<?php

namespace App\Services\Api\Auth;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * What the mobile app needs to know about the logged-in user:
 * role, which part of the app to open (home) and the branches they can work in.
 *
 * Used by POST /api/login and GET /api/me so both always return the same shape.
 */
class MobileSessionService
{
    /** Roles that open the staff part of the app (dashboard, scan, payments ...) */
    public const STAFF_ROLES = ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'];

    /** Roles that see every active branch */
    public const ALL_BRANCH_ROLES = ['SUPER_ADMIN', 'ADMIN'];

    public const TEACHER_ROLE = 'TEACHER';

    public function role(User $user): string
    {
        return strtoupper((string) optional($user->userType)->code);
    }

    /**
     * Reason this user cannot use the mobile app, or null when they can.
     */
    public function loginError(User $user): ?string
    {
        if (!$user->is_active) {
            return 'Your account is inactive.';
        }

        $type = $user->userType;

        if (!$type) {
            return 'User role not found. Please contact the administrator.';
        }

        if (!$type->is_active) {
            return 'Your user role is inactive. Please contact the administrator.';
        }

        $role = $this->role($user);

        if ($role === self::TEACHER_ROLE) {
            $teacher = $user->teacher;

            if (!$teacher) {
                return 'Teacher profile not found. Please contact the administrator.';
            }

            if (!$teacher->is_active) {
                return 'Your teacher account is inactive.';
            }

            return null;
        }

        if (!in_array($role, self::STAFF_ROLES, true)) {
            return 'This account cannot use the mobile app.';
        }

        if ($this->branches($user)->isEmpty()) {
            return 'No branch is assigned to your account. Please contact the administrator.';
        }

        return null;
    }

    /**
     * Same data for login and /me.
     */
    public function payload(User $user): array
    {
        $user->loadMissing('userType');

        $role = $this->role($user);
        $branches = $this->branches($user);
        $teacher = $role === self::TEACHER_ROLE ? $user->teacher : null;

        return [
            // "user.user_type.code" is what the current app reads to pick the dashboard
            'user' => $user,
            'role' => $role,
            'home' => $role === self::TEACHER_ROLE ? 'teacher' : 'staff',
            'all_branches' => in_array($role, self::ALL_BRANCH_ROLES, true),
            'branches' => $branches
                ->map(fn (Branch $branch) => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                ])
                ->values(),
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'custom_id' => $teacher->custom_id,
                'full_name' => $teacher->full_name,
            ] : null,
        ];
    }

    /**
     * Active branches the user can work in (same rules as BranchAccessService).
     */
    public function branches(User $user): Collection
    {
        $role = $this->role($user);

        $query = Branch::query()->where('is_active', true)->orderBy('name');

        if (in_array($role, self::ALL_BRANCH_ROLES, true)) {
            return $query->get(['id', 'name', 'code']);
        }

        if ($role === self::TEACHER_ROLE) {
            $branchIds = optional($user->teacher)->branches()->pluck('branches.id') ?? collect();
        } else {
            $branchIds = $user->branches()->pluck('branches.id');
        }

        return $query->whereIn('id', $branchIds)->get(['id', 'name', 'code']);
    }
}