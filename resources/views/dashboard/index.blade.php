@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

    $stats = $stats ?? [];
    $todayClassSummary = $todayClassSummary ?? [];
    $unpaid = $unpaid ?? [];
    $todayClasses = $todayClasses ?? collect();
    $paymentMethods = $paymentMethods ?? collect();
    $branchSummary = $branchSummary ?? collect();
    $recentPayments = $recentPayments ?? collect();
    $dailyIncome = $dailyIncome ?? ['labels' => [], 'values' => []];

    $money = fn ($value) => 'Rs. ' . number_format((float) $value, 2);

    // [css class, text] for a % change badge
    $change = function ($value) {
        if ($value === null) {
            return ['neutral', '—'];
        }

        return $value >= 0
            ? ['up', '+' . number_format($value, 1) . '%']
            : ['down', number_format($value, 1) . '%'];
    };

    [$todayChangeClass, $todayChangeText] = $change($stats['todayChangePercent'] ?? null);
    [$monthChangeClass, $monthChangeText] = $change($stats['monthlyChangePercent'] ?? null);

    $expected = (int) ($todayClassSummary['expected_students'] ?? 0);
    $present = (int) ($todayClassSummary['present_students'] ?? 0);
    $todayAttendancePercent = $expected > 0 ? (int) round(($present / $expected) * 100) : 0;

    $methodsTotal = (float) $paymentMethods->sum('total');

    $statusBadge = [
        'scheduled' => 'nx-badge-blue',
        'ongoing' => 'nx-badge-amber',
        'completed' => 'nx-badge-green',
        'cancelled' => 'nx-badge-red',
    ];
@endphp

