@extends('layouts.app')

@section('title', 'Edit Class Schedule')
@section('page-title', 'Edit Class Schedule')

@section('content')
@php
    $className    = optional(optional($classSchedule)->studentClass)->class_name ?: '-';
    $categoryName = optional(optional($classSchedule)->category)->category_name ?: '-';
@endphp

<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Edit Schedule</span>
                <h1 class="hero-title">Edit Class Schedule</h1>
                <p class="hero-subtitle">{{ $className }} / {{ $categoryName }}</p>
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
                <h5 class="nx-card-title"><i class="bi bi-pencil-square text-primary"></i> Edit Schedule</h5>
                <p class="nx-card-subtitle">Update schedule details</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            @include('class_schedules.partials.form', [
                'classSchedule'         => $classSchedule,
                'scheduleType'          => 'single',
                'selectedClass'         => $classSchedule->studentClass ?? null,
                'selectedCategory'      => $classSchedule->classCategoryFee->category ?? null,
                'selectedClassId'       => $classSchedule->student_class_id,
                'selectedCategoryFeeId' => $classSchedule->class_category_fee_id,
                'halls'                 => $halls,
                'buttonText'            => 'Update Schedule',
            ])
        </div>
    </div>

</div>
@endsection