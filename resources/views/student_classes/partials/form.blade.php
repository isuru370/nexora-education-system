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
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST"
      action="{{ $isEdit ? route('student-classes.update', $studentClass) : route('student-classes.store') }}">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    <div class="row g-4">

        {{-- ================================================== --}}
        {{-- CLASS INFORMATION --}}
        {{-- ================================================== --}}
        <div class="col-12">
            <div class="section-card p-4">
                <h6 class="section-title">
                    <i class="bi bi-journal-bookmark me-2"></i> Class Information
                </h6>

                <div class="row g-3">

                    {{-- ✅ Branch --}}
                    <div class="col-md-6">
                        <label for="branch_id" class="form-label fw-semibold">
                            <i class="bi bi-building me-1"></i>
                            Branch <span class="text-danger">*</span>
                        </label>

                        <select
                            name="branch_id"
                            id="branch_id"
                            class="form-select custom-input @error('branch_id') is-invalid @enderror"
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
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Class Name --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-book me-1"></i>
                            Class Name <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="class_name"
                            class="form-control custom-input @error('class_name') is-invalid @enderror"
                            value="{{ old('class_name', $studentClass->class_name ?? '') }}"
                            required
                            placeholder="Enter class name">
                        @error('class_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Class Type --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-laptop me-1"></i>
                            Class Type <span class="text-danger">*</span>
                        </label>
                        <select name="class_type" class="form-select custom-input @error('class_type') is-invalid @enderror" required>
                            <option value="offline" {{ $selectedClassType === 'offline' ? 'selected="selected"' : '' }}>Offline</option>
                            <option value="online" {{ $selectedClassType === 'online' ? 'selected="selected"' : '' }}>Online</option>
                            <option value="hybrid" {{ $selectedClassType === 'hybrid' ? 'selected="selected"' : '' }}>Hybrid</option>
                        </select>
                        @error('class_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Medium --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-translate me-1"></i>
                            Medium <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="medium"
                            class="form-control custom-input @error('medium') is-invalid @enderror"
                            value="{{ old('medium', $studentClass->medium ?? 'Sinhala') }}"
                            required
                            placeholder="Sinhala / English / Tamil">
                        @error('medium')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Teacher --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person-workspace me-1"></i>
                            Teacher <span class="text-danger">*</span>
                        </label>
                        <select name="teacher_id" class="form-select custom-input @error('teacher_id') is-invalid @enderror" required>
                            <option value="">Select Teacher</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ $selectedTeacherId == $teacher->id ? 'selected="selected"' : '' }}>
                                    {{ $teacher->full_name }} - {{ $teacher->custom_id }}
                                </option>
                            @endforeach
                        </select>
                        @error('teacher_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Grade --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-mortarboard me-1"></i>
                            Grade <span class="text-danger">*</span>
                        </label>
                        <select name="grade_id" class="form-select custom-input @error('grade_id') is-invalid @enderror">
                            <option value="">Select Grade</option>
                            @foreach($grades as $grade)
                                <option value="{{ $grade->id }}" {{ $selectedGradeId == $grade->id ? 'selected="selected"' : '' }}>
                                    {{ $grade->grade_name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-2">
                            <input
                                type="text"
                                name="new_grade"
                                class="form-control custom-input @error('new_grade') is-invalid @enderror"
                                value="{{ old('new_grade') }}"
                                placeholder="Or add new grade">
                            @error('new_grade')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        @error('grade_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Subject --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-bookmark me-1"></i>
                            Subject <span class="text-danger">*</span>
                        </label>
                        <select name="subject_id" class="form-select custom-input @error('subject_id') is-invalid @enderror">
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ $selectedSubjectId == $subject->id ? 'selected="selected"' : '' }}>
                                    {{ $subject->subject_name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-2">
                            <input
                                type="text"
                                name="new_subject"
                                class="form-control custom-input @error('new_subject') is-invalid @enderror"
                                value="{{ old('new_subject') }}"
                                placeholder="Or add new subject">
                            @error('new_subject')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        @error('subject_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- ================================================== --}}
        {{-- PAYMENT CONFIGURATION --}}
        {{-- ================================================== --}}
        <div class="col-12">
            <div class="section-card p-4">
                <h6 class="section-title">
                    <i class="bi bi-cash-coin me-2"></i> Payment Configuration
                </h6>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Teacher Percentage *</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            name="teacher_percentage"
                            class="form-control custom-input @error('teacher_percentage') is-invalid @enderror"
                            value="{{ old('teacher_percentage', $paymentConfig->teacher_percentage ?? '') }}"
                            required>
                        @error('teacher_percentage')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Organizer Percentage</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            name="organizer_percentage"
                            class="form-control custom-input @error('organizer_percentage') is-invalid @enderror"
                            value="{{ old('organizer_percentage', $paymentConfig->organizer_percentage ?? 0) }}">
                        <small class="text-muted">If there is no organizer, put 0.</small>
                        @error('organizer_percentage')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Institution Percentage *</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            name="institution_percentage"
                            class="form-control custom-input @error('institution_percentage') is-invalid @enderror"
                            value="{{ old('institution_percentage', $paymentConfig->institution_percentage ?? '') }}"
                            required>
                        @error('institution_percentage')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Organizer</label>
                        <select name="organizer_id" class="form-select custom-input @error('organizer_id') is-invalid @enderror">
                            <option value="">None</option>
                            @foreach($organizers as $organizer)
                                <option value="{{ $organizer->id }}" {{ $selectedOrganizerId == $organizer->id ? 'selected="selected"' : '' }}>
                                    {{ $organizer->name }} - {{ $organizer->code }}
                                </option>
                            @endforeach
                        </select>
                        @error('organizer_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Effective From</label>
                        <input
                            type="date"
                            name="effective_from"
                            class="form-control custom-input @error('effective_from') is-invalid @enderror"
                            value="{{ $effectiveFrom }}">
                        @error('effective_from')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Effective To</label>
                        <input
                            type="date"
                            name="effective_to"
                            class="form-control custom-input @error('effective_to') is-invalid @enderror"
                            value="{{ $effectiveTo }}">
                        @error('effective_to')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
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
        <div class="col-12">
            <div class="section-card p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <h6 class="section-title mb-0">
                        <i class="bi bi-tags me-2"></i> Categories &amp; Fees *
                    </h6>
                    <small class="text-muted">Each category needs at least one fee option. ★ = the default option.</small>
                </div>

                @error('fees')
                    <div class="alert alert-danger py-2">{{ $message }}</div>
                @enderror

                <div id="feeBlocks" data-next="{{ count($feeBlocks) }}">
                    @foreach ($feeBlocks as $i => $block)
                        @include('student_classes.partials.fee-block', ['i' => $i, 'block' => $block, 'categories' => $categories, 'studentsOn' => $studentsOn])
                    @endforeach
                </div>

                <button type="button" class="btn btn-outline-primary custom-btn mt-2" data-add-block>
                    <i class="bi bi-plus-lg me-1"></i> Add category
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
        <div class="col-12">
            <div class="section-card p-4">
                <h6 class="section-title">
                    <i class="bi bi-toggle-on me-2"></i> Status
                </h6>

                <div class="d-flex gap-4 flex-wrap">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            id="is_active"
                            class="form-check-input"
                            {{ $selectedIsActive ? 'checked="checked"' : '' }}>
                        <label for="is_active" class="form-check-label fw-semibold">Active</label>
                    </div>

                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            name="is_ongoing"
                            value="1"
                            disabled
                            id="is_ongoing"
                            class="form-check-input"
                            {{ $selectedIsOngoing ? 'checked="checked"' : '' }}>
                        <label for="is_ongoing" class="form-check-label fw-semibold">Ongoing Class</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================== --}}
        {{-- BUTTONS --}}
        {{-- ================================================== --}}
        <div class="col-12 d-flex justify-content-end gap-2 pt-2">
            <a href="{{ route('student-classes.index') }}" class="btn btn-outline-secondary px-4 custom-btn">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 custom-btn">
                <i class="bi bi-save me-1"></i> {{ $buttonText ?? 'Save Class' }}
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
    .form-label i {
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

