@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div class="nx-card p-4 text-center">
        <i class="bi bi-diagram-3 text-primary" style="font-size: 2.5rem;"></i>
        <h4 class="fw-bold mt-3 mb-2">No branch assigned</h4>
        <p class="text-muted mb-0">
            Your account is not linked to a branch yet. Ask the Super Admin to add your branch
            (Users &gt; edit your account &gt; Branches).
        </p>
    </div>
@endsection
