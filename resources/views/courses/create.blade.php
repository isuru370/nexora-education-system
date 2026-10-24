@extends('layouts.app')

@section('title', 'New Course')
@section('page-title', 'New Course')

@section('content')
<div class="dashboard-page">
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Courses</span>
                <h1 class="hero-title">New Course</h1>
                <p class="hero-subtitle">Fees, schedule and the teachers' share of the income.</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('courses.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')
    @include('courses.partials.form')
</div>
@endsection

@include('courses.partials.styles')
