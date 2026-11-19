@extends('layouts.app')

@section('title', 'Create Enrollment')
@section('page-title', 'Create Enrollment')

@section('content')

<div class="ui-container-fluid">

    {{-- Page Header --}}
    <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-6!">

        <div>
            <h4 class="tw:font-bold! tw:mb-1!">
                Create Student Enrollment
            </h4>

            <p class="tw:text-bs-secondary-color! tw:mb-0!">
                Enroll a student into a class category.
            </p>
        </div>

        <div>
            <a href="{{ route('student-class-enrollments.index') }}"
               class="ui-btn ui-btn-outline-secondary">
                <i class="bi bi-arrow-left tw:mr-1!"></i>
                Back
            </a>
        </div>

    </div>


    {{-- Validation Errors --}}
    @if ($errors->any())

        <div class="ui-alert ui-alert-danger tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">

            <strong>Please fix the following errors:</strong>

            <ul class="tw:mb-0! tw:mt-2!">

                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    {{-- Create Form --}}
    <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">

        <div class="ui-card-body tw:p-6!">

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