@extends('layouts.app')

@section('title', 'Course Payments')
@section('page-title', 'Course Payments')

@section('content')
@php
    $grand = $totals->sum('total');
    $count = $totals->sum('count');
@endphp
<div class="dashboard-page">

    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Courses</span>
                <h1 class="hero-title">Course Payments</h1>
                <p class="hero-subtitle">{{ $from->format('d M Y') }}{{ $from->isSameDay($to) ? '' : ' – ' . $to->format('d M Y') }}</p>
                <div class="hero-stats-mini">
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($grand, 2) }}</span><span class="mini-stat-label">Collected</span></div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat"><span class="mini-stat-value">{{ $count }}</span><span class="mini-stat-label">Payments</span></div>
                    @foreach ($totals as $row)
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($row->total, 2) }}</span><span class="mini-stat-label">{{ ucwords(str_replace('_', ' ', $row->payment_method)) }}</span></div>
                    @endforeach
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-actions"><a href="{{ route('courses.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Courses</a></div>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')

    <div class="nx-card">
        <div class="nx-search">
            <form method="GET" action="{{ route('course-payments.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-sm-6 col-lg-2"><label class="nx-label" for="from">From</label><input type="date" id="from" name="from" class="form-control custom-input" value="{{ $from->toDateString() }}"></div>
                    <div class="col-sm-6 col-lg-2"><label class="nx-label" for="to">To</label><input type="date" id="to" name="to" class="form-control custom-input" value="{{ $to->toDateString() }}"></div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="nx-label" for="course_id">Course</label>
                        <select id="course_id" name="course_id" class="form-select custom-input">
                            <option value="">All courses</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" {{ (string) request('course_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="nx-label" for="status">Status</label>
                        <select id="status" name="status" class="form-select custom-input">
                            <option value="">All</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-1"><button class="nx-btn nx-btn-primary w-100" type="submit" aria-label="Filter"><i class="bi bi-funnel"></i></button></div>
                    <div class="col-6 col-lg-2"><a href="{{ route('course-payments.index') }}" class="nx-btn nx-btn-light w-100">Today</a></div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead><tr><th>Receipt</th><th>Date</th><th>Student</th><th>Course</th><th>Method</th><th class="text-end">Amount</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        @include('courses.partials.payment-row', ['payment' => $payment, 'showStudent' => true, 'showCourse' => true])
                    @empty
                        <tr><td colspan="8" class="empty-table">No course payments for these dates.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())
            <div class="p-3 border-top">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection

@include('courses.partials.styles')
