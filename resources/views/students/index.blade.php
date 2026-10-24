@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Students')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Students</h1>
                <p class="hero-subtitle">Manage all student information and QR status.</p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $statistics['total_students'] ?? 0 }}</span>
                        <span class="mini-stat-label">Total Students</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $statistics['active_students'] ?? 0 }}</span>
                        <span class="mini-stat-label">Active</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $statistics['admission_paid'] ?? 0 }}</span>
                        <span class="mini-stat-label">Admission Paid</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $statistics['disabled_students'] ?? 0 }}</span>
                        <span class="mini-stat-label">Disabled</span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-date-time">
                    <div class="date-box">
                        <i class="bi bi-people-fill"></i>
                        <span>Student Directory</span>
                    </div>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="stats-grid mb-4">

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Students</span>
                <h2 class="stat-number">{{ $statistics['total_students'] ?? 0 }}</h2>
                <span class="stat-change neutral">
                    All <span class="stat-change-note">registered students</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Active Students</span>
                <h2 class="stat-number">{{ $statistics['active_students'] ?? 0 }}</h2>
                <span class="stat-change up">
                    Currently <span class="stat-change-note">active</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-credit-card-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Admission Paid</span>
                <h2 class="stat-number">{{ $statistics['admission_paid'] ?? 0 }}</h2>
                <span class="stat-change neutral">
                    With <span class="stat-change-note">admission payment</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Disabled Students</span>
                <h2 class="stat-number">{{ $statistics['disabled_students'] ?? 0 }}</h2>
                <span class="stat-change down">
                    Not <span class="stat-change-note">active</span>
                </span>
            </div>
        </div>
    </div>

    {{-- BRANCH-WISE COUNTS --}}
    @if (isset($statistics['branch_wise_counts']) && $statistics['branch_wise_counts']->isNotEmpty())
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-diagram-3 text-primary"></i> Branches</h5>
                    <p class="nx-card-subtitle">Student count per institute branch</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Code</th>
                            <th class="text-end">Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($statistics['branch_wise_counts'] as $branch)
                            <tr>
                                <td class="cell-title">
                                    <i class="bi bi-building me-1 text-muted"></i>
                                    {{ $branch->name }}
                                </td>
                                <td>
                                    @if ($branch->code)
                                        <span class="id-badge">
                                            <i class="bi bi-hash"></i>{{ $branch->code }}
                                        </span>
                                    @else
                                        <span class="cell-sub">—</span>
                                    @endif
                                </td>
                                <td class="text-end"><strong>{{ $branch->students_count }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- MAIN CARD --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-people-fill text-primary"></i> Students Management</h5>
                <p class="nx-card-subtitle">Manage all student information and QR status</p>
            </div>

            <div class="header-buttons">
                {{-- One way to register, chosen per institute: php artisan nexora:card-mode --}}
                @cardaccess('assign')
                    @cardmode('permanent')
                        <a href="{{ route('student-card-registration.create') }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-person-plus-fill"></i> New Registration
                        </a>
                    @endcardmode
                    @cardmode('temporary')
                        <a href="{{ route('student-temporary-registration.create') }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-person-plus-fill"></i> New Registration (Temporary Card)
                        </a>
                    @endcardmode
                @endcardaccess
                <a href="{{ route('students.export.excel') }}" class="nx-btn nx-btn-success">
                    <i class="bi bi-file-earmark-excel-fill"></i> Excel
                </a>
                <a href="{{ route('students.export.pdf') }}" class="nx-btn nx-btn-danger">
                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                </a>
                @if (Route::has('students.export.qr-details-pdf'))
                    <a href="{{ route('students.export.qr-details-pdf') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-qr-code"></i> QR Details PDF
                    </a>
                @endif
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('students.index') }}">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="form-control custom-input"
                                placeholder="Search name / mobile / QR / custom ID..."
                                value="{{ request('search') }}">
                        </div>
                    </div>

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

                    <div class="col-lg-2">
                        <button type="submit" class="nx-btn nx-btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>

                    <div class="col-lg-2">
                        <a href="{{ route('students.index') }}" class="nx-btn nx-btn-light w-100">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table premium-table mb-0">
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
                    @forelse ($students as $student)
                        @php
                            $expireDate = $student->temporary_qr_code_expire_date;
                            $daysLeft = $expireDate ? now()->diffInDays($expireDate, false) : null;
                            $studentImage = $student->img_url
                                ? asset('storage/' . $student->img_url)
                                : asset('images/default-student.png');
                        @endphp

                        <tr>
                            <td class="row-number">
                                {{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $studentImage }}" class="student-avatar"
                                        onerror="this.src='{{ asset('images/default-student.png') }}'">
                                    <div>
                                        <div class="cell-title">{{ $student->full_name }}</div>
                                        <small class="cell-sub">{{ $student->initial_name }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="cell-title">{{ $student->mobile }}</div>
                                <small class="cell-sub">Guardian: {{ $student->guardian_mobile }}</small>
                            </td>

                            <td>
                                <div class="cell-title">{{ $student->grade->grade_name ?? 'N/A' }}</div>
                                <span class="nx-badge nx-badge-blue mt-1">{{ ucfirst($student->class_type) }}</span>
                            </td>

                            <td>
                                @if ($student->permanent_qr_active == 1)
                                    <span class="nx-badge nx-badge-green mb-1">Permanent QR</span>
                                    <div class="cell-title">{{ $student->custom_id }}</div>
                                @else
                                    <span class="nx-badge nx-badge-amber mb-1">Temporary QR</span>
                                    <div class="cell-title">{{ $student->temporary_qr_code }}</div>

                                    @if ($daysLeft < 0)
                                        <small class="cell-sub text-danger fw-semibold d-block">Expired</small>
                                    @elseif ($daysLeft == 0)
                                        <small class="cell-sub text-warning fw-semibold d-block">Expires Today</small>
                                    @else
                                        <small class="cell-sub d-block">{{ $daysLeft }} days left</small>
                                    @endif
                                @endif
                            </td>

                            <td>
                                @if ($student->student_disable)
                                    <span class="nx-badge nx-badge-red">Disabled</span>
                                @elseif ($student->is_active)
                                    <span class="nx-badge nx-badge-green">Active</span>
                                @else
                                    <span class="nx-badge nx-badge-gray">Inactive</span>
                                @endif

                                @if ($student->admission)
                                    <span class="nx-badge nx-badge-blue mt-1 d-block">Admission Paid</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="{{ route('students.show', $student) }}" class="action-btn" title="View" aria-label="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="{{ route('students.edit', $student) }}" class="action-btn" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="{{ route('students.toggle-active', $student) }}"
                                        onsubmit="return confirm('Change active status?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="action-btn" title="Toggle Active" aria-label="Toggle Active">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>

                                    @if (auth()->user()->userType &&
                                        in_array(auth()->user()->userType->code, ['ADMIN', 'BRANCH_ADMIN'], true))
                                        <form method="POST" action="{{ route('students.destroy', $student) }}"
                                            onsubmit="return confirm('Delete student?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-btn-danger" title="Delete" aria-label="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-table">
                                <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                                <strong class="d-block">No students found</strong>
                                <span class="cell-sub">Try adjusting your search filters</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $students->links() }}
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Students — page-specific styles only.
       All shared styles come from layouts/partials/nexora-theme.blade.php
       ============================================================ */

    .dashboard-page .student-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--nx-border);
        flex-shrink: 0;
    }
</style>
@endpush