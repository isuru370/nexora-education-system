<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - {{ config('app.name', 'Nexora Education') }}</title>

    {{-- Bootstrap Icons (nx standard) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Google Font (nx standard) --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Tailwind (no Bootstrap on this page): npm run css --}}
    <link rel="stylesheet" href="{{ asset('css/guest.css') }}?v={{ @filemtime(public_path('css/guest.css')) }}">
</head>

<body class="tw:relative tw:flex tw:min-h-screen tw:items-center tw:justify-center tw:overflow-hidden tw:bg-[radial-gradient(circle_at_top_left,rgba(29,78,216,.28),transparent_40%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,.18),transparent_40%),linear-gradient(135deg,#020617,#0f172a,#1e3a8a)] tw:font-sans tw:text-[#f1f5f9]">

    {{-- FLOATING BACKGROUND --}}
    @php $circle = 'tw:absolute tw:rounded-full tw:bg-white/5 tw:animate-[float_10s_infinite_ease-in-out]'; @endphp
    <div class="tw:pointer-events-none tw:absolute tw:inset-0 tw:overflow-hidden">
        <div class="{{ $circle }} tw:-top-[60px] tw:-left-[60px] tw:h-[240px] tw:w-[240px]"></div>
        <div class="{{ $circle }} tw:-right-[40px] tw:-bottom-[50px] tw:h-[180px] tw:w-[180px] tw:[animation-delay:2s]"></div>
        <div class="{{ $circle }} tw:top-1/2 tw:left-[12%] tw:h-[120px] tw:w-[120px] tw:[animation-delay:1s]"></div>
        <div class="{{ $circle }} tw:top-[18%] tw:right-[20%] tw:h-[90px] tw:w-[90px] tw:[animation-delay:3s]"></div>
    </div>

    {{-- LOGIN WRAPPER --}}
    <div class="tw:relative tw:z-[5] tw:w-full tw:max-w-[460px] tw:p-5">
        <div class="tw:relative tw:overflow-hidden tw:rounded-[12px] tw:border tw:border-white/12 tw:bg-white/8 tw:text-[#f1f5f9] tw:shadow-[0_20px_50px_rgba(0,0,0,.45),inset_0_1px_0_rgba(255,255,255,.08)] tw:backdrop-blur-[22px] tw:before:pointer-events-none tw:before:absolute tw:before:-top-[120px] tw:before:-right-[120px] tw:before:h-[280px] tw:before:w-[280px] tw:before:rounded-full tw:before:bg-[rgba(59,130,246,.14)] tw:max-sm:rounded-[10px]">

            {{-- HEADER --}}
            <div class="tw:relative tw:px-10 tw:pt-[45px] tw:pb-[25px] tw:text-center tw:max-sm:px-[25px] tw:max-sm:pt-[35px] tw:max-sm:pb-5">
                <div class="tw:mx-auto tw:mb-6 tw:flex tw:h-[84px] tw:w-[84px] tw:items-center tw:justify-center tw:rounded-[12px] tw:bg-[linear-gradient(135deg,#1d4ed8,#3b82f6)] tw:shadow-[0_10px_25px_rgba(29,78,216,.45)] tw:max-sm:h-[72px] tw:max-sm:w-[72px]">
                    <i class="bi bi-mortarboard-fill tw:text-[38px] tw:text-white tw:max-sm:text-[32px]"></i>
                </div>
                <h1 class="tw:mb-[10px] tw:text-[1.75rem] tw:leading-[1.2] tw:font-extrabold tw:tracking-[-.3px] tw:text-white tw:max-sm:text-[1.4rem]">NEXORAIT EDU</h1>
                <p class="tw:m-0 tw:text-[.9rem] tw:leading-[1.6] tw:text-white/72">
                    Modern Student Management System for smart educational institutes.
                </p>
            </div>

            {{-- BODY --}}
            <div class="tw:px-10 tw:pt-5 tw:pb-10 tw:max-sm:px-[25px] tw:max-sm:pb-[30px]">

                {{-- ERRORS --}}
                @if ($errors->any())
                    <div class="tw:mb-6 tw:rounded-[12px] tw:border tw:border-[rgba(185,28,28,.35)] tw:bg-[rgba(185,28,28,.18)] tw:px-5 tw:py-4 tw:text-[.85rem] tw:text-[#fecaca]" role="alert">
                        <ul class="tw:m-0 tw:list-disc tw:pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $label = 'tw:mb-[10px] tw:block tw:text-[.8rem] tw:font-bold tw:tracking-[.04em] tw:text-[#cbd5e1] tw:uppercase';
                    $group = 'tw:relative tw:flex tw:w-full tw:flex-wrap tw:items-stretch tw:overflow-hidden tw:rounded-[12px] tw:border tw:border-white/12 tw:bg-white/7 tw:transition-[border-color,box-shadow] tw:duration-150 tw:focus-within:border-[rgba(59,130,246,.7)] tw:focus-within:shadow-[0_0_0_3px_rgba(29,78,216,.18)]';
                    $groupIcon = 'tw:flex tw:items-center tw:py-[.375rem] tw:pr-3 tw:pl-[18px] tw:text-center tw:text-[1rem] tw:whitespace-nowrap tw:text-[#cbd5e1]';
                    $input = 'nx-glass-input tw:block tw:h-[54px] tw:w-[1%] tw:min-w-0 tw:flex-auto tw:appearance-none tw:bg-transparent tw:px-3 tw:py-[.375rem] tw:text-[.92rem] tw:text-white tw:outline-none tw:placeholder:text-white/45';
                    $link = 'tw:text-[.85rem] tw:text-[#bfdbfe] tw:no-underline tw:transition-colors tw:duration-150 tw:hover:text-white tw:hover:underline';
                @endphp

                {{-- FORM --}}
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- EMAIL --}}
                    <div class="tw:mb-6">
                        <label class="{{ $label }}" for="email">Email Address</label>
                        <div class="{{ $group }}">
                            <span class="{{ $groupIcon }}">
                                <i class="bi bi-envelope-fill"></i>
                            </span>
                            <input type="email" name="email" id="email"
                                class="{{ $input }}"
                                placeholder="Enter your email"
                                required
                                value="{{ old('email', request()->boolean('demo') ? config('demo.email') : '') }}">
                        </div>
                    </div>

                    {{-- PASSWORD --}}
                    <div class="tw:mb-6">
                        <label class="{{ $label }}" for="password">Password</label>
                        <div class="{{ $group }}">
                            <span class="{{ $groupIcon }}">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" name="password" id="password"
                                class="{{ $input }}"
                                placeholder="Enter your password"
                                required
                                value="{{ request()->boolean('demo') ? config('demo.password') : '' }}">
                            <button type="button" onclick="togglePassword()" aria-label="Toggle password visibility"
                                class="tw:cursor-pointer tw:bg-transparent tw:px-[18px] tw:text-[#cbd5e1] tw:transition-colors tw:duration-150 tw:hover:text-white">
                                <i class="bi bi-eye-fill" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    {{-- REMEMBER / FORGOT --}}
                    <div class="tw:mb-6 tw:flex tw:items-center tw:justify-between">
                        <div class="tw:mb-[.125rem] tw:block tw:min-h-6 tw:pl-[1.5em]">
                            <input type="checkbox" id="remember"
                                class="nx-check tw:float-left tw:-ml-[1.5em] tw:mt-[.25em] tw:h-[1em] tw:w-[1em] tw:shrink-0 tw:cursor-pointer tw:appearance-none tw:rounded-[.25em] tw:border tw:border-white/35 tw:bg-transparent tw:bg-contain tw:bg-center tw:bg-no-repeat tw:align-top tw:[print-color-adjust:exact] tw:checked:border-[#1d4ed8] tw:checked:bg-[#1d4ed8] tw:focus:border-[#1d4ed8] tw:focus:shadow-[0_0_0_3px_rgba(29,78,216,.25)] tw:focus:outline-none">
                            <label class="tw:cursor-pointer tw:text-[.85rem] tw:text-[#cbd5e1]" for="remember">Remember Me</label>
                        </div>
                        <a href="forgot-password" class="{{ $link }}">Forgot Password?</a>
                    </div>

                    {{-- LOGIN BUTTON --}}
                    <button type="submit"
                        class="tw:inline-flex tw:h-[54px] tw:w-full tw:cursor-pointer tw:items-center tw:justify-center tw:gap-2 tw:rounded-[12px] tw:bg-[linear-gradient(135deg,#1d4ed8,#3b82f6)] tw:text-[.95rem] tw:font-bold tw:text-white tw:shadow-[0_12px_30px_rgba(29,78,216,.35)] tw:transition-[transform,box-shadow] tw:duration-150 tw:hover:transform-[translateY(-2px)] tw:hover:shadow-[0_16px_35px_rgba(29,78,216,.45)]">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Login to Dashboard
                    </button>
                </form>

                {{-- FOOTER --}}
                <div class="tw:mt-7 tw:text-center tw:text-[.82rem] tw:text-white/55">
                    Need help?
                    <a href="{{ route('contact_administrator') }}" class="{{ $link }}">
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