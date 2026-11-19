@extends('layouts.app')

@section('title', 'Course Attendance')
@section('page-title', 'Course Attendance')

@section('content')
<div class="dashboard-page">
    <div class="hero-section tw:mb-6!">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Courses</span>
                <h1 class="hero-title">Course Attendance</h1>
                <p class="hero-subtitle">{{ $date->format('l, d M Y') }} · open a class day to scan students.</p>
            </div>
            <div class="hero-right">
                <form method="GET" action="{{ route('course-desk.attendance') }}" class="tw:flex! tw:gap-2!">
                    <input type="date" name="date" class="ui-form-control custom-input" value="{{ $date->toDateString() }}" aria-label="Date">
                    <button class="nx-btn nx-btn-light" type="submit">Show</button>
                </form>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')

    <div class="desk-grid">
        @forelse ($sessions as $session)
            <a href="{{ route('courses.sessions.show', [$session->course_id, $session]) }}" class="desk-card tw:[text-decoration:none]! tw:text-[inherit]!">
                <div class="tw:flex! tw:justify-between! tw:gap-2!">
                    <h6>{{ $session->course->name }}</h6>
                    @include('courses.partials.status-badge', ['status' => $session->status])
                </div>
                <div class="cell-sub">{{ \Carbon\Carbon::parse($session->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($session->end_time)->format('h:i A') }} @if ($session->hall) · {{ $session->hall->hall_name }} @endif</div>
                <div><strong>{{ $session->attendances_count }}</strong> / {{ $session->course->students_count }} present</div>
                <span class="nx-btn nx-btn-primary tw:self-start!"><i class="bi bi-qr-code-scan"></i> {{ $session->canMarkAttendance() ? 'Mark attendance' : 'View' }}</span>
            </a>
        @empty
            <div class="nx-alert nx-alert-blue"><i class="bi bi-info-circle-fill"></i><span>No course classes on this day.</span></div>
        @endforelse
    </div>
</div>
@endsection

@include('courses.partials.styles')
