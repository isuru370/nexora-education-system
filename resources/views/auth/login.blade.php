<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - {{ config('app.name', 'Nexora Education') }}</title>

    {{-- Bootstrap --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons (nx standard) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Google Font (nx standard) --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           Login — Nexora theme aligned
           Glassmorphism identity preserved.
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
        }

        /* FLOATING BACKGROUND CIRCLES */
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

        .floating-circle:nth-child(4) {
            width: 90px;
            height: 90px;
            top: 18%;
            right: 20%;
            animation-delay: 3s;
        }

        @keyframes float {
            0%   { transform: translateY(0); }
            50%  { transform: translateY(-25px); }
            100% { transform: translateY(0); }
        }

        /* LOGIN WRAPPER */
        .login-wrapper {
            width: 100%;
            max-width: 460px;
            position: relative;
            z-index: 5;
            padding: 20px;
        }

        /* LOGIN CARD */
        .login-card {
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

        .login-card::before {
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
        .login-header {
            position: relative;
            padding: 45px 40px 25px;
            text-align: center;
        }

        .logo-box {
            width: 84px;
            height: 84px;
            margin: 0 auto 24px;
            border-radius: var(--nx-login-radius);
            background: linear-gradient(135deg, var(--nx-login-blue), var(--nx-login-blue-light));
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 10px 25px rgba(29, 78, 216, .45);
        }

        .logo-box i {
            font-size: 38px;
            color: #fff;
        }

        .brand-title {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -.3px;
            margin-bottom: 10px;
            color: #fff;
        }

        .brand-subtitle {
            color: var(--nx-login-muted);
            font-size: .9rem;
            line-height: 1.6;
            margin: 0;
        }

        /* BODY */
        .login-body {
            padding: 20px 40px 40px;
        }

        /* LABELS */
        .login-label {
            display: block;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: .8rem;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        /* INPUT GROUP */
        .custom-input-group {
            background: rgba(255, 255, 255, .07);
            border: 1px solid var(--nx-login-border);
            border-radius: var(--nx-login-radius);
            overflow: hidden;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .custom-input-group:focus-within {
            border-color: rgba(59, 130, 246, .7);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, .18);
        }

        .custom-input-group .input-group-text {
            background: transparent;
            border: none;
            color: #cbd5e1;
            padding-left: 18px;
            font-size: 1rem;
        }

        .custom-input {
            background: transparent !important;
            border: none !important;
            color: #fff !important;
            height: 54px;
            font-size: .92rem;
        }

        .custom-input::placeholder {
            color: rgba(255, 255, 255, .45);
        }

        .custom-input:focus {
            box-shadow: none !important;
        }

        .custom-input:-webkit-autofill,
        .custom-input:-webkit-autofill:hover,
        .custom-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #fff;
            transition: background-color 9999s ease-in-out 0s;
        }

        /* PASSWORD BUTTON */
        .toggle-password {
            border: none;
            background: transparent;
            color: #cbd5e1;
            padding: 0 18px;
            cursor: pointer;
            transition: color .15s ease;
        }

        .toggle-password:hover {
            color: #fff;
        }

        /* REMEMBER / CHECKBOX */
        .form-check-input {
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, .35);
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--nx-login-blue);
            border-color: var(--nx-login-blue);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(29, 78, 216, .25);
            border-color: var(--nx-login-blue);
        }

        .form-check-label {
            color: #cbd5e1;
            cursor: pointer;
            font-size: .85rem;
        }

        /* LOGIN BUTTON */
        .btn-login {
            width: 100%;
            height: 54px;
            border: none;
            border-radius: var(--nx-login-radius);
            background: linear-gradient(135deg, var(--nx-login-blue), var(--nx-login-blue-light));
            color: #fff;
            font-size: .95rem;
            font-weight: 700;
            transition: transform .15s ease, box-shadow .15s ease;
            box-shadow: 0 12px 30px rgba(29, 78, 216, .35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 35px rgba(29, 78, 216, .45);
            color: #fff;
        }

        /* LINKS */
        .custom-link {
            color: #bfdbfe;
            text-decoration: none;
            transition: color .15s ease;
            font-size: .85rem;
        }

        .custom-link:hover {
            color: #fff;
            text-decoration: underline;
        }

        /* ALERT */
        .login-alert {
            background: rgba(185, 28, 28, .18);
            border: 1px solid rgba(185, 28, 28, .35);
            border-radius: var(--nx-login-radius);
            color: #fecaca;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: .85rem;
        }

        .login-alert ul {
            margin: 0;
            padding-left: 1.25rem;
        }

        /* FOOTER */
        .footer-text {
            margin-top: 28px;
            text-align: center;
            color: rgba(255, 255, 255, .55);
            font-size: .82rem;
        }

        /* RESPONSIVE */
        @media (max-width: 576px) {
            .login-card {
                border-radius: 10px;
            }

            .login-header {
                padding: 35px 25px 20px;
            }

            .login-body {
                padding: 20px 25px 30px;
            }

            .brand-title {
                font-size: 1.4rem;
            }

            .logo-box {
                width: 72px;
                height: 72px;
            }

            .logo-box i {
                font-size: 32px;
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
        <div class="floating-circle"></div>
    </div>

    {{-- LOGIN WRAPPER --}}
    <div class="login-wrapper">
        <div class="login-card">

            {{-- HEADER --}}
            <div class="login-header">
                <div class="logo-box">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h1 class="brand-title">NEXORAIT EDU</h1>
                <p class="brand-subtitle">
                    Modern Student Management System for smart educational institutes.
                </p>
            </div>

            {{-- BODY --}}
            <div class="login-body">

                {{-- ERRORS --}}
                @if ($errors->any())
                    <div class="login-alert" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- FORM --}}
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- EMAIL --}}
                    <div class="mb-4">
                        <label class="login-label" for="email">Email Address</label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope-fill"></i>
                            </span>
                            <input type="email" name="email" id="email"
                                class="form-control custom-input"
                                placeholder="Enter your email"
                                required
                                value="{{ old('email', request()->boolean('demo') ? config('demo.email') : '') }}">
                        </div>
                    </div>

                    {{-- PASSWORD --}}
                    <div class="mb-4">
                        <label class="login-label" for="password">Password</label>
                        <div class="input-group custom-input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" name="password" id="password"
                                class="form-control custom-input"
                                placeholder="Enter your password"
                                required
                                value="{{ request()->boolean('demo') ? config('demo.password') : '' }}">
                            <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Toggle password visibility">
                                <i class="bi bi-eye-fill" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    {{-- REMEMBER / FORGOT --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember Me</label>
                        </div>
                        <a href="forgot-password" class="custom-link">Forgot Password?</a>
                    </div>

                    {{-- LOGIN BUTTON --}}
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Login to Dashboard
                    </button>
                </form>

                {{-- FOOTER --}}
                <div class="footer-text">
                    Need help?
                    <a href="{{ route('contact_administrator') }}" class="custom-link">
                        Contact Administrator
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- PASSWORD TOGGLE --}}
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.classList.remove('bi-eye-fill');
                eyeIcon.classList.add('bi-eye-slash-fill');
            } else {
                password.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash-fill');
                eyeIcon.classList.add('bi-eye-fill');
            }
        }
    </script>

</body>

</html>