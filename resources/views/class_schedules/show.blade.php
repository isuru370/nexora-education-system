@extends('layouts.app')

@section('title', 'Class Schedule Details')
@section('page-title', 'Class Schedule Details')

@section('content')
    @php
        $canManageSchedules = strtoupper((string) optional(optional(auth()->user())->userType)->code) !== 'TEACHER';
    @endphp

@php
    $studentClass = $classSchedule->studentClass ?? null;
    $category     = $classSchedule->category ?? null;
    $hall         = $classSchedule->hall ?? null;
    $cancelledBy  = $classSchedule->cancelledBy ?? null;

    $className    = $studentClass->class_name ?? '-';
    $gradeName    = $studentClass->grade->grade_name ?? 'N/A';
    $subjectName  = $studentClass->subject->subject_name ?? 'N/A';
    $teacherName  = $studentClass->teacher->full_name ?? 'N/A';

    $categoryName = $category->category_name ?? '-';
    $categoryCode = $category->code ?? '-';
    $hallName     = $hall->hall_name ?? '-';

    $classDate  = $classSchedule->class_date ? \Carbon\Carbon::parse($classSchedule->class_date)->format('Y-m-d') : '-';
    $createdAt  = $classSchedule->created_at ? \Carbon\Carbon::parse($classSchedule->created_at)->format('Y-m-d h:i A') : '-';
    $cancelledAt = $classSchedule->cancelled_at ? \Carbon\Carbon::parse($classSchedule->cancelled_at)->format('Y-m-d h:i A') : '-';
    $startTime  = $classSchedule->start_time ? \Carbon\Carbon::parse($classSchedule->start_time)->format('h:i A') : '-';
    $endTime    = $classSchedule->end_time ? \Carbon\Carbon::parse($classSchedule->end_time)->format('h:i A') : '-';

    $cancelledByName = $cancelledBy->name ?? '-';
    $cancelReason    = $classSchedule->cancel_reason ?? '-';
    $note            = $classSchedule->note ?? '-';

    $statusBadgeMap = [
        'scheduled' => 'nx-badge-blue',
        'ongoing'   => 'nx-badge-amber',
        'completed' => 'nx-badge-green',
        'cancelled' => 'nx-badge-red',
    ];
    $statusClass = $statusBadgeMap[$classSchedule->status] ?? 'nx-badge-gray';
@endphp

