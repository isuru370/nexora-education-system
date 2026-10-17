@extends('layouts.app')

@section('title', 'Edit Admission Payment')
@section('page-title', 'Edit Admission Payment')

@section('content')

@php
    $currentStatus = $admissionPayment->status ?? 'pending';
@endphp

<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Edit Admission Payment</h1>
                <p class="hero-subtitle">Update payment details, status, and note information.</p>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('admission-payments.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <small class="hero-updated">Editing</small>
            </div>
        </div>
    </div>

    {{-- CURRENT PAYMENT SUMMARY --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-info-circle text-primary"></i> Current Payment</h5>
                <p class="nx-card-subtitle">Quick overview of the payment being edited</p>
            </div>
        </div>

        <div class="p-3">
            <div class="row g-3">

                <div class="col-md-4">
                    <div class="summary-item">
                        <i class="bi bi-receipt"></i>
                        <div>
                            <span class="cell-sub">Receipt</span>
                            <div class="cell-title">{{ $admissionPayment->receipt_number ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="summary-item">
                        <i class="bi bi-person"></i>
                        <div>
                            <span class="cell-sub">Student</span>
                            <div class="cell-title">{{ $admissionPayment->student->initial_name ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="summary-item">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            <span class="cell-sub">Status</span>
                            <div class="cell-title">{{ ucfirst($currentStatus) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FORM CARD --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-pencil-square text-primary"></i> Payment Details</h5>
                <p class="nx-card-subtitle">Update the selected payment and save the changes</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            <form action="{{ route('admission-payments.update', $admissionPayment) }}" method="POST">
                @csrf
                @method('PUT')

                @include('admission_payments.partials.form', ['admissionPayment' => $admissionPayment])

                <div class="nx-form-actions">
                    <a href="{{ route('admission-payments.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>

                    <button type="submit" class="nx-btn nx-btn-primary">
                        <i class="bi bi-check2-circle"></i> Update Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection


@push('styles')
<style>
    /* ============================================================
       Edit Admission Payment — page-specific styles only.
       ============================================================ */

    .dashboard-page .summary-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .75rem .85rem;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        background: #fafbfd;
    }
    .dashboard-page .summary-item > i {
        color: var(--nx-amber);
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .dashboard-page .nx-form-actions {
        display: flex;
        gap: .5rem;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--nx-border);
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .dashboard-page .nx-form-actions {
            flex-direction: column;
        }
        .dashboard-page .nx-form-actions .nx-btn {
            width: 100%;
        }
    }
</style>
@endpush