@extends('layouts.app')

@section('title', 'Collection Report')
@section('page-title', 'Collection Report')

@section('content')
    @php
        $query = $filter->toQuery();

        $byBranch = $summary['by_branch'];

        // The "By Branch" tab is only useful when the user has more than one branch
        if ($branches->count() <= 1 && $tab !== 'branches') {
            unset($tabs['branches']);
        }
    @endphp

    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Collection Report</h1>
                    <p class="hero-subtitle">
                        <i class="bi bi-calendar-event"></i> {{ $filter->dateLabel() }}
                        • <i class="bi bi-building"></i> {{ $branchName }}
                    </p>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('reports.daily-collection.export', ['tab' => $tab, 'format' => 'excel'] + $query) }}"
                            class="nx-btn nx-btn-light">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                        </a>
                        <a href="{{ route('reports.daily-collection.export', ['tab' => $tab, 'format' => 'pdf'] + $query) }}"
                            class="nx-btn nx-btn-light">
                            <i class="bi bi-filetype-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- VALIDATION ERRORS --}}
        {{-- ===================================================== --}}
        @if ($errors->any())
            <div class="nx-alert nx-alert-red mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- FILTER --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Filters</h5>
                    <p class="nx-card-subtitle">Filter by date, branch, collector or payment method</p>
                </div>
            </div>

            <div class="p-3">
                <form method="GET"
                    action="{{ route('reports.daily-collection.index', ['tab' => $tab]) }}"
                    class="row g-3 align-items-end">

                    <div class="col-lg-2 col-md-4">
                        <label class="nx-label">From</label>
                        <input type="date" name="from" class="form-control nx-input"
                            value="{{ $filter->from->toDateString() }}"
                            max="{{ now()->toDateString() }}">
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="nx-label">To</label>
                        <input type="date" name="to" class="form-control nx-input"
                            value="{{ $filter->to->toDateString() }}"
                            max="{{ now()->toDateString() }}">
                    </div>

                    @if ($branches->count() > 1)
                        <div class="col-lg-2 col-md-4">
                            <label class="nx-label">Branch</label>
                            <select name="branch_id" class="form-select nx-input">
                                <option value="">All branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (int) $filter->branchId === (int) $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-lg-2 col-md-4">
                        <label class="nx-label">Collected by</label>
                        <select name="collected_by" class="form-select nx-input">
                            <option value="">Everyone</option>
                            @foreach ($collectors as $collector)
                                <option value="{{ $collector->id }}" {{ (int) $filter->collectedBy === (int) $collector->id ? 'selected' : '' }}>
                                    {{ $collector->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="nx-label">Payment method</label>
                        <select name="payment_method" class="form-select nx-input">
                            <option value="">All methods</option>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method }}" {{ $filter->paymentMethod === $method ? 'selected' : '' }}>
                                    {{ $methodLabel($method) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 d-flex gap-2">
                        <button type="submit" class="nx-btn nx-btn-primary flex-grow-1">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                        <a href="{{ route('reports.daily-collection.index', ['tab' => $tab]) }}"
                            class="nx-btn nx-btn-light" title="Reset to today">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>

                @if ($summary['show_expenses'] === false)
                    <div class="nx-alert nx-alert-blue mt-3">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>
                            "Collected by" and "Payment method" only apply to class fees and admission fees,
                            so extra income and expenses are hidden.
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- STATS --}}
        {{-- ===================================================== --}}
        <div class="stats-grid mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-arrow-down-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Income</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['total_income'], 2) }}</h2>
                    <span class="stat-change up">Collected <span class="stat-change-note">income</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-credit-card-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Class Fees · {{ $summary['student_payment_count'] }} payments</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['income']['Class fee payments'], 2) }}</h2>
                    <span class="stat-change neutral">Student <span class="stat-change-note">payments</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-arrow-up-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Expenses</span>
                    <h2 class="stat-number">
                        {{ $summary['show_expenses'] ? 'Rs. ' . number_format($summary['total_expenses'], 2) : '-' }}
                    </h2>
                    <span class="stat-change down">Spent <span class="stat-change-note">amount</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-wallet2"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Net Balance</span>
                    <h2 class="stat-number">
                        {{ $summary['show_expenses'] ? 'Rs. ' . number_format($summary['net'], 2) : '-' }}
                    </h2>
                    <span class="stat-change neutral">Income <span class="stat-change-note">minus expenses</span></span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- TABS --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4 p-3">
            <ul class="nav nx-tabs gap-2">
                @foreach ($tabs as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                            href="{{ route('reports.daily-collection.index', ['tab' => $key] + $query) }}">
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- ===================================================== --}}
        {{-- TAB CONTENT --}}
        {{-- ===================================================== --}}
        @if ($tab === 'summary')

            <div class="row g-3">

                {{-- INCOME --}}
                <div class="col-lg-6">
                    <div class="nx-card h-100">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title">
                                    <i class="bi bi-arrow-down-circle text-success"></i> Income
                                </h5>
                                <p class="nx-card-subtitle">Breakdown by category</p>
                            </div>
                        </div>
                        <div class="p-3">
                            <table class="table premium-table mb-0">
                                @foreach ($summary['income'] as $label => $amount)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end cell-title">Rs. {{ number_format($amount, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="fw-bold" style="border-top: 2px solid var(--nx-border);">
                                    <td>Total income</td>
                                    <td class="text-end cell-title">Rs. {{ number_format($summary['total_income'], 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- EXPENSES --}}
                <div class="col-lg-6">
                    <div class="nx-card h-100">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title">
                                    <i class="bi bi-arrow-up-circle text-danger"></i> Expenses
                                </h5>
                                <p class="nx-card-subtitle">Breakdown by category</p>
                            </div>
                        </div>
                        <div class="p-3">
                            @if ($summary['show_expenses'])
                                <table class="table premium-table mb-0">
                                    @foreach ($summary['expenses'] as $label => $amount)
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td class="text-end cell-title">Rs. {{ number_format($amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="fw-bold" style="border-top: 2px solid var(--nx-border);">
                                        <td>Total expenses</td>
                                        <td class="text-end cell-title">Rs. {{ number_format($summary['total_expenses'], 2) }}</td>
                                    </tr>
                                    <tr class="fw-bold" style="border-top: 2px solid var(--nx-border);">
                                        <td class="text-primary">Net balance</td>
                                        <td class="text-end cell-title text-primary">Rs. {{ number_format($summary['net'], 2) }}</td>
                                    </tr>
                                </table>
                            @else
                                <p class="cell-sub mb-0">Hidden while a collector or payment method filter is used.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ✅ BY BRANCH (conditional) --}}
                @if ($byBranch->count() > 1)
                    <div class="col-12">
                        <div class="nx-card h-100">
                            <div class="nx-card-header">
                                <div>
                                    <h5 class="nx-card-title">
                                        <i class="bi bi-diagram-3 text-primary"></i> By branch
                                    </h5>
                                    <p class="nx-card-subtitle">Income and expenses per branch</p>
                                </div>
                                <a href="{{ route('reports.daily-collection.index', ['tab' => 'branches'] + $query) }}"
                                    class="nx-btn nx-btn-light">
                                    Full breakdown <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table premium-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Branch</th>
                                            <th class="text-end">Payments</th>
                                            <th class="text-end">Income</th>
                                            @if ($summary['show_expenses'])
                                                <th class="text-end">Expenses</th>
                                                <th class="text-end">Net</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($byBranch as $row)
                                            <tr class="{{ $row['key'] === 'shared' ? 'row-muted' : '' }}">
                                                <td class="cell-title">{{ $row['branch'] }}</td>
                                                <td class="text-end cell-sub">{{ number_format($row['payments']) }}</td>
                                                <td class="text-end cell-title">Rs. {{ number_format($row['total_income'], 2) }}</td>
                                                @if ($summary['show_expenses'])
                                                    <td class="text-end cell-title {{ $row['total_expenses'] > 0 ? 'text-danger' : '' }}">
                                                        Rs. {{ number_format($row['total_expenses'], 2) }}
                                                    </td>
                                                    <td class="text-end cell-title {{ $row['net'] < 0 ? 'text-danger' : 'text-success' }}">
                                                        Rs. {{ number_format($row['net'], 2) }}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- BY PAYMENT METHOD --}}
                <div class="col-lg-4">
                    <div class="nx-card h-100">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-credit-card text-primary"></i> By payment method</h5>
                            </div>
                        </div>
                        <div class="p-3">
                            <table class="table premium-table mb-0">
                                @forelse ($summary['by_method'] as $row)
                                    <tr>
                                        <td>
                                            {{ $row['label'] }}
                                            <small class="cell-sub">({{ $row['count'] }})</small>
                                        </td>
                                        <td class="text-end cell-title">Rs. {{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="cell-sub">No payments</td></tr>
                                @endforelse
                            </table>
                        </div>
                    </div>
                </div>

                {{-- BY COLLECTOR --}}
                <div class="col-lg-4">
                    <div class="nx-card h-100">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-person-badge text-primary"></i> By collector</h5>
                            </div>
                        </div>
                        <div class="p-3">
                            <table class="table premium-table mb-0">
                                @forelse ($summary['by_collector'] as $row)
                                    <tr>
                                        <td>
                                            {{ $row['name'] }}
                                            <small class="cell-sub">({{ $row['count'] }})</small>
                                        </td>
                                        <td class="text-end cell-title">Rs. {{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td class="cell-sub">No payments</td></tr>
                                @endforelse
                            </table>
                        </div>
                    </div>
                </div>

                {{-- CLASS FEE SPLIT --}}
                <div class="col-lg-4">
                    <div class="nx-card h-100">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-pie-chart text-primary"></i> Class fee split</h5>
                            </div>
                        </div>
                        <div class="p-3">
                            <table class="table premium-table mb-0">
                                @foreach ($summary['split'] as $label => $amount)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end cell-title">Rs. {{ number_format($amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        @else

            {{-- OTHER TABS --}}
            <div class="nx-card">
                                @if ($tab === 'branches' && !$summary['show_expenses'])
                    <div class="p-3 pb-0">
                        <p class="cell-sub mb-0">
                            <i class="bi bi-info-circle"></i>
                            Only class fees and admission fees are shown while a collector or payment method filter is used.
                        </p>
                    </div>
                @endif

                @if ($tab === 'expenses' && !$summary['show_expenses'])
                    <div class="p-3">
                        <p class="cell-sub mb-0">
                            Expenses are hidden while a collector or payment method filter is used.
                        </p>
                    </div>
                @else
                    @include('reports.partials.table', ['table' => $table, 'rows' => $rows])
                @endif
            </div>

        @endif

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Daily Collection Report — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.blade.php
       ============================================================ */

    .dashboard-page .nx-tabs {
        border-bottom: 0;
        flex-wrap: wrap;
    }

    .dashboard-page .nx-tabs .nav-link {
        border-radius: 8px;
        padding: .45rem .95rem;
        font-size: .82rem;
        font-weight: 600;
        color: var(--nx-muted);
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        transition: all .15s ease;
    }

    .dashboard-page .nx-tabs .nav-link:hover {
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        border-color: var(--nx-blue);
    }

    .dashboard-page .nx-tabs .nav-link.active {
        background: var(--nx-blue);
        color: #fff;
        border-color: var(--nx-blue);
        box-shadow: 0 2px 8px rgba(29, 78, 216, .25);
    }

    /* Table row borders for summary lists */
    .dashboard-page .premium-table tbody tr {
        border-bottom: 1px solid var(--nx-border);
    }
    .dashboard-page .premium-table tbody tr:last-child {
        border-bottom: 0;
    }
</style>
@endpush