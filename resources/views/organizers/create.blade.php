@extends('layouts.app')

@section('title', 'Create Organizer')
@section('page-title', 'Create Organizer')

@section('content')
<div class="ui-card tw:[border:0]! tw:shadow-bs-sm! tw:rounded-bs-xl!">
    <div class="ui-card-header tw:bg-bs-white! tw:flex! tw:justify-between! tw:items-center!">
        <h5 class="tw:font-bold! tw:mb-0!">
            <i class="bi bi-person-plus tw:mr-2! tw:text-bs-primary!"></i>
            Create Organizer
        </h5>

        <a href="{{ route('organizers.index') }}"
           class="ui-btn ui-btn-outline-secondary ui-btn-sm">
            <i class="bi bi-arrow-left tw:mr-1!"></i> Back
        </a>
    </div>

    <div class="ui-card-body">
        @include('organizers.partials.form', [
            'organizer'        => null,
            'branches'         => $branches,
            'selectedBranches' => [],
            'buttonText'       => 'Save Organizer',
        ])
    </div>
</div>
@endsection