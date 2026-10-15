@extends('layouts.app')

@section('title', 'Attendance Details - ' . ($data['student']->full_name ?? 'Student'))
@section('page-title', 'Attendance Details')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">{{ $data['student']->initial_name ?? ($data['student']->full_name ?? 'Student') }}</h1>
                <p class="hero-subtitle">{{ $data['class']->class_name ?? 'N/A' }} • {{ $data['category']->category_name ?? 'N/A' }}</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('student-class-management.show', $data['student']->id) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back to Classes
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- SUMMARY --}}
    <div class="stats-grid mb-4">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-calendar-week"></i></div>
            <div class="stat-info">
                <span class="stat-label">Class Days</span>
                <h2 class="stat-number">{{ $data['total_days'] ?? 0 }}</h2>
                <span class="stat-change neutral">After <span class="stat-change-note">enrollment</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Attended</span>
                <h2 class="stat-number">{{ $data['attended_days'] ?? 0 }}</h2>
                <span class="stat-change up">Attendance <span class="stat-change-note">marked</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-red"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Absent</span>
                <h2 class="stat-number">{{ $data['absent_days'] ?? 0 }}</h2>
                <span class="stat-change down">From <span class="stat-change-note">class days</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-percent"></i></div>
            <div class="stat-info">
                <span class="stat-label">Attendance Rate</span>
                <h2 class="stat-number">{{ number_format((float) ($data['attendance_percentage'] ?? 0), 2) }}%</h2>
                <span class="stat-change neutral">Attended / <span class="stat-change-note">class days</span></span>
            </div>
        </div>
    </div>

    {{-- MONTHLY BAR CHART --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-bar-chart-fill text-primary"></i> Monthly Attendance Overview</h5>
                <p class="nx-card-subtitle">Completed classes, attended and absent by month</p>
            </div>
            <span class="nx-badge nx-badge-green">
                <i class="bi bi-graph-up-arrow"></i>
                {{ number_format((float) ($data['attendance_percentage'] ?? 0), 2) }}%
            </span>
        </div>

        <div class="p-3">
            @if (isset($data['monthly_attendance']) && $data['monthly_attendance']->count() > 0)
                <div class="monthly-chart-wrapper">
                    @foreach ($data['monthly_attendance'] as $month)
                        @php
                            $classDays = (int) ($month['total_days'] ?? 0);
                            $attended = (int) ($month['attended_days'] ?? 0);
                            $absent = (int) ($month['absent_days'] ?? 0);
                            $maxValue = max($classDays, $attended, $absent, 1);
                            $classHeight = ($classDays / $maxValue) * 100;
                            $attendedHeight = ($attended / $maxValue) * 100;
                            $absentHeight = ($absent / $maxValue) * 100;
                        @endphp
                        <div class="month-group">
                            <div class="month-bars">
                                <div class="month-bar-column">
                                    <div class="month-bar-value">{{ $classDays }}</div>
                                    <div class="month-bar-area">
                                        <div class="month-bar class-bar"
                                            style="height: {{ $classDays > 0 ? max($classHeight, 5) : 0 }}%;"></div>
                                    </div>
                                </div>
                                <div class="month-bar-column">
                                    <div class="month-bar-value">{{ $attended }}</div>
                                    <div class="month-bar-area">
                                        <div class="month-bar attended-bar"
                                            style="height: {{ $attended > 0 ? max($attendedHeight, 5) : 0 }}%;"></div>
                                    </div>
                                </div>
                                <div class="month-bar-column">
                                    <div class="month-bar-value">{{ $absent }}</div>
                                    <div class="month-bar-area">
                                        <div class="month-bar absent-bar"
                                            style="height: {{ $absent > 0 ? max($absentHeight, 5) : 0 }}%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="month-label">{{ $month['month_name'] ?? $month['month'] }}</div>
                            <div class="month-percentage">
                                {{ number_format((float) ($month['attendance_percentage'] ?? 0), 0) }}%
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="chart-legend">
                    <div class="legend-item"><span class="legend-dot class-dot"></span><span>Class Days</span></div>
                    <div class="legend-item"><span class="legend-dot attended-dot"></span><span>Attended</span></div>
                    <div class="legend-item"><span class="legend-dot absent-dot"></span><span>Absent</span></div>
                </div>
            @else
                <div class="empty-table">
                    <i class="bi bi-bar-chart fs-2 d-block mb-2"></i>
                    <strong class="d-block">No Monthly Attendance Data</strong>
                    <span class="cell-sub">No completed class schedules were found after the enrollment date.</span>
                </div>
            @endif
        </div>
    </div>

    {{-- MONTHLY ATTENDANCE HISTORY --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-clock-history text-primary"></i> Monthly Attendance History</h5>
                <p class="nx-card-subtitle">Completed class schedules and attendance by month</p>
            </div>
            <span class="nx-badge nx-badge-blue">{{ $data['total_days'] ?? 0 }} Days</span>
        </div>

        <div class="p-3">
            @if (isset($data['monthly_attendance']) && $data['monthly_attendance']->count() > 0)
                <div class="monthly-history">
                    @foreach ($data['monthly_attendance'] as $month)
                        @php
                            $monthClassDays = (int) ($month['total_days'] ?? 0);
                            $monthAttended = (int) ($month['attended_days'] ?? 0);
                            $monthAbsent = (int) ($month['absent_days'] ?? 0);
                            $monthPercentage = (float) ($month['attendance_percentage'] ?? 0);
                            $monthAttendanceData = $month['attendance_data'] ?? collect();
                        @endphp
                        <div class="monthly-history-card">
                            <div class="monthly-history-header">
                                <div class="monthly-title">
                                    <div class="monthly-icon"><i class="bi bi-calendar3"></i></div>
                                    <div>
                                        <h5>{{ $month['month_name'] ?? $month['month'] }}</h5>
                                        <small>{{ $monthClassDays }} completed {{ $monthClassDays == 1 ? 'class' : 'classes' }}</small>
                                    </div>
                                </div>
                                <div class="monthly-summary">
                                    <div class="mini-stat"><span>Classes</span><strong>{{ $monthClassDays }}</strong></div>
                                    <div class="mini-stat attended-mini"><span>Attended</span><strong>{{ $monthAttended }}</strong></div>
                                    <div class="mini-stat absent-mini"><span>Absent</span><strong>{{ $monthAbsent }}</strong></div>
                                    <div class="monthly-rate">
                                        <strong>{{ number_format($monthPercentage, 2) }}%</strong>
                                        <span>Attendance</span>
                                    </div>
                                </div>
                            </div>

                            @if ($monthAttendanceData->count() > 0)
                                <div class="table-responsive">
                                    <table class="table premium-table mb-0">
                                        <thead>
                                            <tr><th>#</th><th>Date</th><th>Time</th><th>Status</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($monthAttendanceData as $index => $attendance)
                                                <tr>
                                                    <td class="row-number">{{ $index + 1 }}</td>
                                                    <td>
                                                        <i class="bi bi-calendar3 me-1 text-muted"></i>
                                                        {{ $attendance['date'] ?? 'N/A' }}
                                                    </td>
                                                    <td class="cell-sub">
                                                        {{ $attendance['start_time'] ?? '--' }}
                                                        <span class="time-separator">-</span>
                                                        {{ $attendance['end_time'] ?? '--' }}
                                                    </td>
                                                    <td>
                                                        @if ($attendance['attended'])
                                                            <span class="nx-badge nx-badge-green"><i class="bi bi-check-circle-fill"></i> Attended</span>
                                                        @else
                                                            <span class="nx-badge nx-badge-red"><i class="bi bi-x-circle-fill"></i> Absent</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="month-empty">No attendance records for this month.</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-table">
                    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                    <strong class="d-block">No Monthly Attendance Data</strong>
                    <span class="cell-sub">No completed class schedules were found after the enrollment date.</span>
                </div>
            @endif
        </div>
    </div>

    {{-- ENROLLMENT DETAILS --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-info-circle text-primary"></i> Enrollment Details</h5>
                <p class="nx-card-subtitle">Student class enrollment information</p>
            </div>
        </div>
        <div class="p-3">
            <div class="detail-grid">
                <div class="detail-item"><span class="detail-label">Student ID</span><strong class="detail-value">{{ $data['student']->custom_id ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Class</span><strong class="detail-value">{{ $data['class']->class_name ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Category</span><strong class="detail-value">{{ $data['category']->category_name ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Teacher</span><strong class="detail-value">{{ $data['teacher']->initials ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Enrolled Date</span><strong class="detail-value">{{ $data['enrolled_at']?->format('Y-m-d') ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Left Date</span><strong class="detail-value">{{ $data['left_at']?->format('Y-m-d') ?? 'Still Enrolled' }}</strong></div>
            </div>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Attendance Details — page-specific styles only.
       ============================================================ */

    .dashboard-page .monthly-chart-wrapper {
        display: flex;
        align-items: flex-end;
        gap: 1.5rem;
        width: 100%;
        min-height: 360px;
        overflow-x: auto;
        padding: 1.5rem .5rem .5rem;
    }
    .dashboard-page .month-group {
        flex: 0 0 100px;
        min-width: 100px;
        height: 340px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        position: relative;
    }
    .dashboard-page .month-bars {
        width: 100%;
        height: 270px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        gap: 5px;
        padding: 0 5px;
    }
    .dashboard-page .month-bar-column {
        height: 270px;
        width: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        position: relative;
    }
    .dashboard-page .month-bar-value {
        position: absolute;
        top: -25px;
        font-size: .68rem;
        font-weight: 700;
        color: var(--nx-text);
        text-align: center;
        width: 30px;
    }
    .dashboard-page .month-bar-area {
        width: 24px;
        height: 250px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
    }
    .dashboard-page .month-bar {
        width: 22px;
        border-radius: 5px 5px 2px 2px;
        transition: height .5s ease, transform .2s ease;
    }
    .dashboard-page .month-bar:hover { transform: scaleX(1.12); }
    .dashboard-page .class-bar { background: var(--nx-muted); }
    .dashboard-page .attended-bar { background: var(--nx-green); }
    .dashboard-page .absent-bar { background: var(--nx-red); }
    .dashboard-page .month-label {
        margin-top: .75rem;
        font-size: .75rem;
        font-weight: 700;
        color: var(--nx-text);
        white-space: nowrap;
    }
    .dashboard-page .month-percentage {
        margin-top: .3rem;
        padding: .2rem .45rem;
        border-radius: 6px;
        background: #f1f3f7;
        color: var(--nx-muted);
        font-size: .65rem;
        font-weight: 700;
    }
    .dashboard-page .chart-legend {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1.5rem;
        margin-top: .5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--nx-border);
        flex-wrap: wrap;
    }
    .dashboard-page .legend-item {
        display: flex;
        align-items: center;
        gap: .45rem;
        font-size: .78rem;
        color: var(--nx-muted);
        font-weight: 600;
    }
    .dashboard-page .legend-dot {
        width: 10px; height: 10px; border-radius: 50%; display: inline-block;
    }
    .dashboard-page .class-dot { background: var(--nx-muted); }
    .dashboard-page .attended-dot { background: var(--nx-green); }
    .dashboard-page .absent-dot { background: var(--nx-red); }

    .dashboard-page .monthly-history {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .dashboard-page .monthly-history-card {
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }
    .dashboard-page .monthly-history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background: #fafbfd;
        border-bottom: 1px solid var(--nx-border);
        flex-wrap: wrap;
    }
    .dashboard-page .monthly-title {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 180px;
    }
    .dashboard-page .monthly-icon {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        font-size: 1.05rem;
    }
    .dashboard-page .monthly-title h5 {
        margin: 0;
        font-size: .95rem;
        font-weight: 700;
        color: var(--nx-text);
    }
    .dashboard-page .monthly-title small {
        display: block;
        margin-top: .2rem;
        color: var(--nx-muted);
        font-size: .72rem;
    }
    .dashboard-page .monthly-summary {
        display: flex;
        align-items: center;
        gap: .65rem;
        flex-wrap: wrap;
    }
    .dashboard-page .mini-stat {
        min-width: 70px;
        padding: .45rem .65rem;
        border-radius: 8px;
        background: #fff;
        border: 1px solid var(--nx-border);
        text-align: center;
    }
    .dashboard-page .mini-stat span {
        display: block;
        color: var(--nx-muted);
        font-size: .65rem;
        font-weight: 600;
    }
    .dashboard-page .mini-stat strong {
        display: block;
        margin-top: .1rem;
        color: var(--nx-text);
        font-size: .85rem;
    }
    .dashboard-page .attended-mini {
        background: var(--nx-green-soft);
        border-color: rgba(16, 163, 127, .2);
    }
    .dashboard-page .attended-mini strong { color: var(--nx-green); }
    .dashboard-page .absent-mini {
        background: var(--nx-red-soft);
        border-color: rgba(220, 38, 38, .2);
    }
    .dashboard-page .absent-mini strong { color: var(--nx-red); }
    .dashboard-page .monthly-rate {
        min-width: 85px;
        text-align: center;
        padding-left: .6rem;
        border-left: 1px solid var(--nx-border);
    }
    .dashboard-page .monthly-rate strong {
        display: block;
        color: var(--nx-text);
        font-size: .9rem;
        font-weight: 700;
    }
    .dashboard-page .monthly-rate span {
        display: block;
        margin-top: .1rem;
        color: var(--nx-muted);
        font-size: .62rem;
    }
    .dashboard-page .month-empty {
        padding: 1rem;
        color: var(--nx-muted);
        font-size: .8rem;
        text-align: center;
    }
    .dashboard-page .time-separator {
        color: var(--nx-muted);
        margin: 0 .25rem;
    }

    .dashboard-page .detail-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    .dashboard-page .detail-item {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        padding: .85rem 1rem;
    }
    .dashboard-page .detail-label {
        display: block;
        color: var(--nx-muted);
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .dashboard-page .detail-value {
        display: block;
        margin-top: .25rem;
        font-size: .88rem;
        font-weight: 600;
        color: var(--nx-text);
    }

    @media (max-width: 992px) {
        .dashboard-page .detail-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .dashboard-page .monthly-chart-wrapper { gap: 1rem; min-height: 330px; }
        .dashboard-page .month-group { flex-basis: 85px; min-width: 85px; height: 310px; }
        .dashboard-page .month-bars { height: 250px; }
        .dashboard-page .month-bar-column { height: 250px; width: 20px; }
        .dashboard-page .month-bar-area { height: 230px; }
        .dashboard-page .month-bar { width: 18px; }
        .dashboard-page .month-bar-value { font-size: .62rem; }
        .dashboard-page .month-label { font-size: .68rem; }

        .dashboard-page .monthly-history-header {
            flex-direction: column;
            align-items: stretch;
        }
        .dashboard-page .monthly-summary {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }
        .dashboard-page .mini-stat { min-width: 0; }
        .dashboard-page .monthly-rate {
            min-width: 0;
            border-left: 0;
            padding-left: 0;
            padding-top: .45rem;
            border-top: 1px solid var(--nx-border);
        }
    }
    @media (max-width: 576px) {
        .dashboard-page .detail-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush