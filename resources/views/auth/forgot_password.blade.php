<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password - {{ config('app.name', 'Nexora Education') }}</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           Forgot Password — Nexora theme aligned
           ============================================================ */

        :root {
            --nx-login-navy: #0f172a;
            --nx-login-blue: #1d4ed8;
            --nx-login-blue-light: #3b82f6;
            --nx-login-text: #f1f5f9;
            --nx-login-muted: rgba(255, 255, 255, .72);
            --nx-login-border: rgba(255, 255, 255, .12);
            --nx-login-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            min-height: 100vh;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(29, 78, 216, .28), transparent 40%),
                radial-gradient(circle at bottom right, rgba(59, 130, 246, .18), transparent 40%),
                linear-gradient(135deg, #020617, #0f172a, #1e3a8a);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
            color: var(--nx-login-text);
            padding: 20px 0;
        }

        /* FLOATING BACKGROUND */
        .floating-bg {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, .05);
            animation: float 10s infinite ease-in-out;
        }

        .floating-circle:nth-child(1) {
            width: 240px;
            height: 240px;
            top: -60px;
            left: -60px;
        }
        .floating-circle:nth-child(2) {
            width: 180px;
            height: 180px;
            bottom: -50px;
            right: -40px;
            animation-delay: 2s;
        }
        .floating-circle:nth-child(3) {
            width: 120px;
            height: 120px;
            top: 50%;
            left: 12%;
            animation-delay: 1s;
        }

        @keyframes float {
            0%   { transform: translateY(0); }
            50%  { transform: translateY(-25px); }
            100% { transform: translateY(0); }
        }

        /* WRAPPER */
        .fp-wrapper {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 5;
        }

        /* CARD */
        .fp-card {
            position: relative;
            overflow: hidden;
            border-radius: var(--nx-login-radius);
            background: rgba(255, 255, 255, .08);
            border: 1px solid var(--nx-login-border);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow:
                0 20px 50px rgba(0, 0, 0, .45),
                inset 0 1px 0 rgba(255, 255, 255, .08);
            color: var(--nx-login-text);
        }

        .fp-card::before {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            background: rgba(59, 130, 246, .14);
            border-radius: 50%;
            top: -120px;
            right: -120px;
            pointer-events: none;
        }

        /* HEADER */
        .fp-header {
            position: relative;
            padding: 40px 40px 20px;
            text-align: center;
        }

        .fp-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 18px;
            border-radius: var(--nx-login-radius);
            background: linear-gradient(135deg, var(--nx-login-blue), var(--nx-login-blue-light));
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 10px 25px rgba(29, 78, 216, .45);
        }

        .fp-icon i {
            font-size: 32px;
            color: #fff;
        }

        .fp-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -.3px;
            margin-bottom: 8px;
            color: #fff;
        }

        .fp-subtitle {
            color: var(--nx-login-muted);
            font-size: .85rem;
            margin: 0;
            line-height: 1.5;
        }

        /* BODY */
        .fp-body {
            padding: 15px 40px 40px;
        }

        /* STEPPER */
        .fp-stepper {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: .5rem;
            margin-bottom: 24px;
        }

        .fp-step {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 700;
            background: rgba(255, 255, 255, .08);
            border: 1px solid var(--nx-login-border);
            color: var(--nx-login-muted);
            transition: all .2s ease;
        }

        .fp-step.active {
            background: var(--nx-login-blue);
            border-color: var(--nx-login-blue);
            color: #fff;
            box-shadow: 0 4px 12px rgba(29, 78, 216, .35);
        }

        .fp-step.done {
            background: var(--nx-login-blue);
            border-color: var(--nx-login-blue);
            color: #fff;
        }

        .fp-step-line {
            width: 30px;
            height: 1px;
            background: var(--nx-login-border);
        }

        .fp-step-line.done {
            background: var(--nx-login-blue);
        }

        /* LABELS */
        .fp-label {
            display: block;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: .8rem;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        /* INPUTS */
        .fp-input-group {
            background: rgba(255, 255, 255, .07);
            border: 1px solid var(--nx-login-border);
            border-radius: var(--nx-login-radius);
            overflow: hidden;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .fp-input-group:focus-within {
            border-color: rgba(59, 130, 246, .7);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, .18);
        }

        .fp-input-group .input-group-text {
            background: transparent;
            border: none;
            color: #cbd5e1;
            padding-left: 18px;
            font-size: 1rem;
        }

        .fp-input {
            background: transparent !important;
            border: none !important;
            color: #fff !important;
            height: 52px;
            font-size: .92rem;
        }

        .fp-input::placeholder {
            color: rgba(255, 255, 255, .45);
        }

        .fp-input:focus {
            box-shadow: none !important;
        }

        .fp-input[readonly] {
            opacity: .75;
            cursor: not-allowed;
        }

        .fp-input:-webkit-autofill,
        .fp-input:-webkit-autofill:hover,
        .fp-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #fff;
            transition: background-color 9999s ease-in-out 0s;
        }

        /* BUTTONS */
        .btn-fp {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: var(--nx-login-radius);
            font-size: .92rem;
            font-weight: 700;
            transition: transform .15s ease, box-shadow .15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            color: #fff;
        }

        .btn-fp-primary {
            background: linear-gradient(135deg, var(--nx-login-blue), var(--nx-login-blue-light));
            box-shadow: 0 10px 25px rgba(29, 78, 216, .35);
        }
        .btn-fp-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(29, 78, 216, .45);
            color: #fff;
        }

        .btn-fp-success {
            background: linear-gradient(135deg, #047857, #10a37f);
            box-shadow: 0 10px 25px rgba(4, 120, 87, .35);
        }
        .btn-fp-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(4, 120, 87, .45);
            color: #fff;
        }

        .btn-fp-amber {
            background: rgba(255, 255, 255, .08);
            border: 1px solid var(--nx-login-border);
            color: #fbbf24;
        }
        .btn-fp-amber:hover {
            background: rgba(255, 255, 255, .14);
            color: #fbbf24;
        }

        /* ALERTS */
        .fp-alert {
            border-radius: var(--nx-login-radius);
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            font-size: .85rem;
            border: 1px solid transparent;
        }

        .fp-alert-success {
            background: rgba(4, 120, 87, .18);
            border-color: rgba(4, 120, 87, .35);
            color: #6ee7b7;
        }

        .fp-alert-danger {
            background: rgba(185, 28, 28, .18);
            border-color: rgba(185, 28, 28, .35);
            color: #fecaca;
        }

        .fp-alert ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        /* TIMER */
        .timer-text {
            font-size: .8rem;
            color: var(--nx-login-muted);
            text-align: center;
            display: block;
            margin-bottom: 1rem;
        }

        .timer-text strong {
            color: #fbbf24;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        /* HIDDEN */
        .hidden {
            display: none;
        }

        /* LINKS */
        .fp-link {
            color: #bfdbfe;
            text-decoration: none;
            transition: color .15s ease;
            font-size: .85rem;
        }

        .fp-link:hover {
            color: #fff;
            text-decoration: underline;
        }

        /* RESPONSIVE */
        @media (max-width: 576px) {
            .fp-header {
                padding: 32px 24px 16px;
            }

            .fp-body {
                padding: 15px 24px 32px;
            }

            .fp-title {
                font-size: 1.25rem;
            }

            .fp-icon {
                width: 64px;
                height: 64px;
            }

            .fp-icon i {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

    {{-- FLOATING BACKGROUND --}}
    <div class="floating-bg">
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
        <div class="floating-circle"></div>
    </div>

    {{-- CARD --}}
    <div class="fp-wrapper">
        <div class="fp-card">

            {{-- HEADER --}}
            <div class="fp-header">
                <div class="fp-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h1 class="fp-title">Forgot Password</h1>
                <p class="fp-subtitle">
                    @if ($step === 'email')
                        Enter your email to receive an OTP
                    @elseif ($step === 'otp')
                        Enter the OTP sent to your email
                    @else
                        Set a new password for your account
                    @endif
                </p>
            </div>

            {{-- BODY --}}
            <div class="fp-body">

                {{-- STEPPER --}}
                @php
                    $stepIndex = ['email' => 1, 'otp' => 2, 'reset' => 3][$step] ?? 1;
                @endphp

                <div class="fp-stepper">
                    <div class="fp-step {{ $stepIndex >= 1 ? 'active' : '' }} {{ $stepIndex > 1 ? 'done' : '' }}">1</div>
                    <div class="fp-step-line {{ $stepIndex > 1 ? 'done' : '' }}"></div>
                    <div class="fp-step {{ $stepIndex >= 2 ? 'active' : '' }} {{ $stepIndex > 2 ? 'done' : '' }}">2</div>
                    <div class="fp-step-line {{ $stepIndex > 2 ? 'done' : '' }}"></div>
                    <div class="fp-step {{ $stepIndex >= 3 ? 'active' : '' }}">3</div>
                </div>

                {{-- SUCCESS --}}
                @if (session('success'))
                    <div class="fp-alert fp-alert-success" role="alert">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        {{ session('success') }}
                    </div>
                @endif

                {{-- ERRORS --}}
                @if ($errors->any())
                    <div class="fp-alert fp-alert-danger" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- ===================================================== --}}
                {{-- STEP 1 — EMAIL --}}
                {{-- ===================================================== --}}
                @if ($step === 'email')
                    <form method="POST" action="{{ route('forgot_password.send_otp') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="fp-label" for="email">Email Address</label>
                            <div class="input-group fp-input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope-fill"></i>
                                </span>
                                <input type="email" name="email" id="email"
                                    class="form-control fp-input"
                                    placeholder="Enter your email"
                                    required
                                    value="{{ old('email') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn-fp btn-fp-primary">
                            <i class="bi bi-send-fill"></i> Send OTP
                        </button>
                    </form>
                @endif

                {{-- ===================================================== --}}
                {{-- STEP 2 — OTP --}}
                {{-- ===================================================== --}}
                @if ($step === 'otp')
                    <form method="POST" action="{{ route('forgot_password.verify_otp') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">

                        <div class="mb-3">
                            <label class="fp-label" for="otp_email">Email</label>
                            <div class="input-group fp-input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope-fill"></i>
                                </span>
                                <input type="email" id="otp_email"
                                    class="form-control fp-input"
                                    value="{{ $email }}"
                                    readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="fp-label" for="otp">Enter 6-Digit OTP</label>
                            <div class="input-group fp-input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-shield-lock-fill"></i>
                                </span>
                                <input type="text" name="otp" id="otp"
                                    class="form-control fp-input"
                                    maxlength="6"
                                    placeholder="Enter 6-digit code"
                                    required>
                            </div>
                        </div>

                        <div class="timer-text">
                            OTP expires in: <strong id="countdown">03:00</strong>
                        </div>

                        <button type="submit" class="btn-fp btn-fp-success mb-2">
                            <i class="bi bi-check-circle-fill"></i> Verify OTP
                        </button>
                    </form>

                    <form method="POST" action="{{ route('forgot_password.resend_otp') }}" id="resendForm" class="hidden">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <button type="submit" class="btn-fp btn-fp-amber">
                            <i class="bi bi-arrow-clockwise"></i> Resend OTP
                        </button>
                    </form>
                @endif

                {{-- ===================================================== --}}
                {{-- STEP 3 — RESET PASSWORD --}}
                {{-- ===================================================== --}}
                @if ($step === 'reset')
                    <form method="POST" action="{{ route('forgot_password.reset') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">

                        <div class="mb-3">
                            <label class="fp-label" for="password">New Password</label>
                            <div class="input-group fp-input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" name="password" id="password"
                                    class="form-control fp-input"
                                    placeholder="Enter new password"
                                    required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fp-label" for="password_confirmation">Confirm Password</label>
                            <div class="input-group fp-input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="form-control fp-input"
                                    placeholder="Confirm new password"
                                    required>
                            </div>
                        </div>

                        <button type="submit" class="btn-fp btn-fp-primary">
                            <i class="bi bi-check-lg"></i> Change Password
                        </button>
                    </form>
                @endif

                {{-- BACK TO LOGIN --}}
                <div class="text-center mt-4">
                    <a href="{{ route('login') }}" class="fp-link">
                        <i class="bi bi-arrow-left"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- COUNTDOWN JS (only on OTP step) --}}
    @if ($step === 'otp')
        <script>
            let timeLeft = 180;
            const countdown = document.getElementById('countdown');
            const resendForm = document.getElementById('resendForm');

            const timer = setInterval(() => {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;

                countdown.textContent =
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

                if (timeLeft <= 0) {
                    clearInterval(timer);
                    countdown.textContent = 'Expired';
                    countdown.style.color = '#f87171';
                    resendForm.classList.remove('hidden');
                }

                timeLeft--;
            }, 1000);
        </script>
    @endif

</body>

</html>