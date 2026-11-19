{{-- Optional special fee for one student (replaces the course fee; split the same way) --}}
@php
    $sfId = $sfId ?? 'sf';
    $sfOpen = old('special_fee') !== null && old('special_fee') !== '';
@endphp
<details class="special-fee" {{ $sfOpen ? 'open' : '' }}>
    <summary>Special fee</summary>
    <div class="tw:flex! tw:flex-wrap! tw:gap-2! tw:mt-2!">
        <div style="min-width:130px; flex:1">
            <label class="nx-label" for="{{ $sfId }}_fee">Fee for this student</label>
            <input id="{{ $sfId }}_fee" type="number" step="0.01" min="0" name="special_fee" class="ui-form-control nx-input" value="{{ old('special_fee') }}" placeholder="e.g. 8000 (0 = free)">
        </div>
        <div style="min-width:160px; flex:2">
            <label class="nx-label" for="{{ $sfId }}_reason">Reason</label>
            <input id="{{ $sfId }}_reason" name="special_fee_reason" class="ui-form-control nx-input" maxlength="255" value="{{ old('special_fee_reason') }}" placeholder="Staff child, scholarship ..." list="{{ $sfId }}_reasons">
            <datalist id="{{ $sfId }}_reasons">
                <option value="Staff child"><option value="Scholarship"><option value="Sibling discount"><option value="Free card"><option value="Special discount">
            </datalist>
        </div>
    </div>
    <small class="cell-sub tw:block! tw:mt-1!">Installments: this fee is split over the course months. At once: paid in one payment.</small>
</details>
