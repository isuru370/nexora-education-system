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
        <div id="attendanceAlert" class="alert alert-info d-none nx-alert" data-persist-alert="true" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-info-circle-fill fs-5 mt-1" id="alertIcon"></i>
                <div class="flex-grow-1">
                    <strong id="alertTitle">Info</strong>
                    <div id="alertMessage" class="mt-1"></div>
                </div>
                <button type="button" class="btn-close" aria-label="Close"
                    onclick="document.getElementById('attendanceAlert').classList.add('d-none')"></button>
            </div>
        </div>

        <div class="row g-4">
            {{-- QR READER --}}
            <div class="col-lg-4">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-qr-code-scan text-primary"></i> QR Reader</h5>
                        <span class="nx-badge nx-badge-blue">Live</span>
                    </div>
                    <div class="p-3">
                        <ul class="nav nav-pills nx-pills mb-3" role="tablist">
                            <li class="nav-item me-2">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#scanTab" type="button">Scan QR</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#manualTab" type="button">Manual Entry</button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="scanTab">
                                <div id="qr-reader" class="qr-reader-box"></div>
                                <div class="d-grid gap-2 mt-3">
                                    <button type="button" class="nx-btn nx-btn-primary" id="startScannerBtn">
                                        <i class="bi bi-camera-fill"></i> Start Scanner
                                    </button>
                                    <button type="button" class="nx-btn nx-btn-light d-none" id="stopScannerBtn">
                                        <i class="bi bi-stop-circle"></i> Stop Scanner
                                    </button>
                                </div>
                                <small class="cell-sub d-block mt-3">Camera scanning requires HTTPS or localhost.</small>
                            </div>
                            <div class="tab-pane fade" id="manualTab">
                                <form id="manualQrForm" autocomplete="off">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="manualQrCode" class="nx-label">Student QR / Code</label>
                                        <div class="nx-input-group">
                                            <span class="nx-input-group-text"><i class="bi bi-upc-scan"></i></span>
                                            <input type="text" class="form-control nx-input" id="manualQrCode"
                                                placeholder="Enter QR code" maxlength="150">
                                        </div>
                                    </div>
                                    <div class="d-grid">
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
            <div class="col-lg-8">
                <div id="emptyStateCard" class="nx-card">
                    <div class="empty-state-box">
                        <div class="empty-icon"><i class="bi bi-qr-code-scan"></i></div>
                        <h5 class="empty-title">No Student Selected</h5>
                        <p class="cell-sub mb-0">Scan a QR code or enter a student code manually.</p>
                    </div>
                </div>

                <div id="studentDetailsCard" class="nx-card d-none mb-4">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-person-badge text-primary"></i> Student Details</h5>
                        <span class="nx-badge nx-badge-gray" id="qrTypeBadge">-</span>
                    </div>
                    <div class="p-3">
                        <div class="row align-items-center g-4">
                            <div class="col-md-3 text-center">
                                <img id="studentImage" src="{{ asset('storage/uploads/male.png') }}" class="student-image" alt="Student Image">
                            </div>
                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="detail-box"><small class="cell-sub">Student ID</small><div class="detail-value" id="studentId">-</div></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-box"><small class="cell-sub">Name</small><div class="detail-value" id="studentName">-</div></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-box"><small class="cell-sub">Mobile</small><div class="detail-value" id="studentMobile">-</div></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-box"><small class="cell-sub">Guardian Mobile</small><div class="detail-value" id="guardianMobile">-</div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="attendanceDetailsCard" class="nx-card d-none">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-clipboard-check text-primary"></i> Attendance Details</h5>
                        <span class="nx-badge nx-badge-green">Ready</span>
                    </div>
                    <div class="p-3"><div id="attendanceDetailsContent"></div></div>
                </div>

                <div id="errorDetailsCard" class="nx-card mt-4 d-none">
                    <div class="nx-card-header nx-card-header-danger">
                        <h5 class="nx-card-title text-white"><i class="bi bi-exclamation-triangle-fill"></i> Error Details</h5>
                    </div>
                    <div class="p-3"><div id="errorDetails"></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ADD CLASS MODAL --}}
    <div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content nx-modal">
                <form id="addClassForm" autocomplete="off">
                    @csrf
                    <div class="modal-header nx-modal-header">
                        <h5 class="modal-title">Add Student To Class</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body nx-modal-body">
                        <div class="nx-info-box">
                            <div><strong>Student:</strong> <span id="modalStudentName">-</span></div>
                            <div><strong>Class:</strong> <span id="modalClassName">-</span></div>
                            <div><strong>Category:</strong> <span id="modalCategoryName">-</span></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="nx-label">Free Card</label>
                                <select id="modalIsFreeCard" class="form-select nx-input">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="nx-label">Custom Fee</label>
                                <input type="number" step="0.01" min="0" id="modalCustomFee" class="form-control nx-input">
                            </div>
                            <div class="col-md-4">
                                <label class="nx-label">Discount %</label>
                                <input type="number" step="0.01" min="0" max="100" id="modalDiscountPercentage" class="form-control nx-input">
                            </div>
                            <div class="col-md-6">
                                <label class="nx-label">Custom Fee Reason</label>
                                <select id="modalCustomFeeReason" class="form-select nx-input">
                                    <option value="">Select Reason</option>
                                    <option value="Student enrolled in multiple categories">Student enrolled in multiple categories</option>
                                    <option value="Special Discount">Special Discount</option>
                                    <option value="Scholarship">Scholarship</option>
                                    <option value="Sibling Discount">Sibling Discount</option>
                                    <option value="Staff Child">Staff Child</option>
                                    <option value="Promotional">Promotional</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="nx-label">Discount Reason</label>
                                <select id="modalDiscountReason" class="form-select nx-input">
                                    <option value="">Select Reason</option>
                                    <option value="Half Card">Half Card</option>
                                    <option value="Special Discount">Special Discount</option>
                                    <option value="Scholarship">Scholarship</option>
                                    <option value="Sibling Discount">Sibling Discount</option>
                                    <option value="Staff Child">Staff Child</option>
                                    <option value="Promotional">Promotional</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="nx-label">Enrolled At</label>
                                <input type="date" id="modalEnrolledAt" class="form-control nx-input" value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer nx-modal-footer">
                        <button type="button" class="nx-btn nx-btn-light" data-bs-dismiss="modal">Cancel</button>
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

    .dashboard-page .p-3 .nx-btn-light,
    .dashboard-page .modal .nx-btn-light {
        background: #fff; border-color: var(--nx-border); color: var(--nx-muted);
    }

    .dashboard-page .nx-pills .nav-link {
        border-radius: 8px;
        font-weight: 500;
        font-size: .82rem;
        color: var(--nx-muted);
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        padding: .4rem .9rem;
    }
    .dashboard-page .nx-pills .nav-link.active {
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
    .dashboard-page .nx-modal-header .modal-title {
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

    .dashboard-page .form-check-input:checked {
        background-color: var(--nx-blue);
        border-color: var(--nx-blue);
    }

    @media (max-width: 768px) {
        .dashboard-page .student-image { width: 130px; height: 130px; }
    }
</style>
@endpush