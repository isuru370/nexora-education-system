<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Show login page.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle web login (session-based).
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->loginWithSession($validated);

        if (!$result['success']) {
            return back()
                ->withErrors(['email' => $result['message']])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Handle web logout.
     */
    public function logout(Request $request)
    {
        $this->authService->logoutSession($request->user());

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}