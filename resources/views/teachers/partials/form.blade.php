@php
    $teacher = $teacher ?? null;
@endphp

{{-- ✅ ALWAYS ACTIVE: Hidden input ensures is_active = 1 --}}
<input type="hidden" name="is_active" value="1">

<div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 bs-row">

    {{-- =========================================================
        SECTION 1: TEACHER INFORMATION
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!" style="border-radius: 18px; overflow: hidden;">
            <div class="ui-card-header tw:bg-bs-white! tw:bs-border-b! tw:p-6!">
                <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-4!">
                    <div class="tw:rounded-bs-lg! bs-d-flex tw:flex! tw:items-center! tw:justify-center!"
                         style="width: 44px; height: 44px; background: linear-gradient(135deg, #2563eb, #60a5fa); color: #fff;">
                        <i class="bi bi-person-badge-fill" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="tw:font-bold! tw:mb-0!" style="color: #0f172a;">Teacher Information</h5>
                        <small class="tw:text-bs-secondary-color!">Basic personal details of the teacher</small>
                    </div>
                </div>
            </div>

            <div class="ui-card-body tw:p-6!">
                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 bs-row">

                    {{-- FULL NAME --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-person tw:mr-1! tw:text-bs-secondary-color!"></i>Full Name <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <input type="text" name="full_name"
                               class="ui-form-control @error('full_name') ui-is-invalid @enderror"
                               value="{{ old('full_name', $teacher->full_name ?? '') }}"
                               placeholder="Enter full name" required>
                        @error('full_name')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- INITIALS --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-type tw:mr-1! tw:text-bs-secondary-color!"></i>Initials <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <input type="text" name="initials"
                               class="ui-form-control @error('initials') ui-is-invalid @enderror"
                               value="{{ old('initials', $teacher->initials ?? '') }}"
                               placeholder="e.g. K.A.S." required>
                        @error('initials')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-envelope tw:mr-1! tw:text-bs-secondary-color!"></i>Email <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <input type="email" name="email"
                               class="ui-form-control @error('email') ui-is-invalid @enderror"
                               value="{{ old('email', $teacher->email ?? '') }}"
                               placeholder="teacher@example.com" required>
                        @error('email')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- MOBILE --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-phone tw:mr-1! tw:text-bs-secondary-color!"></i>Mobile <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <input type="text" name="mobile"
                               class="ui-form-control @error('mobile') ui-is-invalid @enderror"
                               value="{{ old('mobile', $teacher->mobile ?? '') }}"
                               placeholder="07XXXXXXXX" required>
                        @error('mobile')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- NIC --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-card-text tw:mr-1! tw:text-bs-secondary-color!"></i>NIC <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <input type="text" name="nic"
                               class="ui-form-control @error('nic') ui-is-invalid @enderror"
                               value="{{ old('nic', $teacher->nic ?? '') }}"
                               placeholder="e.g. 199012345678" required>
                        @error('nic')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- BIRTHDAY --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-calendar-event tw:mr-1! tw:text-bs-secondary-color!"></i>Birthday <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <input type="date" name="bday"
                               class="ui-form-control @error('bday') ui-is-invalid @enderror"
                               value="{{ old('bday', $teacher?->bday?->format('Y-m-d')) }}" required>
                        @error('bday')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- GENDER --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-gender-ambiguous tw:mr-1! tw:text-bs-secondary-color!"></i>Gender <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <select name="gender" class="ui-form-select @error('gender') ui-is-invalid @enderror" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $teacher->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $teacher->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $teacher->gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        SECTION 2: EDUCATION BRANCHES (Checkbox Cards)
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!" style="border-radius: 18px; overflow: hidden;">
            <div class="ui-card-header tw:bg-bs-white! tw:bs-border-b! tw:p-6!">
                <div class="bs-d-flex tw:flex! tw:items-center! tw:justify-between! tw:flex-wrap! tw:gap-2!">
                    <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-4!">
                        <div class="tw:rounded-bs-lg! bs-d-flex tw:flex! tw:items-center! tw:justify-center!"
                             style="width: 44px; height: 44px; background: linear-gradient(135deg, #10b981, #34d399); color: #fff;">
                            <i class="bi bi-diagram-3-fill" style="font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <h5 class="tw:font-bold! tw:mb-0!" style="color: #0f172a;">Education Branches</h5>
                            <small class="tw:text-bs-secondary-color!">Select the branches where this teacher works</small>
                        </div>
                    </div>

                    <span class="ui-badge tw:rounded-bs-pill!" style="background: #ecfdf5; color: #10b981; padding: 6px 14px; font-weight: 600;">
                        <i class="bi bi-info-circle tw:mr-1!"></i>Multi-select allowed
                    </span>
                </div>
            </div>

            <div class="ui-card-body tw:p-6!">

                @php
                    $selectedBranches = old('branch_ids', $selectedBranchIds ?? []);
                @endphp

                @if(isset($branches) && $branches->count() > 0)
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 bs-row">
                        @foreach ($branches as $branch)
                            @php
                                $isChecked = in_array($branch->id, $selectedBranches);
                            @endphp

                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                                <label class="branch-card-label tw:w-full!" for="branch_{{ $branch->id }}">
                                    <input type="checkbox"
                                           name="branch_ids[]"
                                           value="{{ $branch->id }}"
                                           id="branch_{{ $branch->id }}"
                                           class="branch-card-input"
                                           {{ $isChecked ? 'checked' : '' }}>

                                    <div class="branch-card {{ $isChecked ? 'branch-card-checked' : '' }}">
                                        <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-4!">
                                            <div class="branch-radio">
                                                <i class="bi bi-check-lg branch-check-icon"></i>
                                            </div>

                                            <div class="tw:grow! min-w-0">
                                                <div class="tw:font-bold! tw:truncate" style="color: #0f172a; font-size: 0.9rem;">
                                                    {{ $branch->name }}
                                                </div>
                                                @if($branch->code)
                                                    <div class="tw:mt-1!">
                                                        <span class="ui-badge tw:bg-bs-light! tw:text-bs-dark! tw:bs-border! tw:font-bold!" style="font-family: monospace; font-size: 0.7rem;">
                                                            <i class="bi bi-hash"></i>{{ $branch->code }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="tw:mt-4! tw:p-4! tw:rounded-bs-lg! bs-d-flex tw:flex! tw:items-start! tw:gap-2!" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                        <i class="bi bi-lightbulb tw:text-bs-warning! tw:mt-1!"></i>
                        <small class="tw:text-bs-secondary-color! tw:mb-0!">
                            <strong>Tip:</strong> You can select multiple branches. Click any card to toggle selection.
                        </small>
                    </div>
                @else
                    <div class="tw:text-center! tw:py-6!">
                        <i class="bi bi-inbox tw:text-bs-secondary-color!" style="font-size: 3rem; opacity: 0.4;"></i>
                        <p class="tw:text-bs-secondary-color! tw:mt-4! tw:mb-0!">No branches available. Please create a branch first.</p>
                    </div>
                @endif

                @error('branch_ids')
                    <div class="tw:text-bs-danger! tw:text-[.875em] tw:mt-4!">
                        <i class="bi bi-exclamation-circle tw:mr-1!"></i>{{ $message }}
                    </div>
                @enderror

                @error('branch_ids.*')
                    <div class="tw:text-bs-danger! tw:text-[.875em] tw:mt-1!">
                        <i class="bi bi-exclamation-circle tw:mr-1!"></i>{{ $message }}
                    </div>
                @enderror
            </div>
        </div>
    </div>


    {{-- =========================================================
        SECTION 3: ADDRESS INFORMATION
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!" style="border-radius: 18px; overflow: hidden;">
            <div class="ui-card-header tw:bg-bs-white! tw:bs-border-b! tw:p-6!">
                <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-4!">
                    <div class="tw:rounded-bs-lg! bs-d-flex tw:flex! tw:items-center! tw:justify-center!"
                         style="width: 44px; height: 44px; background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #fff;">
                        <i class="bi bi-geo-alt-fill" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="tw:font-bold! tw:mb-0!" style="color: #0f172a;">Address Information</h5>
                        <small class="tw:text-bs-secondary-color!">Current residential address</small>
                    </div>
                </div>
            </div>

            <div class="ui-card-body tw:p-6!">
                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 bs-row">

                    {{-- ADDRESS 1 --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[100%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            Address Line 1 <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <input type="text" name="address1"
                               class="ui-form-control @error('address1') ui-is-invalid @enderror"
                               value="{{ old('address1', $teacher->address1 ?? '') }}"
                               placeholder="Street / Building" required>
                        @error('address1')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ADDRESS 2 --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            Address Line 2
                        </label>
                        <input type="text" name="address2" class="ui-form-control"
                               value="{{ old('address2', $teacher->address2 ?? '') }}"
                               placeholder="City / Area">
                    </div>

                    {{-- ADDRESS 3 --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            Address Line 3
                        </label>
                        <input type="text" name="address3" class="ui-form-control"
                               value="{{ old('address3', $teacher->address3 ?? '') }}"
                               placeholder="District / Province">
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        SECTION 4: BANK INFORMATION
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!" style="border-radius: 18px; overflow: hidden;">
            <div class="ui-card-header tw:bg-bs-white! tw:bs-border-b! tw:p-6!">
                <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-4!">
                    <div class="tw:rounded-bs-lg! bs-d-flex tw:flex! tw:items-center! tw:justify-center!"
                         style="width: 44px; height: 44px; background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: #fff;">
                        <i class="bi bi-bank2" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="tw:font-bold! tw:mb-0!" style="color: #0f172a;">Bank Information</h5>
                        <small class="tw:text-bs-secondary-color!">Bank account details for salary payments</small>
                    </div>
                </div>
            </div>

            <div class="ui-card-body tw:p-6!">
                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 bs-row">

                    {{-- BANK BRANCH --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-building tw:mr-1! tw:text-bs-secondary-color!"></i>Bank Branch
                        </label>
                        <select name="bank_branch_id" class="ui-form-select select2-bank-branch @error('bank_branch_id') ui-is-invalid @enderror">
                            <option value="">Search Bank or Branch</option>
                            @foreach ($bankBranches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ old('bank_branch_id', $teacher->bank_branch_id ?? '') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->bank->bank_name ?? '' }} - {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_branch_id')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ACCOUNT NUMBER --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-credit-card tw:mr-1! tw:text-bs-secondary-color!"></i>Account Number
                        </label>
                        <input type="text" name="account_number"
                               class="ui-form-control @error('account_number') ui-is-invalid @enderror"
                               value="{{ old('account_number', $teacher->account_number ?? '') }}"
                               placeholder="Enter account number">
                        @error('account_number')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        SECTION 5: QUALIFICATION INFORMATION
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!" style="border-radius: 18px; overflow: hidden;">
            <div class="ui-card-header tw:bg-bs-white! tw:bs-border-b! tw:p-6!">
                <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-4!">
                    <div class="tw:rounded-bs-lg! bs-d-flex tw:flex! tw:items-center! tw:justify-center!"
                         style="width: 44px; height: 44px; background: linear-gradient(135deg, #06b6d4, #22d3ee); color: #fff;">
                        <i class="bi bi-mortarboard-fill" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="tw:font-bold! tw:mb-0!" style="color: #0f172a;">Qualification Information</h5>
                        <small class="tw:text-bs-secondary-color!">Educational background and experience</small>
                    </div>
                </div>
            </div>

            <div class="ui-card-body tw:p-6!">
                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 bs-row">

                    {{-- GRADUATION --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[100%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-award tw:mr-1! tw:text-bs-secondary-color!"></i>Graduation Details
                        </label>
                        <textarea name="graduation_details"
                                  class="ui-form-control @error('graduation_details') ui-is-invalid @enderror"
                                  rows="3"
                                  placeholder="e.g. B.Sc. in Mathematics, University of Colombo (2015)">{{ old('graduation_details', $teacher->graduation_details ?? '') }}</textarea>
                        @error('graduation_details')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- EXPERIENCE --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[100%]">
                        <label class="ui-form-label tw:font-semibold!" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-briefcase tw:mr-1! tw:text-bs-secondary-color!"></i>Experience
                        </label>
                        <textarea name="experience"
                                  class="ui-form-control @error('experience') ui-is-invalid @enderror"
                                  rows="3"
                                  placeholder="e.g. 5 years teaching experience at ABC Institute">{{ old('experience', $teacher->experience ?? '') }}</textarea>
                        @error('experience')
                            <div class="ui-invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        ACTIONS
    ========================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
        <div class="ui-card tw:[border:0]! tw:shadow-bs-sm!" style="border-radius: 18px;">
            <div class="ui-card-body tw:p-6!">
                <div class="bs-d-flex tw:flex! tw:flex-col! tw:sm:flex-row! tw:justify-between! tw:sm:items-center! tw:gap-4!">
                    <div class="bs-d-flex tw:flex! tw:items-center! tw:gap-2!">
                        <span class="ui-badge tw:rounded-bs-pill!" style="background: #ecfdf5; color: #10b981; padding: 8px 16px; font-weight: 600;">
                            <i class="bi bi-check-circle-fill tw:mr-1!"></i>Will be saved as <strong>Active</strong>
                        </span>
                    </div>

                    <div class="bs-d-flex tw:flex! tw:gap-2!">
                        <a href="{{ route('teachers.index') }}"
                           class="ui-btn ui-btn-light tw:bs-border! tw:font-semibold! tw:px-6!"
                           style="border-radius: 10px;">
                            <i class="bi bi-x-circle tw:mr-1!"></i>Cancel
                        </a>

                        <button type="submit"
                                class="ui-btn ui-btn-primary tw:font-semibold! tw:px-6!"
                                style="border-radius: 10px; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
                            <i class="bi bi-check-circle tw:mr-1!"></i>{{ $buttonText ?? 'Save Teacher' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- =========================================================
    CUSTOM STYLES FOR BRANCH CARDS
========================================================== --}}
@push('styles')
<style>
    /* Branch Card - Radio/Checkbox style */
    .branch-card-label {
        cursor: pointer;
        display: block;
    }

    .branch-card-input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .branch-card {
        padding: 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        transition: all 0.25s ease;
        position: relative;
    }

    .branch-card:hover {
        border-color: #bfdbfe;
        background: #f8fafc;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.08);
    }

    .branch-radio {
        width: 26px;
        height: 26px;
        border: 2px solid #cbd5e1;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        transition: all 0.25s ease;
        flex-shrink: 0;
    }

    .branch-check-icon {
        font-size: 0.9rem;
        color: #fff;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.25s ease;
        font-weight: bold;
    }

    /* Checked State */
    .branch-card-input:checked + .branch-card {
        border-color: #2563eb;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.15);
    }

    .branch-card-input:checked + .branch-card .branch-radio {
        background: #2563eb;
        border-color: #2563eb;
    }

    .branch-card-input:checked + .branch-card .branch-check-icon {
        opacity: 1;
        transform: scale(1);
    }

    /* Focus State */
    .branch-card-input:focus + .branch-card {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }
</style>
@endpush