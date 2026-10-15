@extends('layouts.app')

@section('title', 'Organizers')
@section('page-title', 'Organizers')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Organizers</h1>
                <p class="hero-subtitle">Manage organizer profiles and account status.</p>

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
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $stats['registered_codes'] ?? 0 }}</span>
                        <span class="mini-stat-label">Codes</span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-date-time">
                    <div class="date-box">
                        <i class="bi bi-person-badge"></i>
                        <span>Organizer Directory</span>
                    </div>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="stats-grid mb-4">

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Organizers</span>
                <h2 class="stat-number">{{ $stats['total'] ?? 0 }}</h2>
                <span class="stat-change neutral">
                    All <span class="stat-change-note">registered organizers</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Active Organizers</span>
                <h2 class="stat-number">{{ $stats['active'] ?? 0 }}</h2>
                <span class="stat-change up">
                    Currently <span class="stat-change-note">active</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-red"><i class="bi bi-pause-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Inactive Organizers</span>
                <h2 class="stat-number">{{ $stats['inactive'] ?? 0 }}</h2>
                <span class="stat-change down">
                    Not <span class="stat-change-note">active</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-person-badge-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Registered Codes</span>
                <h2 class="stat-number">{{ $stats['registered_codes'] ?? 0 }}</h2>
                <span class="stat-change neutral">
                    With <span class="stat-change-note">assigned codes</span>
                </span>
            </div>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-person-badge text-primary"></i> Organizers Management</h5>
                <p class="nx-card-subtitle">Manage organizer profiles and account status</p>
            </div>

            <div class="header-buttons">
                <a href="{{ route('organizers.create') }}" class="nx-btn nx-btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Organizer
                </a>
                <a href="{{ route('organizers.exportExcel') }}" class="nx-btn nx-btn-success">
                    <i class="bi bi-file-earmark-excel-fill"></i> Excel
                </a>
                <a href="{{ route('organizers.exportPdf') }}" class="nx-btn nx-btn-danger">
                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                </a>
                <a href="{{ route('admin.organizer-payments.index') }}" class="nx-btn nx-btn-light">
                    <i class="bi bi-cash-stack"></i> Payments
                </a>
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('organizers.index') }}">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="form-control custom-input"
                                placeholder="Search name / mobile / code / NIC" value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <select name="branch_id" class="form-select custom-input" aria-label="Filter by branch">
                            <option value="">All Accessible Branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <select name="is_active" class="form-select custom-input" aria-label="Filter by status">
                            <option value="">All Status</option>
                            <option value="true" {{ request('is_active') === 'true' ? 'selected' : '' }}>Active</option>
                            <option value="false" {{ request('is_active') === 'false' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-lg-1">
                        <button class="nx-btn nx-btn-primary w-100" type="submit" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-lg-2">
                        <a href="{{ route('organizers.index') }}" class="nx-btn nx-btn-light w-100">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Organizer</th>
                        <th>Branches</th>
                        <th>Contact</th>
                        <th>NIC</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($organizers as $organizer)
                        <tr>
                            <td class="row-number">
                                {{ $loop->iteration + ($organizers->currentPage() - 1) * $organizers->perPage() }}
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="organizer-avatar">
                                        {{ strtoupper(substr($organizer->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="cell-title">{{ $organizer->name }}</div>
                                        <small class="cell-sub">{{ $organizer->code }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                @forelse ($organizer->branches as $branch)
                                    <span class="nx-badge nx-badge-gray mb-1">{{ $branch->name }}</span>
                                @empty
                                    <span class="cell-sub">N/A</span>
                                @endforelse
                            </td>

                            <td>
                                <div class="cell-title">{{ $organizer->mobile }}</div>
                                <small class="cell-sub">{{ $organizer->email }}</small>
                            </td>

                            <td>
                                <span class="cell-title">{{ $organizer->nic }}</span>
                            </td>

                            <td>
                                @if ($organizer->is_active)
                                    <span class="nx-badge nx-badge-green">Active</span>
                                @else
                                    <span class="nx-badge nx-badge-gray">Inactive</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="{{ route('organizers.show', $organizer) }}" class="action-btn" title="View" aria-label="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="{{ route('organizers.edit', $organizer) }}" class="action-btn" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="{{ route('organizers.toggleActive', $organizer) }}"
                                        onsubmit="return confirm('Change active status?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="action-btn" title="Toggle Active" aria-label="Toggle Active">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('organizers.destroy', $organizer) }}"
                                        onsubmit="return confirm('Delete this organizer?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn action-btn-danger" title="Delete" aria-label="Delete">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-table">
                                <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                                <strong class="d-block">No Organizers Found</strong>
                                <span class="cell-sub">Try adjusting the search filters</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $organizers->links() }}
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Organizers — page-specific styles only.
       ============================================================ */

    .dashboard-page .organizer-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--nx-violet-soft);
        color: var(--nx-violet);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        flex-shrink: 0;
    }
</style>
@endpush