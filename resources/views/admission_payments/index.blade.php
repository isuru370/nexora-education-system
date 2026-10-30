@extends('layouts.app')

@section('title', 'Admission Payments')
@section('page-title', 'Admission Payments')

@php
    $summary = $summary ?? [];

    $statusBadges = [
        'pending'   => ['nx-badge-amber', 'Pending'],
        'paid'      => ['nx-badge-green', 'Paid'],
        'cancelled' => ['nx-badge-red',   'Cancelled'],
        'refunded'  => ['nx-badge-blue',  'Refunded'],
    ];

    $methodBadges = [
        'cash'          => ['nx-badge-gray',   'Cash'],
        'card'          => ['nx-badge-blue',   'Card'],
        'bank_transfer' => ['nx-badge-blue',   'Bank Transfer'],
        'online'        => ['nx-badge-green',  'Online'],
        'other'         => ['nx-badge-gray',   'Other'],
    ];

    $hasFilters = request()->filled('search') || request()->filled('status');
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
                    <h1 class="hero-title">Admission Payments</h1>
                    <p class="hero-subtitle">Manage student admission payment records, status and payment details.</p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($summary['total'] ?? 0) }}</span>
                            <span class="mini-stat-label">Total Payments</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($summary['paid'] ?? 0) }}</span>
                            <span class="mini-stat-label">Paid</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">Rs. {{ number_format($summary['paid_amount'] ?? 0, 2) }}</span>
                            <span class="mini-stat-label">Collected</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('admission-payments.create') }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-plus-lg"></i> Add Payment
                        </a>
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
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-receipt"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Payments</span>
                    <h2 class="stat-number">{{ number_format($summary['total'] ?? 0) }}</h2>
                    <span class="stat-change neutral">All <span class="stat-change-note">payments</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Paid</span>
                    <h2 class="stat-number">{{ number_format($summary['paid'] ?? 0) }}</h2>
                    @if (($summary['cancelled'] ?? 0) + ($summary['refunded'] ?? 0) > 0)
                        <span class="stat-change down">
                            {{ $summary['cancelled'] ?? 0 }} cancelled · {{ $summary['refunded'] ?? 0 }} refunded
                        </span>
                    @else
                        <span class="stat-change up">All <span class="stat-change-note">paid</span></span>
                    @endif
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-clock-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Pending</span>
                    <h2 class="stat-number">{{ number_format($summary['pending'] ?? 0) }}</h2>
                    @if (($summary['pending_amount'] ?? 0) > 0)
                        <span class="stat-change down">
                            Rs. {{ number_format($summary['pending_amount'], 2) }} <span class="stat-change-note">due</span>
                        </span>
                    @else
                        <span class="stat-change neutral">No <span class="stat-change-note">pending</span></span>
                    @endif
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-cash-coin"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Collected</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['paid_amount'] ?? 0, 2) }}</h2>
                    <span class="stat-change up">
                        Today: Rs. {{ number_format($summary['today_paid_amount'] ?? 0, 2) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- MAIN CARD --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-receipt text-primary"></i> Admission Payments</h5>
                    <p class="nx-card-subtitle">Manage student admission payment records, status and payment details</p>
                </div>
            </div>

            {{-- ALERTS --}}
            @if (session('success'))
                <div class="p-3 pb-0">
                    <div class="nx-alert nx-alert-green">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-3 pb-0">
                    <div class="nx-alert nx-alert-red">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            {{-- SEARCH / FILTER --}}
            <div class="nx-search">
                <form method="GET" action="{{ route('admission-payments.index') }}">
                    <div class="row g-3 align-items-center">

                        <div class="col-lg-6">
                            <div class="search-input-wrapper">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" value="{{ request('search') }}"
                                    class="form-control custom-input"
                                    placeholder="Search receipt / student name / student ID / admission">
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <select name="status" class="form-select custom-input">
                                <option value="">All Status</option>
                                @foreach ($statusBadges as $value => [$class, $label])
                                    <option value="{{ $value }}" {{ (request('status') === $value) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 d-flex gap-2">
                            <button type="submit" class="nx-btn nx-btn-primary flex-grow-1">
                                <i class="bi bi-funnel-fill"></i> Search
                            </button>

                            @if ($hasFilters)
                                <a href="{{ route('admission-payments.index') }}" class="nx-btn nx-btn-light" title="Clear filters" aria-label="Clear filters">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- RESULT COUNT --}}
            @if ($payments->total() > 0)
                <div class="p-3 pb-0">
                    <p class="cell-sub mb-0">
                        Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ number_format($payments->total()) }}
                        {{ $hasFilters ? 'matching' : '' }} payments
                    </p>
                </div>
            @endif

            {{-- TABLE --}}
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Receipt</th>
                            <th>Student</th>
                            <th>Admission</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($payments as $payment)
                            @php
                                [$statusClass, $statusLabel] = $statusBadges[$payment->status] ?? ['nx-badge-gray', 'Unknown'];
                                [$methodClass, $methodLabel] = $methodBadges[$payment->payment_method] ?? ['nx-badge-gray', 'Unknown'];
                                $canDelete = $payment->status !== 'paid';
                            @endphp

                            <tr>
                                <td class="row-number">{{ $payments->firstItem() + $loop->index }}</td>

                                {{-- RECEIPT --}}
                                <td>
                                    <div class="cell-title">{{ $payment->receipt_number ?: '-' }}</div>
                                    <small class="cell-sub">
                                        {{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : 'Not paid yet' }}
                                    </small>
                                </td>

                                {{-- STUDENT --}}
                                <td>
                                    <div class="cell-title">{{ optional($payment->student)->initial_name ?? '-' }}</div>
                                    <small class="cell-sub">{{ optional($payment->student)->custom_id ?? '-' }}</small>
                                </td>

                                {{-- ADMISSION --}}
                                <td>
                                    <div class="cell-title">{{ optional($payment->admission)->name ?? '-' }}</div>
                                    @if ($payment->collectedBy)
                                        <small class="cell-sub">by {{ $payment->collectedBy->name }}</small>
                                    @endif
                                </td>

                                {{-- AMOUNT --}}
                                <td>
                                    <div class="amount-text {{ in_array($payment->status, ['cancelled', 'refunded'], true) ? 'amount-void' : '' }}">
                                        Rs. {{ number_format((float) $payment->amount, 2) }}
                                    </div>
                                </td>

                                {{-- METHOD --}}
                                <td>
                                    <span class="nx-badge {{ $methodClass }}">{{ $methodLabel }}</span>
                                </td>

                                {{-- STATUS --}}
                                <td>
                                    <span class="nx-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>

                                {{-- ACTIONS --}}
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="{{ route('admission-payments.show', $payment) }}" class="action-btn" title="View" aria-label="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <a href="{{ route('admission-payments.edit', $payment) }}" class="action-btn" title="Edit" aria-label="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        @if ($canDelete)
                                            <form action="{{ route('admission-payments.destroy', $payment) }}" method="POST"
                                                onsubmit="return confirm('Delete this {{ $statusLabel }} payment for {{ e(optional($payment->student)->initial_name ?? 'this student') }}?')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="action-btn action-btn-danger" title="Delete" aria-label="Delete">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="action-btn locked-btn"
                                                title="Paid payments cannot be deleted. Change the status to cancelled or refunded first."
                                                aria-label="Locked">
                                                <i class="bi bi-lock-fill"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="empty-table">
                                    <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                                    <strong class="d-block">No Payments Found</strong>
                                    <span class="cell-sub">
                                        @if ($hasFilters)
                                            No payments match your search.
                                        @else
                                            Add the first admission payment to get started.
                                        @endif
                                    </span>

                                    @if ($hasFilters)
                                        <div class="mt-3">
                                            <a href="{{ route('admission-payments.index') }}" class="nx-btn nx-btn-light">
                                                <i class="bi bi-x-lg"></i> Clear filters
                                            </a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($payments->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Admission Payments — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .amount-text {
        font-weight: 700;
        color: var(--nx-text);
        white-space: nowrap;
    }

    .dashboard-page .amount-void {
        color: var(--nx-muted);
        text-decoration: line-through;
    }

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

    @media (max-width: 992px) {
        .dashboard-page .premium-table { min-width: 1000px; }
    }
</style>
@endpush