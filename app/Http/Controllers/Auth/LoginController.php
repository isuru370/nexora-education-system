<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Show login page.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);

        $result = $this->authService->login($credentials);

        if (!$result['success']) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => $result['message'],
                ]);
        }

        return redirect()->intended(
            route('dashboard')
        );
    }

    /**
     * Logout authenticated user.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}