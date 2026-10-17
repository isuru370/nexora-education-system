@extends('layouts.app')

@section('title', 'Create Admission')
@section('page-title', 'Create Admission')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">
                    <i class="bi bi-plus-circle-fill"></i> Create New
                </span>
                <h1 class="hero-title">Create Admission</h1>
                <p class="hero-subtitle">Add a new admission fee structure to the system.</p>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('admissions.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- INFO BOX --}}
    <div class="nx-alert nx-alert-blue mb-4">
        <i class="bi bi-info-circle-fill"></i>
        <span>
            Fill in the details below to create a new admission fee structure.
            All fields marked with <strong style="color: var(--nx-red);">*</strong> are required.
        </span>
    </div>

    {{-- FORM CARD --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-journal-plus text-primary"></i> Admission Information</h5>
                <p class="nx-card-subtitle">New fee structure details</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            <form action="{{ route('admissions.store') }}" method="POST">
                @csrf

                {{-- NAME --}}
                <div class="nx-form-group mb-3">
                    <label class="nx-label">
                        <i class="bi bi-tag-fill"></i> Admission Name <span style="color: var(--nx-red);">*</span>
                    </label>
                    <input type="text" name="name"
                        class="form-control nx-input @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        placeholder="Enter admission name (e.g., Primary Admission, Secondary Admission)"
                        autofocus>
                    @error('name')
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- AMOUNT --}}
                <div class="nx-form-group mb-3">
                    <label class="nx-label">
                        <i class="bi bi-cash-stack"></i> Amount <span style="color: var(--nx-red);">*</span>
                    </label>
                    <div class="currency-input">
                        <span class="currency-symbol">Rs.</span>
                        <input type="number" step="0.01" name="amount"
                            class="form-control nx-input @error('amount') is-invalid @enderror"
                            value="{{ old('amount') }}"
                            placeholder="0.00">
                    </div>
                    @error('amount')
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- NOTE --}}
                <div class="nx-form-group mb-3">
                    <label class="nx-label">
                        <i class="bi bi-chat-text-fill"></i> Note (Optional)
                    </label>
                    <textarea name="note" rows="4"
                        class="form-control nx-input @error('note') is-invalid @enderror"
                        placeholder="Enter any additional notes or description about this admission...">{{ old('note') }}</textarea>
                    @error('note')
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- STATUS TOGGLE --}}
                <div class="toggle-switch mb-3">
                    <div class="custom-checkbox">
                        <input type="checkbox" name="is_active" value="1" id="isActive"
                            {{ old('is_active', true) ? 'checked' : '' }}>
                        <label for="isActive" class="toggle-label">
                            <i class="bi bi-flag-fill"></i> Active
                        </label>
                    </div>
                    <div class="toggle-description">
                        When active, this admission will be available for student enrollment
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="nx-form-actions">
                    <a href="{{ route('admissions.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-x-lg"></i> Cancel
                    </a>
                    <button type="submit" class="nx-btn nx-btn-primary">
                        <i class="bi bi-check-lg"></i> Save Admission
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="text-center">
        <small class="cell-sub">
            <i class="bi bi-shield-check"></i>
            All admission records are securely stored and can be managed from the admissions list
        </small>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Create Admission — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .currency-input {
        position: relative;
    }
    .dashboard-page .currency-symbol {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-weight: 600;
        color: var(--nx-muted);
        pointer-events: none;
        z-index: 2;
    }
    .dashboard-page .currency-input .nx-input {
        padding-left: 2.75rem;
    }

    .dashboard-page .toggle-switch {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .85rem 1rem;
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        flex-wrap: wrap;
    }

    .dashboard-page .custom-checkbox {
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
        gap: .5rem;
    }
    .dashboard-page .custom-checkbox input {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: var(--nx-blue);
    }
    .dashboard-page .toggle-label {
        font-weight: 600;
        color: var(--nx-text);
        margin: 0;
        cursor: pointer;
        font-size: .85rem;
    }
    .dashboard-page .toggle-description {
        font-size: .74rem;
        color: var(--nx-muted);
        margin-left: auto;
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
        .dashboard-page .toggle-description {
            margin-left: 0;
            width: 100%;
        }
    }
</style>
@endpush