@extends('layouts.app')

@section('title', 'Teacher Salary Report')
@section('page-title', 'Teacher Salary Report')

@section('content')
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- HERO --}}
    {{-- ===================================================== --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Teacher Salary Report</h1>
                <p class="hero-subtitle">
                    {{ $monthName }} {{ $year }}
                    @if ($branch)
                        • <i class="bi bi-building"></i> {{ $branch->name }}
                    @endif
                </p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $summary['total_teachers'] ?? count($salaryRows) }}</span>
                        <span class="mini-stat-label">Teachers</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">LKR {{ number_format($summary['net_payable'] ?? 0, 2) }}</span>
                        <span class="mini-stat-label">Net Payable</span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('teacher-salaries.index', [
                        'year' => $year,
                        'month' => $month,
                        'branch_id' => $branchId,
                    ]) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </a>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- FILTER --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Filters</h5>
                <p class="nx-card-subtitle">Filter by branch, year and month</p>
            </div>
        </div>

        <div class="p-3">
            <form method="GET" action="{{ route('teacher-salaries.index') }}" class="row g-3 align-items-end">

                @if ($branches->count() > 1)
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Branch</label>
                        <select name="branch_id" class="form-select nx-input">
                            @foreach ($branches as $branchOption)
                                <option value="{{ $branchOption->id }}" {{ (int) $branchId === (int) $branchOption->id ? 'selected' : '' }}>
                                    {{ $branchOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                @endif

                <div class="col-lg-3 col-md-6">
                    <label class="nx-label">Year</label>
                    <select name="year" class="form-select nx-input">
                        @for ($y = now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="nx-label">Month</label>
                    <select name="month" class="form-select nx-input">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <button type="submit" class="nx-btn nx-btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- SUMMARY --}}
    {{-- ===================================================== --}}
    <div class="stats-grid mb-4">

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Teachers</span>
                <h2 class="stat-number">{{ $summary['total_teachers'] ?? count($salaryRows) }}</h2>
                <span class="stat-change neutral">All <span class="stat-change-note">teachers</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-info">
                <span class="stat-label">Gross Earnings</span>
                <h2 class="stat-number">LKR {{ number_format($summary['gross_earnings'] ?? 0, 2) }}</h2>
                <span class="stat-change up">Total <span class="stat-change-note">gross earnings</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-red"><i class="bi bi-arrow-down-circle"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Deductions</span>
                <h2 class="stat-number">
                    LKR {{
                        number_format(
                            ($summary['total_advance'] ?? 0) +
                            ($summary['total_deduction'] ?? 0) +
                            ($summary['total_other'] ?? 0),
                            2
                        )
                    }}
                </h2>
                <span class="stat-change down">Advances + <span class="stat-change-note">deductions</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-wallet2"></i></div>
            <div class="stat-info">
                <span class="stat-label">Net Payable</span>
                <h2 class="stat-number">LKR {{ number_format($summary['net_payable'] ?? 0, 2) }}</h2>
                <span class="stat-change up">After <span class="stat-change-note">deductions</span></span>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- TABLE --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-people-fill text-primary"></i> Monthly Teacher Salary Summary</h5>
                <p class="nx-card-subtitle">Salary earnings and deductions overview</p>
            </div>
            <span class="nx-badge nx-badge-blue">
                <i class="bi bi-list-ul"></i> {{ count($salaryRows) }} Teachers
            </span>
        </div>

        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Teacher</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Advance</th>
                        <th class="text-end">Deduction</th>
                        <th class="text-end">Other</th>
                        <th class="text-end">Net Payable</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($salaryRows as $i => $row)
                        <tr>
                            <td class="row-number">{{ $i + 1 }}</td>

                            {{-- TEACHER --}}
                            <td>
                                <div class="teacher-cell">
                                    <div class="teacher-avatar">
                                        {{ strtoupper(substr($row['teacher_name'], 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="cell-title">{{ $row['teacher_name'] }}</div>
                                        <small class="cell-sub">{{ $row['teacher_custom_id'] }}</small>
                                    </div>
                                </div>
                            </td>

                            {{-- GROSS --}}
                            <td class="text-end cell-title">
                                {{ number_format($row['gross_earnings'] ?? 0, 2) }}
                            </td>

                            {{-- ADVANCE --}}
                            <td class="text-end cell-title" style="color: var(--nx-amber);">
                                {{ number_format($row['advance'] ?? 0, 2) }}
                            </td>

                            {{-- DEDUCTION --}}
                            <td class="text-end cell-title" style="color: var(--nx-red);">
                                {{ number_format($row['deduction'] ?? 0, 2) }}
                            </td>

                            {{-- OTHER --}}
                            <td class="text-end cell-sub">
                                {{ number_format($row['other'] ?? 0, 2) }}
                            </td>

                            {{-- NET PAYABLE --}}
                            <td class="text-end cell-title" style="color: var(--nx-green);">
                                {{ number_format($row['net_payable'] ?? 0, 2) }}
                            </td>

                            {{-- STATUS --}}
                            <td>
                                @php
                                    $status = $row['status'] ?? 'pending';
                                    $statusClass = [
                                        'paid'      => 'nx-badge-green',
                                        'approved'  => 'nx-badge-blue',
                                        'cancelled' => 'nx-badge-gray',
                                    ][$status] ?? 'nx-badge-amber';
                                @endphp

                                <span class="nx-badge {{ $statusClass }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>

                            {{-- ACTION --}}
                            <td class="text-center">
                                <div class="action-buttons justify-content-center">
                                    <a href="{{ route('teacher-salaries.show', [
                                        'teacher' => $row['teacher_id'],
                                        'year' => $year,
                                        'month' => $month,
                                        'branch_id' => $branchId,
                                    ]) }}" class="action-btn" title="View salary" aria-label="View salary">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty-table">
                                <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                                <strong class="d-block">No salary data found</strong>
                                <span class="cell-sub">Try changing the selected branch, month or year</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Teacher Salary Report — page-specific styles only.
       Shared nx-* theme comes from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .teacher-cell {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .dashboard-page .teacher-avatar {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: var(--nx-violet-soft);
        color: var(--nx-violet);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
    }
</style>
@endpush