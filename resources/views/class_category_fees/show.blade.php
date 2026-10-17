@extends('layouts.app')

@section('title', 'Class Category Fee Details')
@section('page-title', 'Class Category Fee Details')

@section('content')

    @php
        $studentClass = optional($classCategoryFee)->studentClass;
        $category     = optional($classCategoryFee)->category;

        $className    = optional($studentClass)->class_name ?? '-';
        $gradeName    = optional(optional($studentClass)->grade)->grade_name ?? 'N/A';
        $subjectName  = optional(optional($studentClass)->subject)->subject_name ?? 'N/A';
        $teacherName  = optional(optional($studentClass)->teacher)->full_name ?? 'N/A';

        $categoryName = optional($category)->category_name ?? '-';
        $categoryCode = optional($category)->code ?? '-';

        $classIsActive    = optional($studentClass)->is_active ?? false;
        $categoryIsActive = optional($category)->is_active ?? false;
        $feeIsActive      = optional($classCategoryFee)->is_active ?? false;
    @endphp

    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">
                        <i class="bi bi-journal-bookmark-fill"></i> Class Category Fee
                    </span>
                    <h1 class="hero-title">
                        {{ $className }} / {{ $categoryName }}
                    </h1>
                    <p class="hero-subtitle">Detailed information and fee options</p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="nx-badge {{ $feeIsActive ? 'nx-badge-green' : 'nx-badge-gray' }}">
                                <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                                {{ $feeIsActive ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="nx-badge nx-badge-blue">
                                <i class="bi bi-list-ul"></i>
                                {{ $classCategoryFee->feeOptions->count() }} Fee Options
                            </span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('class-category-fees.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>

                        @if ($classIsActive && $categoryIsActive)
                            <a href="{{ route('class-category-fees.edit', $classCategoryFee->id) }}" class="nx-btn nx-btn-primary">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                        @endif
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ALERTS --}}
        {{-- ===================================================== --}}
        @if (session('success'))
            <div class="nx-alert nx-alert-green mb-4">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="nx-alert nx-alert-red mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- MAIN INFO GRID --}}
        {{-- ===================================================== --}}
        <div class="row g-4 mb-4">

            {{-- CLASS --}}
            <div class="col-md-6">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-book text-primary"></i> Class Information</h5>
                            <p class="nx-card-subtitle">Details of the assigned class</p>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="detail-line">
                            <span class="detail-label">Class</span>
                            <span class="detail-value">{{ $className }}</span>
                        </div>
                        <div class="detail-line">
                            <span class="detail-label">Grade</span>
                            <span class="detail-value">{{ $gradeName }}</span>
                        </div>
                        <div class="detail-line">
                            <span class="detail-label">Subject</span>
                            <span class="detail-value">{{ $subjectName }}</span>
                        </div>
                        <div class="detail-line">
                            <span class="detail-label">Teacher</span>
                            <span class="detail-value">{{ $teacherName }}</span>
                        </div>
                        <div class="detail-line" style="border-bottom: 0;">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                <span class="nx-badge {{ $classIsActive ? 'nx-badge-green' : 'nx-badge-gray' }}">
                                    <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                                    {{ $classIsActive ? 'Active' : 'Inactive' }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CATEGORY --}}
            <div class="col-md-6">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-tags text-primary"></i> Category Information</h5>
                            <p class="nx-card-subtitle">Details of the category</p>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="detail-line">
                            <span class="detail-label">Category</span>
                            <span class="detail-value">{{ $categoryName }}</span>
                        </div>
                        <div class="detail-line">
                            <span class="detail-label">Code</span>
                            <span class="detail-value" style="font-family: ui-monospace, monospace;">{{ $categoryCode }}</span>
                        </div>
                        <div class="detail-line" style="border-bottom: 0;">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">
                                <span class="nx-badge {{ $categoryIsActive ? 'nx-badge-green' : 'nx-badge-gray' }}">
                                    <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                                    {{ $categoryIsActive ? 'Active' : 'Inactive' }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ASSOCIATION STATUS --}}
            <div class="col-md-6">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-link-45deg text-primary"></i> Assignment Status</h5>
                            <p class="nx-card-subtitle">Whether this assignment is active</p>
                        </div>
                    </div>
                    <div class="p-3">
                        @if ($feeIsActive)
                            <div class="status-box status-box-green">
                                <div class="status-box-icon"><i class="bi bi-check-lg"></i></div>
                                <div>
                                    <h6 class="status-box-title">Active</h6>
                                    <p class="status-box-text">This category is currently assigned to this class.</p>
                                </div>
                            </div>
                        @else
                            <div class="status-box status-box-amber">
                                <div class="status-box-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                                <div>
                                    <h6 class="status-box-title">Inactive</h6>
                                    <p class="status-box-text">This category assignment is currently inactive.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- RECORD INFORMATION --}}
            <div class="col-md-6">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-info-circle-fill text-primary"></i> Record Information</h5>
                            <p class="nx-card-subtitle">Metadata for this record</p>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="detail-line">
                            <span class="detail-label">ID</span>
                            <span class="detail-value" style="font-family: ui-monospace, monospace;">#{{ $classCategoryFee->id }}</span>
                        </div>
                        <div class="detail-line">
                            <span class="detail-label">Created</span>
                            <span class="detail-value">
                                {{ $classCategoryFee->created_at
                                    ? $classCategoryFee->created_at->format('d M Y h:i A')
                                    : 'N/A' }}
                            </span>
                        </div>
                        <div class="detail-line" style="border-bottom: 0;">
                            <span class="detail-label">Updated</span>
                            <span class="detail-value">
                                {{ $classCategoryFee->updated_at
                                    ? $classCategoryFee->updated_at->format('d M Y h:i A')
                                    : 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===================================================== --}}
        {{-- NOTE --}}
        {{-- ===================================================== --}}
        @if ($classCategoryFee->note)
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-sticky-fill text-primary"></i> Note</h5>
                    </div>
                </div>
                <div class="p-3">
                    <p class="mb-0 cell-title" style="white-space: pre-line;">
                        {{ $classCategoryFee->note }}
                    </p>
                </div>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- FEE OPTIONS --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-list-ul text-primary"></i> Fee Options</h5>
                    <p class="nx-card-subtitle">Predefined fee options for this class category</p>
                </div>
                <span class="nx-badge nx-badge-blue">
                    {{ $classCategoryFee->feeOptions->count() }} Options
                </span>
            </div>

            @if ($classCategoryFee->feeOptions->count())
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th class="text-end">Fee</th>
                                <th class="text-center">Default</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classCategoryFee->feeOptions as $option)
                                <tr>
                                    <td class="cell-title">{{ $option->label }}</td>
                                    <td class="text-end cell-title">Rs. {{ number_format($option->fee, 2) }}</td>
                                    <td class="text-center">
                                        @if ($option->is_default)
                                            <span class="nx-badge nx-badge-blue">
                                                <i class="bi bi-star-fill"></i> Default
                                            </span>
                                        @else
                                            <span class="cell-sub">No</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($option->is_active)
                                            <span class="nx-badge nx-badge-green">Active</span>
                                        @else
                                            <span class="nx-badge nx-badge-gray">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-table">
                    <i class="bi bi-cash-stack fs-2 d-block mb-2"></i>
                    <strong class="d-block">No Fee Options</strong>
                    <span class="cell-sub">No fee options have been created for this category yet.</span>
                </div>
            @endif
        </div>

        {{-- ===================================================== --}}
        {{-- BOTTOM ACTIONS --}}
        {{-- ===================================================== --}}
        <div class="d-flex justify-content-end gap-2 flex-wrap">
            <a href="{{ route('class-category-fees.index') }}" class="nx-btn nx-btn-light">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>

            @if ($classIsActive && $categoryIsActive)
                <a href="{{ route('class-category-fees.edit', $classCategoryFee->id) }}" class="nx-btn nx-btn-primary">
                    <i class="bi bi-pencil-square"></i> Edit Fee
                </a>
            @endif
        </div>

    </div>

