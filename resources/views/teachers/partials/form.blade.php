@php
    $teacher = $teacher ?? null;
@endphp

{{-- ✅ ALWAYS ACTIVE: Hidden input ensures is_active = 1 --}}
<input type="hidden" name="is_active" value="1">

<div class="row g-4">

    {{-- =========================================================
        SECTION 1: TEACHER INFORMATION
    ========================================================== --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 18px; overflow: hidden;">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width: 44px; height: 44px; background: linear-gradient(135deg, #2563eb, #60a5fa); color: #fff;">
                        <i class="bi bi-person-badge-fill" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #0f172a;">Teacher Information</h5>
                        <small class="text-muted">Basic personal details of the teacher</small>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">

                    {{-- FULL NAME --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-person me-1 text-muted"></i>Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="full_name"
                               class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name', $teacher->full_name ?? '') }}"
                               placeholder="Enter full name" required>
                        @error('full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- INITIALS --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-type me-1 text-muted"></i>Initials <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="initials"
                               class="form-control @error('initials') is-invalid @enderror"
                               value="{{ old('initials', $teacher->initials ?? '') }}"
                               placeholder="e.g. K.A.S." required>
                        @error('initials')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-envelope me-1 text-muted"></i>Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $teacher->email ?? '') }}"
                               placeholder="teacher@example.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- MOBILE --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-phone me-1 text-muted"></i>Mobile <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="mobile"
                               class="form-control @error('mobile') is-invalid @enderror"
                               value="{{ old('mobile', $teacher->mobile ?? '') }}"
                               placeholder="07XXXXXXXX" required>
                        @error('mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- NIC --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-card-text me-1 text-muted"></i>NIC <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nic"
                               class="form-control @error('nic') is-invalid @enderror"
                               value="{{ old('nic', $teacher->nic ?? '') }}"
                               placeholder="e.g. 199012345678" required>
                        @error('nic')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- BIRTHDAY --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-calendar-event me-1 text-muted"></i>Birthday <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="bday"
                               class="form-control @error('bday') is-invalid @enderror"
                               value="{{ old('bday', $teacher?->bday?->format('Y-m-d')) }}" required>
                        @error('bday')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- GENDER --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-gender-ambiguous me-1 text-muted"></i>Gender <span class="text-danger">*</span>
                        </label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $teacher->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $teacher->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $teacher->gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        SECTION 2: EDUCATION BRANCHES (Checkbox Cards)
    ========================================================== --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 18px; overflow: hidden;">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width: 44px; height: 44px; background: linear-gradient(135deg, #10b981, #34d399); color: #fff;">
                            <i class="bi bi-diagram-3-fill" style="font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #0f172a;">Education Branches</h5>
                            <small class="text-muted">Select the branches where this teacher works</small>
                        </div>
                    </div>

                    <span class="badge rounded-pill" style="background: #ecfdf5; color: #10b981; padding: 6px 14px; font-weight: 600;">
                        <i class="bi bi-info-circle me-1"></i>Multi-select allowed
                    </span>
                </div>
            </div>

            <div class="card-body p-4">

                @php
                    $selectedBranches = old('branch_ids', $selectedBranchIds ?? []);
                @endphp

                @if(isset($branches) && $branches->count() > 0)
                    <div class="row g-3">
                        @foreach ($branches as $branch)
                            @php
                                $isChecked = in_array($branch->id, $selectedBranches);
                            @endphp

                            <div class="col-md-6 col-lg-4">
                                <label class="branch-card-label w-100" for="branch_{{ $branch->id }}">
                                    <input type="checkbox"
                                           name="branch_ids[]"
                                           value="{{ $branch->id }}"
                                           id="branch_{{ $branch->id }}"
                                           class="branch-card-input"
                                           {{ $isChecked ? 'checked' : '' }}>

                                    <div class="branch-card {{ $isChecked ? 'branch-card-checked' : '' }}">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="branch-radio">
                                                <i class="bi bi-check-lg branch-check-icon"></i>
                                            </div>

                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-bold text-truncate" style="color: #0f172a; font-size: 0.9rem;">
                                                    {{ $branch->name }}
                                                </div>
                                                @if($branch->code)
                                                    <div class="mt-1">
                                                        <span class="badge bg-light text-dark border fw-bold" style="font-family: monospace; font-size: 0.7rem;">
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

                    <div class="mt-3 p-3 rounded-3 d-flex align-items-start gap-2" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                        <i class="bi bi-lightbulb text-warning mt-1"></i>
                        <small class="text-muted mb-0">
                            <strong>Tip:</strong> You can select multiple branches. Click any card to toggle selection.
                        </small>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.4;"></i>
                        <p class="text-muted mt-3 mb-0">No branches available. Please create a branch first.</p>
                    </div>
                @endif

                @error('branch_ids')
                    <div class="text-danger small mt-3">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror

                @error('branch_ids.*')
                    <div class="text-danger small mt-1">
                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                    </div>
                @enderror
            </div>
        </div>
    </div>


    {{-- =========================================================
        SECTION 3: ADDRESS INFORMATION
    ========================================================== --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 18px; overflow: hidden;">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width: 44px; height: 44px; background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #fff;">
                        <i class="bi bi-geo-alt-fill" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #0f172a;">Address Information</h5>
                        <small class="text-muted">Current residential address</small>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">

                    {{-- ADDRESS 1 --}}
                    <div class="col-md-12">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            Address Line 1 <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="address1"
                               class="form-control @error('address1') is-invalid @enderror"
                               value="{{ old('address1', $teacher->address1 ?? '') }}"
                               placeholder="Street / Building" required>
                        @error('address1')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ADDRESS 2 --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            Address Line 2
                        </label>
                        <input type="text" name="address2" class="form-control"
                               value="{{ old('address2', $teacher->address2 ?? '') }}"
                               placeholder="City / Area">
                    </div>

                    {{-- ADDRESS 3 --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            Address Line 3
                        </label>
                        <input type="text" name="address3" class="form-control"
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
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 18px; overflow: hidden;">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width: 44px; height: 44px; background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: #fff;">
                        <i class="bi bi-bank2" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #0f172a;">Bank Information</h5>
                        <small class="text-muted">Bank account details for salary payments</small>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">

                    {{-- BANK BRANCH --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-building me-1 text-muted"></i>Bank Branch
                        </label>
                        <select name="bank_branch_id" class="form-select select2-bank-branch @error('bank_branch_id') is-invalid @enderror">
                            <option value="">Search Bank or Branch</option>
                            @foreach ($bankBranches as $branch)
                                <option value="{{ $branch->id }}"
                                    {{ old('bank_branch_id', $teacher->bank_branch_id ?? '') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->bank->bank_name ?? '' }} - {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ACCOUNT NUMBER --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-credit-card me-1 text-muted"></i>Account Number
                        </label>
                        <input type="text" name="account_number"
                               class="form-control @error('account_number') is-invalid @enderror"
                               value="{{ old('account_number', $teacher->account_number ?? '') }}"
                               placeholder="Enter account number">
                        @error('account_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        SECTION 5: QUALIFICATION INFORMATION
    ========================================================== --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 18px; overflow: hidden;">
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width: 44px; height: 44px; background: linear-gradient(135deg, #06b6d4, #22d3ee); color: #fff;">
                        <i class="bi bi-mortarboard-fill" style="font-size: 1.1rem;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #0f172a;">Qualification Information</h5>
                        <small class="text-muted">Educational background and experience</small>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">

                    {{-- GRADUATION --}}
                    <div class="col-md-12">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-award me-1 text-muted"></i>Graduation Details
                        </label>
                        <textarea name="graduation_details"
                                  class="form-control @error('graduation_details') is-invalid @enderror"
                                  rows="3"
                                  placeholder="e.g. B.Sc. in Mathematics, University of Colombo (2015)">{{ old('graduation_details', $teacher->graduation_details ?? '') }}</textarea>
                        @error('graduation_details')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- EXPERIENCE --}}
                    <div class="col-md-12">
                        <label class="form-label fw-semibold" style="color: #334155; font-size: 0.85rem;">
                            <i class="bi bi-briefcase me-1 text-muted"></i>Experience
                        </label>
                        <textarea name="experience"
                                  class="form-control @error('experience') is-invalid @enderror"
                                  rows="3"
                                  placeholder="e.g. 5 years teaching experience at ABC Institute">{{ old('experience', $teacher->experience ?? '') }}</textarea>
                        @error('experience')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
        ACTIONS
    ========================================================== --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 18px;">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill" style="background: #ecfdf5; color: #10b981; padding: 8px 16px; font-weight: 600;">
                            <i class="bi bi-check-circle-fill me-1"></i>Will be saved as <strong>Active</strong>
                        </span>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('teachers.index') }}"
                           class="btn btn-light border fw-semibold px-4"
                           style="border-radius: 10px;">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>

                        <button type="submit"
                                class="btn btn-primary fw-semibold px-4"
                                style="border-radius: 10px; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
                            <i class="bi bi-check-circle me-1"></i>{{ $buttonText ?? 'Save Teacher' }}
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