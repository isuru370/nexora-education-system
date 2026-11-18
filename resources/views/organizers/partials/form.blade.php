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
    <div class="ui-alert ui-alert-danger ui-alert-dismissible ui-fade show tw:mb-6!" role="alert">
        <i class="bi bi-exclamation-triangle-fill tw:mr-2!"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="tw:mb-0! tw:mt-2!">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="ui-btn-close" data-nx-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ $isEdit ? route('organizers.update', $organizer) : route('organizers.store') }}">
    @csrf

    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">

        {{-- ==================================================
             BASIC INFORMATION
        ================================================== --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
            <h6 class="tw:font-bold! tw:mb-4!">
                <i class="bi bi-person tw:mr-2! tw:text-bs-primary!"></i>
                Basic Information
            </h6>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                {{-- Name --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                    <label class="ui-form-label tw:font-semibold!">
                        Organizer Name <span class="tw:text-bs-danger!">*</span>
                    </label>
                    <input type="text" name="name"
                        class="ui-form-control custom-input @error('name') ui-is-invalid @enderror"
                        value="{{ old('name', $organizer->name ?? '') }}" required placeholder="Enter organizer name">
                    @error('name')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                {{-- ✅ Organizer Code — Auto Generated --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                    <label class="ui-form-label tw:font-semibold!">
                        <i class="bi bi-hash tw:mr-1! tw:text-bs-primary!"></i>
                        Organizer Code
                        <span class="ui-badge tw:bg-[rgba(13,202,240,0.1)]! tw:text-bs-info! tw:ml-2!">
                            <i class="bi bi-magic"></i> Auto
                        </span>
                    </label>

                    @if ($isEdit)
                        {{-- ✅ Edit Mode — Show generated code (readonly) --}}
                        <input type="text" class="ui-form-control custom-input tw:bg-bs-light!" value="{{ $organizer->code }}"
                            readonly disabled>

                        <small class="tw:text-bs-secondary-color! tw:block! tw:mt-1!">
                            <i class="bi bi-info-circle tw:mr-1!"></i>
                            Code is auto-generated and cannot be changed.
                        </small>
                    @else
                        {{-- ✅ Create Mode — Show preview --}}
                        <input type="text" class="ui-form-control custom-input tw:bg-bs-light!"
                            value="{{ $previewCode ?? 'Will be generated on save' }}" readonly disabled>

                        <small class="tw:text-bs-secondary-color! tw:block! tw:mt-1!">
                            <i class="bi bi-info-circle tw:mr-1!"></i>
                            Code will be generated automatically (e.g. ORG001).
                        </small>
                    @endif
                </div>

                {{-- Email --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                    <label class="ui-form-label tw:font-semibold!">Email</label>
                    <input type="email" name="email"
                        class="ui-form-control custom-input @error('email') ui-is-invalid @enderror"
                        value="{{ old('email', $organizer->email ?? '') }}" placeholder="organizer@example.com">
                    @error('email')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Phone --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                    <label class="ui-form-label tw:font-semibold!">Phone</label>
                    <input type="text" name="phone"
                        class="ui-form-control custom-input @error('phone') ui-is-invalid @enderror"
                        value="{{ old('phone', $organizer->phone ?? '') }}" placeholder="0771234567">
                    @error('phone')
                        <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>

        {{-- ==================================================
             BRANCHES (MULTI-SELECT)
        ================================================== --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
            <div class="tw:flex! tw:justify-between! tw:items-center! tw:flex-wrap! tw:gap-2! tw:mb-4!">
                <h6 class="tw:font-bold! tw:mb-0!">
                    <i class="bi bi-building tw:mr-2! tw:text-bs-primary!"></i>
                    Branches
                    <span class="tw:text-bs-danger!">*</span>
                    <span class="branch-counter">
                        <i class="bi bi-check2-square"></i>
                        <span id="selectedBranchCount">0</span> selected
                    </span>
                </h6>

                <div class="branch-actions">
                    <button type="button" class="ui-btn ui-btn-outline-primary ui-btn-sm" id="selectAllBranches">
                        <i class="bi bi-check-all tw:mr-1!"></i> Select All
                    </button>
                    <button type="button" class="ui-btn ui-btn-outline-secondary ui-btn-sm" id="deselectAllBranches">
                        <i class="bi bi-x-circle tw:mr-1!"></i> Clear
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
                <div class="tw:text-bs-danger! tw:text-[.875em] tw:mt-2!">{{ $message }}</div>
            @enderror

            @error('branch_ids.*')
                <div class="tw:text-bs-danger! tw:text-[.875em] tw:mt-2!">{{ $message }}</div>
            @enderror

            <small class="tw:text-bs-secondary-color! tw:block! tw:mt-2!">
                <i class="bi bi-info-circle tw:mr-1!"></i>
                Select one or more branches this organizer belongs to.
            </small>
        </div>

        {{-- ==================================================
             STATUS
        ================================================== --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
            <h6 class="tw:font-bold! tw:mb-4!">
                <i class="bi bi-toggle-on tw:mr-2! tw:text-bs-primary!"></i>
                Status
            </h6>

            <div class="ui-form-check ui-form-switch">
                <input type="checkbox" name="is_active" value="1" id="is_active" class="ui-form-check-input"
                    {{ old('is_active', $organizer->is_active ?? true) ? 'checked' : '' }}>
                <label for="is_active" class="ui-form-check-label tw:font-semibold!">
                    Active
                </label>
            </div>
        </div>

        {{-- ==================================================
             BUTTONS
        ================================================== --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%] tw:flex! tw:justify-end! tw:gap-2! tw:pt-4! tw:bs-border-t!">
            <a href="{{ route('organizers.index') }}" class="ui-btn ui-btn-outline-secondary tw:px-6!">
                <i class="bi bi-x-lg tw:mr-1!"></i> Cancel
            </a>
            <button type="submit" class="ui-btn ui-btn-primary tw:px-6!">
                <i class="bi bi-save tw:mr-1!"></i> {{ $buttonText ?? 'Save Organizer' }}
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
