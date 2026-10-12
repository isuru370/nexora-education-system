<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * Examples:
     *
     * ->middleware('role:ADMIN')
     * ->middleware('role:ADMIN,SUPER_ADMIN')
     * ->middleware('role:ADMIN,USER,BRANCH_ADMIN')
     * ->middleware('role:TEACHER')
     */
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        if (!Auth::check()) {
            abort(401, 'Unauthenticated.');
        }

        $user = Auth::user();


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


        /*
        |--------------------------------------------------------------------------
        | Role Status
        |--------------------------------------------------------------------------
        */

        if (!$user->userType->is_active) {
            abort(403, 'User role is inactive.');
        }


        /*
        |--------------------------------------------------------------------------
        | Current Role
        |--------------------------------------------------------------------------
        */

        $currentRole = strtoupper(
            $user->userType->code
        );


        /*
        |--------------------------------------------------------------------------
        | SUPER ADMIN
        |--------------------------------------------------------------------------
        |
        | SUPER_ADMIN has full system access.
        |
        | Therefore SUPER_ADMIN does not need to be included
        | in every role middleware definition.
        |
        */

        if ($currentRole === 'SUPER_ADMIN') {
            return $next($request);
        }


        /*
        |--------------------------------------------------------------------------
        | Requested Roles
        |--------------------------------------------------------------------------
        */

        $allowedRoles = array_map(
            'strtoupper',
            $roles
        );


        /*
        |--------------------------------------------------------------------------
        | Role Authorization
        |--------------------------------------------------------------------------
        */

        if (!in_array(
            $currentRole,
            $allowedRoles,
            true
        )) {
            abort(403, 'You do not have permission to access this resource.');
        }


        /*
        |--------------------------------------------------------------------------
        | Continue Request
        |--------------------------------------------------------------------------
        */

        return $next($request);
    }
}