@extends('layouts.app')

@section('title', 'Edit Organizer')
@section('page-title', 'Edit Organizer')

@section('content')
<div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">
    <div class="ui-card-header tw:bg-bs-white! tw:flex! tw:justify-between! tw:items-center!">
        <h5 class="tw:font-bold! tw:mb-0!">
            <i class="bi bi-pencil-square tw:mr-2! tw:text-bs-primary!"></i>
            Edit Organizer
        </h5>

        <a href="{{ route('organizers.index') }}"
           class="ui-btn ui-btn-outline-secondary ui-btn-sm">
            <i class="bi bi-arrow-left tw:mr-1!"></i> Back
        </a>
    </div>

    <div class="ui-card-body">
        @include('organizers.partials.form', [
            'organizer'        => $organizer,
            'branches'         => $branches,
            'selectedBranches' => $selectedBranches ?? $organizer->branches->pluck('id')->toArray(),
            'buttonText'       => 'Update Organizer',
        ])
    </div>
</div>
@endsection