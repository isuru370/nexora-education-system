@extends('layouts.app')

@section('title', 'Weekly Timetable')
@section('page-title', 'Weekly Timetable')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Weekly Timetable</h1>
                <p class="hero-subtitle">View and manage class timetable for the selected week.</p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $stats['total'] ?? 0 }}</span>
                        <span class="mini-stat-label">Total Classes</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $stats['completed'] ?? 0 }}</span>
                        <span class="mini-stat-label">Completed</span>
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
                @if (isset($startOfWeek, $endOfWeek))
                    <div class="hero-date-time">
                        <div class="date-box">
                            <i class="bi bi-calendar-range"></i>
                            <span>
                                {{ $startOfWeek->format('d M') }} – {{ $endOfWeek->format('d M Y') }}
                            </span>
                        </div>
                    </div>
                @endif
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="stats-grid mb-4">

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-calendar-week"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Classes</span>
                <h2 class="stat-number">{{ $stats['total'] ?? 0 }}</h2>
                <span class="stat-change neutral">This <span class="stat-change-note">week</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Completed</span>
                <h2 class="stat-number">{{ $stats['completed'] ?? 0 }}</h2>
                <span class="stat-change up">Finished <span class="stat-change-note">classes</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <span class="stat-label">Scheduled</span>
                <h2 class="stat-number">{{ $stats['scheduled'] ?? 0 }}</h2>
                <span class="stat-change neutral">Upcoming <span class="stat-change-note">classes</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-play-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Ongoing</span>
                <h2 class="stat-number">{{ $stats['ongoing'] ?? 0 }}</h2>
                <span class="stat-change up">Running <span class="stat-change-note">now</span></span>
            </div>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-calendar-week text-primary"></i> Weekly Class Schedule</h5>
                <p class="nx-card-subtitle">View and manage class timetable for the selected week</p>
            </div>

            <div class="header-buttons">
                <a href="{{ route('class-time-table.weekly.pdf', ['week_date' => $selectedDate ?? now()->toDateString()]) }}"
                    class="nx-btn nx-btn-danger">
                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                </a>
                <a href="{{ route('class-time-table.weekly.excel', ['week_date' => $selectedDate ?? now()->toDateString()]) }}"
                    class="nx-btn nx-btn-success">
                    <i class="bi bi-file-earmark-excel-fill"></i> Excel
                </a>
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('class-time-table.weekly') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="nx-label">Select Week</label>
                        <div class="search-input-wrapper">
                            <i class="bi bi-calendar-date"></i>
                            <input type="date" name="week_date" class="form-control custom-input"
                                value="{{ old('week_date', $selectedDate ?? now()->toDateString()) }}">
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <label class="nx-label">Status</label>
                        <select name="status" class="form-select custom-input">
                            <option value="">All Status</option>
                            <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <div class="d-flex gap-2">
                            <button class="nx-btn nx-btn-primary flex-grow-1" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <a href="{{ route('class-time-table.weekly') }}" class="nx-btn nx-btn-light flex-grow-1">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            @if (session('error'))
                <div class="nx-alert nx-alert-red mt-3">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @error('week_date')
                <div class="nx-alert nx-alert-red mt-3">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 4%">#</th>
                        <th style="width: 18%">Date & Time</th>
                        <th style="width: 22%">Class Details</th>
                        <th style="width: 12%">Branch</th>
                        <th style="width: 10%">Grade</th>
                        <th style="width: 14%">Category</th>
                        <th style="width: 15%">Fee Options</th>
                        <th style="width: 10%">Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($schedules as $schedule)
                        @php
                            $classDate = $schedule->class_date;
                            $startTime = $schedule->start_time;
                            $endTime = $schedule->end_time;

                            $statusMap = [
                                'scheduled' => 'nx-badge-blue',
                                'ongoing'   => 'nx-badge-amber',
                                'completed' => 'nx-badge-green',
                                'cancelled' => 'nx-badge-red',
                            ];
                            $status = strtolower($schedule->status ?? 'scheduled');
                            $statusClass = $statusMap[$status] ?? 'nx-badge-gray';

                            $feeOptions = $schedule->classCategoryFee?->activeFeeOptions ?? collect();
                        @endphp

                        <tr>
                            <td class="row-number">{{ $loop->iteration }}</td>

                            <td>
                                <div class="cell-title">
                                    <i class="bi bi-calendar3 text-muted me-1"></i>
                                    {{ $classDate ? $classDate->format('d M Y') : '-' }}
                                </div>
                                <small class="cell-sub d-block">
                                    <i class="bi bi-clock"></i>
                                    {{ $startTime ? \Carbon\Carbon::parse($startTime)->format('h:i A') : '-' }}
                                    –
                                    {{ $endTime ? \Carbon\Carbon::parse($endTime)->format('h:i A') : '-' }}
                                </small>
                                <small class="cell-sub">{{ $classDate ? $classDate->format('l') : '' }}</small>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="class-avatar">
                                        {{ strtoupper(substr($schedule->studentClass?->class_name ?? 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="cell-title">{{ $schedule->studentClass?->class_name ?? '-' }}</div>
                                        <small class="cell-sub">
                                            <i class="bi bi-easel"></i>
                                            {{ $schedule->hall?->hall_name ?? 'No Hall Assigned' }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                @if ($schedule->branch)
                                    <span class="nx-badge nx-badge-blue">
                                        <i class="bi bi-building"></i> {{ $schedule->branch->name }}
                                    </span>
                                @else
                                    <span class="cell-sub">—</span>
                                @endif
                            </td>

                            <td>
                                @php
                                    $gradeName = $schedule->studentClass?->grade?->grade_name ?? '-';
                                @endphp
                                <span class="nx-badge nx-badge-gray">
                                    <i class="bi bi-star-fill"></i> {{ $gradeName }}
                                </span>
                            </td>

                            <td>
                                <span class="nx-badge nx-badge-violet">
                                    <i class="bi bi-tag-fill"></i>
                                    {{ $schedule->classCategoryFee?->category?->category_name ?? '-' }}
                                </span>
                            </td>

                            <td>
                                @if ($feeOptions->count() > 0)
                                    <div class="fee-options-list">
                                        @foreach ($feeOptions as $feeOption)
                                            <div class="fee-option-item">
                                                <div class="fee-option-label">
                                                    {{ $feeOption->label }}
                                                    @if ($feeOption->is_default)
                                                        <span class="default-fee-badge">Default</span>
                                                    @endif
                                                </div>
                                                <div class="fee-option-price">
                                                    <span class="currency">LKR</span>
                                                    {{ number_format((float) $feeOption->fee, 2) }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="nx-badge nx-badge-green">
                                        <i class="bi bi-gift-fill"></i> No Fee Options
                                    </span>
                                @endif
                            </td>

                            <td>
                                <span class="nx-badge {{ $statusClass }}">
                                    {{ ucfirst($schedule->status ?? '-') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-table">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                <strong class="d-block">No Classes Found</strong>
                                <span class="cell-sub">No timetable records found for this week</span>
                                <small class="cell-sub d-block mt-1">Try selecting a different week</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Weekly Timetable — page-specific styles only.
       ============================================================ */

    .dashboard-page .class-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--nx-violet-soft);
        color: var(--nx-violet);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: .9rem;
        flex-shrink: 0;
    }

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

    @media (max-width: 992px) {
        .dashboard-page .premium-table { min-width: 900px; }
    }
</style>
@endpush