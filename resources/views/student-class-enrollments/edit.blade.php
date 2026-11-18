@extends('layouts.app')

@section('title', 'Edit Enrollment')
@section('page-title', 'Edit Enrollment')

@section('content')

<div class="ui-container-fluid">

    {{-- Page Header --}}
    <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-6!">

        <div>
            <h4 class="tw:font-bold! tw:mb-1!">
                Edit Student Enrollment
            </h4>

            <p class="tw:text-bs-secondary-color! tw:mb-0!">
                {{ $enrollment->student->custom_id ?? '-' }}
                -
                {{ $enrollment->student->initial_name ?? '-' }}
            </p>
        </div>

        <div>
            <a href="{{ route('student-class-enrollments.show', $enrollment) }}"
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


    {{-- Edit Form --}}
    <div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">

        <div class="ui-card-body tw:p-6!">

            <form action="{{ route('student-class-enrollments.update', $enrollment) }}"
                  method="POST">

                @csrf
                @method('PUT')

                @include(
                    'student-class-enrollments.partials.form',
                    [
                        'enrollment'  => $enrollment,
                        'buttonText'  => 'Update Enrollment',
                        'feeOptions'  => $feeOptions ?? collect(),
                    ]
                )

            </form>

        </div>

    </div>

</div>

@endsection