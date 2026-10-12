@extends('layouts.app')

@section('title', 'Teacher Dashboard')

@section('content')

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

            <div>
                <h3 class="fw-bold mb-1">
                    Teacher Dashboard
                </h3>

                <p class="text-muted mb-0">
                    Manage your classes, students and teaching activities
                </p>
            </div>

            <div class="mt-2 mt-md-0">
                <span class="badge bg-success-subtle text-success px-3 py-2">
                    <i class="bi bi-person-workspace me-1"></i>
                    Teacher
                </span>
            </div>

        </div>


        {{-- Teacher Statistics --}}
        <div class="row g-4 mb-4">

            {{-- My Classes --}}
            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>
                                <p class="text-muted mb-2">
                                    My Classes
                                </p>

                                <h3 class="fw-bold mb-0">
                                    {{ $classesCount ?? 0 }}
                                </h3>
                            </div>

                            <div class="bg-primary-subtle text-primary rounded-3 p-3">
                                <i class="bi bi-mortarboard fs-4"></i>
                            </div>

                        </div>

                        @if (hasPermission('student-classes.index'))
                            <div class="mt-3">

                                <a href="{{ route('admin.student-classes.index') }}" class="text-decoration-none small">

                                    View Classes

                                    <i class="bi bi-arrow-right ms-1"></i>

                                </a>

                            </div>
                        @endif

                    </div>

                </div>

            </div>


            {{-- My Students --}}
            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="text-muted mb-2">
                                    My Students
                                </p>

                                <h3 class="fw-bold mb-0">
                                    {{ $studentsCount ?? 0 }}
                                </h3>

                            </div>

                            <div class="bg-success-subtle text-success rounded-3 p-3">
                                <i class="bi bi-people fs-4"></i>
                            </div>

                        </div>

                        @if (hasPermission('student-classes.index'))
                            <div class="mt-3">

                                <a href="{{ route('admin.student-classes.index') }}" class="text-decoration-none small">

                                    View Students

                                    <i class="bi bi-arrow-right ms-1"></i>

                                </a>

                            </div>
                        @endif

                    </div>

                </div>

            </div>


            {{-- Today's Classes --}}
            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="text-muted mb-2">
                                    Today's Classes
                                </p>

                                <h3 class="fw-bold mb-0">
                                    {{ $todayClassesCount ?? 0 }}
                                </h3>

                            </div>

                            <div class="bg-warning-subtle text-warning rounded-3 p-3">
                                <i class="bi bi-calendar-day fs-4"></i>
                            </div>

                        </div>

                        @if (hasPermission('class-schedules.index'))
                            <div class="mt-3">

                                <a href="{{ route('admin.class-schedules.index') }}" class="text-decoration-none small">

                                    View Timetable

                                    <i class="bi bi-arrow-right ms-1"></i>

                                </a>

                            </div>
                        @endif

                    </div>

                </div>

            </div>


            {{-- Upcoming Exams --}}
            <div class="col-xl-3 col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-start">

                            <div>

                                <p class="text-muted mb-2">
                                    Upcoming Exams
                                </p>

                                <h3 class="fw-bold mb-0">
                                    {{ $upcomingExamsCount ?? 0 }}
                                </h3>

                            </div>

                            <div class="bg-danger-subtle text-danger rounded-3 p-3">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>

                        </div>

                        @if (hasPermission('exams.index'))
                            <div class="mt-3">

                                <a href="{{ route('admin.exams.index') }}" class="text-decoration-none small">

                                    View Exams

                                    <i class="bi bi-arrow-right ms-1"></i>

                                </a>

                            </div>
                        @endif

                    </div>

                </div>

            </div>

        </div>


        {{-- Main Content --}}
        <div class="row g-4 mb-4">

            {{-- Today's Classes --}}
            <div class="col-lg-8">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-header bg-white border-0 px-4 pt-4">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <h5 class="fw-bold mb-1">
                                    Today's Classes
                                </h5>

                                <p class="text-muted small mb-0">
                                    Your scheduled classes for today
                                </p>

                            </div>

                            @if (hasPermission('class-schedules.index'))
                                <a href="{{ route('admin.class-schedules.index') }}" class="btn btn-sm btn-light">

                                    View All

                                </a>
                            @endif

                        </div>

                    </div>

                    <div class="card-body px-4 pb-4">

                        @if (isset($todayClasses) && $todayClasses->count())

                            <div class="list-group list-group-flush">

                                @foreach ($todayClasses as $class)
                                    <div class="list-group-item px-0 py-3">

                                        <div class="d-flex align-items-center">

                                            <div class="bg-primary-subtle text-primary rounded-3 p-3 me-3">

                                                <i class="bi bi-calendar-event fs-5"></i>

                                            </div>

                                            <div class="flex-grow-1">

                                                <div class="fw-semibold">
                                                    {{ $class->studentClass->name ?? 'Class' }}
                                                </div>

                                                <small class="text-muted">

                                                    {{ $class->start_time ?? '--:--' }}

                                                    @if (isset($class->end_time))
                                                        - {{ $class->end_time }}
                                                    @endif

                                                </small>

                                            </div>

                                            @if (isset($class->status))
                                                <span class="badge bg-success-subtle text-success">
                                                    {{ ucfirst($class->status) }}
                                                </span>
                                            @endif

                                        </div>

                                    </div>
                                @endforeach

                            </div>
                        @else
                            <div class="text-center py-5">

                                <div class="text-muted mb-3">

                                    <i class="bi bi-calendar-x fs-1"></i>

                                </div>

                                <h6 class="fw-semibold">
                                    No classes scheduled today
                                </h6>

                                <p class="text-muted small mb-0">
                                    You don't have any scheduled classes for today.
                                </p>

                            </div>

                        @endif

                    </div>

                </div>

            </div>


            {{-- Teacher Profile --}}
            <div class="col-lg-4">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-header bg-white border-0 px-4 pt-4">

                        <h5 class="fw-bold mb-1">
                            My Profile
                        </h5>

                        <p class="text-muted small mb-0">
                            Teacher account information
                        </p>

                    </div>

                    <div class="card-body px-4 pb-4">

                        <div class="text-center mb-4">

                            <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                                style="width: 70px; height: 70px;">

                                <i class="bi bi-person-workspace fs-2"></i>

                            </div>

                            <h5 class="fw-bold mt-3 mb-1">
                                {{ auth()->user()->name }}
                            </h5>

                            <p class="text-muted small mb-0">
                                {{ auth()->user()->email }}
                            </p>

                        </div>


                        <div class="border-top pt-3">

                            <div class="d-flex justify-content-between mb-3">

                                <span class="text-muted">
                                    Role
                                </span>

                                <span class="fw-semibold">
                                    {{ auth()->user()->userType->name ?? 'Teacher' }}
                                </span>

                            </div>


                            <div class="d-flex justify-content-between mb-3">

                                <span class="text-muted">
                                    Status
                                </span>

                                <span class="badge bg-success-subtle text-success">
                                    Active
                                </span>

                            </div>


                            <div class="d-flex justify-content-between">

                                <span class="text-muted">
                                    Branches
                                </span>

                                <span class="fw-semibold">

                                    {{ isset($branches) ? $branches->count() : 0 }}

                                </span>

                            </div>

                        </div>


                        <div class="mt-4">

                            <a href="{{ route('admin.profile.index') }}" class="btn btn-light w-100">

                                <i class="bi bi-person me-1"></i>

                                View Profile

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Quick Actions --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white border-0 px-4 pt-4">

                <h5 class="fw-bold mb-1">
                    Quick Actions
                </h5>

                <p class="text-muted small mb-0">
                    Teaching features available to you
                </p>

            </div>


            <div class="card-body p-4">

                <div class="row g-3">


                    {{-- My Classes --}}
                    @if (hasPermission('student-classes.index'))
                        <div class="col-md-4 col-lg-3">

                            <a href="{{ route('admin.student-classes.index') }}" class="text-decoration-none">

                                <div class="border rounded-4 p-3 h-100">

                                    <div class="bg-primary-subtle text-primary rounded-3 p-2 d-inline-flex mb-3">

                                        <i class="bi bi-mortarboard"></i>

                                    </div>

                                    <h6 class="fw-bold text-dark mb-1">
                                        My Classes
                                    </h6>

                                    <small class="text-muted">
                                        View assigned classes
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Timetable --}}
                    @if (hasPermission('class-schedules.index'))
                        <div class="col-md-4 col-lg-3">

                            <a href="{{ route('admin.class-schedules.index') }}" class="text-decoration-none">

                                <div class="border rounded-4 p-3 h-100">

                                    <div class="bg-warning-subtle text-warning rounded-3 p-2 d-inline-flex mb-3">

                                        <i class="bi bi-calendar3"></i>

                                    </div>

                                    <h6 class="fw-bold text-dark mb-1">
                                        Timetable
                                    </h6>

                                    <small class="text-muted">
                                        View class timetable
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Attendance --}}
                    @if (hasPermission('attendance.index'))
                        <div class="col-md-4 col-lg-3">

                            <a href="{{ route('admin.attendance.index') }}" class="text-decoration-none">

                                <div class="border rounded-4 p-3 h-100">

                                    <div class="bg-success-subtle text-success rounded-3 p-2 d-inline-flex mb-3">

                                        <i class="bi bi-calendar-check"></i>

                                    </div>

                                    <h6 class="fw-bold text-dark mb-1">
                                        Attendance
                                    </h6>

                                    <small class="text-muted">
                                        View attendance
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Exams --}}
                    @if (hasPermission('exams.index'))
                        <div class="col-md-4 col-lg-3">

                            <a href="{{ route('admin.exams.index') }}" class="text-decoration-none">

                                <div class="border rounded-4 p-3 h-100">

                                    <div class="bg-danger-subtle text-danger rounded-3 p-2 d-inline-flex mb-3">

                                        <i class="bi bi-file-earmark-text"></i>

                                    </div>

                                    <h6 class="fw-bold text-dark mb-1">
                                        Exams
                                    </h6>

                                    <small class="text-muted">
                                        View and manage exams
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Student Images --}}
                    @if (hasPermission('student-images.index'))
                        <div class="col-md-4 col-lg-3">

                            <a href="{{ route('admin.student-images.index') }}" class="text-decoration-none">

                                <div class="border rounded-4 p-3 h-100">

                                    <div class="bg-info-subtle text-info rounded-3 p-2 d-inline-flex mb-3">

                                        <i class="bi bi-images"></i>

                                    </div>

                                    <h6 class="fw-bold text-dark mb-1">
                                        Student Images
                                    </h6>

                                    <small class="text-muted">
                                        View student images
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Weekly Timetable --}}
                    @if (hasPermission('weekly-timetable'))
                        <div class="col-md-4 col-lg-3">

                            <a href="{{ route('admin.weekly-timetable') }}" class="text-decoration-none">

                                <div class="border rounded-4 p-3 h-100">

                                    <div class="bg-secondary-subtle text-secondary rounded-3 p-2 d-inline-flex mb-3">

                                        <i class="bi bi-calendar-week"></i>

                                    </div>

                                    <h6 class="fw-bold text-dark mb-1">
                                        Weekly Timetable
                                    </h6>

                                    <small class="text-muted">
                                        View weekly schedule
                                    </small>

                                </div>

                            </a>

                        </div>
                    @endif


                    {{-- Profile --}}
                    <div class="col-md-4 col-lg-3">

                        <a href="{{ route('admin.profile.index') }}" class="text-decoration-none">

                            <div class="border rounded-4 p-3 h-100">

                                <div class="bg-dark-subtle text-dark rounded-3 p-2 d-inline-flex mb-3">

                                    <i class="bi bi-person"></i>

                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    My Profile
                                </h6>

                                <small class="text-muted">
                                    Manage your profile
                                </small>

                            </div>

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection
