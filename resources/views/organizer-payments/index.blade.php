@extends('layouts.app')

@section('title', 'Organizer Payments')
@section('page-title', 'Organizer Payments')

@section('content')

    @php
        $selectedYear  = request('year', $year);
        $selectedMonth = request('month', $month);

        $totalIncome     = $summary['total_income'] ?? 0;
        $advanceAmount   = $summary['advance_amount'] ?? 0;
        $deductionAmount = $summary['deduction_amount'] ?? 0;
        $otherAmount     = $summary['other_amount'] ?? 0;
        $salaryPaid      = $summary['salary_paid'] ?? 0;
        $netTotal        = $summary['net_total'] ?? 0;
    @endphp

    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Organizer Payments</h1>
                    <p class="hero-subtitle">
                        Manage organizer income, advances, deductions and salary payments.
                    </p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ count($organizerRows) }}</span>
                            <span class="mini-stat-label">Organizers</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($netTotal, 2) }}</span>
                            <span class="mini-stat-label">Net Total</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $selectedMonth }} / {{ $selectedYear }}</span>
                            <span class="mini-stat-label">Period</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <button type="button" onclick="window.print()" class="nx-btn nx-btn-light">
                            <i class="bi bi-printer"></i> Print
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
                    <p class="nx-card-subtitle">Filter by year and month</p>
                </div>
            </div>

            <div class="p-3">
                <form method="GET" action="{{ route('admin.organizer-payments.index') }}" class="row g-3 align-items-end">

                    <div class="col-lg-3 col-md-4">
                        <label class="nx-label">Year</label>
                        <input type="number" name="year" value="{{ $selectedYear }}"
                            class="form-control nx-input"
                            placeholder="Year" min="2000" max="2100">
                    </div>

                    <div class="col-lg-3 col-md-4">
                        <label class="nx-label">Month</label>
                        <input type="number" name="month" value="{{ $selectedMonth }}"
                            class="form-control nx-input"
                            placeholder="Month" min="1" max="12">
                    </div>

                    <div class="col-lg-3 col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="nx-btn nx-btn-primary flex-grow-1">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('admin.organizer-payments.index') }}" class="nx-btn nx-btn-light">
                                Reset
                            </a>
                        </div>
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
                    <h2 class="stat-number">{{ number_format($totalIncome, 2) }}</h2>
                    <span class="stat-change up">Total <span class="stat-change-note">earned</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-arrow-down-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Advance</span>
                    <h2 class="stat-number">{{ number_format($advanceAmount, 2) }}</h2>
                    <span class="stat-change down">Paid <span class="stat-change-note">in advance</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-dash-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Deduction</span>
                    <h2 class="stat-number">{{ number_format($deductionAmount, 2) }}</h2>
                    <span class="stat-change down">Applied <span class="stat-change-note">deductions</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-three-dots"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Other</span>
                    <h2 class="stat-number">{{ number_format($otherAmount, 2) }}</h2>
                    <span class="stat-change neutral">Miscellaneous <span class="stat-change-note">amounts</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-wallet2"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Salary Paid</span>
                    <h2 class="stat-number">{{ number_format($salaryPaid, 2) }}</h2>
                    <span class="stat-change up">Paid <span class="stat-change-note">salaries</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-check2-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Net Total</span>
                    <h2 class="stat-number">{{ number_format($netTotal, 2) }}</h2>
                    <span class="stat-change up">Final <span class="stat-change-note">net balance</span></span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- MAIN TABLE --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-person-badge text-primary"></i> Monthly Organizer Payment Summary</h5>
                    <p class="nx-card-subtitle">
                        Showing records for {{ $selectedMonth }} / {{ $selectedYear }}
                    </p>
                </div>
                <span class="nx-badge nx-badge-blue">
                    <i class="bi bi-list-ul"></i> {{ count($organizerRows) }} Organizers
                </span>
            </div>

            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th class="text-end">Total Income</th>
                            <th class="text-end">Advance</th>
                            <th class="text-end">Deduction</th>
                            <th class="text-end">Other</th>
                            <th class="text-end">Salary Paid</th>
                            <th class="text-end">Net Total</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($organizerRows as $row)
                            <tr>
                                <td class="cell-title">{{ $row['organizer_id'] }}</td>

                                <td>
                                    <span class="id-badge">{{ $row['code'] }}</span>
                                </td>

                                <td>
                                    <div class="cell-title">{{ $row['name'] }}</div>
                                    @if (!empty($row['mobile']))
                                        <small class="cell-sub">{{ $row['mobile'] }}</small>
                                    @endif
                                </td>

                                <td class="text-end cell-title">
                                    {{ number_format($row['total_income'], 2) }}
                                </td>

                                <td class="text-end cell-title" style="color: var(--nx-red);">
                                    {{ number_format($row['advance_amount'], 2) }}
                                </td>

                                <td class="text-end cell-title" style="color: var(--nx-red);">
                                    {{ number_format($row['deduction_amount'], 2) }}
                                </td>

                                <td class="text-end cell-title" style="color: var(--nx-amber);">
                                    {{ number_format($row['other_amount'], 2) }}
                                </td>

                                <td class="text-end cell-title" style="color: var(--nx-blue);">
                                    {{ number_format($row['salary_paid'], 2) }}
                                </td>

                                <td class="text-end cell-title" style="color: var(--nx-green);">
                                    {{ number_format($row['net_total'], 2) }}
                                </td>

                                <td class="text-center">
                                    <div class="action-buttons justify-content-center">
                                        @if (!$row['is_salary_paid'])
                                            <button type="button" class="action-btn action-btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#adjustmentModal{{ $row['organizer_id'] }}"
                                                title="Add Payment" aria-label="Add Payment">
                                                <i class="bi bi-plus-circle-fill"></i>
                                            </button>

                                            <a href="{{ route('admin.organizer-payments.pay', [
                                                'organizer' => $row['organizer_id'],
                                                'year' => $selectedYear,
                                                'month' => $selectedMonth,
                                            ]) }}" class="action-btn action-btn-success" title="Pay Salary" aria-label="Pay Salary">
                                                <i class="bi bi-cash-coin"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('admin.organizer-payments.salary-slip', [
                                                'organizer' => $row['organizer_id'],
                                                'year' => $selectedYear,
                                                'month' => $selectedMonth,
                                            ]) }}" target="_blank" class="action-btn" title="Print Salary Slip" aria-label="Print Salary Slip">
                                                <i class="bi bi-printer-fill"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="empty-table">
                                    <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                                    <strong class="d-block">No organizer payment records found</strong>
                                    <span class="cell-sub">Try changing the year or month filters</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ADJUSTMENT MODALS --}}
        {{-- ===================================================== --}}
        @foreach ($organizerRows as $row)
            @if (!$row['is_salary_paid'])
                <div class="modal fade" id="adjustmentModal{{ $row['organizer_id'] }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST"
                                action="{{ route('admin.organizer-payments.adjustment-store', $row['organizer_id']) }}">
                                @csrf

                                <div class="modal-header">
                                    <div>
                                        <h5 class="modal-title">Add Organizer Payment</h5>
                                        <small class="cell-sub">{{ $row['name'] }} · {{ $row['code'] }}</small>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="nx-label">Payment Type</label>
                                        <select name="payment_type" class="form-select nx-input" required>
                                            <option value="advance">Advance</option>
                                            <option value="deduction">Deduction</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="nx-label">Amount</label>
                                        <input type="number" name="amount" step="0.01" min="0.01"
                                            class="form-control nx-input" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="nx-label">Payment Date</label>
                                        <input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}"
                                            class="form-control nx-input" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="nx-label">Reason</label>
                                        <input type="text" name="reason" class="form-control nx-input" maxlength="150">
                                    </div>

                                    <div class="mb-0">
                                        <label class="nx-label">Note</label>
                                        <textarea name="note" class="form-control nx-input" rows="3"></textarea>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="nx-btn nx-btn-light" data-bs-dismiss="modal">
                                        Cancel
                                    </button>
                                    <button type="submit" class="nx-btn nx-btn-primary">
                                        <i class="bi bi-check-lg"></i> Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

    </div>

