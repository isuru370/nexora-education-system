@extends('layouts.app')

@section('title', "Today's Classes")
@section('page-title', "Today's Classes")

@section('content')

    @php
        $todayDate = \Carbon\Carbon::parse($today);
        $dayName = $todayDate->format('l');
        $searchValue = request('search', '');
    @endphp

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Today's Classes</h1>
                    <p class="hero-subtitle">
                        {{ $todayDate->format('Y-m-d') }} · {{ ucfirst($dayName) }}
                    </p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['total'] ?? 0 }}</span>
                            <span class="mini-stat-label">Classes Today</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['scheduled'] ?? 0 }}</span>
                            <span class="mini-stat-label">Scheduled</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['ongoing'] ?? 0 }}</span>
                            <span class="mini-stat-label">Ongoing</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-date-time">
                        <div class="date-box">
                            <i class="bi bi-calendar-day"></i>
                            <span>{{ ucfirst($dayName) }}</span>
                        </div>
                    </div>
                    <div class="hero-actions">
                        <button type="button" class="nx-btn nx-btn-light" disabled>
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <button type="button" class="nx-btn nx-btn-light" disabled>
                            <i class="bi bi-file-earmark-excel-fill"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI CARDS --}}
        <div class="stats-grid mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-calendar2-week-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Classes Today</span>
                    <h2 class="stat-number">{{ $stats['total'] ?? 0 }}</h2>
                    <span class="stat-change neutral">All <span class="stat-change-note">scheduled classes</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-clock-history"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Scheduled</span>
                    <h2 class="stat-number">{{ $stats['scheduled'] ?? 0 }}</h2>
                    <span class="stat-change neutral">Upcoming <span class="stat-change-note">classes</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-play-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Ongoing</span>
                    <h2 class="stat-number">{{ $stats['ongoing'] ?? 0 }}</h2>
                    <span class="stat-change up">Running <span class="stat-change-note">now</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-journal-bookmark-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Unique Classes</span>
                    <h2 class="stat-number">{{ $stats['unique_classes'] ?? 0 }}</h2>
                    <span class="stat-change neutral">Distinct <span class="stat-change-note">classes</span></span>
                </div>
            </div>
        </div>

        {{-- MAIN CARD --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-calendar-day text-primary"></i> Today's Schedule</h5>
                    <p class="nx-card-subtitle">{{ $todayDate->format('l, d M Y') }}</p>
                </div>
            </div>

            {{-- SEARCH --}}
            <div class="nx-search">
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
                            <button type="submit" class="nx-btn nx-btn-primary w-100">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>

                    @if ($searchValue !== '')
                        <div class="mt-3">
                            <a href="{{ url()->current() }}" class="nx-btn nx-btn-light">
                                <i class="bi bi-x-circle"></i> Clear Search
                            </a>
                        </div>
                    @endif
                </form>
            </div>

            {{-- CLASSES LIST --}}
            @forelse ($classes as $classItem)

                @php
                    $studentClass = $classItem['student_class'];
                    $categoryFee = $classItem['category_fee'];
                    $schedule = $classItem['schedule'];

                    $branch = $studentClass['branch'] ?? null;
                    $feeOptions = $categoryFee['fee_options'] ?? [];

                    $statusMap = [
                        'scheduled' => 'nx-badge-blue',
                        'ongoing'   => 'nx-badge-amber',
                        'completed' => 'nx-badge-green',
                        'cancelled' => 'nx-badge-red',
                    ];
                    $statusKey = strtolower($schedule['status'] ?? 'scheduled');
                    $statusClass = $statusMap[$statusKey] ?? 'nx-badge-gray';
                @endphp

                <div class="class-card">

                    <div class="class-card-header">
                        <div>
                            <h5 class="class-card-title">
                                {{ $studentClass['class_name'] }}

                                @if ($branch)
                                    <span class="nx-badge nx-badge-blue ms-2">
                                        <i class="bi bi-building"></i> {{ $branch['name'] }}
                                    </span>
                                @endif
                            </h5>

                            <div class="class-meta">
                                Grade: {{ $studentClass['grade']['grade_name'] ?? '-' }}
                                <span class="meta-sep">|</span>
                                Subject: {{ $studentClass['subject']['subject_name'] ?? '-' }}
                                <span class="meta-sep">|</span>
                                Teacher: {{ $studentClass['teacher']['full_name'] ?? '-' }}
                            </div>
                        </div>

                        <div class="class-badges">
                            <span class="nx-badge {{ $statusClass }}">
                                <i class="bi bi-clock"></i>
                                {{ ucfirst($schedule['status'] ?? 'scheduled') }}
                            </span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
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
                                    <td>
                                        <div class="cell-title">
                                            {{ $schedule['start_time'] ? \Carbon\Carbon::parse($schedule['start_time'])->format('h:i A') : '-' }}
                                            –
                                            {{ $schedule['end_time'] ? \Carbon\Carbon::parse($schedule['end_time'])->format('h:i A') : '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="cell-title">{{ $studentClass['class_name'] }}</div>
                                        <small class="cell-sub">
                                            {{ $studentClass['grade']['grade_name'] ?? '-' }}
                                            /
                                            {{ $studentClass['subject']['subject_name'] ?? '-' }}
                                        </small>
                                    </td>

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
                                                                    <span class="default-fee-badge">Default</span>
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
                                                <span class="cell-sub">No fee options configured</span>
                                            @endif
                                        @else
                                            <span class="cell-sub">No fee configured</span>
                                        @endif
                                    </td>

                                    <td>{{ $studentClass['teacher']['full_name'] ?? '-' }}</td>

                                    <td>
                                        @if (!empty($schedule['hall']))
                                            <span class="nx-badge nx-badge-gray">
                                                <i class="bi bi-easel"></i>
                                                {{ $schedule['hall']['hall_name'] }}
                                            </span>
                                        @else
                                            <span class="cell-sub">—</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="action-buttons">
                                            <a href="{{ route('class-schedules.show', $schedule['id']) }}"
                                                class="action-btn" title="View Schedule" aria-label="View Schedule">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

            @empty

                <div class="empty-table">
                    <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                    <strong class="d-block">No classes scheduled for today.</strong>
                    <span class="cell-sub">Check back later or try a different date.</span>
                </div>

            @endforelse

        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Today's Classes — page-specific styles only.
       ============================================================ */

    .dashboard-page .class-card {
        padding: 1.25rem;
        border-bottom: 1px solid var(--nx-border);
    }

    .dashboard-page .class-card:last-child { border-bottom: 0; }

    .dashboard-page .class-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .dashboard-page .class-card-title {
        font-size: .95rem;
        font-weight: 700;
        margin: 0 0 .25rem;
        color: var(--nx-text);
    }

    .dashboard-page .class-meta {
        color: var(--nx-muted);
        font-size: .82rem;
    }

    .dashboard-page .meta-sep { margin: 0 .35rem; }

    .dashboard-page .class-badges {
        display: flex; gap: .5rem; flex-wrap: wrap;
    }

    .dashboard-page .category-name {
        font-weight: 700;
        color: var(--nx-text);
        font-size: .82rem;
    }

    /* Fee options shared with Weekly Timetable */
    .dashboard-page .fee-options-list {
        display: flex;
        flex-direction: column;
        gap: .35rem;
        min-width: 140px;
    }

    .dashboard-page .fee-option-item {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        padding: .4rem .55rem;
    }

    .dashboard-page .fee-option-label {
        color: var(--nx-text);
        font-size: .72rem;
        font-weight: 600;
        margin-bottom: .1rem;
    }

    .dashboard-page .fee-option-price {
        color: var(--nx-text);
        font-size: .78rem;
        font-weight: 700;
    }

    .dashboard-page .fee-option-price .currency {
        color: var(--nx-muted);
        font-size: .65rem;
        margin-right: 2px;
    }

    .dashboard-page .default-fee-badge {
        display: inline-block;
        margin-left: 4px;
        padding: 1px 5px;
        border-radius: 4px;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        font-size: .58rem;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .dashboard-page .class-card-header { flex-direction: column; align-items: stretch; }
    }
</style>
@endpush