@extends('layouts.app')

@section('title', "Today's Classes")
@section('page-title', "Today's Classes")

@section('content')

    @php
        $todayDate = \Carbon\Carbon::parse($today);
        $dayName = $todayDate->format('l');
        $searchValue = request('search', '');
    @endphp

    <div class="today-classes-page">

        {{-- ============================================= --}}
        {{-- HERO SECTION --}}
        {{-- ============================================= --}}
        <div class="hero-card mb-4">
            <div class="hero-content">
                <div>
                    <h3 class="fw-bold mb-1">Today's Classes</h3>
                    <p class="text-muted mb-0">
                        {{ $todayDate->format('Y-m-d') }}
                        <span class="mx-1">|</span>
                        {{ ucfirst($dayName) }}
                    </p>
                </div>

                <div class="hero-actions">
                    <button type="button" class="btn btn-light border custom-btn" disabled>
                        <i class="bi bi-printer"></i>
                        Print
                    </button>

                    <button type="button" class="btn btn-light border custom-btn" disabled>
                        <i class="bi bi-file-earmark-excel-fill"></i>
                        Export
                    </button>
                </div>
            </div>
        </div>


        {{-- ============================================= --}}
        {{-- STATS SECTION --}}
        {{-- ============================================= --}}
        <div class="row g-4 mb-4">

            {{-- Total Classes Today --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="bi bi-calendar2-week-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $classes->count() }}</h3>
                        <p>Classes Today</p>
                    </div>
                </div>
            </div>

            {{-- Scheduled --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h3>{{ $classes->filter(fn($row) => ($row['schedule']['status'] ?? null) === 'scheduled')->count() }}
                        </h3>
                        <p>Scheduled</p>
                    </div>
                </div>
            </div>

            {{-- Ongoing --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="bi bi-play-circle-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $classes->filter(fn($row) => ($row['schedule']['status'] ?? null) === 'ongoing')->count() }}
                        </h3>
                        <p>Ongoing</p>
                    </div>
                </div>
            </div>

            {{-- Unique Branches --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h3>
                            {{ $classes->pluck('student_class.branch.id')->filter()->unique()->count() }}
                        </h3>
                        <p>Branches</p>
                    </div>
                </div>
            </div>

        </div>


        {{-- ============================================= --}}
        {{-- MAIN CARD --}}
        {{-- ============================================= --}}
        <div class="main-card">

            {{-- SEARCH SECTION --}}
            <div class="search-card">
                <form method="GET" action="{{ url()->current() }}">
                    <div class="row g-3">
                        <div class="col-lg-10">
                            <div class="search-input-wrapper">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" class="form-control custom-input"
                                    placeholder="Search class, grade, subject, teacher, category..."
                                    value="{{ $searchValue }}">
                            </div>
                        </div>
                        <div class="col-lg-2">
                            <button type="submit" class="btn btn-primary w-100 custom-btn">
                                <i class="bi bi-search"></i>
                                Search
                            </button>
                        </div>
                    </div>

                    @if ($searchValue !== '')
                        <div class="mt-3">
                            <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-circle"></i>
                                Clear Search
                            </a>
                        </div>
                    @endif
                </form>
            </div>


            {{-- CLASSES LIST --}}
            @forelse($classes as $classItem)

                @php
                    $studentClass = $classItem['student_class'];
                    $categoryFee = $classItem['category_fee'];
                    $schedule = $classItem['schedule'];

                    $branch = $studentClass['branch'] ?? null;
                    $feeOptions = $categoryFee['fee_options'] ?? [];
                @endphp

                <div class="class-card mb-4">

                    {{-- ============================================= --}}
                    {{-- CLASS HEADER --}}
                    {{-- ============================================= --}}
                    <div class="class-card-header">

                        <div>
                            <h5 class="mb-1 fw-bold">
                                {{ $studentClass['class_name'] }}

                                {{-- Branch Badge --}}
                                @if ($branch)
                                    <span class="badge bg-info text-dark ms-2">
                                        <i class="bi bi-building me-1"></i>
                                        {{ $branch['name'] }}
                                    </span>
                                @endif
                            </h5>

                            <div class="meta-line">
                                Grade: {{ $studentClass['grade']['grade_name'] ?? '-' }}
                                <span class="meta-separator">|</span>
                                Subject: {{ $studentClass['subject']['subject_name'] ?? '-' }}
                                <span class="meta-separator">|</span>
                                Teacher: {{ $studentClass['teacher']['full_name'] ?? '-' }}
                            </div>
                        </div>

                        <div class="class-badges">
                            <span class="badge bg-primary custom-badge">
                                <i class="bi bi-clock me-1"></i>
                                {{ ucfirst($schedule['status'] ?? 'scheduled') }}
                            </span>
                        </div>

                    </div>


                    {{-- ============================================= --}}
                    {{-- TABLE --}}
                    {{-- ============================================= --}}
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Class</th>
                                    <th>Category & Fees</th>
                                    <th>Teacher</th>
                                    <th>Hall</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>

                                    {{-- TIME --}}
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $schedule['start_time'] ? \Carbon\Carbon::parse($schedule['start_time'])->format('h:i A') : '-' }}
                                            -
                                            {{ $schedule['end_time'] ? \Carbon\Carbon::parse($schedule['end_time'])->format('h:i A') : '-' }}
                                        </div>
                                    </td>

                                    {{-- CLASS --}}
                                    <td>
                                        <div class="fw-bold">
                                            {{ $studentClass['class_name'] }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $studentClass['grade']['grade_name'] ?? '-' }}
                                            /
                                            {{ $studentClass['subject']['subject_name'] ?? '-' }}
                                        </small>
                                    </td>

                                    {{-- CATEGORY & FEES --}}
                                    <td>
                                        @if ($categoryFee && !empty($categoryFee['category']))
                                            <div class="category-name mb-2">
                                                <i class="bi bi-tag-fill me-1"></i>
                                                {{ $categoryFee['category']['category_name'] }}
                                            </div>

                                            @if (!empty($feeOptions))
                                                <div class="fee-options-list">
                                                    @foreach ($feeOptions as $feeOption)
                                                        <div class="fee-option-item">
                                                            <div class="fee-option-label">
                                                                {{ $feeOption['label'] }}

                                                                @if (!empty($feeOption['is_default']))
                                                                    <span class="default-fee-badge">
                                                                        Default
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="fee-option-price">
                                                                <span class="currency">LKR</span>
                                                                {{ number_format((float) $feeOption['fee'], 2) }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    No fee options configured
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">
                                                No fee configured
                                            </span>
                                        @endif
                                    </td>

                                    {{-- TEACHER --}}
                                    <td>
                                        {{ $studentClass['teacher']['full_name'] ?? '-' }}
                                    </td>

                                    {{-- HALL --}}
                                    <td>
                                        @if (!empty($schedule['hall']))
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-easel me-1"></i>
                                                {{ $schedule['hall']['hall_name'] }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- ACTIONS --}}
                                    <td>
                                        <div class="action-buttons">

                                            {{-- Today Attendance --}}
                                            <a href="{{ route('today-classes.attendance', [
                                                'class_schedule_id' => $schedule['id'],
                                                'student_class_id' => $studentClass['id'],
                                                'class_category_fee_id' => $categoryFee['id'] ?? 0,
                                            ]) }}"
                                                class="action-btn today-attendance-btn" title="Take Attendance">
                                                <i class="bi bi-check2-square"></i>
                                            </a>

                                            {{-- View --}}
                                            <a href="{{ route('class-schedules.show', $schedule['id']) }}"
                                                class="action-btn view-btn" title="View Schedule">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                        </div>
                                    </td>

                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

            @empty

                <div class="alert alert-info border-0 shadow-sm">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    No classes scheduled for today.
                </div>

            @endforelse

        </div>

    </div>

@endsection


@push('styles')
    <style>
        .today-classes-page {
            animation: fadeIn .4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =========================================
           CARDS
        ========================================= */
        .hero-card,
        .main-card,
        .stats-card,
        .class-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .04);
            border: 1px solid #eef2f7;
        }

        .hero-card,
        .main-card {
            padding: 1.5rem;
        }

        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hero-actions {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
        }

        /* =========================================
           STATS
        ========================================= */
        .stats-card {
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .stats-icon.blue {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }

        .stats-icon.green {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .stats-icon.orange {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }

        .stats-icon.red {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

        .stats-card h3 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .stats-card p {
            margin: 0;
            color: #64748b;
        }

        /* =========================================
           BUTTONS
        ========================================= */
        .custom-btn {
            border-radius: 14px;
            padding: .7rem 1.2rem;
            font-weight: 600;
            border: none;
            transition: .2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .custom-btn:hover:not(:disabled) {
            transform: translateY(-2px);
        }

        .custom-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* =========================================
           SEARCH
        ========================================= */
        .search-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #64748b;
        }

        .custom-input {
            border-radius: 14px !important;
            border: 1px solid #e2e8f0;
            min-height: 48px;
            padding-left: 42px;
        }

        .custom-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
            outline: none;
        }

        /* =========================================
           CLASS CARD
        ========================================= */
        .class-card {
            padding: 1.25rem;
        }

        .class-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .meta-line {
            color: #64748b;
            font-size: .92rem;
        }

        .meta-separator {
            margin: 0 .35rem;
        }

        .class-badges {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        /* =========================================
           TABLE
        ========================================= */
        .custom-table thead th {
            border: none;
            background: #f8fafc;
            color: #475569;
            font-size: .82rem;
            text-transform: uppercase;
            padding: 1rem;
            white-space: nowrap;
            font-weight: 600;
        }

        .custom-table tbody tr {
            transition: .2s ease;
        }

        .custom-table tbody tr:hover {
            background: #f8fafc;
        }

        .custom-table tbody td {
            padding: 1rem;
            border-color: #f1f5f9;
            vertical-align: middle;
        }

        /* =========================================
           BADGES
        ========================================= */
        .badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
        }

        .custom-badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
        }

        /* =========================================
           FEE OPTIONS
        ========================================= */
        .category-name {
            font-weight: 700;
            color: #1e293b;
            font-size: .85rem;
        }

        .fee-options-list {
            display: flex;
            flex-direction: column;
            gap: .45rem;
            min-width: 150px;
        }

        .fee-option-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: .5rem .65rem;
        }

        .fee-option-label {
            color: #334155;
            font-size: .72rem;
            font-weight: 600;
            margin-bottom: .15rem;
        }

        .fee-option-price {
            color: #1e293b;
            font-size: .78rem;
            font-weight: 700;
        }

        .fee-option-price .currency {
            color: #64748b;
            font-size: .65rem;
            margin-right: 2px;
        }

        .default-fee-badge {
            display: inline-block;
            margin-left: 4px;
            padding: 2px 5px;
            border-radius: 5px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: .58rem;
            font-weight: 700;
        }

        /* =========================================
           ACTIONS
        ========================================= */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: .2s ease;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .view-btn {
            background: #eff6ff;
            color: #2563eb;
        }

        .view-btn:hover {
            background: #2563eb;
            color: #fff;
        }

        .today-attendance-btn {
            background: #fef3c7;
            color: #d97706;
        }

        .today-attendance-btn:hover {
            background: #d97706;
            color: #fff;
        }

        /* =========================================
           RESPONSIVE
        ========================================= */
        @media (max-width: 768px) {

            .hero-content,
            .class-card-header {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-actions,
            .class-badges {
                width: 100%;
            }

            .hero-actions .btn {
                flex: 1;
            }

            .action-buttons {
                justify-content: flex-start;
            }
        }
    </style>
@endpush
