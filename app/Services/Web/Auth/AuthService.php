<?php

namespace App\Services\Web\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * Login user using web session authentication.
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
            ];
        }

        $user = Auth::user();

        if (!$user instanceof User) {
            Auth::logout();

            return [
                'success' => false,
                'message' => 'Unable to authenticate user.',
            ];
        }

        if (!$user->is_active) {
            Auth::logout();

            return [
                'success' => false,
                'message' => 'Your account is inactive.',
            ];
        }

        /*
         * Regenerate session after successful authentication
         * to prevent session fixation.
         */
        request()->session()->regenerate();

        return [
            'success' => true,
            'user' => $user,
        ];
    }

    /**
     * Logout the authenticated web user.
     */
    public function logout(): void
    {
        Auth::logout();
    }
}