{{-- One category of the class with its fee options (i = block index) --}}
@php
    $saved = !empty($block['id']);
    $blockActive = (bool) ($block['is_active'] ?? true);
    $default = (int) ($block['default'] ?? 0);
    $options = array_values($block['options'] ?? []);
@endphp
<div class="fee-block {{ $blockActive ? '' : 'is-off' }}" data-index="{{ $i }}">
    <div class="tw:flex! tw:gap-2! tw:items-center! tw:flex-wrap!">
        <div class="tw:grow!" style="min-width: 220px">
            @if ($saved)
                <input type="hidden" name="fees[{{ $i }}][category_id]" value="{{ $block['category_id'] }}">
                <div class="tw:font-bold!">{{ optional($categories->firstWhere('id', $block['category_id']))->category_name ?? \App\Models\ClassCategory::find($block['category_id'])?->category_name }}</div>
            @else
                <select name="fees[{{ $i }}][category_id]" class="ui-form-select custom-input @error("fees.$i.category_id") ui-is-invalid @enderror" required>
                    <option value="">Choose a category...</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ ((string) ($block['category_id'] ?? '') === (string) $category->id) ? 'selected' : '' }}>{{ $category->category_name }}</option>
                    @endforeach
                </select>
                @error("fees.$i.category_id")<div class="ui-invalid-feedback tw:block!">{{ $message }}</div>@enderror
            @endif
        </div>

        @if ($saved)
            <input type="hidden" name="fees[{{ $i }}][id]" value="{{ $block['id'] }}">
            <div class="ui-form-check ui-form-switch tw:m-0!">
                <input type="hidden" name="fees[{{ $i }}][is_active]" value="0">
                <input type="checkbox" class="ui-form-check-input" name="fees[{{ $i }}][is_active]" value="1" data-block-active {{ $blockActive ? 'checked' : '' }} id="fee-active-{{ $i }}">
                <label class="ui-form-check-label tw:text-[.875em] tw:font-semibold!" for="fee-active-{{ $i }}">Active</label>
            </div>
        @else
            <button type="button" class="ui-btn ui-btn-sm ui-btn-outline-danger" data-remove-block><i class="bi bi-trash"></i> Remove</button>
        @endif
    </div>

    <div class="fee-options" data-next="{{ count($options) }}">
        @foreach ($options as $j => $option)
            @include('student_classes.partials.fee-option', ['i' => $i, 'j' => $j, 'option' => $option, 'checked' => $j === $default, 'studentsOn' => $studentsOn])
        @endforeach
    </div>
    @error("fees.$i.options")<div class="tw:text-bs-danger! tw:text-[.875em] tw:mt-1!">{{ $message }}</div>@enderror

    <button type="button" class="ui-btn ui-btn-sm ui-btn-link tw:px-0! tw:mt-2!" data-add-option><i class="bi bi-plus-circle tw:mr-1!"></i> Add fee option</button>
</div>
