<aside class="sidebar" id="sidebar">

    <!-- SIDEBAR HEADER -->
    <div class="sidebar-header">

        <button type="button" class="brand text-decoration-none" id="brandButton"
            style="background: none; border: none; width: 100%; cursor: pointer; padding: 0;">

            <div class="brand-icon">
                N
            </div>

            <div class="brand-text">
                <h4>Nexora</h4>
                <small>Education System</small>
            </div>

        </button>

    </div>


    <!-- SIDEBAR BODY -->
    <div class="sidebar-body">

        @php
            $role = auth()->check() && auth()->user()->userType ? strtoupper(auth()->user()->userType->code) : null;

            $isSuperAdmin = $role === 'SUPER_ADMIN';
            $isAdmin = in_array($role, ['SUPER_ADMIN', 'ADMIN'], true);
            $isBranchAdmin = in_array($role, ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], true);
            $isUser = in_array($role, ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER'], true);
            $isTeacher = in_array($role, ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER', 'TEACHER'], true);

            $canDashboard = in_array($role, ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN', 'USER', 'TEACHER'], true);
            $canBranches = $isAdmin;

            $canTimetable = $isTeacher;
            $canTodayClasses = $isTeacher;
            $canAttendance = $isUser;
            $canPayments = $isUser;
            $canPaymentHistory = $isUser;
            $canNotifications = $isUser;

            $canStudents = $isUser;
            $canTeachers = $isBranchAdmin;
            $canOrganizers = $isBranchAdmin;
            $canStudentClassManagement = $isUser;
            $canSystemUsers = $isAdmin;
            $canUsers = $isAdmin;

            $canStudentImages = $isTeacher;
            // ID cards: App\Support\CardAccess (roles) + CardMode (nexora:card-mode)
            $canTemporaryIdCards = \App\Support\CardAccess::canView() && \App\Support\CardMode::temporary();
            // card stock: permanent-card institutes only
            $canStudentCards = \App\Support\CardAccess::canView() && \App\Support\CardMode::permanent();
            $canGenerateStudentId = \App\Support\CardAccess::canDownload() && \App\Support\CardMode::temporary();

            $canStudentClasses = $isTeacher;
            $canClassSchedules = $isTeacher;
            $canEnrollments = $isUser;
            $canExams = $isTeacher;

            $canAdmissions = $isUser;
            $canAdmissionPayments = $isUser;
            $canPaymentReminder = $isUser;

            $canTeacherSalaries = $isBranchAdmin;
            $canOrganizerPayments = $isBranchAdmin;
            $canExtraIncomes = $isBranchAdmin;
            $canInstituteExpenses = $isBranchAdmin;
            $canInstituteIncome = $isAdmin;
            $canReceipts = $isUser;

            $canDailyReport = $isUser;
            $canMonthlyReport = $isUser;
            $canTeacherReport = $isBranchAdmin;
            $canMonthlyClassAttendanceReport = $isUser;
            $canPaymentCollectionReport = $isAdmin;
            $canBranchCollectionReport = $isBranchAdmin;
            $canAnnualReport = $isBranchAdmin;
            $canActivityLogs = $isAdmin;
        @endphp


        @php
            /*
            | Menu groups. Each item: label, icon, route (link), active (routeIs
            | patterns), show (role flag above), badge (optional).
            | Super Admin: everything + "Nexora Admin". Admin: whole institute.
            | Branch Admin: their branch's daily work, staff, finance, reports.
            */
            $hasCourses = \App\Support\Features::enabled('courses');
            $hasWelfare = \App\Support\Features::enabled('welfare');

            $newFeedback = 0;
            if ($isSuperAdmin && \Illuminate\Support\Facades\Schema::hasTable('app_feedback')) {
                $newFeedback = \App\Models\AppFeedback::query()->where('status', 'new')->count();
            }

            $menu = [
                [
                    'key' => 'daily',
                    'title' => 'Daily Work',
                    'icon' => 'bi-lightning-charge-fill',
                    'items' => [
                        ['Today\'s Classes', 'bi-calendar2-check-fill', 'class-schedules.todayClasses', ['class-schedules.todayClasses'], $canTodayClasses],
                        ['Attendance', 'bi-person-check-fill', 'new-attendance.index', ['new-attendance.*'], $canAttendance],
                        ['Payments', 'bi-credit-card-fill', 'new-payment.index', ['new-payment.*'], $canPayments],
                        ['Payments History', 'bi-piggy-bank-fill', 'payments.index', ['payments.*'], $canPaymentHistory],
                        ['Timetable', 'bi-calendar3', 'class-time-table.weekly', ['class-time-table.*'], $canTimetable],
                    ],
                ],
                [
                    'key' => 'students',
                    'title' => 'Students',
                    'icon' => 'bi-people-fill',
                    'items' => [
                        ['Students', 'bi-people-fill', 'students.index', ['students.*'], $canStudents],
                        ['Enrollments', 'bi-pencil-square', 'student-class-enrollments.index', ['student-class-enrollments.*'], $canEnrollments],
                        ['Admissions', 'bi-person-plus-fill', 'admissions.index', ['admissions.*'], $canAdmissions],
                        ['Admission Payments', 'bi-credit-card-2-front-fill', 'admission-payments.index', ['admission-payments.*'], $canAdmissionPayments],
                        ['Payment Reminder', 'bi-bell-fill', 'payment-reminder.index', ['payment-reminder.*'], $canPaymentReminder],
                        ['Disabled Students', 'bi-person-slash', 'disabled-students.index', ['disabled-students.*'], $isTeacher],
                        ['Student Images', 'bi-images', 'admin.student-images.index', ['admin.student-images.*'], $canStudentImages],
                    ],
                ],
                [
                    'key' => 'cards',
                    'title' => 'ID Cards',
                    'icon' => 'bi-person-vcard-fill',
                    'items' => [
                        ['Student Cards', 'bi-person-vcard-fill', 'student-cards.index', ['student-cards.*'], $canStudentCards],
                        ['Temporary Cards', 'bi-person-vcard', 'temporary_id_cards.index', ['temporary_id_cards.*'], $canTemporaryIdCards],
                        ['Generate Student ID', 'bi-download', 'generate-student-id.index', ['generate-student-id.*'], $canGenerateStudentId],
                    ],
                ],
                [
                    'key' => 'academic',
                    'title' => 'Academic',
                    'icon' => 'bi-book-fill',
                    'items' => [
                        ['Classes', 'bi-book-fill', 'student-classes.index', ['student-classes.*'], $canStudentClasses],
                        ['Class Schedule', 'bi-calendar-event-fill', 'class-schedules.index', ['class-schedules.index', 'class-schedules.create', 'class-schedules.edit', 'class-schedules.show'], $canClassSchedules],
                        ['Student Class Mgmt', 'bi-person-workspace', 'student-class-management.index', ['student-class-management.*'], $canStudentClassManagement],
                        ['Exams', 'bi-journal-check', 'admin.exams.index', ['admin.exams.*'], $canExams],
                    ],
                ],
                [
                    'key' => 'courses',
                    'title' => 'Courses',
                    'icon' => 'bi-mortarboard-fill',
                    'items' => [
                        ['Courses', 'bi-mortarboard-fill', 'courses.index', ['courses.*'], $hasCourses && $isUser],
                        ['Course Enrollment', 'bi-person-plus-fill', 'course-desk.enroll', ['course-desk.enroll'], $hasCourses && $isUser],
                        ['Course Attendance', 'bi-person-check-fill', 'course-desk.attendance', ['course-desk.attendance'], $hasCourses && $isUser],
                        ['Course Payment', 'bi-cash-coin', 'course-desk.payment', ['course-desk.payment'], $hasCourses && $isUser],
                        ['Course Payments History', 'bi-receipt-cutoff', 'course-payments.index', ['course-payments.*'], $hasCourses && $isUser],
                        ['Course Teacher Payments', 'bi-person-badge-fill', 'course-teacher-payments.index', ['course-teacher-payments.*'], $hasCourses && $isBranchAdmin],
                        ['Course Reports', 'bi-bar-chart-fill', ['course-reports.show', 'daily'], ['course-reports.*'], $hasCourses && $isBranchAdmin],
                    ],
                ],
                [
                    'key' => 'staff',
                    'title' => 'Staff & Salary',
                    'icon' => 'bi-person-badge-fill',
                    'items' => [
                        ['Teachers', 'bi-person-badge-fill', 'teachers.index', ['teachers.*'], $canTeachers],
                        ['Teacher Salaries', 'bi-cash-stack', 'teacher-salaries.index', ['teacher-salaries.*'], $canTeacherSalaries],
                        ['Teacher Welfare', 'bi-heart-pulse', 'welfare.index', ['welfare.index'], $hasWelfare && $canTeacherSalaries],
                        ['Welfare Fund', 'bi-piggy-bank', 'welfare.fund', ['welfare.fund*'], $hasWelfare && $canTeacherSalaries],
                        ['Organizers', 'bi-calendar-check-fill', 'organizers.index', ['organizers.*'], $canOrganizers],
                        ['Organizer Payments', 'bi-wallet2', 'admin.organizer-payments.index', ['admin.organizer-payments.*'], $canOrganizerPayments],
                    ],
                ],
                [
                    'key' => 'finance',
                    'title' => 'Finance',
                    'icon' => 'bi-wallet-fill',
                    'items' => [
                        ['Extra Incomes', 'bi-plus-circle-fill', 'admin.extra-incomes.index', ['admin.extra-incomes.*'], $canExtraIncomes],
                        ['Institute Expenses', 'bi-dash-circle-fill', 'admin.institute-expenses.index', ['admin.institute-expenses.*'], $canInstituteExpenses],
                        ['Institute Income', 'bi-bar-chart-line-fill', 'reports.institute-income.index', ['reports.institute-income.*'], $canInstituteIncome],
                        ['Receipts', 'bi-receipt', 'receipts.index', ['receipts.*'], $canReceipts],
                    ],
                ],
                [
                    'key' => 'reports',
                    'title' => 'Reports',
                    'icon' => 'bi-file-earmark-bar-graph-fill',
                    'items' => [
                        ['Collection Report', 'bi-file-earmark-bar-graph', 'reports.daily-collection.index', ['reports.daily-collection.*'], $canDailyReport],
                        ['Monthly Report', 'bi-file-earmark-bar-graph', 'monthly-report.index', ['monthly-report.*'], $canMonthlyReport],
                        ['Teacher Collection', 'bi-file-earmark-person', 'reports.teacher-collection.index', ['reports.teacher-collection.*'], $canTeacherReport],
                        ['Card Scan vs Manual', 'bi-qr-code-scan', 'reports.manual-attendance.index', ['reports.manual-attendance.*'], $canMonthlyClassAttendanceReport],
                        ['Class Attendance', 'bi-calendar-check', 'admin.monthly-class-attendance-report.index', ['admin.monthly-class-attendance-report.*'], $canMonthlyClassAttendanceReport],
                        ['Monthly Collection', 'bi-cash-stack', 'reports.monthly-collection.index', ['reports.monthly-collection.*'], $canPaymentCollectionReport],
                        ['Branch-wise Collection', 'bi-diagram-3', 'reports.branch-collection.index', ['reports.branch-collection.*'], $canBranchCollectionReport],
                        ['Annual Report', 'bi-calendar-range', 'reports.annual.index', ['reports.annual.*'], $canAnnualReport],
                    ],
                ],
                [
                    'key' => 'admin',
                    'title' => 'Administration',
                    'icon' => 'bi-gear-fill',
                    'items' => [
                        ['Branches', 'bi-diagram-3', 'branches.index', ['branches.*'], $canBranches],
                        ['System Users', 'bi-people-fill', 'admin.system-users.index', ['admin.system-users.*'], $canSystemUsers],
                        ['Users', 'bi-person-circle', 'admin.users.index', ['admin.users.*'], $canUsers],
                        ['Close Batch (Grade)', 'bi-archive-fill', 'close-grade.index', ['close-grade.*'], $isAdmin],
                        ['Promote Class', 'bi-arrow-up-circle-fill', 'class-promotions.index', ['class-promotions.*'], $isAdmin],
                        ['Staff App QR', 'bi-qr-code', 'app-setup.index', ['app-setup.*'], $isAdmin],
                        ['Activities', 'bi-clock-history', 'admin.activity-logs.index', ['admin.activity-logs.*'], $canActivityLogs],
                        ['Settings', 'bi-sliders2', 'admin.setting.index', ['admin.setting.*'], $isAdmin],
                    ],
                ],
                [
                    'key' => 'nexora',
                    'title' => 'Nexora Admin',
                    'icon' => 'bi-shield-lock-fill',
                    'nexora' => true,
                    'items' => [
                        ['Payment & Attendance Rules', 'bi-sliders', 'payment-rules.index', ['payment-rules.*'], $isSuperAdmin],
                        ['ID Card Designer', 'bi-person-badge', 'card-designer.index', ['card-designer.*'], $isSuperAdmin],
                        ['App Feedback', 'bi-chat-square-heart', 'app-feedback.index', ['app-feedback.*'], $isSuperAdmin, $newFeedback],
                        ['Database Backup', 'bi-database-fill', 'admin.setting.index', [], $isSuperAdmin, 0, '#backup'],
                        ['Laravel Logs', 'bi-file-earmark-code', 'logs.laravel.index', ['logs.laravel.*'], $isSuperAdmin],
                    ],
                ],
            ];

            // Visible items only; a group with nothing visible is not shown
            foreach ($menu as $g => $group) {
                $menu[$g]['items'] = array_values(array_filter($group['items'], fn ($item) => (bool) $item[4]));
                $menu[$g]['open'] = false;

                foreach ($menu[$g]['items'] as $i => $item) {
                    $isActive = $item[3] && request()->routeIs(...$item[3]);
                    $menu[$g]['items'][$i]['active'] = $isActive;
                    $menu[$g]['items'][$i]['url'] = (is_array($item[2]) ? route($item[2][0], $item[2][1]) : route($item[2])) . ($item[6] ?? '');
                    $menu[$g]['items'][$i]['route'] = is_array($item[2]) ? $item[2][0] : $item[2];
                    $menu[$g]['open'] = $menu[$g]['open'] || $isActive;
                }
            }
            $menu = array_values(array_filter($menu, fn ($group) => count($group['items']) > 0));
        @endphp

        {{-- DASHBOARD + MENU SEARCH --}}
        <div class="sidebar-section sidebar-top">
            @if ($canDashboard)
                <div class="nav-item">
                    <button type="button" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        data-route="dashboard" data-href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </button>
                </div>
            @endif

            <div class="sidebar-search">
                <i class="bi bi-search"></i>
                <input type="search" id="sidebarSearch" placeholder="Search menu..." autocomplete="off"
                    aria-label="Search menu">
            </div>
        </div>

        {{-- MENU GROUPS --}}
        @foreach ($menu as $group)
            <div class="sidebar-group {{ $group['open'] ? 'is-open' : '' }} {{ !empty($group['nexora']) ? 'is-nexora' : '' }}"
                data-group="{{ $group['key'] }}">
                <button type="button" class="sidebar-group-toggle" aria-expanded="{{ $group['open'] ? 'true' : 'false' }}">
                    <i class="bi {{ $group['icon'] }}"></i>
                    <span>{{ $group['title'] }}</span>
                    <i class="bi bi-chevron-down sidebar-group-caret"></i>
                </button>

                <div class="sidebar-group-items">
                    @foreach ($group['items'] as $item)
                        <div class="nav-item" data-label="{{ strtolower($item[0]) }}">
                            <button type="button" class="nav-link-custom {{ $item['active'] ? 'active' : '' }}"
                                data-route="{{ $item['route'] }}" data-href="{{ $item['url'] }}">
                                <i class="bi {{ $item[1] }}"></i>
                                <span>{{ $item[0] }}</span>
                                @if (!empty($item[5]))
                                    <span class="badge bg-danger ms-auto">{{ $item[5] }}</span>
                                @endif
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="sidebar-search-empty" id="sidebarSearchEmpty">No menu found</div>

    </div>

</aside>


<style>
    /* ============================================================
       Sidebar — aligned to nexoraEDU (nx-*) design system.
       Uses :root level tokens (outside .dashboard-page scope).
       Business logic and JS untouched.
    ============================================================ */

    :root {
        --nx-sidebar-bg-start: #141c3a;
        --nx-sidebar-bg-mid: #1a2344;
        --nx-sidebar-bg-end: #1e293b;
        --nx-sidebar-border: rgba(255, 255, 255, .06);
        --nx-sidebar-title: #8894b3;
        --nx-sidebar-text: #b8c2da;
        --nx-sidebar-text-hover: #ffffff;
        --nx-sidebar-active: #2563eb;
        --nx-sidebar-active-soft: #eef3ff;
        --nx-sidebar-radius: 10px;
    }

    /* ---------- SIDEBAR ---------- */
    .sidebar {
        width: 280px;

        background: linear-gradient(180deg,
                var(--nx-sidebar-bg-start) 0%,
                var(--nx-sidebar-bg-mid) 50%,
                var(--nx-sidebar-bg-end) 100%);

        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 1030;

        display: flex;
        flex-direction: column;

        border-right: 1px solid var(--nx-sidebar-border);
        box-shadow: 6px 0 25px rgba(0, 0, 0, 0.20);
    }

    /* ---------- HEADER ---------- */
    .sidebar-header {
        padding: 1.5rem 1.25rem;
        border-bottom: 1px solid var(--nx-sidebar-border);
        flex-shrink: 0;
    }

    /* ---------- BODY ---------- */
    .sidebar-body {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, .15) transparent;
    }

    .sidebar-body::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-body::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, .15);
        border-radius: 20px;
    }

    /* ---------- BRAND ---------- */
    .brand {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .brand-icon {
        width: 44px;
        height: 44px;

        border-radius: 10px;

        background: var(--nx-sidebar-active);
        color: #fff;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 1.3rem;
        font-weight: 800;

        flex-shrink: 0;
    }

    .brand-text h4 {
        margin: 0;
        color: #fff;
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .brand-text small {
        color: var(--nx-sidebar-title);
        font-size: 0.72rem;
    }

    /* ---------- SECTIONS ---------- */
    .sidebar-section {
        margin-bottom: 1.5rem;
    }

    .sidebar-section-title {
        color: var(--nx-sidebar-title);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 0.7rem;
        padding-left: 0.8rem;
    }

    /* ---------- NAV ITEMS ---------- */
    .nav-item {
        margin-bottom: 0.25rem;
    }

    .nav-link-custom {
        display: flex;
        align-items: center;
        gap: 14px;

        padding: 0.7rem 0.9rem;

        border-radius: var(--nx-sidebar-radius);

        color: var(--nx-sidebar-text);
        text-decoration: none;

        font-size: 0.88rem;
        font-weight: 500;

        transition: background-color .15s ease, color .15s ease;

        /* Button reset — keeps original look */
        background: none;
        border: none;
        width: 100%;
        cursor: pointer;
        text-align: left;
        font-family: inherit;
    }

    .nav-link-custom i {
        font-size: 1.1rem;
        width: 22px;
        flex-shrink: 0;
    }

    .nav-link-custom:hover {
        background: rgba(255, 255, 255, 0.06);
        color: var(--nx-sidebar-text-hover);
    }

    .nav-link-custom.active {
        background: var(--nx-sidebar-active);
        color: #fff;
    }

    /* ---------- MENU SEARCH ---------- */
    .sidebar-top {
        margin-bottom: 1rem;
    }

    .sidebar-search {
        position: relative;
        margin-top: 0.6rem;
    }

    .sidebar-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--nx-sidebar-title);
        font-size: 0.85rem;
    }

    .sidebar-search input {
        width: 100%;
        padding: 0.55rem 0.8rem 0.55rem 2.1rem;
        border-radius: var(--nx-sidebar-radius);
        border: 1px solid rgba(255, 255, 255, .08);
        background: rgba(255, 255, 255, .05);
        color: #fff;
        font-size: 0.85rem;
        outline: none;
    }

    .sidebar-search input::placeholder {
        color: var(--nx-sidebar-title);
    }

    .sidebar-search input:focus {
        border-color: var(--nx-sidebar-active);
        background: rgba(255, 255, 255, .08);
    }

    .sidebar-search-empty {
        display: none;
        color: var(--nx-sidebar-title);
        font-size: 0.82rem;
        padding: 0.5rem 0.9rem;
    }

    /* ---------- MENU GROUPS (open / close) ---------- */
    .sidebar-group {
        margin-bottom: 0.35rem;
    }

    .sidebar-group-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 0.6rem 0.9rem;
        border: none;
        border-radius: var(--nx-sidebar-radius);
        background: none;
        color: var(--nx-sidebar-title);
        font-family: inherit;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: .8px;
        text-transform: uppercase;
        text-align: left;
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
    }

    .sidebar-group-toggle:hover {
        background: rgba(255, 255, 255, 0.04);
        color: var(--nx-sidebar-text-hover);
    }

    .sidebar-group-toggle > i:first-child {
        font-size: 0.95rem;
        width: 22px;
        flex-shrink: 0;
    }

    .sidebar-group-caret {
        margin-left: auto;
        font-size: 0.75rem;
        transition: transform .2s ease;
    }

    .sidebar-group.is-open > .sidebar-group-toggle {
        color: var(--nx-sidebar-text-hover);
    }

    .sidebar-group.is-open .sidebar-group-caret {
        transform: rotate(180deg);
    }

    .sidebar-group-items {
        display: none;
        padding: 0.15rem 0 0.4rem 0.6rem;
        margin-left: 1.15rem;
        border-left: 1px solid rgba(255, 255, 255, .08);
    }

    .sidebar-group.is-open > .sidebar-group-items {
        display: block;
    }

    .sidebar-group-items .nav-link-custom {
        padding: 0.55rem 0.8rem;
        font-size: 0.85rem;
        gap: 12px;
    }

    .sidebar-group-items .nav-link-custom i {
        font-size: 1rem;
    }

    /* Super Admin only group */
    .sidebar-group.is-nexora {
        margin-top: 0.9rem;
        padding-top: 0.9rem;
        border-top: 1px solid rgba(255, 255, 255, .08);
    }

    .sidebar-group.is-nexora > .sidebar-group-toggle {
        color: #fbbf24;
    }

    /* ---------- MOBILE ---------- */
    @media (max-width: 991px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform .3s ease;
        }

        .sidebar.show {
            transform: translateX(0);
        }
    }
