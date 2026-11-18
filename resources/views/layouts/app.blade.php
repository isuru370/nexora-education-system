<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    {{-- Old layout styles + nx-* design system + Tailwind (tw: classes): npm run css --}}
    <link rel="stylesheet" href="{{ asset('css/nexora.css') }}?v={{ @filemtime(public_path('css/nexora.css')) }}">

    @stack('styles')

</head>

<body>

    {{-- Old sidebar / navbar styles: here, after the page styles, as before --}}
    <link rel="stylesheet" href="{{ asset('css/legacy-layout.css') }}?v={{ @filemtime(public_path('css/legacy-layout.css')) }}">

    <div class="tw:flex tw:min-h-screen">

        <!-- Sidebar -->
        @include('layouts.sidebar')

        <!-- Main -->
        <main class="main-content tw:min-w-0 tw:flex-1 tw:ml-[280px] tw:max-lg:ml-0">

            <!-- Header -->
            @include('layouts.header')

            <!-- Content -->
            <div class="content-area tw:p-8 tw:max-lg:p-4 tw:animate-[fadeIn_0.4s_ease]">

                @foreach (['success' => ['bi-check-circle-fill', 'tw:bg-[#d1e7dd] tw:text-[#0a3622]'], 'error' => ['bi-exclamation-triangle-fill', 'tw:bg-[#f8d7da] tw:text-[#58151c]']] as $type => [$icon, $colors])
                    @if (session($type))
                        <div data-nx-alert role="alert"
                            class="tw:relative tw:mb-4 tw:rounded-[14px] tw:py-4 tw:pr-12 tw:pl-5 tw:font-medium tw:shadow-[0_6px_18px_rgba(0,0,0,0.05)] {{ $colors }}">
                            <i class="bi {{ $icon }} tw:mr-2"></i>
                            {{ session($type) }}
                            <button type="button" data-nx-dismiss="alert" aria-label="Close"
                                class="tw:absolute tw:top-0 tw:right-0 tw:z-[2] tw:box-content tw:h-4 tw:w-4 tw:cursor-pointer tw:border-0 tw:bg-transparent tw:px-4 tw:py-5 tw:text-black tw:opacity-50 tw:hover:opacity-75">
                                <svg viewBox="0 0 16 16" fill="currentColor" class="tw:h-4 tw:w-4"><path d="M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414"/></svg>
                            </button>
                        </div>
                    @endif
                @endforeach

                <!-- Page Content -->
                @yield('content')

            </div>

        </main>

    </div>

    <script src="{{ asset('js/nx-ui.js') }}?v={{ @filemtime(public_path('js/nx-ui.js')) }}"></script>

    <script>
        // Mobile sidebar
        (function () {
            const mobileToggle = document.getElementById('mobileToggle');
            const sidebar = document.getElementById('sidebar');

            if (!mobileToggle || !sidebar) return;

            mobileToggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
            });

            document.addEventListener('click', function (event) {
                if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)
                    && window.innerWidth <= 991 && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            });
        })();
    </script>

    @stack('scripts')

</body>

</html>
