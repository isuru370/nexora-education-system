@extends('layouts.app')

@section('title', 'Edit Class Category')
@section('page-title', 'Edit Class Category')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Edit Class Category</h1>
                <p class="hero-subtitle">{{ $classCategory->category_name }}</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-categories.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- FORM CARD --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-pencil-square text-primary"></i> Edit Category</h5>
                <p class="nx-card-subtitle">Update the category details</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            @include('class_categories.partials.form', [
                'category' => $classCategory,
                'buttonText' => 'Update Category',
            ])
        </div>
    </div>

</div>
@endsection