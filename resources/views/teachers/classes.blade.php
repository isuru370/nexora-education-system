@extends('layouts.app')

@section('title', 'Teacher Classes')
@section('page-title', 'Teacher Classes')

@section('breadcrumb')
    <li class="ui-breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="ui-breadcrumb-item"><a href="{{ route('teachers.index') }}">Teachers</a></li>
    <li class="ui-breadcrumb-item active">Teacher Classes</li>
@endsection

@section('content')
    <div class="ui-container-fluid">
        <!-- Header -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="bs-d-flex tw:flex! tw:justify-between! tw:items-center! tw:mb-4!">
                    <div>
                        <h2 class="ui-h3 tw:mb-1!">Teacher Classes</h2>
                        <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-4!">
                            <span class="ui-badge tw:bg-bs-light! tw:text-bs-dark! tw:text-[length:1rem]!" id="teacherName">Loading...</span>
                            <span class="ui-badge tw:bg-bs-secondary! tw:text-[length:1rem]!" id="teacherId">ID: Loading...</span>
                        </div>
                    </div>
                    <div class="bs-d-flex tw:flex! tw:gap-2!">
                        <button class="ui-btn ui-btn-outline-primary ui-btn-sm" onclick="loadTeacherClasses()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <a href="{{ route('class_rooms.create') }}" class="ui-btn ui-btn-primary ui-btn-sm">
                            <i class="fas fa-plus tw:mr-1!"></i>New Class
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%] tw:mb-4!">
                <div class="ui-card tw:bg-bs-primary! tw:text-bs-white!">
                    <div class="ui-card-body">
                        <div class="bs-d-flex tw:flex! tw:justify-between!">
                            <div>
                                <h4 class="ui-card-title tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:font-bold! tw:mb-1!" id="totalClasses">0</h4>
                                <p class="ui-card-text tw:mb-0!">Total Classes</p>
                            </div>
                            <div class="tw:self-center!">
                                <i class="fas fa-layer-group tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:[opacity:0.75]!"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%] tw:mb-4!">
                <div class="ui-card tw:bg-bs-success! tw:text-bs-white!">
                    <div class="ui-card-body">
                        <div class="bs-d-flex tw:flex! tw:justify-between!">
                            <div>
                                <h4 class="ui-card-title tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:font-bold! tw:mb-1!" id="activeClasses">0</h4>
                                <p class="ui-card-text tw:mb-0!">Active Classes</p>
                            </div>
                            <div class="tw:self-center!">
                                <i class="fas fa-play-circle tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:[opacity:0.75]!"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%] tw:mb-4!">
                <div class="ui-card tw:bg-bs-warning! tw:text-bs-white!">
                    <div class="ui-card-body">
                        <div class="bs-d-flex tw:flex! tw:justify-between!">
                            <div>
                                <h4 class="ui-card-title tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:font-bold! tw:mb-1!" id="ongoingClasses">0</h4>
                                <p class="ui-card-text tw:mb-0!">Ongoing</p>
                            </div>
                            <div class="tw:self-center!">
                                <i class="fas fa-running tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:[opacity:0.75]!"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%] tw:mb-4!">
                <div class="ui-card tw:bg-bs-info! tw:text-bs-white!">
                    <div class="ui-card-body">
                        <div class="bs-d-flex tw:flex! tw:justify-between!">
                            <div>
                                <h4 class="ui-card-title tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:font-bold! tw:mb-1!" id="subjectsCount">0</h4>
                                <p class="ui-card-text tw:mb-0!">Subjects</p>
                            </div>
                            <div class="tw:self-center!">
                                <i class="fas fa-book tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:[opacity:0.75]!"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="ui-card">
                    <div class="ui-card-body">
                        <div class="ui-input-group">
                            <span class="ui-input-group-text tw:bg-bs-light! tw:[border-right:0]!">
                                <i class="fas fa-search tw:text-bs-secondary-color!"></i>
                            </span>
                            <input type="text" id="searchInput" class="ui-form-control tw:[border-left:0]!"
                                placeholder="Search classes by name, subject, or grade...">
                            <button class="ui-btn ui-btn-outline-secondary" type="button" id="clearSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classes Grid -->
        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 bs-row">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:flex-[0_0_auto] tw:w-[100%]">
                <!-- Loading State -->
                <div id="loadingState" class="tw:text-center! tw:py-12!">
                    <div class="ui-spinner-border tw:text-bs-primary! tw:mb-4!" style="width: 3rem; height: 3rem;"></div>
                    <h4 class="tw:text-bs-secondary-color!">Loading Classes</h4>
                    <p class="tw:text-bs-secondary-color!">Please wait while we fetch the class information</p>
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="tw:text-center! tw:py-12!" style="display: none;">
                    <div class="tw:mb-6!">
                        <i class="fas fa-chalkboard-teacher fa-4x tw:text-bs-secondary-color!"></i>
                    </div>
                    <h4 class="tw:text-bs-secondary-color!">No Classes Assigned</h4>
                    <p class="tw:text-bs-secondary-color! tw:mb-6!">This teacher doesn't have any classes assigned yet</p>
                    <a href="{{ route('class_rooms.create') }}" class="ui-btn ui-btn-primary">
                        <i class="fas fa-plus tw:mr-2!"></i>Assign First Class
                    </a>
                </div>

                <!-- Error State -->
                <div id="errorState" class="tw:text-center! tw:py-12!" style="display: none;">
                    <div class="tw:mb-6!">
                        <i class="fas fa-exclamation-triangle fa-4x tw:text-bs-danger!"></i>
                    </div>
                    <h4 class="tw:text-bs-danger!">Unable to Load Classes</h4>
                    <p class="tw:text-bs-secondary-color! tw:mb-6!">There was an error loading the classes</p>
                    <button class="ui-btn ui-btn-outline-primary" onclick="loadTeacherClasses()">
                        <i class="fas fa-redo tw:mr-2!"></i>Try Again
                    </button>
                </div>

                <!-- Classes Grid -->
                <div id="classesGrid" class="bs-row ui-row ui-g-3" style="display: none;">
                    <!-- Classes will be loaded here -->
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
    const teacherId = {{ $id }};
    let allClasses = [];

    document.addEventListener('DOMContentLoaded', function () {
        loadTeacherClasses();

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');

        searchInput.addEventListener('input', function () {
            filterClasses(this.value);
        });

        clearSearch.addEventListener('click', function () {
            searchInput.value = '';
            filterClasses('');
        });
    });

    function loadTeacherClasses() {
        showLoadingState();

        fetch(`/api/class-rooms/teacher/${teacherId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API Response:', data);

                // Check if the response has the expected structure
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    // Response structure: {status: "success", data: [...]}
                    allClasses = data.data;
                    displayClasses(allClasses);
                    updateStats(allClasses);
                    updateTeacherInfo(allClasses[0]?.teacher);
                    showClassesGrid();
                } else if (data.status === 'error' || !data.data || data.data.length === 0) {
                    // No classes found or error response
                    showEmptyState();
                    updateTeacherInfoFromAPI();
                } else {
                    // Fallback for unexpected response structure
                    showEmptyState();
                    updateTeacherInfoFromAPI();
                }
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                showErrorState();
                updateTeacherInfoFromAPI();
            });
    }

    function updateTeacherInfoFromAPI() {
        fetch(`/api/teachers/${teacherId}`)
            .then(response => {
                if (!response.ok) throw new Error('Teacher not found');
                return response.json(); // FIXED: removed line break
            })
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('teacherName').textContent =
                        `${data.data.fname} ${data.data.lname}`;
                    document.getElementById('teacherId').textContent =
                        `ID: ${data.data.custom_id}`;
                }
            })
            .catch(error => {
                console.error('Error loading teacher info:', error);
                document.getElementById('teacherName').textContent = 'Teacher Not Found';
            });
    }

    function displayClasses(classes) {
        const classesGrid = document.getElementById('classesGrid');

        if (!classes || classes.length === 0) {
            classesGrid.innerHTML = '';
            return;
        }

        classesGrid.innerHTML = classes.map(classRoom => {
            // Safely handle potentially undefined properties
            const className = classRoom.class_name || 'Unnamed Class';
            const subjectName = classRoom.subject?.subject_name || 'N/A';
            const gradeName = classRoom.grade?.grade_name || 'N/A';
            const createdDate = classRoom.created_at ?
                new Date(classRoom.created_at).toLocaleDateString() :
                'N/A';

            return `
            <div class="ui-col-xl-4 ui-col-lg-6 tw:mb-4!">
                <div class="ui-card tw:h-full! tw:shadow-bs-sm!">
                    <div class="ui-card-header tw:bg-bs-light!">
                        <div class="bs-d-flex tw:flex! tw:justify-between! tw:items-start!">
                            <h5 class="ui-card-title tw:mb-0! tw:text-bs-dark!">${className}</h5>
                            <div class="bs-d-flex tw:flex! tw:gap-1!">
                                <span class="ui-badge ${classRoom.is_active ? 'tw:bg-bs-success!' : 'tw:bg-bs-secondary!'}">
                                    ${classRoom.is_active ? 'Active' : 'Inactive'}
                                </span>
                                ${classRoom.is_ongoing ? '<span class="ui-badge tw:bg-bs-info!">Ongoing</span>' : ''}
                            </div>
                        </div>
                    </div>
                    <div class="ui-card-body">
                        <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1 bs-row">
                            <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[100%]">
                                <div class="bs-d-flex tw:flex! tw:items-center!">
                                    <i class="fas fa-book tw:text-bs-primary! tw:mr-2!"></i>
                                    <div>
                                        <small class="tw:text-bs-secondary-color!">Subject</small>
                                        <p class="tw:mb-0! tw:font-semibold!">${subjectName}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[100%]">
                                <div class="bs-d-flex tw:flex! tw:items-center!">
                                    <i class="fas fa-graduation-cap tw:text-bs-warning! tw:mr-2!"></i>
                                    <div>
                                        <small class="tw:text-bs-secondary-color!">Grade</small>
                                        <p class="tw:mb-0! tw:font-semibold!">${gradeName}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[100%]">
                                <div class="bs-d-flex tw:flex! tw:items-center!">
                                    <i class="fas fa-calendar tw:text-bs-success! tw:mr-2!"></i>
                                    <div>
                                        <small class="tw:text-bs-secondary-color!">Created</small>
                                        <p class="tw:mb-0! tw:font-semibold!">${createdDate}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="ui-card-footer tw:bg-bs-white! tw:[border-top:0]!">
                        <div class="bs-d-flex tw:flex!">
                            <a href="/teachers/view_student/${classRoom.id}" class="ui-btn ui-btn-outline-warning ui-btn-sm tw:[flex:1_1_auto]!">
                                <i class="fas fa-users tw:mr-1!"></i> View Students
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        }).join('');
    }

    function filterClasses(searchTerm) {
        const filteredClasses = allClasses.filter(classRoom => {
            const searchLower = searchTerm.toLowerCase();
            return (
                classRoom.class_name.toLowerCase().includes(searchLower) ||
                (classRoom.subject?.subject_name || '').toLowerCase().includes(searchLower) ||
                (classRoom.grade?.grade_name || '').toLowerCase().includes(searchLower)
            );
        });

        displayClasses(filteredClasses);
        updateStats(filteredClasses);
    }

    function updateStats(classes) {
        const totalClasses = classes.length;
        const activeClasses = classes.filter(c => c.is_active).length;
        const ongoingClasses = classes.filter(c => c.is_ongoing).length;
        const subjectsCount = new Set(classes.map(c => c.subject?.subject_name).filter(Boolean)).size;

        document.getElementById('totalClasses').textContent = totalClasses;
        document.getElementById('activeClasses').textContent = activeClasses;
        document.getElementById('ongoingClasses').textContent = ongoingClasses;
        document.getElementById('subjectsCount').textContent = subjectsCount;
    }

    function updateTeacherInfo(teacher) {
        if (teacher) {
            document.getElementById('teacherName').textContent = `${teacher.fname} ${teacher.lname}`;
            document.getElementById('teacherId').textContent = `ID: ${teacher.custom_id}`;
        }
    }

    function showLoadingState() {
        document.getElementById('loadingState').style.display = 'block';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('errorState').style.display = 'none';
        document.getElementById('classesGrid').style.display = 'none';
    }

    function showClassesGrid() {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('errorState').style.display = 'none';
        document.getElementById('classesGrid').style.display = 'flex';
    }

    function showEmptyState() {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'block';
        document.getElementById('errorState').style.display = 'none';
        document.getElementById('classesGrid').style.display = 'none';
    }

    function showErrorState() {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('errorState').style.display = 'block';
        document.getElementById('classesGrid').style.display = 'none';
    }
</script>