@php
    $studentClass = $studentClass ?? null;
    $paymentConfig = $paymentConfig ?? null;
    $isEdit = !empty($studentClass);

    $selectedClassType = old('class_type', $studentClass->class_type ?? 'offline');
    $selectedTeacherId = old('teacher_id', $studentClass->teacher_id ?? '');
    $selectedGradeId = old('grade_id', $studentClass->grade_id ?? '');
    $selectedSubjectId = old('subject_id', $studentClass->subject_id ?? '');
    $selectedOrganizerId = old('organizer_id', $paymentConfig->organizer_id ?? '');
    $selectedBranchId = old('branch_id', $studentClass->branch_id ?? '');

    $selectedIsActive = old('is_active', $studentClass->is_active ?? true);
    $selectedIsOngoing = old('is_ongoing', $studentClass->is_ongoing ?? false);

    $effectiveFrom = old(
        'effective_from',
        !empty($paymentConfig?->effective_from)
            ? \Carbon\Carbon::parse($paymentConfig->effective_from)->format('Y-m-d')
            : ''
    );

    $effectiveTo = old(
        'effective_to',
        !empty($paymentConfig?->effective_to)
            ? \Carbon\Carbon::parse($paymentConfig->effective_to)->format('Y-m-d')
            : ''
    );
@endphp

@if($errors->any())
    <div class="ui-alert ui-alert-danger ui-alert-dismissible ui-fade show tw:mb-6!" role="alert">
        <i class="bi bi-exclamation-triangle-fill tw:mr-2!"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="tw:mb-0! tw:mt-2!">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="ui-btn-close" data-nx-dismiss="alert"></button>
    </div>
@endif

