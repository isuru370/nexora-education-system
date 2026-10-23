@extends('layouts.app')

@section('title', 'Class Schedules')
@section('page-title', 'Class Schedules')

@section('content')
    @php
        $canManageSchedules = strtoupper((string) optional(optional(auth()->user())->userType)->code) !== 'TEACHER';
    @endphp
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- HERO --}}
    {{-- ===================================================== --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Class Schedules</h1>
                <p class="hero-subtitle">Manage ongoing classes and category-wise scheduling.</p>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-category-fees.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-cash-stack"></i> Class Category Options
                    </a>
                    <button type="button" class="nx-btn nx-btn-light" disabled>
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- FILTERS --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Filters</h5>
                <p class="nx-card-subtitle">Filter by branch, class or search</p>
            </div>
        </div>

        <div class="p-3">
            <form method="GET" action="{{ route('class-schedules.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="nx-label">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-select nx-input">
                            <option value="">All Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="nx-label">Class</label>
                        <select name="student_class_id" id="student_class_id" class="form-select nx-input">
                            <option value="">All Classes</option>
                            @foreach ($filterClasses as $filterClass)
                                <option value="{{ $filterClass->id }}"
                                    data-branch-id="{{ $filterClass->branch_id }}"
                                    {{ (string) request('student_class_id') === (string) $filterClass->id ? 'selected' : '' }}>
                                    {{ $filterClass->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="nx-label">Search</label>
                        <input type="text" name="search" class="form-control nx-input"
                            value="{{ request('search', '') }}"
                            placeholder="Class, grade, subject, teacher...">
                    </div>

                    <div class="col-md-1">
                        <label class="nx-label">Show</label>
                        <select name="per_page" class="form-select nx-input">
                            @foreach ([10, 25, 50, 100] as $count)
                                <option value="{{ $count }}"
                                    {{ (int) request('per_page', 10) === $count ? 'selected' : '' }}>
                                    {{ $count }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="nx-btn nx-btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="{{ route('class-schedules.index') }}" class="nx-btn nx-btn-light w-100 mt-2">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const branchSelect = document.getElementById('branch_id');
        const classSelect = document.getElementById('student_class_id');

        function filterClasses() {
            const branchId = branchSelect.value;
            const selectedClass = classSelect.value;

            Array.from(classSelect.options).forEach(function (option) {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                option.hidden = branchId !== '' &&
                    option.dataset.branchId !== branchId;
            });

            if (
                selectedClass &&
                classSelect.selectedOptions[0]?.hidden
            ) {
                classSelect.value = '';
            }
        }

        branchSelect.addEventListener('change', filterClasses);
        filterClasses();
    });
    </script>

    {{-- ===================================================== --}}
    {{-- CLASSES --}}
    {{-- ===================================================== --}}
    <div class="row g-4">

        @forelse($classes as $class)
            @php
                $gradeName = optional($class->grade)->grade_name ?? 'N/A';
                $subjectName = optional($class->subject)->subject_name ?? 'N/A';
                $teacherName = optional($class->teacher)->full_name ?? 'N/A';
            @endphp

            <div class="col-12">
                <div class="class-card">

                    {{-- CLASS HEADER --}}
                    <div class="class-card-header">
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <h5 class="class-card-title">{{ $class->class_name }}</h5>
                                <span class="nx-badge nx-badge-green">Ongoing</span>
                            </div>

                            <div class="class-meta">
                                Grade: {{ $gradeName }}
                                • Subject: {{ $subjectName }}
                                • Medium: {{ $class->medium ?? 'N/A' }}
                            </div>

                            <div class="class-meta">Teacher: {{ $teacherName }}</div>
                        </div>

                        <div class="class-stats">
                            <div class="stat-box">
                                <div class="stat-value">{{ $class->categoryFees->count() }}</div>
                                <div class="stat-label">Categories</div>
                            </div>
                        </div>
                    </div>

                    {{-- CLASS BODY --}}
                    <div class="class-card-body">
                        @if ($class->categoryFees->count())
                            <div class="row g-3">
                                @foreach ($class->categoryFees as $fee)
                                    @php
                                        $category = $fee->category;
                                        $feeOptions = $fee->activeFeeOptions;
                                    @endphp

                                    <div class="col-xl-4 col-lg-6 col-md-6">
                                        <div class="category-card">

                                            {{-- CATEGORY --}}
                                            <div class="category-card-top">
                                                <div>
                                                    <div class="category-name">
                                                        {{ optional($category)->category_name ?? 'N/A' }}
                                                    </div>
                                                    <div class="category-code">
                                                        @if (optional($category)->code)
                                                            {{ $category->code }}
                                                        @else
                                                            No code
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- FEE OPTIONS --}}
                                            <div>
                                                @if ($feeOptions->count())
                                                    <div class="fee-options-list">
                                                        @foreach ($feeOptions as $option)
                                                            <div class="fee-option-item">
                                                                <div>
                                                                    <div class="fee-option-label">{{ $option->label }}</div>
                                                                    @if ($option->note)
                                                                        <div class="fee-option-note">{{ $option->note }}</div>
                                                                    @endif
                                                                </div>
                                                                <div class="fee-option-amount">
                                                                    LKR {{ number_format($option->fee, 2) }}
                                                                    @if ($option->is_default)
                                                                        <span class="fee-default-badge">Default</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="cell-sub">No active fee options</div>
                                                @endif
                                            </div>

                                            {{-- ACTIONS --}}
                                            <div class="category-card-bottom">
                                                <div class="cell-sub">Category schedule</div>

                                                <div class="action-group">
                                                    @if ($canManageSchedules)
                                                    <a href="{{ route('class-schedules.create', [
                                                        'student_class_id' => $class->id,
                                                        'class_category_fee_id' => $fee->id,
                                                    ]) }}" class="icon-btn icon-btn-primary" title="Schedule this category" aria-label="Schedule this category">
                                                        <i class="bi bi-calendar-plus"></i>
                                                    </a>
                                                    @endif

                                                    <a href="{{ route('class-schedules.categorySchedules', [
                                                        'student_class_id' => $class->id,
                                                        'class_category_fee_id' => $fee->id,
                                                    ]) }}" class="icon-btn icon-btn-success" title="View schedules" aria-label="View schedules">
                                                        <i class="bi bi-eye"></i>
                                                    </a>

                                                    @if ($canManageSchedules)
                                                    <a href="{{ route('class-schedules.bulkEdit', [
                                                        'student_class_id' => $class->id,
                                                        'class_category_fee_id' => $fee->id,
                                                    ]) }}" class="icon-btn icon-btn-warning" title="Bulk Update Schedule" aria-label="Bulk Update Schedule">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-box">
                                <i class="bi bi-folder-x fs-2 d-block mb-2"></i>
                                <div class="cell-title">No categories found</div>
                                <div class="cell-sub">This class does not have any assigned category yet.</div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-table">
                    <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                    <strong class="d-block">No ongoing classes found</strong>
                    <span class="cell-sub">Try a different search term.</span>
                </div>
            </div>
        @endforelse

    </div>

    {{-- PAGINATION --}}
    <div class="p-3 border-top d-flex justify-content-end">
        {{ $classes->appends(request()->query())->links() }}
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Class Schedules — page-specific styles only.
       ============================================================ */

    .dashboard-page .class-card {
        background: #fff;
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius);
        box-shadow: 0 1px 2px rgba(20, 28, 58, .04);
        overflow: hidden;
    }

    .dashboard-page .class-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--nx-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .dashboard-page .class-card-title {
        font-size: .98rem;
        font-weight: 700;
        margin: 0;
        color: var(--nx-text);
    }

    .dashboard-page .class-meta {
        color: var(--nx-muted);
        font-size: .82rem;
        margin-bottom: .1rem;
    }

    .dashboard-page .class-card-body { padding: 1.25rem; }

    .dashboard-page .class-stats {
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
    }

    .dashboard-page .stat-box {
        min-width: 110px;
        padding: .9rem 1rem;
        border-radius: 10px;
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        text-align: center;
    }
    .dashboard-page .stat-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: var(--nx-text);
        line-height: 1;
    }
    .dashboard-page .stat-label {
        font-size: .75rem;
        color: var(--nx-muted);
        margin-top: .25rem;
        font-weight: 600;
    }

    /* Category card */
    .dashboard-page .category-card {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        padding: 1rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 1rem;
        transition: box-shadow .15s, border-color .15s;
    }
    .dashboard-page .category-card:hover {
        border-color: var(--nx-blue);
        box-shadow: 0 4px 12px rgba(37, 99, 235, .08);
    }

    .dashboard-page .category-card-top {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
    }

    .dashboard-page .category-name {
        font-weight: 800;
        font-size: .95rem;
        color: var(--nx-text);
    }
    .dashboard-page .category-code {
        font-size: .78rem;
        color: var(--nx-muted);
        margin-top: .2rem;
    }

    /* Fee options */
    .dashboard-page .fee-options-list {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
    .dashboard-page .fee-option-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        padding: .6rem .75rem;
        border-radius: 8px;
        background: #fff;
        border: 1px solid var(--nx-border);
    }
    .dashboard-page .fee-option-label {
        font-size: .82rem;
        font-weight: 700;
        color: var(--nx-text);
    }
    .dashboard-page .fee-option-note {
        font-size: .7rem;
        color: var(--nx-muted);
        margin-top: .15rem;
    }
    .dashboard-page .fee-option-amount {
        font-size: .82rem;
        font-weight: 800;
        color: var(--nx-blue);
        white-space: nowrap;
    }
    .dashboard-page .fee-default-badge {
        display: inline-block;
        margin-left: 4px;
        padding: 1px 5px;
        border-radius: 4px;
        background: var(--nx-green-soft);
        color: var(--nx-green);
        font-size: .6rem;
        font-weight: 700;
    }

    /* Category card bottom */
    .dashboard-page .category-card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
    }
    .dashboard-page .action-group {
        display: flex;
        gap: .5rem;
    }

    .dashboard-page .icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--nx-border);
        background: #fff;
        color: var(--nx-muted);
        font-size: .85rem;
        transition: all .15s ease;
        text-decoration: none;
    }
    .dashboard-page .icon-btn:hover {
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        border-color: var(--nx-blue);
    }
    .dashboard-page .icon-btn-primary {
        background: var(--nx-blue); color: #fff; border-color: var(--nx-blue);
    }
    .dashboard-page .icon-btn-primary:hover {
        background: #1d4ed8; color: #fff; border-color: #1d4ed8;
    }
    .dashboard-page .icon-btn-success { color: var(--nx-green); }
    .dashboard-page .icon-btn-success:hover {
        background: var(--nx-green-soft); color: var(--nx-green); border-color: var(--nx-green);
    }
    .dashboard-page .icon-btn-warning { color: var(--nx-amber); }
    .dashboard-page .icon-btn-warning:hover {
        background: var(--nx-amber-soft); color: var(--nx-amber); border-color: var(--nx-amber);
    }

    /* Empty box */
    .dashboard-page .empty-box {
        padding: 2rem;
        text-align: center;
        color: var(--nx-muted);
        background: #fafbfd;
        border: 1px dashed var(--nx-border);
        border-radius: 8px;
    }

    @media (max-width: 768px) {
        .dashboard-page .class-card-header {
            flex-direction: column;
            align-items: stretch;
        }
        .dashboard-page .category-card-top,
        .dashboard-page .category-card-bottom {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endpush