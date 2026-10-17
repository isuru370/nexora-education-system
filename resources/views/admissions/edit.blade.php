@extends('layouts.app')

@section('title', 'Edit Admission')
@section('page-title', 'Edit Admission')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">
                    <i class="bi bi-pencil-square-fill"></i> Edit Record
                </span>
                <h1 class="hero-title">Edit Admission</h1>
                <p class="hero-subtitle">Update admission fee structure details.</p>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('admissions.show', $admission) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-eye"></i> View
                    </a>
                    <a href="{{ route('admissions.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
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
            Update the admission details below. Fields marked with
            <strong style="color: var(--nx-red);">*</strong> are required.
        </span>
    </div>

    {{-- WARNING IF INACTIVE --}}
    @if (!$admission->is_active)
        <div class="nx-alert nx-alert-amber mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>
                This admission is currently <strong>Inactive</strong>.
                Inactive admissions will not appear in student enrollment options.
            </span>
        </div>
    @endif

    {{-- FORM CARD --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-journal-text text-primary"></i> Edit Admission Information</h5>
                <p class="nx-card-subtitle">Update the admission fee structure</p>
            </div>
        </div>

        <div class="p-3 p-md-4">
            <form action="{{ route('admissions.update', $admission) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- NAME --}}
                <div class="nx-form-group mb-3">
                    <label class="nx-label">
                        <i class="bi bi-tag-fill"></i> Admission Name <span style="color: var(--nx-red);">*</span>
                    </label>
                    <input type="text" name="name"
                        class="form-control nx-input @error('name') is-invalid @enderror"
                        value="{{ old('name', $admission->name) }}"
                        placeholder="Enter admission name (e.g., Primary Admission, Secondary Admission)">
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
                            value="{{ old('amount', $admission->amount) }}"
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
                        placeholder="Enter any additional notes or description about this admission...">{{ old('note', $admission->note) }}</textarea>
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
                            {{ old('is_active', $admission->is_active) ? 'checked' : '' }}>
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
                        <i class="bi bi-check-lg"></i> Update Admission
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- FOOTER --}}
    <div class="text-center">
        <small class="cell-sub">
            <i class="bi bi-shield-check"></i>
            Last updated: {{ $admission->updated_at->format('d M Y, h:i A') }} |
            Created: {{ $admission->created_at->format('d M Y, h:i A') }}
        </small>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Edit Admission — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    .dashboard-page .currency-input { position: relative; }
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