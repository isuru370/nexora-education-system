@extends('layouts.app')

@section('title', 'Monthly Class Attendance Report')
@section('page-title', 'Attendance Report')

@section('content')

    @php
        // ✅ Precompute quick stats so the view stays clean
        $totalPresent = 0;
        $totalAbsent  = 0;
        $totalDays    = 0;

        foreach ($report['classes'] ?? [] as $class) {
            foreach ($class['categories'] ?? [] as $cat) {
                foreach ($cat['students'] ?? [] as $student) {
                    $totalPresent += (int) ($student['attended'] ?? 0);
                    $totalAbsent  += (int) ($student['absent'] ?? 0);
                }
                $totalDays += (int) ($cat['class_days'] ?? 0);
            }
        }

        $rate = $report['summary']['attendance_rate'] ?? 0;
        $rateTone = $rate >= 80 ? 'green' : ($rate >= 60 ? 'amber' : 'red');
        $rateLabel = $rate >= 80 ? 'Excellent' : ($rate >= 60 ? 'Average' : 'Needs Improvement');
        $rateIcon = $rate >= 80 ? 'bi-check-circle' : ($rate >= 60 ? 'bi-exclamation-triangle' : 'bi-x-circle');
    @endphp

    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">
                        Monthly Class Attendance
                        <span class="nx-badge nx-badge-blue">{{ $report['month_name'] }}</span>
                    </h1>
                    <p class="hero-subtitle">
                        <i class="bi bi-clock-history"></i>
                        Detailed attendance summary for all classes and categories
                    </p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $report['summary']['total_classes'] }}</span>
                            <span class="mini-stat-label">Classes</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $report['summary']['total_students'] }}</span>
                            <span class="mini-stat-label">Students</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ number_format($rate, 1) }}%</span>
                            <span class="mini-stat-label">Attendance</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    {{-- ✅ FIXED: $option loop + $month comparison --}}
                    <form method="GET" action="{{ route('admin.monthly-class-attendance-report.index') }}">
                        <div class="hero-date-time">
                            <div class="date-box">
                                <i class="bi bi-calendar-range"></i>
                                <select name="month"
                                    onchange="this.form.submit()"
                                    class="hero-select"
                                    aria-label="Select month">
                                    @foreach ($availableMonths as $option)
                                        <option value="{{ $option['value'] }}"
                                            {{ $month === $option['value'] ? 'selected' : '' }}>
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    {{-- ✅ FIXED: $paymentMonth → $month --}}
                    <a href="{{ route('admin.monthly-class-attendance-report.export', ['month' => $month]) }}"
                        class="nx-btn nx-btn-light">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                    </a>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- KPI CARDS --}}
        {{-- ===================================================== --}}
        <div class="stats-grid mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-book"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Classes</span>
                    <h2 class="stat-number">{{ $report['summary']['total_classes'] }}</h2>
                    <span class="stat-change neutral">
                        <i class="bi bi-grid"></i>
                        <span class="stat-change-note">Active</span>
                    </span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-people-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Students</span>
                    <h2 class="stat-number">{{ $report['summary']['total_students'] }}</h2>
                    <span class="stat-change neutral">
                        <i class="bi bi-person-check"></i>
                        <span class="stat-change-note">Enrolled</span>
                    </span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-person-plus-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">New Students</span>
                    <h2 class="stat-number">{{ $report['summary']['new_students'] }}</h2>
                    <span class="stat-change up">
                        {{ $report['summary']['total_students'] > 0
                            ? number_format(($report['summary']['new_students'] / $report['summary']['total_students']) * 100, 1)
                            : 0 }}%
                        <span class="stat-change-note">of total</span>
                    </span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-{{ $rateTone === 'green' ? 'green' : ($rateTone === 'amber' ? 'amber' : 'red') }}">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Attendance Rate</span>
                    <h2 class="stat-number">{{ number_format($rate, 1) }}%</h2>
                    <span class="stat-change {{ $rate >= 80 ? 'up' : ($rate >= 60 ? 'neutral' : 'down') }}">
                        <i class="bi {{ $rateIcon }}"></i>
                        {{ $rateLabel }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- QUICK STATS BAR --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">
            <div class="quick-stats-bar">
                <div class="quick-stat-item">
                    <span class="quick-stat-label"><i class="bi bi-calendar-check text-primary"></i> Month</span>
                    <span class="quick-stat-value">{{ $report['month_name'] }}</span>
                </div>
                <div class="quick-stat-divider"></div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label"><i class="bi bi-person-check text-success"></i> Present</span>
                    <span class="quick-stat-value">{{ number_format($totalPresent) }}</span>
                </div>
                <div class="quick-stat-divider"></div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label"><i class="bi bi-person-x text-danger"></i> Absent</span>
                    <span class="quick-stat-value">{{ number_format($totalAbsent) }}</span>
                </div>
                <div class="quick-stat-divider"></div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label"><i class="bi bi-calendar-week text-info"></i> Total Class Days</span>
                    <span class="quick-stat-value">{{ number_format($totalDays) }}</span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- CLASS CARDS --}}
        {{-- ===================================================== --}}
        @forelse ($report['classes'] ?? [] as $classIndex => $class)

            <div class="nx-card mb-4">

                {{-- CLASS HEADER --}}
                <div class="nx-card-header att-class-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="att-class-icon">
                            {{ substr($class['class_name'], 0, 2) }}
                        </div>
                        <div>
                            <h5 class="att-class-name">{{ $class['class_name'] }}</h5>
                            <div class="att-class-meta">
                                <span class="att-meta-tag">
                                    <i class="bi bi-mortarboard"></i>
                                    {{ $class['grade_name'] ?: 'N/A' }}
                                </span>
                                <span class="att-meta-tag">
                                    <i class="bi bi-person-badge"></i>
                                    {{ $class['teacher_name'] ?: 'N/A' }}
                                </span>
                                <span class="att-meta-tag">
                                    <i class="bi bi-people"></i>
                                    {{ $class['total_students'] }} Students
                                </span>
                                <span class="att-meta-tag">
                                    <i class="bi bi-person-plus"></i>
                                    {{ $class['new_students'] }} New
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Attendance rate circle --}}
                    <div class="att-rate-circle">
                        <svg viewBox="0 0 36 36">
                            <path class="rate-bg"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="rate-fill"
                                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                stroke-dasharray="{{ $class['attendance_rate'] }}, 100"/>
                        </svg>
                        <span class="rate-text">{{ number_format($class['attendance_rate'], 1) }}%</span>
                    </div>
                </div>

                {{-- CLASS BODY --}}
                <div class="p-3">

                    @foreach ($class['categories'] as $categoryIndex => $category)

                        <div class="att-category">

                            {{-- CATEGORY HEADER --}}
                            <div class="att-category-header">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="att-category-dot"
                                        style="background: {{ ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316'][$categoryIndex % 8] }};"></span>
                                    <h6 class="att-category-name">{{ $category['category_name'] }}</h6>
                                    <span class="nx-badge nx-badge-gray">
                                        <i class="bi bi-people"></i>
                                        {{ count($category['students']) }} students
                                    </span>
                                </div>

                                <div class="att-category-stats">
                                    <span class="att-cat-stat">
                                        <i class="bi bi-person-plus text-success"></i>
                                        {{ $category['new_students'] }} New
                                    </span>
                                    <span class="att-cat-stat">
                                        <i class="bi bi-calendar3"></i>
                                        {{ $category['class_days'] }} Days
                                    </span>
                                    <span class="nx-badge nx-badge-{{ $category['attendance_rate'] >= 80 ? 'green' : ($category['attendance_rate'] >= 60 ? 'amber' : 'red') }}">
                                        <i class="bi bi-percent"></i>
                                        {{ number_format($category['attendance_rate'], 1) }}%
                                    </span>
                                </div>
                            </div>

                            {{-- SCROLLABLE STUDENT TABLE --}}
                            <div class="att-table-scroll">
                                <table class="table premium-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>ID</th>
                                            <th class="text-center">Class Days</th>
                                            <th class="text-center">Attended</th>
                                            <th class="text-center">Absent</th>
                                            <th class="text-center">Attendance %</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($category['students'] as $student)
                                            @php
                                                $percent = $student['attendance_percentage'];
                                                $tone = $percent >= 80 ? 'green' : ($percent >= 60 ? 'amber' : 'red');
                                            @endphp

                                            <tr class="att-student-row att-row-{{ $tone }}">
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="att-avatar">
                                                            {{ strtoupper(substr($student['student_name'], 0, 1)) }}
                                                        </div>
                                                        <span class="cell-title">{{ $student['student_name'] }}</span>
                                                        @if ($student['is_new_student'])
                                                            <span class="nx-badge nx-badge-green">New</span>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td>
                                                    <span class="id-badge">{{ $student['student_custom_id'] }}</span>
                                                </td>

                                                <td class="text-center">
                                                    <span class="cell-title" style="color: var(--nx-blue);">
                                                        {{ $student['class_days'] }}
                                                    </span>
                                                </td>

                                                <td class="text-center">
                                                    <span class="cell-title" style="color: var(--nx-green);">
                                                        {{ $student['attended'] }}
                                                    </span>
                                                </td>

                                                <td class="text-center">
                                                    <span class="cell-title" style="color: var(--nx-red);">
                                                        {{ $student['absent'] }}
                                                    </span>
                                                </td>

                                                <td class="text-center">
                                                    <span class="nx-badge nx-badge-{{ $tone }}">
                                                        {{ number_format($student['attendance_percentage'], 1) }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="empty-table">
                                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                                    <strong class="d-block">No students found</strong>
                                                    <span class="cell-sub">No students in this category</span>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- TABLE FOOTER --}}
                            @if (count($category['students']) > 0)
                                <div class="att-table-footer">
                                    <span>
                                        <i class="bi bi-person-check"></i>
                                        Showing <strong>{{ count($category['students']) }}</strong> students
                                    </span>
                                    <span>
                                        <i class="bi bi-arrow-up-short"></i>
                                        Scroll for more
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach

                </div>
            </div>

        @empty
            <div class="nx-card">
                <div class="empty-table py-5">
                    <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                    <strong class="d-block mb-2">No Attendance Data</strong>
                    <p class="cell-sub mb-2">
                        No attendance records found for <strong>{{ $report['month_name'] }}</strong>
                    </p>
                    <span class="cell-sub">Please try selecting a different month</span>
                </div>
            </div>
        @endforelse

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Monthly Class Attendance Report — page-specific styles only.
       Shared nx-* theme comes from layouts/partials/nexora-theme.
       ============================================================ */

    /* Hero select (month picker inside hero) */
    .dashboard-page .hero-select {
        background: transparent;
        border: 0;
        color: #fff;
        font-size: .875rem;
        font-weight: 600;
        padding: 0 .25rem 0 0;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        outline: none;
    }
    .dashboard-page .hero-select option {
        color: var(--nx-text);
        background: #fff;
    }

    /* Class header */
    .dashboard-page .att-class-header {
        background: #f4f6fa;
    }

    .dashboard-page .att-class-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        display: grid; place-items: center;
        font-weight: 700;
        font-size: .85rem;
        flex-shrink: 0;
    }

    .dashboard-page .att-class-name {
        margin: 0;
        font-size: .98rem;
        font-weight: 700;
        color: var(--nx-text);
    }

    .dashboard-page .att-class-meta {
        display: flex;
        gap: .4rem;
        flex-wrap: wrap;
        margin-top: .25rem;
    }

    .dashboard-page .att-meta-tag {
        font-size: .7rem;
        color: var(--nx-muted);
        background: #fff;
        border: 1px solid var(--nx-border);
        padding: .1rem .55rem;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
    }
    .dashboard-page .att-meta-tag i { font-size: .65rem; }

    /* Attendance rate circle */
    .dashboard-page .att-rate-circle {
        position: relative;
        width: 52px;
        height: 52px;
        flex-shrink: 0;
    }
    .dashboard-page .att-rate-circle svg {
        width: 52px;
        height: 52px;
        transform: rotate(-90deg);
    }
    .dashboard-page .att-rate-circle .rate-bg {
        fill: none;
        stroke: var(--nx-border);
        stroke-width: 3;
    }
    .dashboard-page .att-rate-circle .rate-fill {
        fill: none;
        stroke: var(--nx-blue);
        stroke-width: 3;
        stroke-linecap: round;
        transition: stroke-dasharray 1s ease;
    }
    .dashboard-page .att-rate-circle .rate-text {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        font-size: .65rem;
        font-weight: 700;
        color: var(--nx-text);
    }

    /* Category block */
    .dashboard-page .att-category {
        margin-bottom: 1.25rem;
    }
    .dashboard-page .att-category:last-child { margin-bottom: 0; }

    .dashboard-page .att-category-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        padding: .5rem .75rem;
        background: #f4f6fa;
        border-radius: 8px;
        margin-bottom: .5rem;
    }

    .dashboard-page .att-category-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }

    .dashboard-page .att-category-name {
        margin: 0;
        font-size: .88rem;
        font-weight: 700;
        color: var(--nx-text);
    }

    .dashboard-page .att-category-stats {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .dashboard-page .att-cat-stat {
        font-size: .72rem;
        color: var(--nx-muted);
        background: #fff;
        border: 1px solid var(--nx-border);
        padding: .1rem .55rem;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: .2rem;
    }
    .dashboard-page .att-cat-stat i { font-size: .7rem; }

    /* Student table scroll wrapper */
    .dashboard-page .att-table-scroll {
        max-height: 320px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        scrollbar-width: thin;
        scrollbar-color: var(--nx-border) #f4f6fa;
    }
    .dashboard-page .att-table-scroll::-webkit-scrollbar { width: 8px; height: 8px; }
    .dashboard-page .att-table-scroll::-webkit-scrollbar-track { background: #f4f6fa; }
    .dashboard-page .att-table-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .dashboard-page .att-table-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* Sticky header for the inner table */
    .dashboard-page .att-table-scroll .premium-table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        box-shadow: 0 1px 0 var(--nx-border);
    }

    /* Row left-accent by attendance tone */
    .dashboard-page .att-student-row.att-row-green  td:first-child { box-shadow: inset 3px 0 0 var(--nx-green); }
    .dashboard-page .att-student-row.att-row-amber  td:first-child { box-shadow: inset 3px 0 0 var(--nx-amber); }
    .dashboard-page .att-student-row.att-row-red    td:first-child { box-shadow: inset 3px 0 0 var(--nx-red); }

    .dashboard-page .att-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        display: grid; place-items: center;
        font-size: .7rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    /* Table footer */
    .dashboard-page .att-table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: .3rem;
        padding: .4rem .75rem;
        margin-top: .4rem;
        background: #f4f6fa;
        border-radius: 8px;
        font-size: .72rem;
        color: var(--nx-muted);
    }

    /* Quick stats bar (inline row inside nx-card) */
    .dashboard-page .quick-stats-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 2rem;
        padding: 1rem 1.25rem;
        flex-wrap: wrap;
    }

    .dashboard-page .quick-stat-item {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .dashboard-page .quick-stat-label {
        font-size: .78rem;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 600;
    }

    .dashboard-page .quick-stat-value {
        font-weight: 700;
        font-size: .95rem;
        color: var(--nx-text);
    }

    .dashboard-page .quick-stat-divider {
        width: 1px;
        height: 26px;
        background: var(--nx-border);
    }

    /* Responsive */
    @media (max-width: 992px) {
        .dashboard-page .att-class-header { flex-direction: column; align-items: stretch; }
        .dashboard-page .att-rate-circle { align-self: flex-start; }
    }

    @media (max-width: 768px) {
        .dashboard-page .quick-stats-bar { gap: 1rem; padding: .75rem; }
        .dashboard-page .quick-stat-divider { display: none; }
        .dashboard-page .att-category-header { flex-direction: column; align-items: flex-start; }
        .dashboard-page .att-table-scroll { max-height: 260px; }
        .dashboard-page .premium-table { min-width: 520px; }
    }

    @media (max-width: 480px) {
        .dashboard-page .quick-stats-bar { flex-direction: column; align-items: stretch; gap: .35rem; }
        .dashboard-page .quick-stat-item { justify-content: space-between; }
        .dashboard-page .att-table-scroll { max-height: 220px; }
        .dashboard-page .premium-table { min-width: 460px; }
        .dashboard-page .att-avatar { width: 26px; height: 26px; font-size: .62rem; }
    }

    @media print {
        .dashboard-page .nx-btn,
        .dashboard-page .hero-select { display: none !important; }

        .dashboard-page .nx-card,
        .dashboard-page .att-table-scroll {
            break-inside: avoid;
            box-shadow: none !important;
        }

        .dashboard-page .att-table-scroll {
            max-height: none !important;
            overflow: visible !important;
        }

        .dashboard-page .att-table-scroll .premium-table thead th {
            position: static !important;
            box-shadow: none !important;
            background: #e9ecef !important;
        }

        .dashboard-page .att-table-footer { display: none !important; }
    }
</style>
@endpush