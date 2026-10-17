@extends('layouts.app')

@section('title', 'Class Category Details')
@section('page-title', 'Class Category Details')

@section('content')
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- HERO --}}
    {{-- ===================================================== --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">
                    <i class="bi bi-tag-fill"></i> Class Category
                </span>
                <h1 class="hero-title">{{ $classCategory->category_name }}</h1>
                <p class="hero-subtitle">{{ $classCategory->code }}</p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="nx-badge {{ $classCategory->is_schedulable ? 'nx-badge-blue' : 'nx-badge-gray' }}">
                            <i class="bi bi-calendar-check"></i>
                            {{ $classCategory->is_schedulable ? 'Schedulable' : 'Not Schedulable' }}
                        </span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="nx-badge {{ $classCategory->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                            <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                            {{ $classCategory->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-categories.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('class-categories.edit', $classCategory) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-pencil-square"></i> Edit
                    </a>
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
    {{-- CATEGORY INFORMATION --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-info-circle-fill text-primary"></i> Category Information</h5>
                <p class="nx-card-subtitle">Details of this class category</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            <div class="detail-grid">

                {{-- NAME --}}
                <div class="detail-item">
                    <span class="detail-label">Name</span>
                    <strong class="detail-value">{{ $classCategory->category_name }}</strong>
                </div>

                {{-- CODE --}}
                <div class="detail-item">
                    <span class="detail-label">Code</span>
                    <strong class="detail-value" style="font-family: ui-monospace, monospace;">
                        {{ $classCategory->code }}
                    </strong>
                </div>

                {{-- SCHEDULABLE --}}
                <div class="detail-item">
                    <span class="detail-label">Schedulable</span>
                    <span class="nx-badge {{ $classCategory->is_schedulable ? 'nx-badge-blue' : 'nx-badge-gray' }}">
                        <i class="bi bi-calendar-check"></i>
                        {{ $classCategory->is_schedulable ? 'Yes' : 'No' }}
                    </span>
                </div>

                {{-- STATUS --}}
                <div class="detail-item">
                    <span class="detail-label">Status</span>
                    <span class="nx-badge {{ $classCategory->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                        <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                        {{ $classCategory->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                {{-- CREATED --}}
                <div class="detail-item">
                    <span class="detail-label">Created At</span>
                    <strong class="detail-value">{{ $classCategory->created_at?->format('Y-m-d h:i A') }}</strong>
                </div>

                {{-- UPDATED --}}
                <div class="detail-item">
                    <span class="detail-label">Updated At</span>
                    <strong class="detail-value">{{ $classCategory->updated_at?->format('Y-m-d h:i A') }}</strong>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Class Category Details — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .dashboard-page .detail-item {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        padding: .85rem 1rem;
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }

    .dashboard-page .detail-label {
        font-size: .72rem;
        font-weight: 700;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .dashboard-page .detail-value {
        font-size: .88rem;
        font-weight: 600;
        color: var(--nx-text);
        word-break: break-word;
    }

    @media (max-width: 768px) {
        .dashboard-page .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush