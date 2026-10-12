@extends('layouts.app')

@section('title', 'Create Organizer')
@section('page-title', 'Create Organizer')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-person-plus me-2 text-primary"></i>
            Create Organizer
        </h5>

        <a href="{{ route('organizers.index') }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card-body">
        @include('organizers.partials.form', [
            'organizer'        => null,
            'branches'         => $branches,
            'selectedBranches' => [],
            'buttonText'       => 'Save Organizer',
        ])
    </div>
</div>
@endsection