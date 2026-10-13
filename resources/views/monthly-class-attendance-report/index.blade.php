@extends('layouts.app')

@section('content')
    <div class="attendance-report-page">

        {{-- ============================================= --}}
        {{-- HEADER SECTION --}}
        {{-- ============================================= --}}
        <div class="report-header-wrapper">
            <div class="report-header">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <h1 class="report-title">
                            Monthly Class Attendance Report
                            <span class="title-badge">{{ $report['month_name'] }}</span>
                        </h1>
                        <p class="report-subtitle">
                            <i class="bi bi-clock-history me-1"></i>
                            Detailed attendance summary for all classes and categories
                        </p>
                    </div>
                </div>

                <div class="header-right">
                    {{-- Month Filter --}}
                    <form method="GET" 
                          action="{{ route('admin.monthly-class-attendance-report.index') }}" 
                          class="filter-form">
                        <div class="filter-group">
                            <i class="bi bi-calendar-range"></i>
                            <select name="month" class="filter-select" onchange="this.form.submit()">
                                @foreach ($availableMonths as $month)
                                    <option value="{{ $month['value'] }}" 
                                        {{ $paymentMonth == $month['value'] ? 'selected' : '' }}>
                                        {{ $month['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    {{-- Export Button --}}
                    <a href="{{ route('admin.monthly-class-attendance-report.export', ['month' => $paymentMonth]) }}" 
                       class="btn-export">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Export Excel</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ============================================= --}}
        {{-- SUMMARY STATS CARDS --}}
        {{-- ============================================= --}}
        <div class="stats-grid">
            {{-- Total Classes --}}
            <div class="stats-card stats-card-classes">
                <div class="stats-card-inner">
                    <div class="stats-icon">
                        <i class="bi bi-book"></i>
                    </div>
                    <div class="stats-content">
                        <span class="stats-label">Total Classes</span>
                        <h3 class="stats-value">{{ $report['summary']['total_classes'] }}</h3>
                        <div class="stats-meta">
                            <span class="meta-badge">
                                <i class="bi bi-grid"></i>
                                Active
                            </span>
                        </div>
                    </div>
                </div>
                <div class="stats-progress-bar">
                    <div class="progress-fill" style="width: 100%"></div>
                </div>
            </div>

            {{-- Total Students --}}
            <div class="stats-card stats-card-students">
                <div class="stats-card-inner">
                    <div class="stats-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-content">
                        <span class="stats-label">Total Students</span>
                        <h3 class="stats-value">{{ $report['summary']['total_students'] }}</h3>
                        <div class="stats-meta">
                            <span class="meta-badge">
                                <i class="bi bi-person"></i>
                                Enrolled
                            </span>
                        </div>
                    </div>
                </div>
                <div class="stats-progress-bar">
                    <div class="progress-fill" style="width: 100%"></div>
                </div>
            </div>

            {{-- New Students --}}
            <div class="stats-card stats-card-new">
                <div class="stats-card-inner">
                    <div class="stats-icon">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div class="stats-content">
                        <span class="stats-label">New Students</span>
                        <h3 class="stats-value">{{ $report['summary']['new_students'] }}</h3>
                        <div class="stats-meta">
                            <span class="meta-badge success">
                                <i class="bi bi-arrow-up"></i>
                                {{ $report['summary']['total_students'] > 0 
                                    ? number_format(($report['summary']['new_students'] / $report['summary']['total_students']) * 100, 1) 
                                    : 0 }}% of total
                            </span>
                        </div>
                    </div>
                </div>
                <div class="stats-progress-bar">
                    <div class="progress-fill" style="width: {{ $report['summary']['total_students'] > 0 ? min(($report['summary']['new_students'] / $report['summary']['total_students']) * 100, 100) : 0 }}%; background: linear-gradient(90deg, #8b5cf6, #6d28d9);"></div>
                </div>
            </div>

            {{-- Attendance Rate --}}
            <div class="stats-card stats-card-rate">
                <div class="stats-card-inner">
                    <div class="stats-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="stats-content">
                        <span class="stats-label">Attendance Rate</span>
                        <h3 class="stats-value">{{ number_format($report['summary']['attendance_rate'], 1) }}%</h3>
                        <div class="stats-meta">
                            <span class="meta-badge {{ $report['summary']['attendance_rate'] >= 80 ? 'success' : ($report['summary']['attendance_rate'] >= 60 ? 'warning' : 'danger') }}">
                                <i class="bi {{ $report['summary']['attendance_rate'] >= 80 ? 'bi-check-circle' : ($report['summary']['attendance_rate'] >= 60 ? 'bi-exclamation-triangle' : 'bi-x-circle') }}"></i>
                                {{ $report['summary']['attendance_rate'] >= 80 ? 'Excellent' : ($report['summary']['attendance_rate'] >= 60 ? 'Average' : 'Needs Improvement') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="stats-progress-bar">
                    <div class="progress-fill" style="width: {{ min($report['summary']['attendance_rate'], 100) }}%; background: {{ $report['summary']['attendance_rate'] >= 80 ? 'linear-gradient(90deg, #10b981, #34d399)' : ($report['summary']['attendance_rate'] >= 60 ? 'linear-gradient(90deg, #f59e0b, #fbbf24)' : 'linear-gradient(90deg, #ef4444, #f87171)') }};"></div>
                </div>
            </div>
        </div>

        {{-- ============================================= --}}
        {{-- QUICK STATS BAR --}}
        {{-- ============================================= --}}
        <div class="quick-stats-bar">
            <div class="quick-stat-item">
                <span class="quick-stat-label">
                    <i class="bi bi-calendar-check text-primary"></i>
                    Month
                </span>
                <span class="quick-stat-value">{{ $report['month_name'] }}</span>
            </div>
            <div class="quick-stat-divider"></div>
            <div class="quick-stat-item">
                <span class="quick-stat-label">
                    <i class="bi bi-person-check text-success"></i>
                    Present
                </span>
                <span class="quick-stat-value">
                    {{ array_sum(array_map(function($class) { 
                        return array_sum(array_map(function($cat) { 
                            return array_sum(array_column($cat['students'] ?? [], 'attended')); 
                        }, $class['categories'] ?? [])); 
                    }, $report['classes'] ?? [])) }}
                </span>
            </div>
            <div class="quick-stat-divider"></div>
            <div class="quick-stat-item">
                <span class="quick-stat-label">
                    <i class="bi bi-person-x text-danger"></i>
                    Absent
                </span>
                <span class="quick-stat-value">
                    {{ array_sum(array_map(function($class) { 
                        return array_sum(array_map(function($cat) { 
                            return array_sum(array_column($cat['students'] ?? [], 'absent')); 
                        }, $class['categories'] ?? [])); 
                    }, $report['classes'] ?? [])) }}
                </span>
            </div>
            <div class="quick-stat-divider"></div>
            <div class="quick-stat-item">
                <span class="quick-stat-label">
                    <i class="bi bi-calendar-week text-info"></i>
                    Total Class Days
                </span>
                <span class="quick-stat-value">
                    {{ array_sum(array_map(function($class) { 
                        return array_sum(array_map(function($cat) { 
                            return $cat['class_days'] ?? 0; 
                        }, $class['categories'] ?? [])); 
                    }, $report['classes'] ?? [])) }}
                </span>
            </div>
        </div>

        {{-- ============================================= --}}
        {{-- CLASS CARDS --}}
        {{-- ============================================= --}}
        @foreach ($report['classes'] as $classIndex => $class)
            <div class="class-card">
                {{-- Class Header --}}
                <div class="class-header">
                    <div class="class-info">
                        <div class="class-icon">
                            <span class="class-initial">{{ substr($class['class_name'], 0, 2) }}</span>
                        </div>
                        <div>
                            <h5 class="class-name">{{ $class['class_name'] }}</h5>
                            <div class="class-meta">
                                <span class="meta-tag">
                                    <i class="bi bi-mortarboard"></i>
                                    {{ $class['grade_name'] ?: 'N/A' }}
                                </span>
                                <span class="meta-tag">
                                    <i class="bi bi-person-badge"></i>
                                    {{ $class['teacher_name'] ?: 'N/A' }}
                                </span>
                                <span class="meta-tag">
                                    <i class="bi bi-people"></i>
                                    {{ $class['total_students'] }} Students
                                </span>
                                <span class="meta-tag">
                                    <i class="bi bi-person-plus"></i>
                                    {{ $class['new_students'] }} New
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="class-rate">
                        <div class="rate-circle">
                            <svg class="rate-circle-svg" viewBox="0 0 36 36">
                                <path class="rate-circle-bg"
                                      d="M18 2.0845
                                         a 15.9155 15.9155 0 0 1 0 31.831
                                         a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="rate-circle-fill"
                                      d="M18 2.0845
                                         a 15.9155 15.9155 0 0 1 0 31.831
                                         a 15.9155 15.9155 0 0 1 0 -31.831"
                                      stroke-dasharray="{{ $class['attendance_rate'] }}, 100"/>
                            </svg>
                            <span class="rate-text">{{ number_format($class['attendance_rate'], 1) }}%</span>
                        </div>
                    </div>
                </div>

                {{-- Categories --}}
                <div class="class-body">
                    @foreach ($class['categories'] as $categoryIndex => $category)
                        <div class="category-section">
                            {{-- Category Header --}}
                            <div class="category-header">
                                <div class="category-info">
                                    <span class="category-dot" style="background: {{ ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316'][$categoryIndex % 8] }};"></span>
                                    <h6 class="category-name">{{ $category['category_name'] }}</h6>
                                    <span class="student-count-badge">
                                        <i class="bi bi-people"></i>
                                        {{ count($category['students']) }} students
                                    </span>
                                </div>
                                <div class="category-stats">
                                    <span class="category-stat">
                                        <i class="bi bi-person-plus text-success"></i>
                                        {{ $category['new_students'] }} New
                                    </span>
                                    <span class="category-stat">
                                        <i class="bi bi-calendar3"></i>
                                        {{ $category['class_days'] }} Days
                                    </span>
                                    <span class="category-stat attendance-stat {{ $category['attendance_rate'] >= 80 ? 'high' : ($category['attendance_rate'] >= 60 ? 'medium' : 'low') }}">
                                        <i class="bi bi-percent"></i>
                                        {{ number_format($category['attendance_rate'], 1) }}%
                                    </span>
                                </div>
                            </div>

                            {{-- ========================================== --}}
                            {{-- ✅ SCROLLABLE TABLE - FIXED HEIGHT --}}
                            {{-- ========================================== --}}
                            <div class="table-scroll-wrapper">
                                <table class="attendance-table">
                                    <thead>
                                        <tr>
                                            <th class="col-student">Student</th>
                                            <th class="col-id">ID</th>
                                            <th class="col-days text-center">Class Days</th>
                                            <th class="col-attended text-center">Attended</th>
                                            <th class="col-absent text-center">Absent</th>
                                            <th class="col-percentage text-center">Attendance %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($category['students'] as $student)
                                            @php
                                                $percent = $student['attendance_percentage'];
                                                $statusClass = $percent >= 80 ? 'high' : ($percent >= 60 ? 'medium' : 'low');
                                            @endphp
                                            <tr class="student-row {{ $statusClass }}">
                                                <td class="col-student">
                                                    <div class="student-info">
                                                        <div class="student-avatar">
                                                            {{ strtoupper(substr($student['student_name'], 0, 1)) }}
                                                        </div>
                                                        <span class="student-name">{{ $student['student_name'] }}</span>
                                                        @if ($student['is_new_student'])
                                                            <span class="new-badge">New</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="col-id">
                                                    <span class="id-badge">{{ $student['student_custom_id'] }}</span>
                                                </td>
                                                <td class="col-days text-center">
                                                    <span class="day-badge">{{ $student['class_days'] }}</span>
                                                </td>
                                                <td class="col-attended text-center">
                                                    <span class="attended-badge">{{ $student['attended'] }}</span>
                                                </td>
                                                <td class="col-absent text-center">
                                                    <span class="absent-badge">{{ $student['absent'] }}</span>
                                                </td>
                                                <td class="col-percentage text-center">
                                                    <span class="percent-badge {{ $statusClass }}">
                                                        {{ number_format($student['attendance_percentage'], 1) }}%
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="empty-cell">
                                                    <div class="empty-student">
                                                        <i class="bi bi-inbox"></i>
                                                        <span>No students found in this category</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{-- END SCROLLABLE TABLE --}}

                            {{-- Table Footer - Student Count --}}
                            @if(count($category['students']) > 0)
                                <div class="table-footer">
                                    <span class="footer-info">
                                        <i class="bi bi-person-check"></i>
                                        Showing <strong>{{ count($category['students']) }}</strong> students
                                    </span>
                                    <span class="footer-info">
                                        <i class="bi bi-arrow-up-short"></i>
                                        Scroll for more
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- No Data --}}
        @if (empty($report['classes']))
            <div class="empty-state">
                <div class="empty-state-content">
                    <i class="bi bi-calendar-x"></i>
                    <h5>No Attendance Data</h5>
                    <p>No attendance records found for <strong>{{ $report['month_name'] }}</strong></p>
                    <small class="text-muted">Please try selecting a different month</small>
                </div>
            </div>
        @endif

    </div>

    {{-- ============================================= --}}
    {{-- STYLES --}}
    {{-- ============================================= --}}
    @push('styles')
    <style>
        /* ==========================================
           PAGE CONTAINER
           ========================================== */
        .attendance-report-page {
            padding: 1.5rem;
            max-width: 1600px;
            margin: 0 auto;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ==========================================
           HEADER
           ========================================== */
        .report-header-wrapper {
            margin-bottom: 2rem;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            background: #ffffff;
            padding: 1.5rem 2rem;
            border-radius: 20px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .header-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.6rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .report-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .title-badge {
            font-size: 0.7rem;
            font-weight: 600;
            background: #eef2ff;
            color: #4f46e5;
            padding: 0.25rem 0.9rem;
            border-radius: 20px;
        }

        .report-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-form {
            margin: 0;
        }

        .filter-group {
            position: relative;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .filter-group:focus-within {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: #ffffff;
        }

        .filter-group i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            z-index: 2;
        }

        .filter-select {
            min-height: 44px;
            min-width: 180px;
            padding: 0.5rem 2.5rem 0.5rem 2.8rem;
            background: transparent;
            border: none;
            border-radius: 12px;
            font-size: 0.9rem;
            color: #0f172a;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 2.5rem;
        }

        .filter-select:focus {
            outline: none;
        }

        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #059669, #10b981);
            color: #ffffff;
            padding: 0.6rem 1.4rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            transition: all 0.25s ease;
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(5, 150, 105, 0.4);
            color: #fff;
        }

        /* ==========================================
           STATS GRID
           ========================================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stats-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 1.5rem 1.75rem;
            border: 1px solid #f1f5f9;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
            border-color: transparent;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stats-card-classes::before {
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
        }
        .stats-card-students::before {
            background: linear-gradient(90deg, #059669, #34d399);
        }
        .stats-card-new::before {
            background: linear-gradient(90deg, #8b5cf6, #6d28d9);
        }
        .stats-card-rate::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .stats-card-inner {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .stats-card-classes .stats-icon {
            background: #eef2ff;
            color: #4f46e5;
        }
        .stats-card-students .stats-icon {
            background: #ecfdf5;
            color: #059669;
        }
        .stats-card-new .stats-icon {
            background: #f3e8ff;
            color: #7c3aed;
        }
        .stats-card-rate .stats-icon {
            background: #fef3c7;
            color: #d97706;
        }

        .stats-content {
            flex: 1;
            min-width: 0;
        }

        .stats-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .stats-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0.2rem 0 0.3rem;
            line-height: 1.2;
        }

        .stats-meta {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .meta-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            background: #f1f5f9;
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .meta-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        .meta-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        .meta-badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .stats-progress-bar {
            margin-top: 1rem;
            height: 3px;
            background: #f1f5f9;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            transition: width 1.5s ease;
        }

        /* ==========================================
           QUICK STATS BAR
           ========================================== */
        .quick-stats-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            background: #ffffff;
            border-radius: 16px;
            padding: 0.8rem 2rem;
            border: 1px solid #f1f5f9;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .quick-stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-stat-label {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .quick-stat-value {
            font-weight: 700;
            font-size: 0.95rem;
            color: #0f172a;
        }

        .quick-stat-divider {
            width: 1px;
            height: 28px;
            background: #e2e8f0;
        }

        /* ==========================================
           CLASS CARDS
           ========================================== */
        .class-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #f1f5f9;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.3s ease;
        }

        .class-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .class-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .class-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .class-name {
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }

        .class-meta {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-top: 0.2rem;
        }

        .meta-tag {
            font-size: 0.7rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 0.1rem 0.6rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .meta-tag i {
            font-size: 0.65rem;
        }

        .class-rate {
            flex-shrink: 0;
        }

        .rate-circle {
            position: relative;
            width: 52px;
            height: 52px;
        }

        .rate-circle-svg {
            width: 52px;
            height: 52px;
            transform: rotate(-90deg);
        }

        .rate-circle-bg {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 3;
        }

        .rate-circle-fill {
            fill: none;
            stroke: #4f46e5;
            stroke-width: 3;
            stroke-linecap: round;
            transition: stroke-dasharray 1s ease;
        }

        .rate-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 0.65rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* ==========================================
           CATEGORY SECTION
           ========================================== */
        .class-body {
            padding: 1.25rem 1.75rem;
        }

        .category-section {
            margin-bottom: 1.5rem;
        }

        .category-section:last-child {
            margin-bottom: 0;
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.8rem;
            background: #fafbfc;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .category-info {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .category-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .category-name {
            font-weight: 600;
            margin: 0;
            color: #0f172a;
            font-size: 0.95rem;
        }

        .student-count-badge {
            font-size: 0.7rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 0.1rem 0.6rem;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .category-stats {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .category-stat {
            font-size: 0.75rem;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            background: #ffffff;
            padding: 0.1rem 0.6rem;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
        }

        .category-stat i {
            font-size: 0.7rem;
        }

        .category-stat.attendance-stat.high {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .category-stat.attendance-stat.medium {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
        .category-stat.attendance-stat.low {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }

        /* ==========================================
           ✅ SCROLLABLE TABLE - FIXED HEIGHT
           ========================================== */
        .table-scroll-wrapper {
            max-height: 320px; /* Fixed height */
            overflow-y: auto;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
            position: relative;
        }

        /* Custom Scrollbar Styling */
        .table-scroll-wrapper::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-scroll-wrapper::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 0 0 12px 12px;
        }

        .table-scroll-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Firefox Scrollbar */
        .table-scroll-wrapper {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            min-width: 600px;
        }

        /* ✅ STICKY HEADER */
        .attendance-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .attendance-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.7rem 1rem;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 21;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .attendance-table tbody td {
            padding: 0.65rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            background: #ffffff;
        }

        .attendance-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Row Status */
        .student-row {
            transition: all 0.2s ease;
        }

        .student-row:hover {
            background: #f8fafc;
        }

        .student-row.high {
            border-left: 3px solid #10b981;
        }
        .student-row.medium {
            border-left: 3px solid #f59e0b;
        }
        .student-row.low {
            border-left: 3px solid #ef4444;
        }

        /* Table Cells */
        .col-student {
            min-width: 170px;
        }
        .col-id {
            min-width: 80px;
        }
        .col-days, .col-attended, .col-absent {
            min-width: 70px;
        }
        .col-percentage {
            min-width: 80px;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .student-avatar {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 0.65rem;
            flex-shrink: 0;
        }

        .student-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 0.88rem;
        }

        .new-badge {
            font-size: 0.55rem;
            font-weight: 700;
            background: #d1fae5;
            color: #065f46;
            padding: 0.05rem 0.5rem;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }

        .id-badge {
            font-size: 0.8rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 0.15rem 0.6rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .day-badge {
            font-weight: 600;
            color: #4f46e5;
        }

        .attended-badge {
            font-weight: 600;
            color: #059669;
        }

        .absent-badge {
            font-weight: 600;
            color: #ef4444;
        }

        .percent-badge {
            display: inline-block;
            padding: 0.15rem 0.7rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.8rem;
            min-width: 55px;
        }

        .percent-badge.high {
            background: #d1fae5;
            color: #065f46;
        }
        .percent-badge.medium {
            background: #fef3c7;
            color: #92400e;
        }
        .percent-badge.low {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ==========================================
           TABLE FOOTER
           ========================================== */
        .table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.4rem 0.8rem;
            margin-top: 0.4rem;
            background: #fafbfc;
            border-radius: 8px;
            font-size: 0.75rem;
            color: #94a3b8;
            flex-wrap: wrap;
            gap: 0.3rem;
        }

        .footer-info {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .footer-info i {
            font-size: 0.8rem;
        }

        /* ==========================================
           EMPTY STATES
           ========================================== */
        .empty-cell {
            text-align: center;
            padding: 1.5rem !important;
            color: #94a3b8;
        }

        .empty-student {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.3rem;
        }

        .empty-student i {
            font-size: 1.5rem;
            color: #cbd5e1;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #f1f5f9;
        }

        .empty-state-content i {
            font-size: 4rem;
            color: #cbd5e1;
            display: block;
            margin-bottom: 1rem;
        }

        .empty-state-content h5 {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .empty-state-content p {
            color: #64748b;
            margin-bottom: 0;
        }

        /* ==========================================
           RESPONSIVE
           ========================================== */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .class-header {
                flex-direction: column;
                align-items: stretch;
            }

            .class-rate {
                align-self: flex-start;
            }
        }

        @media (max-width: 768px) {
            .attendance-report-page {
                padding: 0.75rem;
            }

            .report-header {
                padding: 1.25rem;
                flex-direction: column;
                align-items: stretch;
            }

            .header-left {
                flex-direction: column;
                text-align: center;
            }

            .header-icon {
                width: 48px;
                height: 48px;
                font-size: 1.2rem;
            }

            .report-title {
                font-size: 1.2rem;
                justify-content: center;
            }

            .header-right {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                flex: 1;
            }

            .filter-select {
                min-width: 0;
                width: 100%;
            }

            .btn-export {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }

            .stats-card {
                padding: 1rem;
            }

            .stats-value {
                font-size: 1.1rem;
            }

            .quick-stats-bar {
                gap: 1rem;
                padding: 0.6rem 1rem;
            }

            .quick-stat-divider {
                display: none;
            }

            .class-header {
                padding: 1rem;
            }

            .class-body {
                padding: 1rem;
            }

            .category-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .category-stats {
                width: 100%;
                justify-content: flex-start;
            }

            .category-stat {
                font-size: 0.65rem;
            }

            .table-scroll-wrapper {
                max-height: 250px; /* Smaller height on mobile */
            }

            .attendance-table {
                font-size: 0.8rem;
                min-width: 500px;
            }

            .attendance-table thead th,
            .attendance-table tbody td {
                padding: 0.5rem 0.6rem;
            }

            .student-avatar {
                width: 26px;
                height: 26px;
                font-size: 0.6rem;
            }

            .student-name {
                font-size: 0.8rem;
            }

            .percent-badge {
                font-size: 0.7rem;
                min-width: 45px;
                padding: 0.1rem 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.6rem;
            }

            .stats-card {
                padding: 0.85rem;
            }

            .stats-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .stats-value {
                font-size: 1rem;
            }

            .quick-stats-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 0.4rem;
            }

            .quick-stat-item {
                justify-content: space-between;
            }

            .class-info {
                flex-direction: column;
                text-align: center;
            }

            .class-meta {
                justify-content: center;
            }

            .table-scroll-wrapper {
                max-height: 200px; /* Even smaller on very small screens */
            }

            .attendance-table {
                font-size: 0.7rem;
                min-width: 400px;
            }

            .attendance-table thead th {
                font-size: 0.55rem;
                padding: 0.4rem 0.4rem;
            }

            .attendance-table tbody td {
                padding: 0.4rem 0.4rem;
            }

            .col-student {
                min-width: 120px;
            }
            .col-id {
                min-width: 60px;
            }
            .col-days, .col-attended, .col-absent {
                min-width: 50px;
            }
            .col-percentage {
                min-width: 60px;
            }

            .id-badge {
                font-size: 0.65rem;
                padding: 0.1rem 0.4rem;
            }

            .percent-badge {
                font-size: 0.6rem;
                min-width: 35px;
                padding: 0.05rem 0.4rem;
            }
        }

        /* ==========================================
           PRINT STYLES
           ========================================== */
        @media print {
            .btn-export,
            .filter-form {
                display: none !important;
            }

            .stats-card {
                break-inside: avoid;
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }

            .class-card {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }

            .table-scroll-wrapper {
                max-height: none !important;
                overflow: visible !important;
                border: 1px solid #ddd !important;
            }

            .attendance-table thead {
                position: static !important;
            }

            .attendance-table {
                font-size: 0.7rem;
            }

            .attendance-table thead th {
                background: #e9ecef !important;
                position: static !important;
                box-shadow: none !important;
            }

            .table-footer {
                display: none !important;
            }
        }
    </style>
    @endpush
@endsection