<?php

namespace App\Http\Middleware;

use App\Support\CardMode;
use Closure;
use Illuminate\Http\Request;

/**
 * ->middleware('card.mode:temporary')
 *
 * The page / endpoint only works when this institute uses that card mode
 * (php artisan nexora:card-mode). Otherwise: 403 JSON for the app, or back
 * to the students list with a message on the web.
 */
class EnsureCardMode
{
    public function handle(Request $request, Closure $next, string $mode)
    {
        if (CardMode::is($mode)) {
            return $next($request);
        }

        $message = $mode === CardMode::TEMPORARY
            ? 'This institute does not use temporary ID cards. Register the student with a permanent ID card.'
            : 'This institute registers students with a temporary ID card first.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => $message,
                'card_mode' => CardMode::get(),
            ], 403);
        }

        return redirect()->route('students.index')->with('error', $message);
    }
}
