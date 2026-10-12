@extends('layouts.app')

@section('title', 'Create Class Category Fee')
@section('page-title', 'Create Class Category Fee')

@section('content')

    <div class="container-fluid">

        <div class="mb-4">
            <h4 class="mb-1">
                Create Class Category Fee
            </h4>

            <p class="text-muted mb-0">
                Assign a category to a class.
            </p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('class_category_fees.partials.form', [
            'classCategoryFee' => null,
            'classes' => $classes,
            'categories' => $categories,
            'selectedClassId' => $selectedClassId ?? null,
            'buttonText' => 'Save'
        ])

    </div>

@endsection