@extends('layouts.app')

@section('title', $teacher->full_name . ' - Course Payments')
@section('page-title', 'Course Teacher Payment')

@section('content')
<div class="dashboard-page">
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Course teacher · {{ $teacher->custom_id }}</span>
                <h1 class="hero-title">{{ $teacher->full_name }}</h1>
                <p class="hero-subtitle">Share of course payments. Separate from the monthly teacher salary.</p>
                <div class="hero-stats-mini">
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($totals['earned'], 2) }}</span><span class="mini-stat-label">Earned</span></div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($totals['paid'], 2) }}</span><span class="mini-stat-label">Paid</span></div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($totals['balance'], 2) }}</span><span class="mini-stat-label">To pay</span></div>
                </div>
            </div>
            <div class="hero-right"><div class="hero-actions"><a href="{{ route('course-teacher-payments.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> All teachers</a></div></div>
        </div>
    </div>

    @include('courses.partials.alerts')

    <div class="desk-grid mb-4">
        @foreach ($rows as $row)
            <div class="desk-card">
                <div class="d-flex justify-content-between gap-2">
                    <h6><a class="text-decoration-none" href="{{ route('courses.show', $row['course']) }}#teachers">{{ $row['course']->name }}</a></h6>
                    <span class="cell-sub">{{ $row['percentage'] !== null ? rtrim(rtrim(number_format($row['percentage'], 2), '0'), '.') . '%' : 'removed' }}</span>
                </div>
                <dl class="kv mb-0">
                    <dt>Earned</dt><dd class="money">{{ number_format($row['earned'], 2) }}</dd>
                    <dt>Paid</dt><dd class="money">{{ number_format($row['paid'], 2) }}</dd>
                    <dt>Balance</dt><dd class="money {{ $row['balance'] < 0 ? 'text-danger' : '' }}">{{ number_format($row['balance'], 2) }}</dd>
                </dl>
                @if ($row['balance'] > 0)
                    <form method="POST" action="{{ route('courses.payouts.store', $row['course']) }}" class="d-flex flex-column gap-2" onsubmit="if (!confirm('Pay ' + this.amount.value + ' to {{ addslashes($teacher->full_name) }}?')) return false; this.querySelector('button[type=submit]').disabled = true;">
                        @csrf
                        <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                        <div class="d-flex gap-2">
                            <input type="number" step="0.01" min="1" max="{{ $row['balance'] }}" name="amount" class="form-control nx-input" value="{{ $row['balance'] }}" required aria-label="Amount">
                            <select name="payment_method" class="form-select nx-input" aria-label="Method">
                                @foreach ($methods as $method)
                                    <option value="{{ $method }}">{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input name="note" class="form-control nx-input" maxlength="500" placeholder="Note (optional)" aria-label="Note">
                        <button class="nx-btn nx-btn-primary" type="submit"><i class="bi bi-cash"></i> Pay teacher</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>

    <div class="nx-card">
        <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-clock-history text-primary"></i> Payments to {{ $teacher->full_name }}</h5></div>
        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead><tr><th>Date</th><th>Course</th><th>Method</th><th class="text-end">Amount</th><th>By</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                    @forelse ($payouts as $payout)
                        <tr class="{{ $payout->status === 'cancelled' ? 'row-muted' : '' }}">
                            <td>{{ $payout->paid_on->format('d M Y') }}</td>
                            <td>{{ $payout->course->name ?? '' }}@if ($payout->note)<small class="cell-sub d-block">{{ $payout->note }}</small>@endif</td>
                            <td>{{ ucwords(str_replace('_', ' ', $payout->payment_method)) }}</td>
                            <td class="text-end money">{{ number_format($payout->amount, 2) }}</td>
                            <td><small>{{ $payout->paidBy->name ?? '' }}</small></td>
                            <td>@include('courses.partials.status-badge', ['status' => $payout->status])</td>
                            <td class="text-end">
                                <div class="action-buttons">
                                    <a class="action-btn" href="{{ route('course-teacher-payouts.slip', $payout) }}" title="Slip" aria-label="Payment slip"><i class="bi bi-printer"></i></a>
                                    @if ($payout->status === 'paid')
                                        <form method="POST" action="{{ route('courses.payouts.cancel', [$payout->course_id, $payout]) }}" onsubmit="return confirm('Cancel this teacher payment?')">
                                            @csrf @method('PATCH')
                                            <button class="action-btn action-btn-danger" title="Cancel" aria-label="Cancel teacher payment"><i class="bi bi-x-circle-fill"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-table">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@include('courses.partials.styles')