<form method="POST"
      action="{{ $isEdit ? route('student-classes.update', $studentClass) : route('student-classes.store') }}">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">

        {{-- ================================================== --}}
        {{-- CLASS INFORMATION --}}
        {{-- ================================================== --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
            <div class="section-card tw:p-6!">
                <h6 class="section-title">
                    <i class="bi bi-journal-bookmark tw:mr-2!"></i> Class Information
                </h6>

                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                    {{-- ✅ Branch --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label for="branch_id" class="ui-form-label tw:font-semibold!">
                            <i class="bi bi-building tw:mr-1!"></i>
                            Branch <span class="tw:text-bs-danger!">*</span>
                        </label>

                        <select
                            name="branch_id"
                            id="branch_id"
                            class="ui-form-select custom-input @error('branch_id') ui-is-invalid @enderror"
                            required
                        >
                            <option value="">Select Branch</option>

                            @foreach($branches as $branch)
                                <option
                                    value="{{ $branch->id }}"
                                    {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}
                                >
                                    {{ $branch->name }}
                                    @if(!empty($branch->code))
                                        ({{ $branch->code }})
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @error('branch_id')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Class Name --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!">
                            <i class="bi bi-book tw:mr-1!"></i>
                            Class Name <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <input
                            type="text"
                            name="class_name"
                            class="ui-form-control custom-input @error('class_name') ui-is-invalid @enderror"
                            value="{{ old('class_name', $studentClass->class_name ?? '') }}"
                            required
                            placeholder="Enter class name">
                        @error('class_name')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Class Type --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!">
                            <i class="bi bi-laptop tw:mr-1!"></i>
                            Class Type <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <select name="class_type" class="ui-form-select custom-input @error('class_type') ui-is-invalid @enderror" required>
                            <option value="offline" {{ $selectedClassType === 'offline' ? 'selected="selected"' : '' }}>Offline</option>
                            <option value="online" {{ $selectedClassType === 'online' ? 'selected="selected"' : '' }}>Online</option>
                            <option value="hybrid" {{ $selectedClassType === 'hybrid' ? 'selected="selected"' : '' }}>Hybrid</option>
                        </select>
                        @error('class_type')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Medium --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!">
                            <i class="bi bi-translate tw:mr-1!"></i>
                            Medium <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <input
                            type="text"
                            name="medium"
                            class="ui-form-control custom-input @error('medium') ui-is-invalid @enderror"
                            value="{{ old('medium', $studentClass->medium ?? 'Sinhala') }}"
                            required
                            placeholder="Sinhala / English / Tamil">
                        @error('medium')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Teacher --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!">
                            <i class="bi bi-person-workspace tw:mr-1!"></i>
                            Teacher <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <select name="teacher_id" class="ui-form-select custom-input @error('teacher_id') ui-is-invalid @enderror" required>
                            <option value="">Select Teacher</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ $selectedTeacherId == $teacher->id ? 'selected="selected"' : '' }}>
                                    {{ $teacher->full_name }} - {{ $teacher->custom_id }}
                                </option>
                            @endforeach
                        </select>
                        @error('teacher_id')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Grade --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!">
                            <i class="bi bi-mortarboard tw:mr-1!"></i>
                            Grade <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <select name="grade_id" class="ui-form-select custom-input @error('grade_id') ui-is-invalid @enderror">
                            <option value="">Select Grade</option>
                            @foreach($grades as $grade)
                                <option value="{{ $grade->id }}" {{ $selectedGradeId == $grade->id ? 'selected="selected"' : '' }}>
                                    {{ $grade->grade_name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="tw:mt-2!">
                            <input
                                type="text"
                                name="new_grade"
                                class="ui-form-control custom-input @error('new_grade') ui-is-invalid @enderror"
                                value="{{ old('new_grade') }}"
                                placeholder="Or add new grade">
                            @error('new_grade')
                                <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                            @enderror
                        </div>
                        @error('grade_id')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Subject --}}
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                        <label class="ui-form-label tw:font-semibold!">
                            <i class="bi bi-bookmark tw:mr-1!"></i>
                            Subject <span class="tw:text-bs-danger!">*</span>
                        </label>
                        <select name="subject_id" class="ui-form-select custom-input @error('subject_id') ui-is-invalid @enderror">
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ $selectedSubjectId == $subject->id ? 'selected="selected"' : '' }}>
                                    {{ $subject->subject_name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="tw:mt-2!">
                            <input
                                type="text"
                                name="new_subject"
                                class="ui-form-control custom-input @error('new_subject') ui-is-invalid @enderror"
                                value="{{ old('new_subject') }}"
                                placeholder="Or add new subject">
                            @error('new_subject')
                                <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                            @enderror
                        </div>
                        @error('subject_id')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- ================================================== --}}
        {{-- PAYMENT CONFIGURATION --}}
        {{-- ================================================== --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
            <div class="section-card tw:p-6!">
                <h6 class="section-title">
                    <i class="bi bi-cash-coin tw:mr-2!"></i> Payment Configuration
                </h6>

                <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!">Teacher Percentage *</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            name="teacher_percentage"
                            class="ui-form-control custom-input @error('teacher_percentage') ui-is-invalid @enderror"
                            value="{{ old('teacher_percentage', $paymentConfig->teacher_percentage ?? '') }}"
                            required>
                        @error('teacher_percentage')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!">Organizer Percentage</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            name="organizer_percentage"
                            class="ui-form-control custom-input @error('organizer_percentage') ui-is-invalid @enderror"
                            value="{{ old('organizer_percentage', $paymentConfig->organizer_percentage ?? 0) }}">
                        <small class="tw:text-bs-secondary-color!">If there is no organizer, put 0.</small>
                        @error('organizer_percentage')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!">Institution Percentage *</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            name="institution_percentage"
                            class="ui-form-control custom-input @error('institution_percentage') ui-is-invalid @enderror"
                            value="{{ old('institution_percentage', $paymentConfig->institution_percentage ?? '') }}"
                            required>
                        @error('institution_percentage')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!">Organizer</label>
                        <select name="organizer_id" class="ui-form-select custom-input @error('organizer_id') ui-is-invalid @enderror">
                            <option value="">None</option>
                            @foreach($organizers as $organizer)
                                <option value="{{ $organizer->id }}" {{ $selectedOrganizerId == $organizer->id ? 'selected="selected"' : '' }}>
                                    {{ $organizer->name }} - {{ $organizer->code }}
                                </option>
                            @endforeach
                        </select>
                        @error('organizer_id')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!">Effective From</label>
                        <input
                            type="date"
                            name="effective_from"
                            class="ui-form-control custom-input @error('effective_from') ui-is-invalid @enderror"
                            value="{{ $effectiveFrom }}">
                        @error('effective_from')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                        <label class="ui-form-label tw:font-semibold!">Effective To</label>
                        <input
                            type="date"
                            name="effective_to"
                            class="ui-form-control custom-input @error('effective_to') ui-is-invalid @enderror"
                            value="{{ $effectiveTo }}">
                        @error('effective_to')
                            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================== --}}
        {{-- CATEGORIES & FEES --}}
        {{-- ================================================== --}}
        @php
            $feeBlocksSaved = $feeBlocks ?? [];
            $feeBlocks = old('fees', $feeBlocksSaved);
            if (!$isEdit && empty($feeBlocks)) {
                $feeBlocks = [['category_id' => '', 'default' => 0, 'options' => [['label' => 'Normal', 'fee' => '']]]];
            }
            // students on each saved option (old() does not carry it)
            $studentsOn = collect($feeBlocksSaved)->flatMap(fn ($b) => collect($b['options'])->mapWithKeys(fn ($o) => [$o['id'] => $o['students']]))->all();
        @endphp
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
            <div class="section-card tw:p-6!">
                <div class="tw:flex! tw:justify-between! tw:items-start! tw:flex-wrap! tw:gap-2! tw:mb-4!">
                    <h6 class="section-title tw:mb-0!">
                        <i class="bi bi-tags tw:mr-2!"></i> Categories &amp; Fees *
                    </h6>
                    <small class="tw:text-bs-secondary-color!">Each category needs at least one fee option. ★ = the default option.</small>
                </div>

                @error('fees')
                    <div class="ui-alert ui-alert-danger tw:py-2!">{{ $message }}</div>
                @enderror

                <div id="feeBlocks" data-next="{{ count($feeBlocks) }}">
                    @foreach ($feeBlocks as $i => $block)
                        @include('student_classes.partials.fee-block', ['i' => $i, 'block' => $block, 'categories' => $categories, 'studentsOn' => $studentsOn])
                    @endforeach
                </div>

                <button type="button" class="ui-btn ui-btn-outline-primary custom-btn tw:mt-2!" data-add-block>
                    <i class="bi bi-plus-lg tw:mr-1!"></i> Add category
                </button>

                <template id="feeBlockTemplate">
                    @include('student_classes.partials.fee-block', ['i' => '__I__', 'block' => ['category_id' => '', 'default' => 0, 'options' => [['label' => 'Normal', 'fee' => '']]], 'categories' => $categories, 'studentsOn' => []])
                </template>
                <template id="feeOptionTemplate">
                    @include('student_classes.partials.fee-option', ['i' => '__I__', 'j' => '__J__', 'option' => ['label' => '', 'fee' => ''], 'checked' => false, 'studentsOn' => []])
                </template>
            </div>
        </div>

        {{-- ================================================== --}}
        {{-- STATUS --}}
        {{-- ================================================== --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
            <div class="section-card tw:p-6!">
                <h6 class="section-title">
                    <i class="bi bi-toggle-on tw:mr-2!"></i> Status
                </h6>

                <div class="tw:flex! tw:gap-6! tw:flex-wrap!">
                    <div class="ui-form-check ui-form-switch">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            id="is_active"
                            class="ui-form-check-input"
                            {{ $selectedIsActive ? 'checked="checked"' : '' }}>
                        <label for="is_active" class="ui-form-check-label tw:font-semibold!">Active</label>
                    </div>

                    <div class="ui-form-check ui-form-switch">
                        <input
                            type="checkbox"
                            name="is_ongoing"
                            value="1"
                            disabled
                            id="is_ongoing"
                            class="ui-form-check-input"
                            {{ $selectedIsOngoing ? 'checked="checked"' : '' }}>
                        <label for="is_ongoing" class="ui-form-check-label tw:font-semibold!">Ongoing Class</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================== --}}
        {{-- BUTTONS --}}
        {{-- ================================================== --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%] tw:flex! tw:justify-end! tw:gap-2! tw:pt-2!">
            <a href="{{ route('student-classes.index') }}" class="ui-btn ui-btn-outline-secondary tw:px-6! custom-btn">
                <i class="bi bi-x-lg tw:mr-1!"></i> Cancel
            </a>
            <button type="submit" class="ui-btn ui-btn-primary tw:px-6! custom-btn">
                <i class="bi bi-save tw:mr-1!"></i> {{ $buttonText ?? 'Save Class' }}
            </button>
        </div>

    </div>
</form>

@push('styles')
<style>
    .fee-block { border: 1px solid #e2e8f0; border-radius: 20px; padding: 1rem 1.1rem; margin-bottom: 1rem; background: #f8fafc; }
    .fee-block.is-off { opacity: .6; }
    .fee-option { display: grid; grid-template-columns: 36px 1fr 170px auto; gap: .6rem; align-items: center; margin-top: .6rem; }
    .fee-option .default-star { display: grid; place-items: center; cursor: pointer; }
    .fee-option .default-star input { display: none; }
    .fee-option .default-star i { font-size: 1.25rem; color: #cbd5e1; }
    .fee-option .default-star input:checked + i { color: #f59e0b; }
    .fee-option .students { font-size: .75rem; color: #b45309; }
    @media (max-width: 640px) { .fee-option { grid-template-columns: 30px 1fr 110px auto; } }
    .section-card {
        background: #fff;
        border-radius: 24px;
        border: 1px solid #eef2f7;
        box-shadow: 0 6px 20px rgba(0,0,0,.04);
    }

    .section-title {
        font-weight: 700;
        margin-bottom: 1.25rem;
        color: #0f172a;
        display: flex;
        align-items: center;
    }

    .section-title i {
        color: var(--primary, #2563eb);
    }

    .custom-input {
        border-radius: 14px;
        min-height: 48px;
        border: 1px solid #dbe3ec;
        box-shadow: none;
    }

    .custom-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37,99,235,.10);
    }

    textarea.custom-input {
        min-height: 110px;
    }

    /* Branch dropdown - icon alignment */
    .ui-form-label i {
        opacity: 0.7;
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const wrap = document.getElementById('feeBlocks');
    if (!wrap) return;

    const fromTemplate = (id, i, j) => {
        const html = document.getElementById(id).innerHTML.replaceAll('__I__', i).replaceAll('__J__', j ?? 0);
        const box = document.createElement('div');
        box.innerHTML = html.trim();
        return box.firstElementChild;
    };

    document.querySelector('[data-add-block]').addEventListener('click', () => {
        const i = Number(wrap.dataset.next);
        wrap.dataset.next = i + 1;
        wrap.appendChild(fromTemplate('feeBlockTemplate', i));
    });

    wrap.addEventListener('click', (e) => {
        const addOption = e.target.closest('[data-add-option]');
        if (addOption) {
            const block = addOption.closest('.fee-block');
            const list = block.querySelector('.fee-options');
            const j = Number(list.dataset.next);
            list.dataset.next = j + 1;
            list.appendChild(fromTemplate('feeOptionTemplate', block.dataset.index, j));
            return;
        }

        const removeOption = e.target.closest('[data-remove-option]');
        if (removeOption) {
            const list = removeOption.closest('.fee-options');
            if (list.querySelectorAll('.fee-option').length > 1) {
                removeOption.closest('.fee-option').remove();
            }
            return;
        }

        const removeBlock = e.target.closest('[data-remove-block]');
        if (removeBlock) {
            removeBlock.closest('.fee-block').remove();
        }
    });

    wrap.addEventListener('change', (e) => {
        if (e.target.matches('[data-block-active]')) {
            e.target.closest('.fee-block').classList.toggle('is-off', !e.target.checked);
        }
    });
})();
</script>
@endpush

