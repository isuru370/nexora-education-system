@extends('layouts.app')

@section('title', 'Edit Class Hall')
@section('page-title', 'Edit Class Hall')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Edit Class Hall</h5>
            <small class="text-muted">
                {{ $classHall->hall_name }}
                &middot;
                {{ $classHall->branch->name ?? 'No Branch' }}
            </small>
        </div>

        <a href="{{ route('class-halls.index') }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card-body">
        @include('class_halls.partials.form', [
            'classHall' => $classHall,
            'branches' => $branches,
            'buttonText' => 'Update Hall'
        ])
    </div>
</div>
@endsection
