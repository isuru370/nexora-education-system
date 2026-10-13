@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
    <div class="container-fluid py-4">

        {{-- ===================================================== --}}
        {{-- Header --}}
        {{-- ===================================================== --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Attendance</h3>
                <p class="text-muted mb-0">Scan student QR or enter Student ID</p>
            </div>

            <span id="scannerStatus" class="badge bg-secondary">Starting scanner...</span>
        </div>

        {{-- ===================================================== --}}
        {{-- Scanner + Manual Entry --}}
        {{-- ===================================================== --}}
        <div class="row g-3 mb-3">

            {{-- QR Scanner --}}
            <div class="col-lg-4 col-md-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-qr-code-scan me-2"></i>QR Scanner
                        </h6>
                    </div>

                    <div class="card-body p-2">
                        <div id="qr-reader"></div>

                        <div id="scannerMessage" class="text-center text-muted mt-2 small">
                            Point the camera at the student's QR code.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Manual Attendance --}}
            <div class="col-lg-8 col-md-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-keyboard me-2"></i>Manual Attendance
                        </h6>
                    </div>

                    <div class="card-body">
                        <form id="manualAttendanceForm" autocomplete="off">
                            <label for="manualCode" class="form-label small mb-1">
                                Student ID / QR Code
                            </label>

                            <div class="input-group">
                                <input type="text" id="manualCode" class="form-control" placeholder="Enter Student ID"
                                    maxlength="255">

                                <button type="submit" id="manualSubmitBtn" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Mark
                                </button>
                            </div>

                            <small class="text-muted">Enter the Student ID or QR code.</small>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- Error --}}
        {{-- ===================================================== --}}
        <div id="errorResult" class="alert alert-danger shadow-sm border-0 mb-3 d-none" role="alert"></div>

        {{-- ===================================================== --}}
        {{-- Empty State --}}
        {{-- ===================================================== --}}
        <div id="emptyState" class="card shadow-sm border-0">
            <div class="card-body text-center py-4">
                <i class="bi bi-person-badge empty-icon mb-2"></i>
                <h6 class="text-muted mb-1">Waiting for Student</h6>
                <p class="text-muted mb-0 small">Scan a QR code or enter a Student ID.</p>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- Student Result --}}
        {{-- ===================================================== --}}
        <div id="studentResult" class="d-none">

            {{-- Student --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row align-items-center g-3">

                        <div class="col-auto">
                            <img id="studentImage" src="" alt="Student"
                                class="student-photo rounded-circle d-none">
                            <div id="studentImagePlaceholder" class="student-photo-placeholder rounded-circle">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>

                        <div class="col">
                            <h4 id="studentName" class="mb-1">-</h4>
                            <div class="text-muted">ID: <strong id="studentCustomId">-</strong></div>
                            <div class="text-muted">Grade: <span id="studentGrade">-</span></div>
                        </div>

                        <div class="col-auto">
                            <span id="attendanceBadge" class="badge bg-success fs-6">PRESENT</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Class + Monthly Attendance --}}
            <div class="row g-3 mb-4">

                {{-- Class Information --}}
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i>Class Information
                            </h5>
                        </div>

                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="info-item">
                                        <small>Class</small>
                                        <div id="className">-</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="info-item">
                                        <small>Category</small>
                                        <div id="classCategory">-</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="info-item">
                                        <small><i class="bi bi-building me-1"></i>Branch</small>
                                        <div id="classBranch">-</div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="info-item">
                                        <small><i class="bi bi-calendar3 me-1"></i>Date</small>
                                        <div id="classDate">-</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="info-item">
                                        <small><i class="bi bi-clock me-1"></i>Start</small>
                                        <div id="startTime">-</div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="info-item">
                                        <small><i class="bi bi-clock me-1"></i>End</small>
                                        <div id="endTime">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- This Month Attendance --}}
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-graph-up-arrow me-2 text-success"></i>This Month Attendance
                            </h5>
                        </div>

                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="attendance-stat">
                                        <div class="stat-icon stat-primary"><i class="bi bi-calendar-event"></i></div>
                                        <div class="stat-info">
                                            <span class="stat-label">Class Days</span>
                                            <h3 id="classDays" class="stat-number">0</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="attendance-stat">
                                        <div class="stat-icon stat-success"><i class="bi bi-check-circle"></i></div>
                                        <div class="stat-info">
                                            <span class="stat-label">Attended</span>
                                            <h3 id="attendedDays" class="stat-number text-success">0</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="attendance-stat">
                                        <div class="stat-icon stat-danger"><i class="bi bi-x-circle"></i></div>
                                        <div class="stat-info">
                                            <span class="stat-label">Absent</span>
                                            <h3 id="absentDays" class="stat-number text-danger">0</h3>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="attendance-stat">
                                        <div class="stat-icon stat-warning"><i class="bi bi-percent"></i></div>
                                        <div class="stat-info">
                                            <span class="stat-label">Percentage</span>
                                            <h3 id="attendancePercentage" class="stat-number text-primary">0%</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Last Payment --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-cash-stack me-2 text-warning"></i>Last Payment
                    </h5>
                </div>

                <div class="card-body">
                    <div id="paymentEmpty" class="text-muted">No payment information.</div>

                    <div id="paymentDetails" class="d-none">
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <small class="text-muted">Amount</small>
                                <div id="paymentAmount" class="fw-semibold">-</div>
                            </div>

                            <div class="col-md-3 col-6">
                                <small class="text-muted">Payment Month</small>
                                <div id="paymentMonth" class="fw-semibold">-</div>
                            </div>

                            <div class="col-md-3 col-6">
                                <small class="text-muted">Payment Method</small>
                                <div id="paymentMethod" class="fw-semibold text-capitalize">-</div>
                            </div>

                            <div class="col-md-3 col-6">
                                <small class="text-muted">Receipt</small>
                                <div id="paymentReceipt" class="fw-semibold">-</div>
                            </div>
                        </div>

                        <hr>

                        <small class="text-muted">Paid At</small>
                        <div id="paymentPaidAt" class="fw-semibold">-</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection


