<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Api\Auth\MobileSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Mobile app login (Sanctum token based).
 *
 * Web login is handled by App\Http\Controllers\Web\Auth\LoginController (session based).
 * The API has no session, so AuthService::login() cannot be used here.
 *
 * Response (login and /me):
 *   user      - the user, with user_type (the app reads user.user_type.code)
 *   role      - SUPER_ADMIN | ADMIN | BRANCH_ADMIN | USER | TEACHER
 *   home      - "staff" or "teacher" (which part of the app to open)
 *   branches  - branches the user can work in
 *   teacher   - teacher profile (only for TEACHER)
 */
class LoginController extends Controller
{
    public function __construct(
        protected MobileSessionService $sessionService
    ) {}

    /**
     * POST /api/login  { email, password, device_name? }
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->with('userType')
            ->where('email', $validated['email'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Inactive account / role, no teacher profile, no branch ...
        if ($error = $this->sessionService->loginError($user)) {
            return response()->json([
                'status' => 'error',
                'message' => $error,
            ], 403);
        }

        $token = $user
            ->createToken($validated['device_name'] ?? 'mobile-token')
            ->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token,
        ] + $this->sessionService->payload($user), 200);
    }

    /**
     * GET /api/me - the app calls this on start to check the saved token
     * and get the latest role / branches.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('userType');

        if ($error = $this->sessionService->loginError($user)) {
            return response()->json([
                'status' => 'error',
                'message' => $error,
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User loaded successfully.',
        ] + $this->sessionService->payload($user), 200);
    }

    /**
     * POST /api/logout - deletes the current token (any role).
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        // Only real personal access tokens can be deleted
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful',
        ], 200);
    }
}