@section('content')
    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">{{ $greeting }}</span>
                    <h1 class="hero-title">Welcome back, {{ auth()->user()->name ?? 'Administrator' }}</h1>
                    <p class="hero-subtitle">Here is what is happening at the institute today.</p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($stats['activeStudents'] ?? $studentsCount ?? 0) }}</span>
                            <span class="mini-stat-label">Active Students</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $todayClassSummary['total'] ?? 0 }}</span>
                            <span class="mini-stat-label">Classes Today</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($stats['todayAttendance'] ?? 0) }}</span>
                            <span class="mini-stat-label">Attendance Today</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($stats['todayPaymentsCount'] ?? 0) }}</span>
                            <span class="mini-stat-label">Payments Today</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-date-time">
                        <div class="date-box">
                            <i class="bi bi-calendar-event"></i>
                            <span>{{ now()->format('D, d M Y') }}</span>
                        </div>
                        <div class="time-box">
                            <i class="bi bi-clock"></i>
                            <span id="liveTime">{{ now()->format('h:i A') }}</span>
                        </div>
                    </div>
                    <small class="hero-updated">Updated {{ $generatedAt ?? now()->format('h:i A') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ALERTS --}}
        {{-- ===================================================== --}}
        @if (($showStudentCardWarning ?? false) || ($expiringStudentsCount ?? 0) > 0 || ($incompleteRegistrationCount ?? 0) > 0)
            <div class="alerts-row mb-4">
                @if ($showStudentCardWarning ?? false)
                    <a href="{{ \App\Support\CardAccess::canView() ? route('student-cards.index') : '#' }}" class="nx-alert nx-alert-amber">
                        <i class="bi bi-person-vcard"></i>
                        <span>Student cards are running low: <strong>{{ $availableStudentCardCount ?? 0 }}</strong> left</span>
                        <i class="bi bi-chevron-right ms-auto"></i>
                    </a>
                @endif

                @if (($expiringStudentsCount ?? 0) > 0)
                    <a href="{{ \App\Support\CardAccess::canView() && \App\Support\CardMode::temporary() ? route('temporary_id_cards.index') : route('students.index') }}" class="nx-alert nx-alert-red">
                        <i class="bi bi-qr-code"></i>
                        <span><strong>{{ $expiringStudentsCount }}</strong> temporary QR codes expire within 10 days</span>
                        <i class="bi bi-chevron-right ms-auto"></i>
                    </a>
                @endif

                @if (($incompleteRegistrationCount ?? 0) > 0)
                    <a href="#incomplete-registrations" class="nx-alert nx-alert-blue">
                        <i class="bi bi-hourglass-split"></i>
                        <span><strong>{{ $incompleteRegistrationCount }}</strong> incomplete student registrations</span>
                        <i class="bi bi-chevron-right ms-auto"></i>
                    </a>
                @endif
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- KPI CARDS --}}
        {{-- ===================================================== --}}
        <div class="stats-grid mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Today's Income</span>
                    <h2 class="stat-number">{{ $money($stats['todayIncome'] ?? $todayIncome ?? 0) }}</h2>
                    <span class="stat-change {{ $todayChangeClass }}">
                        {{ $todayChangeText }} <span class="stat-change-note">vs yesterday</span>
                    </span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="stat-info">
                    <span class="stat-label">{{ now()->format('F') }} Income</span>
                    <h2 class="stat-number">{{ $money($stats['monthlyIncome'] ?? $monthlyIncome ?? 0) }}</h2>
                    <span class="stat-change {{ $monthChangeClass }}">
                        {{ $monthChangeText }} <span class="stat-change-note">vs same days last month</span>
                    </span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-mortarboard-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Active Students</span>
                    <h2 class="stat-number">{{ number_format($stats['activeStudents'] ?? $studentsCount ?? 0) }}</h2>
                    <span class="stat-change neutral">
                        +{{ number_format($stats['newStudentsThisMonth'] ?? 0) }}
                        <span class="stat-change-note">new this month · {{ number_format($stats['activeEnrollments'] ?? 0) }} enrollments</span>
                    </span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-receipt-cutoff"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Fee Collection · {{ $unpaid['month'] ?? now()->format('F Y') }}</span>
                    <h2 class="stat-number">{{ $unpaid['paid_percent'] ?? 0 }}%</h2>
                    <div class="nx-progress mt-1">
                        <div class="nx-progress-fill bg-amber" style="width: {{ $unpaid['paid_percent'] ?? 0 }}%"></div>
                    </div>
                    <span class="stat-change-note">
                        {{ number_format($unpaid['unpaid_count'] ?? 0) }} of {{ number_format($unpaid['payable_count'] ?? 0) }} not paid yet
                    </span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- QUICK ACTIONS --}}
        {{-- ===================================================== --}}
        <div class="quick-actions-section mb-4">
            <div class="quick-actions-grid">
                @cardaccess('assign')
                <a href="{{ \App\Support\CardMode::temporary() ? route('student-temporary-registration.create') : route('student-card-registration.create') }}" class="quick-action-btn">
                    <span class="qa-icon"><i class="bi bi-person-plus-fill"></i></span>
                    <span class="qa-text"><span class="qa-title">Add Student</span><span class="qa-desc">{{ \App\Support\CardMode::temporary() ? 'Temporary card registration' : 'New registration' }}</span></span>
                </a>
                @endcardaccess

                <a href="{{ route('new-payment.index') }}" class="quick-action-btn">
                    <span class="qa-icon"><i class="bi bi-credit-card-2-front-fill"></i></span>
                    <span class="qa-text"><span class="qa-title">Add Payment</span><span class="qa-desc">Record a fee</span></span>
                </a>

                <a href="{{ route('class-schedules.todayClasses') }}" class="quick-action-btn">
                    <span class="qa-icon"><i class="bi bi-calendar-day"></i></span>
                    <span class="qa-text"><span class="qa-title">Today's Classes</span><span class="qa-desc">Live schedule</span></span>
                </a>

                <a href="{{ route('student-class-management.index') }}" class="quick-action-btn">
                    <span class="qa-icon"><i class="bi bi-person-workspace"></i></span>
                    <span class="qa-text"><span class="qa-title">Class Mgmt</span><span class="qa-desc">Enrollments</span></span>
                </a>

                <a href="{{ route('payment-reminder.index') }}" class="quick-action-btn">
                    <span class="qa-icon"><i class="bi bi-bell-fill"></i></span>
                    <span class="qa-text"><span class="qa-title">Reminders</span><span class="qa-desc">Unpaid fees</span></span>
                </a>

                <a href="{{ route('monthly-report.index') }}" class="quick-action-btn">
                    <span class="qa-icon"><i class="bi bi-file-earmark-bar-graph"></i></span>
                    <span class="qa-text"><span class="qa-title">Reports</span><span class="qa-desc">Monthly report</span></span>
                </a>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- TODAY: CLASSES + SUMMARY --}}
        {{-- ===================================================== --}}
        <div class="row g-4 mb-4">

            {{-- Today's Classes --}}
            <div class="col-xl-8">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-calendar-day text-primary"></i> Today's Classes</h5>
                            <p class="nx-card-subtitle">{{ now()->format('l, d M') }} · attendance vs enrolled</p>
                        </div>
                        <a href="{{ route('class-schedules.todayClasses') }}" class="nx-link">View all <i class="bi bi-arrow-right"></i></a>
                    </div>

                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Class</th>
                                    <th>Hall</th>
                                    <th>Status</th>
                                    <th class="attendance-col">Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($todayClasses as $class)
                                    <tr class="{{ $class['status'] === 'cancelled' ? 'row-muted' : '' }}">
                                        <td class="text-nowrap">
                                            <strong>{{ $class['start_time'] }}</strong>
                                            <div class="cell-sub">{{ $class['end_time'] }}</div>
                                        </td>
                                        <td>
                                            <a href="{{ route('class-schedules.show', $class['id']) }}" class="cell-title">{{ $class['class_name'] }}</a>
                                            <div class="cell-sub">{{ $class['category'] }} · {{ $class['teacher'] }}</div>
                                        </td>
                                        <td>{{ $class['hall'] }}</td>
                                        <td>
                                            <span class="nx-badge {{ $statusBadge[$class['status']] ?? 'nx-badge-gray' }}">
                                                {{ ucfirst($class['status']) }}
                                            </span>
                                            @if ($class['is_live'])
                                                <span class="live-dot" title="Running now"></span>
                                            @endif
                                        </td>
                                        <td class="attendance-col">
                                            @if ($class['status'] === 'cancelled')
                                                <span class="cell-sub">—</span>
                                            @else
                                                <div class="d-flex justify-content-between small">
                                                    <span>{{ $class['present'] }} / {{ $class['enrolled'] }}</span>
                                                    <span class="cell-sub">{{ $class['attendance_percent'] }}%</span>
                                                </div>
                                                <div class="nx-progress">
                                                    <div class="nx-progress-fill bg-blue" style="width: {{ min(100, $class['attendance_percent']) }}%"></div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="empty-table">
                                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                            No classes scheduled for today.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Today Summary + Unpaid --}}
            <div class="col-xl-4">
                <div class="d-flex flex-column gap-4 h-100">

                    <div class="nx-card p-3">
                        <h5 class="nx-card-title mb-3"><i class="bi bi-activity text-success"></i> Today at a Glance</h5>

                        <div class="glance-grid">
                            <div class="glance-item">
                                <span class="glance-value">{{ $todayClassSummary['scheduled'] ?? 0 }}</span>
                                <span class="glance-label">Upcoming</span>
                            </div>
                            <div class="glance-item">
                                <span class="glance-value text-amber">{{ $todayClassSummary['ongoing'] ?? 0 }}</span>
                                <span class="glance-label">Ongoing</span>
                            </div>
                            <div class="glance-item">
                                <span class="glance-value text-green">{{ $todayClassSummary['completed'] ?? 0 }}</span>
                                <span class="glance-label">Completed</span>
                            </div>
                            <div class="glance-item">
                                <span class="glance-value text-red">{{ $todayClassSummary['cancelled'] ?? 0 }}</span>
                                <span class="glance-label">Cancelled</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Students present</span>
                                <strong>{{ $present }} / {{ $expected }} ({{ $todayAttendancePercent }}%)</strong>
                            </div>
                            <div class="nx-progress nx-progress-lg">
                                <div class="nx-progress-fill bg-green" style="width: {{ min(100, $todayAttendancePercent) }}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="nx-card p-3 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="nx-card-title"><i class="bi bi-exclamation-circle text-danger"></i> Not Paid This Month</h5>
                                <p class="nx-card-subtitle">Classes with the most unpaid students</p>
                            </div>
                            <span class="nx-badge nx-badge-red">{{ number_format($unpaid['unpaid_count'] ?? 0) }}</span>
                        </div>

                        @forelse ($unpaid['top_classes'] ?? [] as $row)
                            <div class="unpaid-row">
                                <a href="{{ route('payment-reminder.index', ['class_id' => $row['student_class_id']]) }}" class="text-truncate text-reset">{{ $row['class_name'] }}</a>
                                <strong>{{ $row['unpaid_count'] }}</strong>
                            </div>
                        @empty
                            <p class="cell-sub mb-0"><i class="bi bi-check-circle text-success"></i> Everyone has paid this month.</p>
                        @endforelse

                        <a href="{{ route('payment-reminder.index') }}" class="nx-link d-inline-block mt-3">
                            Send reminders <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- INCOME: LAST 14 DAYS + PAYMENT METHODS --}}
        {{-- ===================================================== --}}
        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-bar-chart-fill text-primary"></i> Daily Income</h5>
                            <p class="nx-card-subtitle">Last 14 days · completed payments</p>
                        </div>
                        <strong>{{ $money(array_sum($dailyIncome['values'] ?? [])) }}</strong>
                    </div>
                    <div class="chart-box">
                        <canvas id="dailyIncomeChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="nx-card h-100 p-3">
                    <h5 class="nx-card-title"><i class="bi bi-wallet2 text-success"></i> Payment Methods</h5>
                    <p class="nx-card-subtitle mb-3">{{ now()->format('F Y') }}</p>

                    @forelse ($paymentMethods as $method)
                        @php
                            $share = $methodsTotal > 0 ? round(($method['total'] / $methodsTotal) * 100) : 0;
                        @endphp
                        <div class="method-row">
                            <div class="d-flex justify-content-between small">
                                <span>{{ $method['label'] }} <span class="cell-sub">({{ $method['payments'] }})</span></span>
                                <strong>{{ $money($method['total']) }}</strong>
                            </div>
                            <div class="nx-progress">
                                <div class="nx-progress-fill bg-blue" style="width: {{ $share }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="cell-sub mb-0">No payments this month yet.</p>
                    @endforelse

                    @if (($stats['monthlyDiscount'] ?? 0) > 0)
                        <div class="discount-note">
                            <i class="bi bi-tag"></i>
                            Discounts given this month: <strong>{{ $money($stats['monthlyDiscount']) }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- YEARLY CHART + CARD STOCK --}}
        {{-- ===================================================== --}}
        <div class="row g-4 mb-4">
            <div class="col-xl-9">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-graph-up-arrow text-primary"></i> Yearly Payment Report</h5>
                            <p class="nx-card-subtitle">Total payments vs institute income by month</p>
                        </div>
                        <div class="chart-controls">
                            <span class="legend-item"><span class="legend-dot bg-blue"></span> Total Payments</span>
                            <span class="legend-item"><span class="legend-dot bg-green"></span> Institute Income</span>
                            <select id="yearSelector" class="year-selector" aria-label="Select year">
                                @for ($y = now()->year; $y >= 2022; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="chart-box chart-box-lg">
                        <canvas id="yearlyPaymentChart"></canvas>
                        <div id="chartLoading" class="chart-loading d-none"><div class="spinner"></div></div>
                    </div>

                    <div class="chart-stats">
                        <div class="chart-stat">
                            <span class="chart-stat-label">Total Revenue</span>
                            <span class="chart-stat-value" id="totalRevenue">Rs. 0.00</span>
                        </div>
                        <div class="chart-stat">
                            <span class="chart-stat-label">Institute Income</span>
                            <span class="chart-stat-value" id="instituteIncome">Rs. 0.00</span>
                        </div>
                        <div class="chart-stat">
                            <span class="chart-stat-label">Best Month</span>
                            <span class="chart-stat-value" id="bestMonth">-</span>
                        </div>
                        <div class="chart-stat">
                            <span class="chart-stat-label">H2 vs H1</span>
                            <span class="chart-stat-value" id="growthRate">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3">
                <div class="nx-card h-100 p-3 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="stat-label text-uppercase">Available Student Cards</span>
                        <div class="stat-icon-wrapper icon-amber"><i class="bi bi-person-vcard-fill"></i></div>
                    </div>

                    <h2 class="card-stock-number">{{ $availableStudentCardCount ?? 0 }}</h2>

                    <div class="my-2">
                        @if ($showStudentCardWarning ?? false)
                            <span class="nx-badge nx-badge-amber"><i class="bi bi-exclamation-triangle"></i> Low stock</span>
                        @else
                            <span class="nx-badge nx-badge-green"><i class="bi bi-check-circle"></i> Enough stock</span>
                        @endif
                    </div>

                    <div class="mt-auto">
                        <div class="nx-progress">
                            <div class="nx-progress-fill bg-amber" style="width: {{ min(100, (($availableStudentCardCount ?? 0) / 50) * 100) }}%"></div>
                        </div>
                        <span class="cell-sub d-block mt-1">Reorder when stock reaches 20</span>
                        @cardaccess('view')
                        <a href="{{ route('student-cards.index') }}" class="nx-link d-inline-block mt-2">Manage cards <i class="bi bi-arrow-right"></i></a>
                        @endcardaccess
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- BRANCH SUMMARY (only when there is more than one branch) --}}
        {{-- ===================================================== --}}
        @if ($branchSummary->isNotEmpty())
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <h5 class="nx-card-title"><i class="bi bi-diagram-3 text-primary"></i> Branches</h5>
                </div>
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th class="text-end">Active Students</th>
                                <th class="text-end">Classes Today</th>
                                <th class="text-end">Today's Income</th>
                                <th class="text-end">{{ now()->format('F') }} Income</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($branchSummary as $branch)
                                <tr>
                                    <td class="cell-title">{{ $branch['name'] }}</td>
                                    <td class="text-end">{{ number_format($branch['active_students']) }}</td>
                                    <td class="text-end">{{ $branch['today_classes'] }}</td>
                                    <td class="text-end">{{ $money($branch['today_income']) }}</td>
                                    <td class="text-end"><strong>{{ $money($branch['month_income']) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- RECENT PAYMENTS + LATEST STUDENTS --}}
        {{-- ===================================================== --}}
        <div class="row g-4 mb-4">
            <div class="col-xl-7">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-receipt text-success"></i> Recent Payments</h5>
                        <a href="{{ route('receipts.index') }}" class="nx-link">All receipts <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Month</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPayments as $payment)
                                    <tr>
                                        <td>
                                            <span class="cell-title">{{ $payment['student_name'] }}</span>
                                            <div class="cell-sub"><span class="id-badge">{{ $payment['student_code'] }}</span></div>
                                        </td>
                                        <td>
                                            {{ $payment['class_name'] }}
                                            <div class="cell-sub">{{ $payment['receipt_number'] }} · {{ $payment['collected_by'] }}</div>
                                        </td>
                                        <td>{{ $payment['payment_month'] }}</td>
                                        <td class="text-end">
                                            <strong>{{ $money($payment['amount']) }}</strong>
                                            <div class="cell-sub">{{ $payment['payment_method'] }}</div>
                                        </td>
                                        <td class="text-end cell-sub" title="{{ $payment['paid_at'] }}">{{ $payment['paid_at_human'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="empty-table">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            No payments yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-clock-history text-info"></i> Latest Students</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Guardian</th>
                                    <th class="text-end">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($latestStudents ?? [] as $student)
                                    <tr>
                                        <td>
                                            <a href="{{ route('students.show', $student->id) }}" class="student-name-cell">
                                                <span class="avatar-xs">{{ strtoupper(substr($student->initial_name ?? 'N', 0, 1)) }}</span>
                                                <span>
                                                    <span class="cell-title">{{ $student->initial_name ?? '-' }}</span>
                                                    <span class="cell-sub d-block">{{ $student->custom_id ?? '-' }}</span>
                                                </span>
                                            </a>
                                        </td>
                                        <td>{{ $student->guardian_mobile ?? '-' }}</td>
                                        <td class="text-end cell-sub">{{ optional($student->created_at)->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="empty-table">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            No students found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- INCOMPLETE REGISTRATIONS + EXPIRING QR --}}
        {{-- ===================================================== --}}
        <div class="row g-4">
            <div class="col-xl-7" id="incomplete-registrations">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-hourglass-split text-warning"></i> Incomplete Registrations</h5>
                        <span class="nx-badge nx-badge-amber">{{ $incompleteRegistrationCount ?? 0 }} pending</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Guardian Mobile</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($incompleteRegistrations ?? [] as $student)
                                    <tr>
                                        <td><span class="id-badge">{{ $student->custom_id ?? '-' }}</span></td>
                                        <td>{{ $student->initial_name ?? '-' }}</td>
                                        <td>{{ $student->guardian_mobile ?? '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('students.edit', $student->id) }}" class="nx-link">
                                                <i class="bi bi-check2-circle"></i> Complete
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="empty-table">
                                            <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>
                                            No incomplete registrations.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="nx-card h-100">
                    <div class="nx-card-header">
                        <h5 class="nx-card-title"><i class="bi bi-qr-code text-danger"></i> Temporary QR Expiring</h5>
                        <span class="nx-badge nx-badge-red">{{ $expiringStudentsCount ?? 0 }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Guardian</th>
                                    <th class="text-end">Expires</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($expiringStudents ?? collect())->take(6) as $student)
                                    @php
                                        $expiresAt = $student->temporary_qr_code_expire_date
                                            ? \Carbon\Carbon::parse($student->temporary_qr_code_expire_date)
                                            : null;
                                        $daysLeft = $expiresAt ? today()->diffInDays($expiresAt, false) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="cell-title">{{ $student->initial_name ?? '-' }}</span>
                                            <div class="cell-sub">{{ $student->temporary_qr_code ?? '-' }}</div>
                                        </td>
                                        <td>{{ $student->guardian_mobile ?? '-' }}</td>
                                        <td class="text-end">
                                            @if ($daysLeft === 0)
                                                <span class="nx-badge nx-badge-red">Today</span>
                                            @elseif ($daysLeft !== null && $daysLeft <= 3)
                                                <span class="nx-badge nx-badge-amber">{{ $daysLeft }}d</span>
                                            @else
                                                <span class="cell-sub">{{ optional($expiresAt)->format('d M') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="empty-table">
                                            <i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i>
                                            No QR codes expiring soon.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection


@push('styles')
    <style>
        .dashboard-page {
            --nx-navy: #141c3a;
            --nx-blue: #2563eb;
            --nx-blue-soft: #eef3ff;
            --nx-green: #10a37f;
            --nx-green-soft: #e9f8f2;
            --nx-amber: #b7791f;
            --nx-amber-soft: #fef6e4;
            --nx-red: #dc2626;
            --nx-red-soft: #fdecec;
            --nx-violet: #7c3aed;
            --nx-violet-soft: #f3edff;
            --nx-bg: #f8fafc;
            --nx-border: #e5e9f0;
            --nx-text: #1e2640;
            --nx-muted: #6b7590;
            --nx-radius: 12px;

            color: var(--nx-text);
            background: var(--nx-bg);
            padding: 1.5rem;
            max-width: 1440px;
            margin: 0 auto;
        }

        /* ---------- Cards ---------- */
        .nx-card {
            background: #fff;
            border: 1px solid var(--nx-border);
            border-radius: var(--nx-radius);
            box-shadow: 0 1px 2px rgba(20, 28, 58, .04);
            overflow: hidden;
        }

        .nx-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--nx-border);
        }

        .nx-card-title {
            font-size: .95rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .nx-card-subtitle {
            font-size: .75rem;
            color: var(--nx-muted);
            margin: 0;
        }

        .nx-link {
            font-size: .8rem;
            font-weight: 500;
            color: var(--nx-blue);
            text-decoration: none;
        }

        .nx-link:hover {
            text-decoration: underline;
        }

        /* ---------- Hero ---------- */
        .hero-section {
            background: var(--nx-navy) radial-gradient(circle at 100% 0, rgba(37, 99, 235, .35), transparent 45%);
            color: #f1f4fb;
            border-radius: var(--nx-radius);
            padding: 1.75rem;
        }

        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .hero-left {
            flex: 1;
            min-width: 280px;
        }

        .greeting-badge {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            opacity: .7;
        }

        .hero-title {
            font-size: 1.7rem;
            font-weight: 600;
            margin: .25rem 0;
            color: #fff;
        }

        .hero-subtitle {
            font-size: .875rem;
            opacity: .7;
            margin: 0;
        }

        .hero-stats-mini {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-top: 1.1rem;
            flex-wrap: wrap;
        }

        .mini-stat {
            display: flex;
            flex-direction: column;
        }

        .mini-stat-value {
            font-size: 1.15rem;
            font-weight: 600;
        }

        .mini-stat-label {
            font-size: .75rem;
            opacity: .6;
        }

        .mini-stat-divider {
            width: 1px;
            height: 28px;
            background: rgba(255, 255, 255, .15);
        }

        .hero-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .5rem;
        }

        .hero-date-time {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .date-box,
        .time-box {
            background: rgba(255, 255, 255, .1);
            border-radius: 8px;
            padding: .5rem .75rem;
            font-size: .875rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .hero-updated {
            font-size: .7rem;
            opacity: .55;
        }

        /* ---------- Alerts ---------- */
        .alerts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: .75rem;
        }

        .nx-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .7rem 1rem;
            border-radius: 10px;
            font-size: .85rem;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .nx-alert-amber { background: var(--nx-amber-soft); color: #7a5010; border-color: rgba(183, 121, 31, .25); }
        .nx-alert-red { background: var(--nx-red-soft); color: #8f1d1d; border-color: rgba(220, 38, 38, .2); }
        .nx-alert-blue { background: var(--nx-blue-soft); color: #1e40af; border-color: rgba(37, 99, 235, .2); }
        .nx-alert:hover { filter: brightness(.97); }

        /* ---------- KPI ---------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .stat-card {
            display: flex;
            gap: .85rem;
            align-items: flex-start;
            padding: 1.1rem;
        }

        .stat-icon-wrapper {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .icon-blue { background: var(--nx-blue-soft); color: var(--nx-blue); }
        .icon-green { background: var(--nx-green-soft); color: var(--nx-green); }
        .icon-amber { background: var(--nx-amber-soft); color: var(--nx-amber); }
        .icon-violet { background: var(--nx-violet-soft); color: var(--nx-violet); }

        .stat-info {
            flex: 1;
            min-width: 0;
        }

        .stat-label {
            font-size: .75rem;
            color: var(--nx-muted);
            display: block;
        }

        .stat-number {
            font-size: 1.35rem;
            font-weight: 700;
            margin: .15rem 0;
            line-height: 1.25;
        }

        .stat-change {
            font-size: .75rem;
            font-weight: 600;
        }

        .stat-change.up { color: var(--nx-green); }
        .stat-change.down { color: var(--nx-red); }
        .stat-change.neutral { color: var(--nx-muted); }

        .stat-change-note {
            font-size: .72rem;
            font-weight: 400;
            color: var(--nx-muted);
        }

        /* ---------- Progress ---------- */
        .nx-progress {
            height: 6px;
            background: #eef1f6;
            border-radius: 99px;
            overflow: hidden;
        }

        .nx-progress-lg { height: 9px; }

        .nx-progress-fill {
            height: 100%;
            border-radius: 99px;
            transition: width .6s ease;
        }

        .bg-blue { background: var(--nx-blue); }
        .bg-green { background: var(--nx-green); }
        .bg-amber { background: var(--nx-amber); }

        .text-amber { color: var(--nx-amber); }
        .text-green { color: var(--nx-green); }
        .text-red { color: var(--nx-red); }

        /* ---------- Badges ---------- */
        .nx-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .2rem .55rem;
            border-radius: 6px;
            font-size: .72rem;
            font-weight: 600;
        }

        .nx-badge-blue { background: var(--nx-blue-soft); color: var(--nx-blue); }
        .nx-badge-green { background: var(--nx-green-soft); color: var(--nx-green); }
        .nx-badge-amber { background: var(--nx-amber-soft); color: var(--nx-amber); }
        .nx-badge-red { background: var(--nx-red-soft); color: var(--nx-red); }
        .nx-badge-gray { background: #f1f3f7; color: var(--nx-muted); }

        .live-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin-left: .3rem;
            border-radius: 50%;
            background: var(--nx-green);
            box-shadow: 0 0 0 0 rgba(16, 163, 127, .6);
            animation: nx-pulse 1.6s infinite;
            vertical-align: middle;
        }

        @keyframes nx-pulse {
            70% { box-shadow: 0 0 0 7px rgba(16, 163, 127, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 163, 127, 0); }
        }

        /* ---------- Quick Actions ---------- */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: .6rem;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: .6rem;
            border: 1px solid var(--nx-border);
            background: #fff;
            border-radius: 10px;
            padding: .65rem .75rem;
            color: var(--nx-text);
            text-decoration: none;
            transition: all .15s ease;
        }

        .quick-action-btn:hover {
            border-color: rgba(37, 99, 235, .4);
            background: var(--nx-blue-soft);
            color: var(--nx-text);
        }

        .qa-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: var(--nx-blue-soft);
            color: var(--nx-blue);
            flex-shrink: 0;
        }

        .qa-text { min-width: 0; }
        .qa-title { display: block; font-weight: 600; font-size: .83rem; }
        .qa-desc { display: block; font-size: .7rem; color: var(--nx-muted); }

        /* ---------- Today ---------- */
        .glance-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .5rem;
            text-align: center;
        }

        .glance-item {
            background: var(--nx-bg);
            border-radius: 10px;
            padding: .6rem .25rem;
        }

        .glance-value {
            display: block;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .glance-label {
            font-size: .68rem;
            color: var(--nx-muted);
        }

        .unpaid-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: .45rem 0;
            border-bottom: 1px dashed var(--nx-border);
            font-size: .85rem;
        }

        .unpaid-row:last-of-type { border-bottom: 0; }

        .method-row { margin-bottom: .85rem; }
        .method-row .nx-progress { margin-top: .3rem; }

        .discount-note {
            margin-top: 1rem;
            padding: .6rem .75rem;
            border-radius: 8px;
            background: var(--nx-amber-soft);
            color: #7a5010;
            font-size: .8rem;
        }

        /* ---------- Charts ---------- */
        .chart-box {
            position: relative;
            height: 260px;
            padding: 1rem .75rem .5rem;
        }

        .chart-box-lg { height: 300px; }

        .chart-controls {
            display: flex;
            align-items: center;
            gap: .9rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: .35rem;
            font-size: .75rem;
            color: var(--nx-muted);
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 3px;
        }

        .year-selector {
            border: 1px solid var(--nx-border);
            border-radius: 6px;
            padding: .3rem .5rem;
            font-size: .85rem;
            background: #fff;
        }

        .chart-loading {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, .85);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #e2e8f0;
            border-top-color: var(--nx-blue);
            border-radius: 50%;
            animation: nx-spin .8s linear infinite;
        }

        @keyframes nx-spin { to { transform: rotate(360deg); } }

        .chart-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-top: 1px solid var(--nx-border);
        }

        .chart-stat {
            padding: .75rem 1.25rem;
            border-right: 1px solid var(--nx-border);
        }

        .chart-stat:last-child { border-right: 0; }
        .chart-stat-label { display: block; font-size: .72rem; color: var(--nx-muted); }
        .chart-stat-value { font-size: .9rem; font-weight: 600; }

        .card-stock-number {
            font-size: 2.4rem;
            font-weight: 700;
            margin: 1rem 0 0;
        }

        /* ---------- Tables ---------- */
        .premium-table { font-size: .85rem; }

        .premium-table thead th {
            background: #f4f6fa;
            color: var(--nx-muted);
            font-size: .72rem;
            font-weight: 600;
            padding: .55rem 1.25rem;
            border: 0;
            text-transform: uppercase;
            letter-spacing: .03em;
            white-space: nowrap;
        }

        .premium-table tbody td {
            padding: .7rem 1.25rem;
            border-top: 1px solid var(--nx-border);
            vertical-align: middle;
        }

        .premium-table tbody tr:hover { background: #fafbfd; }
        .row-muted td { opacity: .55; }

        .attendance-col { min-width: 150px; }

        .cell-title {
            font-weight: 600;
            color: var(--nx-text);
            text-decoration: none;
        }

        a.cell-title:hover { color: var(--nx-blue); }

        .cell-sub {
            font-size: .74rem;
            color: var(--nx-muted);
        }

        .id-badge {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .72rem;
            background: #f1f3f7;
            padding: .05rem .35rem;
            border-radius: 4px;
        }

        .student-name-cell {
            display: flex;
            align-items: center;
            gap: .5rem;
            text-decoration: none;
            color: inherit;
        }

        .avatar-xs {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--nx-blue-soft);
            color: var(--nx-blue);
            font-size: 12px;
            font-weight: 600;
            display: inline-grid;
            place-items: center;
            flex-shrink: 0;
        }

        .empty-table {
            text-align: center;
            padding: 2.25rem 1rem !important;
            color: var(--nx-muted);
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 1200px) {
            .quick-actions-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 992px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .dashboard-page { padding: 1rem; }
            .hero-section { padding: 1.25rem; }
            .hero-title { font-size: 1.35rem; }
            .hero-right { align-items: flex-start; width: 100%; }
            .chart-stats { grid-template-columns: 1fr 1fr; }
            .chart-stat:nth-child(2) { border-right: 0; }
            .chart-stat:nth-child(-n+2) { border-bottom: 1px solid var(--nx-border); }
            .chart-box, .chart-box-lg { height: 230px; }
            .premium-table thead th,
            .premium-table tbody td { padding: .5rem .6rem; }
        }

        @media (max-width: 576px) {
            .stats-grid { grid-template-columns: 1fr; }
            .quick-actions-grid { grid-template-columns: repeat(2, 1fr); }
            .glance-grid { grid-template-columns: repeat(2, 1fr); }
            .mini-stat-divider { display: none; }
        }
    </style>
@endpush


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        (function () {
            'use strict';

            const DAILY_INCOME = @json($dailyIncome);
            const YEARLY_URL = @json(route('reports.institute-income.yearly'));

            const COLORS = {
                blue: '#2563eb',
                green: '#10a37f',
                grid: '#eef1f6',
                tick: '#6b7590',
            };

            /* ---------------------------------------------------------------
             | Helpers
             * ------------------------------------------------------------- */

            function money(value) {
                return 'Rs. ' + Number(value || 0).toLocaleString('en-LK', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function shortMoney(value) {
                const n = Number(value || 0);

                if (n >= 1000000) return 'Rs. ' + (n / 1000000).toFixed(1) + 'M';
                if (n >= 1000) return 'Rs. ' + (n / 1000).toFixed(0) + 'K';

                return 'Rs. ' + n;
            }

            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label}: ${money(ctx.raw)}`,
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: COLORS.grid },
                        ticks: { color: COLORS.tick, callback: shortMoney },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: COLORS.tick },
                    },
                },
            };

            /* ---------------------------------------------------------------
             | Live clock
             * ------------------------------------------------------------- */

            const timeEl = document.getElementById('liveTime');

            function updateTime() {
                if (timeEl) {
                    timeEl.textContent = new Date().toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true,
                    });
                }
            }

            updateTime();
            setInterval(updateTime, 30000);

            if (typeof Chart === 'undefined') {
                return; // Chart.js failed to load; the rest of the page still works.
            }

            /* ---------------------------------------------------------------
             | Daily income (last 14 days)
             * ------------------------------------------------------------- */

            const dailyCanvas = document.getElementById('dailyIncomeChart');

            if (dailyCanvas) {
                const lastIndex = DAILY_INCOME.values.length - 1;

                new Chart(dailyCanvas, {
                    type: 'bar',
                    data: {
                        labels: DAILY_INCOME.labels,
                        datasets: [{
                            label: 'Income',
                            data: DAILY_INCOME.values,
                            // Highlight today
                            backgroundColor: DAILY_INCOME.values.map((_, i) => i === lastIndex ? COLORS.green : COLORS.blue),
                            borderRadius: 6,
                            maxBarThickness: 32,
                        }],
                    },
                    options: baseOptions,
                });
            }

            /* ---------------------------------------------------------------
             | Yearly report (existing endpoint)
             * ------------------------------------------------------------- */

            const yearlyCanvas = document.getElementById('yearlyPaymentChart');
            const yearSelector = document.getElementById('yearSelector');
            const loader = document.getElementById('chartLoading');
            let yearlyChart = null;

            function setText(id, text, color) {
                const el = document.getElementById(id);

                if (el) {
                    el.textContent = text;
                    el.style.color = color || '';
                }
            }

            function updateYearStats(total, institute, labels) {
                const sum = (arr) => arr.reduce((a, b) => a + Number(b || 0), 0);

                const totalRevenue = sum(total);
                let best = 0;

                total.forEach((value, i) => {
                    if (Number(value) > Number(total[best])) best = i;
                });

                const firstHalf = sum(total.slice(0, 6));
                const secondHalf = sum(total.slice(6, 12));

                setText('totalRevenue', money(totalRevenue));
                setText('instituteIncome', money(sum(institute)));
                setText('bestMonth', totalRevenue > 0 ? `${labels[best]} (${money(total[best])})` : '-');

                if (firstHalf > 0) {
                    const growth = ((secondHalf - firstHalf) / firstHalf) * 100;
                    setText('growthRate', (growth >= 0 ? '+' : '') + growth.toFixed(1) + '%', growth >= 0 ? COLORS.green : '#dc2626');
                } else {
                    setText('growthRate', '-');
                }
            }

            async function loadYearlyReport(year) {
                if (!yearlyCanvas) return;

                loader.classList.remove('d-none');

                try {
                    const response = await fetch(`${YEARLY_URL}?year=${encodeURIComponent(year)}`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Failed to load report.');
                    }

                    const ctx = yearlyCanvas.getContext('2d');

                    const gradient = (color) => {
                        const g = ctx.createLinearGradient(0, 0, 0, 300);
                        g.addColorStop(0, color + '2e');
                        g.addColorStop(1, color + '00');
                        return g;
                    };

                    if (yearlyChart) yearlyChart.destroy();

                    yearlyChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: result.labels,
                            datasets: [
                                {
                                    label: 'Total Payments',
                                    data: result.total_payments,
                                    borderColor: COLORS.blue,
                                    backgroundColor: gradient(COLORS.blue),
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 0,
                                    pointHoverRadius: 4,
                                },
                                {
                                    label: 'Institute Income',
                                    data: result.institution_payments,
                                    borderColor: COLORS.green,
                                    backgroundColor: gradient(COLORS.green),
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 0,
                                    pointHoverRadius: 4,
                                },
                            ],
                        },
                        options: baseOptions,
                    });

                    updateYearStats(result.total_payments || [], result.institution_payments || [], result.labels || []);
                } catch (error) {
                    setText('bestMonth', 'Could not load report');
                } finally {
                    loader.classList.add('d-none');
                }
            }

            if (yearSelector) {
                loadYearlyReport(yearSelector.value);
                yearSelector.addEventListener('change', () => loadYearlyReport(yearSelector.value));
            }
        })();
    </script>
@endpush