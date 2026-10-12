@extends('layouts.app')

@section('title', 'Edit Organizer')
@section('page-title', 'Edit Organizer')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">
            <i class="bi bi-pencil-square me-2 text-primary"></i>
            Edit Organizer
        </h5>

        <a href="{{ route('organizers.index') }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card-body">
        @include('organizers.partials.form', [
            'organizer'        => $organizer,
            'branches'         => $branches,
            'selectedBranches' => $selectedBranches ?? $organizer->branches->pluck('id')->toArray(),
            'buttonText'       => 'Update Organizer',
        ])
    </div>
</div>
@endsection