<?php

namespace App\Http\Middleware;

use App\Support\TeacherAppRules;
use Closure;
use Illuminate\Http\Request;

/** route middleware "teacher.rule:attendance" - 403 while the setting is off */
class TeacherAppRule
{
    public function handle(Request $request, Closure $next, string $rule)
    {
        if (!TeacherAppRules::allows($rule)) {
            return response()->json([
                'success' => false,
                'message' => 'Your institute has not turned this on for teachers.',
            ], 403);
        }

        return $next($request);
    }
}
