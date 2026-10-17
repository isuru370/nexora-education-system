@extends('layouts.app')

@section('title', 'Branch Details')

@section('content')
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- HERO --}}
    {{-- ===================================================== --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">
                    <i class="bi bi-building"></i> Branch Details
                </span>

                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="branch-hero-avatar">
                        {{ strtoupper(substr($branch->name ?? 'B', 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="hero-title mb-0">{{ $branch->name }}</h1>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            @if ($branch->code)
                                <span class="hero-meta-badge code">
                                    <i class="bi bi-hash"></i>{{ $branch->code }}
                                </span>
                            @endif
                            
                            @if ($branch->is_active)
                                <span class="hero-meta-badge status-active">
                                    <span class="dot"></span> Active
                                </span>
                            @else
                                <span class="hero-meta-badge status-inactive">
                                    <span class="dot"></span> Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('branches.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('branches.edit', $branch->id) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-pencil-square"></i> Edit Branch
                    </a>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MAIN CONTENT --}}
    {{-- ===================================================== --}}
    <div class="row g-4">

        {{-- LEFT COLUMN --}}
        <div class="col-lg-8 d-flex flex-column gap-4">

            {{-- CONTACT INFO --}}
            <div class="nx-card">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-telephone-fill text-primary"></i> Contact Information</h5>
                        <p class="nx-card-subtitle">How to reach this branch</p>
                    </div>
                </div>

                <div>
                    <div class="detail-row">
                        <div class="detail-label">Mobile</div>
                        <div class="detail-value">
                            @if ($branch->mobile)
                                <a href="tel:{{ $branch->mobile }}">
                                    <i class="bi bi-phone-fill"></i> {{ $branch->mobile }}
                                </a>
                            @else
                                <span class="empty">Not provided</span>
                            @endif
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">
                            @if ($branch->email)
                                <a href="mailto:{{ $branch->email }}">
                                    <i class="bi bi-envelope-fill"></i> {{ $branch->email }}
                                </a>
                            @else
                                <span class="empty">Not provided</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ADDRESS --}}
            <div class="nx-card">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-geo-alt-fill text-primary"></i> Address Information</h5>
                        <p class="nx-card-subtitle">Physical location of the branch</p>
                    </div>
                </div>

                <div>
                    @if ($branch->address1 || $branch->address2 || $branch->address3)
                        <div class="detail-row">
                            <div class="detail-label">Address</div>
                            <div class="detail-value address-block">
                                @if ($branch->address1) <div>{{ $branch->address1 }}</div> @endif
                                @if ($branch->address2) <div>{{ $branch->address2 }}</div> @endif
                                @if ($branch->address3) <div>{{ $branch->address3 }}</div> @endif
                            </div>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-geo-alt"></i>
                            <p>No address information available</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-lg-4 d-flex flex-column gap-4">

            {{-- STATUS CARD --}}
            <div class="nx-card">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-toggles text-primary"></i> Branch Status</h5>
                    </div>
                </div>

                <div class="p-3">
                    @if ($branch->is_active)
                        <div class="status-box status-box-green mb-3">
                            <div class="status-box-icon">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <div>
                                <h6 class="status-box-title">Active</h6>
                                <p class="status-box-text">
                                    This branch is currently active and available for operations.
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="status-box status-box-amber mb-3">
                            <div class="status-box-icon">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <div>
                                <h6 class="status-box-title">Inactive</h6>
                                <p class="status-box-text">
                                    This branch is currently inactive. Operations are paused.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RECORD INFO --}}
            <div class="nx-card">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-clock-history text-primary"></i> Record Info</h5>
                    </div>
                </div>

                <div>
                    <div class="detail-row detail-row-stacked">
                        <div class="detail-label">Created</div>
                        <div class="detail-value small">
                            {{ $branch->created_at ? $branch->created_at->format('d M Y, h:i A') : '—' }}
                        </div>
                    </div>

                    <div class="detail-row detail-row-stacked">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value small">
                            {{ $branch->updated_at ? $branch->updated_at->format('d M Y, h:i A') : '—' }}
                        </div>
                    </div>
                    
                    @if($branch->deleted_at)
                        <div class="detail-row detail-row-stacked detail-row-danger">
                            <div class="detail-label">Deleted</div>
                            <div class="detail-value small text-danger">
                                {{ $branch->deleted_at->format('d M Y, h:i A') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="nx-card">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-gear-fill text-primary"></i> Actions</h5>
                    </div>
                </div>

                <div class="p-3 d-flex flex-column gap-2">
                    
                    <a href="{{ route('branches.edit', $branch->id) }}" class="action-btn action-btn-edit">
                        <i class="bi bi-pencil-square"></i> Edit Branch
                    </a>

                    {{-- ✅ Activate / Deactivate — Routes aligned --}}
                    @if ($branch->is_active)
                        <form action="{{ route('branches.deactivate', $branch->id) }}" method="POST" onsubmit="return confirm('Deactivate this branch?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="action-btn action-btn-deactivate w-100">
                                <i class="bi bi-pause-circle"></i> Deactivate Branch
                            </button>
                        </form>
                    @else
                        <form action="{{ route('branches.activate', $branch->id) }}" method="POST" onsubmit="return confirm('Activate this branch?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="action-btn action-btn-activate w-100">
                                <i class="bi bi-check-circle"></i> Activate Branch
                            </button>
                        </form>
                    @endif

                    {{-- ✅ Restore (if deleted) --}}
                    @if (method_exists($branch, 'trashed') && $branch->trashed())
                        <form action="{{ route('branches.restore', $branch->id) }}" method="POST" onsubmit="return confirm('Restore this branch?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="action-btn action-btn-activate w-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Restore Branch
                            </button>
                        </form>
                    @endif

                    <div class="danger-divider">
                        <hr>
                        <span>DANGER ZONE</span>
                        <hr>
                    </div>

                    <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this branch? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-btn action-btn-delete w-100">
                            <i class="bi bi-trash3"></i> Delete Branch
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
       Branch Show — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    /* Hero Avatar */
    .dashboard-page .branch-hero-avatar {
        width: 64px;
        height: 64px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .15);
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 1.6rem;
        font-weight: 700;
        flex-shrink: 0;
        border: 1px solid rgba(255, 255, 255, .2);
    }

    .dashboard-page .hero-meta-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .7rem;
        border-radius: 20px;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .15);
        color: rgba(255, 255, 255, .92);
        font-size: .72rem;
        font-weight: 600;
    }
    .dashboard-page .hero-meta-badge.code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    }
    .dashboard-page .hero-meta-badge .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }
    .dashboard-page .hero-meta-badge.status-active {
        background: rgba(16, 185, 129, .2);
        color: #6ee7b7;
    }
    .dashboard-page .hero-meta-badge.status-active .dot { background: #34d399; }
    .dashboard-page .hero-meta-badge.status-inactive {
        background: rgba(148, 163, 184, .2);
        color: #cbd5e1;
    }
    .dashboard-page .hero-meta-badge.status-inactive .dot { background: #94a3b8; }

    /* Detail rows */
    .dashboard-page .detail-row {
        display: flex;
        align-items: flex-start;
        padding: .8rem 1.25rem;
        border-bottom: 1px solid var(--nx-border);
        gap: 1rem;
    }
    .dashboard-page .detail-row:last-child { border-bottom: none; }

    .dashboard-page .detail-label {
        flex-shrink: 0;
        width: 120px;
        font-size: .72rem;
        font-weight: 700;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
        padding-top: .1rem;
    }

    .dashboard-page .detail-value {
        flex: 1;
        font-size: .85rem;
        font-weight: 600;
        color: var(--nx-text);
        word-break: break-word;
        min-width: 0;
    }
    .dashboard-page .detail-value.empty {
        color: var(--nx-muted);
        font-style: italic;
        font-weight: 400;
    }
    .dashboard-page .detail-value a {
        color: var(--nx-blue);
        text-decoration: none;
        font-weight: 600;
    }
    .dashboard-page .detail-value a:hover { text-decoration: underline; }

    .dashboard-page .detail-row-stacked {
        flex-direction: column;
        gap: .25rem;
    }
    .dashboard-page .detail-row-stacked .detail-label { width: 100%; }
    .dashboard-page .detail-row-danger { background: var(--nx-red-soft); }
    .dashboard-page .detail-row-danger .detail-label { color: var(--nx-red); }

    .dashboard-page .address-block { line-height: 1.7; }

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

    /* Actions */
    .dashboard-page .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        padding: .65rem 1rem;
        border-radius: 8px;
        font-size: .82rem;
        font-weight: 600;
        border: 1px solid var(--nx-border);
        background: #fff;
        color: var(--nx-text);
        text-decoration: none;
        transition: all .15s ease;
        cursor: pointer;
    }
    .dashboard-page .action-btn:hover {
        transform: translateY(-1px);
        filter: brightness(.97);
    }

    .dashboard-page .action-btn-edit {
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        border-color: rgba(29, 78, 216, .25);
    }
    .dashboard-page .action-btn-edit:hover { background: var(--nx-blue); color: #fff; }

    .dashboard-page .action-btn-activate {
        background: var(--nx-green-soft);
        color: var(--nx-green);
        border-color: rgba(4, 120, 87, .25);
    }
    .dashboard-page .action-btn-activate:hover { background: var(--nx-green); color: #fff; }

    .dashboard-page .action-btn-deactivate {
        background: var(--nx-amber-soft);
        color: var(--nx-amber);
        border-color: rgba(180, 83, 9, .25);
    }
    .dashboard-page .action-btn-deactivate:hover { background: var(--nx-amber); color: #fff; }

    .dashboard-page .action-btn-delete {
        background: var(--nx-red-soft);
        color: var(--nx-red);
        border-color: rgba(185, 28, 28, .25);
    }
    .dashboard-page .action-btn-delete:hover { background: var(--nx-red); color: #fff; }

    .dashboard-page .danger-divider {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: .25rem 0;
    }
    .dashboard-page .danger-divider hr {
        flex: 1;
        margin: 0;
        border: 0;
        border-top: 1px solid var(--nx-border);
    }
    .dashboard-page .danger-divider span {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: var(--nx-muted);
    }

    /* Empty */
    .dashboard-page .empty-state {
        text-align: center;
        padding: 2rem 1rem;
    }
    .dashboard-page .empty-state i {
        font-size: 2.5rem;
        color: var(--nx-muted);
        opacity: .5;
    }
    .dashboard-page .empty-state p {
        color: var(--nx-muted);
        font-size: .82rem;
        margin: .5rem 0 0;
    }

    @media (max-width: 768px) {
        .dashboard-page .detail-row {
            flex-direction: column;
            gap: .35rem;
            padding: .7rem 1rem;
        }
        .dashboard-page .detail-label { width: 100%; }
    }
</style>
@endpush