@push('styles')
    <style>
        /* ---------- QR Scanner ---------- */
        #qr-reader {
            width: 100% !important;
            max-width: 360px;
            margin: 0 auto;
        }

        #qr-reader video {
            width: 100% !important;
            max-height: 230px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* ---------- Empty State / Student Photo ---------- */
        .empty-icon {
            display: block;
            font-size: 50px;
            color: #adb5bd;
        }

        .student-photo,
        .student-photo-placeholder {
            width: 90px;
            height: 90px;
        }

        .student-photo {
            object-fit: cover;
        }

        .student-photo-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #94a3b8;
            font-size: 2.5rem;
        }

        /* ---------- Class Info ---------- */
        .info-item {
            height: 100%;
            padding: 0.5rem 0.7rem;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
        }

        .info-item small {
            display: block;
            font-size: 0.65rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .info-item div {
            margin-top: 0.1rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: #212529;
        }

        /* ---------- Attendance Stats ---------- */
        .attendance-stat {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            height: 100%;
            padding: 0.75rem;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 14px;
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            font-size: 1.1rem;
        }

        .stat-primary {
            background: #eff6ff;
            color: #2563eb;
        }

        .stat-success {
            background: #ecfdf5;
            color: #10b981;
        }

        .stat-danger {
            background: #fef2f2;
            color: #ef4444;
        }

        .stat-warning {
            background: #fffbeb;
            color: #f59e0b;
        }

        .stat-info {
            flex: 1;
            min-width: 0;
        }

        .stat-label {
            display: block;
            font-size: 0.65rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .stat-number {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            line-height: 1.2;
            color: #0f172a;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {
            #qr-reader {
                max-width: 280px;
            }

            #qr-reader video {
                max-height: 180px;
            }

            .stat-number {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            #qr-reader {
                max-width: 220px;
            }

            #qr-reader video {
                max-height: 140px;
            }

            .attendance-stat {
                padding: 0.5rem 0.6rem;
            }

            .stat-icon {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
                border-radius: 10px;
            }

            .stat-number {
                font-size: 0.95rem;
            }

            .stat-label {
                font-size: 0.55rem;
            }
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
            };

            /* ---------------------------------------------------------------
             | State
             * ------------------------------------------------------------- */

            let qrScanner = null;
            let isProcessing = false;
            let lastScannedCode = null;
            let lastScanTime = 0;

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

                if (!code || isProcessing) {
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

            async function runAttendance(code, markMethod, busyMessage) {
                if (isProcessing) {
                    return;
                }

                isProcessing = true;
                el.manualSubmitBtn.disabled = true;
                setScannerMessage(busyMessage);
                hideError();

                try {
                    const result = await postScan(code, markMethod);

                    if (result.success === true) {
                        renderStudent(result.data);
                    } else {
                        renderError(result);
                    }
                } catch (error) {
                    showError(error.message || 'Unable to connect to the server.');
                } finally {
                    isProcessing = false;
                    el.manualSubmitBtn.disabled = false;
                    setScannerMessage('Ready for next student...');
                }
            }

            async function postScan(code, markMethod) {
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
                        mark_method: markMethod
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
                    el.attendanceBadge.className = 'badge bg-warning text-dark fs-6';
                } else {
                    el.attendanceBadge.textContent = 'PRESENT';
                    el.attendanceBadge.className = 'badge bg-success fs-6';
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
                el.scannerStatus.className = active ? 'badge bg-success' : 'badge bg-danger';
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
