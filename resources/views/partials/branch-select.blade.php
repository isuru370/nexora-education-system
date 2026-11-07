{{--
    Branch box for Extra Income / Expense forms.
    $branches  accessible branches
    $selected  current branch_id (null = whole institute)
    Admins may choose "Whole institute"; a Branch Admin picks one of theirs.
--}}
@php
    $wholeInstitute = in_array(strtoupper((string) optional(auth()->user()->userType)->code), ['SUPER_ADMIN', 'ADMIN'], true);
    $current = old('branch_id', $selected ?? ($wholeInstitute ? '' : optional($branches->first())->id));
@endphp

<div class="{{ $wrapperClass ?? 'col-md-6' }}">
    <label class="form-label fw-semibold" for="branch_id">
        Branch @unless ($wholeInstitute)<span class="text-danger">*</span>@endunless
    </label>
    <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" {{ $wholeInstitute ? '' : 'required' }}>
        @if ($wholeInstitute)
            <option value="">Whole institute (no branch)</option>
        @endif
        @foreach ($branches as $branch)
            <option value="{{ $branch->id }}" {{ (string) $current === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
        @endforeach
    </select>
    @error('branch_id')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
