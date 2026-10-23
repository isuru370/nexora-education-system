@extends('layouts.app')

@section('title', 'Enroll in a Course')
@section('page-title', 'Course Enrollment')

@section('content')
<div class="dashboard-page">
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Courses</span>
                <h1 class="hero-title">Course Enrollment</h1>
                <p class="hero-subtitle">Scan the student, choose the course and how they pay.</p>
            </div>
            <div class="hero-right"><div class="hero-actions"><a href="{{ route('courses.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-mortarboard"></i> Courses</a></div></div>
        </div>
    </div>

    @include('courses.partials.alerts')
    @include('courses.partials.student-scan', ['action' => route('course-desk.enroll')])

    @if ($student)
        @if ($enrolled->count())
            <div class="nx-card mb-4">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-journal-check text-primary"></i> Already in</h5></div>
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead><tr><th>Course</th><th>Plan</th><th class="text-end">Balance</th><th>Status</th><th class="text-end"></th></tr></thead>
                        <tbody>
                            @foreach ($enrolled as $row)
                                <tr class="{{ $row['enrollment']->isActive() ? '' : 'row-muted' }}">
                                    <td>{{ $row['enrollment']->course->name }}</td>
                                    <td>{{ $row['enrollment']->payment_plan === 'full' ? 'At once' : 'Installments' }}</td>
                                    <td class="text-end money">{{ number_format($row['fees']['balance'], 2) }}</td>
                                    <td>@include('courses.partials.status-badge', ['status' => $row['enrollment']->status])</td>
                                    <td class="text-end"><a class="nx-btn nx-btn-light" href="{{ route('courses.enrollments.show', [$row['enrollment']->course_id, $row['enrollment']]) }}">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <h5 class="mb-3">Courses to join</h5>
        <div class="desk-grid">
            @forelse ($available as $course)
                <form method="POST" action="{{ route('courses.enrollments.store', $course) }}" class="desk-card">
                    @csrf
                    <input type="hidden" name="student_code" value="{{ $student->custom_id ?: request('code') }}">
                    <div>
                        <h6>{{ $course->name }}</h6>
                        <small class="cell-sub">{{ $course->start_date->format('d M Y') }} – {{ $course->end_date->format('d M Y') }}<br>{{ $course->scheduleLabel() }}</small>
                    </div>
                    <div>
                        <label class="nx-label" for="plan{{ $course->id }}">Payment</label>
                        <select id="plan{{ $course->id }}" name="payment_plan" class="form-select nx-input">
                            <option value="installment">{{ $course->installment_count }} installments · {{ number_format($course->full_fee, 2) }}</option>
                            <option value="full">Pay at once · {{ number_format($course->one_time_fee, 2) }}</option>
                        </select>
                    </div>
                    @include('courses.partials.special-fee-fields', ['sfId' => 'c' . $course->id])
                    <button class="nx-btn nx-btn-primary" type="submit"><i class="bi bi-person-plus"></i> Enroll</button>
                </form>
            @empty
                <div class="nx-alert nx-alert-blue"><i class="bi bi-info-circle-fill"></i><span>No other open courses.</span></div>
            @endforelse
        </div>
    @endif
</div>
@endsection

@include('courses.partials.styles')
