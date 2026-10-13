@extends('layouts.app')

@section('title', 'Admission Payments')
@section('page-title', 'Admission Payments')

@php
    $summary = $summary ?? [];

    $statusBadges = [
        'pending' => ['bg-warning text-dark', 'Pending'],
        'paid' => ['bg-success', 'Paid'],
        'cancelled' => ['bg-danger', 'Cancelled'],
        'refunded' => ['bg-info text-dark', 'Refunded'],
    ];

    $methodBadges = [
        'cash' => ['bg-light text-dark', 'Cash'],
        'card' => ['bg-primary-subtle text-primary', 'Card'],
        'bank_transfer' => ['bg-info-subtle text-info', 'Bank Transfer'],
        'online' => ['bg-success-subtle text-success', 'Online'],
        'other' => ['bg-secondary-subtle text-secondary', 'Other'],
    ];

    $hasFilters = request()->filled('search') || request()->filled('status');
@endphp

@section('content')
    <div class="admission-payments-page">

        {{-- STATS (all payments in your branches, not only this page) --}}
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <h3>{{ number_format($summary['total'] ?? 0) }}</h3>
                        <p>Total Payments</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <h3>{{ number_format($summary['paid'] ?? 0) }}</h3>
                        <p>Paid</p>
                        @if (($summary['cancelled'] ?? 0) + ($summary['refunded'] ?? 0) > 0)
                            <small class="text-muted">
                                {{ $summary['cancelled'] ?? 0 }} cancelled · {{ $summary['refunded'] ?? 0 }} refunded
                            </small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div>
                        <h3>{{ number_format($summary['pending'] ?? 0) }}</h3>
                        <p>Pending</p>
                        @if (($summary['pending_amount'] ?? 0) > 0)
                            <small class="text-muted">Rs. {{ number_format($summary['pending_amount'], 2) }} due</small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="min-w-0">
                        <h3>Rs. {{ number_format($summary['paid_amount'] ?? 0, 2) }}</h3>
                        <p>Total Collected</p>
                        <small class="text-muted">Today: Rs. {{ number_format($summary['today_paid_amount'] ?? 0, 2) }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- MAIN CARD --}}
        <div class="main-card">

            {{-- HEADER --}}
            <div class="main-card-header">
                <div>
                    <h4>Admission Payments</h4>
                    <p>Manage student admission payment records, status and payment details</p>
                </div>

                <div class="header-buttons">
                    <a href="{{ route('admission-payments.create') }}" class="btn btn-primary custom-btn">
                        <i class="bi bi-plus-lg"></i>
                        Add Payment
                    </a>
                </div>
            </div>

            {{-- ALERTS --}}
            @if (session('success'))
                <div class="alert alert-success custom-alert alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger custom-alert custom-alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- SEARCH / FILTER --}}
            <div class="search-card">
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
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 d-flex gap-2">
                            <button class="btn btn-dark flex-grow-1 custom-btn justify-content-center" type="submit">
                                <i class="bi bi-funnel-fill"></i>
                                Search
                            </button>

                            @if ($hasFilters)
                                <a href="{{ route('admission-payments.index') }}" class="btn btn-outline-secondary custom-btn"
                                    title="Clear filters">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- RESULT COUNT --}}
            @if ($payments->total() > 0)
                <p class="result-count">
                    Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ number_format($payments->total()) }}
                    {{ $hasFilters ? 'matching' : '' }} payments
                </p>
            @endif

            {{-- TABLE --}}
            <div class="table-responsive">
                <table class="table custom-table align-middle">
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
                                [$statusClass, $statusLabel] = $statusBadges[$payment->status] ?? ['bg-secondary', 'Unknown'];
                                [$methodClass, $methodLabel] = $methodBadges[$payment->payment_method] ?? ['bg-light text-dark', 'Unknown'];
                                $canDelete = $payment->status !== 'paid';
                            @endphp

                            <tr>
                                <td>{{ $payments->firstItem() + $loop->index }}</td>

                                <td>
                                    <div class="fw-semibold">{{ $payment->receipt_number ?: '-' }}</div>
                                    <small class="text-muted">
                                        {{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : 'Not paid yet' }}
                                    </small>
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ optional($payment->student)->initial_name ?? '-' }}</div>
                                    <small class="text-muted">{{ optional($payment->student)->custom_id ?? '-' }}</small>
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ optional($payment->admission)->name ?? '-' }}</div>
                                    @if ($payment->collectedBy)
                                        <small class="text-muted">by {{ $payment->collectedBy->name }}</small>
                                    @endif
                                </td>

                                <td>
                                    <div class="amount-text {{ in_array($payment->status, ['cancelled', 'refunded'], true) ? 'amount-void' : '' }}">
                                        Rs. {{ number_format((float) $payment->amount, 2) }}
                                    </div>
                                </td>

                                <td>
                                    <span class="badge custom-badge border {{ $methodClass }}">{{ $methodLabel }}</span>
                                </td>

                                <td>
                                    <span class="badge custom-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>

                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="{{ route('admission-payments.show', $payment) }}" class="action-btn view-btn"
                                            title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <a href="{{ route('admission-payments.edit', $payment) }}" class="action-btn edit-btn"
                                            title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        @if ($canDelete)
                                            <form action="{{ route('admission-payments.destroy', $payment) }}" method="POST"
                                                onsubmit="return confirm('Delete this {{ $statusLabel }} payment for {{ e(optional($payment->student)->initial_name ?? 'this student') }}?')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="action-btn delete-btn" title="Delete">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="action-btn locked-btn"
                                                title="Paid payments cannot be deleted. Change the status to cancelled or refunded first.">
                                                <i class="bi bi-lock-fill"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="empty-state">
                                        <i class="bi bi-receipt"></i>
                                        <h5>No Payments Found</h5>
                                        <p>
                                            @if ($hasFilters)
                                                No payments match your search.
                                                <a href="{{ route('admission-payments.index') }}">Clear filters</a>
                                            @else
                                                Add the first admission payment to get started.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($payments->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $payments->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection

@push('styles')
    <style>
        .admission-payments-page {
            animation: fadeIn 0.4s ease;
        }

        .min-w-0 {
            min-width: 0;
        }

        .stats-card {
            background: #fff;
            border-radius: 24px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            height: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f7;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: #fff;
            flex-shrink: 0;
        }

        .stats-icon.blue { background: linear-gradient(135deg, #2563eb, #3b82f6); }
        .stats-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .stats-icon.orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .stats-icon.red { background: linear-gradient(135deg, #ef4444, #f87171); }

        .stats-card h3 {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 800;
            line-height: 1.1;
            word-break: break-word;
        }

        .stats-card p {
            margin: 0;
            color: #64748b;
        }

        .stats-card small {
            font-size: .75rem;
        }

        .main-card {
            background: #fff;
            border-radius: 28px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid #eef2f7;
        }

        .main-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }

        .main-card-header h4 {
            margin: 0;
            font-weight: 800;
        }

        .main-card-header p {
            margin: .25rem 0 0 0;
            color: #64748b;
        }

        .header-buttons {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
        }

        .custom-btn {
            border-radius: 14px;
            padding: .72rem 1.2rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
        }

        .custom-alert {
            border-radius: 16px;
            border: 1px solid #bbf7d0;
        }

        .custom-alert-danger {
            border-color: #fecaca;
        }

        .search-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #eef2f7;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }

        .custom-input {
            min-height: 48px;
            border-radius: 14px !important;
            border: 1px solid #e2e8f0;
            box-shadow: none !important;
            padding-left: 42px;
        }

        select.custom-input {
            padding-left: 16px;
        }

        .result-count {
            font-size: .82rem;
            color: #64748b;
            margin-bottom: .75rem;
        }

        .custom-table thead th {
            border: none;
            background: #f8fafc;
            color: #475569;
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            padding: 1rem;
            white-space: nowrap;
        }

        .custom-table tbody tr {
            transition: .2s ease;
        }

        .custom-table tbody tr:hover {
            background: #f8fafc;
        }

        .custom-table tbody td {
            padding: 1rem;
            border-color: #f1f5f9;
            vertical-align: middle;
        }

        .amount-text {
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
        }

        .amount-void {
            color: #94a3b8;
            text-decoration: line-through;
        }

        .custom-badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: .2s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .view-btn {
            background: #eff6ff;
            color: #2563eb;
        }

        .edit-btn {
            background: #fef3c7;
            color: #d97706;
        }

        .delete-btn {
            background: #fef2f2;
            color: #ef4444;
        }

        .locked-btn {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .locked-btn:hover {
            transform: none;
        }

        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .empty-state h5 {
            font-weight: 800;
            margin-bottom: .35rem;
        }

        .empty-state p {
            margin: 0;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .main-card,
            .stats-card {
                border-radius: 22px;
            }

            .main-card-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-buttons {
                width: 100%;
            }

            .header-buttons a {
                flex: 1;
                justify-content: center;
            }

            .action-buttons {
                justify-content: flex-start;
            }
        }
    </style>
@endpush