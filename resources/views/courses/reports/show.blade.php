@extends('layouts.app')

@section('title', $report['title'])
@section('page-title', 'Course Reports')

@section('content')
@php
    $exportQuery = fn ($format) => request()->fullUrlWithQuery(['export' => $format]);
@endphp
<div class="dashboard-page">
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Course reports</span>
                <h1 class="hero-title">{{ $report['title'] }}</h1>
                <p class="hero-subtitle">{{ $report['subtitle'] }}</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <button type="button" class="nx-btn nx-btn-light" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                    <a href="{{ $exportQuery('pdf') }}" class="nx-btn nx-btn-light"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                    <a href="{{ $exportQuery('excel') }}" class="nx-btn nx-btn-light"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                </div>
            </div>
        </div>
    </div>

    <div class="nx-card mb-4 no-print">
        <ul class="nav nx-tabs">
            @foreach ($types as $key => $label)
                <li class="nav-item"><a class="nav-link {{ $type === $key ? 'active' : '' }}" href="{{ route('course-reports.show', $key) }}">{{ $label }}</a></li>
            @endforeach
        </ul>
        <div class="p-3">
            <form method="GET" action="{{ route('course-reports.show', $type) }}" class="inline-form">
                @if ($type === 'daily')
                    <div><label class="nx-label" for="date">Date</label><input type="date" id="date" name="date" class="form-control nx-input" value="{{ $filters['date'] }}"></div>
                @elseif ($type === 'monthly')
                    <div><label class="nx-label" for="month">Month</label><input type="month" id="month" name="month" class="form-control nx-input" value="{{ $filters['month'] }}"></div>
                @elseif ($type === 'yearly')
                    <div><label class="nx-label" for="year">Year</label><input type="number" id="year" name="year" min="2000" max="2100" class="form-control nx-input" value="{{ $filters['year'] }}"></div>
                @else
                    <div><label class="nx-label" for="active">Courses</label>
                        <select id="active" name="active" class="form-select nx-input">
                            <option value="0">All courses</option>
                            <option value="1" {{ $filters['active'] ? 'selected' : '' }}>Active only</option>
                        </select>
                    </div>
                @endif
                <div style="min-width:auto"><button class="nx-btn nx-btn-primary" type="submit">Show</button></div>
            </form>
        </div>
    </div>

    <div class="report-summary mb-4">
        @foreach ($report['summary'] as $label => $value)
            <div class="nx-card p-3">
                <div class="stat-label">{{ $label }}</div>
                <div class="report-value money">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    @foreach ($report['tables'] as $table)
        <div class="nx-card mb-4">
            <div class="nx-card-header"><h5 class="nx-card-title">{{ $table['title'] }}</h5></div>
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            @foreach ($table['columns'] as $key => $heading)
                                <th class="{{ in_array($key, $table['money'], true) ? 'text-end' : '' }}">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($table['rows'] as $row)
                            <tr>
                                @foreach ($table['columns'] as $key => $heading)
                                    @if (in_array($key, $table['money'], true))
                                        <td class="text-end money">{{ number_format((float) $row[$key], 2) }}</td>
                                    @else
                                        <td>{{ $row[$key] }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($table['columns']) }}" class="empty-table">Nothing to show.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($table['total'] && count($table['rows']))
                        <tfoot>
                            <tr class="report-total">
                                @foreach ($table['columns'] as $key => $heading)
                                    @if ($loop->first)
                                        <td>Total</td>
                                    @elseif (in_array($key, $table['money'], true))
                                        <td class="text-end money">{{ number_format(collect($table['rows'])->sum($key), 2) }}</td>
                                    @else
                                        <td></td>
                                    @endif
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection

@include('courses.partials.styles')

@push('styles')
<style>
    .dashboard-page .report-summary { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 1rem; }
    .dashboard-page .report-value { font-size: 1.25rem; font-weight: 700; margin-top: .25rem; }
    .dashboard-page .report-total td { font-weight: 700; background: var(--nx-head-bg); }
    @media print {
        .sidebar, .top-navbar, .no-print, .hero-actions { display: none !important; }
        .dashboard-page .nx-card { box-shadow: none !important; break-inside: avoid; }
    }
</style>
@endpush
