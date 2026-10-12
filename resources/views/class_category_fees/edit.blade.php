@extends('layouts.app')

@section('title', 'Edit Class Category Fee')
@section('page-title', 'Edit Class Category Fee')

@section('content')

@php
    $studentClass = optional($classCategoryFee)->studentClass;
    $category = optional($classCategoryFee)->category;
@endphp

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Edit Class Category Fee</h4>

            <p class="text-muted mb-0">
                {{ optional($studentClass)->class_name ?? '-' }}
                /
                {{ optional($category)->category_name ?? '-' }}
            </p>
        </div>

        <a href="{{ route('class-category-fees.index') }}"
           class="btn btn-secondary">
            Back
        </a>
    </div>


    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>

            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- Form --}}
    @include('class_category_fees.partials.form', [
        'classCategoryFee' => $classCategoryFee,
        'classes' => $classes,
        'categories' => $categories,
        'selectedClassId' => optional($studentClass)->id,
        'buttonText' => 'Update'
    ])

</div>

@endsection
