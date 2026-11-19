@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="nx-card tw:p-6! tw:text-center!">
        <i class="bi bi-diagram-3 tw:text-bs-primary!" style="font-size: 2.5rem;"></i>
        <h4 class="tw:font-bold! tw:mt-4! tw:mb-2!">No branch assigned</h4>
        <p class="tw:text-bs-secondary-color! tw:mb-0!">
            Your account is not linked to a branch yet. Ask the Super Admin to add your branch
            (Users &gt; edit your account &gt; Branches).
        </p>
    </div>
@endsection
