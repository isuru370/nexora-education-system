@extends('layouts.app')

@section('title', 'Attendance - ' . $course->name)
@section('page-title', 'Course Attendance')

@section('content')
@php
    $present = $marked->count();
@endphp
<div class="dashboard-page">

    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">{{ $course->name }}</span>
                <h1 class="hero-title">{{ $session->session_date->format('l, d M Y') }}</h1>
                <p class="hero-subtitle">{{ \Carbon\Carbon::parse($session->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($session->end_time)->format('h:i A') }} @if ($session->hall) · {{ $session->hall->hall_name }} @endif</p>
                <div class="hero-stats-mini">
                    <div class="mini-stat"><span class="mini-stat-value">{{ $present }}</span><span class="mini-stat-label">Present</span></div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat"><span class="mini-stat-value">{{ max(0, $students->count() - $present) }}</span><span class="mini-stat-label">Absent</span></div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('courses.show', $course) }}#sessions" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Course</a>
                </div>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')

    @if ($session->isCancelled())
        <div class="nx-alert nx-alert-red mb-4"><i class="bi bi-x-circle-fill"></i><span>This class day is cancelled{{ $session->cancel_reason ? ': ' . $session->cancel_reason : '' }}.</span></div>
    @elseif (!$canMark)
        <div class="nx-alert nx-alert-blue mb-4"><i class="bi bi-info-circle-fill"></i><span>Attendance can be marked from this day on.</span></div>
    @else
        <div class="nx-card mb-4">
            <div class="p-3">
                <form method="POST" action="{{ route('courses.sessions.mark', [$course, $session]) }}" class="inline-form" autocomplete="off">
                    @csrf
                    <div class="flex-grow-1">
                        <label class="nx-label" for="student_code">Scan QR or type student ID</label>
                        <input id="student_code" name="student_code" class="form-control nx-input scan-box" placeholder="Student ID / QR" required autofocus>
                    </div>
                    <div style="min-width:auto"><button class="nx-btn nx-btn-primary" type="submit"><i class="bi bi-person-check-fill"></i> Mark present</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="nx-card">
        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead><tr><th>Student</th><th>Mobile</th><th>Status</th><th>Time</th><th class="text-end"></th></tr></thead>
                <tbody>
                    @forelse ($students as $enrollment)
                        @php
                            $row = $marked->get($enrollment->student_id);
                        @endphp
                        <tr>
                            <td><span class="cell-title">{{ $enrollment->student->initial_name ?? '' }}</span><small class="cell-sub d-block">{{ $enrollment->student->custom_id ?? '' }}</small></td>
                            <td>{{ $enrollment->student->mobile ?? '—' }}</td>
                            <td>
                                @if ($row)
                                    <span class="nx-badge nx-badge-green">Present</span>
                                @else
                                    <span class="nx-badge nx-badge-gray">Absent</span>
                                @endif
                            </td>
                            <td>
                                @if ($row)
                                    {{ $row->attended_at->format('h:i A') }}
                                    <small class="cell-sub d-block">{{ str_contains($row->mark_method, 'mobile') ? 'App' : 'Web' }} · {{ $row->markedBy->name ?? '' }}</small>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($row)
                                    <form method="POST" action="{{ route('courses.sessions.unmark', [$course, $session, $row]) }}" onsubmit="return confirm('Remove this attendance?')">
                                        @csrf @method('DELETE')
                                        <button class="action-btn action-btn-danger" title="Remove" aria-label="Remove attendance"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                @elseif ($canMark)
                                    <form method="POST" action="{{ route('courses.sessions.mark', [$course, $session]) }}">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $enrollment->student_id }}">
                                        <button class="action-btn action-btn-success" title="Mark present" aria-label="Mark present"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-table">No students in this course yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@include('courses.partials.styles')
