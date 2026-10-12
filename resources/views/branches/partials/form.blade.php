@csrf

@if(isset($branch))
    @method('PUT')
@endif

{{-- ============================================ --}}
{{-- SECTION: Branch Information --}}
{{-- ============================================ --}}
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-1">
        <div class="rounded-3 d-flex align-items-center justify-content-center"
             style="width: 32px; height: 32px; background: #eff6ff; color: #2563eb;">
            <i class="bi bi-building" style="font-size: 0.9rem;"></i>
        </div>
        <h5 class="fw-bold mb-0" style="color: #0f172a; font-size: 1rem;">Branch Information</h5>
    </div>
    <p class="text-muted mb-0 ms-5" style="font-size: 0.85rem;">Enter the basic information of the branch.</p>
</div>

<div class="row g-3 mb-4">

    {{-- Branch Name --}}
    <div class="col-12">
        <label for="name" class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
            Branch Name <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-tag text-muted"></i>
            </span>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $branch->name ?? '') }}"
                required
                maxlength="150"
                placeholder="Enter branch name"
                class="form-control border-start-0 @error('name') is-invalid @enderror"
            >
            @error('name')
                <div class="invalid-feedback">
                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    {{-- Branch Code (Read-only in Edit Mode) --}}
    @if(isset($branch))
        <div class="col-12">
            <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                Branch Code
            </label>
            <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                <span class="badge bg-white text-dark border fw-bold px-3 py-2" style="font-family: monospace; font-size: 0.9rem;">
                    <i class="bi bi-hash"></i>{{ $branch->code }}
                </span>
                <span class="text-muted" style="font-size: 0.8rem;">
                    <i class="bi bi-lock-fill me-1"></i> Branch code cannot be changed.
                </span>
            </div>
        </div>
    @endif

    {{-- Address 1 --}}
    <div class="col-12">
        <label for="address1" class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
            Address Line 1
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-geo-alt text-muted"></i>
            </span>
            <input
                type="text"
                id="address1"
                name="address1"
                value="{{ old('address1', $branch->address1 ?? '') }}"
                maxlength="150"
                placeholder="Street / Building"
                class="form-control border-start-0 @error('address1') is-invalid @enderror"
            >
            @error('address1')
                <div class="invalid-feedback">
                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    {{-- Address 2 --}}
    <div class="col-md-6">
        <label for="address2" class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
            Address Line 2
        </label>
        <input
            type="text"
            id="address2"
            name="address2"
            value="{{ old('address2', $branch->address2 ?? '') }}"
            maxlength="150"
            placeholder="City / Area"
            class="form-control @error('address2') is-invalid @enderror"
        >
        @error('address2')
            <div class="invalid-feedback">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
        @enderror
    </div>

    {{-- Address 3 --}}
    <div class="col-md-6">
        <label for="address3" class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
            Address Line 3
        </label>
        <input
            type="text"
            id="address3"
            name="address3"
            value="{{ old('address3', $branch->address3 ?? '') }}"
            maxlength="150"
            placeholder="District / Province"
            class="form-control @error('address3') is-invalid @enderror"
        >
        @error('address3')
            <div class="invalid-feedback">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
        @enderror
    </div>
</div>

{{-- ============================================ --}}
{{-- SECTION: Contact Information --}}
{{-- ============================================ --}}
<div class="d-flex align-items-center gap-2 mb-1 pt-4 border-top">
    <div class="rounded-3 d-flex align-items-center justify-content-center"
         style="width: 32px; height: 32px; background: #ecfdf5; color: #10b981;">
        <i class="bi bi-telephone-fill" style="font-size: 0.9rem;"></i>
    </div>
    <h5 class="fw-bold mb-0" style="color: #0f172a; font-size: 1rem;">Contact Information</h5>
</div>
<p class="text-muted mb-3 ms-5" style="font-size: 0.85rem;">Add contact details for this branch.</p>

<div class="row g-3 mb-4">

    {{-- Mobile --}}
    <div class="col-md-6">
        <label for="mobile" class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
            Mobile Number
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-phone text-muted"></i>
            </span>
            <input
                type="text"
                id="mobile"
                name="mobile"
                value="{{ old('mobile', $branch->mobile ?? '') }}"
                maxlength="20"
                placeholder="07XXXXXXXX"
                class="form-control border-start-0 @error('mobile') is-invalid @enderror"
            >
            @error('mobile')
                <div class="invalid-feedback">
                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    {{-- Email --}}
    <div class="col-md-6">
        <label for="email" class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
            Email Address
        </label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
                <i class="bi bi-envelope text-muted"></i>
            </span>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $branch->email ?? '') }}"
                maxlength="150"
                placeholder="branch@example.com"
                class="form-control border-start-0 @error('email') is-invalid @enderror"
            >
            @error('email')
                <div class="invalid-feedback">
                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

{{-- ============================================ --}}
{{-- SECTION: Status (Edit Mode Only) --}}
{{-- ============================================ --}}
@if(isset($branch))
    <div class="pt-4 border-top">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center"
                 style="width: 32px; height: 32px; background: #fef3c7; color: #f59e0b;">
                <i class="bi bi-toggle-on" style="font-size: 0.9rem;"></i>
            </div>
            <h5 class="fw-bold mb-0" style="color: #0f172a; font-size: 1rem;">Branch Status</h5>
        </div>

        <input type="hidden" name="is_active" value="0">

        <div class="form-check form-switch p-3 rounded-3 d-flex align-items-center gap-3"
             style="background: #f8fafc; border: 1px solid #e2e8f0;">
            <input
                type="checkbox"
                id="is_active"
                name="is_active"
                value="1"
                @checked(old('is_active', $branch->is_active))
                class="form-check-input m-0 flex-shrink-0"
                style="width: 3em; height: 1.5em; cursor: pointer;"
            >
            <label for="is_active" class="form-check-label flex-grow-1" style="cursor: pointer;">
                <span class="fw-semibold" style="color: #334155;">Active Branch</span>
                <div class="text-muted small mt-1">
                    <i class="bi bi-info-circle me-1"></i>
                    Inactive branches cannot be used for normal branch operations.
                </div>
            </label>
        </div>

        @error('is_active')
            <div class="text-danger small mt-2">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </div>
        @enderror
    </div>
@endif

{{-- ============================================ --}}
{{-- FORM ACTIONS --}}
{{-- ============================================ --}}
<div class="d-flex flex-column-reverse flex-sm-row justify-content-sm-end gap-2 mt-4 pt-4 border-top">
    <a href="{{ route('branches.index') }}"
       class="btn btn-light border fw-semibold px-4 py-2"
       style="border-radius: 12px;">
        <i class="bi bi-x-circle me-1"></i> Cancel
    </a>

    <button type="submit"
            class="btn btn-primary fw-semibold px-4 py-2"
            style="border-radius: 12px;">
        @if(isset($branch))
            <i class="bi bi-check-circle me-1"></i> Update Branch
        @else
            <i class="bi bi-check-circle me-1"></i> Create Branch
        @endif
    </button>
</div>