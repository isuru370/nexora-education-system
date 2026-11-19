@extends('layouts.app')

@section('title', 'New Payment')
@section('page-title', 'New Payment')

@section('content')
    <div class="ui-container-fluid tw:py-6!">
        @include('new-payment.partials.header')
        @include('new-payment.partials.student-search')

        <div id="payment-loading" class="bs-text-center tw:text-center! tw:py-12! tw:hidden!">
            <div class="ui-spinner-border tw:text-bs-primary!" role="status"></div>
            <div class="tw:mt-2! tw:text-bs-secondary-color!">Loading student information...</div>
        </div>

        <div id="payment-error" class="ui-alert ui-alert-danger tw:hidden!"></div>

        <div id="student-section" class="tw:hidden!">
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
    {{-- What the receipt shows (Settings > Receipt) --}}
    <script>window.NX_RECEIPT = @json(\App\Support\ReceiptRules::all());</script>
    @include('new-payment.scripts')
@endpush