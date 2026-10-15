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