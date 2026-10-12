@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Students')

@section('content')
<div class="students-page">

    {{-- =========================================================
        TOP STATS (Controller data - NO direct queries)
    ========================================================== --}}
    <div class="row g-4 mb-4">

        {{-- TOTAL --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-icon blue">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h3>{{ $statistics['total_students'] ?? 0 }}</h3>
                    <p>Total Students</p>
                </div>
            </div>
        </div>

        {{-- ACTIVE --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-icon green">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <h3>{{ $statistics['active_students'] ?? 0 }}</h3>
                    <p>Active Students</p>
                </div>
            </div>
        </div>

        {{-- ADMISSION PAID --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-icon orange">
                    <i class="bi bi-credit-card-fill"></i>
                </div>
                <div>
                    <h3>{{ $statistics['admission_paid'] ?? 0 }}</h3>
                    <p>Admission Paid</p>
                </div>
            </div>
        </div>

        {{-- DISABLED --}}
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-icon red">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <h3>{{ $statistics['disabled_students'] ?? 0 }}</h3>
                    <p>Disabled Students</p>
                </div>
            </div>
        </div>

    </div>


    {{-- =========================================================
        BRANCH-WISE STUDENT COUNTS (NEW)
    ========================================================== --}}
    @if(isset($statistics['branch_wise_counts']) && $statistics['branch_wise_counts']->isNotEmpty())
        <div class="row g-4 mb-4">
            @foreach ($statistics['branch_wise_counts'] as $branch)
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card branch-stats-card">
                        <div class="stats-icon blue">
                            <i class="bi bi-building"></i>
                        </div>
                        <div>
                            <h3>{{ $branch->students_count }}</h3>
                            <p>{{ $branch->name }}</p>
                            @if($branch->code)
                                <small class="text-muted d-block">
                                    <i class="bi bi-hash" style="font-size: 0.7rem;"></i>{{ $branch->code }}
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif


    {{-- =========================================================
        MAIN CARD
    ========================================================== --}}
    <div class="main-card">

        {{-- HEADER --}}
        <div class="main-card-header">
            <div>
                <h4>Students Management</h4>
                <p>Manage all student information and QR status</p>
            </div>

            {{-- BUTTONS --}}
            <div class="header-buttons">
                {{-- New Registration --}}
                <a href="{{ route('student-card-registration.create') }}" class="btn btn-primary custom-btn">
                    <i class="bi bi-person-plus-fill me-1"></i>
                    New Registration
                </a>

                {{-- Temporary Registration --}}
                <a href="{{ route('student-temporary-registration.create') }}" class="btn btn-primary custom-btn">
                    <i class="bi bi-person-plus-fill me-1"></i>
                    Temporary Registration
                </a>

                {{-- Excel Export --}}
                <a href="{{ route('students.export.excel') }}" class="btn btn-success custom-btn">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                    Excel
                </a>

                {{-- PDF Export --}}
                <a href="{{ route('students.export.pdf') }}" class="btn btn-danger custom-btn">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                    PDF
                </a>

                {{-- QR Details PDF --}}
                @if(Route::has('students.export.qr-details-pdf'))
                    <a href="{{ route('students.export.qr-details-pdf') }}" class="btn btn-info custom-btn">
                        <i class="bi bi-qr-code"></i>
                        QR Details PDF
                    </a>
                @endif
            </div>
        </div>


        {{-- =====================================================
            SEARCH BAR
        ====================================================== --}}
        <div class="search-card">
            <form method="GET" action="{{ route('students.index') }}">
                <div class="row g-3">

                    {{-- SEARCH --}}
                    <div class="col-lg-5">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text"
                                   name="search"
                                   class="form-control custom-input"
                                   placeholder="Search name / mobile / QR / custom ID..."
                                   value="{{ request('search') }}">
                        </div>
                    </div>

                    {{-- GRADE (Controller data) --}}
                    <div class="col-lg-3">
                        <select name="grade_id" class="form-select custom-input">
                            <option value="">All Grades</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->id }}"
                                    {{ request('grade_id') == $grade->id ? 'selected' : '' }}>
                                    {{ $grade->grade_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- SEARCH BUTTON --}}
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100 custom-btn">
                            <i class="bi bi-search"></i>
                            Search
                        </button>
                    </div>

                    {{-- RESET --}}
                    <div class="col-lg-2">
                        <a href="{{ route('students.index') }}" class="btn btn-light border w-100 custom-btn">
                            Reset
                        </a>
                    </div>

                </div>
            </form>
        </div>


        {{-- =====================================================
            TABLE
        ====================================================== --}}
        <div class="table-responsive">
            <table class="table custom-table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Contact</th>
                        <th>Grade</th>
                        <th>QR Details</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($students as $student)
                        @php
                            $expireDate = $student->temporary_qr_code_expire_date;
                            $daysLeft = $expireDate ? now()->diffInDays($expireDate, false) : null;
                            $studentImage = $student->img_url
                                ? asset('storage/' . $student->img_url)
                                : asset('images/default-student.png');
                        @endphp

                        <tr>
                            {{-- NUMBER --}}
                            <td>
                                {{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}
                            </td>

                            {{-- STUDENT --}}
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $studentImage }}"
                                         class="student-avatar"
                                         onerror="this.src='{{ asset('images/default-student.png') }}'">
                                    <div>
                                        <div class="student-name">{{ $student->full_name }}</div>
                                        <small class="text-muted">{{ $student->initial_name }}</small>
                                    </div>
                                </div>
                            </td>

                            {{-- CONTACT --}}
                            <td>
                                <div class="fw-semibold">{{ $student->mobile }}</div>
                                <small class="text-muted">
                                    Guardian: {{ $student->guardian_mobile }}
                                </small>
                            </td>

                            {{-- GRADE --}}
                            <td>
                                <div class="fw-semibold">
                                    {{ $student->grade->grade_name ?? 'N/A' }}
                                </div>
                                <span class="badge custom-badge bg-info-subtle text-info">
                                    {{ ucfirst($student->class_type) }}
                                </span>
                            </td>

                            {{-- QR --}}
                            <td>
                                @if ($student->permanent_qr_active == 1)
                                    <span class="badge bg-success custom-badge mb-2">
                                        Permanent QR
                                    </span>
                                    <div class="fw-bold">{{ $student->custom_id }}</div>
                                @else
                                    <span class="badge bg-warning text-dark custom-badge mb-2">
                                        Temporary QR
                                    </span>
                                    <div class="fw-bold">{{ $student->temporary_qr_code }}</div>

                                    @if ($daysLeft < 0)
                                        <small class="text-danger fw-semibold">Expired</small>
                                    @elseif($daysLeft == 0)
                                        <small class="text-warning fw-semibold">Expires Today</small>
                                    @else
                                        <small class="text-muted">{{ $daysLeft }} days left</small>
                                    @endif
                                @endif
                            </td>

                            {{-- STATUS --}}
                            <td>
                                @if ($student->student_disable)
                                    <span class="badge bg-danger custom-badge">Disabled</span>
                                @elseif($student->is_active)
                                    <span class="badge bg-success custom-badge">Active</span>
                                @else
                                    <span class="badge bg-secondary custom-badge">Inactive</span>
                                @endif

                                <br>

                                @if ($student->admission)
                                    <span class="badge bg-primary custom-badge mt-2">
                                        Admission Paid
                                    </span>
                                @endif
                            </td>

                            {{-- ACTIONS --}}
                            <td class="text-end">
                                <div class="action-buttons">

                                    {{-- VIEW --}}
                                    <a href="{{ route('students.show', $student) }}" class="action-btn view-btn" title="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>

                                    {{-- EDIT --}}
                                    <a href="{{ route('students.edit', $student) }}" class="action-btn edit-btn" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    {{-- TOGGLE ACTIVE --}}
                                    <form method="POST"
                                          action="{{ route('students.toggle-active', $student) }}"
                                          onsubmit="return confirm('Change active status?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="action-btn toggle-btn" title="Toggle Active">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>

                                    {{-- DELETE (Only for ADMIN / BRANCH_ADMIN) --}}
                                    @if (auth()->user()->userType &&
                                        in_array(auth()->user()->userType->code, ['ADMIN', 'BRANCH_ADMIN'], true))
                                        <form method="POST"
                                              action="{{ route('students.destroy', $student) }}"
                                              onsubmit="return confirm('Delete student?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn delete-btn" title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="empty-state">
                                    <i class="bi bi-person-x"></i>
                                    <h5>No students found</h5>
                                    <p>Try adjusting your search filters</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>


        {{-- =====================================================
            PAGINATION (filters preserved via service appends)
        ====================================================== --}}
        <div class="mt-4">
            {{ $students->links() }}
        </div>

    </div>

</div>
@endsection


{{-- =============================================================
    STYLES
============================================================= --}}
@push('styles')
<style>
    /* ============================================
       PAGE
    ============================================ */
    .students-page {
        animation: fadeIn 0.4s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ============================================
       STATS
    ============================================ */
    .stats-card {
        background: white;
        border-radius: 24px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        border: 1px solid #eef2f7;
        transition: all 0.25s ease;
    }

    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
    }

    .branch-stats-card {
        border-left: 4px solid #2563eb;
    }

    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        flex-shrink: 0;
    }

    .blue { background: linear-gradient(135deg, #2563eb, #3b82f6); }
    .green { background: linear-gradient(135deg, #10b981, #34d399); }
    .orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .red { background: linear-gradient(135deg, #ef4444, #f87171); }

    .stats-card h3 {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
        color: #0f172a;
    }

    .stats-card p {
        margin: 0;
        color: #64748b;
    }

    /* ============================================
       MAIN CARD
    ============================================ */
    .main-card {
        background: white;
        border-radius: 28px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid #eef2f7;
    }

    .main-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .main-card-header h4 {
        margin: 0;
        font-weight: 700;
        color: #0f172a;
    }

    .main-card-header p {
        margin: 0;
        color: #64748b;
    }

    /* ============================================
       BUTTONS
    ============================================ */
    .header-buttons {
        display: flex;
        gap: 0.7rem;
        flex-wrap: wrap;
    }

    .custom-btn {
        border-radius: 14px;
        padding: 0.7rem 1.2rem;
        font-weight: 600;
        border: none;
    }

    /* ============================================
       SEARCH
    ============================================ */
    .search-card {
        background: #f8fafc;
        border-radius: 20px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border: 1px solid #eef2f7;
    }

    .search-input-wrapper {
        position: relative;
    }

    .search-input-wrapper i {
        position: absolute;
        top: 50%;
        left: 15px;
        transform: translateY(-50%);
        color: #64748b;
        z-index: 2;
    }

    .custom-input {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0;
        min-height: 48px;
        transition: all 0.2s ease;
    }

    .custom-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
    }

    .search-input-wrapper .custom-input {
        padding-left: 42px;
    }

    /* ============================================
       TABLE
    ============================================ */
    .custom-table thead th {
        border: none;
        background: #f8fafc;
        color: #475569;
        font-size: 0.82rem;
        text-transform: uppercase;
        padding: 1rem;
        font-weight: 700;
    }

    .custom-table tbody tr {
        transition: 0.2s ease;
    }

    .custom-table tbody tr:hover {
        background: #f8fafc;
    }

    .custom-table tbody td {
        padding: 1rem;
        border-color: #f1f5f9;
        vertical-align: middle;
    }

    /* ============================================
       AVATAR
    ============================================ */
    .student-avatar {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e2e8f0;
        flex-shrink: 0;
    }

    .student-name {
        font-weight: 700;
        color: #0f172a;
    }

    /* ============================================
       BADGES
    ============================================ */
    .custom-badge {
        border-radius: 10px;
        padding: 0.5rem 0.7rem;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* ============================================
       ACTIONS
    ============================================ */
    .action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .action-btn {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(0, 0, 0, 0.10);
    }

    .view-btn { background: #eff6ff; color: #2563eb; }
    .view-btn:hover { background: #2563eb; color: #fff; }

    .edit-btn { background: #fef3c7; color: #d97706; }
    .edit-btn:hover { background: #f59e0b; color: #fff; }

    .toggle-btn { background: #ecfdf5; color: #10b981; }
    .toggle-btn:hover { background: #10b981; color: #fff; }

    .delete-btn { background: #fef2f2; color: #ef4444; }
    .delete-btn:hover { background: #ef4444; color: #fff; }

    /* ============================================
       EMPTY STATE
    ============================================ */
    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
        display: block;
    }

    .empty-state h5 {
        font-weight: 700;
        color: #475569;
    }

    .empty-state p {
        color: #94a3b8;
        font-size: 0.85rem;
    }

    /* ============================================
       RESPONSIVE
    ============================================ */
    @media (max-width: 768px) {
        .main-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .header-buttons {
            width: 100%;
        }

        .header-buttons a {
            flex: 1;
        }

        .student-avatar {
            width: 45px;
            height: 45px;
        }

        .action-btn {
            width: 34px;
            height: 34px;
        }
    }
</style>
@endpush