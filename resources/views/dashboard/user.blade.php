@extends('layouts.app')

@section('title', 'User Dashboard')

@section('content')

<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h3 class="fw-bold mb-1">
                User Dashboard
            </h3>

            <p class="text-muted mb-0">
                Manage your assigned branch operations
            </p>
        </div>

        <div class="mt-2 mt-md-0">
            <span class="badge bg-primary-subtle text-primary px-3 py-2">
                <i class="bi bi-person me-1"></i>
                User
            </span>
        </div>

    </div>


    {{-- Statistics --}}
    <div class="row g-4 mb-4">

        {{-- Students --}}
        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>
                            <p class="text-muted mb-2">
                                Students
                            </p>

                            <h3 class="fw-bold mb-0">
                                {{ $studentsCount ?? 0 }}
                            </h3>
                        </div>

                        <div class="bg-primary-subtle text-primary rounded-3 p-3">
                            <i class="bi bi-people fs-4"></i>
                        </div>

                    </div>

                    @if(hasPermission('students.index'))
                        <div class="mt-3">
                            <a href="{{ route('admin.students.index') }}"
                               class="text-decoration-none small">
                                View Students
                                <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    @endif

                </div>

            </div>

        </div>


        {{-- Classes --}}
        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>
                            <p class="text-muted mb-2">
                                Classes
                            </p>

                            <h3 class="fw-bold mb-0">
                                {{ $classesCount ?? 0 }}
                            </h3>
                        </div>

                        <div class="bg-warning-subtle text-warning rounded-3 p-3">
                            <i class="bi bi-mortarboard fs-4"></i>
                        </div>

                    </div>

                    @if(hasPermission('student-classes.index'))
                        <div class="mt-3">
                            <a href="{{ route('admin.student-classes.index') }}"
                               class="text-decoration-none small">
                                View Classes
                                <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    @endif

                </div>

            </div>

        </div>


        {{-- Monthly Income --}}
        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>
                            <p class="text-muted mb-2">
                                Monthly Income
                            </p>

                            <h3 class="fw-bold mb-0">
                                LKR {{ number_format($monthlyIncome ?? 0, 2) }}
                            </h3>
                        </div>

                        <div class="bg-success-subtle text-success rounded-3 p-3">
                            <i class="bi bi-cash-stack fs-4"></i>
                        </div>

                    </div>

                    <div class="mt-3">
                        <span class="text-muted small">
                            Current month
                        </span>
                    </div>

                </div>

            </div>

        </div>


        {{-- Today's Income --}}
        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>
                            <p class="text-muted mb-2">
                                Today's Income
                            </p>

                            <h3 class="fw-bold mb-0">
                                LKR {{ number_format($todayIncome ?? 0, 2) }}
                            </h3>
                        </div>

                        <div class="bg-info-subtle text-info rounded-3 p-3">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>

                    </div>

                    <div class="mt-3">
                        <span class="text-muted small">
                            Today's collection
                        </span>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Assigned Branches --}}
    <div class="row g-4 mb-4">

        <div class="col-lg-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white border-0 px-4 pt-4">

                    <h5 class="fw-bold mb-1">
                        My Branches
                    </h5>

                    <p class="text-muted small mb-0">
                        Branches assigned to your account
                    </p>

                </div>

                <div class="card-body px-4 pb-4">

                    @if(isset($branches) && $branches->count())

                        <div class="list-group list-group-flush">

                            @foreach($branches as $branch)

                                <div class="list-group-item px-0 d-flex align-items-center">

                                    <div class="bg-primary-subtle text-primary rounded-3 p-2 me-3">
                                        <i class="bi bi-building"></i>
                                    </div>

                                    <div class="flex-grow-1">

                                        <div class="fw-semibold">
                                            {{ $branch->name }}
                                        </div>

                                        <small class="text-muted">
                                            {{ $branch->code }}
                                        </small>

                                    </div>

                                    <span class="badge bg-success-subtle text-success">
                                        Active
                                    </span>

                                </div>

                            @endforeach

                        </div>

                    @else

                        <div class="text-center py-4">

                            <div class="text-muted mb-2">
                                <i class="bi bi-building fs-1"></i>
                            </div>

                            <p class="text-muted mb-0">
                                No branches assigned.
                            </p>

                        </div>

                    @endif

                </div>

            </div>

        </div>


        {{-- Account Information --}}
        <div class="col-lg-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white border-0 px-4 pt-4">

                    <h5 class="fw-bold mb-1">
                        Account Information
                    </h5>

                    <p class="text-muted small mb-0">
                        Your current account details
                    </p>

                </div>

                <div class="card-body px-4 pb-4">

                    <div class="d-flex align-items-center mb-3">

                        <div class="bg-primary-subtle text-primary rounded-circle p-3 me-3">
                            <i class="bi bi-person fs-4"></i>
                        </div>

                        <div>

                            <div class="fw-semibold">
                                {{ auth()->user()->name }}
                            </div>

                            <small class="text-muted">
                                {{ auth()->user()->email }}
                            </small>

                        </div>

                    </div>

                    <div class="border-top pt-3">

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">
                                Role
                            </span>

                            <span class="fw-semibold">
                                {{ auth()->user()->userType->name ?? 'User' }}
                            </span>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">
                                Status
                            </span>

                            <span class="badge bg-success-subtle text-success">
                                Active
                            </span>
                        </div>

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
                Access features based on your permissions
            </p>

        </div>

        <div class="card-body p-4">

            <div class="row g-3">

                {{-- Students --}}
                @if(hasPermission('students.index'))

                    <div class="col-md-4 col-lg-3">

                        <a href="{{ route('admin.students.index') }}"
                           class="text-decoration-none">

                            <div class="border rounded-4 p-3 h-100">

                                <div class="bg-primary-subtle text-primary rounded-3 p-2 d-inline-flex mb-3">
                                    <i class="bi bi-people"></i>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    Students
                                </h6>

                                <small class="text-muted">
                                    Manage students
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Classes --}}
                @if(hasPermission('student-classes.index'))

                    <div class="col-md-4 col-lg-3">

                        <a href="{{ route('admin.student-classes.index') }}"
                           class="text-decoration-none">

                            <div class="border rounded-4 p-3 h-100">

                                <div class="bg-warning-subtle text-warning rounded-3 p-2 d-inline-flex mb-3">
                                    <i class="bi bi-mortarboard"></i>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    Classes
                                </h6>

                                <small class="text-muted">
                                    Manage classes
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Attendance --}}
                @if(hasPermission('attendance.index'))

                    <div class="col-md-4 col-lg-3">

                        <a href="{{ route('admin.attendance.index') }}"
                           class="text-decoration-none">

                            <div class="border rounded-4 p-3 h-100">

                                <div class="bg-success-subtle text-success rounded-3 p-2 d-inline-flex mb-3">
                                    <i class="bi bi-calendar-check"></i>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    Attendance
                                </h6>

                                <small class="text-muted">
                                    Manage attendance
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Payments --}}
                @if(hasPermission('payments.index'))

                    <div class="col-md-4 col-lg-3">

                        <a href="{{ route('admin.payments.index') }}"
                           class="text-decoration-none">

                            <div class="border rounded-4 p-3 h-100">

                                <div class="bg-info-subtle text-info rounded-3 p-2 d-inline-flex mb-3">
                                    <i class="bi bi-credit-card"></i>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    Payments
                                </h6>

                                <small class="text-muted">
                                    Manage payments
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Exams --}}
                @if(hasPermission('exams.index'))

                    <div class="col-md-4 col-lg-3">

                        <a href="{{ route('admin.exams.index') }}"
                           class="text-decoration-none">

                            <div class="border rounded-4 p-3 h-100">

                                <div class="bg-danger-subtle text-danger rounded-3 p-2 d-inline-flex mb-3">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    Exams
                                </h6>

                                <small class="text-muted">
                                    Manage exams
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Timetable --}}
                @if(hasPermission('weekly-timetable'))

                    <div class="col-md-4 col-lg-3">

                        <a href="{{ route('admin.weekly-timetable') }}"
                           class="text-decoration-none">

                            <div class="border rounded-4 p-3 h-100">

                                <div class="bg-secondary-subtle text-secondary rounded-3 p-2 d-inline-flex mb-3">
                                    <i class="bi bi-calendar3"></i>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    Timetable
                                </h6>

                                <small class="text-muted">
                                    View weekly timetable
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Student Images --}}
                @if(hasPermission('student-images.index'))

                    <div class="col-md-4 col-lg-3">

                        <a href="{{ route('admin.student-images.index') }}"
                           class="text-decoration-none">

                            <div class="border rounded-4 p-3 h-100">

                                <div class="bg-dark-subtle text-dark rounded-3 p-2 d-inline-flex mb-3">
                                    <i class="bi bi-images"></i>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    Student Images
                                </h6>

                                <small class="text-muted">
                                    Manage student images
                                </small>

                            </div>

                        </a>

                    </div>

                @endif


                {{-- Notifications --}}
                @if(hasPermission('notification.view'))

                    <div class="col-md-4 col-lg-3">

                        <a href="{{ route('admin.notifications.index') }}"
                           class="text-decoration-none">

                            <div class="border rounded-4 p-3 h-100">

                                <div class="bg-primary-subtle text-primary rounded-3 p-2 d-inline-flex mb-3">
                                    <i class="bi bi-bell"></i>
                                </div>

                                <h6 class="fw-bold text-dark mb-1">
                                    Notifications
                                </h6>

                                <small class="text-muted">
                                    Manage notifications
                                </small>

                            </div>

                        </a>

                    </div>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection