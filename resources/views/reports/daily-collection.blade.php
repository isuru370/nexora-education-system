@extends('layouts.app')

@section('title', 'Collection Report')
@section('page-title', 'Collection Report')

@include('reports.partials.styles')

@section('content')
    @php
        $query = $filter->toQuery();
    @endphp

    <div class="report-page">

        {{-- HERO --}}
        <div class="report-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1">Collection Report</h3>
                    <div class="sub">
                        <i class="bi bi-calendar-event"></i> {{ $filter->dateLabel() }}
                        &nbsp;•&nbsp; <i class="bi bi-building"></i> {{ $branchName }}
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('reports.daily-collection.export', ['tab' => $tab, 'format' => 'excel'] + $query) }}"
                        class="btn btn-light btn-export">
                        <i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel
                    </a>
                    <a href="{{ route('reports.daily-collection.export', ['tab' => $tab, 'format' => 'pdf'] + $query) }}"
                        class="btn btn-light btn-export">
                        <i class="bi bi-filetype-pdf text-danger"></i> PDF
                    </a>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 16px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- FILTER --}}
        <div class="report-card">
            <form method="GET" action="{{ route('reports.daily-collection.index', ['tab' => $tab]) }}" class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="report-label">From</label>
                    <input type="date" name="from" class="form-control report-input" value="{{ $filter->from->toDateString() }}"
                        max="{{ now()->toDateString() }}">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="report-label">To</label>
                    <input type="date" name="to" class="form-control report-input" value="{{ $filter->to->toDateString() }}"
                        max="{{ now()->toDateString() }}">
                </div>

                @if ($branches->count() > 1)
                    <div class="col-lg-2 col-md-4">
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

                <div class="col-lg-2 col-md-4">
                    <label class="report-label">Collected by</label>
                    <select name="collected_by" class="form-select report-input">
                        <option value="">Everyone</option>
                        @foreach ($collectors as $collector)
                            <option value="{{ $collector->id }}" {{ (int) $filter->collectedBy === (int) $collector->id ? 'selected' : '' }}>
                                {{ $collector->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="report-label">Payment method</label>
                    <select name="payment_method" class="form-select report-input">
                        <option value="">All methods</option>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method }}" {{ $filter->paymentMethod === $method ? 'selected' : '' }}>
                                {{ $methodLabel($method) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 report-input">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="{{ route('reports.daily-collection.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary report-input"
                        title="Reset to today">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>

            @if ($summary['show_expenses'] === false)
                <div class="alert alert-info mt-3 mb-0" style="border-radius: 14px;">
                    <i class="bi bi-info-circle"></i>
                    "Collected by" and "Payment method" only apply to class fees and admission fees,
                    so extra income and expenses are hidden.
                </div>
            @endif
        </div>

        {{-- STATS --}}
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-green">
                    <div class="label">Total income</div>
                    <div class="value">Rs. {{ number_format($summary['total_income'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-primary">
                    <div class="label">Class fees ({{ $summary['student_payment_count'] }} payments)</div>
                    <div class="value">Rs. {{ number_format($summary['income']['Class fee payments'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-red">
                    <div class="label">Total expenses</div>
                    <div class="value">
                        {{ $summary['show_expenses'] ? 'Rs. ' . number_format($summary['total_expenses'], 2) : '-' }}
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-blue">
                    <div class="label">Net balance</div>
                    <div class="value">
                        {{ $summary['show_expenses'] ? 'Rs. ' . number_format($summary['net'], 2) : '-' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- TABS --}}
        <ul class="nav report-tabs gap-2 mb-3">
            @foreach ($tabs as $key => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                        href="{{ route('reports.daily-collection.index', ['tab' => $key] + $query) }}">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($tab === 'summary')
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="report-card h-100">
                        <h6 class="mb-3"><i class="bi bi-arrow-down-circle text-success"></i> Income</h6>
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
                        </table>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="report-card h-100">
                        <h6 class="mb-3"><i class="bi bi-arrow-up-circle text-danger"></i> Expenses</h6>
                        @if ($summary['show_expenses'])
                            <table class="w-100 mini-table">
                                @foreach ($summary['expenses'] as $label => $amount)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end amount">Rs. {{ number_format($amount, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="fw-bold">
                                    <td>Total expenses</td>
                                    <td class="text-end amount">Rs. {{ number_format($summary['total_expenses'], 2) }}</td>
                                </tr>
                                <tr class="fw-bold text-primary">
                                    <td>Net balance</td>
                                    <td class="text-end amount">Rs. {{ number_format($summary['net'], 2) }}</td>
                                </tr>
                            </table>
                        @else
                            <p class="text-muted mb-0">Hidden while a collector or payment method filter is used.</p>
                        @endif
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="report-card h-100">
                        <h6 class="mb-3"><i class="bi bi-credit-card"></i> By payment method</h6>
                        <table class="w-100 mini-table">
                            @forelse ($summary['by_method'] as $row)
                                <tr>
                                    <td>{{ $row['label'] }} <small class="text-muted">({{ $row['count'] }})</small></td>
                                    <td class="text-end amount">Rs. {{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-muted">No payments</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="report-card h-100">
                        <h6 class="mb-3"><i class="bi bi-person-badge"></i> By collector</h6>
                        <table class="w-100 mini-table">
                            @forelse ($summary['by_collector'] as $row)
                                <tr>
                                    <td>{{ $row['name'] }} <small class="text-muted">({{ $row['count'] }})</small></td>
                                    <td class="text-end amount">Rs. {{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td class="text-muted">No payments</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="report-card h-100">
                        <h6 class="mb-3"><i class="bi bi-pie-chart"></i> Class fee split</h6>
                        <table class="w-100 mini-table">
                            @foreach ($summary['split'] as $label => $amount)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end amount">Rs. {{ number_format($amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="report-card">
                @if ($tab === 'expenses' && !$summary['show_expenses'])
                    <p class="text-muted mb-0">Expenses are hidden while a collector or payment method filter is used.</p>
                @else
                    @include('reports.partials.table', ['table' => $table, 'rows' => $rows])
                @endif
            </div>
        @endif

    </div>
@endsection
