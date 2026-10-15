@extends('layouts.app')

@section('title', 'Teachers')
@section('page-title', 'Teachers')

@section('content')
    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Teachers</h1>
                    <p class="hero-subtitle">Teacher directory, status and banking details.</p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['total'] ?? $teachers->total() }}</span>
                            <span class="mini-stat-label">Total Teachers</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['active'] ?? 0 }}</span>
                            <span class="mini-stat-label">Active</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['bank_linked'] ?? 0 }}</span>
                            <span class="mini-stat-label">Bank Linked</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['inactive'] ?? 0 }}</span>
                            <span class="mini-stat-label">Inactive</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-date-time">
                        <div class="date-box">
                            <i class="bi bi-person-badge"></i>
                            <span>Teacher Directory</span>
                        </div>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- KPI CARDS --}}
        {{-- ===================================================== --}}
        <div class="stats-grid mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-person-badge-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Teachers</span>
                    <h2 class="stat-number">{{ $stats['total'] ?? $teachers->total() }}</h2>
                    <span class="stat-change neutral">
                        All registered <span class="stat-change-note">teachers</span>
                    </span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Active Teachers</span>
                    <h2 class="stat-number">{{ $stats['active'] ?? 0 }}</h2>
                    <span class="stat-change up">
                        Currently <span class="stat-change-note">active</span>
                    </span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-bank2"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Bank Linked</span>
                    <h2 class="stat-number">{{ $stats['bank_linked'] ?? 0 }}</h2>
                    <span class="stat-change neutral">
                        With <span class="stat-change-note">bank details</span>
                    </span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-pause-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Inactive Teachers</span>
                    <h2 class="stat-number">{{ $stats['inactive'] ?? 0 }}</h2>
                    <span class="stat-change down">
                        Not <span class="stat-change-note">active</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- BRANCH SUMMARY --}}
        {{-- ===================================================== --}}
        @if (!empty($stats['branches']))
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-diagram-3 text-primary"></i> Branches</h5>
                        <p class="nx-card-subtitle">Teacher count per institute branch</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th class="text-end">Teachers</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['branches'] as $branch)
                                <tr>
                                    <td class="cell-title">
                                        <i class="bi bi-building me-1 text-muted"></i>
                                        {{ $branch->name }}
                                    </td>
                                    <td class="text-end"><strong>{{ $branch->teachers_count }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- MAIN CARD --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-people-fill text-primary"></i> Teachers Management</h5>
                    <p class="nx-card-subtitle">Teacher records, status and banking details</p>
                </div>

                <div class="header-buttons">
                    <a href="{{ route('teachers.create') }}" class="nx-btn nx-btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Teacher
                    </a>
                    <a href="{{ route('teachers.exportExcel', request()->query()) }}" class="nx-btn nx-btn-success">
                        <i class="bi bi-file-earmark-excel-fill"></i> Excel
                    </a>
                    <a href="{{ route('teachers.exportPdf', request()->query()) }}" class="nx-btn nx-btn-danger">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
                    <button class="nx-btn nx-btn-light" disabled>
                        <i class="bi bi-graph-up-arrow"></i> Analytics
                    </button>
                </div>
            </div>

            {{-- SEARCH --}}
            <div class="nx-search">
                <form method="GET" action="{{ route('teachers.index') }}">
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="search-input-wrapper">
                                <i class="bi bi-search"></i>
                                <input aria-label="Search teachers" type="text" name="search" class="form-control custom-input"
                                    placeholder="Name, mobile, NIC, email or teacher ID"
                                    value="{{ request('search') }}">
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <select aria-label="Teacher status" name="is_active" class="form-select custom-input">
                                <option value="">All Status</option>
                                <option value="true" {{ request('is_active') === 'true' ? 'selected' : '' }}>Active</option>
                                <option value="false" {{ request('is_active') === 'false' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <button class="nx-btn nx-btn-primary w-100" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>

                        <div class="col-lg-2">
                            <a href="{{ route('teachers.index') }}" class="nx-btn nx-btn-light w-100">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="table-responsive">
                <table class="table premium-table mb-0 custom-table">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 50px;">#</th>
                            <th scope="col" style="width: 220px;">Teacher</th>
                            <th scope="col" style="width: 180px;">Contact</th>
                            <th scope="col" style="width: 140px;">NIC</th>
                            <th scope="col" style="width: 240px;">Bank</th>
                            <th scope="col" style="width: 240px;">Institute Branch</th>
                            <th scope="col" style="width: 120px;">Status</th>
                            <th scope="col" class="text-end" style="width: 230px;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td class="row-number">
                                    {{ $loop->iteration + ($teachers->currentPage() - 1) * $teachers->perPage() }}
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="teacher-avatar">
                                            {{ strtoupper(substr($teacher->full_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="cell-title">{{ $teacher->full_name }}</div>
                                            <small class="cell-sub d-block">{{ $teacher->initials }}</small>
                                            <span class="id-badge mt-1 d-inline-block">{{ $teacher->custom_id }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="cell-title">{{ $teacher->mobile ?? '-' }}</div>
                                    <small class="cell-sub">{{ $teacher->email ?? '-' }}</small>
                                </td>

                                <td>
                                    <span class="cell-title">{{ $teacher->nic ?? '-' }}</span>
                                </td>

                                <td>
                                    <div class="cell-title">
                                        {{ $teacher->bankBranch->bank->bank_name ?? 'N/A' }}
                                    </div>
                                    <small class="cell-sub d-block">
                                        Branch: {{ $teacher->bankBranch->branch_name ?? 'No branch selected' }}
                                    </small>
                                    @if ($teacher->account_number)
                                        <small class="cell-sub d-block mt-1">
                                            <i class="bi bi-credit-card" style="font-size: 0.7rem;"></i>
                                            Acct:
                                            <span class="account-number">{{ $teacher->account_number }}</span>
                                        </small>
                                    @endif
                                </td>

                                <td>
                                    @if ($teacher->branches && $teacher->branches->isNotEmpty())
                                        <div class="cell-title">
                                            <i class="bi bi-building" style="font-size: 0.75rem;"></i>
                                            {{ $teacher->branches->pluck('name')->implode(', ') }}
                                        </div>
                                        <div class="mt-1">
                                            @foreach ($teacher->branches as $branch)
                                                @if ($branch->code)
                                                    <span class="id-badge">{{ $branch->code }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="cell-sub fst-italic">No branch assigned</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($teacher->is_active)
                                        <span class="nx-badge nx-badge-green">Active</span>
                                    @else
                                        <span class="nx-badge nx-badge-gray">Inactive</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="{{ route('teachers.show', $teacher) }}" class="action-btn" title="View" aria-label="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <a href="{{ route('teachers.edit', $teacher) }}" class="action-btn" title="Edit" aria-label="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="{{ route('teachers.loginDetails', $teacher) }}" class="action-btn" title="Login Details" aria-label="Login Details">
                                            <i class="bi bi-key-fill"></i>
                                        </a>

                                        <form method="POST" action="{{ route('teachers.toggleActive', $teacher) }}"
                                            onsubmit="return confirm('Change status?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="action-btn" title="Change Status" aria-label="Change Status">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>

                                        @if (Route::has('teacher-salaries.show'))
                                            <a href="{{ route('teacher-salaries.show', [
                                                'teacher' => $teacher->id,
                                                'year' => now()->year,
                                                'month' => now()->month,
                                            ]) }}" class="action-btn" title="Teacher Salary Report" aria-label="Teacher Salary Report">
                                                <i class="bi bi-cash-stack"></i>
                                            </a>
                                        @endif

                                        <form method="POST" action="{{ route('teachers.destroy', $teacher) }}"
                                            onsubmit="return confirm('Delete this teacher?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-btn-danger" title="Delete" aria-label="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="empty-table">
                                    <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                                    <strong class="d-block">No Teachers Found</strong>
                                    <span class="cell-sub">Try adjusting search filters</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="p-3 border-top">
                {{ $teachers->appends(request()->query())->links() }}
            </div>
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Teachers — page-specific styles only.
       All shared styles (.dashboard-page, .nx-card, .nx-btn, etc.)
       come from layouts/partials/nexora-theme.blade.php
       ============================================================ */

    /* ---------- Teacher avatar ---------- */
    .dashboard-page .teacher-avatar {
        width: 38px; height: 38px;
        display: grid; place-items: center;
        border-radius: 50%;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        flex-shrink: 0;
        font-size: 14px;
        font-weight: 600;
    }

    /* ---------- Account number ---------- */
    .dashboard-page .account-number {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-weight: 500;
        color: var(--nx-text);
    }
</style>
@endpush