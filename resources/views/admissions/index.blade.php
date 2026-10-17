@extends('layouts.app')

@section('title', 'Admissions')
@section('page-title', 'Admissions')

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
                        Admission Management
                    </span>
                    <h1 class="hero-title">Admission Fees</h1>
                    <p class="hero-subtitle">
                        Manage admission fee structures and settings for student enrollments.
                    </p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $admissions->total() }}</span>
                            <span class="mini-stat-label">Total</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $admissions->where('is_active', true)->count() }}</span>
                            <span class="mini-stat-label">Active</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">Rs. {{ number_format($admissions->sum('amount'), 2) }}</span>
                            <span class="mini-stat-label">Total Amount</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('admissions.create') }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-plus-lg"></i> Add New Admission
                        </a>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }} · {{ now()->format('h:i A') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- KPI CARDS --}}
        {{-- ===================================================== --}}
        <div class="stats-grid stats-grid-3 mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-journal-bookmark-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Admissions</span>
                    <h2 class="stat-number">{{ $admissions->total() }}</h2>
                    <span class="stat-change neutral">All <span class="stat-change-note">admission records</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Active Admissions</span>
                    <h2 class="stat-number">{{ $admissions->where('is_active', true)->count() }}</h2>
                    <span class="stat-change up">Currently <span class="stat-change-note">active</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Amount</span>
                    <h2 class="stat-number">Rs. {{ number_format($admissions->sum('amount'), 2) }}</h2>
                    <span class="stat-change neutral">Sum of <span class="stat-change-note">all fees</span></span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- FILTER --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-search text-primary"></i> Search Admissions</h5>
                    <p class="nx-card-subtitle">Search by admission name or note</p>
                </div>
            </div>

            <div class="p-3">
                <form method="GET" action="{{ route('admissions.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="nx-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control nx-input"
                            placeholder="Search by admission name or note...">
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="nx-btn nx-btn-primary flex-grow-1">
                                <i class="bi bi-search"></i> Search
                            </button>
                            @if (request('search'))
                                <a href="{{ route('admissions.index') }}" class="nx-btn nx-btn-light">
                                    <i class="bi bi-x-circle"></i> Clear
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- TABLE --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-table text-primary"></i> Admissions List</h5>
                    <p class="nx-card-subtitle">All admission fee records</p>
                </div>
                <div class="header-buttons">
                    <a href="{{ route('admissions.create') }}" class="nx-btn nx-btn-primary">
                        <i class="bi bi-plus-lg"></i> Add New Admission
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 25%;">Admission Name</th>
                            <th style="width: 15%;">Amount</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 30%;">Note</th>
                            <th style="width: 15%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($admissions as $admission)
                            <tr>
                                <td class="row-number">{{ $admissions->firstItem() + $loop->index }}</td>

                                <td class="cell-title">{{ $admission->name }}</td>

                                <td class="amount-value">Rs. {{ number_format($admission->amount, 2) }}</td>

                                <td>
                                    @if ($admission->is_active)
                                        <span class="nx-badge nx-badge-green">
                                            <i class="bi bi-check-circle-fill"></i> Active
                                        </span>
                                    @else
                                        <span class="nx-badge nx-badge-red">
                                            <i class="bi bi-x-circle-fill"></i> Inactive
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($admission->note)
                                        <span class="cell-sub">{{ \Illuminate\Support\Str::limit($admission->note, 50) }}</span>
                                    @else
                                        <span class="cell-sub">—</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="{{ route('admissions.show', $admission) }}" class="action-btn" title="View" aria-label="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <a href="{{ route('admissions.edit', $admission) }}" class="action-btn action-btn-warning" title="Edit" aria-label="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        <form action="{{ route('admissions.destroy', $admission) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this admission?')">
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
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    <strong class="d-block">No Admissions Found</strong>
                                    <span class="cell-sub">Click "Add New Admission" to create your first admission record.</span>
                                    <div class="mt-3">
                                        <a href="{{ route('admissions.create') }}" class="nx-btn nx-btn-primary">
                                            <i class="bi bi-plus-lg"></i> Add New Admission
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($admissions->hasPages())
                <div class="p-3 border-top">
                    {{ $admissions->links() }}
                </div>
            @endif
        </div>

        {{-- FOOTER NOTE --}}
        <div class="text-center">
            <small class="cell-sub">
                <i class="bi bi-info-circle"></i>
                Showing {{ $admissions->firstItem() ?? 0 }} to {{ $admissions->lastItem() ?? 0 }} of
                {{ $admissions->total() }} admissions
            </small>
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Admissions — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .amount-value {
        font-weight: 700;
        color: var(--nx-green);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        white-space: nowrap;
    }

    @media (max-width: 992px) {
        .dashboard-page .premium-table { min-width: 800px; }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        /* Auto-hide alerts after 5 seconds */
        const alerts = document.querySelectorAll('.nx-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) alert.remove();
                }, 500);
            }, 5000);
        });
    });
</script>
@endpush