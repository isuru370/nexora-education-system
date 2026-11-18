@extends('layouts.app')

@section('title', 'Add Student to Class')
@section('page-title', 'Add Student to Class')

@section('breadcrumb')
    <li class="ui-breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="ui-breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
    <li class="ui-breadcrumb-item active">Add Student</li>
@endsection

@section('content')
    <div class="ui-container-fluid">
        <!-- Student Information Section -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! tw:shadow-bs-lg!">
                    <div class="ui-card-header tw:bg-bs-primary! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-bs-white! tw:py-4!">
                        <h5 class="ui-card-title tw:mb-0!">
                            <i class="fas fa-user tw:mr-2!"></i>Student Information
                        </h5>
                    </div>
                    <div class="ui-card-body">
                        <div id="studentInfo">
                            <div class="tw:text-center! tw:py-4!">
                                <div class="ui-spinner-border tw:text-bs-primary!" role="status">
                                    <span class="tw:sr-only">Loading...</span>
                                </div>
                                <p class="tw:mt-2! tw:mb-0!">Loading student information...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Search Section -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! tw:shadow-bs-lg!">
                    <div class="ui-card-header bs-bg-info tw:bg-bs-info! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-bs-white! tw:py-4!">
                        <h5 class="ui-card-title tw:mb-0!">
                            <i class="fas fa-search tw:mr-2!"></i>Search and Select Class
                        </h5>
                    </div>
                    <div class="ui-card-body">
                        <!-- Search Input -->
                        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-6!">
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[66.66666667%]">
                                <label class="ui-form-label tw:font-semibold!">Search Classes</label>
                                <div class="ui-input-group">
                                    <input type="text" id="classSearch" class="ui-form-control"
                                        placeholder="Search by class name, teacher name, teacher ID, or category...">
                                    <button class="ui-btn ui-btn-outline-secondary" type="button" id="clearSearch">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <small class="tw:text-bs-secondary-color!">Search by class name, teacher name, teacher custom ID, or category
                                    name</small>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                <label class="ui-form-label tw:font-semibold!">&nbsp;</label>
                                <div>
                                    <button class="ui-btn ui-btn-success tw:w-full!" onclick="searchClasses()">
                                        <i class="fas fa-search tw:mr-2!"></i>Search Classes
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Search Results -->
                        <div id="searchResults" class="tw:hidden!">
                            <h6 class="tw:mb-4! tw:font-semibold!">Search Results:</h6>
                            <div class="bs-row ui-row bs-flex-nowrap tw:flex-nowrap! tw:overflow-auto! tw:pb-4!" id="classesList" style="scrollbar-width: thin;">
                                <!-- Cards will appear here -->
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div id="searchLoading" class="tw:text-center! tw:py-6! tw:hidden!">
                            <div class="ui-spinner-border tw:text-bs-info!" role="status">
                                <span class="tw:sr-only">Loading...</span>
                            </div>
                            <p class="tw:mt-2! tw:text-bs-secondary-color!">Searching classes...</p>
                        </div>

                        <!-- Empty State -->
                        <div id="searchEmpty" class="tw:text-center! tw:py-12! tw:hidden!">
                            <div class="empty-state-icon tw:mb-4!">
                                <i class="fas fa-chalkboard fa-3x tw:text-bs-secondary-color!"></i>
                            </div>
                            <h4 class="tw:text-bs-secondary-color!">No Classes Found</h4>
                            <p class="tw:text-bs-secondary-color!">No classes match your search criteria.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Class Confirmation -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! tw:shadow-bs-lg!">
                    <div class="ui-card-header bs-bg-success tw:bg-bs-success! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-bs-white! tw:py-4!">
                        <h5 class="ui-card-title tw:mb-0!">
                            <i class="fas fa-check-circle tw:mr-2!"></i>Confirm Selection
                        </h5>
                    </div>
                    <div class="ui-card-body">
                        <div id="confirmationSection" class="tw:hidden!">
                            <div class="ui-alert ui-alert-warning">
                                <h6 class="ui-alert-heading">Please Confirm Student Enrollment</h6>
                                <p class="tw:mb-0!">Review the details below before adding the student to the class.</p>
                            </div>

                            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row">
                                <!-- Student Details -->
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                    <div class="ui-card tw:h-full! tw:[border:0]! bs-shadow-sm tw:shadow-bs-sm!">
                                        <div class="ui-card-header tw:bg-bs-light!">
                                            <h6 class="ui-card-title tw:mb-0! tw:font-semibold!">Student Details</h6>
                                        </div>
                                        <div class="ui-card-body">
                                            <div id="studentDetails">
                                                <!-- Student details will be populated here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Class Details -->
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                    <div class="ui-card tw:h-full! tw:[border:0]! bs-shadow-sm tw:shadow-bs-sm!">
                                        <div class="ui-card-header tw:bg-bs-light!">
                                            <h6 class="ui-card-title tw:mb-0! tw:font-semibold!">Class Details</h6>
                                        </div>
                                        <div class="ui-card-body">
                                            <div id="classDetails">
                                                <!-- Class details will be populated here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fee Options -->
                            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mt-6!">
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                                    <div class="ui-card tw:[border:0]! bs-shadow-sm tw:shadow-bs-sm!">
                                        <div class="ui-card-header tw:bg-bs-light!">
                                            <h6 class="ui-card-title tw:mb-0! tw:font-semibold!">Fee Options</h6>
                                        </div>
                                        <div class="ui-card-body">
                                            <!-- Free Card Option -->
                                            <div class="ui-form-check ui-form-switch tw:mb-4!">
                                                <input class="ui-form-check-input" type="checkbox" id="freeCardCheckbox"
                                                    style="transform: scale(1.2);" onchange="toggleFeeFields()">
                                                <label class="ui-form-check-label tw:font-semibold!" for="freeCardCheckbox">
                                                    <i class="fas fa-id-card tw:mr-2! tw:text-bs-warning!"></i>Mark as Free Card
                                                </label>
                                                <small class="ui-form-text tw:text-bs-secondary-color! tw:block! tw:mt-1!">
                                                    Enable this if the student should have free access to this class without
                                                    payment.
                                                </small>
                                            </div>

                                            <!-- Custom Fee and Discount Fields -->
                                            <div id="feeFields">
                                                <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row">
                                                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:mb-4!">
                                                        <label for="customFee" class="ui-form-label">Custom Fee</label>
                                                        <div class="ui-input-group">
                                                            <span class="ui-input-group-text">Rs.</span>
                                                            <input type="number" class="ui-form-control" id="customFee"
                                                                placeholder="Enter custom fee" step="0.01" min="0">
                                                        </div>
                                                        <small class="tw:text-bs-secondary-color!">Override the default class fee</small>
                                                    </div>
                                                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:mb-4!">
                                                        <label for="discountPercentage" class="ui-form-label">Discount
                                                            Percentage</label>
                                                        <div class="ui-input-group">
                                                            <input type="number" class="ui-form-control"
                                                                id="discountPercentage" placeholder="Enter discount %"
                                                                step="0.01" min="0" max="100">
                                                            <span class="ui-input-group-text">%</span>
                                                        </div>
                                                        <small class="tw:text-bs-secondary-color!">Apply percentage discount to the
                                                            fee</small>
                                                    </div>
                                                </div>

                                                <div class="tw:mb-4!">
                                                    <label for="discountType" class="ui-form-label">Discount Type
                                                        (Optional)</label>
                                                    <select class="ui-form-select" id="discountType">
                                                        <option value="">Select discount type</option>
                                                        <option value="half_card">Half Card</option>
                                                        <option value="early_bird">Early Bird</option>
                                                        <option value="scholarship">Scholarship</option>
                                                        <option value="referral">Referral</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                    <small class="tw:text-bs-secondary-color!">Type of discount being applied</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mt-6!">
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                                    <div class="tw:flex! tw:justify-between! tw:items-center!">
                                        <button class="ui-btn ui-btn-outline-secondary" onclick="clearSelection()">
                                            <i class="fas fa-times tw:mr-2!"></i>Cancel
                                        </button>
                                        <button class="ui-btn ui-btn-success" id="addToClassBtn" onclick="addStudentToClass()">
                                            <i class="fas fa-user-plus tw:mr-2!"></i>Add Student to Class
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Initial State -->
                        <div id="initialState" class="tw:text-center! tw:py-12!">
                            <div class="empty-state-icon tw:mb-4!">
                                <i class="fas fa-mouse-pointer fa-3x tw:text-bs-secondary-color!"></i>
                            </div>
                            <h4 class="tw:text-bs-secondary-color!">Select a Class</h4>
                            <p class="tw:text-bs-secondary-color!">Search and select a class to add the student.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Enrollments Section -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-6!" id="enrollmentsSection" style="display: none;">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card tw:[border:0]! tw:shadow-bs-lg!">
                    <div class="ui-card-header bs-bg-warning tw:bg-bs-warning! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-bs-dark! tw:py-4!">
                        <div class="tw:flex! tw:items-center!">
                            <i class="fas fa-list fa-lg tw:mr-4!"></i>
                            <h5 class="ui-card-title tw:mb-0! tw:font-bold!">Current Class Enrollments</h5>
                            <span class="ui-badge tw:bg-[rgba(33,37,41,0.25)]! tw:ml-2! tw:text-[length:1rem]!" id="enrollmentCount">0</span>
                        </div>
                    </div>
                    <div class="ui-card-body tw:p-6!">
                        <div class="bs-row ui-row bs-flex-nowrap tw:flex-nowrap! tw:overflow-auto! tw:pb-4! ui-g-3" id="enrollmentsList">
                            <!-- Enrollments will be loaded here -->
                        </div>
                        <div id="noEnrollments" class="tw:text-center! tw:py-12! tw:hidden!">
                            <div class="tw:mb-6!">
                                <i class="fas fa-chalkboard-teacher fa-4x tw:text-bs-secondary-color! tw:[opacity:0.5]!"></i>
                            </div>
                            <h5 class="tw:text-bs-secondary-color! tw:font-semibold! tw:mb-2!">No Current Enrollments</h5>
                            <p class="tw:text-bs-secondary-color! tw:mb-0!">This student is not enrolled in any classes yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .search-highlight {
            background-color: yellow;
            font-weight: bold;
            padding: 0 2px;
        }

        .class-card {
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 280px;
        }

        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .class-card.selected {
            border: 2px solid #0d6efd;
            background-color: #f0f8ff;
        }

        .class-name-highlight {
            font-size: 1.1rem;
        }

        .enrollment-card {
            transition: all 0.3s ease;
            min-width: 320px;
        }

        .enrollment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .bs-row.bs-flex-nowrap {
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }

        .bs-row.bs-flex-nowrap::-webkit-scrollbar {
            height: 8px;
        }

        .bs-row.bs-flex-nowrap::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .bs-row.bs-flex-nowrap::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .bs-row.bs-flex-nowrap::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .fs-7 {
            font-size: 0.875rem !important;
        }

        .action-buttons .ui-btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }

        .free-card-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 193, 7, 0.9);
            color: #212529;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 10;
        }

        .fee-highlight {
            background-color: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }

        /* Update Modal Styles */
        .update-modal .ui-modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .fee-option-card {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .fee-option-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .fee-option-card.selected {
            border-color: #0d6efd;
            background-color: #f0f8ff;
        }

        .info-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 12px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Helper functions
        const api = function (endpoint) {
            return `/api/${endpoint}`;
        };

        const getCsrfToken = function () {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        };

        // Get student ID from URL
        const pathArray = window.location.pathname.split('/');
        const studentId = pathArray[pathArray.length - 1];

        let selectedClass = null;
        let currentStudent = null;
        let currentEditingEnrollment = null;

        document.addEventListener('DOMContentLoaded', function () {
            loadStudentInfo();
            loadStudentEnrollments();

            // Event listeners
            document.getElementById('classSearch').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    searchClasses();
                }
            });

            document.getElementById('clearSearch').addEventListener('click', function () {
                document.getElementById('classSearch').value = '';
            });
        });

        // Toggle fee fields based on free card checkbox
        function toggleFeeFields() {
            const isFreeCard = document.getElementById('freeCardCheckbox').checked;
            const feeFields = document.getElementById('feeFields');

            if (isFreeCard) {
                feeFields.style.display = 'none';
                // Clear values when free card is checked
                document.getElementById('customFee').value = '';
                document.getElementById('discountPercentage').value = '';
                document.getElementById('discountType').value = '';
            } else {
                feeFields.style.display = 'block';
            }
        }

        // Load Student Information
        async function loadStudentInfo() {
            try {
                const studentResponse = await fetch(api(`students/${studentId}`));
                if (studentResponse.ok) {
                    const studentData = await studentResponse.json();
                    currentStudent = studentData.data || studentData;
                    displayStudentInfo(currentStudent);
                } else {
                    await loadStudentInfoFromEnrollment();
                }
            } catch (error) {
                console.error('Error loading student info:', error);
                await loadStudentInfoFromEnrollment();
            }
        }

        // Fallback function to get student info from enrollment API
        async function loadStudentInfoFromEnrollment() {
            try {
                const response = await fetch(api(`student-classes/student/${studentId}`));
                if (response.ok) {
                    const data = await response.json();
                    if (Array.isArray(data) && data.length > 0 && data[0].student) {
                        currentStudent = data[0].student;
                        displayStudentInfo(currentStudent);
                    } else if (data.student) {
                        currentStudent = data.student;
                        displayStudentInfo(currentStudent);
                    } else {
                        displayStudentInfo(null);
                    }
                } else {
                    displayStudentInfo(null);
                }
            } catch (error) {
                console.error('Error loading student info from enrollment:', error);
                displayStudentInfo(null);
            }
        }

        // Load Student Enrollments
        async function loadStudentEnrollments() {
            try {
                const response = await fetch(api(`student-classes/student/${studentId}`));

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('Enrollments API response:', data);

                let enrollments = [];

                // Handle different response structures
                if (Array.isArray(data)) {
                    enrollments = data;
                } else if (data.data && Array.isArray(data.data)) {
                    enrollments = data.data;
                } else if (data.data && !Array.isArray(data.data) && data.data.id) {
                    enrollments = [data.data];
                } else if (data.id) {
                    enrollments = [data];
                } else if (data.student) {
                    enrollments = [data];
                } else if (data.enrollments && Array.isArray(data.enrollments)) {
                    enrollments = data.enrollments;
                }

                console.log('Processed enrollments:', enrollments);
                displayStudentEnrollments(enrollments);

            } catch (error) {
                console.error('Error loading enrollments:', error);
                showNoEnrollments();
                showAlert('Failed to load current enrollments', 'warning');
            }
        }

        // Display Student Enrollments
        // Display Student Enrollments - UPDATED to match backend structure
        function displayStudentEnrollments(enrollments) {
            const enrollmentsSection = document.getElementById('enrollmentsSection');
            const enrollmentsList = document.getElementById('enrollmentsList');
            const noEnrollmentsDiv = document.getElementById('noEnrollments');
            const enrollmentCount = document.getElementById('enrollmentCount');

            enrollmentsList.innerHTML = '';
            enrollmentsSection.style.display = 'block';

            // Check if we have data array from the response
            let validEnrollments = [];

            if (Array.isArray(enrollments)) {
                validEnrollments = enrollments;
            } else if (enrollments.data && Array.isArray(enrollments.data)) {
                validEnrollments = enrollments.data;
            } else if (enrollments.data && !Array.isArray(enrollments.data)) {
                validEnrollments = [enrollments.data];
            }

            enrollmentCount.textContent = validEnrollments.length;

            if (validEnrollments.length === 0) {
                showNoEnrollments();
                return;
            }

            let enrollmentsHTML = '';

            validEnrollments.forEach(enrollment => {
                // Extract data using the exact structure from backend
                const studentClass = enrollment.student_class || {};
                const teacher = enrollment.teacher || {};
                const subject = enrollment.subject || {};
                const grade = enrollment.grade || {};
                const categoryData = enrollment.class_category_has_student_class || {};
                const classCategory = categoryData.class_category || {};

                // Get values with proper fallbacks
                const className = studentClass.class_name || 'Unnamed Class';
                const gradeName = grade.grade_name || 'N/A';
                const teacherName = teacher.teacher_name || (teacher.fname && teacher.lname ? `${teacher.fname} ${teacher.lname}` : 'N/A');
                const subjectName = subject.subject_name || 'N/A';
                const categoryName = classCategory.category_name || 'N/A';

                const isActive = enrollment.status === true || enrollment.status === 1 || enrollment.status === 'active';
                const statusBadge = isActive ?
                    '<span class="ui-badge bs-bg-success tw:bg-bs-success! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:rounded-bs-pill! tw:px-4! tw:py-2!">Active</span>' :
                    '<span class="ui-badge tw:bg-bs-secondary! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:rounded-bs-pill! tw:px-4! tw:py-2!">Inactive</span>';

                const freeCardStatus = enrollment.is_free_card === true || enrollment.is_free_card === 1;
                const freeCardBadge = freeCardStatus ?
                    '<span class="ui-badge bs-bg-warning tw:bg-bs-warning! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-bs-dark! tw:rounded-bs-pill! tw:px-2! tw:py-1!"><i class="fas fa-id-card tw:mr-1!"></i>Free Card</span>' :
                    '<span class="ui-badge tw:bg-bs-light! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-bs-dark! tw:bs-border! tw:rounded-bs-pill! tw:px-2! tw:py-1!"><i class="fas fa-money-bill tw:mr-1!"></i>Paid</span>';

                // Get enrollment date from created_at or enrollment_date
                const enrollmentDate = enrollment.created_at ? formatDate(enrollment.created_at) :
                    (enrollment.enrollment_date ? formatDate(enrollment.enrollment_date) : 'N/A');

                // Get fee information
                const finalFee = enrollment.final_fee || 'N/A';
                const defaultFee = enrollment.default_fee || 'N/A';
                const customFee = enrollment.custom_fee || null;
                const discountPercentage = enrollment.discount_percentage || null;
                const discountType = enrollment.discount_type || '';

                // Build fee info display
                let feeInfo = '';
                if (freeCardStatus) {
                    feeInfo = '<span class="ui-badge bs-bg-warning tw:bg-bs-warning! tw:text-bs-dark!"><i class="fas fa-gift tw:mr-1!"></i>Free Card</span>';
                } else if (customFee) {
                    feeInfo = `<span class="ui-badge bs-bg-info tw:bg-bs-info!"><i class="fas fa-tag tw:mr-1!"></i>Custom Fee: Rs. ${parseFloat(customFee).toFixed(2)}</span>`;
                } else if (discountPercentage) {
                    feeInfo = `<span class="ui-badge bs-bg-info tw:bg-bs-info!"><i class="fas fa-percent tw:mr-1!"></i>${discountPercentage}% Discount ${discountType ? '(' + discountType.replace('_', ' ') + ')' : ''}</span>`;
                }

                enrollmentsHTML += `
                <div class="ui-col-auto">
                    <div class="ui-card tw:[border:0]! bs-shadow-sm tw:shadow-bs-sm! tw:h-full! enrollment-card tw:relative!" style="width: 350px;">
                        ${freeCardStatus ? '<div class="free-card-indicator"><i class="fas fa-crown tw:mr-1!"></i>FREE</div>' : ''}
                        <div class="ui-card-header tw:bg-bs-primary! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-bs-white! tw:py-4!">
                            <div class="tw:flex! tw:justify-between! tw:items-start!">
                                <h6 class="ui-card-title tw:mb-0! tw:font-bold! tw:truncate" style="max-width: 200px;" title="${className}">${escapeHtml(className)}</h6>
                                <span class="ui-badge tw:bg-bs-light! tw:text-bs-dark! fs-7">${escapeHtml(gradeName)}</span>
                            </div>
                        </div>
                        <div class="ui-card-body tw:p-4!">
                            <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-4!">
                                <div>
                                    ${statusBadge}
                                    ${freeCardBadge}
                                </div>
                                <span class="ui-badge tw:bg-bs-primary! tw:[background-image:linear-gradient(180deg,_rgba(255,255,255,0.15),_rgba(255,255,255,0))]! tw:text-[length:1rem]!">Rs. ${finalFee}</span>
                            </div>

                            <div class="tw:mb-2!">
                                <div class="tw:flex! tw:items-center! tw:mb-2!">
                                    <i class="fas fa-chalkboard-teacher tw:text-bs-secondary-color! tw:mr-2! fs-7" style="width: 20px;"></i>
                                    <small class="tw:text-bs-secondary-color!"><strong>Teacher:</strong></small>
                                    <small class="tw:ml-2!">${escapeHtml(teacherName)}</small>
                                </div>
                                <div class="tw:flex! tw:items-center! tw:mb-2!">
                                    <i class="fas fa-book tw:text-bs-secondary-color! tw:mr-2! fs-7" style="width: 20px;"></i>
                                    <small class="tw:text-bs-secondary-color!"><strong>Subject:</strong></small>
                                    <small class="tw:ml-2!">${escapeHtml(subjectName)}</small>
                                </div>
                                <div class="tw:flex! tw:items-center! tw:mb-2!">
                                    <i class="fas fa-tag tw:text-bs-secondary-color! tw:mr-2! fs-7" style="width: 20px;"></i>
                                    <small class="tw:text-bs-secondary-color!"><strong>Category:</strong></small>
                                    <small class="tw:ml-2!">${escapeHtml(categoryName)}</small>
                                </div>
                            </div>

                            <div class="tw:bs-border-t! tw:pt-2! tw:mt-2!">
                                <div class="tw:flex tw:flex-wrap tw:-mt-1 tw:mx-[-0.125rem] bs-row tw:mb-4!">
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-[0.125rem] tw:mt-1 tw:flex-[0_0_auto] tw:w-[50%]">
                                        <small class="tw:text-bs-secondary-color! tw:block!">
                                            <i class="fas fa-calendar tw:mr-1!"></i>
                                            <strong>Enrolled:</strong>
                                        </small>
                                        <small class="tw:text-bs-dark! tw:font-semibold!">${enrollmentDate}</small>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-[0.125rem] tw:mt-1 tw:flex-[0_0_auto] tw:w-[50%] tw:text-right!">
                                        <small class="tw:text-bs-secondary-color! tw:block!">
                                            <strong>Enrollment ID:</strong>
                                        </small>
                                        <small class="tw:text-bs-dark! tw:font-semibold!">#${enrollment.id || 'N/A'}</small>
                                    </div>
                                </div>

                                ${defaultFee !== 'N/A' && defaultFee !== finalFee && !freeCardStatus && !customFee ?
                        `<div class="tw:text-center! tw:mb-2!">
                                        <small class="tw:text-bs-secondary-color!">Default Fee: Rs. ${defaultFee}</small>
                                    </div>` : ''}

                                ${feeInfo ? `<div class="tw:text-center! tw:mb-2!">${feeInfo}</div>` : ''}

                                <div class="action-buttons tw:flex! tw:gap-2!">
                                    <button class="ui-btn ui-btn-outline-primary ui-btn-sm tw:grow!" onclick="openUpdateModal(${enrollment.id})">
                                        <i class="fas fa-edit tw:mr-1!"></i>Update
                                    </button>
                                    ${isActive ?
                        `<button class="ui-btn ui-btn-outline-danger ui-btn-sm tw:grow!" onclick="deactivateEnrollment(${enrollment.id})">
                                                <i class="fas fa-pause tw:mr-1!"></i>Deactivate
                                            </button>` :
                        `<button class="ui-btn ui-btn-outline-success ui-btn-sm tw:grow!" onclick="activateEnrollment(${enrollment.id})">
                                                <i class="fas fa-play tw:mr-1!"></i>Activate
                                            </button>`
                    }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            });

            enrollmentsList.innerHTML = enrollmentsHTML;
            noEnrollmentsDiv.classList.add('tw:hidden!');
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return 'N/A';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Load Student Enrollments - UPDATED
        async function loadStudentEnrollments() {
            try {
                const response = await fetch(api(`student-classes/student/${studentId}`));

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('Enrollments API response:', data);

                let enrollments = [];

                // Handle different response structures from your backend
                if (data.status === 'success' && Array.isArray(data.data)) {
                    enrollments = data.data;
                } else if (data.status === 'success' && data.data && !Array.isArray(data.data)) {
                    enrollments = [data.data];
                } else if (Array.isArray(data)) {
                    enrollments = data;
                } else if (data.data && Array.isArray(data.data)) {
                    enrollments = data.data;
                } else if (data.data && !Array.isArray(data.data) && data.data.id) {
                    enrollments = [data.data];
                } else if (data.id) {
                    enrollments = [data];
                }

                console.log('Processed enrollments:', enrollments);
                displayStudentEnrollments(enrollments);

            } catch (error) {
                console.error('Error loading enrollments:', error);
                showNoEnrollments();
                showAlert('Failed to load current enrollments', 'warning');
            }
        }

        // Open Update Modal - UPDATED to match backend structure
        function openUpdateModal(enrollmentId) {
            // Find the enrollment data from the current list or fetch fresh
            fetch(api(`student-classes/student/${studentId}`))
                .then(response => response.json())
                .then(data => {
                    let enrollments = [];
                    if (data.status === 'success' && Array.isArray(data.data)) {
                        enrollments = data.data;
                    } else if (Array.isArray(data)) {
                        enrollments = data;
                    } else if (data.data && Array.isArray(data.data)) {
                        enrollments = data.data;
                    } else if (data.data) {
                        enrollments = [data.data];
                    }

                    const enrollment = enrollments.find(e => e.id === enrollmentId);

                    if (!enrollment) {
                        showAlert('Enrollment not found', 'danger');
                        return;
                    }

                    currentEditingEnrollment = enrollment;
                    showUpdateModal(enrollment);
                })
                .catch(error => {
                    console.error('Error fetching enrollment:', error);
                    showAlert('Failed to load enrollment details', 'danger');
                });
        }

        // Show Update Modal UI - UPDATED to match backend structure
        function showUpdateModal(enrollment) {
            const studentClass = enrollment.student_class || {};
            const teacher = enrollment.teacher || {};
            const subject = enrollment.subject || {};
            const grade = enrollment.grade || {};
            const categoryData = enrollment.class_category_has_student_class || {};
            const classCategory = categoryData.class_category || {};

            const className = studentClass.class_name || 'Unnamed Class';
            const gradeName = grade.grade_name || 'N/A';
            const teacherName = teacher.teacher_name || (teacher.fname && teacher.lname ? `${teacher.fname} ${teacher.lname}` : 'N/A');
            const subjectName = subject.subject_name || 'N/A';
            const categoryName = classCategory.category_name || 'N/A';
            const defaultFee = enrollment.default_fee || categoryData.fees || 0;

            const isFreeCardCurrent = enrollment.is_free_card === true || enrollment.is_free_card === 1;
            const customFeeCurrent = enrollment.custom_fee || '';
            const discountPercentageCurrent = enrollment.discount_percentage || '';
            const discountTypeCurrent = enrollment.discount_type || '';
            const currentStatus = (enrollment.status === true || enrollment.status === 1) ? 1 : 0;

            // Get the class_category_has_student_class_id
            const categoryId = enrollment.class_category_has_student_class_id ||
                (categoryData ? categoryData.id : null);

            // Create modal HTML
            const modalHtml = `
            <div class="ui-modal ui-fade update-modal" id="updateEnrollmentModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
                <div class="ui-modal-dialog ui-modal-lg">
                    <div class="ui-modal-content">
                        <div class="ui-modal-header tw:text-bs-white!" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h5 class="ui-modal-title" id="updateModalLabel">
                                <i class="fas fa-edit tw:mr-2!"></i>Update Enrollment
                            </h5>
                            <button type="button" class="ui-btn-close ui-btn-close-white" data-nx-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="ui-modal-body">
                            <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-6!">
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                    <div class="ui-card tw:bg-bs-light!">
                                        <div class="ui-card-body">
                                            <h6 class="tw:font-bold! tw:mb-2!"><i class="fas fa-user-graduate tw:mr-2!"></i>Student</h6>
                                            <p class="tw:mb-1!"><strong>${currentStudent ? escapeHtml(currentStudent.fname + ' ' + currentStudent.lname) : 'N/A'}</strong></p>
                                            <small class="tw:text-bs-secondary-color!">ID: ${currentStudent ? currentStudent.custom_id : studentId}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                    <div class="ui-card tw:bg-bs-light!">
                                        <div class="ui-card-body">
                                            <h6 class="tw:font-bold! tw:mb-2!"><i class="fas fa-chalkboard tw:mr-2!"></i>Class</h6>
                                            <p class="tw:mb-1!"><strong>${escapeHtml(className)} ${escapeHtml(gradeName)}</strong></p>
                                            <small class="tw:text-bs-secondary-color!">Teacher: ${escapeHtml(teacherName)} | Subject: ${escapeHtml(subjectName)}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="ui-alert ui-alert-info tw:mb-4!">
                                <i class="fas fa-info-circle tw:mr-2!"></i>
                                <strong>Default Fee:</strong> Rs. ${defaultFee}
                            </div>

                            <form id="updateEnrollmentForm">
                                <input type="hidden" id="update_enrollment_id" value="${enrollment.id}">
                                <input type="hidden" id="update_category_id" value="${categoryId}">

                                <div class="tw:mb-4!">
                                    <label class="ui-form-label tw:font-semibold!">Status</label>
                                    <select class="ui-form-select" id="update_status">
                                        <option value="1" ${currentStatus === 1 ? 'selected' : ''}>Active</option>
                                        <option value="0" ${currentStatus === 0 ? 'selected' : ''}>Inactive</option>
                                    </select>
                                </div>

                                <div class="tw:mb-4!">
                                    <div class="ui-form-check ui-form-switch">
                                        <input class="ui-form-check-input" type="checkbox" id="update_freeCardCheckbox" 
                                            ${isFreeCardCurrent ? 'checked' : ''} onchange="toggleUpdateFeeFields()">
                                        <label class="ui-form-check-label tw:font-semibold!" for="update_freeCardCheckbox">
                                            <i class="fas fa-id-card tw:mr-2! tw:text-bs-warning!"></i>Free Card
                                        </label>
                                        <small class="ui-form-text tw:text-bs-secondary-color! tw:block! tw:mt-1!">
                                            Enable if this enrollment should be free
                                        </small>
                                    </div>
                                </div>

                                <div id="update_feeFields" style="${isFreeCardCurrent ? 'display: none;' : 'display: block;'}">
                                    <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row">
                                        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:mb-4!">
                                            <label for="update_customFee" class="ui-form-label">Custom Fee</label>
                                            <div class="ui-input-group">
                                                <span class="ui-input-group-text">Rs.</span>
                                                <input type="number" class="ui-form-control" id="update_customFee" 
                                                    placeholder="Enter custom fee" step="0.01" min="0" value="${customFeeCurrent}">
                                            </div>
                                            <small class="tw:text-bs-secondary-color!">Override the default class fee (Rs. ${defaultFee})</small>
                                        </div>
                                        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:mb-4!">
                                            <label for="update_discountPercentage" class="ui-form-label">Discount Percentage</label>
                                            <div class="ui-input-group">
                                                <input type="number" class="ui-form-control" id="update_discountPercentage" 
                                                    placeholder="Enter discount %" step="0.01" min="0" max="100" value="${discountPercentageCurrent}">
                                                <span class="ui-input-group-text">%</span>
                                            </div>
                                            <small class="tw:text-bs-secondary-color!">Apply percentage discount to the fee</small>
                                        </div>
                                    </div>

                                    <div class="tw:mb-4!">
                                        <label for="update_discountType" class="ui-form-label">Discount Type</label>
                                        <select class="ui-form-select" id="update_discountType">
                                            <option value="">Select discount type</option>
                                            <option value="half_card" ${discountTypeCurrent === 'half_card' ? 'selected' : ''}>Half Card</option>
                                            <option value="early_bird" ${discountTypeCurrent === 'early_bird' ? 'selected' : ''}>Early Bird</option>
                                            <option value="scholarship" ${discountTypeCurrent === 'scholarship' ? 'selected' : ''}>Scholarship</option>
                                            <option value="referral" ${discountTypeCurrent === 'referral' ? 'selected' : ''}>Referral</option>
                                            <option value="other" ${discountTypeCurrent === 'other' ? 'selected' : ''}>Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="ui-alert ui-alert-secondary tw:mt-4!" id="feeCalculationInfo">
                                    <i class="fas fa-calculator tw:mr-2!"></i>
                                    <span id="feeCalculationText">Fee calculation will be shown here</span>
                                </div>
                            </form>
                        </div>
                        <div class="ui-modal-footer">
                            <button type="button" class="ui-btn ui-btn-secondary" data-nx-dismiss="modal">Cancel</button>
                            <button type="button" class="ui-btn ui-btn-primary" onclick="submitUpdateEnrollment()">
                                <i class="fas fa-save tw:mr-2!"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

            // Remove existing modal if any
            const existingModal = document.getElementById('updateEnrollmentModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Initialize modal
            const modalElement = document.getElementById('updateEnrollmentModal');
            const modal = new NxUi.Modal(modalElement);

            // Add event listeners for fee calculation
            const customFeeInput = document.getElementById('update_customFee');
            const discountInput = document.getElementById('update_discountPercentage');
            if (customFeeInput) customFeeInput.addEventListener('input', updateFeeCalculation);
            if (discountInput) discountInput.addEventListener('input', updateFeeCalculation);

            const freeCardCheckbox = document.getElementById('update_freeCardCheckbox');
            if (freeCardCheckbox) freeCardCheckbox.addEventListener('change', updateFeeCalculation);

            // Initial fee calculation with default fee
            window.updateDefaultFee = defaultFee;
            updateFeeCalculation();

            modal.show();

            // Clean up modal when hidden
            modalElement.addEventListener('hidden.nx.modal', function () {
                modalElement.remove();
                currentEditingEnrollment = null;
            });
        }

        // Update fee calculation display
        function updateFeeCalculation() {
            const isFreeCard = document.getElementById('update_freeCardCheckbox')?.checked || false;
            const customFee = parseFloat(document.getElementById('update_customFee')?.value) || 0;
            const discountPercentage = parseFloat(document.getElementById('update_discountPercentage')?.value) || 0;
            const defaultFee = window.updateDefaultFee || 0;

            const calculationText = document.getElementById('feeCalculationText');

            if (isFreeCard) {
                calculationText.innerHTML = '<strong>Free Card:</strong> No fee will be charged for this enrollment.';
                return;
            }

            if (customFee > 0 && discountPercentage > 0) {
                calculationText.innerHTML = '<span class="tw:text-bs-danger!"><strong>Note:</strong> Custom fee and discount percentage cannot both be set. Please choose only one.</span>';
                return;
            }

            if (customFee > 0) {
                const savings = defaultFee - customFee;
                calculationText.innerHTML = `<strong>Custom Fee: Rs. ${customFee.toFixed(2)}</strong><br>
                <small>Default fee is Rs. ${defaultFee.toFixed(2)}. Student saves Rs. ${savings > 0 ? savings.toFixed(2) : '0'}</small>`;
            } else if (discountPercentage > 0) {
                const discountedAmount = defaultFee * (discountPercentage / 100);
                const finalAmount = defaultFee - discountedAmount;
                calculationText.innerHTML = `<strong>${discountPercentage}% Discount</strong><br>
                <small>Discount: Rs. ${discountedAmount.toFixed(2)} | Final Fee: Rs. ${finalAmount.toFixed(2)}</small>`;
            } else {
                calculationText.innerHTML = `<strong>Default Fee: Rs. ${defaultFee.toFixed(2)}</strong><br>
                <small>Student will be charged the standard class fee.</small>`;
            }
        }

        // Submit update enrollment
        async function submitUpdateEnrollment() {
            const enrollmentId = document.getElementById('update_enrollment_id').value;
            const categoryId = document.getElementById('update_category_id').value;
            const status = document.getElementById('update_status').value === '1' ? 1 : 0;
            const isFreeCard = document.getElementById('update_freeCardCheckbox').checked;
            const customFee = document.getElementById('update_customFee').value;
            const discountPercentage = document.getElementById('update_discountPercentage').value;
            const discountType = document.getElementById('update_discountType').value;

            const requestData = {
                class_category_has_student_class_id: parseInt(categoryId),
                status: status,
                is_free_card: isFreeCard ? 1 : 0,
                custom_fee: customFee ? parseFloat(customFee) : null,
                discount_percentage: discountPercentage ? parseFloat(discountPercentage) : null,
                discount_type: discountType ? discountType : null
            };

            // Validate: custom_fee and discount_percentage cannot both be set
            if (requestData.custom_fee && requestData.discount_percentage) {
                showAlert('Custom fee and discount percentage cannot both be set', 'danger');
                return;
            }

            const updateBtn = document.querySelector('#updateEnrollmentModal .ui-btn-primary');
            const originalText = updateBtn.innerHTML;

            try {
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin tw:mr-2!"></i>Saving...';

                const response = await fetch(api(`student-classes/${enrollmentId}`), {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert('Enrollment updated successfully!', 'success');

                    // Close modal
                    const modal = NxUi.Modal.getInstance(document.getElementById('updateEnrollmentModal'));
                    if (modal) modal.hide();

                    // Reload enrollments
                    await loadStudentEnrollments();

                } else {
                    throw new Error(result.message || 'Failed to update enrollment');
                }

            } catch (error) {
                console.error('Error updating enrollment:', error);
                showAlert('Failed to update enrollment: ' + error.message, 'danger');
            } finally {
                updateBtn.disabled = false;
                updateBtn.innerHTML = originalText;
            }
        }

        // Toggle fee fields in update modal
        function toggleUpdateFeeFields() {
            const isFreeCard = document.getElementById('update_freeCardCheckbox')?.checked || false;
            const feeFields = document.getElementById('update_feeFields');

            if (feeFields) {
                if (isFreeCard) {
                    feeFields.style.display = 'none';
                    const customFeeInput = document.getElementById('update_customFee');
                    const discountInput = document.getElementById('update_discountPercentage');
                    const discountTypeSelect = document.getElementById('update_discountType');
                    if (customFeeInput) customFeeInput.value = '';
                    if (discountInput) discountInput.value = '';
                    if (discountTypeSelect) discountTypeSelect.value = '';
                } else {
                    feeFields.style.display = 'block';
                }
            }
            updateFeeCalculation();
        }

        // Update fee calculation display
        function updateFeeCalculation() {
            const isFreeCard = document.getElementById('update_freeCardCheckbox').checked;
            const customFee = parseFloat(document.getElementById('update_customFee').value) || 0;
            const discountPercentage = parseFloat(document.getElementById('update_discountPercentage').value) || 0;

            const calculationText = document.getElementById('feeCalculationText');

            if (isFreeCard) {
                calculationText.innerHTML = '<strong>Free Card:</strong> No fee will be charged for this enrollment.';
                return;
            }

            if (customFee > 0 && discountPercentage > 0) {
                calculationText.innerHTML = '<span class="tw:text-bs-danger!"><strong>Note:</strong> Custom fee and discount percentage cannot both be set. Please choose only one.</span>';
                return;
            }

            if (customFee > 0) {
                calculationText.innerHTML = `<strong>Custom Fee:</strong> Student will be charged Rs. ${customFee.toFixed(2)} for this class.`;
            } else if (discountPercentage > 0) {
                calculationText.innerHTML = `<strong>${discountPercentage}% Discount:</strong> Discount will be applied to the default class fee.`;
            } else {
                calculationText.innerHTML = '<strong>Default Fee:</strong> Student will be charged the standard class fee.';
            }
        }

        // Submit update enrollment
        async function submitUpdateEnrollment() {
            const enrollmentId = document.getElementById('update_enrollment_id').value;
            const status = document.getElementById('update_status').value === '1' ? 1 : 0;
            const isFreeCard = document.getElementById('update_freeCardCheckbox').checked;
            const customFee = document.getElementById('update_customFee').value;
            const discountPercentage = document.getElementById('update_discountPercentage').value;
            const discountType = document.getElementById('update_discountType').value;

            // Get the class_category_has_student_class_id from the original enrollment
            const categoryId = currentEditingEnrollment.class_category_has_student_class_id;

            const requestData = {
                class_category_has_student_class_id: categoryId,
                status: status,
                is_free_card: isFreeCard ? 1 : 0,
                custom_fee: customFee ? parseFloat(customFee) : null,
                discount_percentage: discountPercentage ? parseFloat(discountPercentage) : null,
                discount_type: discountType ? discountType : null
            };

            // Validate: custom_fee and discount_percentage cannot both be set
            if (requestData.custom_fee && requestData.discount_percentage) {
                showAlert('Custom fee and discount percentage cannot both be set', 'danger');
                return;
            }

            const updateBtn = document.querySelector('#updateEnrollmentModal .ui-btn-primary');
            const originalText = updateBtn.innerHTML;

            try {
                updateBtn.disabled = true;
                updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin tw:mr-2!"></i>Saving...';

                const response = await fetch(api(`student-classes/${enrollmentId}`), {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert('Enrollment updated successfully!', 'success');

                    // Close modal
                    const modal = NxUi.Modal.getInstance(document.getElementById('updateEnrollmentModal'));
                    modal.hide();

                    // Reload enrollments
                    await loadStudentEnrollments();

                } else {
                    throw new Error(result.message || 'Failed to update enrollment');
                }

            } catch (error) {
                console.error('Error updating enrollment:', error);
                showAlert('Failed to update enrollment: ' + error.message, 'danger');
            } finally {
                updateBtn.disabled = false;
                updateBtn.innerHTML = originalText;
            }
        }

        // Deactivate Enrollment
        async function deactivateEnrollment(enrollmentId) {
            if (!confirm('Are you sure you want to deactivate this enrollment?')) {
                return;
            }

            try {
                const response = await fetch(api(`student-classes/${enrollmentId}/deactivate`), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    showAlert('Enrollment deactivated successfully!', 'success');
                    loadStudentEnrollments();
                } else {
                    throw new Error(result.message || 'Failed to deactivate enrollment');
                }
            } catch (error) {
                console.error('Error deactivating enrollment:', error);
                showAlert('Failed to deactivate enrollment: ' + error.message, 'danger');
            }
        }

        // Activate Enrollment
        async function activateEnrollment(enrollmentId) {
            if (!confirm('Are you sure you want to activate this enrollment?')) {
                return;
            }

            try {
                const response = await fetch(api(`student-classes/${enrollmentId}/activate`), {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    showAlert('Enrollment activated successfully!', 'success');
                    loadStudentEnrollments();
                } else {
                    throw new Error(result.message || 'Failed to activate enrollment');
                }
            } catch (error) {
                console.error('Error activating enrollment:', error);
                showAlert('Failed to activate enrollment: ' + error.message, 'danger');
            }
        }

        // Show no enrollments state
        function showNoEnrollments() {
            const enrollmentsSection = document.getElementById('enrollmentsSection');
            const enrollmentsList = document.getElementById('enrollmentsList');
            const noEnrollmentsDiv = document.getElementById('noEnrollments');

            enrollmentsList.innerHTML = '';
            enrollmentsSection.style.display = 'block';
            noEnrollmentsDiv.classList.remove('tw:hidden!');
        }

        // Format date for display
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Display Student Information
        function displayStudentInfo(student) {
            const studentInfoDiv = document.getElementById('studentInfo');

            if (student) {
                studentInfoDiv.innerHTML = `
                        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row">
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                                <strong>Student ID:</strong><br>
                                <span class="ui-badge tw:bg-bs-secondary! tw:text-[length:1rem]!">${student.custom_id || 'N/A'} / ${student.temporary_qr_code || ''}</span>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                                <strong>Name:</strong><br>
                                <span class="tw:text-[length:1rem]! tw:font-bold!">${student.initial_name || ''}</span>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                                <strong>Grade:</strong><br>
                                <span class="ui-badge bs-bg-info tw:bg-bs-info!">${student.grade ? 'Grade ' + student.grade.grade_name : 'N/A'}</span>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                                <strong>Mobile:</strong><br>
                                <span>${student.mobile || 'N/A'}</span>
                            </div>
                        </div>
                    `;
            } else {
                studentInfoDiv.innerHTML = `
                        <div class="ui-alert ui-alert-warning">
                            <i class="fas fa-exclamation-triangle tw:mr-2!"></i>
                            Student information not available. Student ID: ${studentId}
                        </div>
                    `;
            }
        }

        // Search Classes
        async function searchClasses() {
            const searchTerm = document.getElementById('classSearch').value.trim();

            if (!searchTerm) {
                showAlert('Please enter a search term', 'warning');
                return;
            }

            showSearchLoading();

            try {
                const response = await fetch(api(`class-has-category-classes/classes/search?q=${encodeURIComponent(searchTerm)}`));

                if (!response.ok) {
                    throw new Error('Failed to search classes');
                }

                const data = await response.json();
                displaySearchResults(data.data || [], searchTerm);
                hideSearchLoading();

            } catch (error) {
                console.error('Error searching classes:', error);
                hideSearchLoading();
                showSearchEmptyState();
                showAlert('Failed to search classes: ' + error.message, 'danger');
            }
        }

        // Display Search Results
        function displaySearchResults(classes, searchTerm) {
            const resultsDiv = document.getElementById('searchResults');
            const classesListDiv = document.getElementById('classesList');
            const emptyState = document.getElementById('searchEmpty');

            classesListDiv.innerHTML = '';

            if (classes.length === 0) {
                resultsDiv.classList.add('tw:hidden!');
                emptyState.classList.remove('tw:hidden!');
                return;
            }

            emptyState.classList.add('tw:hidden!');
            resultsDiv.classList.remove('tw:hidden!');

            classes.forEach(classItem => {
                const studentClass = classItem.student_class;
                const classCategory = classItem.class_category;

                if (!studentClass) return;

                const className = studentClass.class_name;
                const gradeName = studentClass.grade ? `G${studentClass.grade.grade_name}` : 'N/A';
                const fullClassName = `${className} ${gradeName}`;

                const classCard = `
                        <div class="ui-col-auto">
                            <div class="ui-card class-card tw:h-full!" onclick='selectClass(${JSON.stringify(classItem).replace(/'/g, "&#39;")})'>
                                <div class="ui-card-body">
                                    <h6 class="ui-card-title">${highlightSearchTerm(fullClassName, searchTerm)}</h6>
                                    <div class="tw:mb-2!">
                                        <small class="tw:text-bs-secondary-color!">Teacher:</small><br>
                                        <strong>${studentClass.teacher ? highlightSearchTerm(studentClass.teacher.fname + ' ' + studentClass.teacher.lname, searchTerm) : 'N/A'}</strong>
                                        <br>
                                        <small class="tw:text-bs-secondary-color!">${studentClass.teacher ? studentClass.teacher.custom_id : ''}</small>
                                    </div>
                                    <div class="tw:mb-2!">
                                        <small class="tw:text-bs-secondary-color!">Subject:</small><br>
                                        <span class="ui-badge tw:bg-bs-light! tw:text-bs-dark!">${studentClass.subject ? studentClass.subject.subject_name : 'N/A'}</span>
                                        <span class="ui-badge bs-bg-info tw:bg-bs-info!">Grade ${studentClass.grade ? studentClass.grade.grade_name : 'N/A'}</span>
                                    </div>
                                    <div class="tw:mt-2!">
                                        <small class="tw:text-bs-secondary-color!">Category & Fee:</small><br>
                                        <span class="ui-badge bs-bg-success tw:bg-bs-success!">${classCategory ? highlightSearchTerm(classCategory.category_name, searchTerm) : 'N/A'}</span>
                                        <span class="ui-badge tw:bg-bs-primary! tw:ml-1!">Rs. ${classItem.fees || 0}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                classesListDiv.innerHTML += classCard;
            });
        }

        // Highlight search term in text
        function highlightSearchTerm(text, searchTerm) {
            if (!text || !searchTerm) return text;
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            return text.replace(regex, '<span class="search-highlight">$1</span>');
        }

        // Select Class
        function selectClass(classData) {
            selectedClass = classData;

            // Update UI
            document.querySelectorAll('.class-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');

            // Reset free card checkbox and fee fields
            document.getElementById('freeCardCheckbox').checked = false;
            document.getElementById('customFee').value = '';
            document.getElementById('discountPercentage').value = '';
            document.getElementById('discountType').value = '';
            toggleFeeFields();

            // Show confirmation section
            showConfirmationSection(classData);
        }

        // Show Confirmation Section
        function showConfirmationSection(classData) {
            const studentClass = classData.student_class;
            const classCategory = classData.class_category;

            const className = studentClass.class_name;
            const gradeName = studentClass.grade ? `G${studentClass.grade.grade_name}` : 'N/A';
            const fullClassName = `${className} ${gradeName}`;

            // Populate student details
            document.getElementById('studentDetails').innerHTML = `
                    <div class="tw:mb-2!">
                        <strong>Student ID:</strong> ${currentStudent ? currentStudent.custom_id : studentId}
                    </div>
                    <div class="tw:mb-2!">
                        <strong>Name:</strong> ${currentStudent ? currentStudent.fname + ' ' + currentStudent.lname : 'N/A'}
                    </div>
                    <div class="tw:mb-2!">
                        <strong>Grade:</strong> <span class="ui-badge bs-bg-info tw:bg-bs-info!">${currentStudent && currentStudent.grade ? 'Grade ' + currentStudent.grade.grade_name : 'N/A'}</span>
                    </div>
                    <div class="tw:mb-2!">
                        <strong>Mobile:</strong> ${currentStudent ? currentStudent.mobile : 'N/A'}
                    </div>
                `;

            // Populate class details
            document.getElementById('classDetails').innerHTML = `
                    <div class="tw:mb-4!">
                        <strong>Class Name:</strong> 
                        <div class="class-name-highlight tw:mt-1! tw:p-2! tw:bg-bs-primary! tw:text-bs-white! tw:rounded-bs! tw:text-center! tw:font-bold!">
                            ${fullClassName}
                        </div>
                    </div>
                    <div class="tw:mb-2!">
                        <strong>Teacher:</strong> ${studentClass.teacher ? studentClass.teacher.fname + ' ' + studentClass.teacher.lname : 'N/A'}
                        <br><small class="tw:text-bs-secondary-color!">${studentClass.teacher ? studentClass.teacher.custom_id : ''}</small>
                    </div>
                    <div class="tw:mb-2!">
                        <strong>Subject:</strong> <span class="ui-badge tw:bg-bs-light! tw:text-bs-dark!">${studentClass.subject ? studentClass.subject.subject_name : 'N/A'}</span>
                    </div>
                    <div class="tw:mb-2!">
                        <strong>Grade:</strong> <span class="ui-badge bs-bg-info tw:bg-bs-info!">${studentClass.grade ? 'Grade ' + studentClass.grade.grade_name : 'N/A'}</span>
                    </div>
                    <div class="tw:mb-2!">
                        <strong>Category:</strong> <span class="ui-badge bs-bg-success tw:bg-bs-success!">${classCategory ? classCategory.category_name : 'N/A'}</span>
                    </div>
                    <div class="tw:mb-2!">
                        <strong>Default Fee:</strong> <span class="ui-badge tw:bg-bs-primary!">Rs. ${classData.fees || 0}</span>
                    </div>
                `;

            // Show confirmation section
            document.getElementById('confirmationSection').classList.remove('tw:hidden!');
            document.getElementById('initialState').classList.add('tw:hidden!');
        }

        // Add Student to Class - UPDATED to match controller
        async function addStudentToClass() {
            if (!selectedClass) {
                showAlert('Please select a class first', 'warning');
                return;
            }

            const addBtn = document.getElementById('addToClassBtn');
            const originalText = addBtn.innerHTML;

            try {
                addBtn.disabled = true;
                addBtn.innerHTML = '<i class="fas fa-spinner fa-spin tw:mr-2!"></i>Adding Student...';

                const isFreeCard = document.getElementById('freeCardCheckbox').checked;
                const customFee = document.getElementById('customFee').value;
                const discountPercentage = document.getElementById('discountPercentage').value;
                const discountType = document.getElementById('discountType').value;

                const requestData = {
                    student_id: parseInt(studentId),
                    student_classes_id: selectedClass.student_classes_id,
                    class_category_has_student_class_id: selectedClass.id,
                    status: 1,
                    is_free_card: isFreeCard ? 1 : 0,
                    custom_fee: customFee ? parseFloat(customFee) : null,
                    discount_percentage: discountPercentage ? parseFloat(discountPercentage) : null,
                    discount_type: discountType ? discountType : null
                };

                console.log('Adding student to class:', requestData);

                const response = await fetch(api('student-classes/single'), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showAlert('Student added to class successfully!', 'success');

                    // Reload enrollments to show the new one
                    await loadStudentEnrollments();

                    // Clear selection
                    clearSelection();

                } else {
                    // Handle duplicate entry or other errors
                    if (result.message && result.message.includes('duplicate')) {
                        showAlert('This student is already enrolled in this class!', 'warning');
                    } else {
                        throw new Error(result.message || 'Failed to add student to class');
                    }
                }

            } catch (error) {
                console.error('Error adding student to class:', error);
                showAlert('Failed to add student: ' + error.message, 'danger');
            } finally {
                addBtn.disabled = false;
                addBtn.innerHTML = originalText;
            }
        }

        // Utility Functions
        function clearSelection() {
            selectedClass = null;
            document.querySelectorAll('.class-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.getElementById('confirmationSection').classList.add('tw:hidden!');
            document.getElementById('initialState').classList.remove('tw:hidden!');
            document.getElementById('freeCardCheckbox').checked = false;
            document.getElementById('customFee').value = '';
            document.getElementById('discountPercentage').value = '';
            document.getElementById('discountType').value = '';
            toggleFeeFields();
        }

        function showSearchLoading() {
            document.getElementById('searchLoading').classList.remove('tw:hidden!');
            document.getElementById('searchResults').classList.add('tw:hidden!');
            document.getElementById('searchEmpty').classList.add('tw:hidden!');
        }

        function hideSearchLoading() {
            document.getElementById('searchLoading').classList.add('tw:hidden!');
        }

        function showSearchEmptyState() {
            document.getElementById('searchEmpty').classList.remove('tw:hidden!');
            document.getElementById('searchResults').classList.add('tw:hidden!');
        }

        function showAlert(message, type) {
            // Remove any existing alerts
            const existingAlerts = document.querySelectorAll('.ui-alert.bs-position-fixed');
            existingAlerts.forEach(alert => alert.remove());

            const alertDiv = document.createElement('div');
            alertDiv.className = `ui-alert ui-alert-${type} ui-alert-dismissible ui-fade show bs-position-fixed tw:fixed!`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                    <strong>${type === 'success' ? 'Success!' : type === 'warning' ? 'Warning!' : 'Error!'}</strong> ${message}
                    <button type="button" class="ui-btn-close" data-nx-dismiss="alert"></button>
                `;

            document.body.appendChild(alertDiv);

            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
@endpush