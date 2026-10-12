@extends('layouts.app')

@section('title', 'New Attendance')

@section('content')

    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Attendance</h3>
                <p class="text-muted mb-0">
                    Scan student QR or enter Student ID
                </p>
            </div>

            <div>
                <span id="scannerStatus" class="badge bg-success">
                    Scanner Active
                </span>
            </div>
        </div>

        {{-- ============================================= --}}
        {{-- TOP ROW: SCANNER + MANUAL (Side by Side) --}}
        {{-- ============================================= --}}
        <div class="row g-3 mb-3">

            {{-- QR SCANNER --}}
            <div class="col-lg-4 col-md-5">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-qr-code-scan me-2"></i>
                            QR Scanner
                        </h6>
                    </div>

                    <div class="card-body p-2">

                        <div id="qr-reader"></div>

                        <div id="scannerMessage" class="text-center text-muted mt-2 small">
                            Point the camera at the student's QR code
                        </div>

                    </div>

                </div>

            </div>


            {{-- MANUAL ATTENDANCE --}}
            <div class="col-lg-8 col-md-7">

                <div class="card shadow-sm border-0">

                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-keyboard me-2"></i>
                            Manual Attendance
                        </h6>
                    </div>

                    <div class="card-body">

                        <form id="manualAttendanceForm">

                            <label class="form-label small mb-1">
                                Student ID / QR Code
                            </label>

                            <div class="input-group">

                                <input type="text" id="manualCode" class="form-control" placeholder="Enter Student ID"
                                    autocomplete="off">

                                <button type="submit" class="btn btn-primary" id="manualSubmitBtn">
                                    <i class="bi bi-check-lg me-1"></i>
                                    Mark
                                </button>

                            </div>

                            <small class="text-muted">
                                Enter the Student ID or QR code.
                            </small>

                        </form>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================= --}}
        {{-- ERROR --}}
        {{-- ============================================= --}}
        <div class="row g-3 mb-3">
            <div class="col-12">

                <div id="errorResult" class="alert alert-danger shadow-sm border-0 mb-0" style="display:none;">
                </div>

            </div>
        </div>


        {{-- ============================================= --}}
        {{-- STUDENT DETAILS - Full Width --}}
        {{-- ============================================= --}}
        <div class="row g-3">
            <div class="col-12">

                {{-- Empty State --}}
                <div id="emptyState" class="card shadow-sm border-0">

                    <div class="card-body text-center py-4">

                        <div class="mb-2">
                            <i class="bi bi-person-badge" style="font-size:50px;color:#adb5bd;">
                            </i>
                        </div>

                        <h6 class="text-muted mb-1">
                            Waiting for Student
                        </h6>

                        <p class="text-muted mb-0 small">
                            Scan a QR code or enter a Student ID.
                        </p>

                    </div>

                </div>


                {{-- Student Result --}}
                <div id="studentResult" style="display:none;">

                    {{-- ========================================= --}}
                    {{-- STUDENT CARD --}}
                    {{-- ========================================= --}}
                    <div class="card shadow-sm border-0 mb-4">

                        <div class="card-body">

                            <div class="row align-items-center">

                                <div class="col-auto">

                                    <img id="studentImage" src="" alt="Student" class="rounded-circle"
                                        width="90" height="90" style="object-fit:cover;">

                                </div>

                                <div class="col">

                                    <h4 id="studentName" class="mb-1">
                                    </h4>

                                    <div class="text-muted">
                                        ID:
                                        <strong id="studentCustomId"></strong>
                                    </div>

                                    <div class="text-muted">
                                        Grade:
                                        <span id="studentGrade"></span>
                                    </div>

                                </div>

                                <div class="col-auto">

                                    <span id="attendanceBadge" class="badge bg-success fs-6">
                                        PRESENT
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ========================================= --}}
                    {{-- CLASS + ATTENDANCE - SIDE BY SIDE --}}
                    {{-- ========================================= --}}
                    <div class="row g-3 mb-4">

                        {{-- CLASS INFORMATION - LEFT --}}
                        <div class="col-lg-6">

                            <div class="card shadow-sm border-0 h-100">

                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i>
                                        Class Information
                                    </h5>
                                </div>

                                <div class="card-body">

                                    <div class="row g-2">

                                        {{-- Class --}}
                                        <div class="col-12">
                                            <div class="info-item">
                                                <small class="text-muted">Class</small>
                                                <div id="className" class="fw-bold text-dark">
                                                    -
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Category --}}
                                        <div class="col-12">
                                            <div class="info-item">
                                                <small class="text-muted">Category</small>
                                                <div id="classCategory" class="fw-bold text-dark">
                                                    -
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Date --}}
                                        <div class="col-12">
                                            <div class="info-item">
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i> Date
                                                </small>
                                                <div id="classDate" class="fw-bold text-dark">
                                                    -
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Time - Start & End in one row --}}
                                        <div class="col-6">
                                            <div class="info-item">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i> Start
                                                </small>
                                                <div id="startTime" class="fw-bold text-dark">
                                                    -
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            <div class="info-item">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i> End
                                                </small>
                                                <div id="endTime" class="fw-bold text-dark">
                                                    -
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- ATTENDANCE - RIGHT --}}
                        <div class="col-lg-6">

                            <div class="card shadow-sm border-0 h-100">

                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-graph-up-arrow me-2 text-success"></i>
                                        This Month Attendance
                                    </h5>
                                </div>

                                <div class="card-body">

                                    <div class="row g-2">

                                        {{-- Class Days --}}
                                        <div class="col-6">
                                            <div class="attendance-stat">
                                                <div class="stat-icon bg-primary-soft">
                                                    <i class="bi bi-calendar-event"></i>
                                                </div>
                                                <div class="stat-info">
                                                    <span class="stat-label">Class Days</span>
                                                    <h3 id="classDays" class="stat-number">0</h3>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Attended --}}
                                        <div class="col-6">
                                            <div class="attendance-stat">
                                                <div class="stat-icon bg-success-soft">
                                                    <i class="bi bi-check-circle"></i>
                                                </div>
                                                <div class="stat-info">
                                                    <span class="stat-label">Attended</span>
                                                    <h3 id="attendedDays" class="stat-number text-success">0</h3>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Absent --}}
                                        <div class="col-6">
                                            <div class="attendance-stat">
                                                <div class="stat-icon bg-danger-soft">
                                                    <i class="bi bi-x-circle"></i>
                                                </div>
                                                <div class="stat-info">
                                                    <span class="stat-label">Absent</span>
                                                    <h3 id="absentDays" class="stat-number text-danger">0</h3>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Percentage --}}
                                        <div class="col-6">
                                            <div class="attendance-stat">
                                                <div class="stat-icon bg-warning-soft">
                                                    <i class="bi bi-percent"></i>
                                                </div>
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


                    {{-- ========================================= --}}
                    {{-- LAST PAYMENT - FULL WIDTH --}}
                    {{-- ========================================= --}}
                    <div class="card shadow-sm border-0 mb-4">

                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-cash-stack me-2 text-warning"></i>
                                Last Payment
                            </h5>
                        </div>

                        <div class="card-body">

                            <div id="paymentDetails">

                                <div class="text-muted">
                                    No payment information.
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </div>

    </div>


    {{-- QR SCANNER --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>


    <style>
        /* =============================================
               QR SCANNER - COMPACT
            ============================================= */
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

        #qr-reader #qr-reader__scan_region {
            min-height: 180px;
        }

        /* =============================================
               ATTENDANCE STATS - Premium Design
            ============================================= */
        .attendance-stat {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 14px;
            border: 1px solid #eef2f7;
            transition: all 0.3s ease;
            height: 100%;
        }

        .attendance-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            background: #ffffff;
            border-color: #e2e8f0;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .stat-icon.bg-primary-soft {
            background: #eff6ff;
            color: #2563eb;
        }

        .stat-icon.bg-success-soft {
            background: #ecfdf5;
            color: #10b981;
        }

        .stat-icon.bg-danger-soft {
            background: #fef2f2;
            color: #ef4444;
        }

        .stat-icon.bg-warning-soft {
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
            font-size: 1.3rem;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
            color: #0f172a;
        }

        .stat-number.text-success {
            color: #10b981;
        }

        .stat-number.text-danger {
            color: #ef4444;
        }

        .stat-number.text-primary {
            color: #2563eb;
        }

        /* =============================================
               CLASS INFO ITEMS
            ============================================= */
        .info-item {
            padding: 0.5rem 0.7rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .info-item:hover {
            background: #ffffff;
            border-color: #e2e8f0;
        }

        .info-item small {
            font-size: 0.65rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: block;
        }

        .info-item div {
            font-size: 0.9rem;
            margin-top: 0.1rem;
        }

        /* =============================================
               RESPONSIVE
            ============================================= */
        @media (max-width: 992px) {
            .col-lg-6 {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 768px) {
            .attendance-stat {
                padding: 0.6rem;
            }

            .stat-number {
                font-size: 1.1rem;
            }

            .info-item {
                padding: 0.4rem 0.6rem;
            }

            .info-item div {
                font-size: 0.85rem;
            }

            #qr-reader {
                max-width: 280px;
            }

            #qr-reader video {
                max-height: 180px;
            }

            #qr-reader #qr-reader__scan_region {
                min-height: 150px;
            }
        }

        @media (max-width: 576px) {
            .attendance-stat {
                flex-direction: row;
                text-align: left;
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

            #qr-reader {
                max-width: 220px;
            }

            #qr-reader video {
                max-height: 140px;
            }

            #qr-reader #qr-reader__scan_region {
                min-height: 120px;
            }
        }
    </style>


    <script>
        let qrScanner = null;
        let scannerRunning = false;
        let processingScan = false;

        // Prevent the same QR from firing repeatedly.
        let lastScannedCode = null;
        let lastScanTime = 0;

        const SCAN_COOLDOWN = 2500;

        /*
        |--------------------------------------------------------------------------
        | Scanner
        |--------------------------------------------------------------------------
        */

        async function startScanner() {
            if (scannerRunning) {
                return;
            }

            try {
                qrScanner = new Html5Qrcode("qr-reader");

                await qrScanner.start(
                    {
                        facingMode: "environment"
                    },
                    {
                        fps: 10,
                        qrbox: {
                            width: 180,
                            height: 180
                        }
                    },
                    async function (decodedText) {
                        await handleQrScan(decodedText);
                    },
                    function () {
                        // Normal QR scanning errors are ignored.
                    }
                );

                scannerRunning = true;
                updateScannerStatus(true);
                setScannerMessage("Point the camera at the student's QR code.");
            } catch (error) {
                console.error("QR SCANNER ERROR:", error);

                scannerRunning = false;
                updateScannerStatus(false);

                showError(
                    "Unable to start camera. Please check camera permission."
                );
            }
        }

        async function handleQrScan(code) {
            code = String(code || "").trim();

            if (!code) {
                return;
            }

            const now = Date.now();

            if (
                lastScannedCode === code &&
                (now - lastScanTime) < SCAN_COOLDOWN
            ) {
                return;
            }

            if (processingScan) {
                return;
            }

            lastScannedCode = code;
            lastScanTime = now;
            processingScan = true;

            setScannerMessage("Processing attendance...");
            hideError();

            try {
                await processAttendance(code, "qr_web");
            } finally {
                processingScan = false;
                setScannerMessage("Ready for next student...");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Manual Attendance
        |--------------------------------------------------------------------------
        */

        document
            .getElementById("manualAttendanceForm")
            .addEventListener("submit", async function (event) {
                event.preventDefault();

                if (processingScan) {
                    return;
                }

                const input = document.getElementById("manualCode");
                const code = String(input.value || "").trim();

                if (!code) {
                    input.focus();
                    showError("Please enter a Student ID or QR code.");
                    return;
                }

                processingScan = true;
                setScannerMessage("Processing manual attendance...");
                hideError();

                try {
                    await processAttendance(code, "manual_web");
                } finally {
                    processingScan = false;
                    input.value = "";
                    input.focus();
                    setScannerMessage("Ready for next student...");
                }
            });

        /*
        |--------------------------------------------------------------------------
        | API
        |--------------------------------------------------------------------------
        */

        async function processAttendance(code, markMethod) {
            const url = "{{ url('/api/new-attendance/scan') }}";

            console.log("ATTENDANCE REQUEST", {
                code: code,
                mark_method: markMethod,
                url: url
            });

            try {
                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    credentials: "same-origin",
                    body: JSON.stringify({
                        code: code,
                        mark_method: markMethod
                    })
                });

                const text = await response.text();

                let result;

                try {
                    result = JSON.parse(text);
                } catch (error) {
                    console.error("INVALID JSON RESPONSE:", text);

                    showError(
                        "Server returned an invalid response."
                    );

                    return;
                }

                console.log("ATTENDANCE RESPONSE:", result);

                if (result.success === true) {
                    displayStudentResult(
                        result.data,
                        result.message
                    );

                    return;
                }

                handleAttendanceError(result);
            } catch (error) {
                console.error("ATTENDANCE API ERROR:", error);

                showError(
                    "Unable to connect to attendance API."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Error
        |--------------------------------------------------------------------------
        */

        function handleAttendanceError(result) {
            const message =
                result.message ||
                "Attendance could not be processed.";

            const data = result.data || {};
            const student = data.student || null;

            if (!student) {
                showError(message);
                return;
            }

            const errorElement =
                document.getElementById("errorResult");

            if (!errorElement) {
                return;
            }

            const grade = getGradeName(student);

            errorElement.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>${escapeHtml(message)}</strong>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-2">
                    <div class="bg-light px-3 py-1 rounded">
                        <strong>Student:</strong>
                        ${escapeHtml(
                            student.full_name ||
                            student.initial_name ||
                            "-"
                        )}
                    </div>

                    <div class="bg-light px-3 py-1 rounded">
                        <strong>ID:</strong>
                        ${escapeHtml(
                            student.custom_id ||
                            student.student_code ||
                            "-"
                        )}
                    </div>

                    <div class="bg-light px-3 py-1 rounded">
                        <strong>Grade:</strong>
                        ${escapeHtml(grade)}
                    </div>
                </div>
            `;

            errorElement.style.display = "block";

            errorElement.scrollIntoView({
                behavior: "smooth",
                block: "nearest"
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Display Student Result
        |--------------------------------------------------------------------------
        */

        function displayStudentResult(data, message) {
            if (!data) {
                showError("No attendance data received.");
                return;
            }

            hideError();

            document.getElementById("emptyState").style.display = "none";
            document.getElementById("studentResult").style.display = "block";

            const student = data.student || {};
            const schedule = data.schedule || {};
            const classData = schedule.class || {};
            const attendance = getAttendanceResult(data);
            const summary = getAttendanceSummary(data);
            const payment = data.last_payment || null;

            /*
            |--------------------------------------------------------------------------
            | Student
            |--------------------------------------------------------------------------
            */

            document.getElementById("studentName").textContent =
                student.initial_name ||
                student.full_name ||
                "-";

            document.getElementById("studentCustomId").textContent =
                student.custom_id ||
                student.student_code ||
                "-";

            document.getElementById("studentGrade").textContent =
                getGradeName(student);

            setStudentImage(student);

            /*
            |--------------------------------------------------------------------------
            | Class / Schedule
            |--------------------------------------------------------------------------
            */

            document.getElementById("className").textContent =
                classData.class_name || "-";

            document.getElementById("classCategory").textContent =
                classData.category || "-";

            document.getElementById("classDate").textContent =
                schedule.class_date ||
                schedule.date ||
                "-";

            document.getElementById("startTime").textContent =
                schedule.start_time || "-";

            document.getElementById("endTime").textContent =
                schedule.end_time || "-";

            /*
            |--------------------------------------------------------------------------
            | Current Attendance
            |--------------------------------------------------------------------------
            */

            const alreadyMarked =
                attendance.already_marked === true;

            const badge =
                document.getElementById("attendanceBadge");

            if (alreadyMarked) {
                badge.textContent = "ALREADY PRESENT";
                badge.className =
                    "badge bg-warning text-dark fs-6";
            } else {
                badge.textContent = "PRESENT";
                badge.className =
                    "badge bg-success fs-6";
            }

            /*
            |--------------------------------------------------------------------------
            | Monthly Attendance
            |--------------------------------------------------------------------------
            */

            document.getElementById("classDays").textContent =
                getNumber(
                    summary.total_count,
                    summary.class_days
                );

            document.getElementById("attendedDays").textContent =
                getNumber(
                    summary.present_count,
                    summary.attended_days
                );

            document.getElementById("absentDays").textContent =
                getNumber(
                    summary.absent_count,
                    summary.absent_days
                );

            document.getElementById("attendancePercentage").textContent =
                getNumber(
                    summary.attendance_percentage,
                    0
                ) + "%";

            /*
            |--------------------------------------------------------------------------
            | Last Payment
            |--------------------------------------------------------------------------
            */

            displayPayment(payment);
        }

        /*
        |--------------------------------------------------------------------------
        | Response Helpers
        |--------------------------------------------------------------------------
        */

        function getAttendanceResult(data) {
            /*
             * New API:
             *
             * data.attendance = {
             *     id,
             *     status,
             *     marked_at,
             *     mark_method,
             *     already_marked
             * }
             */

            if (
                data.attendance &&
                typeof data.attendance === "object" &&
                (
                    Object.prototype.hasOwnProperty.call(
                        data.attendance,
                        "already_marked"
                    ) ||
                    Object.prototype.hasOwnProperty.call(
                        data.attendance,
                        "marked_at"
                    )
                )
            ) {
                return data.attendance;
            }

            return data.attendance_result || {};
        }

        function getAttendanceSummary(data) {
            /*
             * Support the new API name first.
             * The fallback keeps the page safe if the backend
             * currently returns monthly_summary.
             */

            if (
                data.attendance_summary &&
                typeof data.attendance_summary === "object"
            ) {
                return data.attendance_summary;
            }

            if (
                data.monthly_summary &&
                typeof data.monthly_summary === "object"
            ) {
                return data.monthly_summary;
            }

            if (
                data.summary &&
                typeof data.summary === "object"
            ) {
                return data.summary;
            }

            return {};
        }

        function getGradeName(student) {
            if (
                student.grade &&
                typeof student.grade === "object"
            ) {
                return (
                    student.grade.grade_name ||
                    student.grade.name ||
                    "-"
                );
            }

            return student.grade || "-";
        }

        function setStudentImage(student) {
            const image =
                document.getElementById("studentImage");

            const imageUrl =
                student.img_url ||
                student.image_url ||
                "";

            if (imageUrl) {
                image.src = imageUrl;
            } else {
                image.removeAttribute("src");
            }
        }

        function getNumber(value, fallback) {
            if (
                value === null ||
                value === undefined ||
                value === ""
            ) {
                return fallback;
            }

            return value;
        }

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */

        function displayPayment(payment) {
            const container =
                document.getElementById("paymentDetails");

            if (!payment) {
                container.innerHTML = `
                    <div class="text-muted">
                        No payment information.
                    </div>
                `;

                return;
            }

            container.innerHTML = `
                <div class="row g-3">

                    <div class="col-md-3 col-6">
                        <small class="text-muted">
                            Amount
                        </small>

                        <div class="fw-semibold">
                            Rs. ${formatAmount(payment.amount)}
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <small class="text-muted">
                            Payment Month
                        </small>

                        <div class="fw-semibold">
                            ${escapeHtml(
                                payment.payment_month || "-"
                            )}
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <small class="text-muted">
                            Payment Method
                        </small>

                        <div class="fw-semibold">
                            ${escapeHtml(
                                payment.payment_method || "-"
                            )}
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <small class="text-muted">
                            Receipt
                        </small>

                        <div class="fw-semibold">
                            ${escapeHtml(
                                payment.receipt_number || "-"
                            )}
                        </div>
                    </div>

                </div>

                <hr>

                <div class="row g-3">
                    <div class="col-12">
                        <small class="text-muted">
                            Paid At
                        </small>

                        <div class="fw-semibold">
                            ${escapeHtml(
                                payment.paid_at || "-"
                            )}
                        </div>
                    </div>
                </div>
            `;
        }

        function formatAmount(amount) {
            const number =
                parseFloat(amount || 0);

            return number.toLocaleString(
                "en-LK",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Scanner UI
        |--------------------------------------------------------------------------
        */

        function updateScannerStatus(active) {
            const status =
                document.getElementById("scannerStatus");

            if (!status) {
                return;
            }

            if (active) {
                status.textContent = "Scanner Active";
                status.className = "badge bg-success";
            } else {
                status.textContent = "Scanner Inactive";
                status.className = "badge bg-danger";
            }
        }

        function setScannerMessage(message) {
            const element =
                document.getElementById("scannerMessage");

            if (element) {
                element.textContent = message;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Error UI
        |--------------------------------------------------------------------------
        */

        function showError(message) {
            const element =
                document.getElementById("errorResult");

            if (!element) {
                return;
            }

            element.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>${escapeHtml(message)}</strong>
                </div>
            `;

            element.style.display = "block";

            element.scrollIntoView({
                behavior: "smooth",
                block: "nearest"
            });
        }

        function hideError() {
            const element =
                document.getElementById("errorResult");

            if (!element) {
                return;
            }

            element.style.display = "none";
            element.innerHTML = "";
        }

        /*
        |--------------------------------------------------------------------------
        | Safe HTML
        |--------------------------------------------------------------------------
        */

        function escapeHtml(value) {
            const text = String(value ?? "");

            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        /*
        |--------------------------------------------------------------------------
        | Start
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            "DOMContentLoaded",
            function () {
                startScanner();
            }
        );
    </script>

@endsection
