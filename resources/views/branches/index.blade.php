@extends('layouts.app')

@section('title', 'Branches')

@section('content')
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- HERO --}}
    {{-- ===================================================== --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Branch Management</span>
                <h1 class="hero-title">Institution Branches</h1>
                <p class="hero-subtitle">Manage all your education institution branches from one place.</p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $stats['total'] ?? 0 }}</span>
                        <span class="mini-stat-label">Total</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $stats['active'] ?? 0 }}</span>
                        <span class="mini-stat-label">Active</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $stats['inactive'] ?? 0 }}</span>
                        <span class="mini-stat-label">Inactive</span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-date-time">
                    <div class="date-box">
                        <i class="bi bi-buildings"></i>
                        <span>Branch Directory</span>
                    </div>
                </div>
                <a href="{{ route('branches.create') }}" class="nx-btn nx-btn-primary mt-2">
                    <i class="bi bi-plus-circle"></i> Add New Branch
                </a>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- KPI CARDS --}}
    {{-- ===================================================== --}}
    <div class="stats-grid stats-grid-3 mb-4">

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-buildings"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Branches</span>
                <h2 class="stat-number">{{ $stats['total'] ?? 0 }}</h2>
                <span class="stat-change neutral">
                    All <span class="stat-change-note">registered branches</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Active Branches</span>
                <h2 class="stat-number">{{ $stats['active'] ?? 0 }}</h2>
                <div class="nx-progress mt-2">
                    <div class="nx-progress-fill bg-green" style="width: {{ $stats['active_percentage'] ?? 0 }}%"></div>
                </div>
                <span class="stat-change up mt-1">
                    {{ $stats['active_percentage'] ?? 0 }}% <span class="stat-change-note">active rate</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Inactive Branches</span>
                <h2 class="stat-number">{{ $stats['inactive'] ?? 0 }}</h2>
                <span class="stat-change down">
                    Needs <span class="stat-change-note">attention</span>
                </span>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- BRANCHES TABLE --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-list-ul text-primary"></i> All Branches</h5>
                <p class="nx-card-subtitle">View and manage all your institution branches</p>
            </div>
            <span class="nx-badge nx-badge-blue">
                <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                {{ $stats['total'] ?? $branches->total() }} {{ Str::plural('Branch', $stats['total'] ?? $branches->total()) }}
            </span>
        </div>

        @if ($branches->isEmpty())
            <div class="empty-table">
                <i class="bi bi-buildings fs-2 d-block mb-2"></i>
                <strong class="d-block">No branches yet</strong>
                <span class="cell-sub d-block mb-3">Get started by creating your first branch.</span>
                <a href="{{ route('branches.create') }}" class="nx-btn nx-btn-primary">
                    <i class="bi bi-plus-circle"></i> Create First Branch
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Code</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                {{-- BRANCH --}}
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="teacher-avatar">
                                            {{ strtoupper(substr($branch->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="cell-title">{{ $branch->name }}</div>
                                            @if ($branch->address1)
                                                <small class="cell-sub d-block">
                                                    <i class="bi bi-geo-alt"></i> {{ Str::limit($branch->address1, 40) }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- CODE --}}
                                <td>
                                    <span class="id-badge">
                                        <i class="bi bi-hash"></i>{{ $branch->code }}
                                    </span>
                                </td>

                                {{-- CONTACT --}}
                                <td>
                                    @if ($branch->mobile)
                                        <div class="cell-title">
                                            <i class="bi bi-telephone text-muted"></i> {{ $branch->mobile }}
                                        </div>
                                    @endif
                                    @if ($branch->email)
                                        <div class="cell-sub">
                                            <i class="bi bi-envelope"></i> {{ Str::limit($branch->email, 25) }}
                                        </div>
                                    @endif
                                    @if (!$branch->mobile && !$branch->email)
                                        <small class="cell-sub fst-italic">No contact info</small>
                                    @endif
                                </td>

                                {{-- STATUS --}}
                                <td>
                                    @if ($branch->is_active)
                                        <span class="nx-badge nx-badge-green">
                                            <i class="bi bi-circle-fill" style="font-size: .4rem;"></i> Active
                                        </span>
                                    @else
                                        <span class="nx-badge nx-badge-gray">
                                            <i class="bi bi-circle-fill" style="font-size: .4rem;"></i> Inactive
                                        </span>
                                    @endif
                                </td>

                                {{-- ACTIONS --}}
                                <td class="text-end">
                                    <div class="action-buttons">

                                        {{-- VIEW --}}
                                        <a href="{{ route('branches.show', $branch->id) }}" class="action-btn" title="View" aria-label="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        {{-- EDIT --}}
                                        <a href="{{ route('branches.edit', $branch->id) }}" class="action-btn" title="Edit" aria-label="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        {{-- ACTIVATE / DEACTIVATE --}}
                                        @if ($branch->is_active)
                                            <form action="{{ route('branches.deactivate', $branch->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="action-btn action-btn-warning" title="Deactivate" aria-label="Deactivate"
                                                    onclick="return confirm('Deactivate this branch?')">
                                                    <i class="bi bi-pause-circle-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('branches.activate', $branch->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="action-btn action-btn-success" title="Activate" aria-label="Activate"
                                                    onclick="return confirm('Activate this branch?')">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- DELETE --}}
                                        <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-btn-danger" title="Delete" aria-label="Delete"
                                                onclick="return confirm('Delete this branch permanently?')">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (method_exists($branches, 'links') && $branches->hasPages())
                <div class="p-3 border-top">
                    {{ $branches->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Branches Index — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .teacher-avatar {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
    }
</style>
@endpush