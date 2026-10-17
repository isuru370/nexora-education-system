@php
    $feeData = isset($classCategoryFee) ? $classCategoryFee : null;

    $selectedStudentClassId = old(
        'student_class_id',
        isset($selectedClassId) ? $selectedClassId : optional($feeData)->student_class_id,
    );

    $selectedCategoryId = old('class_category_id', optional($feeData)->class_category_id);

    $noteValue = old('note', optional($feeData)->note ?? '');

    $isActiveInput = old('is_active', optional($feeData)->is_active ?? true);

    $formAction = $feeData
        ? route('class-category-fees.update', $feeData->id)
        : route('class-category-fees.store');
@endphp

<form method="POST" action="{{ $formAction }}">
    @csrf

    @if ($feeData)
        @method('PUT')
    @endif

    {{-- ===================================================== --}}
    {{-- SECTION: Class & Category --}}
    {{-- ===================================================== --}}
    <div class="section-header mb-3">
        <div class="section-icon icon-blue"><i class="bi bi-journal-bookmark-fill"></i></div>
        <div>
            <h5 class="section-title">Class &amp; Category</h5>
            <p class="section-subtitle">Choose the class and category this fee belongs to</p>
        </div>
    </div>

    <div class="row g-3 mb-4">

        {{-- CLASS --}}
        <div class="col-md-6">
            <label for="student_class_id" class="nx-label">
                Class <span style="color: var(--nx-red);">*</span>
            </label>

            <select name="student_class_id" id="student_class_id"
                class="form-select nx-input @error('student_class_id') is-invalid @enderror"
                required
                {{ $feeData ? 'disabled' : '' }}>

                <option value="">Select Class</option>

                @foreach ($classes as $class)
                    <option value="{{ $class->id }}"
                        {{ (string) $selectedStudentClassId === (string) $class->id ? 'selected' : '' }}>
                        {{ $class->class_name }}
                        | Grade: {{ optional($class->grade)->grade_name ?? 'N/A' }}
                        | Subject: {{ optional($class->subject)->subject_name ?? 'N/A' }}
                    </option>
                @endforeach
            </select>

            @if ($feeData)
                <input type="hidden" name="student_class_id" value="{{ $selectedStudentClassId }}">
            @endif

            @error('student_class_id')
                <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
            @enderror
        </div>

        {{-- CATEGORY --}}
        <div class="col-md-6">
            <label for="class_category_id" class="nx-label">
                Category <span style="color: var(--nx-red);">*</span>
            </label>

            <select name="class_category_id" id="class_category_id"
                class="form-select nx-input @error('class_category_id') is-invalid @enderror"
                required>

                <option value="">Select Category</option>

                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ (string) $selectedCategoryId === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->category_name }}
                        @if ($category->code)
                            — {{ $category->code }}
                        @endif
                    </option>
                @endforeach
            </select>

            @error('class_category_id')
                <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- SECTION: Status --}}
    {{-- ===================================================== --}}
    <div class="section-header mb-3 pt-3 border-top">
        <div class="section-icon icon-amber"><i class="bi bi-toggles"></i></div>
        <div>
            <h5 class="section-title">Status</h5>
            <p class="section-subtitle">Availability of this fee</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <input type="hidden" name="is_active" value="0">

            <div class="toggle-switch">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                    class="form-check-input flex-shrink-0"
                    style="width: 3em; height: 1.5em; cursor: pointer;"
                    {{ $isActiveInput ? 'checked' : '' }}>
                <label for="is_active" class="toggle-content">
                    <span class="toggle-label">Active</span>
                    <span class="toggle-description">Available for students</span>
                </label>
            </div>

            @error('is_active')
                <div class="nx-error mt-2"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- SECTION: Note --}}
    {{-- ===================================================== --}}
    <div class="section-header mb-3 pt-3 border-top">
        <div class="section-icon icon-blue"><i class="bi bi-chat-text-fill"></i></div>
        <div>
            <h5 class="section-title">Additional Note</h5>
            <p class="section-subtitle">Optional note about this fee</p>
        </div>
    </div>

    <div class="mb-4">
        <label for="note" class="nx-label">Note</label>
        <textarea name="note" id="note" rows="4"
            class="form-control nx-input @error('note') is-invalid @enderror"
            placeholder="Optional note">{{ $noteValue }}</textarea>
        @error('note')
            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
        @enderror
    </div>

    {{-- ===================================================== --}}
    {{-- FORM ACTIONS --}}
    {{-- ===================================================== --}}
    <div class="nx-form-actions">
        <a href="{{ route('class-category-fees.index') }}" class="nx-btn nx-btn-light">
            <i class="bi bi-x-circle"></i> Cancel
        </a>
        <button type="submit" class="nx-btn nx-btn-primary">
            <i class="bi bi-check-lg"></i> {{ $buttonText ?? 'Save' }}
        </button>
    </div>
</form>


@push('styles')
<style>
    /* ============================================================
       Class Category Fee Form — page-specific styles only.
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

    /* Toggle switch */
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
        .dashboard-page .nx-form-actions {
            flex-direction: column-reverse;
        }
        .dashboard-page .nx-form-actions .nx-btn { width: 100%; }
    }
</style>
@endpush