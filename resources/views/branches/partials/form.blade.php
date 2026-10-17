@csrf

@if (isset($branch))
    @method('PUT')
@endif

{{-- ============================================ --}}
{{-- SECTION: Branch Information --}}
{{-- ============================================ --}}
<div class="section-header mb-3">
    <div class="section-icon icon-blue"><i class="bi bi-building"></i></div>
    <div>
        <h5 class="section-title">Branch Information</h5>
        <p class="section-subtitle">Enter the basic information of the branch.</p>
    </div>
</div>

<div class="row g-3 mb-4">

    {{-- NAME --}}
    <div class="col-12">
        <label for="name" class="nx-label">
            Branch Name <span style="color: var(--nx-red);">*</span>
        </label>
        <div class="nx-input-group">
            <span class="nx-input-group-text"><i class="bi bi-tag"></i></span>
            <input type="text" id="name" name="name"
                value="{{ old('name', $branch->name ?? '') }}"
                required maxlength="150"
                placeholder="Enter branch name"
                class="form-control nx-input @error('name') is-invalid @enderror">
        </div>
        @error('name')
            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
        @enderror
    </div>

    {{-- CODE (readonly in edit) --}}
    @if (isset($branch))
        <div class="col-12">
            <label class="nx-label">Branch Code</label>
            <div class="code-display">
                <span class="id-badge" style="font-size: .85rem;">
                    <i class="bi bi-hash"></i>{{ $branch->code }}
                </span>
                <span class="cell-sub">
                    <i class="bi bi-lock-fill"></i> Branch code cannot be changed.
                </span>
            </div>
        </div>
    @endif

    {{-- ADDRESS 1 --}}
    <div class="col-12">
        <label for="address1" class="nx-label">Address Line 1</label>
        <div class="nx-input-group">
            <span class="nx-input-group-text"><i class="bi bi-geo-alt"></i></span>
            <input type="text" id="address1" name="address1"
                value="{{ old('address1', $branch->address1 ?? '') }}"
                maxlength="150"
                placeholder="Street / Building"
                class="form-control nx-input @error('address1') is-invalid @enderror">
        </div>
        @error('address1')
            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
        @enderror
    </div>

    {{-- ADDRESS 2 --}}
    <div class="col-md-6">
        <label for="address2" class="nx-label">Address Line 2</label>
        <input type="text" id="address2" name="address2"
            value="{{ old('address2', $branch->address2 ?? '') }}"
            maxlength="150"
            placeholder="City / Area"
            class="form-control nx-input @error('address2') is-invalid @enderror">
        @error('address2')
            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
        @enderror
    </div>

    {{-- ADDRESS 3 --}}
    <div class="col-md-6">
        <label for="address3" class="nx-label">Address Line 3</label>
        <input type="text" id="address3" name="address3"
            value="{{ old('address3', $branch->address3 ?? '') }}"
            maxlength="150"
            placeholder="District / Province"
            class="form-control nx-input @error('address3') is-invalid @enderror">
        @error('address3')
            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
        @enderror
    </div>
</div>

{{-- ============================================ --}}
{{-- SECTION: Contact Information --}}
{{-- ============================================ --}}
<div class="section-header mb-3 pt-3 border-top">
    <div class="section-icon icon-green"><i class="bi bi-telephone-fill"></i></div>
    <div>
        <h5 class="section-title">Contact Information</h5>
        <p class="section-subtitle">Add contact details for this branch.</p>
    </div>
</div>

