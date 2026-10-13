@extends('layouts.app')

@section('title', 'Admission Payment Details')
@section('page-title', 'Admission Payment Details')

@php
    $payment = $admissionPayment;
    $student = $payment->student;

    $statuses = [
        'paid' => ['badge-paid', 'bi-check-circle-fill', 'Paid'],
        'pending' => ['badge-pending', 'bi-clock-fill', 'Pending'],
        'cancelled' => ['badge-cancelled', 'bi-x-circle-fill', 'Cancelled'],
        'refunded' => ['badge-refunded', 'bi-arrow-counterclockwise', 'Refunded'],
    ];

    [$statusClass, $statusIcon, $statusLabel] = $statuses[$payment->status] ?? ['badge-pending', 'bi-question-circle', 'Unknown'];

    $methods = [
        'cash' => ['method-cash', 'bi-cash-stack', 'Cash'],
        'card' => ['method-card', 'bi-credit-card', 'Card'],
        'bank_transfer' => ['method-bank', 'bi-bank', 'Bank Transfer'],
        'online' => ['method-online', 'bi-globe', 'Online'],
        'other' => ['method-cash', 'bi-three-dots', 'Other'],
    ];

    [$methodClass, $methodIcon, $methodLabel] = $methods[$payment->payment_method] ?? $methods['cash'];

    $isPaid = $payment->status === 'paid';
    $isVoid = in_array($payment->status, ['cancelled', 'refunded'], true);
@endphp

