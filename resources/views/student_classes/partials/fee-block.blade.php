{{-- One category of the class with its fee options (i = block index) --}}
@php
    $saved = !empty($block['id']);
    $blockActive = (bool) ($block['is_active'] ?? true);
    $default = (int) ($block['default'] ?? 0);
    $options = array_values($block['options'] ?? []);
@endphp
<div class="fee-block {{ $blockActive ? '' : 'is-off' }}" data-index="{{ $i }}">
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="flex-grow-1" style="min-width: 220px">
            @if ($saved)
                <input type="hidden" name="fees[{{ $i }}][category_id]" value="{{ $block['category_id'] }}">
                <div class="fw-bold">{{ optional($categories->firstWhere('id', $block['category_id']))->category_name ?? \App\Models\ClassCategory::find($block['category_id'])?->category_name }}</div>
            @else
                <select name="fees[{{ $i }}][category_id]" class="form-select custom-input @error("fees.$i.category_id") is-invalid @enderror" required>
                    <option value="">Choose a category...</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ ((string) ($block['category_id'] ?? '') === (string) $category->id) ? 'selected' : '' }}>{{ $category->category_name }}</option>
                    @endforeach
                </select>
                @error("fees.$i.category_id")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @endif
        </div>

        @if ($saved)
            <input type="hidden" name="fees[{{ $i }}][id]" value="{{ $block['id'] }}">
            <div class="form-check form-switch m-0">
                <input type="hidden" name="fees[{{ $i }}][is_active]" value="0">
                <input type="checkbox" class="form-check-input" name="fees[{{ $i }}][is_active]" value="1" data-block-active {{ $blockActive ? 'checked' : '' }} id="fee-active-{{ $i }}">
                <label class="form-check-label small fw-semibold" for="fee-active-{{ $i }}">Active</label>
            </div>
        @else
            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-block><i class="bi bi-trash"></i> Remove</button>
        @endif
    </div>

    <div class="fee-options" data-next="{{ count($options) }}">
        @foreach ($options as $j => $option)
            @include('student_classes.partials.fee-option', ['i' => $i, 'j' => $j, 'option' => $option, 'checked' => $j === $default, 'studentsOn' => $studentsOn])
        @endforeach
    </div>
    @error("fees.$i.options")<div class="text-danger small mt-1">{{ $message }}</div>@enderror

    <button type="button" class="btn btn-sm btn-link px-0 mt-2" data-add-option><i class="bi bi-plus-circle me-1"></i> Add fee option</button>
</div>