<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Class Schedule</span>
                <h1 class="hero-title">Schedule Details</h1>
                <p class="hero-subtitle">{{ $className }} / {{ $categoryName }}</p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="nx-badge {{ $statusClass }}">{{ ucfirst($classSchedule->status) }}</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="nx-badge {{ $classSchedule->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                            {{ $classSchedule->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-schedules.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    @if ($canManageSchedules)
                    <a href="{{ route('class-schedules.edit', $classSchedule) }}" class="nx-btn nx-btn-primary">
                        <i class="bi bi-pencil-square"></i> Edit
                    </a>
                    @endif
                    <button type="button" onclick="window.print()" class="nx-btn nx-btn-light">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if (session('success'))
        <div class="nx-alert nx-alert-green mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- INFO GRID --}}
    <div class="row g-4 mb-4">

        {{-- CLASS --}}
        <div class="col-lg-6">
            <div class="nx-card h-100">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-book text-primary"></i> Class Information</h5>
                        <p class="nx-card-subtitle">Class, grade, subject and teacher</p>
                    </div>
                </div>
                <div class="p-3">
                    <div class="detail-line"><span class="detail-label">Class</span><span class="detail-value">{{ $className }}</span></div>
                    <div class="detail-line"><span class="detail-label">Grade</span><span class="detail-value">{{ $gradeName }}</span></div>
                    <div class="detail-line"><span class="detail-label">Subject</span><span class="detail-value">{{ $subjectName }}</span></div>
                    <div class="detail-line"><span class="detail-label">Teacher</span><span class="detail-value">{{ $teacherName }}</span></div>
                    <div class="detail-line"><span class="detail-label">Category</span><span class="detail-value">{{ $categoryName }}</span></div>
                    <div class="detail-line"><span class="detail-label">Category Code</span><span class="detail-value" style="font-family: ui-monospace, monospace;">{{ $categoryCode }}</span></div>
                    <div class="detail-line" style="border-bottom: 0;"><span class="detail-label">Hall</span><span class="detail-value">{{ $hallName }}</span></div>
                </div>
            </div>
        </div>

        {{-- SCHEDULE --}}
        <div class="col-lg-6">
            <div class="nx-card h-100">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-calendar2-week text-primary"></i> Schedule Information</h5>
                        <p class="nx-card-subtitle">Date, time, status and metadata</p>
                    </div>
                </div>
                <div class="p-3">
                    <div class="detail-line"><span class="detail-label">Date</span><span class="detail-value">{{ $classDate }}</span></div>
                    <div class="detail-line"><span class="detail-label">Day</span><span class="detail-value">{{ ucfirst($classSchedule->day_of_week) }}</span></div>
                    <div class="detail-line"><span class="detail-label">Time</span><span class="detail-value">{{ $startTime }} – {{ $endTime }}</span></div>
                    <div class="detail-line">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="nx-badge {{ $statusClass }}">{{ ucfirst($classSchedule->status) }}</span>
                        </span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Active</span>
                        <span class="detail-value">
                            <span class="nx-badge {{ $classSchedule->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                                {{ $classSchedule->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </span>
                    </div>
                    <div class="detail-line" style="border-bottom: 0;"><span class="detail-label">Created At</span><span class="detail-value">{{ $createdAt }}</span></div>
                </div>
            </div>
        </div>

        {{-- CANCEL DETAILS --}}
        @if ($classSchedule->status === 'cancelled')
            <div class="col-12">
                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-x-circle text-danger"></i> Cancel Details</h5>
                            <p class="nx-card-subtitle">Why and when this schedule was cancelled</p>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6">
                                <div class="detail-line detail-line-stacked">
                                    <span class="detail-label">Reason</span>
                                    <span class="detail-value">{{ $cancelReason }}</span>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="detail-line detail-line-stacked">
                                    <span class="detail-label">Cancelled By</span>
                                    <span class="detail-value">{{ $cancelledByName }}</span>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="detail-line detail-line-stacked" style="border-bottom: 0;">
                                    <span class="detail-label">Cancelled At</span>
                                    <span class="detail-value">{{ $cancelledAt }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- NOTE --}}
        <div class="col-12">
            <div class="nx-card">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-chat-left-text text-primary"></i> Note</h5>
                    </div>
                </div>
                <div class="p-3">
                    <div class="note-box">{{ $note }}</div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection


@push('styles')
<style>
    .dashboard-page .detail-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .75rem 0;
        border-bottom: 1px solid var(--nx-border);
        gap: 1rem;
    }
    .dashboard-page .detail-line:last-child { border-bottom: 0; }

    .dashboard-page .detail-label {
        flex-shrink: 0;
        font-size: .72rem;
        font-weight: 700;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .dashboard-page .detail-value {
        font-size: .85rem;
        font-weight: 600;
        color: var(--nx-text);
        text-align: right;
        word-break: break-word;
    }

    .dashboard-page .detail-line-stacked {
        flex-direction: column;
        align-items: flex-start;
        gap: .25rem;
    }
    .dashboard-page .detail-line-stacked .detail-label {
        width: 100%;
    }
    .dashboard-page .detail-line-stacked .detail-value {
        text-align: left;
    }

    .dashboard-page .note-box {
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        padding: 1rem;
        line-height: 1.7;
        color: var(--nx-text);
        white-space: pre-line;
    }

    @media (max-width: 768px) {
        .dashboard-page .detail-line {
            flex-direction: column;
            align-items: flex-start;
            gap: .35rem;
        }
        .dashboard-page .detail-value { text-align: left; }
    }

    @media print {
        .dashboard-page .hero-actions,
        .dashboard-page .nx-alert,
        .sidebar,
        .top-navbar { display: none !important; }
        .dashboard-page .hero-section { box-shadow: none !important; }
        .dashboard-page .nx-card { box-shadow: none !important; }
    }
</style>
@endpush