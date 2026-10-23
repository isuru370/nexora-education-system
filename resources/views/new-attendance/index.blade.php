@extends('layouts.app')

@section('title', 'Attendance')
@section('page-title', 'Attendance')

@section('content')
    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Attendance</h1>
                    <p class="hero-subtitle">Scan student QR or enter Student ID to mark attendance.</p>
                </div>
                <div class="hero-right">
                    <span id="scannerStatus" class="nx-badge nx-badge-gray">Starting scanner...</span>
                </div>
            </div>
        </div>

        {{-- SCANNER + MANUAL --}}
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-5">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-qr-code-scan text-primary"></i> QR Scanner</h5>
                    </div>
                    <div class="p-3">
                        <div id="qr-reader" class="qr-reader-box"></div>
                        <div id="scannerMessage" class="scanner-message">Point the camera at the student's QR code.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8 col-md-7">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-keyboard text-primary"></i> Manual Attendance</h5>
                    </div>
                    <div class="p-3">
                        <form id="manualAttendanceForm" autocomplete="off">
                            <label for="manualCode" class="nx-label">Student ID / QR Code</label>
                            <div class="nx-input-group mb-2">
                                <input type="text" id="manualCode" class="form-control nx-input"
                                    placeholder="Enter Student ID" maxlength="255">
                                <button type="submit" id="manualSubmitBtn" class="nx-btn nx-btn-primary">
                                    <i class="bi bi-check-lg"></i> Mark
                                </button>
                            </div>
                            <small class="cell-sub">Enter the Student ID or QR code.</small>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ERROR --}}
        <div id="errorResult" class="nx-alert nx-alert-red mb-4 d-none" role="alert"></div>

        {{-- Shown when the student has more than one class open now --}}
        <div id="classChooser" class="mb-4 d-none"></div>

        {{-- EMPTY STATE --}}
        <div id="emptyState" class="nx-card">
            <div class="empty-state-box">
                <i class="bi bi-person-badge empty-icon"></i>
                <h5 class="empty-title">Waiting for Student</h5>
                <p class="cell-sub mb-0">Scan a QR code or enter a Student ID.</p>
            </div>
        </div>

        {{-- STUDENT RESULT --}}
        <div id="studentResult" class="d-none">
            <div class="nx-card mb-4">
                <div class="p-3">
                    <div class="row align-items-center g-3">
                        <div class="col-auto">
                            <img id="studentImage" src="" alt="Student" class="student-photo d-none">
                            <div id="studentImagePlaceholder" class="student-photo-placeholder"><i class="bi bi-person"></i></div>
                        </div>
                        <div class="col">
                            <h4 id="studentName" class="student-name mb-1">-</h4>
                            <div class="cell-sub">ID: <strong id="studentCustomId">-</strong></div>
                            <div class="cell-sub">Grade: <span id="studentGrade">-</span></div>
                        </div>
                        <div class="col-auto">
                            <span id="attendanceBadge" class="nx-badge nx-badge-green attendance-badge">PRESENT</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="nx-card h-100">
                        <div class="nx-card-header">
                            <h5 class="nx-card-title"><i class="bi bi-journal-bookmark-fill text-primary"></i> Class Information</h5>
                        </div>
                        <div class="p-3">
                            <div class="row g-2">
                                <div class="col-12"><div class="info-item"><small>Class</small><div id="className">-</div></div></div>
                                <div class="col-6"><div class="info-item"><small>Category</small><div id="classCategory">-</div></div></div>
                                <div class="col-6"><div class="info-item"><small><i class="bi bi-building me-1"></i>Branch</small><div id="classBranch">-</div></div></div>
                                <div class="col-12"><div class="info-item"><small><i class="bi bi-calendar3 me-1"></i>Date</small><div id="classDate">-</div></div></div>
                                <div class="col-6"><div class="info-item"><small><i class="bi bi-clock me-1"></i>Start</small><div id="startTime">-</div></div></div>
                                <div class="col-6"><div class="info-item"><small><i class="bi bi-clock me-1"></i>End</small><div id="endTime">-</div></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="nx-card h-100">
                        <div class="nx-card-header">
                            <h5 class="nx-card-title"><i class="bi bi-graph-up-arrow text-primary"></i> This Month Attendance</h5>
                        </div>
                        <div class="p-3">
                            <div class="row g-2">
                                <div class="col-6"><div class="attendance-stat"><div class="stat-icon stat-primary"><i class="bi bi-calendar-event"></i></div><div class="stat-info"><span class="stat-label">Class Days</span><h3 id="classDays" class="stat-number">0</h3></div></div></div>
                                <div class="col-6"><div class="attendance-stat"><div class="stat-icon stat-success"><i class="bi bi-check-circle"></i></div><div class="stat-info"><span class="stat-label">Attended</span><h3 id="attendedDays" class="stat-number text-success">0</h3></div></div></div>
                                <div class="col-6"><div class="attendance-stat"><div class="stat-icon stat-danger"><i class="bi bi-x-circle"></i></div><div class="stat-info"><span class="stat-label">Absent</span><h3 id="absentDays" class="stat-number text-danger">0</h3></div></div></div>
                                <div class="col-6"><div class="attendance-stat"><div class="stat-icon stat-warning"><i class="bi bi-percent"></i></div><div class="stat-info"><span class="stat-label">Percentage</span><h3 id="attendancePercentage" class="stat-number text-primary">0%</h3></div></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <h5 class="nx-card-title"><i class="bi bi-cash-stack text-primary"></i> Last Payment</h5>
                </div>
                <div class="p-3">
                    <div id="paymentEmpty" class="cell-sub">No payment information.</div>
                    <div id="paymentDetails" class="d-none">
                        <div class="row g-3">
                            <div class="col-md-3 col-6"><small class="cell-sub">Amount</small><div id="paymentAmount" class="fw-semibold">-</div></div>
                            <div class="col-md-3 col-6"><small class="cell-sub">Payment Month</small><div id="paymentMonth" class="fw-semibold">-</div></div>
                            <div class="col-md-3 col-6"><small class="cell-sub">Payment Method</small><div id="paymentMethod" class="fw-semibold text-capitalize">-</div></div>
                            <div class="col-md-3 col-6"><small class="cell-sub">Receipt</small><div id="paymentReceipt" class="fw-semibold">-</div></div>
                        </div>
                        <hr class="my-3">
                        <small class="cell-sub">Paid At</small>
                        <div id="paymentPaidAt" class="fw-semibold">-</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Attendance (2) — page-specific styles only.
       ============================================================ */

    .dashboard-page .nx-input-group {
        display: flex;
        align-items: stretch;
    }
    .dashboard-page .nx-input-group .nx-input {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    .dashboard-page .nx-input-group .nx-btn {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: 0;
        margin-left: -1px;
    }

    .dashboard-page .qr-reader-box {
        width: 100%;
        max-width: 360px;
        margin: 0 auto;
        border-radius: var(--nx-radius);
        overflow: hidden;
        background: #f4f6fa;
        border: 1px dashed #cbd5e1;
        min-height: 240px;
    }
    .dashboard-page #qr-reader video {
        width: 100% !important;
        max-height: 230px;
        object-fit: cover;
        border-radius: var(--nx-radius);
    }

    .dashboard-page .scanner-message {
        text-align: center;
        color: var(--nx-muted);
        margin-top: .75rem;
        font-size: .78rem;
    }

    .dashboard-page .empty-state-box { text-align: center; padding: 3rem 1.5rem; }
    .dashboard-page .empty-icon {
        display: block;
        font-size: 50px;
        color: var(--nx-muted);
        margin-bottom: .75rem;
    }
    .dashboard-page .empty-title {
        font-weight: 700;
        font-size: 1rem;
        color: var(--nx-text);
        margin-bottom: .5rem;
    }

    .dashboard-page .student-photo,
    .dashboard-page .student-photo-placeholder {
        width: 90px; height: 90px; border-radius: 50%;
    }
    .dashboard-page .student-photo { object-fit: cover; }
    .dashboard-page .student-photo-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: var(--nx-muted);
        font-size: 2.5rem;
    }
    .dashboard-page .student-name {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--nx-text);
    }
    .dashboard-page .attendance-badge {
        font-size: .8rem;
        padding: .4rem .8rem;
    }

    .dashboard-page .info-item {
        height: 100%;
        padding: .55rem .75rem;
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
    }
    .dashboard-page .info-item small {
        display: block;
        font-size: .65rem;
        font-weight: 600;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .dashboard-page .info-item div {
        margin-top: .15rem;
        font-size: .88rem;
        font-weight: 700;
        color: var(--nx-text);
    }

    .dashboard-page .attendance-stat {
        display: flex;
        align-items: center;
        gap: .75rem;
        height: 100%;
        padding: .75rem;
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
    }
    .dashboard-page .stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 40px; height: 40px;
        border-radius: 10px;
        font-size: 1.1rem;
    }
    .dashboard-page .stat-primary { background: var(--nx-blue-soft); color: var(--nx-blue); }
    .dashboard-page .stat-success { background: var(--nx-green-soft); color: var(--nx-green); }
    .dashboard-page .stat-danger  { background: var(--nx-red-soft); color: var(--nx-red); }
    .dashboard-page .stat-warning { background: var(--nx-amber-soft); color: var(--nx-amber); }
    .dashboard-page .stat-info { flex: 1; min-width: 0; }
    .dashboard-page .stat-label {
        display: block;
        font-size: .65rem;
        font-weight: 600;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .dashboard-page .stat-number {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 800;
        line-height: 1.2;
        color: var(--nx-text);
    }

    .dashboard-page .badge.bg-success { background: var(--nx-green) !important; }
    .dashboard-page .badge.bg-warning { background: var(--nx-amber) !important; color: #fff !important; }
    .dashboard-page .badge.bg-danger  { background: var(--nx-red) !important; }
    .dashboard-page .badge.bg-secondary { background: var(--nx-muted) !important; }

    .dashboard-page .text-success { color: var(--nx-green) !important; }
    .dashboard-page .text-danger  { color: var(--nx-red) !important; }
    .dashboard-page .text-primary { color: var(--nx-blue) !important; }

    @media (max-width: 768px) {
        .dashboard-page .qr-reader-box { max-width: 280px; }
        .dashboard-page #qr-reader video { max-height: 180px; }
        .dashboard-page .stat-number { font-size: 1.1rem; }
    }
    @media (max-width: 576px) {
        .dashboard-page .qr-reader-box { max-width: 220px; }
        .dashboard-page #qr-reader video { max-height: 140px; }
        .dashboard-page .attendance-stat { padding: .5rem .6rem; }
        .dashboard-page .stat-icon { width: 32px; height: 32px; font-size: .9rem; border-radius: 8px; }
        .dashboard-page .stat-number { font-size: .95rem; }
        .dashboard-page .stat-label { font-size: .55rem; }
    }
</style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <script>
        (function() {
            'use strict';

            /* ---------------------------------------------------------------
             | Config
             * ------------------------------------------------------------- */

            const SCAN_URL = @json(route('new-attendance.scan'));
            const CSRF_TOKEN = @json(csrf_token());
            const SCAN_COOLDOWN_MS = 2500;

            /* ---------------------------------------------------------------
             | Elements
             * ------------------------------------------------------------- */

            const el = {
                scannerStatus: document.getElementById('scannerStatus'),
                scannerMessage: document.getElementById('scannerMessage'),
                manualForm: document.getElementById('manualAttendanceForm'),
                manualCode: document.getElementById('manualCode'),
                manualSubmitBtn: document.getElementById('manualSubmitBtn'),
                error: document.getElementById('errorResult'),
                emptyState: document.getElementById('emptyState'),
                studentResult: document.getElementById('studentResult'),
                studentImage: document.getElementById('studentImage'),
                studentImagePlaceholder: document.getElementById('studentImagePlaceholder'),
                attendanceBadge: document.getElementById('attendanceBadge'),
                paymentEmpty: document.getElementById('paymentEmpty'),
                paymentDetails: document.getElementById('paymentDetails'),
                classChooser: document.getElementById('classChooser'),
            };

            /* ---------------------------------------------------------------
             | State
             * ------------------------------------------------------------- */

            let qrScanner = null;
            let isProcessing = false;
            let lastScannedCode = null;
            let lastScanTime = 0;
            let awaitingChoice = false; // "choose the class" panel is open

            /* ---------------------------------------------------------------
             | Scanner
             * ------------------------------------------------------------- */

            async function startScanner() {
                if (typeof Html5Qrcode === 'undefined') {
                    setScannerStatus(false);
                    showError('QR scanner library could not be loaded.');
                    return;
                }

                try {
                    qrScanner = new Html5Qrcode('qr-reader');

                    await qrScanner.start({
                            facingMode: 'environment'
                        }, {
                            fps: 10,
                            qrbox: {
                                width: 180,
                                height: 180
                            }
                        },
                        handleQrScan,
                        () => {} // Frame-level "no QR found" errors are normal.
                    );

                    setScannerStatus(true);
                    setScannerMessage("Point the camera at the student's QR code.");
                } catch (error) {
                    qrScanner = null;
                    setScannerStatus(false);
                    showError('Unable to start camera. Please check camera permission.');
                }
            }

            function stopScanner() {
                if (qrScanner && qrScanner.isScanning) {
                    qrScanner.stop().catch(() => {});
                }
            }

            async function handleQrScan(decodedText) {
                const code = String(decodedText || '').trim();
                const now = Date.now();

                if (!code || isProcessing || awaitingChoice) {
                    return;
                }

                if (code === lastScannedCode && now - lastScanTime < SCAN_COOLDOWN_MS) {
                    return;
                }

                lastScannedCode = code;
                lastScanTime = now;

                await runAttendance(code, 'qr_web', 'Processing attendance...');
            }

            /* ---------------------------------------------------------------
             | Manual Attendance
             * ------------------------------------------------------------- */

            el.manualForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                const code = el.manualCode.value.trim();

                if (!code) {
                    showError('Please enter a Student ID or QR code.');
                    el.manualCode.focus();
                    return;
                }

                await runAttendance(code, 'manual_web', 'Processing manual attendance...');

                el.manualCode.value = '';
                el.manualCode.focus();
            });

            /* ---------------------------------------------------------------
             | Attendance Request
             * ------------------------------------------------------------- */

            async function runAttendance(code, markMethod, busyMessage, scheduleId = null) {
                if (isProcessing) {
                    return;
                }

                isProcessing = true;
                el.manualSubmitBtn.disabled = true;
                setScannerMessage(busyMessage);
                hideError();
                hideClassChooser();

                try {
                    const result = await postScan(code, markMethod, scheduleId);

                    if (result.data && result.data.requires_class_selection) {
                        renderClassChooser(code, markMethod, result);
                    } else if (result.success === true) {
                        renderStudent(result.data);
                    } else {
                        renderError(result);
                    }
                } catch (error) {
                    showError(error.message || 'Unable to connect to the server.');
                } finally {
                    isProcessing = false;
                    el.manualSubmitBtn.disabled = false;
                    if (!awaitingChoice) {
                        setScannerMessage('Ready for next student...');
                    }
                }
            }

            async function postScan(code, markMethod, scheduleId = null) {
                const response = await fetch(SCAN_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({
                        code: code,
                        mark_method: markMethod,
                        schedule_id: scheduleId,
                    }),
                });

                if (response.status === 419) {
                    throw new Error('Your session has expired. Please refresh the page.');
                }

                let result;

                try {
                    result = await response.json();
                } catch (error) {
                    throw new Error('Server returned an invalid response.');
                }

                // Laravel validation errors (422) return { message, errors }.
                if (result.errors && typeof result.success === 'undefined') {
                    return {
                        success: false,
                        message: firstValidationError(result),
                        data: null
                    };
                }

                return result;
            }

            function firstValidationError(result) {
                const messages = Object.values(result.errors || {})[0];

                return (messages && messages[0]) || result.message || 'Invalid request.';
            }

            /* ---------------------------------------------------------------
             | Render: Success
             * ------------------------------------------------------------- */

            function renderStudent(data) {
                if (!data) {
                    showError('No attendance data received.');
                    return;
                }

                const student = data.student || {};
                const schedule = data.schedule || {};
                const classInfo = schedule.class || {};
                const attendance = data.attendance || {};
                const summary = data.attendance_summary || {};

                el.emptyState.classList.add('d-none');
                el.studentResult.classList.remove('d-none');

                // Student
                setText('studentName', student.initial_name || student.full_name);
                setText('studentCustomId', student.custom_id || student.student_code);
                setText('studentGrade', gradeName(student));
                setStudentImage(student.img_url);

                // Class
                setText('className', classInfo.class_name);
                setText('classCategory', classInfo.category);
                setText('classBranch', schedule.branch ? schedule.branch.name : '');
                setText('classDate', schedule.class_date);
                setText('startTime', schedule.start_time);
                setText('endTime', schedule.end_time);

                // Attendance badge
                if (attendance.already_marked === true) {
                    el.attendanceBadge.textContent = 'ALREADY PRESENT';
                    el.attendanceBadge.className = 'nx-badge nx-badge-amber attendance-badge';
                } else {
                    el.attendanceBadge.textContent = 'PRESENT';
                    el.attendanceBadge.className = 'nx-badge nx-badge-green attendance-badge';
                }

                // This month
                setText('classDays', summary.total_count ?? 0);
                setText('attendedDays', summary.present_count ?? 0);
                setText('absentDays', summary.absent_count ?? 0);
                setText('attendancePercentage', (summary.attendance_percentage ?? 0) + '%');

                renderPayment(data.last_payment);
            }

            function renderPayment(payment) {
                el.paymentEmpty.classList.toggle('d-none', Boolean(payment));
                el.paymentDetails.classList.toggle('d-none', !payment);

                if (!payment) {
                    return;
                }

                setText('paymentAmount', 'Rs. ' + formatAmount(payment.amount));
                setText('paymentMonth', payment.payment_month);
                setText('paymentMethod', payment.payment_method);
                setText('paymentReceipt', payment.receipt_number);
                setText('paymentPaidAt', payment.paid_at);
            }

            function setStudentImage(url) {
                const hasImage = Boolean(url);

                if (hasImage) {
                    el.studentImage.src = url;
                } else {
                    el.studentImage.removeAttribute('src');
                }

                el.studentImage.classList.toggle('d-none', !hasImage);
                el.studentImagePlaceholder.classList.toggle('d-none', hasImage);
            }

            // Show placeholder if the image URL is broken.
            el.studentImage.addEventListener('error', function() {
                setStudentImage('');
            });

            /* ---------------------------------------------------------------
             | Render: Choose the class
             |
             | The student has more than one class open now (e.g. A 4:30-6:00
             | and B 6:00-8:00 at 5:30 PM). Nothing was marked - the user picks
             | the class and the scan is sent again with its schedule_id.
             * ------------------------------------------------------------- */

            function renderClassChooser(code, markMethod, result) {
                const student = result.data.student || {};
                const classes = result.data.classes || [];

                awaitingChoice = true;
                setScannerMessage('Choose the class for this student.');

                const buttons = classes.map(function(item) {
                    const info = item.class || {};
                    const when = formatTime(item.start_time) + ' - ' + formatTime(item.end_time);
                    const state = item.already_marked
                        ? '<span class="nx-badge nx-badge-amber ms-2">Already marked</span>'
                        : (item.is_started
                            ? '<span class="nx-badge nx-badge-green ms-2">In progress</span>'
                            : '<span class="nx-badge nx-badge-blue ms-2">Starts ' + escapeHtml(formatTime(item.start_time)) + '</span>');

                    return '<button type="button" class="nx-btn nx-btn-light w-100 text-start mb-2 class-choice" style="display:block;width:100%;text-align:left" data-schedule="' + Number(item.schedule_id) + '">'
                        + '<div class="fw-semibold">' + escapeHtml(info.class_name || '-')
                        + (info.category ? ' <span class="cell-sub">(' + escapeHtml(info.category) + ')</span>' : '') + state + '</div>'
                        + '<div class="cell-sub">' + escapeHtml(when)
                        + (item.hall ? ' &middot; ' + escapeHtml(item.hall) : '')
                        + (info.teacher ? ' &middot; ' + escapeHtml(info.teacher) : '') + '</div>'
                        + '</button>';
                }).join('');

                el.classChooser.innerHTML = '<div class="nx-alert nx-alert-blue"><div style="width:100%">'
                    + '<div class="mb-2"><strong>' + escapeHtml(result.message || 'Choose the class.') + '</strong></div>'
                    + '<div class="cell-sub mb-3">' + escapeHtml(student.initial_name || student.full_name || '-')
                    + ' &middot; ' + escapeHtml(student.custom_id || '-') + '</div>'
                    + buttons
                    + '<button type="button" class="nx-btn nx-btn-light w-100 mt-1" id="classChooserCancel">Cancel</button>'
                    + '</div></div>';

                el.classChooser.classList.remove('d-none');
                el.classChooser.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                el.classChooser.querySelectorAll('.class-choice').forEach(function(button) {
                    button.addEventListener('click', function() {
                        awaitingChoice = false;
                        runAttendance(code, markMethod, 'Marking attendance...', Number(button.dataset.schedule));
                    });
                });

                document.getElementById('classChooserCancel').addEventListener('click', function() {
                    awaitingChoice = false;
                    hideClassChooser();
                    setScannerMessage('Ready for next student...');
                });
            }

            function hideClassChooser() {
                el.classChooser.classList.add('d-none');
                el.classChooser.innerHTML = '';
            }

            // "16:30:00" -> "4:30 PM"
            function formatTime(value) {
                const match = String(value || '').match(/^(\d{1,2}):(\d{2})/);

                if (!match) {
                    return value || '-';
                }

                const hour = Number(match[1]);
                return ((hour % 12) || 12) + ':' + match[2] + ' ' + (hour < 12 ? 'AM' : 'PM');
            }

            /* ---------------------------------------------------------------
             | Render: Error
             * ------------------------------------------------------------- */

            function renderError(result) {
                const message = result.message || 'Attendance could not be processed.';
                const student = result.data && result.data.student;

                if (!student) {
                    showError(message);
                    return;
                }

                showError(message, `
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <div class="bg-light px-3 py-1 rounded">
                            <strong>Student:</strong> ${escapeHtml(student.initial_name || student.full_name || '-')}
                        </div>
                        <div class="bg-light px-3 py-1 rounded">
                            <strong>ID:</strong> ${escapeHtml(student.custom_id || student.student_code || '-')}
                        </div>
                        <div class="bg-light px-3 py-1 rounded">
                            <strong>Grade:</strong> ${escapeHtml(gradeName(student))}
                        </div>
                    </div>
                `);
            }

            /**
             * @param {string} message      Plain text (escaped here).
             * @param {string} [extraHtml]  Already-escaped HTML.
             */
            function showError(message, extraHtml = '') {
                el.error.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>${escapeHtml(message)}</strong>
                    </div>
                    ${extraHtml}
                `;

                el.error.classList.remove('d-none');
                el.error.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }

            function hideError() {
                el.error.classList.add('d-none');
                el.error.innerHTML = '';
            }

            /* ---------------------------------------------------------------
             | Scanner UI
             * ------------------------------------------------------------- */

            function setScannerStatus(active) {
                el.scannerStatus.textContent = active ? 'Scanner Active' : 'Scanner Inactive';
                el.scannerStatus.className = active ? 'nx-badge nx-badge-green' : 'nx-badge nx-badge-red';
            }

            function setScannerMessage(message) {
                el.scannerMessage.textContent = message;
            }

            /* ---------------------------------------------------------------
             | Helpers
             * ------------------------------------------------------------- */

            function setText(id, value) {
                const element = document.getElementById(id);

                if (element) {
                    element.textContent = value === null || value === undefined || value === '' ? '-' : value;
                }
            }

            function gradeName(student) {
                if (student.grade && typeof student.grade === 'object') {
                    return student.grade.grade_name || '-';
                }

                return student.grade || '-';
            }

            function formatAmount(amount) {
                return Number(amount || 0).toLocaleString('en-LK', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            /* ---------------------------------------------------------------
             | Start
             * ------------------------------------------------------------- */

            startScanner();
            window.addEventListener('pagehide', stopScanner);
        })();
    </script>
@endpush
