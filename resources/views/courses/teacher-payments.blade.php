@extends('layouts.app')

@section('title', 'Course Teacher Payments')
@section('page-title', 'Course Teacher Payments')

@section('content')
<div class="dashboard-page">

    <div class="hero-section tw:mb-6!">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Courses</span>
                <h1 class="hero-title">Course Teacher Payments</h1>
                <p class="hero-subtitle">Teachers' share of course payments. Separate from the monthly teacher salary.</p>
                <div class="hero-stats-mini">
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($totals['earned'], 2) }}</span><span class="mini-stat-label">Earned</span></div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($totals['paid'], 2) }}</span><span class="mini-stat-label">Paid</span></div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat"><span class="mini-stat-value money">{{ number_format($totals['balance'], 2) }}</span><span class="mini-stat-label">To pay</span></div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-actions"><a href="{{ route('courses.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Courses</a></div>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')

    <div class="nx-card">
        <div class="ui-table-responsive">
            <table class="ui-table premium-table tw:mb-0!">
                <thead><tr><th>Teacher</th><th class="tw:text-right!">Courses</th><th class="tw:text-right!">Earned</th><th class="tw:text-right!">Paid</th><th class="tw:text-right!">Balance</th><th class="tw:text-right!"></th></tr></thead>
                <tbody>
                    @forelse ($teachers as $row)
                        <tr>
                            <td><a class="cell-title tw:[text-decoration:none]!" href="{{ route('course-teacher-payments.show', $row['teacher']) }}">{{ $row['teacher']->full_name }}</a><small class="cell-sub tw:block!">{{ $row['teacher']->custom_id }}</small></td>
                            <td class="tw:text-right!">{{ $row['courses'] }}</td>
                            <td class="tw:text-right! money">{{ number_format($row['earned'], 2) }}</td>
                            <td class="tw:text-right! money">{{ number_format($row['paid'], 2) }}</td>
                            <td class="tw:text-right! money"><strong class="{{ $row['balance'] < 0 ? 'tw:text-(color:--nx-red)!' : '' }}">{{ number_format($row['balance'], 2) }}</strong></td>
                            <td class="tw:text-right!"><a href="{{ route('course-teacher-payments.show', $row['teacher']) }}" class="nx-btn nx-btn-primary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-table">No course teachers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@include('courses.partials.styles')
