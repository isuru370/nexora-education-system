@extends('layouts.app')

@section('title', 'Receipts - ' . config('app.name', 'EDU NEXORA'))
@section('page-title', 'Receipts')

@php
    $currentStatus = request('status', 'active');

    $typeStyles = [
        'Student Payment'   => ['nx-badge-blue',   'bi-mortarboard'],
        'Admission Payment' => ['nx-badge-green',  'bi-journal-bookmark'],
        'Extra Income'      => ['nx-badge-amber',  'bi-cash'],
    ];

    // Course payments only for institutes with the "courses" feature
    if (feature('courses')) {
        $typeStyles['Course Payment'] = ['nx-badge-violet', 'bi-mortarboard-fill'];
    }

    $hasFilters = request()->hasAny(['receipt_number', 'type', 'from_date', 'to_date'])
        && collect(request()->only(['receipt_number', 'type', 'from_date', 'to_date']))->filter()->isNotEmpty();
@endphp

@section('content')
    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Receipts</h1>
                    <p class="hero-subtitle">Search, filter and manage all payment receipts.</p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">Rs. {{ number_format($totalAmount ?? 0, 2) }}</span>
                            <span class="mini-stat-label">Active Total</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($activeReceipts ?? 0) }}</span>
                            <span class="mini-stat-label">Active</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($totalReceipts ?? 0) }}</span>
                            <span class="mini-stat-label">In List</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-date-time">
                        <div class="date-box">
                            <i class="bi bi-receipt"></i>
                            <span>Receipt Directory</span>
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
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Active Total</span>
                    <h2 class="stat-number">Rs. {{ number_format($totalAmount ?? 0, 2) }}</h2>
                    <span class="stat-change up">Total <span class="stat-change-note">receipts value</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-receipt"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Active Receipts</span>
                    <h2 class="stat-number">{{ number_format($activeReceipts ?? 0) }}</h2>
                    <span class="stat-change neutral">Issued <span class="stat-change-note">receipts</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-trash3"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Deleted Receipts</span>
                    <h2 class="stat-number">{{ number_format($deletedReceipts ?? 0) }}</h2>
                    @if (($deletedAmount ?? 0) > 0)
                        <span class="stat-change down">
                            Rs. {{ number_format($deletedAmount, 2) }} <span class="stat-change-note">removed</span>
                        </span>
                    @else
                        <span class="stat-change neutral">No <span class="stat-change-note">deletions</span></span>
                    @endif
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-calendar-week"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Shown in List</span>
                    <h2 class="stat-number">{{ number_format($totalReceipts ?? 0) }}</h2>
                    <span class="stat-change neutral">Current <span class="stat-change-note">filter result</span></span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- FILTERS --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel-fill text-primary"></i> Filter Receipts</h5>
                    <p class="nx-card-subtitle">Search and filter your receipts</p>
                </div>
            </div>

            <div class="p-3">
                <form method="GET" action="{{ route('receipts.index') }}">
                    <div class="row g-3">

                        <div class="col-lg-3 col-md-6">
                            <label class="nx-label" for="receipt_number">
                                <i class="bi bi-receipt"></i> Receipt Number
                            </label>
                            <input type="text" id="receipt_number" name="receipt_number" class="form-control custom-input"
                                value="{{ request('receipt_number') }}" placeholder="e.g. REC-000123">
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="nx-label" for="type">
                                <i class="bi bi-tag"></i> Type
                            </label>
                            <select id="type" name="type" class="form-select custom-input">
                                <option value="">All Types</option>
                                @foreach (array_keys($typeStyles) as $type)
                                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="nx-label" for="status">
                                <i class="bi bi-funnel"></i> Status
                            </label>
                            <select id="status" name="status" class="form-select custom-input">
                                <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="deleted" {{ $currentStatus === 'deleted' ? 'selected' : '' }}>Deleted</option>
                                <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="nx-label" for="from_date">
                                <i class="bi bi-calendar"></i> From Date
                            </label>
                            <input type="date" id="from_date" name="from_date" class="form-control custom-input"
                                value="{{ request('from_date') }}">
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="nx-label" for="to_date">
                                <i class="bi bi-calendar"></i> To Date
                            </label>
                            <input type="date" id="to_date" name="to_date" class="form-control custom-input"
                                value="{{ request('to_date') }}">
                        </div>

                        <div class="col-lg-1 col-md-12">
                            <label class="nx-label d-none d-lg-block">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="nx-btn nx-btn-primary flex-grow-1" title="Search" aria-label="Search">
                                    <i class="bi bi-search"></i>
                                </button>
                                <a href="{{ route('receipts.index') }}" class="nx-btn nx-btn-light" title="Reset" aria-label="Reset">
                                    <i class="bi bi-arrow-repeat"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- RECEIPTS TABLE --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-receipt text-primary"></i> Receipt List</h5>
                    <p class="nx-card-subtitle">
                        @if ($currentStatus === 'deleted')
                            Deleted receipts (audit view)
                        @elseif ($currentStatus === 'all')
                            All receipts, including deleted
                        @else
                            Active payment receipts
                        @endif
                    </p>
                </div>

                <div class="header-buttons">
                    <button type="button" class="nx-btn nx-btn-light" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="{{ route('receipts.export.excel', request()->query()) }}" class="nx-btn nx-btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                    <a href="{{ route('receipts.export.pdf', request()->query()) }}" class="nx-btn nx-btn-danger">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Type</th>
                            <th>Student / Class</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Date &amp; Time</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($receipts as $receipt)
                            @php
                                $isActive = $receipt['status'] === 'Active';
                                [$typeClass, $typeIcon] = $typeStyles[$receipt['type']] ?? ['nx-badge-gray', 'bi-receipt'];
                                $hasStudent = ($receipt['student_name'] ?? 'N/A') !== 'N/A';
                                $hasClass = !empty($receipt['class_name']) && $receipt['class_name'] !== 'N/A';
                            @endphp

                            <tr class="{{ $isActive ? '' : 'row-muted' }}">
                                {{-- Receipt No --}}
                                <td>
                                    <div class="receipt-number">
                                        <i class="bi bi-receipt-cutoff"></i>

                                        @if ($receipt['url'])
                                            <a href="{{ $receipt['url'] }}" class="receipt-link">
                                                {{ $receipt['receipt_number'] ?: '-' }}
                                            </a>
                                        @else
                                            <span class="cell-title {{ $isActive ? '' : 'text-danger' }}">
                                                {{ $receipt['receipt_number'] ?: '-' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Type --}}
                                <td>
                                    <span class="nx-badge {{ $typeClass }}">
                                        <i class="bi {{ $typeIcon }}"></i>
                                        {{ $receipt['type'] }}
                                    </span>
                                </td>

                                {{-- Student / Class --}}
                                <td>
                                    @if ($hasStudent)
                                        <div class="cell-title">{{ $receipt['student_name'] }}</div>
                                        <small class="cell-sub">
                                            {{ $receipt['student_id'] }}
                                            @if ($hasClass) · {{ $receipt['class_name'] }} @endif
                                            @if (!empty($receipt['payment_month']))
                                                · {{ \Carbon\Carbon::parse($receipt['payment_month'])->format('M Y') }}
                                            @endif
                                        </small>
                                    @else
                                        <span class="cell-sub">—</span>
                                    @endif
                                </td>

                                {{-- Amount --}}
                                <td class="text-end amount-cell">
                                    <span class="currency">Rs.</span>
                                    <span class="amount {{ $isActive ? '' : 'amount-void' }}">
                                        {{ number_format((float) $receipt['amount'], 2) }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if ($isActive)
                                        <span class="nx-badge nx-badge-green">Active</span>
                                    @else
                                        <span class="nx-badge nx-badge-red">{{ $receipt['status'] }}</span>

                                        @if (!empty($receipt['delete_reason']))
                                            <div class="delete-info">
                                                {{ $receipt['delete_reason'] }}
                                                @if (!empty($receipt['deleted_by']))
                                                    <br>by {{ $receipt['deleted_by'] }}
                                                @endif
                                                @if (!empty($receipt['deleted_at']))
                                                    · {{ \Carbon\Carbon::parse($receipt['deleted_at'])->format('Y-m-d h:i A') }}
                                                @endif
                                            </div>
                                        @endif
                                    @endif
                                </td>

                                {{-- Date & Time --}}
                                <td>
                                    @if ($receipt['date'])
                                        <div class="date-time-cell">
                                            <div class="date">
                                                <i class="bi bi-calendar3"></i>
                                                {{ \Carbon\Carbon::parse($receipt['date'])->format('Y-m-d') }}
                                            </div>
                                            <div class="time">
                                                <i class="bi bi-clock"></i>
                                                {{ \Carbon\Carbon::parse($receipt['date'])->format('h:i A') }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="cell-sub">—</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="text-end">
                                    <div class="action-buttons">
                                        @if ($receipt['url'])
                                            <a href="{{ $receipt['url'] }}" class="action-btn" title="View receipt" aria-label="View receipt">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                        @elseif (!$isActive)
                                            <span class="action-btn locked-btn" title="Deleted receipts cannot be opened">
                                                <i class="bi bi-slash-circle"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-table">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    <strong class="d-block">No Receipts Found</strong>
                                    <span class="cell-sub">
                                        @if ($hasFilters || $currentStatus !== 'active')
                                            No receipts match your search criteria
                                        @else
                                            No receipts have been issued yet
                                        @endif
                                    </span>

                                    @if ($hasFilters || $currentStatus !== 'active')
                                        <div class="mt-3">
                                            <a href="{{ route('receipts.index') }}" class="nx-btn nx-btn-primary">
                                                <i class="bi bi-arrow-repeat"></i> Clear Filters
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($receipts->hasPages())
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">
                    <small class="text-muted">
                        Showing {{ $receipts->firstItem() }}-{{ $receipts->lastItem() }} of {{ number_format($receipts->total()) }}
                    </small>
                    {{ $receipts->onEachSide(1)->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Receipts — page-specific styles only.
       Shared nx-* theme comes from layouts/partials/nexora-theme.
       ============================================================ */

    /* Receipt number cell */
    .dashboard-page .receipt-number {
        display: flex;
        align-items: center;
        gap: .5rem;
        white-space: nowrap;
    }
    .dashboard-page .receipt-number i { color: var(--nx-green); }

    .dashboard-page .receipt-link {
        font-weight: 700;
        color: var(--nx-blue);
        text-decoration: none;
    }
    .dashboard-page .receipt-link:hover {
        color: var(--nx-violet);
        text-decoration: underline;
    }

    /* Amount */
    .dashboard-page .amount-cell {
        font-weight: 700;
        white-space: nowrap;
    }
    .dashboard-page .currency {
        color: var(--nx-muted);
        font-size: .7rem;
        margin-right: .2rem;
    }
    .dashboard-page .amount { color: var(--nx-text); }
    .dashboard-page .amount-void {
        color: var(--nx-muted);
        text-decoration: line-through;
    }

    /* Deletion info under the badge */
    .dashboard-page .delete-info {
        margin-top: .35rem;
        max-width: 220px;
        font-size: .72rem;
        color: var(--nx-muted);
        white-space: normal;
    }

    /* Date / time */
    .dashboard-page .date-time-cell {
        display: flex;
        flex-direction: column;
        gap: .2rem;
        white-space: nowrap;
    }
    .dashboard-page .date,
    .dashboard-page .time {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .75rem;
    }
    .dashboard-page .date i,
    .dashboard-page .time i {
        color: var(--nx-muted);
        font-size: .7rem;
    }

    /* Locked (deleted) action button */
    .dashboard-page .locked-btn {
        background: #f1f3f7;
        color: var(--nx-muted);
        cursor: not-allowed;
        border-color: var(--nx-border);
    }
    .dashboard-page .locked-btn:hover {
        background: #f1f3f7;
        color: var(--nx-muted);
        border-color: var(--nx-border);
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 992px) {
        .dashboard-page .premium-table { min-width: 1000px; }
    }

    /* ---------- Print ---------- */
    @media print {
        .dashboard-page .nx-search,
        .dashboard-page .header-buttons,
        .dashboard-page .action-buttons,
        .sidebar,
        .top-navbar {
            display: none !important;
        }

        .dashboard-page .nx-card { box-shadow: none !important; }
    }
</style>
@endpush