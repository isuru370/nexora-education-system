@php
    $classHall = $classHall ?? null;
    $branches = $branches ?? collect();

    $selectedBranchId = old(
        'branch_id',
        $classHall->branch_id ?? ''
    );
@endphp

@if ($errors->any())
    <div class="alert alert-danger rounded-3">
        <div class="fw-bold mb-2">
            Please correct the following errors:
        </div>

        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST"
      action="{{ $classHall
          ? route('class-halls.update', $classHall)
          : route('class-halls.store') }}">

    @csrf

    @if ($classHall)
        @method('PUT')
    @endif

    <div class="row g-4">

        {{-- Branch Selection --}}
        <div class="col-12">
            <div class="border rounded-4 p-3">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-building me-2"></i>
                    Branch Information
                </h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="branch_id" class="form-label">
                            Branch <span class="text-danger">*</span>
                        </label>

                        <select
                            name="branch_id"
                            id="branch_id"
                            class="form-select @error('branch_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Select Branch</option>

                            @foreach ($branches as $branch)
                                <option
                                    value="{{ $branch->id }}"
                                    {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}
                                >
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('branch_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <small class="text-muted">
                            Select the branch that owns this hall.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hall Information --}}
        <div class="col-12">
            <div class="border rounded-4 p-3">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-door-open me-2"></i>
                    Hall Information
                </h6>

                <div class="row g-3">

                    {{-- Code --}}
                    <div class="col-md-4">
                        <label for="code" class="form-label">
                            Hall Code <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="code"
                            id="code"
                            class="form-control @error('code') is-invalid @enderror"
                            value="{{ old('code', $classHall->code ?? '') }}"
                            placeholder="HALL-01"
                            maxlength="50"
                            required
                        >

                        @error('code')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <small class="text-muted">
                            Code must be unique within the selected branch.
                        </small>
                    </div>

                    {{-- Hall Name --}}
                    <div class="col-md-8">
                        <label for="hall_name" class="form-label">
                            Hall Name <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="hall_name"
                            id="hall_name"
                            class="form-control @error('hall_name') is-invalid @enderror"
                            value="{{ old('hall_name', $classHall->hall_name ?? '') }}"
                            placeholder="Main Hall"
                            maxlength="150"
                            required
                        >

                        @error('hall_name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Hall Type --}}
                    <div class="col-md-6">
                        <label for="hall_type" class="form-label">
                            Hall Type
                        </label>

                        <input
                            type="text"
                            name="hall_type"
                            id="hall_type"
                            class="form-control @error('hall_type') is-invalid @enderror"
                            value="{{ old('hall_type', $classHall->hall_type ?? '') }}"
                            placeholder="AC / Non-AC / Online"
                            maxlength="50"
                        >

                        @error('hall_type')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Hall Price --}}
                    <div class="col-md-6">
                        <label for="hall_price" class="form-label">
                            Hall Price
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">LKR</span>

                            <input
                                type="number"
                                name="hall_price"
                                id="hall_price"
                                class="form-control @error('hall_price') is-invalid @enderror"
                                value="{{ old('hall_price', $classHall->hall_price ?? '') }}"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                            >

                            @error('hall_price')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <small class="text-muted">
                            Leave empty if no hall price is specified.
                        </small>
                    </div>

                    {{-- Active Status --}}
                    <div class="col-12">
                        <input type="hidden" name="is_active" value="0">

                        <div class="form-check form-switch">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                id="is_active"
                                class="form-check-input"
                                {{ (bool) old('is_active', $classHall->is_active ?? true) ? 'checked' : '' }}
                            >

                            <label for="is_active" class="form-check-label fw-semibold">
                                Active Hall
                            </label>
                        </div>

                        <small class="text-muted">
                            Inactive halls should not be offered for new bookings.
                        </small>
                    </div>

                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('class-halls.index') }}"
               class="btn btn-light border">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i>
                {{ $buttonText }}
            </button>
        </div>

    </div>
</form>