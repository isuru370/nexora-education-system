{{-- ============================================================
     Fee Option Form Partial
     Used by: create.blade.php, edit.blade.php
     Expected: $feeOption (optional), $classCategoryFee
     ============================================================ --}}

@php
    $isEdit = isset($feeOption);

    $labelValue     = old('label', $isEdit ? $feeOption->label : '');
    $feeValue       = old('fee', $isEdit ? $feeOption->fee : '');
    $isDefaultValue = old('is_default', $isEdit ? $feeOption->is_default : false);
    $isActiveValue  = old('is_active', $isEdit ? $feeOption->is_active : true);
    $noteValue      = old('note', $isEdit ? $feeOption->note : '');
@endphp


{{-- ===================================================== --}}
{{-- CLASS CATEGORY (readonly) --}}
{{-- ===================================================== --}}
<div class="section-header mb-3">
    <div class="section-icon icon-blue"><i class="bi bi-tag-fill"></i></div>
    <div>
        <h5 class="section-title">Class Category</h5>
        <p class="section-subtitle">The category this fee option belongs to</p>
    </div>
</div>

<div class="mb-4">
    <label class="nx-label">Category</label>
    <input type="text"
        class="form-control nx-input readonly-box"
        value="{{ $classCategoryFee->category->category_name ?? 'N/A' }}"
        readonly>
    <small class="cell-sub d-block mt-1">
        <i class="bi bi-mortarboard"></i>
        {{ $classCategoryFee->studentClass->class_name ?? '' }}
    </small>
</div>


{{-- ===================================================== --}}
{{-- FEE OPTION DETAILS --}}
{{-- ===================================================== --}}
<div class="section-header mb-3 pt-3 border-top">
    <div class="section-icon icon-green"><i class="bi bi-cash-stack"></i></div>
    <div>
        <h5 class="section-title">Fee Option Details</h5>
        <p class="section-subtitle">Name, amount and availability</p>
    </div>
</div>

{{-- LABEL --}}
<div class="mb-4">
    <label for="label" class="nx-label">
        Fee Option Name <span style="color: var(--nx-red);">*</span>
    </label>
    <input type="text" name="label" id="label"
        class="form-control nx-input @error('label') is-invalid @enderror"
        value="{{ $labelValue }}"
        placeholder="e.g. Theory Only"
        maxlength="150"
        required>
    @error('label')
        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
    @enderror
    <small class="cell-sub d-block mt-1">
        Enter a clear name for this fee option.
    </small>
</div>

{{-- FEE --}}
<div class="mb-4">
    <label for="fee" class="nx-label">
        Fee Amount <span style="color: var(--nx-red);">*</span>
    </label>
    <div class="nx-input-group">
        <span class="nx-input-group-text">Rs.</span>
        <input type="number" name="fee" id="fee"
            class="form-control nx-input @error('fee') is-invalid @enderror"
            value="{{ $feeValue }}"
            placeholder="2500.00"
            min="0"
            step="0.01"
            required>
    </div>
    @error('fee')
        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
    @enderror
    <small class="cell-sub d-block mt-1">
        Enter the fee amount for this option.
    </small>
</div>


{{-- ===================================================== --}}
{{-- STATUS + DEFAULT --}}
{{-- ===================================================== --}}
<div class="section-header mb-3 pt-3 border-top">
    <div class="section-icon icon-amber"><i class="bi bi-toggles"></i></div>
    <div>
        <h5 class="section-title">Status &amp; Default</h5>
        <p class="section-subtitle">Availability and default option settings</p>
    </div>
</div>

<div class="toggle-switch-group mb-4">

    {{-- STATUS --}}
    <div class="toggle-switch">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1"
            class="form-check-input flex-shrink-0"
            style="width: 3em; height: 1.5em; cursor: pointer;"
            {{ $isActiveValue ? 'checked' : '' }}>
        <label for="is_active" class="toggle-content">
            <span class="toggle-label">Active</span>
            <span class="toggle-description">Available for use</span>
        </label>
    </div>

    @error('is_active')
        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
    @enderror

    {{-- DEFAULT --}}
    <div class="toggle-switch">
        <input type="hidden" name="is_default" value="0">
        <input type="checkbox" name="is_default" id="is_default" value="1"
            class="form-check-input flex-shrink-0"
            style="width: 3em; height: 1.5em; cursor: pointer;"
            {{ $isDefaultValue ? 'checked' : '' }}>
        <label for="is_default" class="toggle-content">
            <span class="toggle-label">Default Option</span>
            <span class="toggle-description">Set as default for this category</span>
        </label>
    </div>

    @error('is_default')
        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
    @enderror

</div>

<small class="cell-sub d-block mb-4">
    <i class="bi bi-info-circle"></i>
    Only one fee option can be the default option for this class category.
</small>


{{-- ===================================================== --}}
{{-- NOTE --}}
{{-- ===================================================== --}}
<div class="mb-4">
    <label for="note" class="nx-label">Note</label>
    <textarea name="note" id="note" rows="4"
        class="form-control nx-input @error('note') is-invalid @enderror"
        placeholder="Optional note about this fee option...">{{ $noteValue }}</textarea>
    @error('note')
        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
    @enderror
</div>


{{-- ===================================================== --}}
{{-- FORM ACTIONS --}}
{{-- ===================================================== --}}
<div class="nx-form-actions">
    <a href="{{ route('class-category-fee-options.index', $classCategoryFee->id) }}"
        class="nx-btn nx-btn-light">
        <i class="bi bi-x-circle"></i> Cancel
    </a>
    <button type="submit" class="nx-btn nx-btn-primary">
        <i class="bi bi-check-lg"></i>
        {{ $isEdit ? 'Update Fee Option' : 'Create Fee Option' }}
    </button>
</div>


@push('styles')
<style>
    /* ============================================================
       Fee Option Form — page-specific styles only.
       ============================================================ */

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
    .dashboard-page .section-icon.icon-amber { background: var(--nx-amber-soft); color: var(--nx-amber); }

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

    /* Readonly input */
    .dashboard-page .readonly-box {
        background: #f4f6fa;
        color: var(--nx-muted);
        cursor: not-allowed;
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

    /* Toggle group */
    .dashboard-page .toggle-switch-group {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
    }

    .dashboard-page .toggle-switch {
        display: flex;
        align-items: center;
        gap: .75rem;
        flex: 1;
        min-width: 220px;
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
        justify-content: space-between;
        align-items: center;
        gap: .5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--nx-border);
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .dashboard-page .nx-form-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }
        .dashboard-page .nx-form-actions .nx-btn { width: 100%; }
    }
</style>
@endpush