@endsection


@push('styles')
<style>
    /* ============================================================
       Class Category Fee Details — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .detail-line {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: .7rem 0;
        border-bottom: 1px solid var(--nx-border);
        gap: 1rem;
    }
    .dashboard-page .detail-line:last-child {
        border-bottom: 0;
    }

    .dashboard-page .detail-label {
        flex-shrink: 0;
        font-size: .72rem;
        font-weight: 700;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .dashboard-page .detail-value {
        flex: 1;
        text-align: right;
        font-size: .85rem;
        font-weight: 600;
        color: var(--nx-text);
        word-break: break-word;
    }

    /* Status box */
    .dashboard-page .status-box {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: .85rem 1rem;
        border-radius: 10px;
    }
    .dashboard-page .status-box-green {
        background: var(--nx-green-soft);
        border: 1px solid rgba(4, 120, 87, .2);
    }
    .dashboard-page .status-box-amber {
        background: var(--nx-amber-soft);
        border: 1px solid rgba(180, 83, 9, .2);
    }

    .dashboard-page .status-box-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 1.1rem;
        flex-shrink: 0;
        color: #fff;
    }
    .dashboard-page .status-box-green .status-box-icon { background: var(--nx-green); }
    .dashboard-page .status-box-amber .status-box-icon { background: var(--nx-amber); }

    .dashboard-page .status-box-title {
        font-size: .9rem;
        font-weight: 700;
        margin: 0 0 .2rem;
    }
    .dashboard-page .status-box-green .status-box-title { color: var(--nx-green); }
    .dashboard-page .status-box-amber .status-box-title { color: var(--nx-amber); }

    .dashboard-page .status-box-text {
        font-size: .78rem;
        color: var(--nx-text);
        margin: 0;
        line-height: 1.5;
    }
</style>
@endpush