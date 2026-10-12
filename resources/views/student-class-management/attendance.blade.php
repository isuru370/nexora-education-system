@extends('layouts.app')

@section('title', 'Attendance Details - ' . ($data['student']->full_name ?? 'Student'))
@section('page-title', 'Attendance Details')

@section('content')

    <div class="details-page">

        {{-- =========================================================
         TOP CARD
    ========================================================== --}}

        <div class="top-card">

            <div>

                <div class="eyebrow">
                    <i class="bi bi-calendar-check"></i>
                    Student Attendance
                </div>

                <h3>
                    {{ $data['student']->initial_name ?? ($data['student']->full_name ?? 'Student') }}
                </h3>

                <p>
                    {{ $data['class']->class_name ?? 'N/A' }}

                    <span class="dot">•</span>

                    {{ $data['category']->category_name ?? 'N/A' }}
                </p>

            </div>

            <a href="{{ route('admin.student-class-management.show', $data['student']->id) }}"
                class="btn btn-light border custom-btn">
                <i class="bi bi-arrow-left"></i>
                Back to Classes
            </a>

        </div>


        {{-- =========================================================
         SUMMARY CARDS
    ========================================================== --}}

        <div class="info-grid">

            {{-- Class Days --}}
            <div class="info-card">

                <div class="info-icon">
                    <i class="bi bi-calendar-week"></i>
                </div>

                <span>Class Days</span>

                <strong>
                    {{ $data['total_days'] ?? 0 }}
                </strong>

                <small>
                    Completed classes after enrollment
                </small>

            </div>


            {{-- Attended --}}
            <div class="info-card success">

                <div class="info-icon success-icon">
                    <i class="bi bi-check-circle"></i>
                </div>

                <span>Attended</span>

                <strong>
                    {{ $data['attended_days'] ?? 0 }}
                </strong>

                <small>
                    Attendance marked
                </small>

            </div>


            {{-- Absent --}}
            <div class="info-card danger">

                <div class="info-icon danger-icon">
                    <i class="bi bi-x-circle"></i>
                </div>

                <span>Absent</span>

                <strong>
                    {{ $data['absent_days'] ?? 0 }}
                </strong>

                <small>
                    Derived from class days
                </small>

            </div>


            {{-- Attendance Rate --}}
            <div class="info-card">

                <div class="info-icon percentage-icon">
                    <i class="bi bi-percent"></i>
                </div>

                <span>Attendance Rate</span>

                <strong>
                    {{ number_format((float) ($data['attendance_percentage'] ?? 0), 2) }}%
                </strong>

                <small>
                    Attended / class days
                </small>

            </div>

        </div>


        {{-- =========================================================
         MONTHLY ATTENDANCE BAR CHART
    ========================================================== --}}

        <div class="main-card chart-card">

            <div class="section-header">

                <div>
                    <h4>Monthly Attendance Overview</h4>
                    <p>Completed classes, attended and absent by month</p>
                </div>

                <div class="chart-rate">
                    <i class="bi bi-graph-up-arrow"></i>
                    {{ number_format((float) ($data['attendance_percentage'] ?? 0), 2) }}%
                </div>

            </div>

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

                            <div class="month-label">
                                {{ $month['month_name'] ?? $month['month'] }}
                            </div>

                            <div class="month-percentage">
                                {{ number_format((float) ($month['attendance_percentage'] ?? 0), 0) }}%
                            </div>

                        </div>
                    @endforeach

                </div>

                <div class="chart-legend">

                    <div class="legend-item">
                        <span class="legend-dot class-dot"></span>
                        <span>Class Days</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-dot attended-dot"></span>
                        <span>Attended</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-dot absent-dot"></span>
                        <span>Absent</span>
                    </div>

                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-bar-chart"></i>
                    <h5>No Monthly Attendance Data</h5>
                    <p>
                        No completed class schedules were found
                        after the enrollment date.
                    </p>
                </div>

            @endif

        </div>


        {{-- =========================================================
         MONTH-WISE ATTENDANCE HISTORY
    ========================================================== --}}

        <div class="main-card">

            <div class="section-header">

                <div>
                    <h4>Monthly Attendance History</h4>
                    <p>Completed class schedules and attendance by month</p>
                </div>

                <span class="count-badge">
                    {{ $data['total_days'] ?? 0 }} Days
                </span>

            </div>


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

                            {{-- Month Header --}}
                            <div class="monthly-history-header">

                                <div class="monthly-title">

                                    <div class="monthly-icon">
                                        <i class="bi bi-calendar3"></i>
                                    </div>

                                    <div>
                                        <h5>
                                            {{ $month['month_name'] ?? $month['month'] }}
                                        </h5>

                                        <small>
                                            {{ $monthClassDays }} completed
                                            {{ $monthClassDays == 1 ? 'class' : 'classes' }}
                                        </small>
                                    </div>

                                </div>


                                <div class="monthly-summary">

                                    <div class="mini-stat">
                                        <span>Classes</span>
                                        <strong>{{ $monthClassDays }}</strong>
                                    </div>

                                    <div class="mini-stat attended-mini">
                                        <span>Attended</span>
                                        <strong>{{ $monthAttended }}</strong>
                                    </div>

                                    <div class="mini-stat absent-mini">
                                        <span>Absent</span>
                                        <strong>{{ $monthAbsent }}</strong>
                                    </div>

                                    <div class="monthly-rate">
                                        <strong>
                                            {{ number_format($monthPercentage, 2) }}%
                                        </strong>
                                        <span>Attendance</span>
                                    </div>

                                </div>

                            </div>


                            {{-- Month Attendance Table --}}
                            @if ($monthAttendanceData->count() > 0)
                                <div class="table-responsive">

                                    <table class="table details-table monthly-details-table align-middle mb-0">

                                        <thead>

                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Status</th>
                                            </tr>

                                        </thead>

                                        <tbody>

                                            @foreach ($monthAttendanceData as $index => $attendance)
                                                <tr>

                                                    <td>
                                                        {{ $index + 1 }}
                                                    </td>

                                                    <td>
                                                        <i class="bi bi-calendar3 me-1"></i>
                                                        {{ $attendance['date'] ?? 'N/A' }}
                                                    </td>

                                                    <td>
                                                        {{ $attendance['start_time'] ?? '--' }}

                                                        <span class="time-separator">
                                                            -
                                                        </span>

                                                        {{ $attendance['end_time'] ?? '--' }}
                                                    </td>

                                                    <td>

                                                        @if ($attendance['attended'])
                                                            <span class="status-badge attended-badge">
                                                                <i class="bi bi-check-circle-fill"></i>
                                                                Attended
                                                            </span>
                                                        @else
                                                            <span class="status-badge absent-badge">
                                                                <i class="bi bi-x-circle-fill"></i>
                                                                Absent
                                                            </span>
                                                        @endif

                                                    </td>

                                                </tr>
                                            @endforeach

                                        </tbody>

                                    </table>

                                </div>
                            @else
                                <div class="month-empty">
                                    No attendance records for this month.
                                </div>
                            @endif

                        </div>
                    @endforeach

                </div>
            @else
                <div class="empty-state">

                    <i class="bi bi-calendar-x"></i>

                    <h5>
                        No Monthly Attendance Data
                    </h5>

                    <p>
                        No completed class schedules were found
                        after the enrollment date.
                    </p>

                </div>

            @endif

        </div>



        {{-- =========================================================
         ENROLLMENT DETAILS
    ========================================================== --}}

        <div class="main-card">

            <div class="section-header">

                <div>

                    <h4>
                        Enrollment Details
                    </h4>

                    <p>
                        Student class enrollment information
                    </p>

                </div>

            </div>


            <div class="detail-grid">


                <div>

                    <span>
                        Student ID
                    </span>

                    <strong>
                        {{ $data['student']->custom_id ?? 'N/A' }}
                    </strong>

                </div>


                <div>

                    <span>
                        Class
                    </span>

                    <strong>
                        {{ $data['class']->class_name ?? 'N/A' }}
                    </strong>

                </div>


                <div>

                    <span>
                        Category
                    </span>

                    <strong>
                        {{ $data['category']->category_name ?? 'N/A' }}
                    </strong>

                </div>


                <div>

                    <span>
                        Teacher
                    </span>

                    <strong>
                        {{ $data['teacher']->initials ?? 'N/A' }}
                    </strong>

                </div>


                <div>

                    <span>
                        Enrolled Date
                    </span>

                    <strong>

                        {{ $data['enrolled_at']?->format('Y-m-d') ?? 'N/A' }}

                    </strong>

                </div>


                <div>

                    <span>
                        Left Date
                    </span>

                    <strong>

                        {{ $data['left_at']?->format('Y-m-d') ?? 'Still Enrolled' }}

                    </strong>

                </div>


            </div>

        </div>

    </div>

