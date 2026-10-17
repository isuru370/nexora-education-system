@php
    $isEdit = isset($exam) && $exam;
    $formAction = $isEdit ? route('admin.exams.update', $exam->id) : route('admin.exams.store');
    $formMethod = $isEdit ? 'PUT' : 'POST';

    $oldTitle = old('title', $exam->title ?? '');
    $oldClassId = old('student_class_id', $exam->student_class_id ?? '');
    $oldCategoryId = old('class_category_id', $exam->class_category_id ?? '');
    $oldHallId = old('class_hall_id', $exam->class_hall_id ?? '');
    $oldExamDate = old('exam_date', isset($exam->exam_date) ? $exam->exam_date->format('Y-m-d') : '');
    $oldStartTime = old('start_time', isset($exam->start_time) ? \Carbon\Carbon::parse($exam->start_time)->format('H:i') : '');
    $oldEndTime = old('end_time', isset($exam->end_time) ? \Carbon\Carbon::parse($exam->end_time)->format('H:i') : '');
    $oldNote = old('note', $exam->note ?? '');
    $oldStatus = old('status', $exam->status ?? 'scheduled');

    $today = now()->format('Y-m-d');

    $selectedClassName = 'Not set';
    if ($oldClassId && isset($classes)) {
        $selectedClass = $classes->firstWhere('id', $oldClassId);
        $selectedClassName = $selectedClass ? $selectedClass->class_name : 'Not set';
    }

    $selectedHallName = 'Not set';
    if ($oldHallId && isset($halls)) {
        $selectedHall = $halls->firstWhere('id', $oldHallId);
        $selectedHallName = $selectedHall ? $selectedHall->hall_name : 'Not set';
    }

    $selectedCategoryName = 'Not set';
    if ($oldCategoryId && isset($categories)) {
        $selectedCategory = $categories->firstWhere('id', $oldCategoryId);
        $selectedCategoryName = $selectedCategory ? $selectedCategory->category_name : 'Not set';
    }

    $formattedTime = 'Not set';
    if ($oldStartTime && $oldEndTime) {
        $formattedTime = date('h:i A', strtotime($oldStartTime)) . ' - ' . date('h:i A', strtotime($oldEndTime));
    }

    $formattedDate = 'Not set';
    if ($oldExamDate) {
        $formattedDate = date('d M Y', strtotime($oldExamDate));
    }
@endphp