@section('content')
    <div class="payment-details-page">

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="alert alert-success custom-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger custom-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- HERO --}}
        <div class="hero-card">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 position-relative">
                <div>
                    <div class="hero-badge mb-3">
                        <i class="bi bi-receipt"></i>
                        {{ $payment->receipt_number ?: 'No receipt yet' }}
                    </div>
                    <h2 class="fw-bold mb-1">{{ optional($payment->admission)->name ?? 'Admission Payment' }}</h2>
                    <p class="mb-0 text-light-soft">
                        {{ optional($student)->initial_name ?? '-' }}
                        @if ($student)
                            · {{ $student->custom_id }}
                        @endif
                    </p>
                </div>

                <div class="text-end">
                    <div class="hero-amount {{ $isVoid ? 'hero-amount-void' : '' }}">
                        Rs. {{ number_format((float) $payment->amount, 2) }}
                    </div>
                    <span class="{{ $statusClass }} mt-2">
                        <i class="bi {{ $statusIcon }}"></i> {{ $statusLabel }}
                    </span>
                </div>
            </div>
        </div>

        {{-- DETAILS --}}
        <div class="details-card">
            <div class="details-header">
                <h5 class="details-title">
                    <i class="bi bi-info-circle-fill"></i>
                    Transaction Details
                </h5>

                <div class="d-flex gap-2 flex-wrap">
                    @if ($isPaid)
                        <button type="button" onclick="window.print()" class="btn-print">
                            <i class="bi bi-printer"></i> Print Receipt
                        </button>
                    @endif

                    <a href="{{ route('admission-payments.index') }}" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="details-body">
                <div class="info-grid">

                    {{-- Receipt --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-receipt"></i> Receipt Number</div>
                        <div class="info-value">
                            @if ($payment->receipt_number)
                                <span class="receipt">{{ $payment->receipt_number }}</span>
                            @else
                                <span class="text-muted">Issued when the payment is marked as paid</span>
                            @endif
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-cash-stack"></i> {{ $isPaid ? 'Amount Paid' : 'Amount' }}</div>
                        <div class="info-value amount {{ $isVoid ? 'amount-void' : '' }}">
                            Rs. {{ number_format((float) $payment->amount, 2) }}
                        </div>
                        @if (optional($payment->admission)->amount !== null && (float) $payment->admission->amount !== (float) $payment->amount)
                            <small class="text-muted">
                                Standard fee: Rs. {{ number_format((float) $payment->admission->amount, 2) }}
                            </small>
                        @endif
                    </div>

                    {{-- Student --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-person-badge-fill"></i> Student</div>
                        <div class="info-value">
                            @if ($student)
                                <a href="{{ route('students.show', $student->id) }}" class="info-link">
                                    {{ $student->initial_name ?? $student->full_name ?? '-' }}
                                </a>
                                <br>
                                <small class="text-muted">ID: {{ $student->custom_id ?? '-' }}</small>
                                @if ($student->guardian_mobile)
                                    <small class="text-muted"> · {{ $student->guardian_mobile }}</small>
                                @endif
                            @else
                                <span class="text-muted">Student record not found</span>
                            @endif
                        </div>
                    </div>

                    {{-- Admission --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-journal-bookmark-fill"></i> Admission Type</div>
                        <div class="info-value">
                            {{ optional($payment->admission)->name ?? '-' }}
                        </div>
                    </div>

                    {{-- Method --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-credit-card"></i> Payment Method</div>
                        <div class="info-value">
                            <span class="method-badge {{ $methodClass }}">
                                <i class="bi {{ $methodIcon }}"></i> {{ $methodLabel }}
                            </span>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-flag-fill"></i> Payment Status</div>
                        <div class="info-value">
                            <span class="{{ $statusClass }}">
                                <i class="bi {{ $statusIcon }}"></i> {{ $statusLabel }}
                            </span>
                        </div>
                    </div>

                    {{-- Collected By --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-person-workspace"></i> Collected By</div>
                        <div class="info-value">
                            {{ optional($payment->collectedBy)->name ?? 'System' }}
                            @if ($payment->branch)
                                <br><small class="text-muted"><i class="bi bi-building"></i> {{ $payment->branch->name }}</small>
                            @endif
                        </div>
                    </div>

                    {{-- Paid At --}}
                    <div class="info-card">
                        <div class="info-label"><i class="bi bi-calendar-check-fill"></i> Payment Date & Time</div>
                        <div class="info-value">
                            @if ($payment->paid_at)
                                {{ $payment->paid_at->format('l, d F Y') }}
                                <br>
                                <small class="text-muted">{{ $payment->paid_at->format('h:i A') }}</small>
                            @else
                                <span class="text-muted">Not paid yet</span>
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
                    <a href="{{ route('admission-payments.edit', $payment) }}" class="btn-back btn-edit">
                        <i class="bi bi-pencil-square"></i> Edit Payment
                    </a>

                    @unless ($isPaid)
                        <form action="{{ route('admission-payments.destroy', $payment) }}" method="POST"
                            onsubmit="return confirm('Delete this {{ strtolower($statusLabel) }} payment?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-back btn-delete">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    @endunless

                    <a href="{{ route('admission-payments.index') }}" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">
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
        .payment-details-page {
            animation: fadeIn .4s ease;
        }

        .custom-alert {
            border-radius: 16px;
        }

        /* ---------- Hero ---------- */
        .hero-card {
            position: relative;
            overflow: hidden;
            border-radius: 32px;
            padding: 2rem;
            background: linear-gradient(135deg, #2563eb, #1d4ed8, #1e40af);
            box-shadow: 0 20px 45px rgba(37, 99, 235, .25);
            color: #fff;
            margin-bottom: 1.5rem;
        }

        .hero-card::before {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            background: rgba(255, 255, 255, .08);
            border-radius: 50%;
            top: -100px;
            right: -80px;
        }

        .hero-card::after {
            content: '';
            position: absolute;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, .06);
            border-radius: 50%;
            bottom: -60px;
            left: -40px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .6rem 1rem;
            border-radius: 14px;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .08);
            font-size: .85rem;
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .hero-amount {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .hero-amount-void {
            text-decoration: line-through;
            opacity: .7;
        }

        .text-light-soft {
            color: rgba(255, 255, 255, .78);
        }

        /* ---------- Details card ---------- */
        .details-card {
            background: #fff;
            border-radius: 28px;
            border: 1px solid #eef2f7;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
        }

        .details-header {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            border-bottom: 1px solid #eef2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .details-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .details-title i {
            color: #2563eb;
            font-size: 1.2rem;
        }

        .details-body {
            padding: 1.5rem;
        }

        /* ---------- Buttons ---------- */
        .btn-back,
        .btn-print {
            border: none;
            border-radius: 12px;
            padding: .5rem 1.25rem;
            font-weight: 600;
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            transition: all .2s ease;
        }

        .btn-back { background: #64748b; }
        .btn-back:hover { background: #475569; color: #fff; transform: translateY(-2px); }

        .btn-print { background: #0ea5e9; }
        .btn-print:hover { background: #0284c7; color: #fff; transform: translateY(-2px); }

        .btn-edit { background: #f59e0b; }
        .btn-edit:hover { background: #d97706; }

        .btn-delete { background: #ef4444; }
        .btn-delete:hover { background: #dc2626; }

        /* ---------- Info grid ---------- */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .info-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 1.25rem;
            border: 1px solid #eef2f7;
            transition: border-color .2s ease, background .2s ease;
        }

        .info-card:hover {
            background: #fff;
            border-color: #bfdbfe;
        }

        .info-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 700;
            color: #64748b;
            margin-bottom: .5rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .info-label i {
            color: #2563eb;
            font-size: .9rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
        }

        .info-value.amount {
            font-size: 1.3rem;
            color: #10b981;
        }

        .info-value.amount-void {
            color: #94a3b8;
            text-decoration: line-through;
        }

        .info-link {
            color: #0f172a;
            text-decoration: none;
        }

        .info-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .receipt {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            background: #f1f5f9;
            padding: .25rem .75rem;
            border-radius: 8px;
            display: inline-block;
        }

        /* ---------- Badges ---------- */
        .badge-paid,
        .badge-pending,
        .badge-cancelled,
        .badge-refunded,
        .method-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .85rem;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 600;
        }

        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-refunded { background: #e0f2fe; color: #075985; }

        .method-cash { background: #f1f5f9; color: #475569; }
        .method-card { background: #dbeafe; color: #1e40af; }
        .method-bank { background: #cffafe; color: #0e7490; }
        .method-online { background: #dcfce7; color: #166534; }

        /* ---------- Note ---------- */
        .note-section {
            margin-top: 1.5rem;
            background: #fef3c7;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            border-left: 4px solid #f59e0b;
        }

        .note-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 700;
            color: #92400e;
            margin-bottom: .5rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .note-text {
            font-size: .9rem;
            color: #78350f;
            line-height: 1.5;
            white-space: pre-line;
        }

        /* ---------- Timeline ---------- */
        .timeline {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eef2f7;
        }

        .timeline-title {
            font-size: .8rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .timeline-item {
            display: flex;
            gap: 1rem;
            padding: .75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .timeline-item:last-child {
            border-bottom: 0;
        }

        .timeline-icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            background: #eff6ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2563eb;
        }

        .timeline-icon-green {
            background: #ecfdf5;
            color: #10b981;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-event {
            font-weight: 600;
            color: #0f172a;
            font-size: .85rem;
        }

        .timeline-date {
            font-size: .72rem;
            color: #64748b;
        }

        /* ---------- Actions ---------- */
        .action-buttons {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eef2f7;
            display: flex;
            gap: .75rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .action-buttons form {
            margin: 0;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {
            .hero-card { padding: 1.5rem; }
            .hero-amount { font-size: 1.5rem; }
            .info-grid { grid-template-columns: 1fr; gap: 1rem; }
            .details-header { flex-direction: column; align-items: flex-start; }
            .action-buttons { justify-content: stretch; }
            .action-buttons > *,
            .action-buttons .btn-back { flex: 1; justify-content: center; }
        }

        /* ---------- Print ---------- */
        @media print {
            .details-header .d-flex,
            .action-buttons,
            .alert,
            .sidebar,
            .top-navbar {
                display: none !important;
            }

            .hero-card {
                background: none !important;
                color: #000 !important;
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .hero-card::before,
            .hero-card::after {
                display: none;
            }

            .hero-badge,
            .text-light-soft {
                color: #000 !important;
                background: none !important;
                border: 0;
            }

            .details-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .info-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
@endpush