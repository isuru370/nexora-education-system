@extends('layouts.app')

@section('title', 'Create Schedule')
@section('page-title', 'Create Schedule')

@section('content')
@php
    $scheduleType = $scheduleType ?? request('type', 'recurring');

    $pageTitle = $scheduleType === 'single'
        ? 'Create Single Day Schedule'
        : 'Create Recurring Schedule';

    $pageDescription = $scheduleType === 'single'
        ? 'Add one class schedule date'
        : 'Generate schedules between start and end date';

    $buttonText = $scheduleType === 'single'
        ? 'Save Single Schedule'
        : 'Save Recurring Schedule';
@endphp

<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Class Scheduling</span>
                <h1 class="hero-title">{{ $pageTitle }}</h1>
                <p class="hero-subtitle">{{ $pageDescription }}</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-schedules.index') }}" class="nx-btn nx-btn-light">
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
                <h5 class="nx-card-title"><i class="bi bi-calendar-plus text-primary"></i> {{ $pageTitle }}</h5>
                <p class="nx-card-subtitle">{{ $pageDescription }}</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            @include('class_schedules.partials.form', [
                'classSchedule'         => null,
                'scheduleType'          => $scheduleType,
                'selectedClass'         => $selectedClass ?? null,
                'selectedCategory'      => $selectedCategory ?? null,
                'selectedClassId'       => $selectedClassId ?? null,
                'selectedCategoryFeeId' => $selectedCategoryFeeId ?? null,
                'halls'                 => $halls,
                'buttonText'            => $buttonText,
            ])
        </div>
    </div>

</div>
@endsection