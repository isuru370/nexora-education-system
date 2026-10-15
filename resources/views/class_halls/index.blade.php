@extends('layouts.app')

@section('title', 'Class Halls')
@section('page-title', 'Class Halls')

@section('content')
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- HERO --}}
    {{-- ===================================================== --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Class Halls</h1>
                <p class="hero-subtitle">Manage physical and online halls used for classes.</p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $halls->total() }}</span>
                        <span class="mini-stat-label">Total Halls</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ \App\Models\ClassHall::where('is_active', 1)->count() }}</span>
                        <span class="mini-stat-label">Active</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ \App\Models\ClassHall::where('hall_price', 0)->count() }}</span>
                        <span class="mini-stat-label">Free</span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-date-time">
                    <div class="date-box">
                        <i class="bi bi-building"></i>
                        <span>Hall Directory</span>
                    </div>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- KPI CARDS --}}
    {{-- ===================================================== --}}
    <div class="stats-grid mb-4">

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-building"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Halls</span>
                <h2 class="stat-number">{{ $halls->total() }}</h2>
                <span class="stat-change neutral">All <span class="stat-change-note">halls</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Active Halls</span>
                <h2 class="stat-number">{{ \App\Models\ClassHall::where('is_active', 1)->count() }}</h2>
                <span class="stat-change up">Currently <span class="stat-change-note">active</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-info">
                <span class="stat-label">Free Halls</span>
                <h2 class="stat-number">{{ \App\Models\ClassHall::where('hall_price', 0)->count() }}</h2>
                <span class="stat-change neutral">No <span class="stat-change-note">price</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-red"><i class="bi bi-pause-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Inactive Halls</span>
                <h2 class="stat-number">{{ \App\Models\ClassHall::where('is_active', 0)->count() }}</h2>
                <span class="stat-change down">Not <span class="stat-change-note">active</span></span>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MAIN CARD --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-building text-primary"></i> Class Halls Management</h5>
                <p class="nx-card-subtitle">Manage physical and online halls used for classes</p>
            </div>

            <div class="header-buttons">
                <a href="{{ route('class-halls.create') }}" class="nx-btn nx-btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Hall
                </a>
                <a href="{{ route('student-classes.index') }}" class="nx-btn nx-btn-light">
                    <i class="bi bi-journal-bookmark-fill"></i> View Classes
                </a>
                <button class="nx-btn nx-btn-light" disabled>
                    <i class="bi bi-graph-up-arrow"></i> Analytics
                </button>
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('class-halls.index') }}">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="form-control custom-input"
                                placeholder="Search hall name / code" value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <select name="is_active" class="form-select custom-input">
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
                        <a href="{{ route('class-halls.index') }}" class="nx-btn nx-btn-light w-100">
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
                        <th>Hall</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($halls as $hall)
                        <tr>
                            <td class="row-number">
                                {{ $loop->iteration + ($halls->currentPage() - 1) * $halls->perPage() }}
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="hall-avatar">
                                        {{ strtoupper(substr($hall->hall_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="cell-title">{{ $hall->hall_name }}</div>
                                        <small class="cell-sub">{{ $hall->code }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="cell-title">{{ $hall->hall_type ?? '-' }}</span>
                            </td>

                            <td>
                                @if ((float) $hall->hall_price == 0)
                                    <span class="nx-badge nx-badge-green">Free</span>
                                @else
                                    <span class="cell-title">{{ number_format($hall->hall_price, 2) }}</span>
                                @endif
                            </td>

                            <td>
                                @if ($hall->is_active)
                                    <span class="nx-badge nx-badge-green">Active</span>
                                @else
                                    <span class="nx-badge nx-badge-gray">Inactive</span>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="{{ route('class-halls.show', $hall) }}" class="action-btn" title="View" aria-label="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="{{ route('class-halls.edit', $hall) }}" class="action-btn" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="{{ route('class-halls.toggleActive', $hall) }}"
                                        onsubmit="return confirm('Change active status?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="action-btn" title="Toggle Active" aria-label="Toggle Active">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('class-halls.destroy', $hall) }}"
                                        onsubmit="return confirm('Delete this hall?')">
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
                            <td colspan="6" class="empty-table">
                                <i class="bi bi-building-x fs-2 d-block mb-2"></i>
                                <strong class="d-block">No Class Halls Found</strong>
                                <span class="cell-sub">Try adjusting the search filters</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $halls->links() }}
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Class Halls — page-specific styles only.
       ============================================================ */

    .dashboard-page .hall-avatar {
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