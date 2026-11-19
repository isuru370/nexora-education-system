@extends('layouts.app')

@section('title', 'Attendance')
@section('page-title', 'Attendance')

@section('content')
    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Attendance Management</h1>
                    <p class="hero-subtitle">Scan a QR code or manually enter a student code to mark attendance.</p>
                </div>
                <div class="hero-right">
                    <div class="hero-actions">
                        <button type="button" class="nx-btn nx-btn-light" disabled><i class="bi bi-printer"></i> Print</button>
                        <button type="button" class="nx-btn nx-btn-light" disabled><i class="bi bi-file-earmark-excel-fill"></i> Export</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALERT --}}
        <div id="attendanceAlert" class="ui-alert ui-alert-info tw:hidden! nx-alert" data-persist-alert="true" role="alert">
            <div class="tw:flex! tw:items-start! tw:gap-2!">
                <i class="bi bi-info-circle-fill tw:text-[length:1.25rem]! tw:mt-1!" id="alertIcon"></i>
                <div class="tw:grow!">
                    <strong id="alertTitle">Info</strong>
                    <div id="alertMessage" class="tw:mt-1!"></div>
                </div>
                <button type="button" class="ui-btn-close" aria-label="Close"
                    onclick="document.getElementById('attendanceAlert').classList.add('d-none')"></button>
            </div>
        </div>

        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">
            {{-- QR READER --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                <div class="nx-card tw:h-full!">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-qr-code-scan tw:text-(color:--nx-blue)!"></i> QR Reader</h5>
                        <span class="nx-badge nx-badge-blue">Live</span>
                    </div>
                    <div class="bs-p-3 tw:p-4!">
                        <ul class="ui-nav ui-nav-pills nx-pills tw:mb-4!" role="tablist">
                            <li class="ui-nav-item tw:mr-2!">
                                <button class="ui-nav-link active" data-nx-toggle="tab" data-nx-target="#scanTab" type="button">Scan QR</button>
                            </li>
                            <li class="ui-nav-item">
                                <button class="ui-nav-link" data-nx-toggle="tab" data-nx-target="#manualTab" type="button">Manual Entry</button>
                            </li>
                        </ul>
                        <div class="ui-tab-content">
                            <div class="ui-tab-pane ui-fade show active" id="scanTab">
                                <div id="qr-reader" class="qr-reader-box"></div>
                                <div class="tw:grid! tw:gap-2! tw:mt-4!">
                                    <button type="button" class="nx-btn nx-btn-primary" id="startScannerBtn">
                                        <i class="bi bi-camera-fill"></i> Start Scanner
                                    </button>
                                    <button type="button" class="nx-btn nx-btn-light tw:hidden!" id="stopScannerBtn">
                                        <i class="bi bi-stop-circle"></i> Stop Scanner
                                    </button>
                                </div>
                                <small class="cell-sub tw:block! tw:mt-4!">Camera scanning requires HTTPS or localhost.</small>
                            </div>
                            <div class="ui-tab-pane ui-fade" id="manualTab">
                                <form id="manualQrForm" autocomplete="off">
                                    @csrf
                                    <div class="tw:mb-4!">
                                        <label for="manualQrCode" class="nx-label">Student QR / Code</label>
                                        <div class="nx-input-group">
                                            <span class="nx-input-group-text"><i class="bi bi-upc-scan"></i></span>
                                            <input type="text" class="ui-form-control nx-input" id="manualQrCode"
                                                placeholder="Enter QR code" maxlength="150">
                                        </div>
                                    </div>
                                    <div class="tw:grid!">
                                        <button type="submit" class="nx-btn nx-btn-success" id="manualReadBtn">
                                            <i class="bi bi-person-check-fill"></i> Read Student
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT SIDE --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[66.66666667%]">
                <div id="emptyStateCard" class="nx-card">
                    <div class="empty-state-box">
                        <div class="empty-icon"><i class="bi bi-qr-code-scan"></i></div>
                        <h5 class="empty-title">No Student Selected</h5>
                        <p class="cell-sub tw:mb-0!">Scan a QR code or enter a student code manually.</p>
                    </div>
                </div>

                <div id="studentDetailsCard" class="nx-card tw:hidden! tw:mb-6!">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-person-badge tw:text-(color:--nx-blue)!"></i> Student Details</h5>
                        <span class="nx-badge nx-badge-gray" id="qrTypeBadge">-</span>
                    </div>
                    <div class="bs-p-3 tw:p-4!">
                        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:items-center!">
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%] tw:text-center!">
                                <img id="studentImage" src="{{ asset('storage/uploads/male.png') }}" class="student-image" alt="Student Image">
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[75%]">
                                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                        <div class="detail-box"><small class="cell-sub">Student ID</small><div class="detail-value" id="studentId">-</div></div>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                        <div class="detail-box"><small class="cell-sub">Name</small><div class="detail-value" id="studentName">-</div></div>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                        <div class="detail-box"><small class="cell-sub">Mobile</small><div class="detail-value" id="studentMobile">-</div></div>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                        <div class="detail-box"><small class="cell-sub">Guardian Mobile</small><div class="detail-value" id="guardianMobile">-</div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="attendanceDetailsCard" class="nx-card tw:hidden!">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-clipboard-check tw:text-(color:--nx-blue)!"></i> Attendance Details</h5>
                        <span class="nx-badge nx-badge-green">Ready</span>
                    </div>
                    <div class="bs-p-3 tw:p-4!"><div id="attendanceDetailsContent"></div></div>
                </div>

                <div id="errorDetailsCard" class="nx-card tw:mt-6! tw:hidden!">
                    <div class="nx-card-header nx-card-header-danger">
                        <h5 class="nx-card-title tw:text-bs-white!"><i class="bi bi-exclamation-triangle-fill"></i> Error Details</h5>
                    </div>
                    <div class="bs-p-3 tw:p-4!"><div id="errorDetails"></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ADD CLASS MODAL --}}
    <div class="ui-modal ui-fade" id="addClassModal" tabindex="-1" aria-hidden="true">
        <div class="ui-modal-dialog ui-modal-dialog-centered ui-modal-lg">
            <div class="ui-modal-content nx-modal">
                <form id="addClassForm" autocomplete="off">
                    @csrf
                    <div class="ui-modal-header nx-modal-header">
                        <h5 class="ui-modal-title">Add Student To Class</h5>
                        <button type="button" class="ui-btn-close" data-nx-dismiss="modal"></button>
                    </div>
                    <div class="ui-modal-body nx-modal-body">
                        <div class="nx-info-box">
                            <div><strong>Student:</strong> <span id="modalStudentName">-</span></div>
                            <div><strong>Class:</strong> <span id="modalClassName">-</span></div>
                            <div><strong>Category:</strong> <span id="modalCategoryName">-</span></div>
                        </div>
                        <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                <label class="nx-label">Free Card</label>
                                <select id="modalIsFreeCard" class="ui-form-select nx-input">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            @php
                                // fee options of this class category (the fee the student pays)
                                $addClassFeeOptions = \App\Models\ClassCategoryFeeOption::query()
                                    ->where('class_category_fee_id', (int) request('class_category_fee_id'))
                                    ->where('is_active', true)
                                    ->orderByDesc('is_default')
                                    ->orderByDesc('fee')
                                    ->get(['id', 'label', 'fee', 'is_default']);
                            @endphp
                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[66.66666667%]">
                                <label class="nx-label">Fee Option <span class="tw:text-bs-danger!">*</span></label>
                                <select id="modalFeeOption" class="ui-form-select nx-input">
                                    @forelse ($addClassFeeOptions as $feeOption)
                                        <option value="{{ $feeOption->id }}" @selected($feeOption->is_default)>
                                            {{ $feeOption->label }} - Rs. {{ number_format((float) $feeOption->fee, 2) }}
                                        </option>
                                    @empty
                                        <option value="">No active fee option for this class category</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                <label class="nx-label">Enrolled At</label>
                                <input type="date" id="modalEnrolledAt" class="ui-form-control nx-input" value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                    </div>
                    <div class="ui-modal-footer nx-modal-footer">
                        <button type="button" class="nx-btn nx-btn-light" data-nx-dismiss="modal">Cancel</button>
                        <button type="submit" class="nx-btn nx-btn-primary" id="saveClassBtn">Save Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Attendance (1) — page-specific styles only.
       ============================================================ */

    .dashboard-page .nx-card-header-danger {
        background: var(--nx-red);
        border-bottom: 0;
    }

    .dashboard-page .nx-btn-success {
        background: var(--nx-green); border-color: var(--nx-green); color: #fff;
    }

    .dashboard-page .bs-p-3 .nx-btn-light,
    .dashboard-page .ui-modal .nx-btn-light {
        background: #fff; border-color: var(--nx-border); color: var(--nx-muted);
    }

    .dashboard-page .nx-pills .ui-nav-link {
        border-radius: 8px;
        font-weight: 500;
        font-size: .82rem;
        color: var(--nx-muted);
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        padding: .4rem .9rem;
    }
    .dashboard-page .nx-pills .ui-nav-link.active {
        background: var(--nx-blue); color: #fff; border-color: var(--nx-blue);
    }

    .dashboard-page .qr-reader-box {
        width: 100%;
        min-height: 320px;
        border-radius: var(--nx-radius);
        overflow: hidden;
        background: #f4f6fa;
        border: 1px dashed #cbd5e1;
    }

    .dashboard-page .nx-input-group {
        display: flex;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }
    .dashboard-page .nx-input-group:focus-within {
        border-color: var(--nx-blue);
        box-shadow: 0 0 0 3px var(--nx-blue-soft);
    }
    .dashboard-page .nx-input-group-text {
        background: #f4f6fa;
        border: 0;
        color: var(--nx-muted);
        padding: .5rem .75rem;
        display: flex;
        align-items: center;
    }
    .dashboard-page .nx-input-group .nx-input { border: 0; box-shadow: none; }
    .dashboard-page .nx-input-group .nx-input:focus { box-shadow: none; }

    .dashboard-page .student-image {
        width: 150px; height: 150px;
        object-fit: cover;
        border-radius: 12px;
        border: 2px solid var(--nx-border);
        box-shadow: 0 1px 2px rgba(20, 28, 58, .06);
        background: #f8fafc;
    }

    .dashboard-page .detail-box {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        padding: .7rem .9rem;
    }
    .dashboard-page .detail-value {
        font-weight: 600;
        color: var(--nx-text);
        font-size: .85rem;
    }

    .dashboard-page .info-card {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        padding: 14px;
        height: 100%;
        transition: background-color .15s;
    }
    .dashboard-page .info-card:hover { background: #f4f6fa; }
    .dashboard-page .info-card small {
        color: var(--nx-muted);
        display: block;
        margin-bottom: 6px;
        font-size: .72rem;
    }
    .dashboard-page .info-card h6 {
        margin: 0;
        font-weight: 700;
        color: var(--nx-text);
        font-size: .88rem;
    }

    .dashboard-page .empty-state-box { text-align: center; padding: 3rem 1.5rem; }
    .dashboard-page .empty-icon {
        width: 72px; height: 72px;
        border-radius: 12px;
        background: var(--nx-blue-soft);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--nx-blue);
        font-size: 2rem;
        margin-bottom: 1rem;
    }
    .dashboard-page .empty-title {
        font-weight: 700;
        font-size: 1rem;
        color: var(--nx-text);
        margin-bottom: .5rem;
    }

    .dashboard-page .nx-modal {
        border: 0;
        border-radius: var(--nx-radius);
        box-shadow: 0 10px 40px rgba(20, 28, 58, .15);
        overflow: hidden;
    }
    .dashboard-page .nx-modal-header {
        border-bottom: 1px solid var(--nx-border);
        padding: 1rem 1.25rem;
    }
    .dashboard-page .nx-modal-header .ui-modal-title {
        font-weight: 600;
        font-size: .95rem;
        color: var(--nx-text);
    }
    .dashboard-page .nx-modal-body { padding: 1.25rem; }
    .dashboard-page .nx-modal-footer {
        border-top: 1px solid var(--nx-border);
        padding: .85rem 1.25rem;
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
    }
    .dashboard-page .nx-info-box {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        padding: .85rem 1rem;
        font-size: .83rem;
        margin-bottom: 1.25rem;
    }
    .dashboard-page .nx-info-box div { margin-bottom: .25rem; }
    .dashboard-page .nx-info-box div:last-child { margin-bottom: 0; }

    .dashboard-page .ui-form-check-input:checked {
        background-color: var(--nx-blue);
        border-color: var(--nx-blue);
    }

    @media (max-width: 768px) {
        .dashboard-page .student-image { width: 130px; height: 130px; }
    }
