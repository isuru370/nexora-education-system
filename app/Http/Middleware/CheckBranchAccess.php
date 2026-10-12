<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        /*
        |--------------------------------------------------------------------------
        | User Status
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {
            abort(403, 'Your account is inactive.');
        }

        /*
        |--------------------------------------------------------------------------
        | User Role
        |--------------------------------------------------------------------------
        */

        if (!$user->userType) {
            abort(403, 'User role not found.');
        }

        if (!$user->userType->is_active) {
            abort(403, 'User role is inactive.');
        }

        $role = strtoupper($user->userType->code);

        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN / ADMIN
        |--------------------------------------------------------------------------
        |
        | These roles have access to all active branches.
        |
        */

        if (in_array($role, ['SUPER_ADMIN', 'ADMIN'], true)) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Branch ID
        |--------------------------------------------------------------------------
        |
        | Branch ID can come from:
        |
        | 1. Route parameter: /branches/{branch}
        | 2. Request input: branch_id
        |
        */

        $branchId = $request->route('branch');

        if (!$branchId) {
            $branchId = $request->input('branch_id');
        }

        /*
        |--------------------------------------------------------------------------
        | Branch Required
        |--------------------------------------------------------------------------
        */

        if (!$branchId) {
            abort(403, 'Branch is required.');
        }

        $branchId = (int) $branchId;

        /*
        |--------------------------------------------------------------------------
        | BRANCH ADMIN / USER
        |--------------------------------------------------------------------------
        |
        | Access is controlled through branch_users.
        |
        */

        if (in_array($role, ['BRANCH_ADMIN', 'USER'], true)) {
            $hasAccess = $user->branches()
                ->where('branches.id', $branchId)
                ->where('branches.is_active', true)
                ->exists();

            if (!$hasAccess) {
                abort(
                    403,
                    'You do not have access to this branch.'
                );
            }

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | TEACHER
        |--------------------------------------------------------------------------
        |
        | Access is controlled through teacher_branches.
        |
        */

        if ($role === 'TEACHER') {
            $teacher = $user->teacher;

            if (!$teacher) {
                abort(403, 'Teacher profile not found.');
            }

            $hasAccess = $teacher->branches()
                ->where('branches.id', $branchId)
                ->where('branches.is_active', true)
                ->exists();

            if (!$hasAccess) {
                abort(
                    403,
                    'You do not have access to this branch.'
                );
            }

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Unknown / Unsupported Role
        |--------------------------------------------------------------------------
        */

        abort(403, 'Branch access denied.');
    }
}
