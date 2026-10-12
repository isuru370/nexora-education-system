@extends('layouts.app')

@section('title', 'Create Class Hall')
@section('page-title', 'Create Class Hall')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Create Class Hall</h5>
            <small class="text-muted">Register a new hall for a branch.</small>
        </div>

        <a href="{{ route('class-halls.index') }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card-body">
        @include('class_halls.partials.form', [
            'classHall' => null,
            'branches' => $branches,
            'buttonText' => 'Save Hall'
        ])
    </div>
</div>
@endsection
