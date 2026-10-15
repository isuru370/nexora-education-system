@extends('layouts.app')

@section('title', 'Teacher Collection Report')
@section('page-title', 'Teacher Collection Report')

@section('content')
    @php
        $query = array_filter($filter->toQuery() + ['teacher_id' => $teacherId]);
        $money = fn ($value) => number_format((float) $value, 2);

        $today = now()->toDateString();
        $lastMonth = now()->startOfMonth()->subMonth();
        $quickRanges = [
            'Today' => ['from' => $today, 'to' => $today],
            'This month' => ['from' => now()->startOfMonth()->toDateString(), 'to' => $today],
            'Last month' => ['from' => $lastMonth->toDateString(), 'to' => $lastMonth->copy()->endOfMonth()->toDateString()],
        ];
        $currentRange = ['from' => $filter->from->toDateString(), 'to' => $filter->to->toDateString()];
    @endphp

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Teacher Collection</h1>
                    <p class="hero-subtitle">
                        Class fees collected for a teacher's classes • {{ $filter->dateLabel() }}
                        @if ($report)
                            • {{ $report['teacher']->custom_id }} - {{ $report['teacher']->full_name }}
                        @endif
                    </p>
                </div>
                @if ($report)
                    <div class="hero-right">
                        <div class="hero-actions">
                            <a href="{{ route('reports.teacher-collection.export', ['format' => 'excel'] + $query) }}" class="nx-btn nx-btn-light">
                                <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                            </a>
                            <a href="{{ route('reports.teacher-collection.export', ['format' => 'pdf'] + $query) }}" class="nx-btn nx-btn-light">
                                <i class="bi bi-filetype-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                @endif
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
                    <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Teacher &amp; Date Range</h5>
                    <p class="nx-card-subtitle">Payments are matched to the teacher who taught the class at payment time (max one year)</p>
                </div>
            </div>
            <div class="p-3">
                <form method="GET" action="{{ route('reports.teacher-collection.index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Teacher</label>
                        <select name="teacher_id" class="form-select nx-input" required>
                            <option value="">-- Select teacher --</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ (int) $teacherId === (int) $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->custom_id }} - {{ $teacher->full_name ?: $teacher->initials }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="nx-label">From</label>
                        <input type="date" name="from" class="form-control nx-input" value="{{ $currentRange['from'] }}" max="{{ $today }}">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="nx-label">To</label>
                        <input type="date" name="to" class="form-control nx-input" value="{{ $currentRange['to'] }}" max="{{ $today }}">
                    </div>

                    @if ($branches->count() > 1)
                        <div class="col-lg-3 col-md-6">
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

                    <div class="col-lg-2 col-md-6">
                        <button type="submit" class="nx-btn nx-btn-primary w-100"><i class="bi bi-funnel"></i> Show</button>
                    </div>
                </form>

                @if ($teacherId)
                    <div class="d-flex gap-2 flex-wrap mt-3">
                        @foreach ($quickRanges as $text => $range)
                            <a href="{{ route('reports.teacher-collection.index', array_filter($range + ['teacher_id' => $teacherId, 'branch_id' => $filter->branchId])) }}"
                                class="nx-btn {{ $range === $currentRange ? 'nx-btn-primary' : 'nx-btn-light' }}">{{ $text }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if (!$report)
            <div class="nx-card mb-4">
                <div class="empty-table">
                    <i class="bi bi-person-badge fs-2 d-block mb-2"></i>
                    <strong class="d-block">No teacher selected</strong>
                    <span class="cell-sub">Select a teacher and a date range to see the collection</span>
                </div>
            </div>
        @else
            @php
                $summary = $report['summary'];
                $classes = $report['classes'];
                $rows = $report['rows'];
                $sharePercent = $summary['amount'] > 0 ? round($summary['teacher_amount'] / $summary['amount'] * 100, 1) : 0;
            @endphp

            {{-- STATS --}}
            <div class="stats-grid mb-4">
                <div class="nx-card stat-card">
                    <div class="stat-icon-wrapper icon-blue"><i class="bi bi-person-badge"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">Teacher</span>
                        <h2 class="stat-number teacher-name">{{ $report['teacher']->full_name }}</h2>
                        <span class="stat-change neutral">{{ $report['teacher']->custom_id }}</span>
                    </div>
                </div>
                <div class="nx-card stat-card">
                    <div class="stat-icon-wrapper icon-green"><i class="bi bi-cash-stack"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">Collected</span>
                        <h2 class="stat-number">Rs. {{ $money($summary['amount']) }}</h2>
                        <span class="stat-change up">{{ number_format($summary['payments']) }} <span class="stat-change-note">payments</span></span>
                    </div>
                </div>
                <div class="nx-card stat-card">
                    <div class="stat-icon-wrapper icon-violet"><i class="bi bi-wallet2"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">Teacher Share</span>
                        <h2 class="stat-number">Rs. {{ $money($summary['teacher_amount']) }}</h2>
                        <span class="stat-change neutral">{{ $sharePercent }}% <span class="stat-change-note">of collected</span></span>
                    </div>
                </div>
                <div class="nx-card stat-card">
                    <div class="stat-icon-wrapper icon-amber"><i class="bi bi-people"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">Students / Classes</span>
                        <h2 class="stat-number">{{ number_format($summary['students']) }} / {{ number_format($summary['classes']) }}</h2>
                        <span class="stat-change neutral">paid <span class="stat-change-note">in this period</span></span>
                    </div>
                </div>
            </div>

            {{-- BY CLASS --}}
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-easel text-primary"></i> By Class &amp; Category</h5>
                        <p class="nx-card-subtitle">Share = class collection as a percentage of the teacher's total</p>
                    </div>
                </div>
                @if ($classes->isEmpty())
                    <div class="empty-table">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        <strong class="d-block">No payments</strong>
                        <span class="cell-sub">No class fees were collected for this teacher in this period</span>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Category</th>
                                    <th class="text-end">Students</th>
                                    <th class="text-end">Payments</th>
                                    <th class="text-end">Collected</th>
                                    <th style="min-width: 140px;">Share</th>
                                    <th class="text-end">Teacher Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($classes as $class)
                                    @php $percent = $summary['amount'] > 0 ? round($class['amount'] / $summary['amount'] * 100, 1) : 0; @endphp
                                    <tr>
                                        <td>
                                            <div class="cell-title">{{ $class['class_name'] }}</div>
                                            <small class="cell-sub">Grade {{ $class['grade_name'] ?? '-' }}</small>
                                        </td>
                                        <td><span class="nx-badge nx-badge-blue">{{ $class['category_name'] ?? '-' }}</span></td>
                                        <td class="text-end">{{ number_format($class['students']) }}</td>
                                        <td class="text-end">{{ number_format($class['payments']) }}</td>
                                        <td class="text-end amount">{{ $money($class['amount']) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="nx-progress flex-grow-1">
                                                    <div class="nx-progress-fill bg-blue" style="width: {{ min($percent, 100) }}%"></div>
                                                </div>
                                                <small class="cell-sub">{{ $percent }}%</small>
                                            </div>
                                        </td>
                                        <td class="text-end amount fw-bold text-success">{{ $money($class['teacher_amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="2">Total</td>
                                    <td class="text-end">{{ number_format($summary['students']) }}</td>
                                    <td class="text-end">{{ number_format($summary['payments']) }}</td>
                                    <td class="text-end amount">{{ $money($summary['amount']) }}</td>
                                    <td></td>
                                    <td class="text-end amount text-success">{{ $money($summary['teacher_amount']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            {{-- PAYMENTS --}}
            @if ($rows->isNotEmpty())
                <div class="nx-card mb-4">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-receipt text-primary"></i> Payments</h5>
                            <p class="nx-card-subtitle">{{ number_format($rows->count()) }} class fee payments, grouped by class</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th>Receipt</th>
                                    <th>Paid At</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Fee Month</th>
                                    <th>Method</th>
                                    <th>Collected By</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Teacher Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="cell-title text-nowrap">{{ $row['receipt_number'] ?? '-' }}</td>
                                        <td class="text-nowrap">{{ $row['paid_at'] ?? '-' }}</td>
                                        <td>
                                            <div class="cell-title text-nowrap">{{ $row['student_name'] ?? '-' }}</div>
                                            <small class="cell-sub">{{ $row['student_code'] ?? '-' }}{{ $row['guardian_mobile'] ? ' · ' . $row['guardian_mobile'] : '' }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $row['class_name'] }}</div>
                                            <small class="cell-sub">{{ $row['category_name'] ?? '-' }}{{ $row['fee_option'] ? ' · ' . $row['fee_option'] : '' }}</small>
                                        </td>
                                        <td class="text-nowrap">{{ $row['fee_month'] ?? '-' }}</td>
                                        <td><span class="nx-badge nx-badge-gray">{{ $row['payment_method'] }}</span></td>
                                        <td>{{ $row['collected_by'] ?? '-' }}</td>
                                        <td class="text-end amount">{{ $money($row['amount']) }}</td>
                                        <td class="text-end amount text-success">{{ $money($row['teacher_amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="7">Total ({{ number_format($rows->count()) }} payments)</td>
                                    <td class="text-end amount">{{ $money($summary['amount']) }}</td>
                                    <td class="text-end amount text-success">{{ $money($summary['teacher_amount']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        @endif

    </div>
@endsection

@push('styles')
<style>
    /* Teacher Collection — page-specific styles only */
    .dashboard-page .amount { font-weight: 600; white-space: nowrap; }
    .dashboard-page .teacher-name { font-size: 1.15rem; line-height: 1.3; }
    .dashboard-page .premium-table tfoot td {
        background: var(--nx-head-bg);
        border-top: 2px solid var(--nx-border);
        padding: .65rem .75rem;
        font-size: .82rem;
    }
</style>
@endpush