{{-- Top navbar. Class names sidebar / top-navbar / main-content stay: page
     print styles hide them. --}}
<nav class="top-navbar tw:sticky tw:top-0 tw:z-[1020] tw:flex tw:items-center tw:justify-between tw:border-b tw:border-nx-border tw:bg-white/85 tw:px-8 tw:py-4 tw:shadow-[0_1px_2px_rgba(20,28,58,0.04)] tw:backdrop-blur-[14px] tw:max-lg:p-4">

    <!-- LEFT SIDE -->
    <div class="tw:flex tw:items-center tw:gap-4">

        <!-- MOBILE TOGGLE -->
        <button class="tw:hidden tw:cursor-pointer tw:border-0 tw:bg-transparent tw:p-1 tw:text-[1.5rem] tw:text-nx-text tw:max-lg:block"
            id="mobileToggle" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>

        <!-- PAGE TITLE -->
        <div>
            <h1 class="tw:m-0 tw:text-[1.5rem] tw:leading-[1.3] tw:font-bold tw:text-nx-text tw:max-lg:text-[1.2rem]">
                @yield('page-title', 'Dashboard')
            </h1>
            <p class="tw:m-0 tw:text-[0.85rem] tw:text-nx-muted">
                Welcome back,
                <strong>{{ auth()->user()->name }}</strong>
                👋
            </p>
        </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="tw:flex tw:items-center tw:gap-4">

        <!-- NOTIFICATION -->
        <div class="tw:relative">
            <button aria-label="Notifications"
                class="tw:relative tw:flex tw:h-[42px] tw:w-[42px] tw:cursor-pointer tw:items-center tw:justify-center tw:rounded-[10px] tw:border tw:border-nx-border tw:bg-white tw:px-3 tw:py-1.5 tw:text-[1.05rem] tw:leading-normal tw:text-nx-muted tw:transition-[background-color,color,border-color] tw:duration-150 tw:hover:border-nx-blue tw:hover:bg-nx-blue-soft tw:hover:text-nx-blue">
                <i class="bi bi-bell"></i>
                <span class="tw:absolute tw:-top-[5px] tw:-right-[5px] tw:flex tw:h-[18px] tw:min-w-[18px] tw:items-center tw:justify-center tw:rounded-full tw:border-2 tw:border-white tw:bg-nx-red tw:px-1 tw:text-[0.68rem] tw:font-semibold tw:text-white">0</span>
            </button>
        </div>

        <!-- USER DROPDOWN -->
        <div class="tw:relative" data-nx-dropdown>

            <div data-nx-toggle="dropdown" aria-expanded="false"
                class="tw:flex tw:cursor-pointer tw:items-center tw:gap-3 tw:rounded-[10px] tw:border tw:border-transparent tw:px-[0.7rem] tw:py-[0.4rem] tw:transition-[background-color,border-color] tw:duration-150 tw:hover:border-nx-border tw:hover:bg-[#fafbfd]">
                <!-- USER INFO -->
                <div class="tw:hidden tw:text-right tw:sm:block">
                    <div class="tw:text-[0.88rem] tw:leading-[1.2] tw:font-semibold tw:text-nx-text">{{ auth()->user()->name }}</div>
                    <div class="tw:text-[0.72rem] tw:leading-[1.2] tw:text-nx-muted">Administrator</div>
                </div>

                <!-- USER AVATAR -->
                <div class="tw:flex tw:h-10 tw:w-10 tw:items-center tw:justify-center tw:rounded-[10px] tw:bg-nx-blue-soft tw:text-[1rem] tw:font-semibold tw:text-nx-blue tw:shadow-[0_8px_18px_rgba(37,99,235,0.25)]">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>

            <!-- DROPDOWN -->
            @php
                $menuItem = 'tw:flex tw:w-full tw:items-center tw:gap-2 tw:whitespace-nowrap tw:rounded-lg tw:border-0 tw:bg-transparent tw:px-[0.85rem] tw:py-[0.6rem] tw:text-left tw:text-[0.85rem] tw:font-medium tw:no-underline tw:transition-[background-color,color] tw:duration-150';
            @endphp
            <div data-nx-menu
                class="tw:absolute tw:top-full tw:right-0 tw:z-[1000] tw:mt-[10px] tw:w-[240px] tw:rounded-[10px] tw:border tw:border-nx-border tw:bg-white tw:p-2 tw:text-left tw:shadow-[0_10px_30px_rgba(20,28,58,0.10)]">

                <div class="tw:px-3 tw:py-[0.6rem] tw:text-[0.875rem] tw:text-nx-text">
                    <div class="tw:font-bold">{{ auth()->user()->name }}</div>
                    <small class="tw:text-nx-muted">Administrator Account</small>
                </div>

                <hr class="tw:my-[0.4rem] tw:border-0 tw:border-t tw:border-nx-border tw:opacity-100">

                <a class="{{ $menuItem }} tw:text-nx-text tw:hover:bg-nx-blue-soft tw:hover:text-nx-blue" href="{{ route('admin.profile.index') }}">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>

                <a class="{{ $menuItem }} tw:text-nx-text tw:hover:bg-nx-blue-soft tw:hover:text-nx-blue" href="{{ route('admin.setting.index') }}">
                    <i class="bi bi-gear"></i> Settings
                </a>

                <a class="{{ $menuItem }} tw:text-nx-text tw:hover:bg-nx-blue-soft tw:hover:text-nx-blue" href="#">
                    <i class="bi bi-question-circle"></i> Help Center
                </a>

                <hr class="tw:my-[0.4rem] tw:border-0 tw:border-t tw:border-nx-border tw:opacity-100">

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="{{ $menuItem }} tw:cursor-pointer tw:text-nx-red tw:hover:bg-[#fdecec]">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>

            </div>

        </div>

    </div>

</nav>
