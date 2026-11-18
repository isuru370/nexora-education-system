@php
    $systemUser = $systemUser ?? null;
@endphp

<div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
        <label class="ui-form-label">Custom ID</label>
        <input type="text" class="ui-form-control custom-input readonly-box"
            value="{{ old('custom_id', $systemUser?->custom_id ?? 'Auto generated') }}" readonly>
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
        <label class="ui-form-label">Full Name <span class="tw:text-bs-danger!">*</span></label>
        <input type="text" name="full_name" class="ui-form-control custom-input @error('full_name') ui-is-invalid @enderror"
            value="{{ old('full_name', $systemUser?->full_name ?? '') }}" required>
        @error('full_name')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
        <label class="ui-form-label">Mobile <span class="tw:text-bs-danger!">*</span></label>
        <input type="text" name="mobile" class="ui-form-control custom-input @error('mobile') ui-is-invalid @enderror"
            value="{{ old('mobile', $systemUser?->mobile ?? '') }}" required>
        @error('mobile')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
        <label class="ui-form-label">NIC <span class="tw:text-bs-danger!">*</span></label>
        <input type="text" name="nic" class="ui-form-control custom-input @error('nic') ui-is-invalid @enderror"
            value="{{ old('nic', $systemUser?->nic ?? '') }}" required>
        @error('nic')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
        <label class="ui-form-label">Birthday</label>
        <input type="date" name="bday" class="ui-form-control custom-input @error('bday') ui-is-invalid @enderror"
            value="{{ old('bday', $systemUser?->bday?->format('Y-m-d') ?? '') }}">
        @error('bday')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>


    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
        <label class="ui-form-label tw:font-semibold!">Gender *</label>
        <select name="gender" class="ui-form-select custom-input" required>
            <option value="">Select Gender</option>
            <option value="male" {{ old('gender', $systemUser?->gender ?? '') == 'male' ? 'selected' : '' }}>Male
            </option>
            <option value="female" {{ old('gender', $systemUser?->gender ?? '') == 'female' ? 'selected' : '' }}>
                Female</option>
            <option value="other" {{ old('gender', $systemUser?->gender ?? '') == 'other' ? 'selected' : '' }}>
                Other</option>
        </select>
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
        <label class="ui-form-label">Address 1 <span class="tw:text-bs-danger!">*</span></label>
        <input type="text" name="address1" class="ui-form-control custom-input @error('address1') ui-is-invalid @enderror"
            value="{{ old('address1', $systemUser?->address1 ?? '') }}" required>
        @error('address1')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
        <label class="ui-form-label">Address 2</label>
        <input type="text" name="address2" class="ui-form-control custom-input @error('address2') ui-is-invalid @enderror"
            value="{{ old('address2', $systemUser?->address2 ?? '') }}">
        @error('address2')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
        <label class="ui-form-label">Address 3</label>
        <input type="text" name="address3" class="ui-form-control custom-input @error('address3') ui-is-invalid @enderror"
            value="{{ old('address3', $systemUser?->address3 ?? '') }}">
        @error('address3')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
        <label class="ui-form-label">Email <span class="tw:text-bs-danger!">*</span></label>
        <input type="email" name="email" class="ui-form-control custom-input @error('email') ui-is-invalid @enderror"
            value="{{ old('email', $systemUser?->user?->email ?? '') }}" required>
        @error('email')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
        <label class="ui-form-label">
            User Type <span class="tw:text-bs-danger!">*</span>
        </label>

        <select name="user_type_id" class="ui-form-select custom-input @error('user_type_id') ui-is-invalid @enderror"
            required>

            <option value="">Select User Type</option>

            @foreach ($userTypes as $userType)
                @if ($userType->code !== 'SUPER_ADMIN')
                    <option value="{{ $userType->id }}"
                        {{ old('user_type_id', $systemUser?->user?->user_type_id ?? '') == $userType->id ? 'selected' : '' }}>
                        {{ $userType->name }}
                    </option>
                @endif
            @endforeach

        </select>

        @error('user_type_id')
            <div class="ui-invalid-feedback tw:block!">
                {{ $message }}
            </div>
        @enderror
    </div>

    {{-- Branch Assignment --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="branch-assignment-box">

            <div class="tw:flex! tw:items-center! tw:justify-between! tw:mb-4!">
                <div>
                    <label class="ui-form-label tw:mb-1!">
                        Branch Access
                    </label>
                    <div class="tw:text-bs-secondary-color! tw:text-[.875em]">
                        Select the branches this user can access.
                    </div>
                </div>

                <span class="ui-badge tw:bg-bs-primary-bg-subtle! tw:text-bs-primary!">
                    <i class="bi bi-diagram-3 tw:mr-1!"></i>
                    Branches
                </span>
            </div>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                @forelse($branches as $branch)
                    @php
                        $selectedBranches = old(
                            'branch_ids',
                            $systemUser?->user?->branches?->pluck('id')->toArray() ?? [],
                        );
                    @endphp

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">

                        <div class="branch-option">

                            <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}"
                                id="branch_{{ $branch->id }}" class="ui-form-check-input branch-checkbox"
                                {{ in_array($branch->id, $selectedBranches) ? 'checked' : '' }}>

                            <label for="branch_{{ $branch->id }}" class="branch-option-label">

                                <div class="branch-icon">
                                    <i class="bi bi-building"></i>
                                </div>

                                <div class="tw:grow!">
                                    <div class="tw:font-semibold!">
                                        {{ $branch->name }}
                                    </div>

                                    <small class="tw:text-bs-secondary-color!">
                                        {{ $branch->code }}
                                    </small>
                                </div>

                                <i class="bi bi-check-circle-fill branch-check"></i>

                            </label>

                        </div>

                    </div>

                @empty

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">
                        <div class="ui-alert ui-alert-warning tw:mb-0!">
                            <i class="bi bi-exclamation-triangle tw:mr-2!"></i>
                            No active branches available.
                        </div>
                    </div>
                @endforelse

            </div>

            @error('branch_ids')
                <div class="tw:text-bs-danger! tw:text-[.875em] tw:mt-2!">
                    {{ $message }}
                </div>
            @enderror

            @error('branch_ids.*')
                <div class="tw:text-bs-danger! tw:text-[.875em] tw:mt-2!">
                    {{ $message }}
                </div>
            @enderror

        </div>
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
        <label class="ui-form-label">
            Password
            @if (isset($systemUser))
                <small class="tw:text-bs-secondary-color!">(leave blank to keep current password)</small>
            @endif
        </label>
        <input type="password" name="password"
            class="ui-form-control custom-input @error('password') ui-is-invalid @enderror"
            @if (!isset($systemUser)) required @endif>
        @error('password')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <label class="ui-form-label">Note</label>
        <textarea name="note" rows="4" class="ui-form-control custom-input @error('note') ui-is-invalid @enderror">{{ old('note', $systemUser?->note ?? '') }}</textarea>
        @error('note')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="custom-switch">
            <input type="checkbox" name="is_active" value="1" id="is_active" class="ui-form-check-input"
                {{ (old('is_active', $systemUser?->is_active ?? true)) ? 'checked' : '' }}>
            <label for="is_active">Active Account</label>
        </div>
    </div>

</div>

@push('styles')
    <style>
        .custom-input {
            min-height: 52px;
            border-radius: 16px !important;
            border: 1px solid #e2e8f0;
            box-shadow: none !important;
            background: #fff;
        }

        .custom-input:focus {
            border-color: #93c5fd !important;
            box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .08) !important;
        }

        .readonly-box {
            background: #f8fafc;
            color: #64748b;
        }

        .custom-switch {
            display: flex;
            align-items: center;
            gap: .7rem;
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 16px;
            padding: 1rem 1.2rem;
        }

        .custom-switch label {
            font-weight: 600;
            margin: 0;
        }
    </style>
@endpush