<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">{{ $isEdit ? 'Edit Exam' : 'Create New Exam' }}</h1>
                <p class="hero-subtitle">
                    {{ $isEdit ? 'Update exam details and schedule' : 'Add a new exam to the system' }}
                </p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <span class="nx-badge {{ $isEdit ? 'nx-badge-amber' : 'nx-badge-green' }}">
                        <i class="bi bi-{{ $isEdit ? 'pencil' : 'plus' }}"></i>
                        {{ $isEdit ? 'Editing' : 'Creating' }}
                    </span>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if ($errors->any())
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li><i class="bi bi-dot"></i> {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-x-circle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if (session('success'))
        <div class="nx-alert nx-alert-green mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form action="{{ $formAction }}" method="POST" id="examForm" novalidate>
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">

            {{-- LEFT COLUMN --}}
            <div class="col-lg-8 d-flex flex-column gap-4">

                {{-- BASIC INFO --}}
                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-info-circle text-primary"></i> Basic Information</h5>
                            <p class="nx-card-subtitle">Title and notes for this exam</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="row g-3">

                            {{-- TITLE --}}
                            <div class="col-12">
                                <label for="title" class="nx-label">
                                    Exam Title <span style="color: var(--nx-red);">*</span>
                                </label>
                                <input type="text" name="title" id="title"
                                    class="form-control nx-input @error('title') is-invalid @enderror"
                                    value="{{ $oldTitle }}" placeholder="Enter exam title" required>
                                @error('title')
                                    <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                                @else
                                    <small class="cell-sub d-block mt-1">
                                        <i class="bi bi-info-circle"></i>
                                        Enter a clear and descriptive title for the exam.
                                    </small>
                                @enderror
                            </div>

                            {{-- NOTE --}}
                            <div class="col-12">
                                <label for="note" class="nx-label">Additional Notes</label>
                                <textarea name="note" id="note"
                                    class="form-control nx-input @error('note') is-invalid @enderror"
                                    placeholder="Add notes about this exam" rows="3">{{ $oldNote }}</textarea>
                                @error('note')
                                    <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                                @else
                                    <small class="cell-sub d-block mt-1">
                                        <i class="bi bi-info-circle"></i>
                                        Optional: Add any special instructions or notes.
                                    </small>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SCHEDULE --}}
                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-calendar-event text-primary"></i> Exam Schedule</h5>
                            <p class="nx-card-subtitle">Date and time</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="row g-3">

                            {{-- DATE --}}
                            <div class="col-md-4">
                                <label for="exam_date" class="nx-label">
                                    Exam Date <span style="color: var(--nx-red);">*</span>
                                </label>
                                <input type="date" name="exam_date" id="exam_date"
                                    class="form-control nx-input @error('exam_date') is-invalid @enderror"
                                    value="{{ $oldExamDate }}" min="{{ $today }}" required>
                                @error('exam_date')
                                    <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>

                            {{-- START TIME --}}
                            <div class="col-md-4">
                                <label for="start_time" class="nx-label">
                                    Start Time <span style="color: var(--nx-red);">*</span>
                                </label>
                                <input type="time" name="start_time" id="start_time"
                                    class="form-control nx-input @error('start_time') is-invalid @enderror"
                                    value="{{ $oldStartTime }}" required>
                                @error('start_time')
                                    <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>

                            {{-- END TIME --}}
                            <div class="col-md-4">
                                <label for="end_time" class="nx-label">
                                    End Time <span style="color: var(--nx-red);">*</span>
                                </label>
                                <input type="time" name="end_time" id="end_time"
                                    class="form-control nx-input @error('end_time') is-invalid @enderror"
                                    value="{{ $oldEndTime }}" required>
                                @error('end_time')
                                    <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- TIME WARNING --}}
                        <div id="timeWarning" class="nx-alert nx-alert-amber d-none mt-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span id="timeWarningText">End time must be after start time.</span>
                        </div>
                    </div>
                </div>

                {{-- ASSIGNMENT --}}
                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-diagram-3 text-primary"></i> Assignment Details</h5>
                            <p class="nx-card-subtitle">Class, category and hall</p>
                        </div>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="row g-3">

                            {{-- CLASS --}}
                            <div class="col-md-4">
                                <label for="student_class_id" class="nx-label">
                                    Class <span style="color: var(--nx-red);">*</span>
                                </label>
                                <select name="student_class_id" id="student_class_id"
                                    class="form-select nx-input @error('student_class_id') is-invalid @enderror"
                                    required>
                                    <option value="">Select Class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ $oldClassId == $class->id ? 'selected' : '' }}>
                                            {{ $class->class_name }}
                                            @if ($class->grade)
                                                ({{ $class->grade->grade_name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_class_id')
                                    <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>

                            {{-- CATEGORY --}}
                            <div class="col-md-4">
                                <label for="class_category_id" class="nx-label">
                                    Category <span style="color: var(--nx-red);">*</span>
                                </label>
                                <select name="class_category_id" id="class_category_id"
                                    class="form-select nx-input @error('class_category_id') is-invalid @enderror"
                                    required>
                                    <option value="">Select Category</option>
                                    @if ($oldCategoryId && isset($categories))
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ $oldCategoryId == $category->id ? 'selected' : '' }}>
                                                {{ $category->category_name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('class_category_id')
                                    <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>

                            {{-- HALL --}}
                            <div class="col-md-4">
                                <label for="class_hall_id" class="nx-label">
                                    Exam Hall <span style="color: var(--nx-red);">*</span>
                                </label>
                                <select name="class_hall_id" id="class_hall_id"
                                    class="form-select nx-input @error('class_hall_id') is-invalid @enderror"
                                    required>
                                    <option value="">Select Hall</option>
                                    @foreach ($halls as $hall)
                                        <option value="{{ $hall->id }}"
                                            {{ $oldHallId == $hall->id ? 'selected' : '' }}>
                                            {{ $hall->hall_name }}
                                            @if ($hall->capacity)
                                                (Capacity: {{ $hall->capacity }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_hall_id')
                                    <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="col-lg-4 d-flex flex-column gap-4">

                {{-- STATUS (Edit Only) --}}
                @if ($isEdit)
                    <div class="nx-card">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-toggles text-primary"></i> Exam Status</h5>
                            </div>
                        </div>
                        <div class="p-3">
                            <select name="status" id="statusSelect"
                                class="form-select nx-input @error('status') is-invalid @enderror">
                                <option value="scheduled" {{ $oldStatus == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="ongoing" {{ $oldStatus == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                                <option value="completed" {{ $oldStatus == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $oldStatus == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            @error('status')
                                <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                            @enderror

                            <div class="status-info mt-3">
                                <div class="status-indicator" id="statusIndicator">
                                    @php
                                        $statusBadgeMap = [
                                            'scheduled' => 'nx-badge-blue',
                                            'ongoing'   => 'nx-badge-amber',
                                            'completed' => 'nx-badge-green',
                                            'cancelled' => 'nx-badge-red',
                                        ];
                                        $currentStatusClass = $statusBadgeMap[$oldStatus] ?? 'nx-badge-gray';
                                    @endphp
                                    <span class="nx-badge {{ $currentStatusClass }}">
                                        {{ ucfirst($oldStatus) }}
                                    </span>
                                </div>
                                <small class="cell-sub d-block mt-2">
                                    <i class="bi bi-info-circle"></i>
                                    <span id="statusDescription">
                                        @switch($oldStatus)
                                            @case('scheduled') Exam is planned and waiting to start. @break
                                            @case('ongoing') Exam is currently in progress. @break
                                            @case('completed') Exam has been finished. @break
                                            @case('cancelled') Exam has been cancelled. @break
                                        @endswitch
                                    </span>
                                </small>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- SUMMARY --}}
                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-list-check text-primary"></i> Exam Summary</h5>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="summary-list">

                            <div class="summary-item">
                                <span class="summary-label"><i class="bi bi-pencil"></i> Title</span>
                                <span class="summary-value" id="summaryTitle">{{ $oldTitle ?: 'Not set' }}</span>
                            </div>

                            <div class="summary-item">
                                <span class="summary-label"><i class="bi bi-calendar"></i> Date</span>
                                <span class="summary-value" id="summaryDate">{{ $formattedDate }}</span>
                            </div>

                            <div class="summary-item">
                                <span class="summary-label"><i class="bi bi-clock"></i> Time</span>
                                <span class="summary-value" id="summaryTime">{{ $formattedTime }}</span>
                            </div>

                            <div class="summary-item">
                                <span class="summary-label"><i class="bi bi-people"></i> Class</span>
                                <span class="summary-value" id="summaryClass">{{ $selectedClassName }}</span>
                            </div>

                            <div class="summary-item">
                                <span class="summary-label"><i class="bi bi-tags"></i> Category</span>
                                <span class="summary-value" id="summaryCategory">{{ $selectedCategoryName }}</span>
                            </div>

                            <div class="summary-item">
                                <span class="summary-label"><i class="bi bi-building"></i> Hall</span>
                                <span class="summary-value" id="summaryHall">{{ $selectedHallName }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="nx-card">
                    <div class="p-3 d-flex flex-column gap-2">
                        <button type="submit" class="nx-btn nx-btn-primary w-100">
                            <i class="bi bi-{{ $isEdit ? 'check-circle' : 'plus-circle' }}"></i>
                            {{ $isEdit ? 'Update Exam' : 'Create Exam' }}
                        </button>
                        <a href="{{ route('admin.exams.index') }}" class="nx-btn nx-btn-light w-100">
                            <i class="bi bi-arrow-left"></i> Cancel & Go Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>


@push('styles')
<style>
    /* ============================================================
       Exam Form — page-specific styles only.
       ============================================================ */

    .dashboard-page .nx-error {
        font-size: .74rem;
        color: var(--nx-red);
        margin-top: .35rem;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    /* Status info */
    .dashboard-page .status-info {
        padding: .75rem 1rem;
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
    }
    .dashboard-page .status-indicator .nx-badge {
        font-size: .8rem;
        padding: .35rem .8rem;
    }

    /* Summary */
    .dashboard-page .summary-list {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
    .dashboard-page .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .55rem .75rem;
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        gap: 1rem;
    }
    .dashboard-page .summary-label {
        font-size: .72rem;
        font-weight: 700;
        color: var(--nx-muted);
        display: flex;
        align-items: center;
        gap: .4rem;
        flex-shrink: 0;
    }
    .dashboard-page .summary-label i {
        font-size: .8rem;
        color: var(--nx-blue);
    }
    .dashboard-page .summary-value {
        font-size: .82rem;
        font-weight: 600;
        color: var(--nx-text);
        text-align: right;
        max-width: 55%;
        word-break: break-word;
    }

    /* Loading state for submit button */
    .dashboard-page .nx-btn.btn-loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }
    .dashboard-page .nx-btn.btn-loading::after {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        top: 50%;
        left: 50%;
        margin: -9px 0 0 -9px;
        border: 2px solid rgba(255, 255, 255, .3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: nx-spin .8s linear infinite;
    }
    @keyframes nx-spin { to { transform: rotate(360deg); } }

    @media (max-width: 768px) {
        .dashboard-page .summary-item {
            flex-direction: column;
            align-items: flex-start;
            gap: .2rem;
        }
        .dashboard-page .summary-value {
            max-width: 100%;
            text-align: left;
        }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        'use strict';

        /* ============================================ */
        /* 1. CLASS CATEGORY DYNAMIC LOADING            */
        /* ============================================ */
        const selectedCategoryId = "{{ $oldCategoryId }}";

        const classCategories = {
            @foreach ($classes as $class)
                {{ $class->id }}: [
                    @foreach ($class->categoryFees as $fee)
                        {
                            id: {{ $fee->category->id }},
                            name: "{{ $fee->category->category_name }}"
                        },
                    @endforeach
                ],
            @endforeach
        };

        const classSelect    = document.getElementById('student_class_id');
        const categorySelect = document.getElementById('class_category_id');

        function loadCategories(classId) {
            categorySelect.innerHTML = '<option value="">Select Category</option>';

            if (!classId || !classCategories[classId] || classCategories[classId].length === 0) {
                categorySelect.innerHTML += '<option value="" disabled>No categories available</option>';
                return;
            }

            classCategories[classId].forEach(function (category) {
                const selected = category.id == selectedCategoryId ? 'selected' : '';
                categorySelect.innerHTML += `
                    <option value="${category.id}" ${selected}>${category.name}</option>
                `;
            });
        }

        classSelect.addEventListener('change', function () {
            loadCategories(this.value);
            updateSummary();
        });

        if (classSelect.value) {
            loadCategories(classSelect.value);
        }

        /* ============================================ */
        /* 2. REAL-TIME VALIDATION                      */
        /* ============================================ */
        const form   = document.getElementById('examForm');
        const inputs = form.querySelectorAll('input, select, textarea');

        inputs.forEach(input => {
            input.addEventListener('blur', function () { validateField(this); });
            input.addEventListener('input', function () {
                if (this.classList.contains('is-invalid') || this.classList.contains('is-valid')) {
                    validateField(this);
                }
                updateSummary();
            });
            input.addEventListener('change', function () {
                validateField(this);
                updateSummary();
            });
        });

        function validateField(input) {
            const isValid = input.checkValidity();

            if (!isValid) {
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
            } else {
                input.classList.remove('is-invalid');
                if (input.value.trim() !== '') {
                    input.classList.add('is-valid');
                }
            }
        }

        /* ============================================ */
        /* 3. TIME VALIDATION                           */
        /* ============================================ */
        const startTime       = document.getElementById('start_time');
        const endTime         = document.getElementById('end_time');
        const timeWarning     = document.getElementById('timeWarning');
        const timeWarningText = document.getElementById('timeWarningText');

        function validateTimes() {
            if (startTime.value && endTime.value) {
                if (startTime.value >= endTime.value) {
                    timeWarning.classList.remove('d-none');
                    timeWarningText.textContent = 'End time must be after start time.';
                    endTime.classList.add('is-invalid');
                    return false;
                }
                timeWarning.classList.add('d-none');
                endTime.classList.remove('is-invalid');
                endTime.classList.add('is-valid');
            }
            return true;
        }

        ['change', 'input'].forEach(ev => {
            startTime.addEventListener(ev, validateTimes);
            endTime.addEventListener(ev, validateTimes);
        });

        /* ============================================ */
        /* 4. DATE VALIDATION                           */
        /* ============================================ */
        const examDate = document.getElementById('exam_date');
        const today    = new Date().toISOString().split('T')[0];
        examDate.setAttribute('min', today);

        examDate.addEventListener('change', function () {
            if (this.value < today) {
                this.classList.add('is-invalid');
                this.setCustomValidity('Exam date cannot be in the past.');
            } else {
                this.classList.remove('is-invalid');
                this.setCustomValidity('');
                this.classList.add('is-valid');
            }
            updateSummary();
        });

        /* ============================================ */
        /* 5. SUMMARY UPDATE                            */
        /* ============================================ */
        function updateSummary() {
            const titleInput = document.getElementById('title');
            document.getElementById('summaryTitle').textContent = titleInput.value || 'Not set';

            const dateInput = document.getElementById('exam_date');
            if (dateInput.value) {
                const date = new Date(dateInput.value + 'T00:00:00');
                document.getElementById('summaryDate').textContent = date.toLocaleDateString('en-US', {
                    day: '2-digit', month: 'short', year: 'numeric'
                });
            } else {
                document.getElementById('summaryDate').textContent = 'Not set';
            }

            if (startTime.value && endTime.value) {
                document.getElementById('summaryTime').textContent =
                    `${formatTime(startTime.value)} - ${formatTime(endTime.value)}`;
            } else {
                document.getElementById('summaryTime').textContent = 'Not set';
            }

            if (classSelect.value) {
                const opt = classSelect.options[classSelect.selectedIndex];
                document.getElementById('summaryClass').textContent = opt.text.split('(')[0].trim();
            } else {
                document.getElementById('summaryClass').textContent = 'Not set';
            }

            if (categorySelect.value) {
                const opt = categorySelect.options[categorySelect.selectedIndex];
                document.getElementById('summaryCategory').textContent = opt.text.trim();
            } else {
                document.getElementById('summaryCategory').textContent = 'Not set';
            }

            const hallSelect = document.getElementById('class_hall_id');
            if (hallSelect.value) {
                const opt = hallSelect.options[hallSelect.selectedIndex];
                document.getElementById('summaryHall').textContent = opt.text.split('(')[0].trim();
            } else {
                document.getElementById('summaryHall').textContent = 'Not set';
            }
        }

        function formatTime(time) {
            const [hours, minutes] = time.split(':');
            const h = parseInt(hours);
            const ampm = h >= 12 ? 'PM' : 'AM';
            const h12 = h % 12 || 12;
            return `${h12}:${minutes} ${ampm}`;
        }

        updateSummary();

        /* ============================================ */
        /* 6. STATUS SELECTOR WITH VISUAL FEEDBACK      */
        /* ============================================ */
        const statusSelect = document.getElementById('statusSelect');
        if (statusSelect) {
            statusSelect.addEventListener('change', function () {
                const status      = this.value;
                const indicator   = document.getElementById('statusIndicator');
                const description = document.getElementById('statusDescription');

                const statusMap = {
                    'scheduled': { class: 'nx-badge-blue',  text: 'Exam is planned and waiting to start.' },
                    'ongoing':   { class: 'nx-badge-amber', text: 'Exam is currently in progress.' },
                    'completed': { class: 'nx-badge-green', text: 'Exam has been finished.' },
                    'cancelled': { class: 'nx-badge-red',   text: 'Exam has been cancelled.' }
                };

                const info = statusMap[status] || statusMap['scheduled'];

                indicator.innerHTML = `<span class="nx-badge ${info.class}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                description.textContent = info.text;

                Swal.fire({
                    icon: 'info',
                    title: 'Status Updated',
                    text: `Exam status changed to ${status.charAt(0).toUpperCase() + status.slice(1)}`,
                    timer: 1500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            });
        }

        /* ============================================ */
        /* 7. FORM SUBMISSION WITH VALIDATION           */
        /* ============================================ */
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            let isValid = true;
            const allInputs = this.querySelectorAll('input[required], select[required], textarea[required]');

            allInputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                }
            });

            if (!validateTimes()) isValid = false;
            if (examDate.value && examDate.value < today) {
                examDate.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                const firstError = this.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fix all errors before submitting.',
                    timer: 3000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;

            this.submit();
        });

        /* ============================================ */
        /* 8. KEYBOARD SHORTCUTS                        */
        /* ============================================ */
        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }
        });

        /* ============================================ */
        /* 9. CONFIRM ON LEAVE                          */
        /* ============================================ */
        let formChanged = false;
        form.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('change', () => { formChanged = true; });
            input.addEventListener('input',  () => { formChanged = true; });
        });

        window.addEventListener('beforeunload', function (e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });

        form.addEventListener('submit', () => { formChanged = false; });

        /* ============================================ */
        /* 10. TOOLTIP INITIALIZATION                   */
        /* ============================================ */
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

        console.log('✅ Exam form initialized successfully');
    });
</script>
@endpush