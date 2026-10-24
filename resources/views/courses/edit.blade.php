@extends('layouts.app')

@section('title', 'Edit Course')
@section('page-title', 'Edit Course')

@section('content')
<div class="dashboard-page">
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Courses</span>
                <h1 class="hero-title">{{ $course->name }}</h1>
                <p class="hero-subtitle">Days with attendance are kept when the schedule changes.</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('courses.show', $course) }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')
    @include('courses.partials.form')
</div>
@endsection

@include('courses.partials.styles')
