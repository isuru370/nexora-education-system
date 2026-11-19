{{--
    Branch box for Extra Income / Expense forms.
    $branches  accessible branches
    $selected  current branch_id (null = whole institute)
    $wrapperClass  classes of the column (the forms put it in a 'row g-4')
    Admins may choose "Whole institute"; a Branch Admin picks one of theirs.
--}}
@php
    $wholeInstitute = in_array(strtoupper((string) optional(auth()->user()->userType)->code), ['SUPER_ADMIN', 'ADMIN'], true);
    $current = old('branch_id', $selected ?? ($wholeInstitute ? '' : optional($branches->first())->id));
@endphp

<div class="{{ $wrapperClass ?? 'tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]' }}">
    <label class="ui-form-label tw:font-semibold!" for="branch_id">
        Branch @unless ($wholeInstitute)<span class="tw:text-bs-danger!">*</span>@endunless
    </label>
    <select name="branch_id" id="branch_id" class="ui-form-select @error('branch_id') ui-is-invalid @enderror" {{ $wholeInstitute ? '' : 'required' }}>
        @if ($wholeInstitute)
            <option value="">Whole institute (no branch)</option>
        @endif
        @foreach ($branches as $branch)
            <option value="{{ $branch->id }}" {{ (string) $current === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
        @endforeach
    </select>
    @error('branch_id')
        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
    @enderror
</div>
