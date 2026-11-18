@extends('layouts.app')

@section('title', 'Monthly Collection Report')
@section('page-title', 'Monthly Collection Report')

@section('content')
    @php
        $summary = $report['summary'];
        $query = array_filter(['month' => $report['month'], 'branch_id' => $filter->branchId]);

        // ✅ (a) Daily breakdown data
        $dailyRows = $daily['rows'];
        $dailyTotals = $daily['totals'];
        $bestDay = $daily['best'];
    @endphp

    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Monthly Collection Report</h1>
                    <p class="hero-subtitle">
                        Expected vs collected class fees for <strong>{{ $report['month_label'] }}</strong>
                    </p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $summary['student_count'] }}</span>
                            <span class="mini-stat-label">Enrollments</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $summary['paid_students'] }}</span>
                            <span class="mini-stat-label">Paid</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $summary['collection_percentage'] }}%</span>
                            <span class="mini-stat-label">Collected</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('reports.monthly-collection.export', ['format' => 'excel'] + $query) }}"
                            class="nx-btn nx-btn-light">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                        </a>
                        <a href="{{ route('reports.monthly-collection.export', ['format' => 'pdf'] + $query) }}"
                            class="nx-btn nx-btn-light">
                            <i class="bi bi-filetype-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- VALIDATION ERRORS --}}
        {{-- ===================================================== --}}
        @if ($errors->any())
            <div class="nx-alert nx-alert-red tw:mb-6!">
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
        <div class="nx-card tw:mb-6!">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel tw:text-(color:--nx-blue)!"></i> Filters</h5>
                    <p class="nx-card-subtitle">Filter by month and branch</p>
                </div>
            </div>

            <div class="tw:p-4!">
                <form method="GET" action="{{ route('reports.monthly-collection.index') }}"
                    class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-end!">

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                        <label class="nx-label">Fee month</label>
                        <input type="month" name="month" class="ui-form-control nx-input"
                            value="{{ $report['month'] }}">
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

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                        <button type="submit" class="nx-btn nx-btn-primary tw:w-full!">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- STATS --}}
        {{-- ===================================================== --}}
        <div class="stats-grid tw:mb-6!">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-file-earmark-text"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Expected · {{ $summary['student_count'] }} enrollments</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['expected'], 2) }}</h2>
                    <span class="stat-change neutral">Total <span class="stat-change-note">expected fees</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Collected · {{ $summary['collection_percentage'] }}%</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['collected'], 2) }}</h2>
                    <span class="stat-change up">Collected <span class="stat-change-note">so far</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Due · {{ $summary['due_percentage'] }}%</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['due'], 2) }}</h2>
                    <span class="stat-change down">Pending <span class="stat-change-note">balance</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-people-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Students Paid</span>
                    <h2 class="stat-number">{{ $summary['paid_students'] }} / {{ $summary['student_count'] }}</h2>
                    <span class="stat-change neutral">Paid <span class="stat-change-note">students</span></span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- COLLECTION PROGRESS --}}
        {{-- ===================================================== --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-graph-up-arrow tw:text-(color:--nx-blue)!"></i> Collection Progress</h5>
                    <p class="nx-card-subtitle">Percentage of expected fees collected</p>
                </div>
                <span class="nx-badge nx-badge-green">{{ $summary['collection_percentage'] }}%</span>
            </div>

            <div class="tw:p-4!">
                <div class="nx-progress tw:mb-4!">
                    <div class="nx-progress-fill bg-green" style="width: {{ min($summary['collection_percentage'], 100) }}%"></div>
                </div>

                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:text-center!">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <span class="cell-sub">Teacher share</span>
                        <div class="cell-title">Rs. {{ number_format($summary['teacher_amount'], 2) }}</div>
                    </div>
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <span class="cell-sub">Organizer share</span>
                        <div class="cell-title">Rs. {{ number_format($summary['organizer_amount'], 2) }}</div>
                    </div>
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <span class="cell-sub">Institute share</span>
                        <div class="cell-title">Rs. {{ number_format($summary['institution_amount'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- CLASS & CATEGORY BREAKDOWN --}}
        {{-- ===================================================== --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-list-ul tw:text-(color:--nx-blue)!"></i> Class &amp; Category Breakdown</h5>
                    <p class="nx-card-subtitle">Expected vs collected per class and category</p>
                </div>
            </div>
            <div>
                @include('reports.partials.table', ['table' => $table, 'rows' => $report['rows']])
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ✅ (b) DAY BY DAY --}}
        {{-- ===================================================== --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-calendar3 tw:text-(color:--nx-blue)!"></i> Day by day</h5>
                    <p class="nx-card-subtitle">
                        Money received and paid out on each day of {{ $report['month_label'] }}
                        (by date paid, any fee month). Click a date to open the Collection Report for that day.
                    </p>
                </div>
                <div class="header-buttons">
                    <a href="{{ route('reports.monthly-collection.export', ['format' => 'excel', 'table' => 'daily'] + $query) }}"
                        class="nx-btn nx-btn-success">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                    </a>
                    <a href="{{ route('reports.monthly-collection.export', ['format' => 'pdf', 'table' => 'daily'] + $query) }}"
                        class="nx-btn nx-btn-danger">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </a>
                </div>
            </div>

            @if ($dailyRows->isEmpty())
                <div class="empty-table">
                    <i class="bi bi-calendar-x tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:block! tw:mb-2!"></i>
                    <strong class="tw:block!">This month has not started yet.</strong>
                </div>
            @else

                {{-- DAY-BY-DAY SUMMARY --}}
                <div class="tw:p-4! tw:pb-0!">
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                            <span class="cell-sub">Received</span>
                            <div class="cell-title" style="color: var(--nx-green);">
                                Rs. {{ number_format($dailyTotals['total_income'], 2) }}
                            </div>
                        </div>
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                            <span class="cell-sub">Paid out</span>
                            <div class="cell-title" style="color: var(--nx-red);">
                                Rs. {{ number_format($dailyTotals['total_expenses'], 2) }}
                            </div>
                        </div>
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                            <span class="cell-sub">Net</span>
                            <div class="cell-title {{ $dailyTotals['net'] < 0 ? 'tw:text-(color:--nx-red)!' : 'tw:text-(color:--nx-blue)!' }}">
                                Rs. {{ number_format($dailyTotals['net'], 2) }}
                            </div>
                        </div>
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                            <span class="cell-sub">Best day</span>
                            <div class="cell-title">
                                {{ $bestDay ? \Carbon\Carbon::parse($bestDay['date'])->format('d M') . ' · Rs. ' . number_format($bestDay['total_income'], 2) : '-' }}
                            </div>
                            <small class="cell-sub tw:block!">
                                avg Rs. {{ number_format($dailyTotals['average_income'], 2) }} / day with income
                            </small>
                        </div>
                    </div>
                </div>

                {{-- DAY-BY-DAY TABLE --}}
                <div class="ui-table-responsive">
                    <table class="ui-table premium-table tw:mb-0!">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="tw:text-right!">Payments</th>
                                <th class="tw:text-right!">Class Fees</th>
                                <th class="tw:text-right!">Admission</th>
                                <th class="tw:text-right!">Extra Income</th>
                                <th class="tw:text-right!">Income</th>
                                <th class="tw:text-right!">Expenses</th>
                                <th class="tw:text-right!">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dailyRows as $row)
                                <tr class="{{ $row['empty'] ? 'row-muted' : '' }}">
                                    <td class="tw:whitespace-nowrap!">
                                        <a href="{{ route('reports.daily-collection.index', array_filter(['from' => $row['date'], 'to' => $row['date'], 'branch_id' => $filter->branchId])) }}"
                                            class="tw:[text-decoration:none]! {{ $row['empty'] ? 'cell-sub' : 'cell-title' }}">
                                            {{ $row['date_label'] }}
                                        </a>
                                        <small class="cell-sub tw:ml-1!">{{ $row['day_name'] }}</small>
                                    </td>
                                    <td class="tw:text-right! cell-sub">{{ $row['payments'] ?: '-' }}</td>
                                    <td class="tw:text-right! cell-title">Rs. {{ number_format($row['class_fees'], 2) }}</td>
                                    <td class="tw:text-right! cell-title">Rs. {{ number_format($row['admission_fees'], 2) }}</td>
                                    <td class="tw:text-right! cell-title">Rs. {{ number_format($row['extra_income'], 2) }}</td>
                                    <td class="tw:text-right! cell-title" style="color: var(--nx-green);">
                                        Rs. {{ number_format($row['total_income'], 2) }}
                                    </td>
                                    <td class="tw:text-right! cell-title {{ $row['total_expenses'] > 0 ? 'tw:text-(color:--nx-red)!' : '' }}">
                                        Rs. {{ number_format($row['total_expenses'], 2) }}
                                    </td>
                                    <td class="tw:text-right! cell-title {{ $row['net'] < 0 ? 'tw:text-(color:--nx-red)!' : ($row['net'] > 0 ? 'tw:text-(color:--nx-green)!' : '') }}">
                                        Rs. {{ number_format($row['net'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="tw:font-bold!" style="border-top: 2px solid var(--nx-border);">
                                <td>Total ({{ $dailyRows->count() }} days)</td>
                                <td class="tw:text-right!">{{ number_format($dailyTotals['payments']) }}</td>
                                <td class="tw:text-right!">Rs. {{ number_format($dailyTotals['class_fees'], 2) }}</td>
                                <td class="tw:text-right!">Rs. {{ number_format($dailyTotals['admission_fees'], 2) }}</td>
                                <td class="tw:text-right!">Rs. {{ number_format($dailyTotals['extra_income'], 2) }}</td>
                                <td class="tw:text-right!" style="color: var(--nx-green);">
                                    Rs. {{ number_format($dailyTotals['total_income'], 2) }}
                                </td>
                                <td class="tw:text-right!" style="color: var(--nx-red);">
                                    Rs. {{ number_format($dailyTotals['total_expenses'], 2) }}
                                </td>
                                <td class="tw:text-right! {{ $dailyTotals['net'] < 0 ? 'tw:text-(color:--nx-red)!' : 'tw:text-(color:--nx-green)!' }}">
                                    Rs. {{ number_format($dailyTotals['net'], 2) }}
                                </td>
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
    /* ============================================================
       Monthly Collection Report — page-specific styles only.
       Shared nx-* theme comes from layouts/partials/nexora-theme.
       ============================================================ */

    /* Table row borders for summary lists */
    .dashboard-page .premium-table tbody tr {
        border-bottom: 1px solid var(--nx-border);
    }
    .dashboard-page .premium-table tbody tr:last-child {
        border-bottom: 0;
    }

    /* Footer totals row */
    .dashboard-page .premium-table tfoot td {
        padding: .75rem .85rem;
        background: #f4f6fa;
    }
</style>
@endpush