@endsection



@push('styles')
    <style>
        /* =========================================================
           PAGE
        ========================================================= */

        .details-page {
            animation: fadeIn .35s ease;
        }


        /* =========================================================
           CARDS
        ========================================================= */

        .top-card,
        .main-card,
        .info-card {

            background: #fff;

            border: 1px solid #eef2f7;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, .05);

        }


        .top-card {

            border-radius: 24px;

            padding: 1.5rem;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 1rem;

            margin-bottom: 1rem;
        }


        .main-card {

            border-radius: 24px;

            padding: 1.5rem;

            margin-bottom: 1rem;
        }


        /* =========================================================
           TOP CARD
        ========================================================= */

        .eyebrow {

            font-size: .78rem;

            text-transform: uppercase;

            letter-spacing: .08em;

            color: #64748b;

            font-weight: 700;
        }


        .top-card h3 {

            margin: .3rem 0;

            font-weight: 800;

            color: #1e293b;
        }


        .top-card p {

            margin: 0;

            color: #64748b;
        }


        .dot {

            margin: 0 .35rem;
        }


        .custom-btn {

            border-radius: 13px;

            padding: .65rem 1rem;

            font-weight: 600;
        }



        /* =========================================================
           SUMMARY CARDS
        ========================================================= */

        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 1rem;

            margin-bottom: 1rem;
        }


        .info-card {

            position: relative;

            border-radius: 20px;

            padding: 1.25rem;

            overflow: hidden;
        }


        .info-card.success {

            border-left: 4px solid #10b981;
        }


        .info-card.danger {

            border-left: 4px solid #ef4444;
        }


        .info-card span {

            display: block;

            color: #64748b;

            font-size: .8rem;

            font-weight: 600;
        }


        .info-card strong {

            display: block;

            font-size: 1.6rem;

            margin: .35rem 0;

            color: #1e293b;
        }


        .info-card small {

            color: #94a3b8;
        }


        .info-icon {

            width: 40px;

            height: 40px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 12px;

            background: #f1f5f9;

            color: #475569;

            margin-bottom: .8rem;

            font-size: 1.1rem;
        }


        .success-icon {

            background: #ecfdf5;

            color: #059669;
        }


        .danger-icon {

            background: #fef2f2;

            color: #dc2626;
        }


        .percentage-icon {

            background: #f8fafc;

            color: #475569;
        }



        /* =========================================================
           SECTION HEADER
        ========================================================= */

        .section-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 1rem;

            margin-bottom: 1.2rem;
        }


        .section-header h4 {

            margin: 0;

            font-weight: 750;

            color: #1e293b;
        }


        .section-header p {

            margin: .2rem 0 0;

            color: #64748b;
        }


        .count-badge {

            background: #f8fafc;

            border-radius: 10px;

            padding: .45rem .7rem;

            color: #475569;

            font-size: .8rem;

            font-weight: 700;
        }


        .chart-rate {

            display: inline-flex;

            align-items: center;

            gap: .4rem;

            padding: .5rem .75rem;

            border-radius: 12px;

            background: #f8fafc;

            color: #475569;

            font-size: .8rem;

            font-weight: 700;
        }



        /* =========================================================
           MONTHLY ATTENDANCE BAR CHART
        ========================================================= */

        .chart-card {
            overflow: hidden;
        }

        .monthly-chart-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 1.5rem;
            width: 100%;
            min-height: 360px;
            overflow-x: auto;
            padding: 1.5rem .5rem .5rem;
        }

        .month-group {
            flex: 0 0 100px;
            min-width: 100px;
            height: 340px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            position: relative;
        }

        .month-bars {
            width: 100%;
            height: 270px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 5px;
            padding: 0 5px;
        }

        .month-bar-column {
            height: 270px;
            width: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            position: relative;
        }

        .month-bar-value {
            position: absolute;
            top: -25px;
            font-size: .68rem;
            font-weight: 800;
            color: #334155;
            text-align: center;
            width: 30px;
        }

        .month-bar-area {
            width: 24px;
            height: 250px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .month-bar {
            width: 22px;
            border-radius: 5px 5px 2px 2px;
            transition: height .5s ease, transform .2s ease;
        }

        .month-bar:hover {
            transform: scaleX(1.12);
        }

        .class-bar {
            background: #64748b;
        }

        .attended-bar {
            background: #10b981;
        }

        .absent-bar {
            background: #ef4444;
        }

        .month-label {
            margin-top: .75rem;
            font-size: .75rem;
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
        }

        .month-percentage {
            margin-top: .3rem;
            padding: .2rem .45rem;
            border-radius: 7px;
            background: #f8fafc;
            color: #64748b;
            font-size: .65rem;
            font-weight: 700;
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
            margin-top: .5rem;
            padding-top: 1rem;
            border-top: 1px solid #f1f5f9;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .78rem;
            color: #64748b;
            font-weight: 600;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .class-dot {
            background: #64748b;
        }

        .attended-dot {
            background: #10b981;
        }

        .absent-dot {
            background: #ef4444;
        }


        /* =========================================================
           TABLE
        ========================================================= */

        .details-table thead th {

            background: #f8fafc;

            border: 0;

            color: #64748b;

            font-size: .76rem;

            text-transform: uppercase;

            padding: .9rem;
        }


        .details-table td {

            padding: .9rem;

            border-color: #f1f5f9;
        }


        .time-separator {

            color: #94a3b8;

            margin:
                0 .25rem;
        }



        /* =========================================================
           STATUS
        ========================================================= */

        .status-badge {

            display: inline-flex;

            align-items: center;

            gap: .35rem;

            border-radius: 10px;

            padding: .45rem .7rem;

            font-size: .75rem;

            font-weight: 700;
        }


        .attended-badge {

            background: #ecfdf5;

            color: #059669;
        }


        .absent-badge {

            background: #fef2f2;

            color: #dc2626;
        }



        /* =========================================================
           MONTHLY ATTENDANCE HISTORY
        ========================================================= */

        .monthly-history {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .monthly-history-card {
            border: 1px solid #eef2f7;
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
        }

        .monthly-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.1rem;
            background: #f8fafc;
            border-bottom: 1px solid #eef2f7;
        }

        .monthly-title {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-width: 180px;
        }

        .monthly-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2f7;
            color: #475569;
            font-size: 1.05rem;
        }

        .monthly-title h5 {
            margin: 0;
            font-size: .95rem;
            font-weight: 800;
            color: #1e293b;
        }

        .monthly-title small {
            display: block;
            margin-top: .2rem;
            color: #94a3b8;
            font-size: .72rem;
        }

        .monthly-summary {
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .mini-stat {
            min-width: 70px;
            padding: .45rem .65rem;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .mini-stat span {
            display: block;
            color: #94a3b8;
            font-size: .65rem;
            font-weight: 600;
        }

        .mini-stat strong {
            display: block;
            margin-top: .1rem;
            color: #334155;
            font-size: .85rem;
        }

        .attended-mini {
            background: #ecfdf5;
            border-color: #d1fae5;
        }

        .attended-mini strong {
            color: #059669;
        }

        .absent-mini {
            background: #fef2f2;
            border-color: #fee2e2;
        }

        .absent-mini strong {
            color: #dc2626;
        }

        .monthly-rate {
            min-width: 85px;
            text-align: center;
            padding-left: .6rem;
            border-left: 1px solid #e2e8f0;
        }

        .monthly-rate strong {
            display: block;
            color: #334155;
            font-size: .9rem;
            font-weight: 800;
        }

        .monthly-rate span {
            display: block;
            margin-top: .1rem;
            color: #94a3b8;
            font-size: .62rem;
        }

        .monthly-details-table {
            margin: 0;
        }

        .monthly-details-table thead th {
            background: #fff;
            font-size: .68rem;
            padding: .7rem 1rem;
        }

        .monthly-details-table td {
            padding: .75rem 1rem;
            font-size: .82rem;
        }

        .month-empty {
            padding: 1rem;
            color: #94a3b8;
            font-size: .8rem;
            text-align: center;
        }


        /* =========================================================
           ENROLLMENT DETAILS
        ========================================================= */

        .detail-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 1rem;
        }


        .detail-grid>div {

            background: #f8fafc;

            border-radius: 14px;

            padding: 1rem;
        }


        .detail-grid span {

            display: block;

            color: #64748b;

            font-size: .8rem;
        }


        .detail-grid strong {

            display: block;

            margin-top: .25rem;

            color: #1e293b;
        }



        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty-state {

            text-align: center;

            padding: 3rem 1rem;

            color: #64748b;
        }


        .empty-state i {

            font-size: 3rem;

            color: #cbd5e1;
        }


        .empty-state h5 {

            color: #334155;

            font-weight: 700;

            margin:
                .8rem 0 .3rem;
        }



        /* =========================================================
           ANIMATION
        ========================================================= */

        @keyframes fadeIn {

            from {

                opacity: 0;

                transform:
                    translateY(8px);

            }

            to {

                opacity: 1;

                transform:
                    translateY(0);

            }
        }



        /* =========================================================
           RESPONSIVE - TABLET
        ========================================================= */

        @media (max-width: 992px) {

            .info-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }


            .detail-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }



        /* =========================================================
           MONTHLY CHART MOBILE
        ========================================================= */

        @media (max-width: 768px) {

            .monthly-chart-wrapper {
                gap: 1rem;
                min-height: 330px;
            }

            .month-group {
                flex-basis: 85px;
                min-width: 85px;
                height: 310px;
            }

            .month-bars {
                height: 250px;
            }

            .month-bar-column {
                height: 250px;
                width: 20px;
            }

            .month-bar-area {
                height: 230px;
            }

            .month-bar {
                width: 18px;
            }

            .month-bar-value {
                font-size: .62rem;
            }

            .month-label {
                font-size: .68rem;
            }
        }


        /* =========================================================
           MONTHLY HISTORY MOBILE
        ========================================================= */

        @media (max-width: 768px) {

            .monthly-history-header {
                flex-direction: column;
                align-items: stretch;
            }

            .monthly-summary {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(4, 1fr);
            }

            .mini-stat {
                min-width: 0;
            }

            .monthly-rate {
                min-width: 0;
                border-left: 0;
                padding-left: 0;
                padding-top: .45rem;
                border-top: 1px solid #e2e8f0;
            }

        }


        /* =========================================================
           RESPONSIVE - MOBILE
        ========================================================= */

        @media (max-width: 576px) {

            .top-card {

                flex-direction: column;

                align-items: stretch;

            }


            .info-grid,
            .detail-grid {

                grid-template-columns: 1fr;

            }

            .main-card,
            .top-card {

                padding: 1rem;

            }


            .section-header {

                align-items: flex-start;

            }


            .chart-rate {

                display: none;

            }


            .attendance-chart {

                height: 310px;

            }


            .chart-y-axis {

                width: 35px;

            }


            .y-axis-value {

                font-size: .65rem;

                padding-right: .4rem;

            }


            .chart-column {

                width: 75px;

            }


            .bar-wrapper {

                width: 45px;

            }


            .vertical-bar {

                width: 45px;

            }


            .bar-label {

                width: 75px;

                font-size: .65rem;

            }


            .label-icon {

                width: 24px;

                height: 24px;

                font-size: .7rem;

            }


            .bars-container {

                padding: 0;

            }


            .bar-value {

                font-size: .8rem;

            }


            .chart-legend {

                gap: .7rem;

                flex-wrap: wrap;

            }

        }
    </style>
@endpush
