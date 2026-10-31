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
            $canExtraIncomes = $isAdmin;
            $canInstituteExpenses = $isAdmin;
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


        <!-- MAIN SECTION -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">MAIN MENU</div>

            @if ($canDashboard)
                <div class="nav-item">
                    <button type="button" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        data-route="dashboard" data-href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </button>
                </div>
            @endif

            @if ($canBranches ?? false)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('branches.index') ? 'active' : '' }}"
                        data-route="branches.index" data-href="{{ route('branches.index') }}">
                        <i class="bi bi-diagram-3"></i>
                        <span>Branches</span>
                    </button>
                </div>
            @endif

            @if ($canTimetable)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('class-time-table.weekly') ? 'active' : '' }}"
                        data-route="class-time-table.weekly" data-href="{{ route('class-time-table.weekly') }}">
                        <i class="bi bi-calendar3"></i>
                        <span>Timetable</span>
                    </button>
                </div>
            @endif

            @if ($canTodayClasses)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('class-schedules.todayClasses') ? 'active' : '' }}"
                        data-route="class-schedules.todayClasses"
                        data-href="{{ route('class-schedules.todayClasses') }}">
                        <i class="bi bi-calendar2-check-fill"></i>
                        <span>Today's Classes</span>
                    </button>
                </div>
            @endif
        </div>


        <div class="sidebar-section">
            <div class="sidebar-section-title">STUDENT SERVICES</div>

            @if ($canAttendance)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('new-attendance.index') ? 'active' : '' }}"
                        data-route="new-attendance.index" data-href="{{ route('new-attendance.index') }}">
                        <i class="bi bi-calendar2-check-fill"></i>
                        <span>Attendance</span>
                    </button>
                </div>
            @endif

            @if ($canPayments)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('new-payment.index') ? 'active' : '' }}"
                        data-route="new-payment.index" data-href="{{ route('new-payment.index') }}">
                        <i class="bi bi-credit-card-fill"></i>
                        <span>Payments</span>
                    </button>
                </div>
            @endif

            @if ($canPaymentHistory)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('payments.*') ? 'active' : '' }}"
                        data-route="payments.index" data-href="{{ route('payments.index') }}">
                        <i class="bi bi-piggy-bank-fill"></i>
                        <span>Payments History</span>
                    </button>
                </div>
            @endif
        </div>


        <!-- MANAGEMENT -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">MANAGEMENT</div>

            @if ($canStudents)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('students.*') ? 'active' : '' }}"
                        data-route="students.index" data-href="{{ route('students.index') }}">
                        <i class="bi bi-people-fill"></i>
                        <span>Students</span>
                    </button>
                </div>
            @endif

            @if ($canTeachers)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('teachers.*') ? 'active' : '' }}"
                        data-route="teachers.index" data-href="{{ route('teachers.index') }}">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Teachers</span>
                    </button>
                </div>
            @endif

            @if ($canOrganizers)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('organizers.*') ? 'active' : '' }}"
                        data-route="organizers.index" data-href="{{ route('organizers.index') }}">
                        <i class="bi bi-calendar-check-fill"></i>
                        <span>Organizers</span>
                    </button>
                </div>
            @endif

            @if ($canStudentClassManagement)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('student-class-management.*') ? 'active' : '' }}"
                        data-route="student-class-management.index"
                        data-href="{{ route('student-class-management.index') }}">
                        <i class="bi bi-person-workspace"></i>
                        <span>Student Class Mgmt</span>
                    </button>
                </div>
            @endif

            @if ($canSystemUsers)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.system-users.*') ? 'active' : '' }}"
                        data-route="admin.system-users.index" data-href="{{ route('admin.system-users.index') }}">
                        <i class="bi bi-people-fill"></i>
                        <span>System User</span>
                    </button>
                </div>
            @endif

            @if ($canUsers)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                        data-route="admin.users.index" data-href="{{ route('admin.users.index') }}">
                        <i class="bi bi-person-circle"></i>
                        <span>Users</span>
                    </button>
                </div>
            @endif
        </div>


        <div class="sidebar-section">
            <div class="sidebar-section-title">STUDENT SERVICES</div>

            @if ($canStudentImages)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.student-images.*') ? 'active' : '' }}"
                        data-route="admin.student-images.index" data-href="{{ route('admin.student-images.index') }}">
                        <i class="bi bi-images"></i>
                        <span>Student Images</span>
                    </button>
                </div>
            @endif

            @if ($canStudentCards)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('student-cards.*') ? 'active' : '' }}"
                        data-route="student-cards.index" data-href="{{ route('student-cards.index') }}">
                        <i class="bi bi-person-vcard-fill"></i>
                        <span>Student Cards</span>
                    </button>
                </div>
            @endif

            @if ($canTemporaryIdCards)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('temporary_id_cards.*') ? 'active' : '' }}"
                        data-route="temporary_id_cards.index" data-href="{{ route('temporary_id_cards.index') }}">
                        <i class="bi bi-person-vcard-fill"></i>
                        <span>Temporary Cards</span>
                    </button>
                </div>
            @endif

            @if ($canGenerateStudentId)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('generate-student-id.*') ? 'active' : '' }}"
                        data-route="generate-student-id.index" data-href="{{ route('generate-student-id.index') }}">
                        <i class="bi bi-person-vcard-fill"></i>
                        <span>Generate Student ID</span>
                    </button>
                </div>
            @endif
        </div>


        <!-- ACADEMIC -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">ACADEMIC</div>

            @if ($canStudentClasses)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('student-classes.*') ? 'active' : '' }}"
                        data-route="student-classes.index" data-href="{{ route('student-classes.index') }}">
                        <i class="bi bi-book-fill"></i>
                        <span>Classes</span>
                    </button>
                </div>
            @endif

            @if ($canClassSchedules)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('class-schedules.*') ? 'active' : '' }}"
                        data-route="class-schedules.index" data-href="{{ route('class-schedules.index') }}">
                        <i class="bi bi-calendar-event-fill"></i>
                        <span>Class Schedule</span>
                    </button>
                </div>
            @endif

            @if ($canEnrollments)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('student-class-enrollments.*') ? 'active' : '' }}"
                        data-route="student-class-enrollments.index"
                        data-href="{{ route('student-class-enrollments.index') }}">
                        <i class="bi bi-pencil-square"></i>
                        <span>Enrollments</span>
                    </button>
                </div>
            @endif

            @if ($isAdmin)
                @if ($isSuperAdmin)
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('payment-rules.*') ? 'active' : '' }}"
                            data-route="payment-rules.index" data-href="{{ route('payment-rules.index') }}">
                            <i class="bi bi-sliders"></i>
                            <span>Payment &amp; Attendance Rules</span>
                        </button>
                    </div>
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('card-designer.*') ? 'active' : '' }}"
                            data-route="card-designer.index" data-href="{{ route('card-designer.index') }}">
                            <i class="bi bi-person-badge"></i>
                            <span>ID Card Designer</span>
                        </button>
                    </div>
                @endif
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('app-setup.*') ? 'active' : '' }}"
                        data-route="app-setup.index" data-href="{{ route('app-setup.index') }}">
                        <i class="bi bi-qr-code"></i>
                        <span>Staff App QR</span>
                    </button>
                </div>
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('close-grade.*') ? 'active' : '' }}"
                        data-route="close-grade.index" data-href="{{ route('close-grade.index') }}">
                        <i class="bi bi-archive-fill"></i>
                        <span>Close Batch (Grade)</span>
                    </button>
                </div>
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('class-promotions.*') ? 'active' : '' }}"
                        data-route="class-promotions.index" data-href="{{ route('class-promotions.index') }}">
                        <i class="bi bi-arrow-up-circle-fill"></i>
                        <span>Promote Class</span>
                    </button>
                </div>
            @endif
        </div>


        <!-- COURSES (feature "courses": only institutes that have it) -->
        @feature('courses')
            @if ($isUser)
                <div class="sidebar-section">
                    <div class="sidebar-section-title">COURSES</div>

                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('courses.*') ? 'active' : '' }}"
                            data-route="courses.index" data-href="{{ route('courses.index') }}">
                            <i class="bi bi-mortarboard-fill"></i>
                            <span>Courses</span>
                        </button>
                    </div>
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('course-desk.enroll') ? 'active' : '' }}"
                            data-route="course-desk.enroll" data-href="{{ route('course-desk.enroll') }}">
                            <i class="bi bi-person-plus-fill"></i>
                            <span>Course Enrollment</span>
                        </button>
                    </div>
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('course-desk.attendance') ? 'active' : '' }}"
                            data-route="course-desk.attendance" data-href="{{ route('course-desk.attendance') }}">
                            <i class="bi bi-person-check-fill"></i>
                            <span>Course Attendance</span>
                        </button>
                    </div>
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('course-desk.payment') ? 'active' : '' }}"
                            data-route="course-desk.payment" data-href="{{ route('course-desk.payment') }}">
                            <i class="bi bi-cash-coin"></i>
                            <span>Course Payment</span>
                        </button>
                    </div>
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('course-payments.*') ? 'active' : '' }}"
                            data-route="course-payments.index" data-href="{{ route('course-payments.index') }}">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span>Course Payments History</span>
                        </button>
                    </div>

                    @if ($isBranchAdmin)
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('course-teacher-payments.*') ? 'active' : '' }}"
                            data-route="course-teacher-payments.index" data-href="{{ route('course-teacher-payments.index') }}">
                            <i class="bi bi-person-badge-fill"></i>
                            <span>Course Teacher Payments</span>
                        </button>
                    </div>
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('course-reports.*') ? 'active' : '' }}"
                            data-route="course-reports.show" data-href="{{ route('course-reports.show', "daily") }}">
                            <i class="bi bi-bar-chart-fill"></i>
                            <span>Course Reports</span>
                        </button>
                    </div>
                    @endif
                </div>
            @endif
        @endfeature


        <!-- EXAM -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">EXAM</div>

            @if ($canExams)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.exams.*') ? 'active' : '' }}"
                        data-route="admin.exams.index" data-href="{{ route('admin.exams.index') }}">
                        <i class="bi bi-calendar-check"></i>
                        <span>All Exams</span>
                    </button>
                </div>
            @endif
        </div>


        <!-- STUDENT FINANCE -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">STUDENT FINANCE</div>

            @if ($canPayments)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admissions.*') ? 'active' : '' }}"
                        data-route="admissions.index" data-href="{{ route('admissions.index') }}">
                        <i class="bi bi-credit-card-fill"></i>
                        <span>Admissions</span>
                    </button>
                </div>
            @endif

            @if ($canPayments)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admission-payments.*') ? 'active' : '' }}"
                        data-route="admission-payments.index" data-href="{{ route('admission-payments.index') }}">
                        <i class="bi bi-credit-card-fill"></i>
                        <span>Admissions Payments</span>
                    </button>
                </div>
            @endif

            @if ($canPaymentReminder)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('payment-reminder.*') ? 'active' : '' }}"
                        data-route="payment-reminder.index" data-href="{{ route('payment-reminder.index') }}">
                        <i class="bi bi-bell-fill"></i>
                        <span>Payment Reminder</span>
                    </button>
                </div>
            @endif
        </div>


        <!-- INSTITUTE FINANCE -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">INSTITUTE FINANCE</div>

            @if ($canTeacherSalaries)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('teacher-salaries.*') ? 'active' : '' }}"
                        data-route="teacher-salaries.index" data-href="{{ route('teacher-salaries.index') }}">
                        <i class="bi bi-cash-stack"></i>
                        <span>Teacher Salaries</span>
                    </button>
                </div>
                @feature('welfare')
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('welfare.index') ? 'active' : '' }}"
                            data-route="welfare.index" data-href="{{ route('welfare.index') }}">
                            <i class="bi bi-heart-pulse"></i>
                            <span>Teacher Welfare</span>
                        </button>
                    </div>
                    <div class="nav-item">
                        <button type="button"
                            class="nav-link-custom {{ request()->routeIs('welfare.fund*') ? 'active' : '' }}"
                            data-route="welfare.fund" data-href="{{ route('welfare.fund') }}">
                            <i class="bi bi-piggy-bank"></i>
                            <span>Welfare Fund</span>
                        </button>
                    </div>
                @endfeature
            @endif

            @if ($canOrganizerPayments)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.organizer-payments.index') ? 'active' : '' }}"
                        data-route="admin.organizer-payments.index"
                        data-href="{{ route('admin.organizer-payments.index') }}">
                        <i class="bi bi-wallet2"></i>
                        <span>Organizer Payments</span>
                    </button>
                </div>
            @endif

            @if ($canExtraIncomes)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.extra-incomes.*') ? 'active' : '' }}"
                        data-route="admin.extra-incomes.index" data-href="{{ route('admin.extra-incomes.index') }}">
                        <i class="bi bi-cash-coin"></i>
                        <span>Extra Incomes</span>
                    </button>
                </div>
            @endif

            @if ($canInstituteExpenses)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.institute-expenses.*') ? 'active' : '' }}"
                        data-route="admin.institute-expenses.index"
                        data-href="{{ route('admin.institute-expenses.index') }}">
                        <i class="bi bi-cash-coin"></i>
                        <span>Institute Expenses</span>
                    </button>
                </div>
            @endif

            @if ($canInstituteIncome)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('reports.institute-income.*') ? 'active' : '' }}"
                        data-route="reports.institute-income.index"
                        data-href="{{ route('reports.institute-income.index') }}">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>Institute Income</span>
                    </button>
                </div>
            @endif
        </div>


        <!-- RECEIPTS -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">RECEIPTS</div>

            @if ($canReceipts)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('receipts.*') ? 'active' : '' }}"
                        data-route="receipts.index" data-href="{{ route('receipts.index') }}">
                        <i class="bi bi-file-earmark-person"></i>
                        <span>Receipts</span>
                    </button>
                </div>
            @endif
        </div>


        <!-- REPORTS -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">REPORTS</div>

            @if ($canDailyReport)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('reports.daily-collection.*') ? 'active' : '' }}"
                        data-route="reports.daily-collection.index"
                        data-href="{{ route('reports.daily-collection.index') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Collection Report</span>
                    </button>
                </div>
            @endif

            @if ($canMonthlyReport)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('monthly-report.*') ? 'active' : '' }}"
                        data-route="monthly-report.index" data-href="{{ route('monthly-report.index') }}">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Monthly Report</span>
                    </button>
                </div>
            @endif

            @if ($canTeacherReport)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('reports.teacher-collection.*') ? 'active' : '' }}"
                        data-route="reports.teacher-collection.index"
                        data-href="{{ route('reports.teacher-collection.index') }}">
                        <i class="bi bi-file-earmark-person"></i>
                        <span>Teacher Collection</span>
                    </button>
                </div>
            @endif

            @if ($canMonthlyClassAttendanceReport)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.monthly-class-attendance-report.*') ? 'active' : '' }}"
                        data-route="admin.monthly-class-attendance-report.index"
                        data-href="{{ route('admin.monthly-class-attendance-report.index') }}">
                        <i class="bi bi-calendar-check"></i>
                        <span>Monthly Class Attendance Report</span>
                    </button>
                </div>
            @endif

            @if ($canPaymentCollectionReport)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('reports.monthly-collection.*') ? 'active' : '' }}"
                        data-route="reports.monthly-collection.index"
                        data-href="{{ route('reports.monthly-collection.index') }}">
                        <i class="bi bi-cash-stack"></i>
                        <span>Monthly Collection</span>
                    </button>
                </div>
            @endif
            @if ($canBranchCollectionReport)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('reports.branch-collection.*') ? 'active' : '' }}"
                        data-route="reports.branch-collection.index"
                        data-href="{{ route('reports.branch-collection.index') }}">
                        <i class="bi bi-diagram-3"></i>
                        <span>Branch-wise Collection</span>
                    </button>
                </div>
            @endif
            @if ($canAnnualReport)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('reports.annual.*') ? 'active' : '' }}"
                        data-route="reports.annual.index" data-href="{{ route('reports.annual.index') }}">
                        <i class="bi bi-calendar-range"></i>
                        <span>Annual Report</span>
                    </button>
                </div>
            @endif
        </div>


        <div class="sidebar-section">
            <div class="sidebar-section-title">RECENT ACTIVITIES</div>

            @if ($canActivityLogs)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}"
                        data-route="admin.activity-logs.index" data-href="{{ route('admin.activity-logs.index') }}">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Activities</span>
                    </button>
                </div>
            @endif

            @if ($isSuperAdmin)
                <div class="nav-item">
                    <button type="button"
                        class="nav-link-custom {{ request()->routeIs('admin.logs.laravel.*') ? 'active' : '' }}"
                        data-route="admin.logs.laravel.index" data-href="{{ route('logs.laravel.index') }}">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Laravel Logs</span>
                    </button>
                </div>
            @endif
        </div>

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

        restoreSidebarScroll();
    })();
</script>
