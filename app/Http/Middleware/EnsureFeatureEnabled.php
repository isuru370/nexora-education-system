<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;

/**
 * ->middleware('feature:courses')
 *
 * When the feature is off for this institute the page / endpoint does not
 * exist: 404 (JSON for API calls).
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        if (Features::enabled($feature)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'This feature is not available.',
            ], 404);
        }

        abort(404);
    }
}
