@extends('layouts.app')

@section('title', 'Student Analytics')
@section('page-title', 'Student Analytics')

@section('breadcrumb')
    <li class="ui-breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="ui-breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
    <li class="ui-breadcrumb-item active">Student Analytics</li>
@endsection

@section('content')
    <div class="ui-container-fluid">
        <!-- Header Section -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="tw:flex! tw:flex-col! tw:md:flex-row! tw:justify-between! tw:items-start! tw:md:items-center!">
                    <div class="tw:mb-4! tw:md:mb-0!">
                        <h2 class="ui-h3 tw:mb-1! text-gray-800">Student Analytics Dashboard</h2>
                        <p class="tw:text-bs-secondary-color! tw:mb-0!">Comprehensive overview of student performance and activities</p>
                    </div>
                    <div class="tw:flex! tw:flex-wrap! tw:gap-2!">
                        <button class="ui-btn ui-btn-outline-primary" onclick="refreshData()" id="refreshBtn">
                            <i class="fas fa-sync-alt tw:mr-2!"></i>Refresh
                        </button>
                        <div class="ui-dropdown">
                            <button class="ui-btn ui-btn-outline-secondary ui-dropdown-toggle" type="button"
                                data-nx-toggle="dropdown">
                                <i class="fas fa-download tw:mr-2!"></i>Export
                            </button>
                            <ul class="ui-dropdown-menu">
                                <li><a class="ui-dropdown-item" href="#" onclick="exportReport('pdf')">
                                        <i class="fas fa-file-pdf tw:mr-2! tw:text-bs-danger!"></i>PDF Report
                                    </a></li>
                                <li><a class="ui-dropdown-item" href="#" onclick="exportReport('excel')">
                                        <i class="fas fa-file-excel tw:mr-2! tw:text-bs-success!"></i>Excel Report
                                    </a></li>
                            </ul>
                        </div>
                        <button class="ui-btn ui-btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print tw:mr-2!"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Profile Card -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!">
                    <div class="ui-card-header tw:bg-bs-primary! tw:text-bs-white! tw:py-4! tw:bs-border-b!">
                        <div
                            class="tw:flex! tw:flex-col! tw:md:flex-row! tw:justify-between! tw:items-start! tw:md:items-center!">
                            <h5 class="ui-card-title tw:mb-2! tw:md:mb-0! tw:text-bs-white!">
                                <i class="fas fa-user-graduate tw:mr-2!"></i>Student Profile
                            </h5>
                            <span class="ui-badge tw:bg-bs-white! tw:text-bs-primary! tw:text-[length:1rem]! tw:px-4! tw:py-2!" id="studentStatusBadge">
                                <i class="fas fa-circle fa-xs tw:mr-1!"></i>Loading...
                            </span>
                        </div>
                    </div>
                    <div class="ui-card-body">
                        <div id="studentInfo">
                            <div class="tw:text-center! tw:py-6!">
                                <div class="ui-spinner-border tw:text-bs-primary!" role="status">
                                    <span class="tw:sr-only">Loading...</span>
                                </div>
                                <p class="tw:mt-2! tw:mb-0! tw:text-bs-secondary-color!">Loading student information...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Dashboard -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 tw:mb-6!" id="statsSection">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!">
                    <div class="ui-card-header tw:bg-bs-primary! tw:text-bs-white! tw:py-4! tw:bs-border-b!">
                        <div
                            class="tw:flex! tw:flex-col! tw:md:flex-row! tw:justify-between! tw:items-start! tw:md:items-center!">
                            <h5 class="ui-card-title tw:mb-0! tw:text-bs-white!">
                                <i class="fas fa-chart-line tw:mr-2!"></i>Performance Overview
                            </h5>
                            <div class="tw:mt-2! tw:md:mt-0!">
                                <span class="ui-badge tw:bg-bs-white! tw:text-bs-primary!">All Time Data</span>
                            </div>
                        </div>
                    </div>
                    <div class="ui-card-body">
                        <!-- Loading State -->
                        <div class="tw:text-center! tw:py-12!" id="statsLoading">
                            <div class="ui-spinner-border tw:text-bs-primary! tw:mb-4!" role="status">
                                <span class="tw:sr-only">Loading statistics...</span>
                            </div>
                            <p class="tw:text-bs-secondary-color! tw:mb-0!">Loading performance metrics...</p>
                        </div>

                        <!-- Stats Content -->
                        <div id="statsContent" class="tw:hidden!">
                            <!-- Main Stats Row -->
                            <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">
                                <!-- Total Enrollments -->
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                                    <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full! tw:bg-[rgba(13,110,253,0.1)]!">
                                        <div class="ui-card-body">
                                            <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-4!">
                                                <div>
                                                    <h6 class="tw:text-bs-secondary-color! tw:mb-1!">Total Classes</h6>
                                                    <h2 class="tw:mb-0! tw:text-bs-primary!" id="totalEnrollments">0</h2>
                                                </div>
                                                <div class="tw:bg-[rgba(13,110,253,0.25)]! tw:p-4! tw:rounded-[50%]!">
                                                    <i class="fas fa-chalkboard-teacher fa-lg tw:text-bs-primary!"></i>
                                                </div>
                                            </div>
                                            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3">
                                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                                    <small class="tw:text-bs-secondary-color!">Active</small>
                                                    <div class="tw:font-bold! tw:text-bs-success!" id="activeEnrollments">0</div>
                                                </div>
                                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                                    <small class="tw:text-bs-secondary-color!">Inactive</small>
                                                    <div class="tw:font-bold! tw:text-bs-secondary!" id="inactiveEnrollments">0</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Overview -->
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                                    <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full! tw:bg-[rgba(25,135,84,0.1)]!">
                                        <div class="ui-card-body">
                                            <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-4!">
                                                <div>
                                                    <h6 class="tw:text-bs-secondary-color! tw:mb-1!">Financial Summary</h6>
                                                    <h2 class="tw:mb-0! tw:text-bs-success!" id="totalFees">Rs. 0</h2>
                                                </div>
                                                <div class="tw:bg-[rgba(25,135,84,0.25)]! tw:p-4! tw:rounded-[50%]!">
                                                    <i class="fas fa-money-bill-wave fa-lg tw:text-bs-success!"></i>
                                                </div>
                                            </div>
                                            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3">
                                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                                    <small class="tw:text-bs-secondary-color!">Paid</small>
                                                    <div class="tw:font-bold! tw:text-bs-success!" id="totalPaid">Rs. 0</div>
                                                </div>
                                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                                    <small class="tw:text-bs-secondary-color!">Due</small>
                                                    <div class="tw:font-bold! tw:text-bs-danger!" id="totalDue">Rs. 0</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Attendance Overview -->
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                                    <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full! tw:bg-[rgba(13,202,240,0.1)]!">
                                        <div class="ui-card-body">
                                            <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-4!">
                                                <div>
                                                    <h6 class="tw:text-bs-secondary-color! tw:mb-1!">Attendance Rate</h6>
                                                    <h2 class="tw:mb-0! tw:text-bs-info!" id="attendanceRate">0%</h2>
                                                </div>
                                                <div class="tw:bg-[rgba(13,202,240,0.25)]! tw:p-4! tw:rounded-[50%]!">
                                                    <i class="fas fa-user-check fa-lg tw:text-bs-info!"></i>
                                                </div>
                                            </div>
                                            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3">
                                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                                    <small class="tw:text-bs-secondary-color!">Present</small>
                                                    <div class="tw:font-bold! tw:text-bs-success!" id="totalPresent">0</div>
                                                </div>
                                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                                    <small class="tw:text-bs-secondary-color!">Absent</small>
                                                    <div class="tw:font-bold! tw:text-bs-warning!" id="totalAbsent">0</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Class Sessions -->
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                                    <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full! tw:bg-[rgba(255,193,7,0.1)]!">
                                        <div class="ui-card-body">
                                            <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-4!">
                                                <div>
                                                    <h6 class="tw:text-bs-secondary-color! tw:mb-1!">Class Sessions</h6>
                                                    <h2 class="tw:mb-0! tw:text-bs-warning!" id="totalSessions">0</h2>
                                                </div>
                                                <div class="tw:bg-[rgba(255,193,7,0.25)]! tw:p-4! tw:rounded-[50%]!">
                                                    <i class="fas fa-calendar-alt fa-lg tw:text-bs-warning!"></i>
                                                </div>
                                            </div>
                                            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3">
                                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                                    <small class="tw:text-bs-secondary-color!">Avg/Class</small>
                                                    <div class="tw:font-bold! tw:text-bs-warning!" id="avgSessions">0</div>
                                                </div>
                                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                                    <small class="tw:text-bs-secondary-color!">Free Cards</small>
                                                    <div class="tw:font-bold! tw:text-bs-primary!" id="freeCards">0</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bars Row -->
                            <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">
                                <!-- Payment Progress -->
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:xl:flex-[0_0_auto] tw:xl:w-[50%]">
                                    <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">
                                        <div class="ui-card-header tw:bg-bs-white! tw:py-4! tw:bs-border-b!">
                                            <div class="tw:flex! tw:justify-between! tw:items-center!">
                                                <h6 class="tw:mb-0!"><i class="fas fa-credit-card tw:mr-2! tw:text-bs-primary!"></i>Payment
                                                    Progress</h6>
                                                <span class="ui-badge tw:bg-[rgba(13,110,253,0.1)]! tw:text-bs-primary!"
                                                    id="paymentCompletion">0%</span>
                                            </div>
                                        </div>
                                        <div class="ui-card-body">
                                            <div class="ui-progress" style="height: 10px;">
                                                <div class="ui-progress-bar tw:bg-bs-success!" id="paymentProgress" style="width: 0%"
                                                    role="progressbar"></div>
                                            </div>
                                            <div class="tw:mt-2! tw:flex! tw:justify-between!">
                                                <small class="tw:text-bs-secondary-color!">Paid: <span class="tw:font-semibold!"
                                                        id="paidAmount">Rs. 0</span></small>
                                                <small class="tw:text-bs-secondary-color!">Total: <span class="tw:font-semibold!"
                                                        id="totalFeesAmount">Rs. 0</span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Attendance Progress -->
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:xl:flex-[0_0_auto] tw:xl:w-[50%]">
                                    <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:h-full!">
                                        <div class="ui-card-header tw:bg-bs-white! tw:py-4! tw:bs-border-b!">
                                            <div class="tw:flex! tw:justify-between! tw:items-center!">
                                                <h6 class="tw:mb-0!"><i class="fas fa-chart-bar tw:mr-2! tw:text-bs-info!"></i>Attendance
                                                    Progress</h6>
                                                <span class="ui-badge tw:bg-[rgba(13,202,240,0.1)]! tw:text-bs-info!"
                                                    id="attendanceCompletion">0%</span>
                                            </div>
                                        </div>
                                        <div class="ui-card-body">
                                            <div class="ui-progress" style="height: 10px;">
                                                <div class="ui-progress-bar tw:bg-bs-info!" id="attendanceProgress" style="width: 0%"
                                                    role="progressbar"></div>
                                            </div>
                                            <div class="tw:mt-2! tw:flex! tw:justify-between!">
                                                <small class="tw:text-bs-secondary-color!">Attended: <span class="tw:font-semibold!"
                                                        id="attendedCount">0</span></small>
                                                <small class="tw:text-bs-secondary-color!">Total Sessions: <span class="tw:font-semibold!"
                                                        id="totalSessionsCount">0</span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Enrollments Section -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 tw:mb-6!" id="enrollmentsSection">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!">
                    <div class="ui-card-header tw:bg-bs-primary! tw:text-bs-white! tw:py-4! tw:bs-border-b!">
                        <div
                            class="tw:flex! tw:flex-col! tw:md:flex-row! tw:justify-between! tw:items-start! tw:md:items-center!">
                            <div class="tw:mb-2! tw:md:mb-0!">
                                <h5 class="ui-card-title tw:mb-0! tw:text-bs-white!">
                                    <i class="fas fa-chalkboard-teacher tw:mr-2!"></i>Class Enrollments
                                </h5>
                                <p class="tw:text-[rgba(255,255,255,0.5)]! tw:text-[.875em] tw:mb-0! tw:mt-1!">Detailed analytics for each enrolled class</p>
                            </div>
                            <div class="tw:flex! tw:flex-wrap! tw:items-center! tw:gap-2!">
                                <div class="ui-input-group ui-input-group-sm">
                                    <span class="ui-input-group-text tw:bg-bs-white!"><i
                                            class="fas fa-search tw:text-bs-primary!"></i></span>
                                    <input type="text" class="ui-form-control" placeholder="Search classes..." id="classSearch"
                                        style="width: 150px;">
                                </div>
                                <div class="ui-btn-group">
                                    <button class="ui-btn ui-btn-sm ui-btn-outline-light active"
                                        onclick="filterEnrollments('all')">All</button>
                                    <button class="ui-btn ui-btn-sm ui-btn-outline-light"
                                        onclick="filterEnrollments('active')">Active</button>
                                    <button class="ui-btn ui-btn-sm ui-btn-outline-light"
                                        onclick="filterEnrollments('free')">Free</button>
                                </div>
                                <button class="ui-btn ui-btn-sm ui-btn-outline-light" onclick="toggleSplitCategories()"
                                    id="toggleSplitBtn">
                                    <i class="fas fa-layer-group tw:mr-1!"></i>Show Split
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="ui-card-body tw:p-0!">
                        <!-- Loading State -->
                        <div id="enrollmentsLoading" class="tw:p-12!">
                            <div class="tw:text-center! tw:py-12!">
                                <div class="ui-spinner-border tw:text-bs-primary! tw:mb-4!" role="status">
                                    <span class="tw:sr-only">Loading...</span>
                                </div>
                                <p class="tw:text-bs-secondary-color! tw:mb-0!">Loading class analytics...</p>
                            </div>
                        </div>

                        <!-- Enrollments Grid -->
                        <div class="ui-row ui-g-4 tw:p-6! tw:hidden!" id="enrollmentsList">
                            <!-- Enrollments will be loaded here -->
                        </div>

                        <!-- No Enrollments State -->
                        <div id="noEnrollments" class="tw:text-center! tw:py-12! tw:hidden!">
                            <div class="tw:mb-6!">
                                <i class="fas fa-chalkboard-teacher fa-4x tw:text-bs-secondary-color! tw:[opacity:0.25]!"></i>
                            </div>
                            <h5 class="tw:text-bs-secondary-color! tw:font-semibold! tw:mb-2!">No Class Enrollments Found</h5>
                            <p class="tw:text-bs-secondary-color! tw:mb-6!">This student is not enrolled in any classes yet.</p>
                            <a href="{{ url('/students') }}" class="ui-btn ui-btn-primary">
                                <i class="fas fa-plus tw:mr-2!"></i>Enroll in Classes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Custom Bootstrap enhancements */
        .ui-card {
            border-radius: 10px;
            transition: transform 0.2s ease-in-out;
        }

        .ui-card:hover {
            transform: translateY(-2px);
        }

        .ui-card-header {
            border-radius: 10px 10px 0 0 !important;
        }

        /* Custom badge styles */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Progress bar customization */
        .ui-progress {
            border-radius: 10px;
            overflow: hidden;
        }

        .ui-progress-bar {
            border-radius: 10px;
        }

        /* Split category styling */
        .split-category-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .combined-category {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        /* Metric cards styling */
        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .ui-card-header h5 {
                font-size: 1.1rem;
            }

            .ui-btn-group .ui-btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
        }

        /* Animation for loading */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let studentCode = null; // custom_id
        let studentId = null;   // student_id from URL

        // Production Configuration
        const config = {
            apiBaseUrl: '/api',
            debounceTime: 300,
            alertTimeout: 5000,
            refreshInterval: null
        };

        // Get student ID from URL
        const getStudentIdFromUrl = () => {
            const pathArray = window.location.pathname.split('/');
            return pathArray[pathArray.length - 1];
        };

        studentId = getStudentIdFromUrl();

        // State Management
        let currentStudent = null;
        let enrollmentsData = [];
        let currentFilter = 'all';
        let showSplitCategories = false;

        // DOM Elements Cache
        const elements = {
            refreshBtn: null,
            studentInfo: null,
            studentStatusBadge: null,
            statsContent: null,
            statsLoading: null,
            enrollmentsList: null,
            enrollmentsLoading: null,
            noEnrollments: null,
            classSearch: null,
            toggleSplitBtn: null
        };

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function () {
            initializeElements();
            initializeEventListeners();
            loadStudentAnalytics();
        });

        // Initialize DOM elements
        function initializeElements() {
            elements.refreshBtn = document.getElementById('refreshBtn');
            elements.studentInfo = document.getElementById('studentInfo');
            elements.studentStatusBadge = document.getElementById('studentStatusBadge');
            elements.statsContent = document.getElementById('statsContent');
            elements.statsLoading = document.getElementById('statsLoading');
            elements.enrollmentsList = document.getElementById('enrollmentsList');
            elements.enrollmentsLoading = document.getElementById('enrollmentsLoading');
            elements.noEnrollments = document.getElementById('noEnrollments');
            elements.classSearch = document.getElementById('classSearch');
            elements.toggleSplitBtn = document.getElementById('toggleSplitBtn');
        }

        // Initialize event listeners
        function initializeEventListeners() {
            // Search with debounce
            let searchTimeout;
            elements.classSearch.addEventListener('keyup', function (e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    filterEnrollmentsBySearch(e.target.value.toLowerCase());
                }, config.debounceTime);
            });

            // Network status
            window.addEventListener('online', handleOnlineStatus);
            window.addEventListener('offline', handleOfflineStatus);

            // Network status handlers
        function handleOnlineStatus() {
            showAlert('Connection restored. Data will be refreshed.', 'success');
            loadStudentAnalytics();
        }

        function handleOfflineStatus() {
            showAlert('You are offline. Some features may not work.', 'warning');
        }

        // API Helper
        async function apiFetch(endpoint, options = {}) {
            const url = `${config.apiBaseUrl}/${endpoint}`;

            try {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        ...options.headers
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                return await response.json();
            } catch (error) {
                console.error(`API Error (${endpoint}):`, error);
                throw error;
            }
        }

        // Load Student Analytics
        async function loadStudentAnalytics() {
            try {
                setLoadingState(true);

                // Load student info and analytics in parallel
                await Promise.all([
                    loadStudentInfo(),
                    loadAnalyticsData()
                ]);

                showAlert('Data loaded successfully', 'success');
            } catch (error) {
                console.error('Error loading analytics:', error);
                showAlert('Failed to load analytics data. Please try again.', 'danger');
                showNoEnrollments();
            } finally {
                setLoadingState(false);
            }
        }

        // Set loading state
        function setLoadingState(isLoading) {
            if (elements.refreshBtn) {
                elements.refreshBtn.innerHTML = isLoading ?
                    '<i class="fas fa-spinner fa-spin tw:mr-2!"></i>Refreshing' :
                    '<i class="fas fa-sync-alt tw:mr-2!"></i>Refresh';
                elements.refreshBtn.disabled = isLoading;
            }
        }

        // Load Student Information
        async function loadStudentInfo() {
            try {
                const studentData = await apiFetch(`students/${studentId}`);
                currentStudent = studentData.data || studentData;

                // Save custom_id
                studentCode = currentStudent.custom_id;

                displayStudentInfo(currentStudent);
            } catch (error) {
                console.error('Error loading student info:', error);
                displayStudentInfo(null);
            }
        }

        // Load Analytics Data
        async function loadAnalyticsData() {
            try {
                const analyticsData = await apiFetch(`students/analytics/${studentId}`);

                // Handle different response structures
                let processedData = analyticsData;
                if (analyticsData.data) {
                    processedData = analyticsData.data;
                } else if (analyticsData.classes) {
                    processedData = analyticsData.classes;
                }

                // Check if it's an array - our new API returns array directly
                if (Array.isArray(processedData)) {
                    enrollmentsData = processedData;
                    displayStudentEnrollments(enrollmentsData);
                    updateStatistics(enrollmentsData);
                } else {
                    throw new Error('Invalid data format received from server');
                }
            } catch (error) {
                throw error;
            }
        }

        // Display Student Info
        function displayStudentInfo(student) {
            if (!student) {
                elements.studentInfo.innerHTML = `
                    <div class="ui-alert ui-alert-warning">
                        <i class="fas fa-exclamation-triangle tw:mr-2!"></i>
                        Unable to load student information
                    </div>
                `;
                return;
            }

            const studentName = `${student.fname || ''} ${student.lname || ''}`.trim();
            const grade = student.grade?.grade_name || 'Not Assigned';
            const studentCode = student.custom_id || student.code || 'N/A';

            // Check student status from API response (is_active field)
            const isStudentActive = student.is_active == 1;

            elements.studentInfo.innerHTML = `
                <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 tw:items-center!">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[66.66666667%]">
                        <div class="tw:flex! tw:items-center!">
                            <div class="tw:bg-[rgba(13,110,253,0.1)]! tw:p-4! tw:rounded-[50%]! tw:mr-4!">
                                <i class="fas fa-user-graduate fa-2x tw:text-bs-primary!"></i>
                            </div>
                            <div>
                                <h4 class="tw:mb-1!">${studentName}</h4>
                                <div class="tw:flex! tw:flex-wrap! tw:gap-4!">
                                    <div>
                                        <small class="tw:text-bs-secondary-color!">Student ID</small>
                                        <div class="tw:font-bold!">${studentCode}</div>
                                    </div>
                                    <div>
                                        <small class="tw:text-bs-secondary-color!">Grade</small>
                                        <div class="tw:font-bold!">${grade}</div>
                                    </div>
                                    <div>
                                        <small class="tw:text-bs-secondary-color!">Status</small>
                                        <div class="tw:font-bold!">
                                            <span class="ui-badge ${isStudentActive ? 'tw:bg-bs-success!' : 'tw:bg-bs-secondary!'}">
                                                <i class="fas fa-circle fa-xs tw:mr-1!"></i>
                                                ${isStudentActive ? 'Active Student' : 'Inactive Student'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:md:text-right! tw:mt-4! tw:md:mt-0!">
                        <div class="tw:flex! tw:flex-col! tw:sm:flex-row! tw:md:flex-col! tw:lg:flex-row! tw:gap-2!">
                            <button class="ui-btn ui-btn-outline-primary ui-btn-sm" onclick="viewStudentProfile()">
                                <i class="fas fa-eye tw:mr-2!"></i>View Profile
                            </button>
                            <button class="ui-btn ui-btn-outline-success ui-btn-sm" onclick="editStudentDetails()">
                                <i class="fas fa-edit tw:mr-2!"></i>Edit Details
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Update student status badge
            updateStudentStatusBadge(isStudentActive);
        }

        // Update Student Status Badge
        function updateStudentStatusBadge(isStudentActive) {
            if (!elements.studentStatusBadge) return;

            if (isStudentActive) {
                elements.studentStatusBadge.innerHTML = '<i class="fas fa-circle fa-xs tw:mr-1! tw:text-bs-success!"></i>Active Student';
                elements.studentStatusBadge.className = 'ui-badge tw:bg-bs-success! bg-opacity-10 tw:text-bs-success! tw:text-[length:1rem]! tw:px-4! tw:py-2!';
            } else {
                elements.studentStatusBadge.innerHTML = '<i class="fas fa-circle fa-xs tw:mr-1! tw:text-bs-secondary!"></i>Inactive Student';
                elements.studentStatusBadge.className = 'ui-badge tw:bg-bs-secondary! bg-opacity-10 tw:text-bs-secondary! tw:text-[length:1rem]! tw:px-4! tw:py-2!';
            }
        }

        // Update Statistics based on new API structure
        function updateStatistics(enrollments) {
            if (!Array.isArray(enrollments)) {
                enrollments = [];
            }

            // Calculate statistics
            const totalClasses = enrollments.length;
            const activeClasses = enrollments.filter(e => e.status === true).length;  // status is boolean true/false
            const inactiveClasses = totalClasses - activeClasses;
            
            // Free cards - based on is_free_card field
            const freeCards = enrollments.filter(e => e.is_free_card === 1).length;

            // Financial calculations
            const totalFees = enrollments.reduce((sum, e) => sum + (e.category_info?.fees || 0), 0);
            const totalPaid = enrollments.reduce((sum, e) => sum + (e.payments?.summary?.total_paid || 0), 0);
            const totalDue = Math.max(0, totalFees - totalPaid);

            // Attendance calculations
            const totalSessions = enrollments.reduce((sum, e) => sum + (e.class_attendance?.total_sessions || 0), 0);
            const totalPresent = enrollments.reduce((sum, e) => sum + (e.student_attendance?.present_count || 0), 0);
            const totalAbsent = enrollments.reduce((sum, e) => sum + (e.student_attendance?.absent_count || 0), 0);

            const attendanceRate = totalSessions > 0 ? Math.round((totalPresent / totalSessions) * 100) : 0;
            const avgSessions = totalClasses > 0 ? Math.round(totalSessions / totalClasses) : 0;
            const paymentCompletion = totalFees > 0 ? Math.round((totalPaid / totalFees) * 100) : 100;

            // Hide loading, show content with animation
            if (elements.statsLoading && elements.statsContent) {
                elements.statsLoading.style.display = 'none';
                elements.statsContent.classList.remove('tw:hidden!');
                elements.statsContent.classList.add('fade-in');
            }

            // Update main stats
            updateElementText('totalEnrollments', totalClasses);
            updateElementText('activeEnrollments', activeClasses);
            updateElementText('inactiveEnrollments', inactiveClasses);
            updateElementText('freeCards', freeCards);
            updateElementText('totalFees', `Rs. ${totalFees.toLocaleString()}`);
            updateElementText('totalPaid', `Rs. ${totalPaid.toLocaleString()}`);
            updateElementText('totalDue', `Rs. ${totalDue.toLocaleString()}`);
            updateElementText('totalSessions', totalSessions);
            updateElementText('avgSessions', avgSessions);
            updateElementText('totalPresent', totalPresent);
            updateElementText('totalAbsent', totalAbsent);
            updateElementText('attendanceRate', `${attendanceRate}%`);

            // Update progress bars
            updateProgressBar('paymentProgress', paymentCompletion);
            updateProgressBar('attendanceProgress', attendanceRate);
            updateElementText('paymentCompletion', `${paymentCompletion}%`);
            updateElementText('attendanceCompletion', `${attendanceRate}%`);
            updateElementText('paidAmount', `Rs. ${totalPaid.toLocaleString()}`);
            updateElementText('totalFeesAmount', `Rs. ${totalFees.toLocaleString()}`);
            updateElementText('attendedCount', totalPresent);
            updateElementText('totalSessionsCount', totalSessions);
        }

        // Helper function to update element text
        function updateElementText(elementId, text) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = text;
            }
        }

        // Helper function to update progress bar
        function updateProgressBar(elementId, percentage) {
            const element = document.getElementById(elementId);
            if (element) {
                element.style.width = `${percentage}%`;
            }
        }

        // Display Student Enrollments with Exam Results button
        function displayStudentEnrollments(enrollments) {
            if (!elements.enrollmentsLoading || !elements.enrollmentsList || !elements.noEnrollments) return;

            // Hide loading, show list with animation
            elements.enrollmentsLoading.style.display = 'none';
            elements.enrollmentsList.classList.remove('tw:hidden!');
            elements.enrollmentsList.classList.add('fade-in');
            elements.enrollmentsList.innerHTML = '';

            if (!Array.isArray(enrollments) || enrollments.length === 0) {
                showNoEnrollments();
                return;
            }

            let enrollmentsHTML = '';

            enrollments.forEach((enrollment) => {
                // Get enrollment_id and classCategoryHasStudentClassId
                const enrollmentId = enrollment.enrollment_id;
                const classCategoryHasStudentClassId = enrollment.category_info?.class_category_has_student_class_id;
                const classInfo = enrollment.class_info || {};
                const categoryInfo = enrollment.category_info || {};
                const payments = enrollment.payments?.summary || {};
                const classAttendance = enrollment.class_attendance || {};
                const studentAttendance = enrollment.student_attendance || {};

                // Class information
                const className = classInfo.class_name || 'No Class Name';
                const teacherName = classInfo.teacher ?
                    `${classInfo.teacher.first_name || ''} ${classInfo.teacher.last_name || ''}`.trim() :
                    'N/A';
                const subjectName = classInfo.subject?.subject_name || 'N/A';
                const gradeName = classInfo.grade?.grade_name || 'N/A';

                // Category information
                const categoryName = categoryInfo.category_name || 'General';
                const fees = categoryInfo.fees || 0;
                const isSplitCategory = categoryInfo.is_split_category || false;

                // Status and badges
                const isActive = enrollment.status === true;  // status is boolean
                const isFreeCard = enrollment.is_free_card === 1;
                const enrollmentDate = formatDate(enrollment.enrollment_date);

                // Payment information
                const paymentCount = payments.payment_count || 0;
                const totalPaid = payments.total_paid || 0;
                const isFullyPaid = payments.is_fully_paid || false;
                const paymentPercentage = fees > 0 ? Math.round((totalPaid / fees) * 100) : 100;

                // Attendance information
                const totalSessions = classAttendance.total_sessions || 0;
                const presentCount = studentAttendance.present_count || 0;
                const absentCount = studentAttendance.absent_count || 0;
                const attendancePercentage = studentAttendance.attendance_rate || 0;

                // Determine if we should show this enrollment based on split category toggle
                if (!showSplitCategories && isSplitCategory) {
                    // Don't show individual split categories when toggle is off
                    return;
                }

                enrollmentsHTML += `
                    <div class="ui-col-xl-6 ui-col-lg-12" 
                         data-enrollment-id="${enrollmentId}"
                         data-class-category-id="${classCategoryHasStudentClassId}"
                         data-status="${isActive ? 'active' : 'inactive'}" 
                         data-type="${isFreeCard ? 'free' : 'paid'}" 
                         data-category-type="${isSplitCategory ? 'split' : 'regular'}">
                        <div class="ui-card tw:bs-border! tw:h-full! ${isSplitCategory ? 'combined-category' : ''}">
                            <div class="ui-card-header ${isSplitCategory ? 'tw:text-bs-white!' : 'tw:bg-bs-white!'}">
                                <div class="tw:flex! tw:justify-between! tw:items-center!">
                                    <div>
                                        <h6 class="tw:mb-0! tw:font-bold! ${isSplitCategory ? 'tw:text-bs-white!' : 'tw:text-bs-dark!'}">${className}</h6>
                                        ${isSplitCategory ?
                                            '<small class="tw:[opacity:0.75]!"><i class="fas fa-layer-group fa-xs tw:mr-1!"></i>Part of Combined Category</small>' :
                                            ''
                                        }
                                    </div>
                                    <div class="tw:flex! tw:gap-2!">
                                        ${isSplitCategory ?
                                            '<span class="split-category-badge"><i class="fas fa-link fa-xs tw:mr-1!"></i>Split</span>' :
                                            ''
                                        }
                                        <span class="ui-badge ${isActive ? 'tw:bg-bs-success!' : 'tw:bg-bs-secondary!'}">
                                            ${isActive ? 'Active' : 'Inactive'}
                                        </span>
                                        ${isFreeCard ?
                                            '<span class="ui-badge tw:bg-bs-warning! tw:text-bs-dark!"><i class="fas fa-crown tw:mr-1!"></i>Free Card</span>' :
                                            ''
                                        }
                                    </div>
                                </div>
                            </div>
                            <div class="ui-card-body">
                                <!-- Class Info -->
                                <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 tw:mb-4!">
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                        <small class="tw:text-bs-secondary-color! tw:block!">Teacher</small>
                                        <div class="tw:font-bold!">${teacherName}</div>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                        <small class="tw:text-bs-secondary-color! tw:block!">Category</small>
                                        <div class="tw:font-bold! ${isSplitCategory ? 'tw:text-bs-primary!' : ''}">${categoryName}</div>
                                        ${isSplitCategory ?
                                            '<small class="tw:text-bs-secondary-color!">(Individual Category)</small>' :
                                            ''
                                        }
                                    </div>
                                </div>
                                <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 tw:mb-6!">
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                        <small class="tw:text-bs-secondary-color! tw:block!">Subject</small>
                                        <span class="ui-badge tw:bg-bs-info!">${subjectName}</span>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[50%]">
                                        <small class="tw:text-bs-secondary-color! tw:block!">Grade</small>
                                        <span class="ui-badge tw:bg-bs-primary!">${gradeName}</span>
                                    </div>
                                </div>

                                <!-- Financial Status -->
                                <div class="tw:mb-6!">
                                    <small class="tw:text-bs-secondary-color! tw:block! tw:mb-2!">Financial Status</small>
                                    <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-2!">
                                        <span>Rs. ${totalPaid.toLocaleString()}</span>
                                        <span class="tw:text-bs-secondary-color!">/ Rs. ${fees.toLocaleString()}</span>
                                    </div>
                                    <div class="ui-progress" style="height: 6px;">
                                        <div class="ui-progress-bar tw:bg-bs-success!" style="width: ${paymentPercentage}%"></div>
                                    </div>
                                    <div class="tw:flex! tw:justify-between! tw:mt-1!">
                                        <small class="tw:text-bs-secondary-color!">${paymentPercentage}% Paid</small>
                                        <small class="tw:text-bs-secondary-color!">${paymentCount} payment${paymentCount !== 1 ? 's' : ''}</small>
                                    </div>
                                </div>

                                <!-- Attendance Status -->
                                <div class="tw:mb-6!">
                                    <small class="tw:text-bs-secondary-color! tw:block! tw:mb-2!">Attendance Status</small>
                                    <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-2!">
                                        <span>${presentCount} present</span>
                                        <span class="tw:text-bs-secondary-color!">/ ${totalSessions} session${totalSessions !== 1 ? 's' : ''}</span>
                                    </div>
                                    <div class="ui-progress" style="height: 6px;">
                                        <div class="ui-progress-bar tw:bg-bs-info!" style="width: ${attendancePercentage}%"></div>
                                    </div>
                                    <div class="tw:flex! tw:justify-between! tw:mt-1!">
                                        <small class="tw:text-bs-secondary-color!">${attendancePercentage}% Attendance</small>
                                        <small class="tw:text-bs-secondary-color!">${absentCount} absent</small>
                                    </div>
                                </div>

                                <!-- Exam Results Button -->
                                <div class="tw:grid!">
                                    <button class="ui-btn ui-btn-primary" onclick="goToExamResults(${classCategoryHasStudentClassId}, ${studentId})">
                                        <i class="fas fa-chart-bar tw:mr-2!"></i>View Exam Results
                                    </button>
                                </div>
                            </div>
                            <div class="ui-card-footer ${isSplitCategory ? 'tw:bg-[transparent]! tw:text-[rgba(255,255,255,0.5)]! tw:[border-top:0]!' : 'tw:bg-bs-white! tw:text-bs-secondary-color!'}">
                                <small><i class="fas fa-calendar tw:mr-1!"></i>Enrolled on: ${enrollmentDate}</small>
                            </div>
                        </div>
                    </div>
                `;
            });

            elements.enrollmentsList.innerHTML = enrollmentsHTML;

            // Apply current filters
            filterEnrollmentsBySearch(elements.classSearch.value);
        }

        // Navigate to Exam Results page
        function goToExamResults(classCategoryHasStudentClassId, studentId) {
            if (classCategoryHasStudentClassId && studentId) {
                window.location.href = `/students/${classCategoryHasStudentClassId}/${studentId}/exam-results`;
            } else {
                showAlert('Cannot navigate to exam results: Missing parameters', 'danger');
            }
        }

        // Toggle Split Categories
        function toggleSplitCategories() {
            showSplitCategories = !showSplitCategories;

            const button = elements.toggleSplitBtn;
            if (button) {
                button.innerHTML = showSplitCategories ?
                    '<i class="fas fa-eye-slash tw:mr-1!"></i>Hide Split' :
                    '<i class="fas fa-layer-group tw:mr-1!"></i>Show Split';
                button.classList.toggle('active', showSplitCategories);
            }

            displayStudentEnrollments(enrollmentsData);
        }

        // Filter Enrollments
        function filterEnrollments(filterType) {
            currentFilter = filterType;

            // Update button states
            const buttons = document.querySelectorAll('#enrollmentsSection .ui-btn-group .ui-btn');
            buttons.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Find which button was clicked and set it active
            const activeBtn = Array.from(buttons).find(btn => btn.textContent.toLowerCase().includes(filterType));
            if (activeBtn) {
                activeBtn.classList.add('active');
            }

            filterEnrollmentsBySearch(elements.classSearch.value);
        }

        // Filter by search term
        function filterEnrollmentsBySearch(searchTerm) {
            const enrollmentCards = document.querySelectorAll('#enrollmentsList .ui-col-xl-6');
            let visibleCount = 0;

            enrollmentCards.forEach(card => {
                const cardText = card.textContent.toLowerCase();
                const status = card.getAttribute('data-status');
                const type = card.getAttribute('data-type');

                const matchesSearch = searchTerm === '' || cardText.includes(searchTerm);
                const matchesStatus = currentFilter === 'all' ||
                    (currentFilter === 'active' && status === 'active') ||
                    (currentFilter === 'free' && type === 'free');

                if (matchesSearch && matchesStatus) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (visibleCount === 0 && elements.noEnrollments && elements.enrollmentsList) {
                elements.noEnrollments.classList.remove('tw:hidden!');
                elements.enrollmentsList.classList.add('tw:hidden!');
            } else if (elements.noEnrollments && elements.enrollmentsList) {
                elements.noEnrollments.classList.add('tw:hidden!');
                elements.enrollmentsList.classList.remove('tw:hidden!');
            }
        }

        // Show No Enrollments
        function showNoEnrollments() {
            if (elements.enrollmentsLoading) {
                elements.enrollmentsLoading.style.display = 'none';
            }
            if (elements.enrollmentsList) {
                elements.enrollmentsList.classList.add('tw:hidden!');
            }
            if (elements.noEnrollments) {
                elements.noEnrollments.classList.remove('tw:hidden!');
            }
        }

        // Refresh Data
        function refreshData() {
            loadStudentAnalytics();
        }

        // Export Report
        function exportReport(format) {
            showAlert(`Exporting report as ${format.toUpperCase()}...`, 'info');
            // Implement export functionality here
        }

        // View Student Profile
        function viewStudentProfile() {
            if (currentStudent && studentCode) {
                window.location.href = `/students/${studentCode}`;
            }
        }

        // Edit Student Details
        function editStudentDetails() {
            if (currentStudent && studentCode) {
                window.location.href = `/students/${studentCode}/edit`;
            }
        }

        // Format Date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (error) {
                return 'Invalid Date';
            }
        }

        // Show Alert (Production-ready)
        function showAlert(message, type = 'info') {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert-toast');
            existingAlerts.forEach(alert => alert.remove());

            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `ui-alert ui-alert-${type} alert-toast tw:fixed! tw:top-0! tw:right-0! tw:m-4! tw:shadow-bs!`;
            alertDiv.style.zIndex = '9999';
            alertDiv.style.minWidth = '300px';
            alertDiv.style.maxWidth = '400px';
            alertDiv.setAttribute('role', 'alert');

            const icon = type === 'success' ? 'check-circle' :
                type === 'danger' ? 'exclamation-circle' :
                    type === 'warning' ? 'exclamation-triangle' : 'info-circle';

            alertDiv.innerHTML = `
                <div class="tw:flex! tw:items-center!">
                    <i class="fas fa-${icon} tw:mr-2!"></i>
                    <span class="tw:grow!">${message}</span>
                    <button type="button" class="ui-btn-close tw:ml-2!" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;

            document.body.appendChild(alertDiv);

            // Auto-remove after timeout
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, config.alertTimeout);
        }

        // Error handling for unhandled promises
        window.addEventListener('unhandledrejection', function (event) {
            console.error('Unhandled promise rejection:', event.reason);
            showAlert('An unexpected error occurred. Please refresh the page.', 'danger');
        });
    </script>
@endpush