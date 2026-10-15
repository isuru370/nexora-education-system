@extends('layouts.app')

@section('title', 'Student Class Enrollments')
@section('page-title', 'Student Class Enrollments')

@section('content')
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- HERO --}}
    {{-- ===================================================== --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Student Class Enrollments</h1>
                <p class="hero-subtitle">Manage class-wise enrollment, fee options and student counts.</p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $classes->total() }}</span>
                        <span class="mini-stat-label">Total Classes</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $classes->sum('total_students_count') }}</span>
                        <span class="mini-stat-label">Enrollments</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $classes->sum('active_students_count') }}</span>
                        <span class="mini-stat-label">Active</span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-date-time">
                    <div class="date-box">
                        <i class="bi bi-people-fill"></i>
                        <span>Enrollment Directory</span>
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
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Classes</span>
                <h2 class="stat-number">{{ $classes->total() }}</h2>
                <span class="stat-change neutral">All <span class="stat-change-note">classes</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Enrollments</span>
                <h2 class="stat-number">{{ $classes->sum('total_students_count') }}</h2>
                <span class="stat-change up">All <span class="stat-change-note">enrollments</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Active Students</span>
                <h2 class="stat-number">{{ $classes->sum('active_students_count') }}</h2>
                <span class="stat-change up">Currently <span class="stat-change-note">active</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-red"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Inactive Students</span>
                <h2 class="stat-number">{{ $classes->sum('inactive_students_count') }}</h2>
                <span class="stat-change down">Not <span class="stat-change-note">active</span></span>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MAIN CARD --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-people-fill text-primary"></i> Student Class Enrollments</h5>
                <p class="nx-card-subtitle">Manage class-wise enrollment, fee options and student counts</p>
            </div>

            <div class="header-buttons">
                <a href="{{ route('student-class-enrollments.create') }}" class="nx-btn nx-btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Enrollment
                </a>
                <button type="button" class="nx-btn nx-btn-light" disabled>
                    <i class="bi bi-file-earmark-excel-fill"></i> Export
                </button>
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('student-class-enrollments.index') }}">
                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="form-control custom-input"
                                placeholder="Search by class, grade, subject, teacher..."
                                value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-lg-2">
                        <select name="per_page" class="form-select custom-input">
                            @foreach ([10, 20, 50, 100] as $count)
                                <option value="{{ $count }}"
                                    {{ (int) request('per_page', 10) === $count ? 'selected' : '' }}>
                                    {{ $count }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-1">
                        <button type="submit" class="nx-btn nx-btn-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-lg-1">
                        <a href="{{ route('student-class-enrollments.index') }}" class="nx-btn nx-btn-light w-100">
                            Reset
                        </a>
                    </div>
                </div>

                @if (request('search') || request('per_page'))
                    <div class="mt-3">
                        <a href="{{ route('student-class-enrollments.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-x-circle"></i> Clear Filter
                        </a>
                    </div>
                @endif
            </form>
        </div>

        {{-- CLASSES LIST --}}
        @forelse($classes as $class)

            <div class="class-card mb-4">

                {{-- CLASS HEADER --}}
                <div class="class-card-header">
                    <div>
                        <h5 class="class-card-title">
                            {{ $class->class_name }}

                            @if ($class->branch)
                                <span class="nx-badge nx-badge-blue ms-2">
                                    <i class="bi bi-building"></i> {{ $class->branch->name }}
                                </span>
                            @endif
                        </h5>

                        <div class="class-meta">
                            Grade: {{ optional($class->grade)->grade_name ?? '-' }}
                            <span class="meta-sep">|</span>
                            Subject: {{ optional($class->subject)->subject_name ?? '-' }}
                            <span class="meta-sep">|</span>
                            Teacher: {{ optional($class->teacher)->initials ?? '-' }}
                        </div>
                    </div>

                    <div class="class-badges">
                        <span class="nx-badge nx-badge-blue">Total: {{ $class->total_students_count }}</span>
                        <span class="nx-badge nx-badge-green">Active: {{ $class->active_students_count }}</span>
                        <span class="nx-badge nx-badge-gray">Inactive: {{ $class->inactive_students_count }}</span>
                    </div>
                </div>

                {{-- CATEGORY TABLE --}}
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Fee Options</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Active</th>
                                <th class="text-center">Inactive</th>
                                <th class="text-center">Free Card</th>
                                <th class="text-center">Status</th>
                                <th width="120" class="text-end">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($class->categoryFees as $fee)
                                @php
                                    $classStats = isset($categoryStats) ? $categoryStats->get($class->id) : null;
                                    $stats = $classStats
                                        ? $classStats->firstWhere('class_category_fee_id', $fee->id)
                                        : null;
                                    $feeOptions = $fee->activeFeeOptions ?? ($fee->feeOptions ?? collect());
                                @endphp

                                <tr>
                                    {{-- CATEGORY --}}
                                    <td>
                                        <span class="cell-title">
                                            {{ optional($fee->category)->category_name ?? '-' }}
                                        </span>

                                        @if (optional($fee->category)->code)
                                            <span class="id-badge ms-1">{{ $fee->category->code }}</span>
                                        @endif

                                        @if (!$fee->is_active)
                                            <span class="nx-badge nx-badge-gray ms-1">Inactive</span>
                                        @endif
                                    </td>

                                    {{-- FEE OPTIONS --}}
                                    <td>
                                        @if ($feeOptions->count())
                                            <div class="fee-options-list">
                                                @foreach ($feeOptions as $option)
                                                    <div class="fee-option-item">
                                                        <span class="fee-option-label">{{ $option->label }}</span>
                                                        <span class="fee-option-price">
                                                            Rs. {{ number_format((float) $option->fee, 2) }}
                                                        </span>
                                                        @if ($option->is_default)
                                                            <span class="fee-default-badge">Default</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="cell-sub">No fee options</span>
                                        @endif
                                    </td>

                                    {{-- TOTAL --}}
                                    <td class="text-center">
                                        <span class="nx-badge nx-badge-blue">{{ $stats ? $stats->total_count : 0 }}</span>
                                    </td>

                                    {{-- ACTIVE --}}
                                    <td class="text-center">
                                        <span class="nx-badge nx-badge-green">{{ $stats ? $stats->active_count : 0 }}</span>
                                    </td>

                                    {{-- INACTIVE --}}
                                    <td class="text-center">
                                        <span class="nx-badge nx-badge-gray">{{ $stats ? $stats->inactive_count : 0 }}</span>
                                    </td>

                                    {{-- FREE CARD --}}
                                    <td class="text-center">
                                        <span class="nx-badge nx-badge-amber">{{ $stats ? $stats->free_card_count : 0 }}</span>
                                    </td>

                                    {{-- STATUS --}}
                                    <td class="text-center">
                                        @if ($fee->is_active)
                                            <span class="nx-badge nx-badge-green">Active</span>
                                        @else
                                            <span class="nx-badge nx-badge-gray">Inactive</span>
                                        @endif
                                    </td>

                                    {{-- ACTION --}}
                                    <td class="text-end">
                                        @if ($fee->category)
                                            <div class="action-buttons">
                                                <a href="{{ route('student-class-enrollments.category-students', [
                                                    'studentClass' => $class->id,
                                                    'classCategory' => $fee->category->id,
                                                ]) }}" class="action-btn" title="View students" aria-label="View students">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                            </div>
                                        @else
                                            <span class="cell-sub">—</span>
                                        @endif
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="8" class="empty-table">
                                        <i class="bi bi-people fs-2 d-block mb-2"></i>
                                        <strong class="d-block">No Categories Found</strong>
                                        <span class="cell-sub">This class has no category fees yet</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

        @empty
            <div class="empty-table">
                <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                <strong class="d-block">No enrolled classes found.</strong>
            </div>
        @endforelse

        {{-- PAGINATION --}}
        <div class="p-3 border-top">
            {{ $classes->appends(request()->query())->links() }}
        </div>

    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Student Class Enrollments — page-specific styles only.
       ============================================================ */

    .dashboard-page .class-card {
        padding: 1.25rem;
        border-bottom: 1px solid var(--nx-border);
        background: #fff;
    }
    .dashboard-page .class-card:last-child { border-bottom: 0; }

    .dashboard-page .class-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .dashboard-page .class-card-title {
        font-size: .95rem;
        font-weight: 700;
        margin: 0 0 .25rem;
        color: var(--nx-text);
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .dashboard-page .class-meta {
        color: var(--nx-muted);
        font-size: .82rem;
    }
    .dashboard-page .meta-sep { margin: 0 .35rem; }

    .dashboard-page .class-badges {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }

    /* Fee options */
    .dashboard-page .fee-options-list {
        display: flex;
        flex-direction: column;
        gap: .45rem;
        min-width: 280px;
    }

    .dashboard-page .fee-option-item {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        padding: .55rem .7rem;
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
    }

    .dashboard-page .fee-option-label {
        font-weight: 600;
        color: var(--nx-text);
        font-size: .8rem;
    }

    .dashboard-page .fee-option-price {
        color: var(--nx-text);
        font-weight: 600;
        font-size: .8rem;
        margin-left: auto;
    }

    .dashboard-page .fee-default-badge {
        font-size: .62rem;
        padding: .15rem .4rem;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        border-radius: 4px;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .dashboard-page .class-card-header {
            flex-direction: column;
            align-items: stretch;
        }
        .dashboard-page .fee-options-list { min-width: 220px; }
    }
</style>
@endpush