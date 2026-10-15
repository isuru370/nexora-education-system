<nav class="top-navbar">

    <!-- LEFT SIDE -->
    <div class="d-flex align-items-center gap-3">

        <!-- MOBILE TOGGLE -->
        <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>

        <!-- PAGE TITLE -->
        <div class="page-title">
            <h1>@yield('page-title', 'Dashboard')</h1>
            <p>
                Welcome back,
                <strong>{{ auth()->user()->name }}</strong>
                👋
            </p>
        </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="user-menu">

        <!-- NOTIFICATION -->
        <div class="notification-btn position-relative">
            <button class="btn notification-button" aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notification-badge">0</span>
            </button>
        </div>

        <!-- USER DROPDOWN -->
        <div class="dropdown">

            <div class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <!-- USER INFO -->
                <div class="user-info d-none d-sm-block">
                    <div class="name">{{ auth()->user()->name }}</div>
                    <div class="role">Administrator</div>
                </div>

                <!-- USER AVATAR -->
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>

            <!-- DROPDOWN -->
            <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu border-0">

                <li class="dropdown-header">
                    <div class="fw-bold">{{ auth()->user()->name }}</div>
                    <small class="text-muted">Administrator Account</small>
                </li>

                <li><hr class="dropdown-divider"></li>

                <!-- PROFILE -->
                <li>
                    <a class="dropdown-item" href="{{ route('admin.profile.index') }}">
                        <i class="bi bi-person-circle me-2"></i> My Profile
                    </a>
                </li>

                <!-- SETTINGS -->
                <li>
                    <a class="dropdown-item" href="{{ route('admin.setting.index') }}">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>

                <!-- HELP -->
                <li>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-question-circle me-2"></i> Help Center
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <!-- LOGOUT -->
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </li>

            </ul>

        </div>

    </div>

</nav>

<style>
    /* ============================================================
       Top Navbar — aligned to nexoraEDU (nx-*) design system.
       Uses :root level tokens (outside .dashboard-page scope).
       Business logic untouched.
    ============================================================ */

    :root {
        --nx-topnav-bg: rgba(255, 255, 255, 0.85);
        --nx-topnav-border: #e5e9f0;
        --nx-topnav-text: #1e2640;
        --nx-topnav-muted: #6b7590;
        --nx-topnav-blue: #2563eb;
        --nx-topnav-blue-soft: #eef3ff;
        --nx-topnav-red: #dc2626;
        --nx-topnav-radius: 10px;
        --nx-topnav-shadow: 0 1px 2px rgba(20, 28, 58, .04);
    }

    /* ---------- TOP NAVBAR ---------- */
    .top-navbar {
        background: var(--nx-topnav-bg);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);

        border-bottom: 1px solid var(--nx-topnav-border);

        padding: 1rem 2rem;

        position: sticky;
        top: 0;
        z-index: 1020;

        display: flex;
        align-items: center;
        justify-content: space-between;

        box-shadow: var(--nx-topnav-shadow);
    }

    /* ---------- PAGE TITLE ---------- */
    .page-title h1 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--nx-topnav-text);
        line-height: 1.3;
    }

    .page-title p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--nx-topnav-muted);
    }

    /* ---------- USER MENU ---------- */
    .user-menu {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    /* ---------- NOTIFICATION BUTTON ---------- */
    .notification-button {
        width: 42px;
        height: 42px;

        border: 1px solid var(--nx-topnav-border);
        border-radius: 10px;

        background: #fff;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 1.05rem;
        color: var(--nx-topnav-muted);

        position: relative;

        transition: background-color .15s, color .15s, border-color .15s;
        box-shadow: none;
    }

    .notification-button:hover {
        background: var(--nx-topnav-blue-soft);
        color: var(--nx-topnav-blue);
        border-color: var(--nx-topnav-blue);
    }

    /* ---------- BADGE ---------- */
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;

        min-width: 18px;
        height: 18px;
        padding: 0 4px;

        background: var(--nx-topnav-red);
        color: #fff;
        border-radius: 99px;

        font-size: 0.68rem;
        font-weight: 600;

        display: flex;
        align-items: center;
        justify-content: center;

        border: 2px solid #fff;
    }

    /* ---------- USER DROPDOWN ---------- */
    .user-dropdown {
        display: flex;
        align-items: center;
        gap: 12px;

        padding: 0.4rem 0.7rem;

        border-radius: 10px;
        border: 1px solid transparent;

        cursor: pointer;
        transition: background-color .15s, border-color .15s;
    }

    .user-dropdown:hover {
        background: #fafbfd;
        border-color: var(--nx-topnav-border);
    }

    /* ---------- USER AVATAR ---------- */
    .user-avatar {
        width: 40px;
        height: 40px;

        border-radius: 10px;

        background: var(--nx-topnav-blue-soft);
        color: var(--nx-topnav-blue);

        display: flex;
        align-items: center;
        justify-content: center;

        font-weight: 600;
        font-size: 1rem;
    }

    /* ---------- USER INFO ---------- */
    .user-info { text-align: right; }

    .user-info .name {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--nx-topnav-text);
        line-height: 1.2;
    }

    .user-info .role {
        font-size: 0.72rem;
        color: var(--nx-topnav-muted);
        line-height: 1.2;
    }

    /* ---------- DROPDOWN MENU ---------- */
    .user-dropdown-menu {
        width: 240px;

        border-radius: var(--nx-topnav-radius);
        padding: 0.5rem;

        margin-top: 10px;

        border: 1px solid var(--nx-topnav-border);
        box-shadow: 0 10px 30px rgba(20, 28, 58, .10);
    }

    .user-dropdown-menu .dropdown-header {
        padding: 0.6rem 0.75rem;
        color: var(--nx-topnav-text);
    }

    .user-dropdown-menu .dropdown-header small {
        color: var(--nx-topnav-muted) !important;
    }

    .user-dropdown-menu .dropdown-item {
        border-radius: 8px;
        padding: 0.6rem 0.85rem;
        font-size: 0.85rem;
        color: var(--nx-topnav-text);
        font-weight: 500;
        transition: background-color .15s, color .15s;
    }

    .user-dropdown-menu .dropdown-item:hover {
        background: var(--nx-topnav-blue-soft);
        color: var(--nx-topnav-blue);
    }

    .user-dropdown-menu .dropdown-item.text-danger {
        color: var(--nx-topnav-red) !important;
    }

    .user-dropdown-menu .dropdown-item.text-danger:hover {
        background: #fdecec;
        color: var(--nx-topnav-red) !important;
    }

    .user-dropdown-menu .dropdown-divider {
        margin: 0.4rem 0;
        border-color: var(--nx-topnav-border);
        opacity: 1;
    }

    /* ---------- MOBILE TOGGLE ---------- */
    .mobile-toggle {
        display: none;

        background: none;
        border: none;

        font-size: 1.5rem;
        color: var(--nx-topnav-text);

        cursor: pointer;
        padding: 0.25rem;
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 991px) {
        .mobile-toggle { display: block; }

        .top-navbar { padding: 1rem; }

        .page-title h1 { font-size: 1.2rem; }

        .user-info { display: none; }
    }
</style>