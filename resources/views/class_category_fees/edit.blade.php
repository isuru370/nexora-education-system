@extends('layouts.app')

@section('title', 'Edit Class Category Fee')
@section('page-title', 'Edit Class Category Fee')

@section('content')

@php
    $studentClass = optional($classCategoryFee)->studentClass;
    $category     = optional($classCategoryFee)->category;
@endphp

<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Edit Class Category Fee</h1>
                <p class="hero-subtitle">
                    {{ optional($studentClass)->class_name ?? '-' }}
                    /
                    {{ optional($category)->category_name ?? '-' }}
                </p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-category-fees.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- ERRORS --}}
    @if ($errors->any())
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- FORM CARD --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-pencil-square text-primary"></i> Edit Class Category Fee</h5>
                <p class="nx-card-subtitle">Update the category and fee details</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            @include('class_category_fees.partials.form', [
                'classCategoryFee' => $classCategoryFee,
                'classes' => $classes,
                'categories' => $categories,
                'selectedClassId' => optional($studentClass)->id,
                'buttonText' => 'Update',
            ])
        </div>
    </div>

</div>
@endsection