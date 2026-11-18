{{-- Class names "sidebar" / nav-link-custom / sidebar-group stay: the JS
     below and page print styles use them. --}}
<aside id="sidebar"
    class="sidebar tw:fixed tw:inset-y-0 tw:left-0 tw:z-[1030] tw:flex tw:w-[280px] tw:flex-col tw:border-r tw:border-white/6 tw:bg-[linear-gradient(180deg,var(--tw-color-nx-side-1)_0%,var(--tw-color-nx-side-2)_50%,var(--tw-color-nx-side-3)_100%)] tw:shadow-[6px_0_25px_rgba(0,0,0,0.20)] tw:transition-all tw:duration-300 tw:max-lg:transform-[translateX(-100%)] tw:max-lg:[&.show]:transform-none">

    <!-- SIDEBAR HEADER -->
    <div class="tw:shrink-0 tw:border-b tw:border-white/6 tw:px-5 tw:py-6">

        <button type="button" id="brandButton"
            class="tw:flex tw:w-full tw:cursor-pointer tw:items-center tw:gap-[14px] tw:border-0 tw:bg-transparent tw:p-0">

            <div class="tw:flex tw:h-11 tw:w-11 tw:shrink-0 tw:items-center tw:justify-center tw:rounded-[10px] tw:bg-nx-blue tw:text-[1.3rem] tw:font-extrabold tw:text-white">
                N
            </div>

            <div>
                <h4 class="tw:m-0 tw:text-[1.15rem] tw:leading-[1.2] tw:font-bold tw:text-white">Nexora</h4>
                <small class="tw:text-[0.72rem] tw:text-nx-side-title">Education System</small>
            </div>

        </button>

    </div>


    <!-- SIDEBAR BODY -->
    <div class="sidebar-body tw:flex-1 tw:overflow-y-auto tw:p-4 tw:[scrollbar-color:rgba(255,255,255,.15)_transparent] tw:[scrollbar-width:thin] tw:[&::-webkit-scrollbar]:w-1.5 tw:[&::-webkit-scrollbar-thumb]:rounded-[20px] tw:[&::-webkit-scrollbar-thumb]:bg-white/15">

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

        @php
            // One look for every menu link; "active" is set here and by the JS below
            $navLink = 'nav-link-custom tw:flex tw:w-full tw:cursor-pointer tw:items-center tw:border-0 tw:bg-transparent tw:text-left tw:font-medium tw:text-nx-side-text tw:no-underline tw:transition-[background-color,color] tw:duration-150 tw:hover:transform-[translateX(5px)] tw:hover:bg-white/6 tw:hover:text-white tw:[&.active]:bg-nx-blue tw:[&.active]:text-white tw:[&.active]:shadow-[0_10px_18px_rgba(37,99,235,0.30)] tw:rounded-[10px]';
        @endphp

        {{-- DASHBOARD + MENU SEARCH --}}
        <div class="tw:mb-4">
            @if ($canDashboard)
                <div class="nav-item tw:mb-1">
                    <button type="button"
                        class="{{ $navLink }} tw:gap-[14px] tw:px-[0.9rem] tw:py-[0.7rem] tw:text-[0.88rem] {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        data-route="dashboard" data-href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2 tw:w-[22px] tw:shrink-0 tw:text-[1.1rem]"></i>
                        <span>Dashboard</span>
                    </button>
                </div>
            @endif

            <div class="tw:relative tw:mt-[0.6rem]">
                <i class="bi bi-search tw:absolute tw:top-1/2 tw:left-3 tw:-translate-y-1/2 tw:text-[0.85rem] tw:text-nx-side-title"></i>
                <input type="search" id="sidebarSearch" placeholder="Search menu..." autocomplete="off"
                    aria-label="Search menu"
                    class="tw:w-full tw:rounded-[10px] tw:border tw:border-white/8 tw:bg-white/5 tw:py-[0.55rem] tw:pr-[0.8rem] tw:pl-[2.1rem] tw:text-[0.85rem] tw:text-white tw:outline-none tw:placeholder:text-nx-side-title tw:focus:border-nx-blue tw:focus:bg-white/8">
            </div>
        </div>

        {{-- MENU GROUPS --}}
        @foreach ($menu as $group)
            @php $nexora = !empty($group['nexora']); @endphp
            <div class="sidebar-group tw:group tw:mb-[0.35rem] {{ $group['open'] ? 'is-open' : '' }} {{ $nexora ? 'tw:mt-[0.9rem] tw:border-t tw:border-white/8 tw:pt-[0.9rem]' : '' }}"
                data-group="{{ $group['key'] }}">
                <button type="button" aria-expanded="{{ $group['open'] ? 'true' : 'false' }}"
                    class="sidebar-group-toggle tw:flex tw:w-full tw:cursor-pointer tw:items-center tw:gap-3 tw:rounded-[10px] tw:border-0 tw:bg-transparent tw:px-[0.9rem] tw:py-[0.6rem] tw:text-left tw:text-[0.74rem] tw:font-bold tw:tracking-[.8px] tw:uppercase tw:transition-[background-color,color] tw:duration-150 tw:hover:bg-white/4 {{ $nexora ? 'tw:text-[#fbbf24]' : 'tw:text-nx-side-title tw:hover:text-white tw:group-[.is-open]:text-white' }}">
                    <i class="bi {{ $group['icon'] }} tw:w-[22px] tw:shrink-0 tw:text-[0.95rem]"></i>
                    <span>{{ $group['title'] }}</span>
                    <i class="bi bi-chevron-down tw:ml-auto tw:text-[0.75rem] tw:transition-transform tw:duration-200 tw:group-[.is-open]:rotate-180"></i>
                </button>

                <div class="tw:ml-[1.15rem] tw:hidden tw:border-l tw:border-white/8 tw:pt-[0.15rem] tw:pb-[0.4rem] tw:pl-[0.6rem] tw:group-[.is-open]:block">
                    @foreach ($group['items'] as $item)
                        <div class="nav-item tw:mb-1" data-label="{{ strtolower($item[0]) }}">
                            <button type="button"
                                class="{{ $navLink }} tw:gap-3 tw:px-[0.8rem] tw:py-[0.55rem] tw:text-[0.85rem] {{ $item['active'] ? 'active' : '' }}"
                                data-route="{{ $item['route'] }}" data-href="{{ $item['url'] }}">
                                <i class="bi {{ $item[1] }} tw:w-[22px] tw:shrink-0 tw:text-[1rem]"></i>
                                <span>{{ $item[0] }}</span>
                                @if (!empty($item[5]))
                                    <span class="tw:ml-auto tw:inline-block tw:rounded-[0.375rem] tw:bg-[#dc3545] tw:px-[0.65em] tw:py-[0.35em] tw:text-[0.75em] tw:leading-none tw:font-bold tw:text-white">{{ $item[5] }}</span>
                                @endif
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div id="sidebarSearchEmpty" class="tw:hidden tw:px-[0.9rem] tw:py-2 tw:text-[0.82rem] tw:text-nx-side-title">No menu found</div>

    </div>

</aside>



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
