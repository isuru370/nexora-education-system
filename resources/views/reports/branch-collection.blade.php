@extends('layouts.app')

@section('title', 'Branch-wise Collection')
@section('page-title', 'Branch-wise Collection')

@section('content')
    @php
        $rows = $report['rows'];
        $totals = $report['totals'];
        $methods = $report['methods'];
        $methodNames = $report['method_names'];
        $query = ['from' => $filter->from->toDateString(), 'to' => $filter->to->toDateString()];

        $today = now()->toDateString();
        $lastMonth = now()->startOfMonth()->subMonth();
        $quickRanges = [
            'Today' => ['from' => $today, 'to' => $today],
            'This month' => ['from' => now()->startOfMonth()->toDateString(), 'to' => $today],
            'Last month' => ['from' => $lastMonth->toDateString(), 'to' => $lastMonth->copy()->endOfMonth()->toDateString()],
            'This year' => ['from' => now()->startOfYear()->toDateString(), 'to' => $today],
        ];

        $money = fn ($value) => number_format((float) $value, 2);
    @endphp

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Branch-wise Collection</h1>
                    <p class="hero-subtitle">Income, expenses and net for every branch • {{ $filter->dateLabel() }}</p>
                </div>
                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('reports.branch-collection.export', ['format' => 'excel'] + $query) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                        </a>
                        <a href="{{ route('reports.branch-collection.export', ['format' => 'pdf'] + $query) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-filetype-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('error'))
            <div class="nx-alert nx-alert-red mb-4"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="nx-alert nx-alert-red mb-4">
                <div>
                    @foreach ($errors->all() as $error)
                        <div><i class="bi bi-exclamation-circle-fill me-1"></i>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- FILTER --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Date Range</h5>
                    <p class="nx-card-subtitle">All branches you can access are compared side by side (max one year)</p>
                </div>
            </div>
            <div class="p-3">
                <form method="GET" action="{{ route('reports.branch-collection.index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-4">
                        <label class="nx-label">From</label>
                        <input type="date" name="from" class="form-control nx-input" value="{{ $filter->from->toDateString() }}" max="{{ $today }}">
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <label class="nx-label">To</label>
                        <input type="date" name="to" class="form-control nx-input" value="{{ $filter->to->toDateString() }}" max="{{ $today }}">
                    </div>
                    <div class="col-lg-2 col-md-4 d-flex gap-2">
                        <button type="submit" class="nx-btn nx-btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Show</button>
                        <a href="{{ route('reports.branch-collection.index') }}" class="nx-btn nx-btn-light" title="This month"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                    <div class="col-lg-4 d-flex gap-2 flex-wrap justify-content-lg-end">
                        @foreach ($quickRanges as $text => $range)
                            @php $active = $range['from'] === $query['from'] && $range['to'] === $query['to']; @endphp
                            <a href="{{ route('reports.branch-collection.index', $range) }}" class="nx-btn {{ $active ? 'nx-btn-primary' : 'nx-btn-light' }}">{{ $text }}</a>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>

        {{-- STATS --}}
        <div class="stats-grid mb-4">
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-arrow-down-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Income</span>
                    <h2 class="stat-number">Rs. {{ $money($totals['total_income']) }}</h2>
                    <span class="stat-change up">{{ $rows->where('key', '!=', 'shared')->count() }} <span class="stat-change-note">branches</span></span>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-arrow-up-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Expenses</span>
                    <h2 class="stat-number">Rs. {{ $money($totals['total_expenses']) }}</h2>
                    <span class="stat-change down">Salaries, advances <span class="stat-change-note">and expenses</span></span>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-wallet2"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Net</span>
                    <h2 class="stat-number {{ $totals['net'] < 0 ? 'text-danger' : '' }}">Rs. {{ $money($totals['net']) }}</h2>
                    <span class="stat-change {{ $totals['net'] < 0 ? 'down' : 'up' }}">Income − expenses</span>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Class Fees · {{ number_format($totals['payments']) }} payments</span>
                    <h2 class="stat-number">Rs. {{ $money($totals['class_fees']) }}</h2>
                    <span class="stat-change neutral">{{ number_format($totals['students']) }} <span class="stat-change-note">students paid</span></span>
                </div>
            </div>
        </div>

        {{-- INCOME & NET BY BRANCH --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-building text-primary"></i> Income &amp; Net by Branch</h5>
                    <p class="nx-card-subtitle">Share = branch income as a percentage of total income</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th class="text-end">Class Fees</th>
                            <th class="text-end">Admission Fees</th>
                            <th class="text-end">Extra Income</th>
                            <th class="text-end">Total Income</th>
                            <th style="min-width: 140px;">Share</th>
                            <th class="text-end">Expenses</th>
                            <th class="text-end">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="{{ $row['key'] === 'shared' ? 'row-muted' : '' }}">
                                <td>
                                    <div class="cell-title">{{ $row['branch'] }}</div>
                                    <small class="cell-sub">{{ number_format($row['payments']) }} payments · {{ number_format($row['students']) }} students</small>
                                </td>
                                <td class="text-end amount">{{ $money($row['class_fees']) }}</td>
                                <td class="text-end amount">{{ $money($row['admission_fees']) }}</td>
                                <td class="text-end amount">{{ $money($row['extra_income']) }}</td>
                                <td class="text-end amount fw-bold">{{ $money($row['total_income']) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="nx-progress flex-grow-1">
                                            <div class="nx-progress-fill bg-blue" style="width: {{ min($row['income_percent'], 100) }}%"></div>
                                        </div>
                                        <small class="cell-sub">{{ $row['income_percent'] }}%</small>
                                    </div>
                                </td>
                                <td class="text-end amount text-danger">{{ $money($row['total_expenses']) }}</td>
                                <td class="text-end amount fw-bold {{ $row['net'] < 0 ? 'text-danger' : 'text-success' }}">{{ $money($row['net']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-end amount">{{ $money($totals['class_fees']) }}</td>
                            <td class="text-end amount">{{ $money($totals['admission_fees']) }}</td>
                            <td class="text-end amount">{{ $money($totals['extra_income']) }}</td>
                            <td class="text-end amount">{{ $money($totals['total_income']) }}</td>
                            <td></td>
                            <td class="text-end amount text-danger">{{ $money($totals['total_expenses']) }}</td>
                            <td class="text-end amount {{ $totals['net'] < 0 ? 'text-danger' : 'text-success' }}">{{ $money($totals['net']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- EXPENSES BY BRANCH --}}
            <div class="col-12">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-arrow-up-circle text-danger"></i> Expenses by Branch</h5>
                            <p class="nx-card-subtitle">Paid salaries, advances and expenses in this period</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th class="text-end">Teacher Salaries</th>
                                    <th class="text-end">Teacher Advances</th>
                                    <th class="text-end">Organizer</th>
                                    <th class="text-end">Institute</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr class="{{ $row['key'] === 'shared' ? 'row-muted' : '' }}">
                                        <td class="cell-title">{{ $row['branch'] }}</td>
                                        <td class="text-end amount">{{ $money($row['teacher_salaries']) }}</td>
                                        <td class="text-end amount">{{ $money($row['teacher_advances']) }}</td>
                                        <td class="text-end amount">{{ $money($row['organizer_payments']) }}</td>
                                        <td class="text-end amount">{{ $money($row['institute_expenses']) }}</td>
                                        <td class="text-end amount fw-bold">{{ $money($row['total_expenses']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-end amount">{{ $money($totals['teacher_salaries']) }}</td>
                                    <td class="text-end amount">{{ $money($totals['teacher_advances']) }}</td>
                                    <td class="text-end amount">{{ $money($totals['organizer_payments']) }}</td>
                                    <td class="text-end amount">{{ $money($totals['institute_expenses']) }}</td>
                                    <td class="text-end amount">{{ $money($totals['total_expenses']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- CLASS FEE SPLIT BY BRANCH --}}
            <div class="col-12">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-pie-chart text-primary"></i> Class Fee Split by Branch</h5>
                            <p class="nx-card-subtitle">Teacher, organizer and institute share of class fees</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th class="text-end">Class Fees</th>
                                    <th class="text-end">Teacher</th>
                                    <th class="text-end">Organizer</th>
                                    <th class="text-end">Institute</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows->where('key', '!=', 'shared') as $row)
                                    <tr>
                                        <td class="cell-title">{{ $row['branch'] }}</td>
                                        <td class="text-end amount">{{ $money($row['class_fees']) }}</td>
                                        <td class="text-end amount">{{ $money($row['teacher_share']) }}</td>
                                        <td class="text-end amount">{{ $money($row['organizer_share']) }}</td>
                                        <td class="text-end amount fw-bold">{{ $money($row['institute_share']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td class="text-end amount">{{ $money($totals['class_fees']) }}</td>
                                    <td class="text-end amount">{{ $money($totals['teacher_share']) }}</td>
                                    <td class="text-end amount">{{ $money($totals['organizer_share']) }}</td>
                                    <td class="text-end amount">{{ $money($totals['institute_share']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- PAYMENT METHODS BY BRANCH --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-credit-card text-primary"></i> Fee Collections by Payment Method</h5>
                    <p class="nx-card-subtitle">Class fees + admission fees per branch (use the Cash column to check cash in hand)</p>
                </div>
            </div>
            @if ($methods->isEmpty())
                <div class="empty-table">
                    <i class="bi bi-credit-card fs-2 d-block mb-2"></i>
                    <strong class="d-block">No fee collections</strong>
                    <span class="cell-sub">No class or admission fees were collected in this period</span>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                @foreach ($methodNames as $method)
                                    <th class="text-end">{{ $label($method) }}</th>
                                @endforeach
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($methods as $row)
                                <tr>
                                    <td class="cell-title">{{ $row['branch'] }}</td>
                                    @foreach ($methodNames as $method)
                                        <td class="text-end amount">{{ $money($row[$method]) }}</td>
                                    @endforeach
                                    <td class="text-end amount fw-bold">{{ $money($row['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total</td>
                                @foreach ($methodNames as $method)
                                    <td class="text-end amount">{{ $money($methods->sum($method)) }}</td>
                                @endforeach
                                <td class="text-end amount">{{ $money($methods->sum('total')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

    </div>
@endsection

@push('styles')
<style>
    /* Branch-wise Collection — page-specific styles only */
    .dashboard-page .amount { font-weight: 600; white-space: nowrap; }
    .dashboard-page .premium-table tfoot td {
        background: var(--nx-head-bg);
        border-top: 2px solid var(--nx-border);
        padding: .65rem .75rem;
        font-size: .82rem;
    }
</style>
@endpush