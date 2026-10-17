@extends('layouts.app')

@section('title', 'Admission Payment Details')
@section('page-title', 'Admission Payment Details')

@php
    $payment = $admissionPayment;
    $student = $payment->student;

    $statuses = [
        'paid'      => ['nx-badge-green',  'bi-check-circle-fill',     'Paid'],
        'pending'   => ['nx-badge-amber',  'bi-clock-fill',            'Pending'],
        'cancelled' => ['nx-badge-red',    'bi-x-circle-fill',         'Cancelled'],
        'refunded'  => ['nx-badge-blue',   'bi-arrow-counterclockwise', 'Refunded'],
    ];

    [$statusClass, $statusIcon, $statusLabel] = $statuses[$payment->status] ?? ['nx-badge-gray', 'bi-question-circle', 'Unknown'];

    $methods = [
        'cash'          => ['nx-badge-gray',  'bi-cash-stack', 'Cash'],
        'card'          => ['nx-badge-blue',  'bi-credit-card', 'Card'],
        'bank_transfer' => ['nx-badge-blue',  'bi-bank',       'Bank Transfer'],
        'online'        => ['nx-badge-green', 'bi-globe',      'Online'],
        'other'         => ['nx-badge-gray',  'bi-three-dots', 'Other'],
    ];

    [$methodClass, $methodIcon, $methodLabel] = $methods[$payment->payment_method] ?? $methods['cash'];

    $isPaid = $payment->status === 'paid';
    $isVoid = in_array($payment->status, ['cancelled', 'refunded'], true);
@endphp

