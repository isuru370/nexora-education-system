@extends('layouts.app')

@section('title', 'Course Owner Payments')
@section('page-title', 'Course Owner Payments')

@section('content')
<div class="dashboard-page">

    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Courses</span>
                <h1 class="hero-title">Course Owner Payments</h1>
                <p class="hero-subtitle">The institute's share of each course, and how much of it was given to the owner.</p>
                <div class="hero-stats-mini">
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($totals['earned'], 2) }}</span><span class="mini-stat-label">Institute share</span></div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($totals['paid'], 2) }}</span><span class="mini-stat-label">Given to owner</span></div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($totals['balance'], 2) }}</span><span class="mini-stat-label">To give</span></div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-actions"><a href="{{ route('courses.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Courses</a></div>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')

    <div class="nx-card mb-4">
        <div class="table-responsive">
            <table class="table premium-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th class="text-end">Institute %</th>
                        <th class="text-end">Institute share</th>
                        <th class="text-end">Given to owner</th>
                        <th class="text-end">Balance</th>
                        <th style="min-width: 360px;">Give to owner</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $row)
                        @php($course = $row['course'])
                        <tr>
                            <td>
                                <a class="cell-title text-decoration-none" href="{{ route('courses.show', $course) }}">{{ $course->name }}</a>
                                <small class="cell-sub d-block">{{ $course->code }} &middot; {{ optional($course->branch)->name }}</small>
                            </td>
                            <td class="text-end">{{ rtrim(rtrim(number_format($course->institutePercentage(), 2), '0'), '.') }}%</td>
                            <td class="text-end money">{{ number_format($row['earned'], 2) }}</td>
                            <td class="text-end money">{{ number_format($row['paid'], 2) }}</td>
                            <td class="text-end money"><strong class="{{ $row['balance'] > 0 ? 'text-warning' : '' }}">{{ number_format($row['balance'], 2) }}</strong></td>
                            <td>
                                @if ($row['balance'] > 0)
                                    <form method="POST" action="{{ route('courses.owner-payouts.store', $course) }}" class="d-flex flex-wrap gap-2 align-items-center"
                                        onsubmit="return confirm('Save: institute share of {{ e($course->name) }} given to the owner?')">
                                        @csrf
                                        <input type="number" step="0.01" min="1" max="{{ $row['balance'] }}" name="amount" value="{{ $row['balance'] }}"
                                            class="form-control form-control-sm" style="width: 120px;" aria-label="Amount" required>
                                        <select name="payment_method" class="form-select form-select-sm" style="width: 130px;" aria-label="Method">
                                            @foreach (\App\Models\CoursePayment::METHODS as $method)
                                                <option value="{{ $method }}">{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                            @endforeach
                                        </select>
                                        <input name="note" class="form-control form-control-sm" style="width: 140px;" maxlength="500" placeholder="Note" aria-label="Note">
                                        <button class="nx-btn nx-btn-primary btn-sm" type="submit"><i class="bi bi-building-check"></i> Paid</button>
                                    </form>
                                @else
                                    <small class="cell-sub">Nothing to give now</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-table">No courses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-clock-history text-primary"></i> Given to the owner</h5>
                <p class="nx-card-subtitle">Latest 100</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table premium-table mb-0 align-middle">
                <thead><tr><th>Date</th><th>Course</th><th>Method</th><th class="text-end">Amount</th><th>By</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($payouts as $payout)
                        <tr class="{{ $payout->status === 'cancelled' ? 'row-muted' : '' }}">
                            <td>{{ $payout->paid_on->format('d M Y') }}</td>
                            <td>{{ optional($payout->course)->name }} @if ($payout->note)<small class="cell-sub d-block">{{ $payout->note }}</small>@endif</td>
                            <td>{{ ucwords(str_replace('_', ' ', $payout->payment_method)) }}</td>
                            <td class="text-end money">{{ number_format($payout->amount, 2) }}</td>
                            <td><small>{{ $payout->paidBy->name ?? '' }}</small></td>
                            <td>@include('courses.partials.status-badge', ['status' => $payout->status])</td>
                            <td class="text-end">
                                @if ($payout->status === 'paid')
                                    <form method="POST" action="{{ route('courses.owner-payouts.cancel', [$payout->course_id, $payout]) }}" onsubmit="return confirm('Cancel this payment to the owner?')">
                                        @csrf @method('PATCH')
                                        <button class="nx-btn nx-btn-light btn-sm" type="submit">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-table">Nothing given to the owner yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@include('courses.partials.styles')
