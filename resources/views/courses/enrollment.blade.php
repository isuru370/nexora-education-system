@extends('layouts.app')

@section('title', ($enrollment->student->initial_name ?? 'Student') . ' - ' . $course->name)
@section('page-title', 'Course Student')

@section('content')
@php
    $canManage = in_array(strtoupper((string) optional(auth()->user()->userType)->code), ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], true);
    $student = $enrollment->student;
    $next = $fees['next_installment'];
@endphp
<div class="dashboard-page">

    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">{{ $course->name }}</span>
                <h1 class="hero-title">{{ $student->initial_name ?? '—' }}</h1>
                <p class="hero-subtitle">{{ $student->custom_id }} · {{ $enrollment->payment_plan === 'full' ? 'Pays at once' : $course->installment_count . ' installments' }} · enrolled {{ $enrollment->enrolled_at->format('d M Y') }}</p>
                @if (!$enrollment->isActive())
                    <div class="hero-stats-mini"><div class="mini-stat">@include('courses.partials.status-badge', ['status' => 'cancelled'])</div></div>
                @endif
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('courses.show', $course) }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Course</a>
                    @if ($canManage && $enrollment->isActive() && $fees['paid'] <= 0)
                        <form method="POST" action="{{ route('courses.enrollments.cancel', [$course, $enrollment]) }}" onsubmit="return confirm('Remove this student from the course?')">
                            @csrf @method('PATCH')
                            <button class="nx-btn nx-btn-light" type="submit"><i class="bi bi-person-x"></i> Cancel enrollment</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')

    <div class="row g-4 mb-4">
        {{-- FEES + PAY --}}
        <div class="col-lg-5">
            <div class="nx-card h-100">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-wallet2 text-primary"></i> Fees</h5></div>
                <div class="p-3">
                    <dl class="kv mb-3">
                        <dt>Course fee</dt><dd class="money">Rs. {{ number_format($fees['total_fee'], 2) }}</dd>
                        <dt>Paid</dt><dd class="money text-success">Rs. {{ number_format($fees['paid'], 2) }}</dd>
                        <dt>Balance</dt><dd class="money">Rs. {{ number_format($fees['balance'], 2) }}</dd>
                        @if ($fees['overdue'] > 0)
                            <dt>Overdue</dt><dd class="money text-danger">Rs. {{ number_format($fees['overdue'], 2) }}</dd>
                        @endif
                        <dt>Attendance</dt><dd>{{ $attendance['attended'] }} / {{ $attendance['held'] }} ({{ $attendance['percentage'] }}%)</dd>
                    </dl>

                    @if ($enrollment->isActive() && $fees['balance'] > 0)
                        <form method="POST" action="{{ route('courses.payments.store', [$course, $enrollment]) }}" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                            @csrf
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <label class="nx-label" for="amount">Amount *</label>
                                    <input id="amount" type="number" step="0.01" min="1" max="{{ $fees['balance'] }}" name="amount" class="form-control nx-input" required
                                        value="{{ old('amount', $next ? $next['remaining'] : $fees['balance']) }}">
                                </div>
                                <div class="col-sm-6">
                                    <label class="nx-label" for="payment_method">Method</label>
                                    <select id="payment_method" name="payment_method" class="form-select nx-input">
                                        @foreach ($methods as $method)
                                            <option value="{{ $method }}" {{ old('payment_method', 'cash') === $method ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="nx-label" for="note">Note</label>
                                    <input id="note" name="note" class="form-control nx-input" maxlength="500" value="{{ old('note') }}">
                                </div>
                                <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                                    @if ($next)
                                        <button type="button" class="nx-btn nx-btn-light" onclick="document.getElementById('amount').value='{{ $next['remaining'] }}'">Installment {{ $next['installment_no'] }}: {{ number_format($next['remaining'], 2) }}</button>
                                    @endif
                                    <button type="button" class="nx-btn nx-btn-light" onclick="document.getElementById('amount').value='{{ $fees['balance'] }}'">Full balance</button>
                                    <button type="submit" class="nx-btn nx-btn-primary ms-auto"><i class="bi bi-cash-coin"></i> Take payment</button>
                                </div>
                            </div>
                        </form>
                    @elseif ($fees['balance'] <= 0)
                        <div class="nx-alert nx-alert-green mb-0"><i class="bi bi-check-circle-fill"></i><span>Fully paid.</span></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- INSTALLMENTS --}}
        <div class="col-lg-7">
            <div class="nx-card h-100">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-calendar3 text-primary"></i> {{ $enrollment->payment_plan === 'full' ? 'Payment' : 'Installments' }}</h5></div>
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead><tr><th>#</th><th>Due</th><th class="text-end">Amount</th><th class="text-end">Paid</th><th class="text-end">Left</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($fees['installments'] as $row)
                                <tr>
                                    <td>{{ $row['installment_no'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row['due_date'])->format('d M Y') }}</td>
                                    <td class="text-end money">{{ number_format($row['amount'], 2) }}</td>
                                    <td class="text-end money">{{ number_format($row['paid'], 2) }}</td>
                                    <td class="text-end money">{{ number_format($row['remaining'], 2) }}</td>
                                    <td>@include('courses.partials.status-badge', ['status' => $row['status']])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="nx-card">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-receipt text-primary"></i> Payments</h5></div>
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th class="text-end">Amount</th><th>Status</th><th class="text-end"></th></tr></thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                @include('courses.partials.payment-row', ['payment' => $payment, 'showStudent' => false])
                            @empty
                                <tr><td colspan="6" class="empty-table">No payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="nx-card">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-person-check text-primary"></i> Attended</h5></div>
                <ul class="list-group list-group-flush">
                    @forelse ($attended as $row)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $row->session->session_date->format('D, d M Y') }}</span><small class="cell-sub">{{ $row->attended_at->format('h:i A') }}</small></li>
                    @empty
                        <li class="list-group-item cell-sub">No attendance yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@include('courses.partials.styles')
