@extends('layouts.app')

@section('title', 'Attendance - ' . $course->name)
@section('page-title', 'Course Attendance')

@section('content')
@php
    $present = $marked->count();
    $canManage = in_array(strtoupper((string) optional(auth()->user()->userType)->code), ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], true);
@endphp
<div class="dashboard-page">

    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">{{ $course->name }}</span>
                <h1 class="hero-title">{{ $session->session_date->format('l, d M Y') }}</h1>
                <p class="hero-subtitle">@include('courses.partials.status-badge', ['status' => $session->status]) {{ \Carbon\Carbon::parse($session->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($session->end_time)->format('h:i A') }} @if ($session->hall) · {{ $session->hall->hall_name }} @endif</p>
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

    @if ($canManage && $session->canEdit())
        <div class="nx-card mb-4" id="edit">
            <div class="p-3">
                <details class="special-fee" {{ $errors->hasAny(['session_date', 'start_time', 'end_time', 'class_hall_id']) ? 'open' : '' }}>
                    <summary>Change date / time / hall</summary>
                    <form method="POST" action="{{ route('courses.sessions.update', [$course, $session]) }}" class="inline-form mt-2" onsubmit="return confirm('Change this class day? Parents will be told.')">
                        @csrf @method('PUT')
                        <div><label class="nx-label" for="edit_date">Date</label><input type="date" id="edit_date" name="session_date" class="form-control nx-input" min="{{ today()->toDateString() }}" value="{{ old('session_date', $session->session_date->toDateString()) }}" required></div>
                        <div><label class="nx-label" for="edit_start">Start</label><input type="time" id="edit_start" name="start_time" class="form-control nx-input" value="{{ old('start_time', substr($session->start_time, 0, 5)) }}" required></div>
                        <div><label class="nx-label" for="edit_end">End</label><input type="time" id="edit_end" name="end_time" class="form-control nx-input" value="{{ old('end_time', substr($session->end_time, 0, 5)) }}" required></div>
                        <div>
                            <label class="nx-label" for="edit_hall">Hall</label>
                            <select id="edit_hall" name="class_hall_id" class="form-select nx-input">
                                <option value="">No hall</option>
                                @foreach ($halls as $hall)
                                    <option value="{{ $hall->id }}" {{ (string) old('class_hall_id', $session->class_hall_id) === (string) $hall->id ? 'selected' : '' }}>{{ $hall->hall_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="min-width:auto"><button class="nx-btn nx-btn-primary" type="submit">Save</button></div>
                    </form>
                    <small class="cell-sub d-block mt-1">Only this day changes. It stays like this when the weekly schedule is edited.</small>
                </details>
            </div>
        </div>
    @endif

    @if ($session->isCancelled())
        <div class="nx-alert nx-alert-red mb-4"><i class="bi bi-x-circle-fill"></i><span>This class day is cancelled{{ $session->cancel_reason ? ': ' . $session->cancel_reason : '' }}.</span></div>
    @elseif ($session->status === 'completed')
        <div class="nx-alert nx-alert-blue mb-4"><i class="bi bi-check-circle-fill"></i><span>This class day is completed.</span></div>
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

@push('scripts')
<script>
    // Opened from "Change class day" on the course page (#edit)
    if (window.location.hash === '#edit') {
        const box = document.querySelector('#edit details');
        if (box) box.open = true;
    }
</script>
@endpush
