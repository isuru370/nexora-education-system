@extends('layouts.app')

@section('title', 'Create Branch')
@section('page-title', 'Create Branch')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">
                    Create New Branch
                </h1>
                <p class="hero-subtitle">
                    <i class="bi bi-info-circle"></i>
                    Fill in the details below to add a new branch to your institution.
                </p>
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

    <div class="row g-4">

        {{-- MAIN FORM --}}
        <div class="col-lg-8">
            <div class="nx-card">

                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-pencil-square text-primary"></i> Branch Information</h5>
                        <p class="nx-card-subtitle">
                            All fields marked <strong style="color: var(--nx-red);">*</strong> are required
                        </p>
                    </div>
                </div>

                <div class="p-3 p-md-4">
                    <form action="{{ route('branches.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('branches.partials.form')
                    </form>
                </div>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="col-lg-4">

            {{-- TIPS --}}
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-lightbulb-fill text-primary"></i> Quick Tips</h5>
                        <p class="nx-card-subtitle">Helpful hints for branch setup</p>
                    </div>
                </div>

                <div class="p-3">
                    <ul class="tips-list">
                        <li>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Use a <strong>unique branch code</strong> for easy identification.</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Provide accurate <strong>contact details</strong> for communication.</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Set the branch <strong>active</strong> to make it available immediately.</span>
                        </li>
                        <li>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>You can <strong>edit details</strong> anytime after creation.</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- INFO CARD --}}
            <div class="nx-card">
                <div class="p-3">
                    <div class="info-card-info">
                        <div class="info-icon-circle">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h6 class="info-card-title">Secure &amp; Organized</h6>
                            <p class="info-card-text">
                                Your branch data is securely stored and easily accessible from the dashboard.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Create Branch — page-specific styles only.
       ============================================================ */

    .dashboard-page .tips-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: .85rem;
    }
    .dashboard-page .tips-list li {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        font-size: .82rem;
        color: var(--nx-muted);
        line-height: 1.5;
    }
    .dashboard-page .tips-list li i {
        font-size: .9rem;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .dashboard-page .tips-list li strong {
        color: var(--nx-text);
    }

    .dashboard-page .info-card-info {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        padding: .85rem 1rem;
        background: var(--nx-blue-soft);
        border: 1px solid rgba(29, 78, 216, .2);
        border-radius: 10px;
    }
    .dashboard-page .info-icon-circle {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--nx-blue);
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .dashboard-page .info-card-title {
        font-size: .88rem;
        font-weight: 700;
        color: var(--nx-blue);
        margin: 0 0 .2rem;
    }
    .dashboard-page .info-card-text {
        font-size: .78rem;
        color: var(--nx-text);
        margin: 0;
        line-height: 1.5;
    }
</style>
@endpush