@extends('layouts.app')

@section('title', 'Course Payment')
@section('page-title', 'Course Payment')

@section('content')
<div class="dashboard-page">
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Courses</span>
                <h1 class="hero-title">Course Payment</h1>
                <p class="hero-subtitle">Scan the student and take the payment. The receipt opens after saving.</p>
            </div>
            <div class="hero-right"><div class="hero-actions"><a href="{{ route('course-payments.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-receipt"></i> Today's payments</a></div></div>
        </div>
    </div>

    @include('courses.partials.alerts')
    @include('courses.partials.student-scan', ['action' => route('course-desk.payment')])

    @if ($student)
        <div class="desk-grid mb-4">
            @forelse ($enrolled as $row)
                @php
                    $e = $row['enrollment'];
                    $f = $row['fees'];
                    $next = $f['next_installment'];
                @endphp
                <div class="desk-card">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <h6>{{ $e->course->name }}</h6>
                            <small class="cell-sub">{{ $e->payment_plan === 'full' ? 'Pays at once' : 'Installments' }}</small>
                        </div>
                        <a href="{{ route('courses.enrollments.show', [$e->course_id, $e]) }}" class="cell-sub">Details</a>
                    </div>
                    <dl class="kv mb-0">
                        <dt>Fee</dt><dd class="money">{{ number_format($f['total_fee'], 2) }}</dd>
                        <dt>Paid</dt><dd class="money">{{ number_format($f['paid'], 2) }}</dd>
                        <dt>Balance</dt><dd class="money">{{ number_format($f['balance'], 2) }}</dd>
                        @if ($next)
                            <dt>Next</dt><dd>{{ number_format($next['remaining'], 2) }} · {{ \Carbon\Carbon::parse($next['due_date'])->format('d M') }} @if ($next['status'] === 'overdue')<span class="text-danger">overdue</span>@endif</dd>
                        @endif
                    </dl>
                    @if ($f['balance'] > 0)
                        <form method="POST" action="{{ route('courses.payments.store', [$e->course_id, $e]) }}" class="d-flex flex-column gap-2" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                            @csrf
                            <div class="d-flex gap-2">
                                <input type="number" step="0.01" min="1" max="{{ $f['balance'] }}" name="amount" class="form-control nx-input" value="{{ $next ? $next['remaining'] : $f['balance'] }}" required aria-label="Amount">
                                <select name="payment_method" class="form-select nx-input" aria-label="Method">
                                    @foreach ($methods as $method)
                                        <option value="{{ $method }}">{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="nx-btn nx-btn-primary" type="submit"><i class="bi bi-cash-coin"></i> Take payment</button>
                        </form>
                    @else
                        <span class="nx-badge nx-badge-green align-self-start">Fully paid</span>
                    @endif
                </div>
            @empty
                <div class="nx-alert nx-alert-blue"><i class="bi bi-info-circle-fill"></i><span>Not in any course. <a href="{{ route('course-desk.enroll', ['code' => request('code')]) }}">Enroll</a></span></div>
            @endforelse
        </div>

        @if ($recent->count())
            <div class="nx-card">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-clock-history text-primary"></i> Recent payments</h5></div>
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead><tr><th>Receipt</th><th>Date</th><th>Course</th><th class="text-end">Amount</th><th>Status</th><th class="text-end"></th></tr></thead>
                        <tbody>
                            @foreach ($recent as $payment)
                                <tr class="{{ $payment->isCompleted() ? '' : 'row-muted' }}">
                                    <td>{{ $payment->receipt_number }}</td>
                                    <td>{{ $payment->paid_at->format('d M Y h:i A') }}</td>
                                    <td>{{ $payment->course->name ?? '' }}</td>
                                    <td class="text-end money">{{ number_format($payment->amount, 2) }}</td>
                                    <td>@include('courses.partials.status-badge', ['status' => $payment->status])</td>
                                    <td class="text-end"><a class="action-btn" href="{{ route('course-payments.receipt', $payment) }}" title="Receipt" aria-label="Receipt"><i class="bi bi-receipt"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection

@include('courses.partials.styles')
