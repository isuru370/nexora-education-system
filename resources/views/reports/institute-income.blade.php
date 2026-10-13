@extends('layouts.app')

@section('title', 'Institute Income')
@section('page-title', 'Institute Income')

@include('reports.partials.styles')

@section('content')
    @php
        $query = array_filter([
            'from' => $filter->from->toDateString(),
            'to' => $filter->to->toDateString(),
            'branch_id' => $filter->branchId,
        ]);

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->startOfMonth()->subMonth();
        $quickRanges = [
            'This month' => ['from' => $thisMonth->toDateString(), 'to' => now()->toDateString()],
            'Last month' => ['from' => $lastMonth->toDateString(), 'to' => $lastMonth->copy()->endOfMonth()->toDateString()],
            'This year' => ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()],
        ];
    @endphp

    <div class="report-page">

        {{-- HERO --}}
        <div class="report-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1">Institute Income</h3>
                    <div class="sub">
                        <i class="bi bi-calendar-event"></i> {{ $filter->dateLabel() }}
                        &nbsp;•&nbsp; <i class="bi bi-building"></i> {{ $branchName }}
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('reports.institute-income.export', ['tab' => $tab, 'format' => 'excel'] + $query) }}"
                        class="btn btn-light btn-export">
                        <i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel
                    </a>
                    <a href="{{ route('reports.institute-income.export', ['tab' => $tab, 'format' => 'pdf'] + $query) }}"
                        class="btn btn-light btn-export">
                        <i class="bi bi-filetype-pdf text-danger"></i> PDF
                    </a>
                </div>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger" style="border-radius: 16px;">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 16px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- FILTER --}}
        <div class="report-card">
            <form method="GET" action="{{ route('reports.institute-income.index', ['tab' => $tab]) }}" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="report-label">From</label>
                    <input type="date" name="from" class="form-control report-input" value="{{ $filter->from->toDateString() }}"
                        max="{{ now()->toDateString() }}">
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="report-label">To</label>
                    <input type="date" name="to" class="form-control report-input" value="{{ $filter->to->toDateString() }}"
                        max="{{ now()->toDateString() }}">
                </div>

                @if ($branches->count() > 1)
                    <div class="col-lg-3 col-md-4">
                        <label class="report-label">Branch</label>
                        <select name="branch_id" class="form-select report-input">
                            <option value="">All branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (int) $filter->branchId === (int) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-lg-3 col-md-4">
                    <button type="submit" class="btn btn-primary w-100 report-input">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>

            <div class="d-flex gap-2 flex-wrap mt-3">
                @foreach ($quickRanges as $label => $range)
                    <a href="{{ route('reports.institute-income.index', ['tab' => $tab] + $range + array_filter(['branch_id' => $filter->branchId])) }}"
                        class="btn btn-sm {{ $range['from'] === $filter->from->toDateString() && $range['to'] === $filter->to->toDateString() ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- STATS --}}
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-green">
                    <div class="label">Institute income</div>
                    <div class="value">Rs. {{ number_format($summary['total_income'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-red">
                    <div class="label">Institute expenses</div>
                    <div class="value">Rs. {{ number_format($summary['expenses'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-blue">
                    <div class="label">Net income</div>
                    <div class="value">Rs. {{ number_format($summary['net'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-primary">
                    <div class="label">Class fees collected ({{ $summary['class_fee_count'] }})</div>
                    <div class="value">Rs. {{ number_format($summary['class_fees'], 2) }}</div>
                </div>
            </div>
        </div>

        @if ($summary['unsplit_count'] > 0)
            <div class="alert alert-warning" style="border-radius: 16px;">
                <i class="bi bi-exclamation-triangle"></i>
                {{ $summary['unsplit_count'] }} class fee payment(s) (Rs. {{ number_format($summary['unsplit_amount'], 2) }})
                have no fee split, so their institute share is not counted.
            </div>
        @endif

        {{-- TABS --}}
        <ul class="nav report-tabs gap-2 mb-3">
            @foreach ($tabs as $key => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                        href="{{ route('reports.institute-income.index', ['tab' => $key] + $query) }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($tab === 'summary')
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="report-card h-100">
                        <h6 class="mb-3"><i class="bi bi-arrow-down-circle text-success"></i> Institute income</h6>
                        <table class="w-100 mini-table">
                            @foreach ($summary['income'] as $label => $amount)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end amount">Rs. {{ number_format($amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-bold">
                                <td>Total income</td>
                                <td class="text-end amount">Rs. {{ number_format($summary['total_income'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Institute expenses</td>
                                <td class="text-end amount text-danger">- Rs. {{ number_format($summary['expenses'], 2) }}</td>
                            </tr>
                            <tr class="fw-bold text-primary">
                                <td>Net income</td>
                                <td class="text-end amount">Rs. {{ number_format($summary['net'], 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="report-card h-100">
                        <h6 class="mb-3"><i class="bi bi-pie-chart"></i> Class fee split</h6>
                        <table class="w-100 mini-table">
                            <tr class="fw-bold">
                                <td>Class fees collected</td>
                                <td class="text-end amount">Rs. {{ number_format($summary['class_fees'], 2) }}</td>
                            </tr>
                            @foreach ($summary['split'] as $label => $amount)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end amount">Rs. {{ number_format($amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="text-muted">Hall fee (part of institute share)</td>
                                <td class="text-end amount text-muted">Rs. {{ number_format($summary['hall_fee'], 2) }}</td>
                            </tr>
                        </table>
                        <p class="text-muted small mt-3 mb-0">
                            Teacher and organizer shares are not institute money. Their salaries and advances are in the
                            <a href="{{ route('reports.daily-collection.index', ['tab' => 'expenses'] + $query) }}">Collection report</a>.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="report-card">
                @include('reports.partials.table', ['table' => $table, 'rows' => $rows])
            </div>
        @endif

    </div>
@endsection