</style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const readApiUrl = @json(route('api.attendance.read'));
            const storeAttendanceApiUrl = @json(route('api.attendance.store'));
            const storeEnrollmentApiUrl = @json(route('api.student-class-enrollments.store'));
            const defaultStudentImage = @json(asset('storage/uploads/male.png'));

            const classScheduleId = @json(request('class_schedule_id'));
            const studentClassId = @json(request('student_class_id'));
            const classCategoryFeeId = @json(request('class_category_fee_id'));

            const alertBox = document.getElementById('attendanceAlert');
            const alertIcon = document.getElementById('alertIcon');
            const alertTitle = document.getElementById('alertTitle');
            const alertMessage = document.getElementById('alertMessage');
            const errorDetailsCard = document.getElementById('errorDetailsCard');
            const errorDetails = document.getElementById('errorDetails');

            const emptyStateCard = document.getElementById('emptyStateCard');
            const studentDetailsCard = document.getElementById('studentDetailsCard');
            const attendanceDetailsCard = document.getElementById('attendanceDetailsCard');

            const studentImage = document.getElementById('studentImage');
            const studentId = document.getElementById('studentId');
            const studentName = document.getElementById('studentName');
            const studentMobile = document.getElementById('studentMobile');
            const guardianMobile = document.getElementById('guardianMobile');
            const qrTypeBadge = document.getElementById('qrTypeBadge');
            const attendanceDetailsContent = document.getElementById('attendanceDetailsContent');

            const manualQrForm = document.getElementById('manualQrForm');
            const manualQrCode = document.getElementById('manualQrCode');
            const manualReadBtn = document.getElementById('manualReadBtn');

            const startScannerBtn = document.getElementById('startScannerBtn');
            const stopScannerBtn = document.getElementById('stopScannerBtn');

            const addClassModalEl = document.getElementById('addClassModal');
            const addClassModal = addClassModalEl ? new NxUi.Modal(addClassModalEl) : null;
            const addClassForm = document.getElementById('addClassForm');
            const saveClassBtn = document.getElementById('saveClassBtn');

            const modalStudentName = document.getElementById('modalStudentName');
            const modalClassName = document.getElementById('modalClassName');
            const modalCategoryName = document.getElementById('modalCategoryName');
            const modalIsFreeCard = document.getElementById('modalIsFreeCard');
            const modalFeeOption = document.getElementById('modalFeeOption');
            const modalEnrolledAt = document.getElementById('modalEnrolledAt');

            let html5QrCode = null;
            let scannerRunning = false;
            let processing = false;
            let lastScannedCode = null;
            let lastScanTime = 0;
            let currentQrCode = null;
            let currentStudent = null;
            let currentEnrollment = null;
            let currentMarkMethod = 'qr_web';

            const SCAN_COOLDOWN_MS = 1200;

            function showAlert(type, message) {
                const safeType = ['success', 'danger', 'warning', 'info'].includes(type) ? type : 'info';

                alertBox.className = `ui-alert ui-alert-${safeType} nx-alert`;
                alertBox.classList.remove('tw:hidden!');
                alertBox.style.display = 'block';

                if (alertIcon) {
                    alertIcon.className =
                        safeType === 'success' ? 'bi bi-check-circle-fill tw:text-[length:1.25rem]! tw:mt-1!' :
                            safeType === 'danger' ? 'bi bi-exclamation-triangle-fill tw:text-[length:1.25rem]! tw:mt-1!' :
                                safeType === 'warning' ? 'bi bi-exclamation-circle-fill tw:text-[length:1.25rem]! tw:mt-1!' :
                                    'bi bi-info-circle-fill tw:text-[length:1.25rem]! tw:mt-1!';
                }

                alertTitle.textContent =
                    safeType === 'success' ? 'Success' :
                        safeType === 'danger' ? 'Error' :
                            safeType === 'warning' ? 'Warning' : 'Info';

                alertMessage.textContent = message || '';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            function hideAlert() {
                alertBox.classList.add('tw:hidden!');
                alertMessage.textContent = '';
            }

            function hideErrorDetails() {
                if (errorDetailsCard) errorDetailsCard.classList.add('tw:hidden!');
                if (errorDetails) errorDetails.innerHTML = '';
            }

            function showErrorDetails(message) {
                if (!errorDetailsCard || !errorDetails) return;

                errorDetails.innerHTML = `
                                                    <div class="nx-alert nx-alert-red tw:mb-0!">
                                                        ${escapeHtml(message || 'Unknown error')}
                                                    </div>
                                                `;
                errorDetailsCard.classList.remove('tw:hidden!');
            }

            function setReadButtonLoading(isLoading) {
                if (!manualReadBtn) return;

                manualReadBtn.disabled = isLoading;
                manualReadBtn.innerHTML = isLoading
                    ? '<span class="ui-spinner-border ui-spinner-border-sm tw:mr-1!"></span>Processing...'
                    : '<i class="bi bi-person-check-fill tw:mr-1!"></i>Read Student';
            }

            function setMarkButtonLoading(isLoading) {
                const button = document.getElementById('markAttendanceBtn');
                if (!button) return;

                button.disabled = isLoading;
                button.innerHTML = isLoading
                    ? '<span class="ui-spinner-border ui-spinner-border-sm tw:mr-1!"></span>Marking...'
                    : '<i class="bi bi-check-circle tw:mr-1!"></i>Mark Attendance';
            }

            async function postJson(url, payload) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                });

                let result = {};
                try {
                    result = await response.json();
                } catch (error) {
                    result = { message: 'Invalid server response.' };
                }

                return { ok: response.ok, status: response.status, result };
            }

            function hasEnrollment(enrollment) {
                return Boolean(
                    enrollment && (
                        enrollment.is_enrolled === true ||
                        String(enrollment.status || '').toLowerCase() === 'enrolled' ||
                        enrollment.enrollment_id !== null
                    )
                );
            }

            function getIssueTuteValue() {
                const checkbox = document.getElementById('issueTuteCheckbox');
                return checkbox && checkbox.checked ? 1 : 0;
            }

            async function readStudent(qrCode, method = 'qr_web') {
                const code = String(qrCode || '').trim();

                currentMarkMethod = method;
                hideAlert();
                hideErrorDetails();

                if (!code) {
                    showAlert('warning', 'Please enter or scan QR code.');
                    return;
                }

                if (!classScheduleId || !studentClassId || !classCategoryFeeId) {
                    showAlert('danger', 'Class schedule, class ID or category fee ID missing.');
                    return;
                }

                if (processing) return;

                try {
                    processing = true;
                    setReadButtonLoading(true);

                    const response = await postJson(readApiUrl, {
                        qr_code: code,
                        student_class_id: studentClassId,
                        class_category_fee_id: classCategoryFeeId,
                    });

                    const result = response.result || {};
                    const isSuccess = response.ok && (result.status === 'success' || result.success === true);

                    if (!isSuccess) {
                        resetView();
                        showAlert('danger', result.message || 'Student read failed.');
                        showErrorDetails(result.message || 'Student read failed.');
                        return;
                    }

                    const payload = result.data || {};

                    currentQrCode = code;
                    currentStudent = payload.student || null;
                    currentEnrollment = pickEnrollmentFromResponse(payload);

                    renderStudent(currentStudent);
                    renderAttendanceDetails(payload);

                    hideErrorDetails();
                    showAlert('success', result.message || 'Student attendance details loaded successfully.');
                    manualQrCode.value = code;
                } catch (error) {
                    console.error(error);
                    resetView();
                    showAlert('danger', 'Something went wrong while reading student.');
                    showErrorDetails('Something went wrong while reading student.');
                } finally {
                    processing = false;
                    setReadButtonLoading(false);
                }
            }

            function renderStudent(student) {
                if (!student) {
                    resetView();
                    return;
                }

                emptyStateCard.classList.add('tw:hidden!');
                studentDetailsCard.classList.remove('tw:hidden!');
                attendanceDetailsCard.classList.remove('tw:hidden!');

                studentImage.src = student.img_url || defaultStudentImage;
                studentImage.onerror = function () {
                    this.src = defaultStudentImage;
                    this.onerror = null;
                };

                studentId.textContent = student.custom_id || student.id || '-';
                studentName.textContent = student.initial_name || '-';
                studentMobile.textContent = student.mobile || '-';
                guardianMobile.textContent = student.guardian_mobile || '-';

                qrTypeBadge.textContent = student.qr_type === 'temporary' ? 'Temporary QR' : 'Permanent QR';
                qrTypeBadge.className = student.qr_type === 'temporary' ? 'nx-badge nx-badge-amber' : 'nx-badge nx-badge-green';
            }

            function renderAttendanceDetails(data) {
                const selectedEnrollmentRaw = pickEnrollmentFromResponse(data);
                const enrollment = normalizeEnrollment(selectedEnrollmentRaw);

                const attendance = data?.attendance || {};
                const lastPayment = data?.last_payment || null;
                const tute = data?.tute || null;

                const enrollmentExists = hasEnrollment(enrollment);
                const statusLabel = enrollmentExists ? 'Enrolled' : 'New Student';
                const statusBadgeClass = enrollmentExists ? 'nx-badge nx-badge-green' : 'nx-badge nx-badge-amber';

                const studentClassIdValue = enrollment.student_class_id ?? studentClassId ?? '-';
                const feeIdValue = enrollment.class_category_fee_id ?? classCategoryFeeId ?? '-';

                const className = enrollment.class_name;
                const grade = enrollment.grade;
                const teacher = enrollment.teacher;
                const categoryName = enrollment.category_name;
                const finalFeeValue = enrollment.final_fee;
                const defaultFeeValue = enrollment.default_fee;

                const paymentHtml = lastPayment
                    ? `
                <div class="tw:font-bold!">${formatMoney(lastPayment.amount)}</div>
                <small class="tw:text-(color:--nx-muted)! tw:block!">Month: ${escapeHtml(lastPayment.payment_month)}</small>
                <small class="tw:text-bs-info! tw:block!">Paid: ${escapeHtml(lastPayment.paid_at)}</small>
            `
                    : '<span class="nx-badge nx-badge-red">No Payment Yet</span>';

                const tuteHtml = tute?.is_issued
                    ? '<span class="nx-badge nx-badge-blue">Tute issued</span>'
                    : '';

                attendanceDetailsContent.innerHTML = `
            <div class="attendance-detail-card">
                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                        <div class="info-card">
                            <small>Status</small>
                            <div class="tw:mt-2!">
                                <span class="${statusBadgeClass}">${statusLabel}</span>
                            </div>
                        </div>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                        <div class="info-card">
                            <small>Class</small>
                            <h6>${escapeHtml(className)}</h6>
                            <span class="tw:text-(color:--nx-muted)!">Class ID: ${escapeHtml(studentClassIdValue)}</span>
                        </div>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                        <div class="info-card">
                            <small>Grade</small>
                            <h6>${escapeHtml(grade)}</h6>
                        </div>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <div class="info-card">
                            <small>Teacher</small>
                            <h6>${escapeHtml(teacher)}</h6>
                        </div>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                        <div class="info-card">
                            <small>Category</small>
                            <h6>${escapeHtml(categoryName)}</h6>
                            <span class="tw:text-(color:--nx-muted)!">Fee ID: ${escapeHtml(feeIdValue)}</span>
                        </div>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                        <div class="info-card">
                            <small>Final Fee</small>
                            <h6>${formatMoney(finalFeeValue)}</h6>
                            <span class="tw:text-(color:--nx-muted)!">Default: ${formatMoney(defaultFeeValue)}</span>
                        </div>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                        <div class="info-card">
                            <small>Last Payment</small>
                            ${paymentHtml}
                        </div>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                        <div class="info-card">
                            <small>Attendance</small>
                            <h6>${attendance.attended_classes ?? 0} / ${attendance.total_classes ?? 0}</h6>
                            <span class="tw:text-(color:--nx-muted)!">
                                ${attendance.attendance_percentage ?? 0}%
                                ${attendance.month ? '(' + escapeHtml(attendance.month) + ')' : ''}
                            </span>
                            ${tuteHtml ? `<div class="tw:mt-2!">${tuteHtml}</div>` : ''}
                        </div>
                    </div>
                </div>

                <div class="tw:flex! tw:justify-end! tw:gap-2! tw:flex-wrap! tw:mt-6!">
                    ${enrollmentExists ? `
                        <div class="ui-form-check tw:flex! tw:items-center! tw:gap-2! tw:mr-2!">
                            <input
                                class="ui-form-check-input"
                                type="checkbox"
                                id="issueTuteCheckbox"
                                ${tute?.is_issued ? 'checked' : ''}
                            >
                            <label class="ui-form-check-label" for="issueTuteCheckbox">
                                Issue Tute
                            </label>
                        </div>
                    ` : ''}

                    <button type="button" class="nx-btn nx-btn-primary" id="addClassBtn">
                        <i class="bi bi-plus-circle tw:mr-1!"></i>
                        Add Class
                    </button>

                    <button type="button" class="nx-btn nx-btn-success" id="markAttendanceBtn">
                        <i class="bi bi-check-circle tw:mr-1!"></i>
                        Mark Attendance
                    </button>
                </div>
            </div>
        `;
            }

            function pickEnrollmentFromResponse(data) {
                // The read API returns this class's enrollment as "enrollment";
                // older responses had an "enrollments" list.
                const enrollments = Array.isArray(data?.enrollments)
                    ? data.enrollments
                    : (data?.enrollment ? [data.enrollment] : []);

                if (!enrollments.length) {
                    return {};
                }

                const matched = enrollments.find((item) => {
                    const itemStudentClassId = Number(item?.student_class_id ?? item?.studentClass?.id ?? 0);
                    const itemFeeId = Number(item?.class_category_fee_id ?? item?.classCategoryFee?.id ?? 0);

                    return (
                        itemStudentClassId === Number(studentClassId) &&
                        itemFeeId === Number(classCategoryFeeId)
                    );
                });

                return matched || enrollments[0] || {};
            }

            function normalizeEnrollment(enrollment) {
                const e = enrollment || {};

                return {
                    status: String(e.status || e.enrollment_status || '').toLowerCase(),
                    is_enrolled:
                        e.is_enrolled === true ||
                        e.is_enrolled === 1 ||
                        String(e.status || '').toLowerCase() === 'enrolled' ||
                        !!e.enrollment_id,
                    enrollment_id: e.enrollment_id ?? e.id ?? null,
                    student_class_id: e.student_class_id ?? e.studentClass?.id ?? null,
                    class_category_fee_id: e.class_category_fee_id ?? e.classCategoryFee?.id ?? null,
                    class_name: e.class_name ?? e.studentClass?.class_name ?? e.studentClass?.name ?? '-',
                    grade: e.grade ?? e.grade_name ?? e.studentClass?.grade?.grade_name ?? e.studentClass?.grade?.name ?? '-',
                    teacher: e.teacher ?? e.teacher_name ?? e.studentClass?.teacher?.name ?? '-',
                    category_name: e.category_name ?? e.classCategoryFee?.category?.category_name ?? e.classCategoryFee?.category?.name ?? '-',
                    default_fee: e.default_fee ?? e.classCategoryFee?.default_fee ?? '-',
                    final_fee: e.final_fee ?? e.classCategoryFee?.final_fee ?? e.default_fee ?? '-',
                };
            }

            async function markAttendance() {
                if (!currentQrCode) {
                    showAlert('warning', 'Please read a student first.');
                    return;
                }

                if (!classScheduleId) {
                    showAlert('danger', 'Class schedule ID is missing.');
                    return;
                }

                if (!currentStudent?.id) {
                    showAlert('danger', 'Student ID is missing.');
                    return;
                }

                const issueTute = getIssueTuteValue();
                const enrollmentExists = hasEnrollment(currentEnrollment);

                if (issueTute && !enrollmentExists) {
                    showAlert('warning', 'Student must be enrolled before issuing a tute.');
                    return;
                }

                try {
                    setMarkButtonLoading(true);

                    const response = await postJson(storeAttendanceApiUrl, {
                        student_id: currentStudent.id,
                        qr_code: currentQrCode,
                        class_schedule_id: classScheduleId,
                        student_class_id: studentClassId,
                        class_category_fee_id: classCategoryFeeId,
                        mark_method: currentMarkMethod,
                        mark_tute: issueTute,
                        note: null,
                    });

                    const result = response.result || {};
                    const isSuccess = response.ok && (result.status === 'success' || result.success === true);

                    if (!isSuccess) {
                        const validationMessage =
                            result.message ||
                            (result.errors ? Object.values(result.errors).flat().join(' ') : 'Attendance mark failed.');

                        showAlert('danger', validationMessage);
                        return;
                    }

                    showAlert('success', result.message || 'Attendance marked successfully.');
                    await readStudent(currentQrCode, currentMarkMethod);
                } catch (error) {
                    console.error(error);
                    showAlert('danger', 'Something went wrong while marking attendance.');
                } finally {
                    setMarkButtonLoading(false);
                }
            }

            function openAddClassModal() {
                if (!currentStudent) {
                    showAlert('warning', 'Please read a student first.');
                    return;
                }

                modalStudentName.textContent = currentStudent.initial_name || currentStudent.custom_id || '-';
                modalClassName.textContent = currentEnrollment?.class_name || '-';
                modalCategoryName.textContent = currentEnrollment?.category_name || '-';

                modalIsFreeCard.value = '0';
                // the default option (first in the list)
                if (modalFeeOption && modalFeeOption.options.length) modalFeeOption.selectedIndex = 0;
                modalEnrolledAt.value = new Date().toISOString().slice(0, 10);

                addClassModal?.show();
            }

            async function saveClassEnrollment() {
                if (!currentStudent) {
                    showAlert('warning', 'Please read a student first.');
                    return;
                }

                if (!modalFeeOption || !modalFeeOption.value) {
                    showAlert('warning', 'This class category has no active fee option. Add one first.');
                    return;
                }

                try {
                    saveClassBtn.disabled = true;
                    saveClassBtn.innerHTML = '<span class="ui-spinner-border ui-spinner-border-sm tw:mr-1!"></span>Saving...';

                    const payload = {
                        student_id: currentStudent.id,
                        student_class_id: studentClassId,
                        class_category_fee_id: classCategoryFeeId,
                        class_category_fee_option_id: modalFeeOption && modalFeeOption.value ? Number(modalFeeOption.value) : null,
                        is_free_card: modalIsFreeCard.value === '1' ? 1 : 0,
                        enrolled_at: modalEnrolledAt.value || null,
                        note: null,
                    };

                    const response = await postJson(storeEnrollmentApiUrl, payload);
                    const result = response.result || {};
                    const isSuccess = response.ok && (result.status === 'success' || result.success === true);

                    if (!isSuccess) {
                        showAlert('danger', result.message || 'Student class enrollment failed.');
                        return;
                    }

                    addClassModal?.hide();
                    showAlert('success', 'Student enrolled successfully.');

                    setTimeout(async () => {
                        if (currentQrCode) {
                            await readStudent(currentQrCode, currentMarkMethod);
                        }
                    }, 350);
                } catch (error) {
                    console.error(error);
                    showAlert('danger', 'Something went wrong while saving class.');
                } finally {
                    saveClassBtn.disabled = false;
                    saveClassBtn.innerHTML = 'Save Class';
                }
            }

            function resetView() {
                currentQrCode = null;
                currentStudent = null;
                currentEnrollment = null;

                emptyStateCard.classList.remove('tw:hidden!');
                studentDetailsCard.classList.add('tw:hidden!');
                attendanceDetailsCard.classList.add('tw:hidden!');

                if (attendanceDetailsContent) {
                    attendanceDetailsContent.innerHTML = '';
                }

                hideErrorDetails();
            }

            function formatMoney(value) {
                if (value === null || value === undefined || value === '') return '-';

                const amount = Number(value);
                if (Number.isNaN(amount)) return '-';

                return 'Rs. ' + amount.toLocaleString('en-LK', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function escapeHtml(value) {
                if (value === null || value === undefined || value === '') return '-';

                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            async function startScanner() {
                if (scannerRunning) return;

                if (typeof Html5Qrcode === 'undefined') {
                    showAlert('danger', 'QR scanner library failed to load.');
                    return;
                }

                try {
                    html5QrCode = new Html5Qrcode('qr-reader');

                    startScannerBtn.disabled = true;
                    startScannerBtn.innerHTML = '<span class="ui-spinner-border ui-spinner-border-sm tw:mr-1!"></span>Starting Scanner...';

                    await html5QrCode.start(
                        { facingMode: 'environment' },
                        {
                            fps: 10,
                            qrbox: 250
                        },
                        function (decodedText) {
                            const code = String(decodedText || '').trim();
                            const now = Date.now();

                            if (!code) return;

                            if (code === lastScannedCode && now - lastScanTime < SCAN_COOLDOWN_MS) {
                                return;
                            }

                            lastScannedCode = code;
                            lastScanTime = now;

                            readStudent(code, 'qr_web');
                        }
                    );

                    scannerRunning = true;
                    startScannerBtn.innerHTML = '<i class="bi bi-camera-video-fill tw:mr-1!"></i>Scanner Running';
                    startScannerBtn.disabled = true;
                    stopScannerBtn.classList.remove('tw:hidden!');
                } catch (error) {
                    console.error(error);
                    showAlert('danger', 'Could not start scanner.');
                    startScannerBtn.disabled = false;
                    startScannerBtn.innerHTML = '<i class="bi bi-camera-fill tw:mr-1!"></i>Start Scanner';
                }
            }

            async function stopScanner() {
                if (html5QrCode && scannerRunning) {
                    await html5QrCode.stop();
                    await html5QrCode.clear();
                }

                scannerRunning = false;
                html5QrCode = null;
                lastScannedCode = null;
                lastScanTime = 0;

                startScannerBtn.disabled = false;
                startScannerBtn.innerHTML = '<i class="bi bi-camera-fill tw:mr-1!"></i>Start Scanner';
                stopScannerBtn.classList.add('tw:hidden!');
            }

            manualQrForm.addEventListener('submit', function (event) {
                event.preventDefault();
                readStudent(manualQrCode.value, 'manual_web');
            });

            addClassForm.addEventListener('submit', function (event) {
                event.preventDefault();
                saveClassEnrollment();
            });

            startScannerBtn.addEventListener('click', startScanner);
            stopScannerBtn.addEventListener('click', stopScanner);

            attendanceDetailsContent.addEventListener('click', function (event) {
                if (event.target.closest('#addClassBtn')) {
                    openAddClassModal();
                }

                if (event.target.closest('#markAttendanceBtn')) {
                    markAttendance();
                }
            });

            window.addEventListener('beforeunload', function () {
                if (html5QrCode && scannerRunning) {
                    html5QrCode.stop().catch(function () { });
                }
            });
        });
    </script>
@endpush
