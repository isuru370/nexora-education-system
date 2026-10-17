@php
    $classHall = $classHall ?? null;
    $branches  = $branches ?? collect();

    $selectedBranchId = old('branch_id', $classHall->branch_id ?? '');
@endphp

{{-- ERRORS --}}
@if ($errors->any())
    <div class="nx-alert nx-alert-red mb-4">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Please correct the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form method="POST"
    action="{{ $classHall ? route('class-halls.update', $classHall) : route('class-halls.store') }}">
    @csrf

    @if ($classHall)
        @method('PUT')
    @endif

    <div class="row g-4">

        {{-- ===================================================== --}}
        {{-- BRANCH --}}
        {{-- ===================================================== --}}
        <div class="col-12">
            <div class="section-header mb-3">
                <div class="section-icon icon-blue"><i class="bi bi-building"></i></div>
                <div>
                    <h5 class="section-title">Branch Information</h5>
                    <p class="section-subtitle">Select the branch that owns this hall</p>
                </div>
            </div>

            <div class="mb-4">
                <label for="branch_id" class="nx-label">
                    Branch <span style="color: var(--nx-red);">*</span>
                </label>

                <select name="branch_id" id="branch_id"
                    class="form-select nx-input @error('branch_id') is-invalid @enderror"
                    required>

                    <option value="">Select Branch</option>

                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}"
                            {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>

                @error('branch_id')
                    <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                @enderror

                <small class="cell-sub d-block mt-1">
                    Select the branch that owns this hall.
                </small>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- HALL INFORMATION --}}
        {{-- ===================================================== --}}
        <div class="col-12">
            <div class="section-header mb-3 pt-3 border-top">
                <div class="section-icon icon-green"><i class="bi bi-door-open"></i></div>
                <div>
                    <h5 class="section-title">Hall Information</h5>
                    <p class="section-subtitle">Name, type, price and status</p>
                </div>
            </div>

            <div class="row g-3 mb-4">

                {{-- CODE --}}
                <div class="col-md-4">
                    <label for="code" class="nx-label">
                        Hall Code <span style="color: var(--nx-red);">*</span>
                    </label>
                    <input type="text" name="code" id="code"
                        class="form-control nx-input @error('code') is-invalid @enderror"
                        value="{{ old('code', $classHall->code ?? '') }}"
                        placeholder="HALL-01"
                        maxlength="50"
                        required>
                    @error('code')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                    <small class="cell-sub d-block mt-1">
                        Code must be unique within the selected branch.
                    </small>
                </div>

                {{-- NAME --}}
                <div class="col-md-8">
                    <label for="hall_name" class="nx-label">
                        Hall Name <span style="color: var(--nx-red);">*</span>
                    </label>
                    <input type="text" name="hall_name" id="hall_name"
                        class="form-control nx-input @error('hall_name') is-invalid @enderror"
                        value="{{ old('hall_name', $classHall->hall_name ?? '') }}"
                        placeholder="Main Hall"
                        maxlength="150"
                        required>
                    @error('hall_name')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- TYPE --}}
                <div class="col-md-6">
                    <label for="hall_type" class="nx-label">Hall Type</label>
                    <input type="text" name="hall_type" id="hall_type"
                        class="form-control nx-input @error('hall_type') is-invalid @enderror"
                        value="{{ old('hall_type', $classHall->hall_type ?? '') }}"
                        placeholder="AC / Non-AC / Online"
                        maxlength="50">
                    @error('hall_type')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- PRICE --}}
                <div class="col-md-6">
                    <label for="hall_price" class="nx-label">Hall Price</label>
                    <div class="nx-input-group">
                        <span class="nx-input-group-text">LKR</span>
                        <input type="number" name="hall_price" id="hall_price"
                            class="form-control nx-input @error('hall_price') is-invalid @enderror"
                            value="{{ old('hall_price', $classHall->hall_price ?? '') }}"
                            min="0"
                            step="0.01"
                            placeholder="0.00">
                    </div>
                    @error('hall_price')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                    <small class="cell-sub d-block mt-1">
                        Leave empty if no hall price is specified.
                    </small>
                </div>

                {{-- ACTIVE --}}
                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">

                    <div class="toggle-switch">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                            class="form-check-input flex-shrink-0"
                            style="width: 3em; height: 1.5em; cursor: pointer;"
                            {{ (bool) old('is_active', $classHall->is_active ?? true) ? 'checked' : '' }}>
                        <label for="is_active" class="toggle-content">
                            <span class="toggle-label">Active Hall</span>
                            <span class="toggle-description">Inactive halls should not be offered for new bookings.</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ACTIONS --}}
        {{-- ===================================================== --}}
        <div class="col-12">
            <div class="nx-form-actions">
                <a href="{{ route('class-halls.index') }}" class="nx-btn nx-btn-light">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="nx-btn nx-btn-primary">
                    <i class="bi bi-check-circle"></i> {{ $buttonText }}
                </button>
            </div>
        </div>
    </div>
</form>


@push('styles')
<style>
    .dashboard-page .section-header {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
    }

    .dashboard-page .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        font-size: .9rem;
        flex-shrink: 0;
    }
    .dashboard-page .section-icon.icon-blue  { background: var(--nx-blue-soft);  color: var(--nx-blue); }
    .dashboard-page .section-icon.icon-green { background: var(--nx-green-soft); color: var(--nx-green); }

    .dashboard-page .section-title {
        font-size: .95rem;
        font-weight: 700;
        color: var(--nx-text);
        margin: 0 0 .15rem;
    }

    .dashboard-page .section-subtitle {
        font-size: .75rem;
        color: var(--nx-muted);
        margin: 0;
    }

    .dashboard-page .nx-error {
        font-size: .74rem;
        color: var(--nx-red);
        margin-top: .35rem;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    /* Input group */
    .dashboard-page .nx-input-group {
        display: flex;
        align-items: stretch;
    }
    .dashboard-page .nx-input-group-text {
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        border-right: 0;
        border-radius: 8px 0 0 8px;
        padding: .5rem .75rem;
        display: flex;
        align-items: center;
        color: var(--nx-muted);
        font-weight: 600;
    }
    .dashboard-page .nx-input-group .nx-input {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }

    /* Toggle */
    .dashboard-page .toggle-switch {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: 1rem 1.1rem;
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
    }

    .dashboard-page .toggle-content { cursor: pointer; }

    .dashboard-page .toggle-label {
        display: block;
        font-size: .88rem;
        font-weight: 700;
        color: var(--nx-text);
        margin-bottom: .1rem;
    }

    .dashboard-page .toggle-description {
        font-size: .72rem;
        color: var(--nx-muted);
    }

    /* Form actions */
    .dashboard-page .nx-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--nx-border);
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .dashboard-page .nx-form-actions { flex-direction: column-reverse; }
        .dashboard-page .nx-form-actions .nx-btn { width: 100%; }
    }
</style>
@endpush