@endsection


@push('styles')
<style>
    /* ============================================================
       Organizer Payments — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    /* Modals are teleported to <body> — scope with ID pattern */
    [id^="adjustmentModal"] .modal-content {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(15, 23, 42, .15);
        overflow: hidden;
    }

    [id^="adjustmentModal"] .modal-header {
        border-bottom: 1px solid #d1d8e3;
        padding: 1rem 1.25rem;
        background: #fafbfd;
    }

    [id^="adjustmentModal"] .modal-title {
        font-weight: 700;
        font-size: .95rem;
        color: #0f172a;
    }

    [id^="adjustmentModal"] .modal-body {
        padding: 1.25rem;
        background: #f4f6fa;
    }

    [id^="adjustmentModal"] .modal-footer {
        border-top: 1px solid #d1d8e3;
        padding: .85rem 1.25rem;
        background: #fafbfd;
    }

    [id^="adjustmentModal"] .nx-label {
        display: block;
        font-size: .75rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: .35rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    [id^="adjustmentModal"] .nx-input {
        min-height: 42px;
        border-radius: 8px !important;
        border: 1px solid #d1d8e3;
        font-size: .88rem;
        color: #0f172a;
        background: #fff;
    }

    [id^="adjustmentModal"] .nx-input:focus {
        border-color: #1d4ed8;
        box-shadow: 0 0 0 3px #dbe6ff;
    }

    [id^="adjustmentModal"] .cell-sub {
        font-size: .74rem;
        color: #475569;
    }

    [id^="adjustmentModal"] .nx-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        padding: .5rem .85rem;
        font-size: .82rem;
        font-weight: 600;
        border-radius: 8px;
        border: 1px solid #d1d8e3;
        background: #fff;
        color: #0f172a;
        text-decoration: none;
        min-height: 38px;
        transition: all .15s ease;
        cursor: pointer;
    }

    [id^="adjustmentModal"] .nx-btn-primary {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
    }

    [id^="adjustmentModal"] .nx-btn-light {
        background: #fff;
        border-color: #d1d8e3;
        color: #475569;
    }

    /* Print */
    @media print {
        .sidebar,
        .top-navbar,
        .header-buttons,
        .action-buttons,
        .nx-search,
        .modal { display: none !important; }

        body { background: #fff !important; }
    }
</style>
@endpush