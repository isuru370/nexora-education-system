{{-- One fee option row (i = block index, j = option index) --}}
@php
    $optionId = $option['id'] ?? null;
    $optionActive = (bool) ($option['is_active'] ?? true);
    $students = $optionId ? (int) ($studentsOn[$optionId] ?? ($option['students'] ?? 0)) : 0;
@endphp
<div class="fee-option">
    <label class="default-star" title="Default option">
        <input type="radio" name="fees[{{ $i }}][default]" value="{{ $j }}" {{ $checked ? 'checked' : '' }}>
        <i class="bi bi-star-fill"></i>
    </label>
    <div>
        @if ($optionId)
            <input type="hidden" name="fees[{{ $i }}][options][{{ $j }}][id]" value="{{ $optionId }}">
        @endif
        <input type="text" name="fees[{{ $i }}][options][{{ $j }}][label]" value="{{ $option['label'] ?? '' }}" maxlength="100" placeholder="Normal, Half card..." class="form-control custom-input @error("fees.$i.options.$j.label") is-invalid @enderror" required>
        @error("fees.$i.options.$j.label")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @if ($students > 0)
            <div class="students"><i class="bi bi-people-fill"></i> {{ $students }} student{{ $students === 1 ? '' : 's' }} on this option - a fee change applies to their next months.</div>
        @endif
    </div>
    <div>
        <div class="input-group">
            <span class="input-group-text">Rs.</span>
            <input type="number" step="0.01" min="0" name="fees[{{ $i }}][options][{{ $j }}][fee]" value="{{ $option['fee'] ?? '' }}" placeholder="2000" class="form-control custom-input @error("fees.$i.options.$j.fee") is-invalid @enderror" required>
        </div>
        @error("fees.$i.options.$j.fee")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div>
        @if ($optionId)
            <div class="form-check form-switch m-0" title="Active">
                <input type="hidden" name="fees[{{ $i }}][options][{{ $j }}][is_active]" value="0">
                <input type="checkbox" class="form-check-input" name="fees[{{ $i }}][options][{{ $j }}][is_active]" value="1" {{ $optionActive ? 'checked' : '' }}>
            </div>
        @else
            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-option title="Remove"><i class="bi bi-x-lg"></i></button>
        @endif
    </div>
</div>
