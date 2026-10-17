@extends('layouts.app')

@section('title', 'Create Admission Payment')
@section('page-title', 'Create Admission Payment')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Create Admission Payment</h1>
                <p class="hero-subtitle">Add a new payment record for a student admission.</p>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('admission-payments.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <small class="hero-updated">New Record</small>
            </div>
        </div>
    </div>

    {{-- QUICK NOTES --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-info-circle text-primary"></i> Quick Notes</h5>
                <p class="nx-card-subtitle">Things to keep in mind when creating a payment</p>
            </div>
        </div>

        <div class="p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="quick-note">
                        <i class="bi bi-shield-check"></i>
                        <span>Required fields are marked with <strong style="color: var(--nx-red);">*</strong></span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="quick-note">
                        <i class="bi bi-credit-card"></i>
                        <span>Payment method must match available options</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="quick-note">
                        <i class="bi bi-clock-history"></i>
                        <span>Status starts as <strong>Paid</strong> by default</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FORM CARD --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-plus-circle-fill text-primary"></i> Payment Details</h5>
                <p class="nx-card-subtitle">Fill in the information below to create the payment</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            <form action="{{ route('admission-payments.store') }}" method="POST">
                @csrf

                @include('admission_payments.partials.form', ['admissionPayment' => null])

                <div class="nx-form-actions">
                    <a href="{{ route('admission-payments.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>

                    <button type="submit" class="nx-btn nx-btn-primary">
                        <i class="bi bi-check2-circle"></i> Save Payment
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
       Create Admission Payment — page-specific styles only.
       ============================================================ */

    .dashboard-page .quick-note {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .75rem .85rem;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        background: #fafbfd;
        font-size: .82rem;
        color: var(--nx-text);
    }
    .dashboard-page .quick-note i {
        color: var(--nx-blue);
        font-size: 1.05rem;
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