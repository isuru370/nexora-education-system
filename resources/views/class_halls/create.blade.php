@extends('layouts.app')

@section('title', 'Create Class Hall')
@section('page-title', 'Create Class Hall')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Create Class Hall</h1>
                <p class="hero-subtitle">Register a new hall for a branch.</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-halls.index') }}" class="nx-btn nx-btn-light">
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
                <h5 class="nx-card-title"><i class="bi bi-plus-circle-fill text-primary"></i> New Class Hall</h5>
                <p class="nx-card-subtitle">Fill in the details to create a new hall</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            @include('class_halls.partials.form', [
                'classHall' => null,
                'branches' => $branches,
                'buttonText' => 'Save Hall',
            ])
        </div>
    </div>

</div>
@endsection