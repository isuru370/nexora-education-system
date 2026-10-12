@extends('layouts.app')

@section('title', 'Teachers')
@section('page-title', 'Teachers')

@section('content')
    <div class="teachers-page">

        {{-- =========================================================
        STATS CARDS
    ========================================================== --}}
        <div class="row g-4 mb-4">

            {{-- TOTAL --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $stats['total'] ?? $teachers->total() }}</h3>
                        <p>Total Teachers</p>
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
                        <h3>{{ $stats['active'] ?? 0 }}</h3>
                        <p>Active Teachers</p>
                    </div>
                </div>
            </div>

            {{-- BANK LINKED --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="bi bi-bank2"></i>
                    </div>
                    <div>
                        <h3>{{ $stats['bank_linked'] ?? 0 }}</h3>
                        <p>Bank Linked</p>
                    </div>
                </div>
            </div>

            {{-- INACTIVE --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="bi bi-pause-circle-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $stats['inactive'] ?? 0 }}</h3>
                        <p>Inactive Teachers</p>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                @foreach ($stats['branches'] as $branch)
                    <div class="col-xl-3 col-md-6">
                        <div class="stats-card">
                            <div class="stats-icon blue">
                                <i class="bi bi-building"></i>
                            </div>

                            <div>
                                <h3>{{ $branch->teachers_count }}</h3>
                                <p>{{ $branch->name }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>


        {{-- =========================================================
        MAIN CARD
    ========================================================== --}}
        <div class="main-card">

            {{-- HEADER --}}
            <div class="main-card-header">
                <div>
                    <h4>Teachers Management</h4>
                    <p>Manage teacher records, status and banking details</p>
                </div>

                {{-- BUTTONS --}}
                <div class="header-buttons">
                    <a href="{{ route('teachers.create') }}" class="btn btn-primary custom-btn">
                        <i class="bi bi-plus-lg"></i>
                        Add Teacher
                    </a>

                    <a href="{{ route('teachers.exportExcel', request()->query()) }}" class="btn btn-success custom-btn">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                        Excel
                    </a>

                    <a href="{{ route('teachers.exportPdf', request()->query()) }}" class="btn btn-danger custom-btn">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                        PDF
                    </a>

                    <button class="btn btn-light border custom-btn" disabled>
                        <i class="bi bi-graph-up-arrow"></i>
                        Analytics
                    </button>
                </div>
            </div>


            {{-- =====================================================
            SEARCH
        ====================================================== --}}
            <div class="search-card">
                <form method="GET" action="{{ route('teachers.index') }}">
                    <div class="row g-3">

                        {{-- SEARCH --}}
                        <div class="col-lg-5">
                            <div class="search-input-wrapper">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" class="form-control custom-input"
                                    placeholder="Search name / mobile / NIC / email / custom ID..."
                                    value="{{ request('search') }}">
                            </div>
                        </div>

                        {{-- STATUS --}}
                        <div class="col-lg-3">
                            <select name="is_active" class="form-select custom-input">
                                <option value="">All Status</option>
                                <option value="true" {{ request('is_active') === 'true' ? 'selected' : '' }}>Active
                                </option>
                                <option value="false" {{ request('is_active') === 'false' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>

                        {{-- SEARCH BUTTON --}}
                        <div class="col-lg-2">
                            <button class="btn btn-primary w-100 custom-btn" type="submit">
                                <i class="bi bi-search"></i>
                                Search
                            </button>
                        </div>

                        {{-- RESET --}}
                        <div class="col-lg-2">
                            <a href="{{ route('teachers.index') }}" class="btn btn-light border w-100 custom-btn">
                                Reset
                            </a>
                        </div>

                    </div>
                </form>
            </div>


            {{-- =====================================================
            TABLE (HORIZONTAL SCROLL ENABLED)
        ====================================================== --}}
            <div class="table-scroll-wrapper">
                <table class="table custom-table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 220px;">Teacher</th>
                            <th style="width: 180px;">Contact</th>
                            <th style="width: 140px;">NIC</th>
                            <th style="width: 240px;">Bank</th>
                            <th style="width: 240px;">Institute Branch</th>
                            <th style="width: 120px;">Status</th>
                            <th class="text-end" style="width: 230px;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                {{-- NUMBER --}}
                                <td class="row-number">
                                    {{ $loop->iteration + ($teachers->currentPage() - 1) * $teachers->perPage() }}
                                </td>

                                {{-- TEACHER --}}
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="teacher-avatar">
                                            {{ strtoupper(substr($teacher->full_name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="teacher-name">
                                                {{ $teacher->full_name }}
                                            </div>
                                            <small class="text-muted d-block">
                                                {{ $teacher->initials }}
                                            </small>
                                            <span class="badge custom-badge bg-light text-dark border mt-2">
                                                {{ $teacher->custom_id }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- CONTACT --}}
                                <td>
                                    <div class="fw-semibold">
                                        {{ $teacher->mobile ?? '-' }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $teacher->email ?? '-' }}
                                    </small>
                                </td>

                                {{-- NIC --}}
                                <td>
                                    <span class="fw-semibold">
                                        {{ $teacher->nic ?? '-' }}
                                    </span>
                                </td>

                                {{-- BANK --}}
                                <td>
                                    <div class="fw-semibold">
                                        {{ $teacher->bankBranch->bank->bank_name ?? 'N/A' }}
                                    </div>
                                    <small class="text-muted d-block">
                                        Bank Branch:
                                        {{ $teacher->bankBranch->branch_name ?? 'No branch selected' }}
                                    </small>
                                    @if ($teacher->account_number)
                                        <small class="text-muted d-block mt-1">
                                            <i class="bi bi-credit-card" style="font-size: 0.7rem;"></i>
                                            Acct:
                                            <span style="font-family: monospace; font-weight: 600; color: #2563eb;">
                                                {{ $teacher->account_number }}
                                            </span>
                                        </small>
                                    @endif
                                </td>

                                {{-- INSTITUTE BRANCH --}}
                                <td>
                                    @if ($teacher->branches && $teacher->branches->isNotEmpty())
                                        <div class="fw-semibold" style="color: #2563eb;">
                                            <i class="bi bi-building" style="font-size: 0.75rem;"></i>
                                            {{ $teacher->branches->pluck('name')->implode(', ') }}
                                        </div>
                                        <div class="mt-1">
                                            @foreach ($teacher->branches as $branch)
                                                @if ($branch->code)
                                                    <span class="badge"
                                                        style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 0.65rem; font-family: monospace; padding: 3px 7px; margin: 1px 2px;">
                                                        {{ $branch->code }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">
                                            No branch assigned
                                        </span>
                                    @endif
                                </td>

                                {{-- STATUS --}}
                                <td>
                                    @if ($teacher->is_active)
                                        <span class="badge bg-success custom-badge">
                                            Active
                                        </span>
                                    @else
                                        <span class="badge bg-secondary custom-badge">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                {{-- ACTIONS --}}
                                <td class="text-end">
                                    <div class="action-buttons">

                                        {{-- VIEW --}}
                                        <a href="{{ route('teachers.show', $teacher) }}" class="action-btn view-btn"
                                            title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        {{-- EDIT --}}
                                        <a href="{{ route('teachers.edit', $teacher) }}" class="action-btn edit-btn"
                                            title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        {{-- LOGIN DETAILS --}}
                                        <a href="{{ route('teachers.loginDetails', $teacher) }}"
                                            class="action-btn login-btn" title="Login Details">
                                            <i class="bi bi-key-fill"></i>
                                        </a>

                                        {{-- TOGGLE --}}
                                        <form method="POST" action="{{ route('teachers.toggleActive', $teacher) }}"
                                            onsubmit="return confirm('Change status?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="action-btn toggle-btn" title="Change Status">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>

                                        {{-- SALARY --}}
                                        @if (Route::has('teacher-salaries.show'))
                                            <a href="{{ route('teacher-salaries.show', [
                                                'teacher' => $teacher->id,
                                                'year' => now()->year,
                                                'month' => now()->month,
                                            ]) }}"
                                                class="action-btn salary-btn" title="Teacher Salary Report">
                                                <i class="bi bi-cash-stack"></i>
                                            </a>
                                        @endif

                                        {{-- DELETE --}}
                                        <form method="POST" action="{{ route('teachers.destroy', $teacher) }}"
                                            onsubmit="return confirm('Delete this teacher?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn delete-btn" title="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="empty-state">
                                        <i class="bi bi-person-x"></i>
                                        <h5>No Teachers Found</h5>
                                        <p>Try adjusting search filters</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


            {{-- =====================================================
            PAGINATION
        ====================================================== --}}
            <div class="mt-4">
                {{ $teachers->links() }}
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
        .teachers-page {
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================
           STATS CARDS
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

        .blue {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }

        .green {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .orange {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }

        .red {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

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
           TABLE SCROLL WRAPPER (HORIZONTAL SCROLL)
        ============================================ */
        .table-scroll-wrapper {
            width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            border-radius: 12px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .table-scroll-wrapper::-webkit-scrollbar {
            height: 10px;
        }

        .table-scroll-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .table-scroll-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
            border: 2px solid #f1f5f9;
        }

        .table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ============================================
           TABLE (ORIGINAL TEXT SIZE)
        ============================================ */
        .custom-table {
            margin-bottom: 0;
            min-width: 1400px;
            /* ✅ Total width - scroll සඳහා */
            white-space: nowrap;
        }

        .custom-table thead th {
            border: none;
            background: #f8fafc;
            color: #475569;
            font-size: 0.82rem;
            text-transform: uppercase;
            padding: 1rem;
            font-weight: 700;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
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
            font-size: 0.9rem;
        }

        /* ============================================
           TEACHER CELL
        ============================================ */
        .teacher-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.20);
        }

        .teacher-name {
            font-weight: 700;
            color: #0f172a;
            font-size: 0.95rem;
        }

        .row-number {
            font-weight: 700;
            color: #94a3b8;
            text-align: center;
        }

        /* ============================================
           BADGES
        ============================================ */
        .custom-badge {
            border-radius: 10px;
            padding: 0.5rem 0.7rem;
            font-size: 0.75rem;
        }

        /* ============================================
           ACTION BUTTONS
        ============================================ */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            flex-wrap: wrap;
            min-width: 220px;
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
            flex-shrink: 0;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.10);
        }

        .view-btn {
            background: #eff6ff;
            color: #2563eb;
        }

        .view-btn:hover {
            background: #2563eb;
            color: #fff;
        }

        .login-btn {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .login-btn:hover {
            background: #8b5cf6;
            color: #fff;
        }

        .edit-btn {
            background: #fef3c7;
            color: #d97706;
        }

        .edit-btn:hover {
            background: #f59e0b;
            color: #fff;
        }

        .toggle-btn {
            background: #ecfdf5;
            color: #10b981;
        }

        .toggle-btn:hover {
            background: #10b981;
            color: #fff;
        }

        .salary-btn {
            background: #f3f4f6;
            color: #475569;
        }

        .salary-btn:hover {
            background: #475569;
            color: #fff;
        }

        .delete-btn {
            background: #fef2f2;
            color: #ef4444;
        }

        .delete-btn:hover {
            background: #ef4444;
            color: #fff;
        }

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

            .header-buttons a,
            .header-buttons button {
                flex: 1;
            }

            .table-scroll-wrapper {
                margin: 0 -1.5rem;
                /* Mobile එකේ full-width feel */
                padding: 0 1.5rem;
            }

            .custom-table {
                min-width: 1300px;
            }

            .custom-table tbody td {
                padding: 0.75rem 0.6rem;
                font-size: 0.85rem;
            }

            .teacher-avatar {
                width: 45px;
                height: 45px;
                font-size: 1rem;
            }

            .action-btn {
                width: 34px;
                height: 34px;
            }
        }
    </style>
@endpush
