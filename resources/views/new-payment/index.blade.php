@extends('layouts.app')

@section('title', 'New Payment')
@section('page-title', 'New Payment')

@section('content')
    <div class="container-fluid py-4">
        @include('new-payment.partials.header')
        @include('new-payment.partials.student-search')

        <div id="payment-loading" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 text-muted">Loading student information...</div>
        </div>

        <div id="payment-error" class="alert alert-danger d-none"></div>

        <div id="student-section" class="d-none">
            @include('new-payment.partials.student-info')
            @include('new-payment.partials.classes')
        </div>
    </div>

    @include('new-payment.partials.payment-modal')
    @include('new-payment.partials.receipt-modal')
@endsection


{{-- ============================================================= --}}
{{-- STYLES --}}
{{-- ============================================================= --}}
@push('styles')
    @include('new-payment.styles')
@endpush


{{-- ============================================================= --}}
{{-- SCRIPTS --}}
{{-- ============================================================= --}}
@push('scripts')
    @include('new-payment.scripts')
@endpush