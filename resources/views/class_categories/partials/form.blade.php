@php
    $category = $category ?? null;
@endphp

{{-- VALIDATION ERRORS --}}
@if ($errors->any())
    <div class="nx-alert nx-alert-red mb-4">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form method="POST"
    action="{{ $category ? route('class-categories.update', $category) : route('class-categories.store') }}">
    @csrf

    @if ($category)
        @method('PUT')
    @endif

    <div class="row g-3">

        {{-- CATEGORY INFORMATION --}}
        <div class="col-12">
            <div class="section-header mb-3">
                <div class="section-icon icon-blue"><i class="bi bi-tag-fill"></i></div>
                <div>
                    <h5 class="section-title">Category Information</h5>
                    <p class="section-subtitle">Basic details of this category</p>
                </div>
            </div>

            <div class="row g-3">

                {{-- NAME --}}
                <div class="col-md-6">
                    <label class="nx-label">
                        Category Name <span style="color: var(--nx-red);">*</span>
                    </label>
                    <input type="text" name="category_name"
                        class="form-control nx-input @error('category_name') is-invalid @enderror"
                        value="{{ old('category_name', $category->category_name ?? '') }}"
                        placeholder="Theory / Revision / Paper" required>
                    @error('category_name')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- CODE --}}
                <div class="col-md-6">
                    <label class="nx-label">
                        Code <span style="color: var(--nx-red);">*</span>
                    </label>
                    <input type="text" name="code"
                        class="form-control nx-input @error('code') is-invalid @enderror"
                        value="{{ old('code', $category->code ?? '') }}"
                        placeholder="THEORY / REVISION / PAPER" required>
                    @error('code')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- SWITCHES --}}
                <div class="col-md-12">
                    <div class="toggle-switch-group">

                        {{-- SCHEDULABLE --}}
                        <div class="toggle-switch">
                            <input type="checkbox" name="is_schedulable" value="1" id="is_schedulable"
                                class="form-check-input flex-shrink-0"
                                style="width: 3em; height: 1.5em; cursor: pointer;"
                                {{ old('is_schedulable', $category->is_schedulable ?? true) ? 'checked' : '' }}>
                            <label for="is_schedulable" class="toggle-content">
                                <span class="toggle-label">Schedulable</span>
                                <span class="toggle-description">This category can be scheduled</span>
                            </label>
                        </div>

                        {{-- ACTIVE --}}
                        <div class="toggle-switch">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                class="form-check-input flex-shrink-0"
                                style="width: 3em; height: 1.5em; cursor: pointer;"
                                {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                            <label for="is_active" class="toggle-content">
                                <span class="toggle-label">Active</span>
                                <span class="toggle-description">Available for use</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FORM ACTIONS --}}
        <div class="col-12">
            <div class="nx-form-actions">
                <a href="{{ route('class-categories.index') }}" class="nx-btn nx-btn-light">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="nx-btn nx-btn-primary">
                    <i class="bi bi-check-lg"></i> {{ $buttonText }}
                </button>
            </div>
        </div>
    </div>
</form>


@push('styles')
<style>
    /* ============================================================
       Class Category Form — page-specific styles only.
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
    .dashboard-page .section-icon.icon-blue {
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
    }

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

    .dashboard-page .toggle-content {
        cursor: pointer;
    }

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