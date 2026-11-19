@extends('layouts.app')

@section('title', 'Category Schedules')
@section('page-title', 'Category Schedules')

@section('content')
    @php
        $canManageSchedules = strtoupper((string) optional(optional(auth()->user())->userType)->code) !== 'TEACHER';
    @endphp
    @php
        $className    = $studentClass->class_name ?? '-';
        $gradeName    = optional($studentClass->grade)->grade_name ?? 'N/A';
        $subjectName  = optional($studentClass->subject)->subject_name ?? 'N/A';
        $teacherName  = optional($studentClass->teacher)->full_name ?? 'N/A';

        $categoryName = $category->category_name ?? '-';
        $categoryCode = $category->code ?? '-';

        $searchValue  = request('search');
    @endphp

    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">Category View</span>
                    <h1 class="hero-title">{{ $className }} / {{ $categoryName }}</h1>
                    <p class="hero-subtitle">
                        Grade: {{ $gradeName }}
                        • Subject: {{ $subjectName }}
                        • Teacher: {{ $teacherName }}
                    </p>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        @if ($canManageSchedules)
                        <a href="{{ route('class-schedules.create', [
                            'type' => 'single',
                            'student_class_id' => $studentClass->id,
                            'class_category_id' => $category->id,
                            'class_category_fee_id' => $categoryFee->id,
                        ]) }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-calendar-plus"></i> Add New Day
                        </a>
                        @endif
                        <a href="{{ route('class-schedules.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- SUMMARY --}}
        {{-- ===================================================== --}}
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="summary-card summary-blue">
                    <div class="summary-icon"><i class="bi bi-book"></i></div>
                    <div>
                        <div class="summary-label">Class</div>
                        <h4 class="summary-value">{{ $className }}</h4>
                        <div class="summary-sub">{{ $gradeName }} · {{ $subjectName }}</div>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="summary-card summary-green">
                    <div class="summary-icon"><i class="bi bi-collection"></i></div>
                    <div>
                        <div class="summary-label">Category</div>
                        <h4 class="summary-value">{{ $categoryName }}</h4>
                        <div class="summary-sub">{{ $categoryCode }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- SEARCH --}}
        {{-- ===================================================== --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-calendar-event tw:text-(color:--nx-blue)!"></i> Filter Schedules</h5>
                    <p class="nx-card-subtitle">Pick a date to filter schedules</p>
                </div>
            </div>
            <div class="tw:p-4!">
                <form method="GET" action="{{ route('class-schedules.categorySchedules') }}">
                    <input type="hidden" name="student_class_id" value="{{ $studentClass->id }}">
                    <input type="hidden" name="class_category_fee_id" value="{{ $categoryFee->id }}">

                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[66.66666667%] tw:lg:flex-[0_0_auto] tw:lg:w-[75%]">
                            <div class="search-input-wrapper">
                                <i class="bi bi-calendar-event"></i>
                                <input type="date" name="search" class="ui-form-control nx-input search-input"
                                    value="{{ $searchValue }}">
                            </div>
                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%] tw:grid!">
                            <button type="submit" class="nx-btn nx-btn-primary tw:w-full!">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%] tw:grid!">
                            <a href="{{ route('class-schedules.categorySchedules', [
                                'student_class_id' => $studentClass->id,
                                'class_category_fee_id' => $categoryFee->id,
                            ]) }}" class="nx-btn nx-btn-light tw:w-full!">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- TABLE --}}
        {{-- ===================================================== --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-list-ul tw:text-(color:--nx-blue)!"></i> Schedules</h5>
                    <p class="nx-card-subtitle">All schedules for this class and category</p>
                </div>
                <span class="nx-badge nx-badge-blue">
                    <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                    {{ $schedules->total() }} Records
                </span>
            </div>

            <div class="ui-table-responsive">
                <table class="ui-table premium-table tw:mb-0!">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Hall</th>
                            <th>Status</th>
                            <th class="tw:text-right!">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $startNumber = $schedules->firstItem() ?? 1;
                        @endphp

                        @forelse ($schedules as $index => $schedule)
                            @php
                                $scheduleDate = $schedule->class_date
                                    ? \Carbon\Carbon::parse($schedule->class_date)
                                    : null;

                                $startTime = $schedule->start_time
                                    ? \Carbon\Carbon::parse($schedule->start_time)->format('h:i A')
                                    : '-';

                                $endTime = $schedule->end_time
                                    ? \Carbon\Carbon::parse($schedule->end_time)->format('h:i A')
                                    : '-';

                                $hallName = optional($schedule->hall)->hall_name ?? '-';

                                $statusMap = [
                                    'scheduled' => 'nx-badge-blue',
                                    'ongoing'   => 'nx-badge-amber',
                                    'completed' => 'nx-badge-green',
                                    'cancelled' => 'nx-badge-red',
                                ];
                                $statusClass = $statusMap[$schedule->status] ?? 'nx-badge-gray';

                                $isLocked = $scheduleDate ? $scheduleDate->lte(today()) : false;
                                $isToday  = $scheduleDate ? $scheduleDate->isToday() : false;

                                $isCancelable = $schedule->isCancellable()
                                    && ($isToday || !$schedule->is_active);

                                $isEditable = !$isLocked
                                    && $schedule->is_active
                                    && !in_array($schedule->status, ['cancelled', 'completed']);

                                $canToggleActive = !$isLocked
                                    && !in_array($schedule->status, ['cancelled', 'completed']);
                            @endphp

                            <tr>
                                <td class="row-number">{{ $startNumber + $index }}</td>

                                <td class="cell-title">
                                    {{ $scheduleDate ? $scheduleDate->format('Y-m-d') : '-' }}
                                </td>

                                <td class="cell-sub">
                                    {{ ucfirst($schedule->day_of_week) }}
                                </td>

                                <td class="cell-sub">
                                    {{ $startTime }} – {{ $endTime }}
                                </td>

                                <td class="cell-sub">{{ $hallName }}</td>

                                <td>
                                    <div class="tw:flex! tw:flex-col! tw:gap-1!">
                                        <span class="nx-badge {{ $statusClass }}">
                                            {{ ucfirst($schedule->status) }}
                                        </span>
                                        <span class="nx-badge {{ $schedule->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                                            {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="tw:text-right!">
                                    <div class="action-buttons">

                                        {{-- VIEW --}}
                                        <a href="{{ route('class-schedules.show', $schedule) }}" class="action-btn" title="View schedule" aria-label="View schedule">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        @if (!$canManageSchedules)
                                        @elseif ($isLocked && !$isCancelable)
                                            <span class="nx-badge nx-badge-gray">Locked</span>
                                        @else
                                            @if (!$isLocked && $isEditable)
                                                <a href="{{ route('class-schedules.edit', $schedule) }}" class="action-btn action-btn-warning" title="Edit schedule" aria-label="Edit schedule">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            @endif

                                            @if (!$isLocked && $canToggleActive)
                                                <form method="POST"
                                                    action="{{ route('class-schedules.toggleActive', $schedule) }}"
                                                    onsubmit="return confirm('Change active status?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="action-btn {{ $schedule->is_active ? 'action-btn-warning' : 'action-btn-success' }}"
                                                        title="{{ $schedule->is_active ? 'Set Inactive' : 'Set Active' }}"
                                                        aria-label="{{ $schedule->is_active ? 'Set Inactive' : 'Set Active' }}">
                                                        <i class="bi {{ $schedule->is_active ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' }}"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($isCancelable)
                                                <button type="button"
                                                    class="action-btn action-btn-danger"
                                                    data-nx-toggle="modal"
                                                    data-nx-target="#cancelModal{{ $schedule->id }}"
                                                    title="Cancel schedule"
                                                    aria-label="Cancel schedule">
                                                    <i class="bi bi-x-circle-fill"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-table">
                                    <i class="bi bi-calendar-x tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:block! tw:mb-4!"></i>
                                    <strong class="tw:block!">No schedules found</strong>
                                    <span class="cell-sub">No schedules found for this category.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($schedules->hasPages())
                <div class="tw:p-4! tw:bs-border-t! tw:flex! tw:justify-center!">
                    {{ $schedules->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- CANCEL MODALS --}}
    {{-- ===================================================== --}}
    @foreach ($schedules as $schedule)
        @php
            $scheduleDate = $schedule->class_date
                ? \Carbon\Carbon::parse($schedule->class_date)
                : null;

            $isToday = $scheduleDate ? $scheduleDate->isToday() : false;
            $isCancelable = $schedule->isCancellable()
                && ($isToday || !$schedule->is_active);
        @endphp

        @if ($isCancelable && $canManageSchedules)
            <div class="ui-modal ui-fade" id="cancelModal{{ $schedule->id }}" tabindex="-1" aria-hidden="true">
                <div class="ui-modal-dialog ui-modal-dialog-centered">
                    <div class="ui-modal-content">
                        <form method="POST" action="{{ route('class-schedules.cancel', $schedule) }}">
                            @csrf
                            @method('PATCH')

                            <div class="ui-modal-header">
                                <h5 class="ui-modal-title">Cancel Schedule</h5>
                                <button type="button" class="ui-btn-close" data-nx-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="ui-modal-body">
                                <p class="cell-sub tw:mb-4!">
                                    {{ \Carbon\Carbon::parse($schedule->class_date)->format('Y-m-d') }}
                                    | {{ ucfirst($schedule->day_of_week) }}
                                    | {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }}
                                    – {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                                </p>

                                @if ($isToday)
                                    <div class="nx-alert nx-alert-amber tw:mb-4!">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        <span>This is today's class. Parents will be notified by SMS.</span>
                                    </div>
                                @endif

                                <label for="cancel_reason_{{ $schedule->id }}" class="nx-label">Cancel Reason *</label>
                                <textarea name="cancel_reason" id="cancel_reason_{{ $schedule->id }}"
                                    class="ui-form-control nx-input" rows="3"
                                    placeholder="Enter cancellation reason" required></textarea>
                            </div>

                            <div class="ui-modal-footer">
                                <button type="button" class="nx-btn nx-btn-light" data-nx-dismiss="modal">
                                    Close
                                </button>
                                <button type="submit" class="nx-btn nx-btn-danger">
                                    <i class="bi bi-x-circle"></i> Cancel Schedule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection


@push('styles')
<style>
    /* ============================================================
       Category Schedules — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    /* Summary cards */
    .dashboard-page .summary-card {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        min-height: 110px;
        box-shadow: 0 1px 2px rgba(20, 28, 58, .04);
        border: 1px solid var(--nx-border);
    }

    .dashboard-page .summary-blue {
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
    }

    .dashboard-page .summary-green {
        background: var(--nx-green-soft);
        color: var(--nx-green);
    }

    .dashboard-page .summary-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .dashboard-page .summary-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        opacity: .85;
    }

    .dashboard-page .summary-value {
        margin: .35rem 0;
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--nx-text);
    }

    .dashboard-page .summary-sub {
        opacity: .85;
        font-size: .82rem;
    }

    /* Search */
    .dashboard-page .search-input {
        padding-left: 38px;
    }

    @media (max-width: 768px) {
        .dashboard-page .premium-table { min-width: 900px; }
        .dashboard-page .summary-card { min-height: auto; }
    }
</style>
@endpush