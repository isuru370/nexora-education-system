@extends('layouts.app')

@section('title', 'Institute Income')
@section('page-title', 'Institute Income')

@section('content')
    @php
        $query = array_filter([
            'from'      => $filter->from->toDateString(),
            'to'        => $filter->to->toDateString(),
            'branch_id' => $filter->branchId,
        ]);

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->startOfMonth()->subMonth();

        $quickRanges = [
            'This month' => ['from' => $thisMonth->toDateString(), 'to' => now()->toDateString()],
            'Last month' => ['from' => $lastMonth->toDateString(), 'to' => $lastMonth->copy()->endOfMonth()->toDateString()],
            'This year'  => ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()],
        ];
    @endphp

    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Institute Income</h1>
                    <p class="hero-subtitle">
                        <i class="bi bi-calendar-event"></i> {{ $filter->dateLabel() }}
                        • <i class="bi bi-building"></i> {{ $branchName }}
                    </p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">Rs. {{ number_format($summary['total_income'], 2) }}</span>
                            <span class="mini-stat-label">Institute Income</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">Rs. {{ number_format($summary['expenses'], 2) }}</span>
                            <span class="mini-stat-label">Expenses</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">Rs. {{ number_format($summary['net'], 2) }}</span>
                            <span class="mini-stat-label">Net Income</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('reports.institute-income.export', ['tab' => $tab, 'format' => 'excel'] + $query) }}"
                            class="nx-btn nx-btn-light">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                        </a>
                        <a href="{{ route('reports.institute-income.export', ['tab' => $tab, 'format' => 'pdf'] + $query) }}"
                            class="nx-btn nx-btn-light">
                            <i class="bi bi-filetype-pdf"></i> PDF
                        </a>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ALERTS --}}
        {{-- ===================================================== --}}
        @if (session('error'))
            <div class="nx-alert nx-alert-red mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="nx-alert nx-alert-red mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- FILTER --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Filters</h5>
                    <p class="nx-card-subtitle">Filter by date range or branch</p>
                </div>
            </div>

            <div class="p-3">
                <form method="GET" action="{{ route('reports.institute-income.index', ['tab' => $tab]) }}"
                    class="row g-3 align-items-end">

                    <div class="col-lg-3 col-md-4">
                        <label class="nx-label">From</label>
                        <input type="date" name="from" class="form-control nx-input"
                            value="{{ $filter->from->toDateString() }}" max="{{ now()->toDateString() }}">
                    </div>

                    <div class="col-lg-3 col-md-4">
                        <label class="nx-label">To</label>
                        <input type="date" name="to" class="form-control nx-input"
                            value="{{ $filter->to->toDateString() }}" max="{{ now()->toDateString() }}">
                    </div>

                    @if ($branches->count() > 1)
                        <div class="col-lg-3 col-md-4">
                            <label class="nx-label">Branch</label>
                            <select name="branch_id" class="form-select nx-input">
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
                        <button type="submit" class="nx-btn nx-btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>

                <div class="d-flex gap-2 flex-wrap mt-3">
                    @foreach ($quickRanges as $label => $range)
                        <a href="{{ route('reports.institute-income.index', ['tab' => $tab] + $range + array_filter(['branch_id' => $filter->branchId])) }}"
                            class="nx-btn {{ $range['from'] === $filter->from->toDateString() && $range['to'] === $filter->to->toDateString() ? 'nx-btn-primary' : 'nx-btn-light' }}"
                            style="min-height: 32px; padding: .3rem .75rem; font-size: .75rem;">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- STATS --}}
        {{-- ===================================================== --}}
        <div class="stats-grid stats-grid-3 mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-credit-card-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Class Fees Collected · {{ $summary['class_fee_count'] }}</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['class_fees'], 2) }}</h2>
                    <span class="stat-change up">Institute share <span class="stat-change-note">Rs. {{ number_format($summary['split']['Institute share'], 2) }}</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-mortarboard-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Admission Payments</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['income']['Admission fees'] ?? 0, 2) }}</h2>
                    <span class="stat-change up">Total <span class="stat-change-note">admission fees</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-plus-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Extra Income</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['income']['Extra income'] ?? 0, 2) }}</h2>
                    <span class="stat-change up">Total <span class="stat-change-note">received</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-person-badge-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Teachers Salary</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['teacher_salary'], 2) }}</h2>
                    <span class="stat-change neutral">Teacher share <span class="stat-change-note">of the fees</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-hand-index-thumb-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Teacher Advances</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['teacher_advances'], 2) }}</h2>
                    <span class="stat-change down">Advances <span class="stat-change-note">given</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-calculator-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Teacher Net Salary</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['teacher_net_salary'], 2) }}</h2>
                    <span class="stat-change neutral">Salary <span class="stat-change-note">minus advances</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Institute Income</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['total_income'], 2) }}</h2>
                    <span class="stat-change up">Total <span class="stat-change-note">earned</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-arrow-up-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Institute Expenses</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['expenses'], 2) }}</h2>
                    <span class="stat-change down">Total <span class="stat-change-note">spent</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-wallet2"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Net Income</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['net'], 2) }}</h2>
                    <span class="stat-change neutral">Income <span class="stat-change-note">minus expenses</span></span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- UNSPLIT WARNING --}}
        {{-- ===================================================== --}}
        @if ($summary['unsplit_count'] > 0)
            <div class="nx-alert nx-alert-amber mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>
                    <strong>{{ $summary['unsplit_count'] }}</strong> class fee payment(s)
                    (Rs. {{ number_format($summary['unsplit_amount'], 2) }})
                    have no fee split, so their institute share is not counted.
                </span>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- TABS --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4 p-3">
            <ul class="nav nx-tabs gap-2">
                @foreach ($tabs as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
                            href="{{ route('reports.institute-income.index', ['tab' => $key] + $query) }}">
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- ===================================================== --}}
        {{-- TAB CONTENT --}}
        {{-- ===================================================== --}}
        @if ($tab === 'summary')

            <div class="row g-3">

                {{-- INSTITUTE INCOME --}}
                <div class="col-lg-6">
                    <div class="nx-card h-100">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title">
                                    <i class="bi bi-arrow-down-circle text-success"></i> Institute Income
                                </h5>
                                <p class="nx-card-subtitle">Income breakdown</p>
                            </div>
                        </div>
                        <div class="p-3">
                            <table class="table premium-table mb-0">
                                @foreach ($summary['income'] as $label => $amount)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end cell-title">Rs. {{ number_format($amount, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="fw-bold" style="border-top: 2px solid var(--nx-border);">
                                    <td>Total income</td>
                                    <td class="text-end cell-title">Rs. {{ number_format($summary['total_income'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Institute expenses</td>
                                    <td class="text-end cell-title" style="color: var(--nx-red);">
                                        - Rs. {{ number_format($summary['expenses'], 2) }}
                                    </td>
                                </tr>
                                <tr class="fw-bold" style="border-top: 2px solid var(--nx-border);">
                                    <td class="text-primary">Net income</td>
                                    <td class="text-end cell-title text-primary">Rs. {{ number_format($summary['net'], 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- CLASS FEE SPLIT --}}
                <div class="col-lg-6">
                    <div class="nx-card h-100">
                        <div class="nx-card-header">
                            <div>
                                <h5 class="nx-card-title">
                                    <i class="bi bi-pie-chart text-primary"></i> {{ empty($summary['courses']) ? 'Class Fee Split' : 'Fee Split' }}
                                </h5>
                                <p class="nx-card-subtitle">{{ empty($summary['courses']) ? 'Where the class fees go' : 'Where the class and course fees go' }}</p>
                            </div>
                        </div>
                        <div class="p-3">
                            <table class="table premium-table mb-0">
                                <tr class="fw-bold">
                                    <td>Class fees collected</td>
                                    <td class="text-end cell-title">Rs. {{ number_format($summary['class_fees'], 2) }}</td>
                                </tr>
                                @foreach ($summary['split'] as $label => $amount)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-end cell-title">Rs. {{ number_format($amount, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="cell-sub">Hall fee (part of institute share)</td>
                                    <td class="text-end cell-sub">Rs. {{ number_format($summary['hall_fee'], 2) }}</td>
                                </tr>
                            </table>

                            @if (!empty($summary['courses']))
                                <table class="table premium-table mb-0 mt-3">
                                    <tr class="fw-bold">
                                        <td>Course fees collected</td>
                                        <td class="text-end cell-title">Rs. {{ number_format($summary['courses']['collected'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Course teacher share</td>
                                        <td class="text-end cell-title">Rs. {{ number_format($summary['courses']['teachers'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Course institute share</td>
                                        <td class="text-end cell-title">Rs. {{ number_format($summary['courses']['institute'], 2) }}</td>
                                    </tr>
                                </table>
                            @endif

                            <p class="cell-sub mt-3 mb-0">
                                Teacher and organizer shares are not institute money. Their salaries and advances are in the
                                <a href="{{ route('reports.daily-collection.index', ['tab' => 'expenses'] + $query) }}">Collection report</a>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        @else

            {{-- OTHER TABS --}}
            <div class="nx-card">
                @include('reports.partials.table', ['table' => $table, 'rows' => $rows])
            </div>

        @endif

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Institute Income — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    /* Tabs (nx style) */
    .dashboard-page .nx-tabs {
        border-bottom: 0;
        flex-wrap: wrap;
    }

    .dashboard-page .nx-tabs .nav-link {
        border-radius: 8px;
        padding: .45rem .95rem;
        font-size: .82rem;
        font-weight: 600;
        color: var(--nx-muted);
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        transition: all .15s ease;
    }

    .dashboard-page .nx-tabs .nav-link:hover {
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        border-color: var(--nx-blue);
    }

    .dashboard-page .nx-tabs .nav-link.active {
        background: var(--nx-blue);
        color: #fff;
        border-color: var(--nx-blue);
        box-shadow: 0 2px 8px rgba(29, 78, 216, .25);
    }

    /* Table row borders for summary lists */
    .dashboard-page .premium-table tbody tr {
        border-bottom: 1px solid var(--nx-border);
    }
    .dashboard-page .premium-table tbody tr:last-child {
        border-bottom: 0;
    }
</style>
@endpush