@extends('layouts.app')

@section('title', 'Class Schedules')
@section('page-title', 'Class Schedules')

@section('content')

    <div class="schedule-dashboard">

        <div class="hero-card mb-4">

            <div class="hero-content">

                <div>

                    <h3 class="fw-bold mb-1">
                        Class Schedules
                    </h3>

                    <p class="text-muted mb-0">
                        Manage ongoing classes and category-wise scheduling
                    </p>

                </div>


                <div class="hero-actions">

                    <a
                        href="{{ route('class-category-fees.index') }}"
                        class="btn btn-outline-secondary custom-btn">

                        <i class="bi bi-cash-stack me-1"></i>

                        Class Category Options

                    </a>


                    <button
                        type="button"
                        class="btn btn-outline-dark custom-btn"
                        disabled>

                        <i class="bi bi-download me-1"></i>

                        Export

                    </button>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- FILTERS (Branch + Class + Search + Per Page) --}}
        {{-- ========================================================= --}}

        <div class="filter-card mb-4">
            <form method="GET" action="{{ route('class-schedules.index') }}">
                <div class="row g-3 align-items-end">

                    {{-- Branch filter --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">
                            Branch
                        </label>

                        <select name="branch_id"
                                id="branch_id"
                                class="form-select custom-input">
                            <option value="">All Branches</option>

                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Class filter --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">
                            Class
                        </label>

                        <select name="student_class_id"
                                id="student_class_id"
                                class="form-select custom-input">
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

                    {{-- Search --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">
                            Search
                        </label>

                        <input type="text"
                               name="search"
                               class="form-control custom-input"
                               value="{{ request('search', '') }}"
                               placeholder="Class, grade, subject, teacher...">
                    </div>

                    {{-- Per page --}}
                    <div class="col-md-1">
                        <label class="form-label small fw-semibold">
                            Show
                        </label>

                        <select name="per_page" class="form-select custom-input">
                            @foreach ([10, 25, 50, 100] as $count)
                                <option value="{{ $count }}"
                                    {{ (int) request('per_page', 10) === $count ? 'selected' : '' }}>
                                    {{ $count }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Buttons --}}
                    <div class="col-md-2">
                        <button type="submit"
                                class="btn btn-primary custom-btn w-100">
                            <i class="bi bi-funnel me-1"></i>
                            Filter
                        </button>

                        <a href="{{ route('class-schedules.index') }}"
                           class="btn btn-outline-secondary custom-btn w-100 mt-2">
                            Reset
                        </a>
                    </div>

                </div>
            </form>
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


        {{-- ========================================================= --}}
        {{-- CLASSES --}}
        {{-- ========================================================= --}}

        <div class="row g-4">

            @forelse($classes as $class)

                @php

                    $gradeName = optional($class->grade)->grade_name ?? 'N/A';

                    $subjectName = optional($class->subject)->subject_name ?? 'N/A';

                    $teacherName = optional($class->teacher)->full_name ?? 'N/A';

                @endphp


                <div class="col-12">

                    <div class="class-card">

                        {{-- ================================================= --}}
                        {{-- CLASS HEADER --}}
                        {{-- ================================================= --}}

                        <div class="class-card-header">

                            <div>

                                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">

                                    <h5 class="mb-0 fw-bold">
                                        {{ $class->class_name }}
                                    </h5>


                                    <span
                                        class="badge bg-success-soft text-success rounded-pill px-3 py-2">

                                        Ongoing

                                    </span>

                                </div>


                                <div class="text-muted small">

                                    Grade:
                                    {{ $gradeName }}

                                    •

                                    Subject:
                                    {{ $subjectName }}

                                    •

                                    Medium:
                                    {{ $class->medium ?? 'N/A' }}

                                </div>


                                <div class="text-muted small">

                                    Teacher:
                                    {{ $teacherName }}

                                </div>

                            </div>


                            <div class="class-stats">

                                <div class="stat-box">

                                    <div class="stat-value">
                                        {{ $class->categoryFees->count() }}
                                    </div>

                                    <div class="stat-label">
                                        Categories
                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- CLASS BODY --}}
                        {{-- ================================================= --}}

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

                                                {{-- ========================================= --}}
                                                {{-- CATEGORY --}}
                                                {{-- ========================================= --}}

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


                                                {{-- ========================================= --}}
                                                {{-- FEE OPTIONS --}}
                                                {{-- ========================================= --}}

                                                <div>

                                                    @if ($feeOptions->count())

                                                        <div class="fee-options-list">

                                                            @foreach ($feeOptions as $option)

                                                                <div class="fee-option-item">

                                                                    <div>

                                                                        <div class="fee-option-label">

                                                                            {{ $option->label }}

                                                                        </div>


                                                                        @if ($option->note)

                                                                            <div class="fee-option-note">

                                                                                {{ $option->note }}

                                                                            </div>

                                                                        @endif

                                                                    </div>


                                                                    <div class="fee-option-amount">

                                                                        LKR
                                                                        {{ number_format($option->fee, 2) }}


                                                                        @if ($option->is_default)

                                                                            <span class="badge bg-success ms-1">

                                                                                Default

                                                                            </span>

                                                                        @endif

                                                                    </div>

                                                                </div>

                                                            @endforeach

                                                        </div>

                                                    @else

                                                        <div class="text-muted small">

                                                            No active fee options

                                                        </div>

                                                    @endif

                                                </div>


                                                {{-- ========================================= --}}
                                                {{-- ACTIONS --}}
                                                {{-- ========================================= --}}

                                                <div class="category-card-bottom">

                                                    <div class="text-muted small">

                                                        Category schedule

                                                    </div>


                                                    <div class="action-group">

                                                        {{-- CREATE SCHEDULE --}}

                                                        <a
                                                            href="{{ route('class-schedules.create', [
                                                                'student_class_id' => $class->id,
                                                                'class_category_fee_id' => $fee->id,
                                                            ]) }}"
                                                            class="btn btn-sm btn-primary icon-btn"
                                                            title="Schedule this category">

                                                            <i class="bi bi-calendar-plus"></i>

                                                        </a>


                                                        {{-- VIEW SCHEDULE --}}

                                                        <a
                                                            href="{{ route('class-schedules.categorySchedules', [
                                                                'student_class_id' => $class->id,
                                                                'class_category_fee_id' => $fee->id,
                                                            ]) }}"
                                                            class="btn btn-sm btn-outline-success icon-btn"
                                                            title="View schedules">

                                                            <i class="bi bi-eye"></i>

                                                        </a>


                                                        {{-- BULK UPDATE --}}

                                                        <a
                                                            href="{{ route('class-schedules.bulkEdit', [
                                                                'student_class_id' => $class->id,
                                                                'class_category_fee_id' => $fee->id,
                                                            ]) }}"
                                                            class="btn btn-sm btn-outline-warning icon-btn"
                                                            title="Bulk Update Schedule">

                                                            <i class="bi bi-pencil-square"></i>

                                                        </a>

                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                    @endforeach

                                </div>

                            @else

                                <div class="empty-box">

                                    <i class="bi bi-folder-x fs-2 d-block mb-2"></i>

                                    <div class="fw-semibold">
                                        No categories found
                                    </div>

                                    <div class="text-muted small">

                                        This class does not have any assigned category yet.

                                    </div>

                                </div>

                            @endif

                        </div>

                    </div>

                </div>

            @empty

                <div class="col-12">

                    <div class="empty-box py-5">

                        <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>

                        <div class="fw-semibold">
                            No ongoing classes found
                        </div>

                        <div class="text-muted small">
                            Try a different search term.
                        </div>

                    </div>

                </div>

            @endforelse

        </div>


        {{-- ========================================================= --}}
        {{-- PAGINATION --}}
        {{-- ========================================================= --}}

        <div class="mt-4 d-flex justify-content-end">

            {{ $classes->appends(request()->query())->links() }}

        </div>

    </div>

