{{-- 
|--------------------------------------------------------------------------
| Fee Option Form Partial
|--------------------------------------------------------------------------
| Used by:
| - create.blade.php
| - edit.blade.php
|
| Expected variables:
| - $feeOption (optional)
| - $classCategoryFee
|--------------------------------------------------------------------------
--}}

@php
    $isEdit = isset($feeOption);

    $labelValue = old(
        'label',
        $isEdit ? $feeOption->label : ''
    );

    $feeValue = old(
        'fee',
        $isEdit ? $feeOption->fee : ''
    );

    $isDefaultValue = old(
        'is_default',
        $isEdit ? $feeOption->is_default : false
    );

    $isActiveValue = old(
        'is_active',
        $isEdit ? $feeOption->is_active : true
    );

    $noteValue = old(
        'note',
        $isEdit ? $feeOption->note : ''
    );
@endphp


{{-- Class Category Fee --}}
<div class="mb-4">
    <label class="form-label fw-semibold">
        Class Category
    </label>

    <input
        type="text"
        class="form-control"
        value="{{ $classCategoryFee->category->category_name ?? 'N/A' }}"
        readonly
    >

    <div class="form-text">
        {{ $classCategoryFee->studentClass->class_name ?? '' }}
    </div>
</div>


{{-- Fee Option Label --}}
<div class="mb-4">
    <label
        for="label"
        class="form-label fw-semibold"
    >
        Fee Option Name
        <span class="text-danger">*</span>
    </label>

    <input
        type="text"
        name="label"
        id="label"
        class="form-control @error('label') is-invalid @enderror"
        value="{{ $labelValue }}"
        placeholder="e.g. Theory Only"
        maxlength="150"
        required
    >

    @error('label')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror

    <div class="form-text">
        Enter a clear name for this fee option.
    </div>
</div>


{{-- Fee --}}
<div class="mb-4">
    <label
        for="fee"
        class="form-label fw-semibold"
    >
        Fee Amount
        <span class="text-danger">*</span>
    </label>

    <div class="input-group">
        <span class="input-group-text">
            Rs.
        </span>

        <input
            type="number"
            name="fee"
            id="fee"
            class="form-control @error('fee') is-invalid @enderror"
            value="{{ $feeValue }}"
            placeholder="2500.00"
            min="0"
            step="0.01"
            required
        >
    </div>

    @error('fee')
        <div class="text-danger small mt-1">
            {{ $message }}
        </div>
    @enderror

    <div class="form-text">
        Enter the fee amount for this option.
    </div>
</div>


{{-- Status --}}
<div class="mb-4">
    <label class="form-label fw-semibold">
        Status
    </label>

    <div class="form-check form-switch">
        <input
            type="hidden"
            name="is_active"
            value="0"
        >

        <input
            class="form-check-input"
            type="checkbox"
            name="is_active"
            id="is_active"
            value="1"
            {{ $isActiveValue ? 'checked' : '' }}
        >

        <label
            class="form-check-label"
            for="is_active"
        >
            Active
        </label>
    </div>

    @error('is_active')
        <div class="text-danger small mt-1">
            {{ $message }}
        </div>
    @enderror
</div>


{{-- Default Option --}}
<div class="mb-4">
    <label class="form-label fw-semibold">
        Default Option
    </label>

    <div class="form-check form-switch">
        <input
            type="hidden"
            name="is_default"
            value="0"
        >

        <input
            class="form-check-input"
            type="checkbox"
            name="is_default"
            id="is_default"
            value="1"
            {{ $isDefaultValue ? 'checked' : '' }}
        >

        <label
            class="form-check-label"
            for="is_default"
        >
            Set as default fee option
        </label>
    </div>

    <div class="form-text">
        Only one fee option can be the default option
        for this class category.
    </div>

    @error('is_default')
        <div class="text-danger small mt-1">
            {{ $message }}
        </div>
    @enderror
</div>


{{-- Note --}}
<div class="mb-4">
    <label
        for="note"
        class="form-label fw-semibold"
    >
        Note
    </label>

    <textarea
        name="note"
        id="note"
        rows="4"
        class="form-control @error('note') is-invalid @enderror"
        placeholder="Optional note about this fee option..."
    >{{ $noteValue }}</textarea>

    @error('note')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>


{{-- Form Actions --}}
<div class="d-flex justify-content-between align-items-center">

    <a
        href="{{ route(
            'class-category-fee-options.index',
            $classCategoryFee->id
        ) }}"
        class="btn btn-light"
    >
        Cancel
    </a>

    <button
        type="submit"
        class="btn btn-primary"
    >
        @if($isEdit)
            Update Fee Option
        @else
            Create Fee Option
        @endif
    </button>

</div>