<?php

namespace App\Http\Middleware;

use App\Models\StudentPortalLogin;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Parent API (/api/parent/v1/*) authentication.
 *
 * With "Authorization: Bearer <token>" (from /auth/login) the student comes
 * from the token: student_id in the request is replaced, so one parent can
 * never read another student's data.
 *
 * Without a token the request is allowed only while
 * PARENT_API_REQUIRE_TOKEN=false (older parent apps).
 */
class AuthenticateParentApi
{
    public function handle(Request $request, Closure $next)
    {
        $plain = $request->bearerToken();

        if ($plain) {
            $token = PersonalAccessToken::findToken($plain);
            $login = $token ? $token->tokenable : null;

            if (!$token || !$login instanceof StudentPortalLogin || !$login->is_active || $this->expired($token)) {
                return $this->unauthorized('Your session has expired. Please log in again.');
            }

            if (!$token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
                $token->forceFill(['last_used_at' => now()])->save();
            }

            // The token decides the student - never the request body / query.
            $request->query->remove('student_id');
            $request->merge(['student_id' => (int) $login->student_id]);
            $request->attributes->set('parent_login', $login);
            $request->attributes->set('parent_token', $token);

            return $next($request);
        }

        if (config('parent_api.require_token')) {
            return $this->unauthorized('Please log in again.');
        }

        $response = $next($request);
        $response->headers->set('X-Parent-Api-Warning', 'token-missing');

        return $response;
    }

    protected function expired(PersonalAccessToken $token): bool
    {
        $days = (int) config('parent_api.token_days', 90);

        if ($days <= 0) {
            return false;
        }

        $lastActive = $token->last_used_at ?? $token->created_at;

        return $lastActive !== null && $lastActive->lt(now()->subDays($days));
    }

    protected function unauthorized(string $message): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message], 401);
    }
}
