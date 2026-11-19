@extends('layouts.app')

@section('title', 'Annual Report ' . $year)
@section('page-title', 'Annual Report')

@section('content')
    @php
        $rows = $report['rows'];
        $totals = $report['totals'];
        $best = $report['best'];
        $query = array_filter(['year' => $year, 'branch_id' => $filter->branchId]);
        $previousYear = $year - 1;

        $money = fn ($value) => number_format((float) $value, 2);

        // "+12.5% vs 2025" style badge
        $growthBadge = function (?float $value, bool $higherIsGood = true) use ($previousYear) {
            if ($value === null) {
                return ['class' => 'neutral', 'text' => 'No data', 'note' => 'for ' . $previousYear];
            }

            $good = $higherIsGood ? $value >= 0 : $value <= 0;

            return [
                'class' => $good ? 'up' : 'down',
                'text' => ($value >= 0 ? '+' : '') . $value . '%',
                'note' => 'vs ' . $previousYear,
            ];
        };

        $incomeGrowth = $growthBadge($growth['income']);
        $expenseGrowth = $growthBadge($growth['expenses'], false);
        $netGrowth = $growthBadge($growth['net']);
        $maxIncome = max($rows->max('total_income'), 1);
    @endphp

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Annual Report {{ $year }}</h1>
                    <p class="hero-subtitle">
                        Month-by-month income, expenses and net • {{ $filter->dateLabel() }}
                        • {{ $filter->branchId ? optional($branches->firstWhere('id', $filter->branchId))->name : 'All branches' }}
                    </p>
                </div>
                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('reports.annual.export', ['format' => 'excel'] + $query) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                        </a>
                        <a href="{{ route('reports.annual.export', ['format' => 'pdf'] + $query) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-filetype-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('error'))
            <div class="nx-alert nx-alert-red tw:mb-6!"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="nx-alert nx-alert-red tw:mb-6!">
                <div>
                    @foreach ($errors->all() as $error)
                        <div><i class="bi bi-exclamation-circle-fill tw:mr-1!"></i>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- FILTER --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel tw:text-(color:--nx-blue)!"></i> Year</h5>
                    <p class="nx-card-subtitle">The current year is shown up to today and compared with the same period last year</p>
                </div>
            </div>
            <div class="tw:p-4!">
                <form method="GET" action="{{ route('reports.annual.index') }}" class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-end!">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                        <label class="nx-label">Year</label>
                        <select name="year" class="ui-form-select nx-input">
                            @foreach ($years as $option)
                                <option value="{{ $option }}" {{ (int) $option === (int) $year ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($branches->count() > 1)
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                            <label class="nx-label">Branch</label>
                            <select name="branch_id" class="ui-form-select nx-input">
                                <option value="">All branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (int) $filter->branchId === (int) $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%] tw:flex! tw:gap-2!">
                        <button type="submit" class="nx-btn nx-btn-primary tw:grow!"><i class="bi bi-funnel"></i> Show</button>
                        <a href="{{ route('reports.annual.index') }}" class="nx-btn nx-btn-light" title="This year"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        {{-- STATS --}}
        <div class="stats-grid tw:mb-6!">
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-arrow-down-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Income</span>
                    <h2 class="stat-number">Rs. {{ $money($totals['total_income']) }}</h2>
                    <span class="stat-change {{ $incomeGrowth['class'] }}">{{ $incomeGrowth['text'] }} <span class="stat-change-note">{{ $incomeGrowth['note'] }}</span></span>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-arrow-up-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Expenses</span>
                    <h2 class="stat-number">Rs. {{ $money($totals['total_expenses']) }}</h2>
                    <span class="stat-change {{ $expenseGrowth['class'] }}">{{ $expenseGrowth['text'] }} <span class="stat-change-note">{{ $expenseGrowth['note'] }}</span></span>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-wallet2"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Net</span>
                    <h2 class="stat-number {{ $totals['net'] < 0 ? 'tw:text-(color:--nx-red)!' : '' }}">Rs. {{ $money($totals['net']) }}</h2>
                    <span class="stat-change {{ $netGrowth['class'] }}">{{ $netGrowth['text'] }} <span class="stat-change-note">{{ $netGrowth['note'] }}</span></span>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-trophy"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Best Month</span>
                    <h2 class="stat-number">{{ $best ? $best['month_name'] : '-' }}</h2>
                    <span class="stat-change neutral">
                        {{ $best ? 'Rs. ' . $money($best['total_income']) : 'No income yet' }}
                        <span class="stat-change-note">· avg Rs. {{ $money($totals['average_income']) }}/month</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- CHART --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-bar-chart-fill tw:text-(color:--nx-blue)!"></i> Income vs Expenses</h5>
                    <p class="nx-card-subtitle">Bars show income and expenses for each month, the line shows net</p>
                </div>
            </div>
            <div class="annual-chart-box">
                <canvas id="annualChart"></canvas>
            </div>
        </div>

        {{-- MONTHLY SUMMARY --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-calendar3 tw:text-(color:--nx-blue)!"></i> Monthly Summary</h5>
                    <p class="nx-card-subtitle">
                        {{ number_format($totals['payments']) }} class fee payments from {{ number_format($totals['students']) }} students this year
                    </p>
                </div>
            </div>
            <div class="ui-table-responsive">
                <table class="ui-table premium-table tw:mb-0!">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="tw:text-right!">Class Fees</th>
                            <th class="tw:text-right!">Admission Fees</th>
                            <th class="tw:text-right!">Extra Income</th>
                            @if (feature('courses'))<th class="tw:text-right!">Course Fees</th>@endif
                            <th class="tw:text-right!">Total Income</th>
                            <th style="min-width: 130px;">Income</th>
                            <th class="tw:text-right!">Expenses</th>
                            <th class="tw:text-right!">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="{{ $row['future'] ? 'row-muted' : '' }}">
                                <td>
                                    <div class="cell-title">{{ $row['month_name'] }}</div>
                                    <small class="cell-sub">
                                        @if ($row['future'])
                                            Not yet
                                        @else
                                            {{ number_format($row['payments']) }} {{ \Illuminate\Support\Str::plural('payment', $row['payments']) }} · {{ number_format($row['students']) }} {{ \Illuminate\Support\Str::plural('student', $row['students']) }}
                                        @endif
                                    </small>
                                </td>
                                <td class="tw:text-right! amount">{{ $money($row['class_fees']) }}</td>
                                <td class="tw:text-right! amount">{{ $money($row['admission_fees']) }}</td>
                                <td class="tw:text-right! amount">{{ $money($row['extra_income']) }}</td>
                                @if (feature('courses'))<td class="tw:text-right! amount">{{ $money($row['course_fees']) }}</td>@endif
                                <td class="tw:text-right! amount tw:font-bold!">{{ $money($row['total_income']) }}</td>
                                <td>
                                    <div class="nx-progress">
                                        <div class="nx-progress-fill {{ $best && $row['month'] === $best['month'] ? 'bg-green' : 'bg-blue' }}"
                                            style="width: {{ round($row['total_income'] / $maxIncome * 100, 1) }}%"></div>
                                    </div>
                                </td>
                                <td class="tw:text-right! amount {{ $row['total_expenses'] > 0 ? 'tw:text-(color:--nx-red)!' : '' }}">{{ $money($row['total_expenses']) }}</td>
                                <td class="tw:text-right! amount tw:font-bold! {{ $row['net'] < 0 ? 'tw:text-(color:--nx-red)!' : ($row['net'] > 0 ? 'tw:text-(color:--nx-green)!' : '') }}">{{ $money($row['net']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="tw:font-bold!">
                            <td>Total {{ $year }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['class_fees']) }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['admission_fees']) }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['extra_income']) }}</td>
                            @if (feature('courses'))<td class="tw:text-right! amount">{{ $money($totals['course_fees']) }}</td>@endif
                            <td class="tw:text-right! amount">{{ $money($totals['total_income']) }}</td>
                            <td></td>
                            <td class="tw:text-right! amount tw:text-(color:--nx-red)!">{{ $money($totals['total_expenses']) }}</td>
                            <td class="tw:text-right! amount {{ $totals['net'] < 0 ? 'tw:text-(color:--nx-red)!' : 'tw:text-(color:--nx-green)!' }}">{{ $money($totals['net']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- EXPENSES BY MONTH --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-arrow-up-circle tw:text-(color:--nx-red)!"></i> Expenses by Month</h5>
                    <p class="nx-card-subtitle">Paid salaries, advances and institute expenses</p>
                </div>
            </div>
            <div class="ui-table-responsive">
                <table class="ui-table premium-table tw:mb-0!">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="tw:text-right!">Teacher Salaries</th>
                            <th class="tw:text-right!">Teacher Advances</th>
                            <th class="tw:text-right!">Organizer</th>
                            <th class="tw:text-right!">Institute</th>
                            @if (feature('courses'))<th class="tw:text-right!">Course Payouts</th>@endif
                            <th class="tw:text-right!">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="{{ $row['future'] ? 'row-muted' : '' }}">
                                <td class="cell-title">{{ $row['month_name'] }}</td>
                                <td class="tw:text-right! amount">{{ $money($row['teacher_salaries']) }}</td>
                                <td class="tw:text-right! amount">{{ $money($row['teacher_advances']) }}</td>
                                <td class="tw:text-right! amount">{{ $money($row['organizer_payments']) }}</td>
                                <td class="tw:text-right! amount">{{ $money($row['institute_expenses']) }}</td>
                                @if (feature('courses'))<td class="tw:text-right! amount">{{ $money($row['course_payouts']) }}</td>@endif
                                <td class="tw:text-right! amount tw:font-bold!">{{ $money($row['total_expenses']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="tw:font-bold!">
                            <td>Total {{ $year }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['teacher_salaries']) }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['teacher_advances']) }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['organizer_payments']) }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['institute_expenses']) }}</td>
                            @if (feature('courses'))<td class="tw:text-right! amount">{{ $money($totals['course_payouts']) }}</td>@endif
                            <td class="tw:text-right! amount">{{ $money($totals['total_expenses']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- CLASS FEE SPLIT BY MONTH --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-pie-chart tw:text-(color:--nx-blue)!"></i> Class Fee Split by Month</h5>
                    <p class="nx-card-subtitle">Teacher, organizer and institute share of class fees</p>
                </div>
            </div>
            <div class="ui-table-responsive">
                <table class="ui-table premium-table tw:mb-0!">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="tw:text-right!">Class Fees</th>
                            <th class="tw:text-right!">Teacher</th>
                            <th class="tw:text-right!">Organizer</th>
                            <th class="tw:text-right!">Institute</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="{{ $row['future'] ? 'row-muted' : '' }}">
                                <td class="cell-title">{{ $row['month_name'] }}</td>
                                <td class="tw:text-right! amount">{{ $money($row['class_fees']) }}</td>
                                <td class="tw:text-right! amount">{{ $money($row['teacher_share']) }}</td>
                                <td class="tw:text-right! amount">{{ $money($row['organizer_share']) }}</td>
                                <td class="tw:text-right! amount tw:font-bold!">{{ $money($row['institute_share']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="tw:font-bold!">
                            <td>Total {{ $year }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['class_fees']) }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['teacher_share']) }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['organizer_share']) }}</td>
                            <td class="tw:text-right! amount">{{ $money($totals['institute_share']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
@endsection

@push('styles')
<style>
    /* Annual Report — page-specific styles only */
    .dashboard-page .amount { font-weight: 600; white-space: nowrap; }
    .dashboard-page .premium-table tfoot td {
        background: var(--nx-head-bg);
        border-top: 2px solid var(--nx-border);
        padding: .65rem .75rem;
        font-size: .82rem;
    }
    .dashboard-page .annual-chart-box {
        position: relative;
        height: 320px;
        padding: 1rem .75rem .5rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('annualChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const data = @json($chart);
        const rs = (value) => 'Rs. ' + Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        new Chart(canvas, {
            data: {
                labels: data.labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Income',
                        data: data.income,
                        backgroundColor: 'rgba(16, 185, 129, .75)',
                        borderRadius: 6,
                        order: 2,
                    },
                    {
                        type: 'bar',
                        label: 'Expenses',
                        data: data.expenses,
                        backgroundColor: 'rgba(239, 68, 68, .7)',
                        borderRadius: 6,
                        order: 3,
                    },
                    {
                        type: 'line',
                        label: 'Net',
                        data: data.net,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgb(59, 130, 246)',
                        borderWidth: 2,
                        tension: .3,
                        pointRadius: 3,
                        spanGaps: false,
                        order: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (context) => context.dataset.label + ': ' + rs(context.parsed.y),
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: (value) => 'Rs. ' + Number(value).toLocaleString('en-LK') },
                    },
                    x: { grid: { display: false } },
                },
            },
        });
    });
</script>
@endpush