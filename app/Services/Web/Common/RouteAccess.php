<?php

namespace App\Services\Web\Common;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Can the logged-in user open a route?
 *
 * Reads the route's "role:..." middleware, so views (dashboards, menus)
 * always follow the same rules as routes/web.php. No separate permission list.
 *
 *   @if (\App\Services\Web\Common\RouteAccess::can('students.index'))
 *       <a href="{{ route('students.index') }}">Students</a>
 *   @endif
 *
 * Same rules as CheckUserActive + CheckUserRole middleware:
 *  - user and user type must be active
 *  - SUPER_ADMIN can open everything
 *  - every "role:" middleware on the route (group + route) must allow the role
 *  - a route name that does not exist returns false (no "Route not defined" crash)
 */
class RouteAccess
{
    public static function can(string $routeName): bool
    {
        $user = auth()->user();

        if (!$user instanceof User || !$user->is_active) {
            return false;
        }

        $userType = $user->userType;

        if (!$userType || !$userType->is_active) {
            return false;
        }

        $route = Route::getRoutes()->getByName($routeName);

        if (!$route) {
            return false;
        }

        $role = strtoupper($userType->code);

        if ($role === 'SUPER_ADMIN') {
            return true;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (!is_string($middleware) || !str_starts_with($middleware, 'role:')) {
                continue;
            }

            $allowedRoles = array_map(
                fn ($item) => strtoupper(trim($item)),
                explode(',', substr($middleware, strlen('role:')))
            );

            if (!in_array($role, $allowedRoles, true)) {
                return false;
            }
        }

        return true;
    }
}
