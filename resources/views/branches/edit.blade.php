@extends('layouts.app')

@section('title', 'Edit Branch')
@section('page-title', 'Edit Branch')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">
                    Edit Branch: {{ $branch->name }}
                </h1>
                <p class="hero-subtitle">
                    <i class="bi bi-pencil-square"></i>
                    Update branch information and settings.
                </p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="id-badge" style="background: rgba(255,255,255,.15); color: #fff; font-size: .8rem; padding: .3rem .7rem;">
                            <i class="bi bi-hash"></i>{{ $branch->code }}
                        </span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        @if ($branch->is_active)
                            <span class="nx-badge nx-badge-green">
                                <i class="bi bi-circle-fill" style="font-size: .4rem;"></i> Active
                            </span>
                        @else
                            <span class="nx-badge nx-badge-gray">
                                <i class="bi bi-circle-fill" style="font-size: .4rem;"></i> Inactive
                            </span>
                        @endif
                    </div>
                    @if ($branch->created_at)
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value" style="font-size: .8rem;">
                                {{ $branch->created_at->format('d M Y') }}
                            </span>
                            <span class="mini-stat-label">Created</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('branches.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back to Branches
                    </a>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- EDIT MODE INFO --}}
    <div class="nx-alert nx-alert-blue mb-4">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            <strong>Editing Mode</strong>
            <div style="font-size: .8rem; margin-top: .15rem;">
                Changes will be applied immediately after saving. Make sure all information is accurate before submitting.
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- MAIN FORM --}}
        <div class="col-lg-8">
            <div class="nx-card">

                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-pencil-square text-primary"></i> Branch Information</h5>
                        <p class="nx-card-subtitle">Update the fields you want to change</p>
                    </div>
                    <span class="nx-badge nx-badge-blue" style="font-family: ui-monospace, monospace;">
                        ID: #{{ $branch->id }}
                    </span>
                </div>

                <div class="p-3 p-md-4">
                    <form action="{{ route('branches.update', $branch->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('branches.partials.form', ['branch' => $branch])
                    </form>
                </div>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="col-lg-4">

            {{-- BRANCH DETAILS --}}
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-info-circle-fill text-primary"></i> Branch Details</h5>
                    </div>
                </div>
                <div class="p-3">
                    <div class="details-list">

                        <div class="detail-row">
                            <span class="detail-label"><i class="bi bi-hash"></i> Code</span>
                            <span class="detail-value" style="font-family: ui-monospace, monospace;">{{ $branch->code }}</span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label"><i class="bi bi-calendar-plus"></i> Created</span>
                            <span class="detail-value">
                                {{ $branch->created_at ? $branch->created_at->format('d M Y') : '-' }}
                            </span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label"><i class="bi bi-clock-history"></i> Updated</span>
                            <span class="detail-value">
                                {{ $branch->updated_at ? $branch->updated_at->diffForHumans() : 'Never' }}
                            </span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label"><i class="bi bi-toggles"></i> Status</span>
                            <span class="detail-value">
                                @if ($branch->is_active)
                                    <span class="nx-badge nx-badge-green">
                                        <i class="bi bi-circle-fill" style="font-size: .4rem;"></i> Active
                                    </span>
                                @else
                                    <span class="nx-badge nx-badge-gray">
                                        <i class="bi bi-circle-fill" style="font-size: .4rem;"></i> Inactive
                                    </span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- WARNING --}}
            <div class="nx-alert nx-alert-amber mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <strong>Important</strong>
                    <div style="font-size: .8rem; margin-top: .15rem;">
                        Changing the <strong>branch code</strong> may affect existing records linked to this branch.
                    </div>
                </div>
            </div>

            {{-- DANGER ZONE --}}
            <div class="nx-card danger-card">
                <div class="nx-card-header" style="border-bottom-color: rgba(185, 28, 28, .2);">
                    <div>
                        <h5 class="nx-card-title" style="color: var(--nx-red);">
                            <i class="bi bi-trash3-fill"></i> Danger Zone
                        </h5>
                        <p class="nx-card-subtitle">Delete this branch permanently</p>
                    </div>
                </div>

                <div class="p-3">
                    <p class="cell-sub mb-3">
                        Once deleted, this branch cannot be recovered. Please be certain.
                    </p>

                    <form action="{{ route('branches.destroy', $branch->id) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this branch permanently? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="nx-btn nx-btn-danger w-100">
                            <i class="bi bi-trash3"></i> Delete This Branch
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Edit Branch — page-specific styles only.
       ============================================================ */

    .dashboard-page .details-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .dashboard-page .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .7rem 0;
        border-bottom: 1px solid var(--nx-border);
        gap: .75rem;
    }
    .dashboard-page .detail-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .dashboard-page .detail-label {
        font-size: .78rem;
        color: var(--nx-muted);
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .dashboard-page .detail-label i {
        font-size: .85rem;
    }

    .dashboard-page .detail-value {
        font-size: .82rem;
        font-weight: 600;
        color: var(--nx-text);
        text-align: right;
    }

    .dashboard-page .danger-card {
        border-color: rgba(185, 28, 28, .25);
    }
</style>
@endpush