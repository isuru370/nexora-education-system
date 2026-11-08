<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Forgot password: email -> 6-digit OTP (10 min) -> new password.
 *
 * The step is kept in the session (reset_email, otp_verified_email), so a
 * wrong OTP or a password mismatch stays on the same step. 5 wrong OTPs
 * cancel the code. The new password can be set only from the browser that
 * verified the OTP. After a reset the user's app logins (API tokens) end.
 */
class ForgotPasswordController extends Controller
{
    /** Wrong OTP tries before the code is cancelled. */
    public const MAX_OTP_ATTEMPTS = 5;

    public function index(Request $request)
    {
        // "Start again"
        if ($request->boolean('restart')) {
            $this->clearFlow($request);

            return redirect()->route('forgot_password.form');
        }

        return view('forgot-password.index');
    }

    protected function clearFlow(Request $request): void
    {
        $request->session()->forget(['reset_email', 'otp_verified_email']);
    }

    protected function otpAttemptsKey(string $email): string
    {
        return 'password-reset-verify:' . $email;
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $email = strtolower(trim($request->email));
        $rateKey = 'password-reset-send:' . $email;

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return back()
                ->withErrors([
                    'email' => 'Too many OTP requests. Please try again later.',
                ])
                ->withInput();
        }

        RateLimiter::hit($rateKey, 600);

        try {
            $user = User::where('email', $email)->firstOrFail();

            PasswordResetOtp::where('email', $email)
                ->whereNull('verified_at')
                ->delete();

            $otp = (string) random_int(100000, 999999);

            PasswordResetOtp::create([
                'email'      => $email,
                'otp'        => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
            ]);

            Mail::to($user->email)->send(new PasswordResetMail($otp, $user->name));

            RateLimiter::clear($this->otpAttemptsKey($email));
            $request->session()->put('reset_email', $email);
            $request->session()->forget('otp_verified_email');

            return redirect()
                ->route('forgot_password.form')
                ->with('success', 'OTP has been sent to your email address.');
        } catch (Throwable $e) {
            Log::error('Password reset OTP send failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'email' => 'Unable to send OTP right now.',
                ])
                ->withInput();
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp'   => ['required', 'digits:6'],
        ]);

        $email = strtolower(trim($request->email));

        try {
            $record = PasswordResetOtp::where('email', $email)
                ->latest()
                ->first();

            if (!$record) {
                return back()
                    ->withErrors(['otp' => 'OTP not found.'])
                    ->withInput();
            }

            if ($record->isExpired()) {
                return back()
                    ->withErrors(['otp' => 'OTP has expired.'])
                    ->withInput();
            }

            $attemptsKey = $this->otpAttemptsKey($email);

            if (RateLimiter::tooManyAttempts($attemptsKey, self::MAX_OTP_ATTEMPTS)) {
                $record->delete();

                return back()
                    ->withErrors(['otp' => 'Too many wrong OTPs. Please request a new OTP.']);
            }

            if (!Hash::check($request->otp, $record->otp)) {
                RateLimiter::hit($attemptsKey, 600);
                $left = self::MAX_OTP_ATTEMPTS - RateLimiter::attempts($attemptsKey);

                if ($left <= 0) {
                    $record->delete();

                    return back()
                        ->withErrors(['otp' => 'Too many wrong OTPs. Please request a new OTP.']);
                }

                return back()
                    ->withErrors(['otp' => "Invalid OTP. {$left} " . ($left === 1 ? 'try' : 'tries') . ' left.']);
            }

            RateLimiter::clear($attemptsKey);

            $record->update([
                'verified_at' => now(),
            ]);

            $request->session()->put('reset_email', $email);
            $request->session()->put('otp_verified_email', $email);

            return redirect()
                ->route('forgot_password.form')
                ->with('success', 'OTP verified successfully.');
        } catch (Throwable $e) {
            Log::error('OTP verification failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['otp' => 'Unable to verify OTP right now.'])
                ->withInput();
        }
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $email = strtolower(trim($request->email));
        $rateKey = 'password-reset-send:' . $email;

        // same limit as "Send OTP": 3 emails in 10 minutes
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return back()
                ->withErrors([
                    'otp' => 'Too many OTP requests. Please try again later.',
                ]);
        }

        RateLimiter::hit($rateKey, 600);

        try {
            $user = User::where('email', $email)->firstOrFail();

            PasswordResetOtp::where('email', $email)
                ->whereNull('verified_at')
                ->delete();

            $otp = (string) random_int(100000, 999999);

            PasswordResetOtp::create([
                'email'      => $email,
                'otp'        => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
            ]);

            Mail::to($user->email)->send(new PasswordResetMail($otp, $user->name));

            RateLimiter::clear($this->otpAttemptsKey($email));
            $request->session()->put('reset_email', $email);
            $request->session()->forget('otp_verified_email');

            return redirect()
                ->route('forgot_password.form')
                ->with('success', 'OTP has been resent to your email address.');
        } catch (Throwable $e) {
            Log::error('Password reset OTP resend failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'email' => 'Unable to resend OTP right now.',
                ])
                ->withInput();
        }
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower(trim($request->email));

        // Only the browser that verified the OTP can set the new password
        if ($request->session()->get('otp_verified_email') !== $email) {
            return back()
                ->withErrors([
                    'email' => 'OTP verification required.',
                ]);
        }

        try {
            $otpRecord = PasswordResetOtp::where('email', $email)
                ->latest()
                ->first();

            if (!$otpRecord || !$otpRecord->isVerified()) {
                return back()
                    ->withErrors([
                        'email' => 'OTP verification required.',
                    ])
                    ->withInput();
            }

            if ($otpRecord->isExpired()) {
                return back()
                    ->withErrors([
                        'email' => 'OTP session expired.',
                    ])
                    ->withInput();
            }

            $user = User::where('email', $email)->firstOrFail();

            $user->update([
                'password' => $request->password,
            ]);

            // Logged-in phones (staff app) must sign in with the new password
            $user->tokens()->delete();

            PasswordResetOtp::where('email', $email)->delete();
            $this->clearFlow($request);

            return redirect()
                ->route('login')
                ->with('success', 'Password updated successfully.');
        } catch (Throwable $e) {
            Log::error('Password update failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'password' => 'Unable to update password.',
                ])
                ->withInput();
        }
    }
}
