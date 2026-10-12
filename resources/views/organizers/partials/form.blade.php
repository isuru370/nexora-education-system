@php
    $organizer = $organizer ?? null;
    $isEdit = !empty($organizer);

    // ✅ Selected branches — priority order:
    // 1. old input (validation fail)
    // 2. $selectedBranches passed from controller/view
    // 3. organizer's branches (if loaded)
// 4. empty array
$selectedBranches = old(
    'branch_ids',
    $selectedBranches ??
        (isset($organizer) && $organizer->relationLoaded('branches')
            ? $organizer->branches->pluck('id')->toArray()
            : []),
);

// Normalize to int array
$selectedBranches = array_map('intval', (array) $selectedBranches);
@endphp

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ $isEdit ? route('organizers.update', $organizer) : route('organizers.store') }}">
    @csrf

    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-4">

        {{-- ==================================================
             BASIC INFORMATION
        ================================================== --}}
        <div class="col-12">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-person me-2 text-primary"></i>
                Basic Information
            </h6>

            <div class="row g-3">

                {{-- Name --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Organizer Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name"
                        class="form-control custom-input @error('name') is-invalid @enderror"
                        value="{{ old('name', $organizer->name ?? '') }}" required placeholder="Enter organizer name">
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ✅ Organizer Code — Auto Generated --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-hash me-1 text-primary"></i>
                        Organizer Code
                        <span class="badge bg-info bg-opacity-10 text-info ms-2">
                            <i class="bi bi-magic"></i> Auto
                        </span>
                    </label>

                    @if ($isEdit)
                        {{-- ✅ Edit Mode — Show generated code (readonly) --}}
                        <input type="text" class="form-control custom-input bg-light" value="{{ $organizer->code }}"
                            readonly disabled>

                        <small class="text-muted d-block mt-1">
                            <i class="bi bi-info-circle me-1"></i>
                            Code is auto-generated and cannot be changed.
                        </small>
                    @else
                        {{-- ✅ Create Mode — Show preview --}}
                        <input type="text" class="form-control custom-input bg-light"
                            value="{{ $previewCode ?? 'Will be generated on save' }}" readonly disabled>

                        <small class="text-muted d-block mt-1">
                            <i class="bi bi-info-circle me-1"></i>
                            Code will be generated automatically (e.g. ORG001).
                        </small>
                    @endif
                </div>

                {{-- Email --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email"
                        class="form-control custom-input @error('email') is-invalid @enderror"
                        value="{{ old('email', $organizer->email ?? '') }}" placeholder="organizer@example.com">
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Phone --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone"
                        class="form-control custom-input @error('phone') is-invalid @enderror"
                        value="{{ old('phone', $organizer->phone ?? '') }}" placeholder="0771234567">
                    @error('phone')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>

        {{-- ==================================================
             BRANCHES (MULTI-SELECT)
        ================================================== --}}
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-building me-2 text-primary"></i>
                    Branches
                    <span class="text-danger">*</span>
                    <span class="branch-counter">
                        <i class="bi bi-check2-square"></i>
                        <span id="selectedBranchCount">0</span> selected
                    </span>
                </h6>

                <div class="branch-actions">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="selectAllBranches">
                        <i class="bi bi-check-all me-1"></i> Select All
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAllBranches">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </button>
                </div>
            </div>

            <div class="branch-multiselect">
                @foreach ($branches as $branch)
                    @php
                        $isChecked = in_array((int) $branch->id, $selectedBranches, true);
                    @endphp

                    <label class="branch-option {{ $isChecked ? 'selected' : '' }}" for="branch_{{ $branch->id }}">
                        <input type="checkbox" name="branch_ids[]" id="branch_{{ $branch->id }}"
                            class="branch-checkbox" value="{{ $branch->id }}" {{ $isChecked ? 'checked' : '' }}>

                        <span class="branch-checkbox-ui">
                            <i class="bi bi-check-lg"></i>
                        </span>

                        <span class="branch-info">
                            <span class="branch-name">{{ $branch->name }}</span>
                            @if (!empty($branch->code))
                                <span class="branch-code">Code: {{ $branch->code }}</span>
                            @endif
                        </span>

                        <i class="bi bi-check-circle-fill branch-check"></i>
                    </label>
                @endforeach
            </div>

            @error('branch_ids')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror

            @error('branch_ids.*')
                <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror

            <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle me-1"></i>
                Select one or more branches this organizer belongs to.
            </small>
        </div>

        {{-- ==================================================
             STATUS
        ================================================== --}}
        <div class="col-12">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-toggle-on me-2 text-primary"></i>
                Status
            </h6>

            <div class="form-check form-switch">
                <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                    {{ old('is_active', $organizer->is_active ?? true) ? 'checked' : '' }}>
                <label for="is_active" class="form-check-label fw-semibold">
                    Active
                </label>
            </div>
        </div>

        {{-- ==================================================
             BUTTONS
        ================================================== --}}
        <div class="col-12 d-flex justify-content-end gap-2 pt-3 border-top">
            <a href="{{ route('organizers.index') }}" class="btn btn-outline-secondary px-4">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save me-1"></i> {{ $buttonText ?? 'Save Organizer' }}
            </button>
        </div>

    </div>
</form>

{{-- ==================================================
     STYLES - Partial Form එකට අදාළ
================================================== --}}
@once
    @push('styles')
        <style>
            /* ==========================================
                           BRANCH MULTI-SELECT
                        ========================================== */
            .branch-multiselect {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 12px;
            }

            .branch-option {
                position: relative;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 14px 16px;
                border: 2px solid #e2e8f0;
                border-radius: 14px;
                background: #fff;
                cursor: pointer;
                transition: all .2s ease;
                user-select: none;
            }

            .branch-option:hover {
                border-color: #93c5fd;
                background: #f8fbff;
            }

            .branch-option.selected {
                border-color: #2563eb;
                background: #eff6ff;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, .08);
            }

            .branch-checkbox {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            /* Custom checkbox */
            .branch-checkbox-ui {
                width: 22px;
                height: 22px;
                border: 2px solid #cbd5e1;
                border-radius: 6px;
                background: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                transition: all .2s ease;
                color: transparent;
                font-size: 0.75rem;
            }

            .branch-option.selected .branch-checkbox-ui {
                background: #2563eb;
                border-color: #2563eb;
                color: #fff;
            }

            .branch-info {
                flex: 1;
                min-width: 0;
            }

            .branch-name {
                font-weight: 700;
                color: #0f172a;
                font-size: 0.92rem;
                line-height: 1.2;
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .branch-code {
                font-size: 0.72rem;
                color: #64748b;
                font-weight: 600;
                margin-top: 2px;
                display: block;
            }

            .branch-check {
                color: #2563eb;
                display: none;
                font-size: 1rem;
            }

            .branch-option.selected .branch-check {
                display: inline-block;
            }

            /* ==========================================
                           ACTION BUTTONS
                        ========================================== */
            .branch-actions {
                display: flex;
                gap: 8px;
            }

            /* ==========================================
                           SELECTED COUNT BADGE
                        ========================================== */
            .branch-counter {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #eff6ff;
                color: #1e40af;
                border-radius: 999px;
                padding: 4px 12px;
                font-size: 0.78rem;
                font-weight: 700;
                margin-left: 8px;
            }

            /* ==========================================
                           RESPONSIVE
                        ========================================== */
            @media (max-width: 576px) {
                .branch-multiselect {
                    grid-template-columns: 1fr;
                }

                .branch-option {
                    padding: 12px 14px;
                }
            }
        </style>
    @endpush
@endonce

{{-- ==================================================
     SCRIPTS - Branch Multi-Select
================================================== --}}
@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const counter = document.getElementById('selectedBranchCount');

                // ==================================================
                // UPDATE OPTION STATE
                // ==================================================
                function updateOptionState(option) {
                    const checkbox = option.querySelector('.branch-checkbox');
                    if (checkbox.checked) {
                        option.classList.add('selected');
                    } else {
                        option.classList.remove('selected');
                    }
                }

                // ==================================================
                // UPDATE COUNTER
                // ==================================================
                function updateCounter() {
                    const checked = document.querySelectorAll('.branch-checkbox:checked');
                    if (counter) {
                        counter.textContent = checked.length;
                    }
                }

                // ==================================================
                // CHECKBOX CHANGE - Primary Handler
                // ✅ <label> default click → checkbox change event
                // ✅ Double-toggle නැහැ
                // ==================================================
                document.querySelectorAll('.branch-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        updateOptionState(this.closest('.branch-option'));
                        updateCounter();
                    });
                });

                // ==================================================
                // SELECT ALL
                // ==================================================
                document.getElementById('selectAllBranches')?.addEventListener('click', function(e) {
                    e.preventDefault();

                    document.querySelectorAll('.branch-option').forEach(option => {
                        const checkbox = option.querySelector('.branch-checkbox');
                        if (!checkbox.disabled) {
                            checkbox.checked = true;
                            updateOptionState(option);
                        }
                    });
                    updateCounter();
                });

                // ==================================================
                // DESELECT ALL
                // ==================================================
                document.getElementById('deselectAllBranches')?.addEventListener('click', function(e) {
                    e.preventDefault();

                    document.querySelectorAll('.branch-option').forEach(option => {
                        const checkbox = option.querySelector('.branch-checkbox');
                        if (!checkbox.disabled) {
                            checkbox.checked = false;
                            updateOptionState(option);
                        }
                    });
                    updateCounter();
                });

                // ==================================================
                // INITIAL STATE
                // ==================================================
                document.querySelectorAll('.branch-option').forEach(option => {
                    updateOptionState(option);
                });
                updateCounter();
            });
        </script>
    @endpush
@endonce