</style>


<script>
    (function() {
        'use strict';

        const sidebarBody = document.querySelector('.sidebar-body');
        const navButtons = document.querySelectorAll('#sidebar .nav-link-custom');
        const brandButton = document.getElementById('brandButton');

        const SIDEBAR_SCROLL_KEY = 'nexora_sidebar_scroll';

        function restoreSidebarScroll() {
            if (!sidebarBody) return;

            const savedScroll = sessionStorage.getItem(SIDEBAR_SCROLL_KEY);

            if (savedScroll !== null) {
                setTimeout(function() {
                    sidebarBody.scrollTop = parseInt(savedScroll, 10) || 0;
                }, 50);
            }
        }

        function saveSidebarScroll() {
            if (!sidebarBody) return;

            sessionStorage.setItem(
                SIDEBAR_SCROLL_KEY,
                sidebarBody.scrollTop
            );
        }

        function navigateTo(url, routeName, buttonElement) {
            saveSidebarScroll();

            navButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });

            if (buttonElement) {
                buttonElement.classList.add('active');
            }

            if (url && url !== '#') {
                window.location.href = url;
            }
        }

        navButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const url = this.getAttribute('data-href');
                const routeName = this.getAttribute('data-route');

                navigateTo(url, routeName, this);
            });
        });

        if (brandButton) {
            brandButton.addEventListener('click', function(e) {
                e.preventDefault();

                saveSidebarScroll();

                const dashboardUrl = "{{ route('dashboard') }}";

                navButtons.forEach(function(btn) {
                    btn.classList.remove('active');
                });

                const dashboardBtn = Array.from(navButtons).find(function(btn) {
                    return btn.getAttribute('data-route') === 'dashboard';
                });

                if (dashboardBtn) {
                    dashboardBtn.classList.add('active');
                }

                if (dashboardUrl && dashboardUrl !== '#') {
                    window.location.href = dashboardUrl;
                }
            });
        }

        // ---------- Menu groups: open / close (kept for this tab) ----------
        const GROUPS_KEY = 'nexora_sidebar_groups';
        const groups = document.querySelectorAll('#sidebar .sidebar-group');

        function readOpenGroups() {
            try {
                return JSON.parse(sessionStorage.getItem(GROUPS_KEY) || 'null');
            } catch (e) {
                return null;
            }
        }

        function saveOpenGroups() {
            try {
                const open = Array.from(groups)
                    .filter(function(g) { return g.classList.contains('is-open'); })
                    .map(function(g) { return g.getAttribute('data-group'); });
                sessionStorage.setItem(GROUPS_KEY, JSON.stringify(open));
            } catch (e) {}
        }

        function setOpen(group, open) {
            group.classList.toggle('is-open', open);
            const toggle = group.querySelector('.sidebar-group-toggle');
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        // groups the user opened before stay open; the current page's group is always open
        const savedOpen = readOpenGroups();
        if (Array.isArray(savedOpen)) {
            groups.forEach(function(group) {
                if (savedOpen.indexOf(group.getAttribute('data-group')) !== -1) {
                    setOpen(group, true);
                }
            });
        }

        groups.forEach(function(group) {
            const toggle = group.querySelector('.sidebar-group-toggle');
            if (!toggle) return;

            toggle.addEventListener('click', function() {
                setOpen(group, !group.classList.contains('is-open'));
                saveOpenGroups();
            });
        });

        // ---------- Menu search ----------
        const search = document.getElementById('sidebarSearch');
        const searchEmpty = document.getElementById('sidebarSearchEmpty');
        let openBeforeSearch = null;

        if (search) {
            search.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();

                if (query !== '' && openBeforeSearch === null) {
                    openBeforeSearch = Array.from(groups).map(function(g) {
                        return g.classList.contains('is-open');
                    });
                }

                let found = 0;

                groups.forEach(function(group, index) {
                    let groupHits = 0;

                    group.querySelectorAll('.nav-item').forEach(function(item) {
                        const hit = query === '' || (item.getAttribute('data-label') || '').indexOf(query) !== -1;
                        item.style.display = hit ? '' : 'none';
                        if (hit) groupHits++;
                    });

                    if (query === '') {
                        group.style.display = '';
                        setOpen(group, openBeforeSearch ? openBeforeSearch[index] : group.classList.contains('is-open'));
                    } else {
                        group.style.display = groupHits ? '' : 'none';
                        setOpen(group, groupHits > 0);
                    }

                    found += groupHits;
                });

                if (query === '') openBeforeSearch = null;
                if (searchEmpty) searchEmpty.style.display = query !== '' && found === 0 ? 'block' : 'none';
            });

            // Enter opens the first match
            search.addEventListener('keydown', function(e) {
                if (e.key !== 'Enter') return;
                const first = Array.from(document.querySelectorAll('#sidebar .sidebar-group .nav-item'))
                    .find(function(item) { return item.style.display !== 'none'; });
                if (first) first.querySelector('.nav-link-custom').click();
            });
        }

        restoreSidebarScroll();
    })();
</script>
