@extends('layouts.app')

@section('title', 'Create Enrollment')
@section('page-title', 'Create Enrollment')

@section('content')

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="fw-bold mb-1">
                Create Student Enrollment
            </h4>

            <p class="text-muted mb-0">
                Enroll a student into a class category.
            </p>
        </div>

        <div>
            <a href="{{ route('student-class-enrollments.index') }}"
               class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                Back
            </a>
        </div>

    </div>


    {{-- Validation Errors --}}
    @if ($errors->any())

        <div class="alert alert-danger border-0 shadow-sm rounded-4">

            <strong>Please fix the following errors:</strong>

            <ul class="mb-0 mt-2">

                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    {{-- Create Form --}}
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <form action="{{ route('student-class-enrollments.store') }}"
                  method="POST">

                @csrf

                @include(
                    'student-class-enrollments.partials.form',
                    [
                        'enrollment'  => $enrollment ?? new \App\Models\StudentClassEnrollment(),
                        'buttonText'  => 'Create Enrollment',
                        'feeOptions'  => $feeOptions ?? collect(),
                    ]
                )

            </form>

        </div>

    </div>

</div>

@endsection