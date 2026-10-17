@extends('layouts.app')

@section('title', 'Admission Details')
@section('page-title', 'Admission Details')

@section('content')
    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">
                        <i class="bi bi-journal-bookmark-fill"></i>
                        Admission Information
                    </span>
                    <h1 class="hero-title">{{ $admission->name }}</h1>
                    <p class="hero-subtitle">Complete details of the admission fee structure.</p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">Rs. {{ number_format($admission->amount, 2) }}</span>
                            <span class="mini-stat-label">Amount</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="nx-badge {{ $admission->is_active ? 'nx-badge-green' : 'nx-badge-red' }}">
                                <i class="bi {{ $admission->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                {{ $admission->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('admissions.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <a href="{{ route('admissions.edit', $admission) }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- DETAILS CARD --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-info-circle-fill text-primary"></i> Admission Details</h5>
                    <p class="nx-card-subtitle">Fee structure, status and record information</p>
                </div>
            </div>

            <div class="p-3 p-md-4">

                {{-- INFO GRID --}}
                <div class="info-grid">

                    {{-- NAME --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-tag-fill text-primary"></i> Admission Name</div>
                        <div class="info-value">{{ $admission->name }}</div>
                    </div>

                    {{-- AMOUNT --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-cash-stack text-primary"></i> Amount</div>
                        <div class="info-value amount">Rs. {{ number_format($admission->amount, 2) }}</div>
                    </div>

                    {{-- STATUS --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-flag-fill text-primary"></i> Status</div>
                        <div class="info-value">
                            @if ($admission->is_active)
                                <span class="nx-badge nx-badge-green">
                                    <i class="bi bi-check-circle-fill"></i> Active
                                </span>
                            @else
                                <span class="nx-badge nx-badge-red">
                                    <i class="bi bi-x-circle-fill"></i> Inactive
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- CREATED BY --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-person-badge-fill text-primary"></i> Created By</div>
                        <div class="info-value">{{ $admission->createdBy->name ?? 'System' }}</div>
                    </div>
                </div>

                {{-- NOTE --}}
                @if ($admission->note)
                    <div class="note-section">
                        <div class="note-label"><i class="bi bi-chat-text-fill"></i> Additional Note</div>
                        <div class="note-text">{{ $admission->note }}</div>
                    </div>
                @endif

                {{-- TIMELINE --}}
                <div class="timeline">
                    <div class="timeline-title"><i class="bi bi-clock-history"></i> Record Timeline</div>

                    <div class="timeline-item">
                        <div class="timeline-icon"><i class="bi bi-plus-circle-fill"></i></div>
                        <div class="timeline-content">
                            <div class="timeline-event">Admission Created</div>
                            <div class="timeline-date">{{ $admission->created_at->format('l, d F Y h:i A') }}</div>
                        </div>
                    </div>

                    @if ($admission->created_at != $admission->updated_at)
                        <div class="timeline-item">
                            <div class="timeline-icon"><i class="bi bi-pencil-square"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-event">Last Updated</div>
                                <div class="timeline-date">{{ $admission->updated_at->format('l, d F Y h:i A') }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ACTIONS --}}
                <div class="action-buttons">
                    <a href="{{ route('admissions.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <a href="{{ route('admissions.edit', $admission) }}" class="nx-btn nx-btn-primary">
                        <i class="bi bi-pencil-square"></i> Edit Admission
                    </a>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="text-center">
            <small class="cell-sub">
                <i class="bi bi-info-circle"></i>
                Admission ID: #{{ $admission->id }} |
                Last modified: {{ $admission->updated_at->diffForHumans() }}
            </small>
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Admission Details — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .dashboard-page .info-card {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        padding: 1rem 1.1rem;
        transition: border-color .15s ease, background .15s ease;
    }
    .dashboard-page .info-card:hover {
        background: #fff;
        border-color: var(--nx-blue);
    }

    .dashboard-page .info-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
        color: var(--nx-muted);
        margin-bottom: .5rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .dashboard-page .info-label i { font-size: .85rem; }

    .dashboard-page .info-value {
        font-size: .92rem;
        font-weight: 700;
        color: var(--nx-text);
        word-break: break-word;
    }

    .dashboard-page .info-value.amount {
        font-size: 1.2rem;
        color: var(--nx-green);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    }

    /* ---------- NOTE ---------- */
    .dashboard-page .note-section {
        margin-top: 1.25rem;
        background: var(--nx-amber-soft);
        border: 1px solid rgba(180, 83, 9, .2);
        border-left: 4px solid var(--nx-amber);
        border-radius: 8px;
        padding: 1rem 1.25rem;
    }
    .dashboard-page .note-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
        color: var(--nx-amber);
        margin-bottom: .5rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .dashboard-page .note-text {
        font-size: .88rem;
        color: var(--nx-text);
        line-height: 1.5;
    }

    /* ---------- TIMELINE ---------- */
    .dashboard-page .timeline {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--nx-border);
    }
    .dashboard-page .timeline-title {
        font-size: .78rem;
        font-weight: 700;
        color: var(--nx-muted);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: .4rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .dashboard-page .timeline-item {
        display: flex;
        gap: 1rem;
        padding: .75rem 0;
        border-bottom: 1px solid var(--nx-border);
    }
    .dashboard-page .timeline-item:last-child { border-bottom: 0; }

    .dashboard-page .timeline-icon {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
    }

    .dashboard-page .timeline-content { flex: 1; }
    .dashboard-page .timeline-event {
        font-weight: 700;
        color: var(--nx-text);
        font-size: .85rem;
    }
    .dashboard-page .timeline-date {
        font-size: .74rem;
        color: var(--nx-muted);
    }

    /* ---------- ACTION BUTTONS ---------- */
    .dashboard-page .action-buttons {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--nx-border);
        display: flex;
        gap: .5rem;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .dashboard-page .info-grid { grid-template-columns: 1fr; }
        .dashboard-page .action-buttons { justify-content: stretch; }
        .dashboard-page .action-buttons .nx-btn { flex: 1; justify-content: center; }
    }
</style>
@endpush