@section('content')
    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- ALERTS --}}
        {{-- ===================================================== --}}
        @if (session('success'))
            <div class="nx-alert nx-alert-green mb-4">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="nx-alert nx-alert-red mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">
                        <i class="bi bi-receipt"></i>
                        {{ $payment->receipt_number ?: 'No receipt yet' }}
                    </span>
                    <h1 class="hero-title">
                        {{ optional($payment->admission)->name ?? 'Admission Payment' }}
                    </h1>
                    <p class="hero-subtitle">
                        {{ optional($student)->initial_name ?? '-' }}
                        @if ($student)
                            · {{ $student->custom_id }}
                        @endif
                    </p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value hero-amount {{ $isVoid ? 'hero-amount-void' : '' }}">
                                Rs. {{ number_format((float) $payment->amount, 2) }}
                            </span>
                            <span class="mini-stat-label">Amount</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="nx-badge {{ $statusClass }}">
                                <i class="bi {{ $statusIcon }}"></i> {{ $statusLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        @if ($isPaid)
                            <button type="button" onclick="window.print()" class="nx-btn nx-btn-light">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        @endif
                        <a href="{{ route('admission-payments.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- DETAILS CARD --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-info-circle-fill text-primary"></i> Transaction Details</h5>
                    <p class="nx-card-subtitle">Payment, student and transaction information</p>
                </div>
            </div>

            <div class="p-3 p-md-4">

                {{-- INFO GRID --}}
                <div class="info-grid">

                    {{-- RECEIPT --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-receipt text-primary"></i> Receipt Number</div>
                        <div class="info-value">
                            @if ($payment->receipt_number)
                                <span class="id-badge">{{ $payment->receipt_number }}</span>
                            @else
                                <span class="cell-sub">Issued when the payment is marked as paid</span>
                            @endif
                        </div>
                    </div>

                    {{-- AMOUNT --}}
                    <div class="info-card">
                        <div class="info-label">
                            <i class="bi bi-cash-stack text-primary"></i>
                            {{ $isPaid ? 'Amount Paid' : 'Amount' }}
                        </div>
                        <div class="info-value amount {{ $isVoid ? 'amount-void' : '' }}">
                            Rs. {{ number_format((float) $payment->amount, 2) }}
                        </div>
                        @if (optional($payment->admission)->amount !== null && (float) $payment->admission->amount !== (float) $payment->amount)
                            <small class="cell-sub">
                                Standard fee: Rs. {{ number_format((float) $payment->admission->amount, 2) }}
                            </small>
                        @endif
                    </div>

                    {{-- STUDENT --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-person-badge-fill text-primary"></i> Student</div>
                        <div class="info-value">
                            @if ($student)
                                <a href="{{ route('students.show', $student->id) }}" class="info-link">
                                    {{ $student->initial_name ?? $student->full_name ?? '-' }}
                                </a>
                                <br>
                                <small class="cell-sub">ID: {{ $student->custom_id ?? '-' }}</small>
                                @if ($student->guardian_mobile)
                                    <small class="cell-sub"> · {{ $student->guardian_mobile }}</small>
                                @endif
                            @else
                                <span class="cell-sub">Student record not found</span>
                            @endif
                        </div>
                    </div>

                    {{-- ADMISSION --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-journal-bookmark-fill text-primary"></i> Admission Type</div>
                        <div class="info-value">{{ optional($payment->admission)->name ?? '-' }}</div>
                    </div>

                    {{-- METHOD --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-credit-card text-primary"></i> Payment Method</div>
                        <div class="info-value">
                            <span class="nx-badge {{ $methodClass }}">
                                <i class="bi {{ $methodIcon }}"></i> {{ $methodLabel }}
                            </span>
                        </div>
                    </div>

                    {{-- STATUS --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-flag-fill text-primary"></i> Payment Status</div>
                        <div class="info-value">
                            <span class="nx-badge {{ $statusClass }}">
                                <i class="bi {{ $statusIcon }}"></i> {{ $statusLabel }}
                            </span>
                        </div>
                    </div>

                    {{-- COLLECTED BY --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-person-workspace text-primary"></i> Collected By</div>
                        <div class="info-value">
                            {{ optional($payment->collectedBy)->name ?? 'System' }}
                            @if ($payment->branch)
                                <br><small class="cell-sub"><i class="bi bi-building"></i> {{ $payment->branch->name }}</small>
                            @endif
                        </div>
                    </div>

                    {{-- PAID AT --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-calendar-check-fill text-primary"></i> Payment Date &amp; Time</div>
                        <div class="info-value">
                            @if ($payment->paid_at)
                                {{ $payment->paid_at->format('l, d F Y') }}
                                <br>
                                <small class="cell-sub">{{ $payment->paid_at->format('h:i A') }}</small>
                            @else
                                <span class="cell-sub">Not paid yet</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- NOTE --}}
                @if ($payment->note)
                    <div class="note-section">
                        <div class="note-label"><i class="bi bi-chat-text-fill"></i> Additional Note</div>
                        <div class="note-text">{{ $payment->note }}</div>
                    </div>
                @endif

                {{-- TIMELINE --}}
                <div class="timeline">
                    <div class="timeline-title"><i class="bi bi-clock-history"></i> Transaction Timeline</div>

                    <div class="timeline-item">
                        <div class="timeline-icon"><i class="bi bi-plus-circle-fill"></i></div>
                        <div class="timeline-content">
                            <div class="timeline-event">Payment record created</div>
                            <div class="timeline-date">{{ optional($payment->created_at)->format('l, d F Y h:i A') }}</div>
                        </div>
                    </div>

                    @if ($payment->paid_at)
                        <div class="timeline-item">
                            <div class="timeline-icon timeline-icon-green"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-event">Payment received</div>
                                <div class="timeline-date">{{ $payment->paid_at->format('l, d F Y h:i A') }}</div>
                            </div>
                        </div>
                    @endif

                    @if ($payment->updated_at && $payment->created_at && $payment->updated_at->gt($payment->created_at))
                        <div class="timeline-item">
                            <div class="timeline-icon"><i class="bi bi-pencil-square"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-event">Last updated{{ $isVoid ? ' (' . strtolower($statusLabel) . ')' : '' }}</div>
                                <div class="timeline-date">{{ $payment->updated_at->format('l, d F Y h:i A') }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ACTIONS --}}
                <div class="action-buttons">
                    <a href="{{ route('admission-payments.edit', $payment) }}" class="nx-btn nx-btn-primary">
                        <i class="bi bi-pencil-square"></i> Edit Payment
                    </a>

                    @unless ($isPaid)
                        <form action="{{ route('admission-payments.destroy', $payment) }}" method="POST"
                            onsubmit="return confirm('Delete this {{ strtolower($statusLabel) }} payment?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nx-btn nx-btn-danger">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    @endunless

                    <a href="{{ route('admission-payments.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="text-center">
            <small class="cell-sub">
                <i class="bi bi-info-circle"></i>
                Transaction ID: #{{ $payment->id }}
                @if ($payment->receipt_number)
                    | Receipt: {{ $payment->receipt_number }}
                @endif
            </small>
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Admission Payment Details — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .hero-amount {
        font-weight: 800;
        letter-spacing: -.3px;
    }
    .dashboard-page .hero-amount-void {
        text-decoration: line-through;
        opacity: .7;
    }

    /* ---------- INFO GRID ---------- */
    .dashboard-page .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .dashboard-page .info-card {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        padding: 1rem 1.1rem;
        transition: border-color .15s ease, background .15s ease;
    }
    .dashboard-page .info-card:hover {
        background: #fff;
        border-color: var(--nx-blue);
    }

    .dashboard-page .info-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
        color: var(--nx-muted);
        margin-bottom: .5rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .dashboard-page .info-label i { font-size: .85rem; }

    .dashboard-page .info-value {
        font-size: .92rem;
        font-weight: 700;
        color: var(--nx-text);
        word-break: break-word;
    }

    .dashboard-page .info-value.amount {
        font-size: 1.2rem;
        color: var(--nx-green);
    }
    .dashboard-page .info-value.amount-void {
        color: var(--nx-muted);
        text-decoration: line-through;
    }

    .dashboard-page .info-link {
        color: var(--nx-text);
        text-decoration: none;
        font-weight: 700;
    }
    .dashboard-page .info-link:hover {
        color: var(--nx-blue);
        text-decoration: underline;
    }

    /* ---------- NOTE ---------- */
    .dashboard-page .note-section {
        margin-top: 1.25rem;
        background: var(--nx-amber-soft);
        border: 1px solid rgba(180, 83, 9, .2);
        border-left: 4px solid var(--nx-amber);
        border-radius: 8px;
        padding: 1rem 1.25rem;
    }
    .dashboard-page .note-label {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
        color: var(--nx-amber);
        margin-bottom: .5rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .dashboard-page .note-text {
        font-size: .88rem;
        color: var(--nx-text);
        line-height: 1.5;
        white-space: pre-line;
    }

    /* ---------- TIMELINE ---------- */
    .dashboard-page .timeline {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--nx-border);
    }
    .dashboard-page .timeline-title {
        font-size: .78rem;
        font-weight: 700;
        color: var(--nx-muted);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: .4rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .dashboard-page .timeline-item {
        display: flex;
        gap: 1rem;
        padding: .75rem 0;
        border-bottom: 1px solid var(--nx-border);
    }
    .dashboard-page .timeline-item:last-child { border-bottom: 0; }

    .dashboard-page .timeline-icon {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
    }
    .dashboard-page .timeline-icon-green {
        background: var(--nx-green-soft);
        color: var(--nx-green);
    }

    .dashboard-page .timeline-content { flex: 1; }
    .dashboard-page .timeline-event {
        font-weight: 700;
        color: var(--nx-text);
        font-size: .85rem;
    }
    .dashboard-page .timeline-date {
        font-size: .74rem;
        color: var(--nx-muted);
    }

    /* ---------- ACTION BUTTONS ---------- */
    .dashboard-page .action-buttons {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--nx-border);
        display: flex;
        gap: .5rem;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .dashboard-page .action-buttons form { margin: 0; }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 768px) {
        .dashboard-page .info-grid { grid-template-columns: 1fr; }
        .dashboard-page .action-buttons { justify-content: stretch; }
        .dashboard-page .action-buttons > *,
        .dashboard-page .action-buttons .nx-btn { flex: 1; justify-content: center; }
    }

    /* ---------- PRINT ---------- */
    @media print {
        .dashboard-page .hero-actions,
        .dashboard-page .action-buttons,
        .nx-alert,
        .sidebar,
        .top-navbar { display: none !important; }

        .dashboard-page .hero-section {
            background: none !important;
            color: #000 !important;
            box-shadow: none !important;
            border: 1px solid #ddd;
        }
        .dashboard-page .hero-section::before,
        .dashboard-page .hero-section::after { display: none; }

        .dashboard-page .greeting-badge,
        .dashboard-page .hero-subtitle { color: #000 !important; }

        .dashboard-page .nx-card { box-shadow: none !important; border: 1px solid #ddd; }
        .dashboard-page .info-card { break-inside: avoid; page-break-inside: avoid; }
    }
</style>
@endpush