@endsection


@push('styles')

<style>

    .schedule-dashboard {
        animation: fadeIn .4s ease;
    }


    .hero-card,
    .filter-card,
    .class-card,
    .empty-box {
        background: #fff;
        border: 1px solid #eef2f7;
        border-radius: 28px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
    }


    .hero-card,
    .filter-card {
        padding: 1.5rem;
    }


    .hero-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }


    .hero-actions {
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
    }


    .custom-btn {
        border-radius: 14px;
        padding: .75rem 1.15rem;
        font-weight: 600;
        transition: .2s ease;
    }


    .custom-btn:hover {
        transform: translateY(-2px);
    }


    .custom-input {
        border-radius: 14px !important;
        min-height: 48px;
        border: 1px solid #e2e8f0;
    }


    .custom-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
    }


    .class-card {
        overflow: hidden;
    }


    .class-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }


    .class-card-body {
        padding: 1.5rem;
    }


    .class-stats {
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
    }


    .stat-box {
        min-width: 110px;
        padding: .9rem 1rem;
        border-radius: 20px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        text-align: center;
    }


    .stat-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }


    .stat-label {
        font-size: .78rem;
        color: #64748b;
        margin-top: .25rem;
        font-weight: 600;
    }


    .bg-success-soft {
        background: rgba(34, 197, 94, .12);
    }


    .category-card {
        background: linear-gradient(
            180deg,
            #ffffff 0%,
            #f8fafc 100%
        );

        border: 1px solid #e8eef6;

        border-radius: 22px;

        padding: 1rem;

        box-shadow:
            0 8px 20px rgba(15, 23, 42, .04);

        height: 100%;

        display: flex;

        flex-direction: column;

        justify-content: space-between;

        gap: 1rem;

        transition: .2s ease;
    }


    .category-card:hover {
        transform: translateY(-3px);

        box-shadow:
            0 12px 28px rgba(15, 23, 42, .08);
    }


    .category-card-top {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
    }


    .category-name {
        font-weight: 800;
        font-size: 1rem;
        color: #0f172a;
    }


    .category-code {
        font-size: .82rem;
        color: #64748b;
        margin-top: .2rem;
    }


    .fee-options-list {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }


    .fee-option-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;

        padding: .7rem .8rem;

        border-radius: 14px;

        background: #f8fafc;

        border: 1px solid #e8eef6;
    }


    .fee-option-label {
        font-size: .85rem;
        font-weight: 700;
        color: #334155;
    }


    .fee-option-note {
        font-size: .72rem;
        color: #94a3b8;
        margin-top: .15rem;
    }


    .fee-option-amount {
        font-size: .85rem;
        font-weight: 800;
        color: #2563eb;
        white-space: nowrap;
    }


    .category-card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
    }


    .action-group {
        display: flex;
        gap: .5rem;
    }


    .icon-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }


    .empty-box {
        padding: 2rem;
        text-align: center;
        color: #64748b;
    }


    @media (max-width: 768px) {

        .hero-content,
        .class-card-header {
            flex-direction: column;
            align-items: stretch;
        }


        .hero-actions {
            width: 100%;
        }


        .hero-actions .btn {
            flex: 1;
        }


        .category-card-top,
        .category-card-bottom {
            flex-direction: column;
            align-items: flex-start;
        }

    }

</style>

@endpush