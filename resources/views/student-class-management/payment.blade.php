@extends('layouts.app')

@section('title', 'Payment Details - ' . ($data['student']->full_name ?? 'Student'))
@section('page-title', 'Payment Details')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">{{ $data['student']->initial_name ?? $data['student']->full_name ?? 'Student' }}</h1>
                <p class="hero-subtitle">{{ $data['class']->class_name ?? 'N/A' }} • {{ $data['category']->category_name ?? 'N/A' }}</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('student-class-management.show', $data['student']->id) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back to Classes
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- SUMMARY --}}
    <div class="stats-grid mb-4">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-file-earmark-text"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Expected</span>
                <h2 class="stat-number">Rs. {{ number_format((float) ($data['expected_fee'] ?? 0), 2) }}</h2>
                <span class="stat-change neutral">From <span class="stat-change-note">enrollment month</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Paid</span>
                <h2 class="stat-number">Rs. {{ number_format((float) ($data['paid_amount'] ?? 0), 2) }}</h2>
                <span class="stat-change up">All <span class="stat-change-note">payments</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-wallet2"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Balance</span>
                <h2 class="stat-number">Rs. {{ number_format((float) ($data['balance'] ?? 0), 2) }}</h2>
                <span class="stat-change neutral">Remaining <span class="stat-change-note">amount</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            @php $status = $data['payment_status'] ?? 'unpaid'; @endphp
            <div class="stat-icon-wrapper {{ $status === 'paid' ? 'icon-green' : 'icon-red' }}">
                <i class="bi bi-{{ $status === 'paid' ? 'check-circle-fill' : 'x-circle-fill' }}"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Overall Status</span>
                <h2 class="stat-number" style="color: var(--nx-{{ $status === 'paid' ? 'green' : 'red' }});">
                    {{ $status === 'paid' ? 'Paid' : 'Unpaid' }}
                </h2>
                <span class="stat-change neutral">Current <span class="stat-change-note">status</span></span>
            </div>
        </div>
    </div>

    {{-- MONTH-WISE PAYMENT --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-calendar3 text-primary"></i> Month-wise Payment</h5>
                <p class="nx-card-subtitle">Payment status from the student's enrollment month</p>
            </div>
            <span class="nx-badge nx-badge-blue">{{ ($data['monthly_payments'] ?? collect())->count() }} Months</span>
        </div>

        @if (($data['monthly_payments'] ?? collect())->count() > 0)
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Payment Month</th>
                            <th>Expected Fee</th>
                            <th>Paid Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Payment Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['monthly_payments'] as $index => $month)
                            @php
                                $expected = (float) ($month['expected_fee'] ?? 0);
                                $paid = (float) ($month['paid_amount'] ?? 0);
                                $balance = max($expected - $paid, 0);
                                $monthStatus = $paid >= $expected ? 'paid' : 'unpaid';
                            @endphp
                            <tr>
                                <td class="row-number">{{ $index + 1 }}</td>
                                <td>
                                    <span class="nx-badge nx-badge-gray"><i class="bi bi-calendar3"></i> {{ $month['month_name'] ?? 'N/A' }}</span>
                                </td>
                                <td class="cell-title">Rs. {{ number_format($expected, 2) }}</td>
                                <td class="cell-title" style="color: var(--nx-green);">Rs. {{ number_format($paid, 2) }}</td>
                                <td>
                                    @if ($balance > 0)
                                        <span class="cell-title" style="color: var(--nx-red);">Rs. {{ number_format($balance, 2) }}</span>
                                    @else
                                        <span class="cell-title" style="color: var(--nx-green);">Rs. 0.00</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($monthStatus === 'paid')
                                        <span class="nx-badge nx-badge-green"><i class="bi bi-check-circle-fill"></i> Paid</span>
                                    @else
                                        <span class="nx-badge nx-badge-red"><i class="bi bi-x-circle-fill"></i> Unpaid</span>
                                    @endif
                                </td>
                                <td>
                                    @if (($month['payments'] ?? collect())->count() > 0)
                                        <button type="button" class="nx-btn nx-btn-light payment-count-btn"
                                            data-bs-toggle="collapse" data-bs-target="#monthPayments{{ $index }}"
                                            aria-expanded="false" aria-controls="monthPayments{{ $index }}">
                                            <i class="bi bi-receipt"></i>
                                            {{ $month['payments']->count() }}
                                            {{ $month['payments']->count() === 1 ? 'Payment' : 'Payments' }}
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    @else
                                        <span class="cell-sub">No Payment</span>
                                    @endif
                                </td>
                            </tr>

                            @if (($month['payments'] ?? collect())->count() > 0)
                                <tr class="payment-details-row">
                                    <td colspan="7" class="p-0">
                                        <div class="collapse" id="monthPayments{{ $index }}">
                                            <div class="monthly-payment-panel">
                                                <div class="panel-title"><i class="bi bi-receipt-cutoff"></i> Payment Records — {{ $month['month_name'] ?? 'N/A' }}</div>
                                                <div class="table-responsive">
                                                    <table class="table premium-table inner-table mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th><th>Receipt No</th><th>Paid At</th><th>Amount</th>
                                                                <th>Discount</th><th>Payment Method</th><th>Reference No</th><th>Mark Method</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($month['payments'] as $paymentIndex => $payment)
                                                                <tr>
                                                                    <td class="row-number">{{ $paymentIndex + 1 }}</td>
                                                                    <td>
                                                                        @if ($payment->receipt_number)
                                                                            <span class="nx-badge nx-badge-blue"><i class="bi bi-receipt"></i> {{ $payment->receipt_number }}</span>
                                                                        @else
                                                                            <span class="cell-sub">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="cell-sub">
                                                                        {{ $payment->paid_at ? $payment->paid_at->format('Y-m-d h:i A') : '—' }}
                                                                    </td>
                                                                    <td class="cell-title" style="color: var(--nx-green);">Rs. {{ number_format((float) ($payment->amount ?? 0), 2) }}</td>
                                                                    <td class="cell-sub">Rs. {{ number_format((float) ($payment->discount_amount ?? 0), 2) }}</td>
                                                                    <td class="cell-sub">{{ $payment->payment_method ?? 'N/A' }}</td>
                                                                    <td class="cell-sub">{{ $payment->reference_number ?? '—' }}</td>
                                                                    <td class="cell-sub">{{ $payment->mark_method ?? '—' }}</td>
                                                                </tr>
                                                                @if ($payment->note)
                                                                    <tr class="payment-note-row">
                                                                        <td colspan="8">
                                                                            <div class="payment-note">
                                                                                <i class="bi bi-chat-left-text"></i>
                                                                                <strong>Note:</strong> {{ $payment->note }}
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-table">
                <i class="bi bi-wallet2 fs-2 d-block mb-2"></i>
                <strong class="d-block">No Payment Information</strong>
                <span class="cell-sub">No payment information was found from the enrollment month.</span>
            </div>
        @endif
    </div>

    {{-- ALL PAYMENT TRANSACTIONS --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-receipt text-primary"></i> Payment Transactions</h5>
                <p class="nx-card-subtitle">All payment transactions for this enrollment</p>
            </div>
            <span class="nx-badge nx-badge-blue">{{ ($data['payments'] ?? collect())->count() }} Records</span>
        </div>
        @if (($data['payments'] ?? collect())->count() > 0)
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th><th>Payment Month</th><th>Receipt No</th><th>Paid At</th>
                            <th>Amount</th><th>Payment Method</th><th>Reference No</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['payments'] as $index => $payment)
                            <tr>
                                <td class="row-number">{{ $index + 1 }}</td>
                                <td>
                                    <span class="nx-badge nx-badge-gray">
                                        <i class="bi bi-calendar3"></i>
                                        {{ $payment->payment_month?->format('F Y') ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($payment->receipt_number)
                                        <span class="nx-badge nx-badge-blue"><i class="bi bi-receipt"></i> {{ $payment->receipt_number }}</span>
                                    @else
                                        <span class="cell-sub">—</span>
                                    @endif
                                </td>
                                <td class="cell-sub">{{ $payment->paid_at?->format('Y-m-d h:i A') ?? 'N/A' }}</td>
                                <td class="cell-title" style="color: var(--nx-green);">Rs. {{ number_format((float) ($payment->amount ?? 0), 2) }}</td>
                                <td class="cell-sub">{{ $payment->payment_method ?? 'N/A' }}</td>
                                <td class="cell-sub">{{ $payment->reference_number ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-table">
                <i class="bi bi-wallet2 fs-2 d-block mb-2"></i>
                <strong class="d-block">No Payments Found</strong>
                <span class="cell-sub">No payment records were found for this enrollment.</span>
            </div>
        @endif
    </div>

    {{-- ENROLLMENT INFORMATION --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-info-circle text-primary"></i> Enrollment Information</h5>
                <p class="nx-card-subtitle">Class and enrollment details</p>
            </div>
        </div>
        <div class="p-3">
            <div class="detail-grid">
                <div class="detail-item"><span class="detail-label">Student ID</span><strong class="detail-value">{{ $data['student']->custom_id ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Class</span><strong class="detail-value">{{ $data['class']->class_name ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Category</span><strong class="detail-value">{{ $data['category']->category_name ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Grade</span><strong class="detail-value">{{ $data['grade']->grade_name ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Teacher</span><strong class="detail-value">{{ $data['teacher']->initials ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Enrolled Date</span><strong class="detail-value">{{ $data['enrolled_at']?->format('Y-m-d') ?? 'N/A' }}</strong></div>
                @if (!empty($data['left_at']))
                    <div class="detail-item"><span class="detail-label">Left Date</span><strong class="detail-value">{{ $data['left_at']?->format('Y-m-d') }}</strong></div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Payment Details — page-specific styles only.
       ============================================================ */

    .dashboard-page .payment-count-btn {
        font-size: .75rem;
        padding: .35rem .65rem;
        min-height: 30px;
    }

    .dashboard-page .payment-details-row > td {
        background: #fafbfd;
        border-top: 0;
    }
    .dashboard-page .monthly-payment-panel { padding: 1rem 1.25rem 1.25rem; }
    .dashboard-page .panel-title {
        font-weight: 600;
        color: var(--nx-text);
        margin-bottom: .75rem;
        font-size: .85rem;
    }
    .dashboard-page .inner-table {
        background: #fff;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        overflow: hidden;
    }
    .dashboard-page .payment-note-row td {
        background: #fff !important;
        padding-top: 0 !important;
    }
    .dashboard-page .payment-note {
        background: var(--nx-amber-soft);
        border-radius: 8px;
        padding: .6rem .8rem;
        color: #7a5010;
        font-size: .78rem;
    }

    .dashboard-page .detail-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    .dashboard-page .detail-item {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        padding: .85rem 1rem;
    }
    .dashboard-page .detail-label {
        display: block;
        color: var(--nx-muted);
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .dashboard-page .detail-value {
        display: block;
        margin-top: .25rem;
        font-size: .88rem;
        font-weight: 600;
        color: var(--nx-text);
    }

    @media (max-width: 1100px) {
        .dashboard-page .detail-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .dashboard-page .detail-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush