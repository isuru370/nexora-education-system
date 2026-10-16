@extends('layouts.app')

@section('title', 'Extra Incomes')
@section('page-title', 'Extra Incomes')

@section('content')

    @php
        $reportYear  = $selectedYear ?? now()->year;
        $reportMonth = $selectedMonth ?? 'all';

        $monthLabel = $reportMonth === 'all'
            ? 'All Months'
            : \Carbon\Carbon::create()->month((int) $reportMonth)->format('F');

        $statusLabel = request('status') ? ucfirst(request('status')) : 'All Status';
        $searchLabel = request('search') ? request('search') : 'All Records';
    @endphp

    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Extra Incomes</h1>
                    <p class="hero-subtitle">
                        Manage extra income records for {{ $reportYear }} · {{ $monthLabel }}
                    </p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($totalIncome, 2) }}</span>
                            <span class="mini-stat-label">Total Income</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $extraIncomes->total() }}</span>
                            <span class="mini-stat-label">Records</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('admin.extra-incomes.create') }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-plus-lg"></i> Add Income
                        </a>
                        <button type="button" onclick="window.print()" class="nx-btn nx-btn-light">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <button type="button" class="nx-btn nx-btn-light" disabled>
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </button>
                        <button type="button" class="nx-btn nx-btn-light" disabled>
                            <i class="bi bi-file-earmark-pdf"></i> PDF
                        </button>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- FILTER --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Filters</h5>
                    <p class="nx-card-subtitle">Filter by year, month, status or search</p>
                </div>
            </div>

            <div class="p-3">
                <form method="GET" action="{{ route('admin.extra-incomes.index') }}" class="row g-3 align-items-end">

                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Year</label>
                        <select name="year" class="form-select nx-input">
                            @foreach ($years as $year)
                                <option value="{{ $year }}" {{ $reportYear == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Month</label>
                        <select name="month" class="form-select nx-input">
                            <option value="all" {{ $reportMonth == 'all' ? 'selected' : '' }}>All Months</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (string) $reportMonth === (string) $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Status</label>
                        <select name="status" class="form-select nx-input">
                            <option value="">All</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control nx-input"
                            placeholder="Reason / Note">
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="nx-btn nx-btn-primary">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="{{ route('admin.extra-incomes.index') }}" class="nx-btn nx-btn-light">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- SUMMARY CARDS --}}
        {{-- ===================================================== --}}
        <div class="stats-grid stats-grid-3 mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Income</span>
                    <h2 class="stat-number">Rs. {{ number_format($totalIncome, 2) }}</h2>
                    <span class="stat-change neutral">{{ $searchLabel }}</span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Received</span>
                    <h2 class="stat-number">Rs. {{ number_format($receivedIncome, 2) }}</h2>
                    <span class="stat-change up">Approved and <span class="stat-change-note">received income</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-clock-history"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Pending</span>
                    <h2 class="stat-number">Rs. {{ number_format($pendingIncome, 2) }}</h2>
                    <span class="stat-change down">Not yet <span class="stat-change-note">received</span></span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- MAIN TABLE --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-cash-coin text-primary"></i> Income Records</h5>
                    <p class="nx-card-subtitle">Showing filtered extra income entries</p>
                </div>
                <span class="nx-badge nx-badge-blue">
                    <i class="bi bi-list-ul"></i> {{ $extraIncomes->total() }} Records
                </span>
            </div>

            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center" width="180">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($extraIncomes as $income)
                            <tr>
                                {{-- DATE --}}
                                <td class="cell-title">
                                    {{ $income->income_date->format('Y-m-d') }}
                                </td>

                                {{-- REASON --}}
                                <td>
                                    <div class="cell-title">{{ $income->reason ?? '-' }}</div>
                                    @if (!empty($income->note))
                                        <small class="cell-sub">
                                            {{ \Illuminate\Support\Str::limit($income->note, 60) }}
                                        </small>
                                    @endif
                                </td>

                                {{-- TYPE --}}
                                <td>
                                    <span class="nx-badge nx-badge-blue">
                                        {{ ucfirst($income->income_type) }}
                                    </span>
                                </td>

                                {{-- STATUS --}}
                                <td>
                                    @if ($income->status == 'received')
                                        <span class="nx-badge nx-badge-green">Received</span>
                                    @elseif ($income->status == 'pending')
                                        <span class="nx-badge nx-badge-amber">Pending</span>
                                    @elseif ($income->status == 'approved')
                                        <span class="nx-badge nx-badge-blue">Approved</span>
                                    @else
                                        <span class="nx-badge nx-badge-red">Cancelled</span>
                                    @endif
                                </td>

                                {{-- AMOUNT --}}
                                <td class="text-end cell-title">
                                    Rs. {{ number_format($income->amount, 2) }}
                                </td>

                                {{-- ACTIONS --}}
                                <td class="text-center">
                                    <div class="action-buttons justify-content-center">
                                        <a href="{{ route('admin.extra-incomes.show', $income) }}"
                                            class="action-btn" title="View" aria-label="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <a href="{{ route('admin.extra-incomes.edit', $income) }}"
                                            class="action-btn action-btn-warning" title="Edit" aria-label="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        <button type="button" class="action-btn" disabled
                                            title="Future Feature" aria-label="Future Feature">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-table">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    <strong class="d-block">No records found</strong>
                                    <span class="cell-sub">Try changing the filters or add a new extra income record.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top">
                {{ $extraIncomes->withQueryString()->links() }}
            </div>
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Extra Incomes — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    /* Print */
    @media print {
        .sidebar,
        .top-navbar,
        .header-buttons,
        .action-buttons,
        .nx-search,
        .pagination { display: none !important; }

        body { background: #fff !important; }
    }
</style>
@endpush