<div class="row g-3 mb-4">

    {{-- MOBILE --}}
    <div class="col-md-6">
        <label for="mobile" class="nx-label">Mobile Number</label>
        <div class="nx-input-group">
            <span class="nx-input-group-text"><i class="bi bi-phone"></i></span>
            <input type="text" id="mobile" name="mobile"
                value="{{ old('mobile', $branch->mobile ?? '') }}"
                maxlength="20"
                placeholder="07XXXXXXXX"
                class="form-control nx-input @error('mobile') is-invalid @enderror">
        </div>
        @error('mobile')
            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
        @enderror
    </div>

    {{-- EMAIL --}}
    <div class="col-md-6">
        <label for="email" class="nx-label">Email Address</label>
        <div class="nx-input-group">
            <span class="nx-input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email"
                value="{{ old('email', $branch->email ?? '') }}"
                maxlength="150"
                placeholder="branch@example.com"
                class="form-control nx-input @error('email') is-invalid @enderror">
        </div>
        @error('email')
            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
        @enderror
    </div>
</div>

{{-- ============================================ --}}
{{-- SECTION: Status (Edit Mode Only) --}}
{{-- ============================================ --}}
@if (isset($branch))
    <div class="section-header mb-3 pt-3 border-top">
        <div class="section-icon icon-amber"><i class="bi bi-toggle-on"></i></div>
        <div>
            <h5 class="section-title">Branch Status</h5>
            <p class="section-subtitle">Toggle branch availability</p>
        </div>
    </div>

    <input type="hidden" name="is_active" value="0">

    <div class="toggle-switch mb-3">
        <input
            type="checkbox"
            id="is_active"
            name="is_active"
            value="1"
            {{ old('is_active', $branch->is_active) ? 'checked' : '' }}
            class="form-check-input flex-shrink-0"
            style="width: 3em; height: 1.5em; cursor: pointer;">
        <label for="is_active" class="toggle-content">
            <span class="toggle-label">Active Branch</span>
            <div class="toggle-description">
                <i class="bi bi-info-circle"></i>
                Inactive branches cannot be used for normal branch operations.
            </div>
        </label>
    </div>

    @error('is_active')
        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
    @enderror
@endif

{{-- ============================================ --}}
{{-- FORM ACTIONS --}}
{{-- ============================================ --}}
<div class="nx-form-actions">
    <a href="{{ route('branches.index') }}" class="nx-btn nx-btn-light">
        <i class="bi bi-x-circle"></i> Cancel
    </a>

    <button type="submit" class="nx-btn nx-btn-primary">
        @if (isset($branch))
            <i class="bi bi-check-circle"></i> Update Branch
        @else
            <i class="bi bi-check-circle"></i> Create Branch
        @endif
    </button>
</div>


@push('styles')
<style>
    /* ============================================================
       Branches Form — page-specific styles only.
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
    .dashboard-page .section-icon.icon-blue  { background: var(--nx-blue-soft);   color: var(--nx-blue); }
    .dashboard-page .section-icon.icon-green { background: var(--nx-green-soft);  color: var(--nx-green); }
    .dashboard-page .section-icon.icon-amber { background: var(--nx-amber-soft);  color: var(--nx-amber); }

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
    }
    .dashboard-page .nx-input-group .nx-input {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }

    /* Code display */
    .dashboard-page .code-display {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .75rem 1rem;
        background: #f4f6fa;
        border: 1px dashed var(--nx-border);
        border-radius: 8px;
    }

    /* Error */
    .dashboard-page .nx-error {
        font-size: .74rem;
        color: var(--nx-red);
        margin-top: .35rem;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    /* Toggle switch */
    .dashboard-page .toggle-switch {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        flex-wrap: wrap;
    }
    .dashboard-page .toggle-content {
        flex: 1;
        min-width: 200px;
        cursor: pointer;
    }
    .dashboard-page .toggle-label {
        display: block;
        font-size: .88rem;
        font-weight: 700;
        color: var(--nx-text);
        margin-bottom: .2rem;
    }
    .dashboard-page .toggle-description {
        font-size: .75rem;
        color: var(--nx-muted);
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    /* Form actions */
    .dashboard-page .nx-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--nx-border);
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .dashboard-page .nx-form-actions { flex-direction: column-reverse; }
        .dashboard-page .nx-form-actions .nx-btn { width: 100%; }
    }
</style>
@endpush