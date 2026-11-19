@extends('layouts.app')

@section('title', 'Course Payments')
@section('page-title', 'Course Payments')

@section('content')
@php
    $grand = $totals->sum('total');
    $count = $totals->sum('count');
@endphp
<div class="dashboard-page">

    <div class="hero-section tw:mb-6!">
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
                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-end!">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]"><label class="nx-label" for="from">From</label><input type="date" id="from" name="from" class="ui-form-control custom-input" value="{{ $from->toDateString() }}"></div>
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]"><label class="nx-label" for="to">To</label><input type="date" id="to" name="to" class="ui-form-control custom-input" value="{{ $to->toDateString() }}"></div>
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                        <label class="nx-label" for="course_id">Course</label>
                        <select id="course_id" name="course_id" class="ui-form-select custom-input">
                            <option value="">All courses</option>
                            @foreach ($courses as $c)
                                <option value="{{ $c->id }}" {{ (string) request('course_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                        <label class="nx-label" for="status">Status</label>
                        <select id="status" name="status" class="ui-form-select custom-input">
                            <option value="">All</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[8.33333333%]"><button class="nx-btn nx-btn-primary tw:w-full!" type="submit" aria-label="Filter"><i class="bi bi-funnel"></i></button></div>
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]"><a href="{{ route('course-payments.index') }}" class="nx-btn nx-btn-light tw:w-full!">Today</a></div>
                </div>
            </form>
        </div>

        <div class="ui-table-responsive">
            <table class="ui-table premium-table tw:mb-0!">
                <thead><tr><th>Receipt</th><th>Date</th><th>Student</th><th>Course</th><th>Method</th><th class="tw:text-right!">Amount</th><th>Status</th><th class="tw:text-right!"></th></tr></thead>
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
            <div class="tw:p-4! tw:bs-border-t!">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection

@include('courses.partials.styles')
