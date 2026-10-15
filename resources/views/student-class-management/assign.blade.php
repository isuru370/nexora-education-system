@extends('layouts.app')

@section('title', 'Assign Class to Student')
@section('page-title', 'Assign Class')

@section('content')

    @php
        /** @var \App\Models\Student|null $student */
        /** @var \Illuminate\Support\Collection<int, \App\Models\StudentClass> $classes */
    @endphp

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Assign Class to Student</h1>
                    <p class="hero-subtitle">Enroll a student in a new class.</p>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('student-class-management.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- MAIN CARD --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-person-plus text-primary"></i> Enrollment Details</h5>
                    <p class="nx-card-subtitle">Select a class, category and fee option for the student</p>
                </div>
            </div>

            <div class="p-3 p-md-4">

                {{-- VALIDATION ERRORS --}}
                @if ($errors->any())
                    <div class="nx-alert nx-alert-red mb-4">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <div>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="nx-alert nx-alert-red mb-4">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if (session('success'))
                    <div class="nx-alert nx-alert-green mb-4">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('student-class-management.assign') }}" method="POST" id="assignForm">
                    @csrf

                    <div class="row g-3">

                        {{-- STUDENT --}}
                        <div class="col-md-6">
                            <label for="student_id_display" class="nx-label">
                                Student <span style="color: var(--nx-red);">*</span>
                            </label>

                            <select id="student_id_display" class="form-select nx-input" disabled>
                                <option value="{{ $student ? $student->getKey() : '' }}" selected>
                                    {{ $student
                                        ? ($student->custom_id . ' - ' . $student->initial_name)
                                        : '-- Select Student --' }}
                                </option>
                            </select>

                            <input type="hidden" name="student_id" id="student_id"
                                value="{{ $student ? $student->getKey() : old('student_id') }}">
                        </div>

                        {{-- CLASS --}}
                        <div class="col-md-6">
                            <label for="student_class_id" class="nx-label">
                                Class <span style="color: var(--nx-red);">*</span>
                            </label>

                            <select name="student_class_id" id="student_class_id"
                                class="form-select nx-input @error('student_class_id') is-invalid @enderror" required>

                                <option value="">-- Select Class --</option>

                                @foreach ($classes as $class)
                                    <option value="{{ $class->getKey() }}"
                                        {{ old('student_class_id') == $class->getKey() ? 'selected' : '' }}>
                                        {{ $class->class_name }}
                                        ({{ $class->class_type }} - {{ $class->medium }})
                                    </option>
                                @endforeach

                            </select>

                            @error('student_class_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- CATEGORY --}}
                        <div class="col-md-6">
                            <label for="class_category_fee_id" class="nx-label">
                                Class Category <span style="color: var(--nx-red);">*</span>
                            </label>

                            <select name="class_category_fee_id" id="class_category_fee_id"
                                class="form-select nx-input @error('class_category_fee_id') is-invalid @enderror"
                                required>
                                <option value="">-- Select Category --</option>
                            </select>

                            @error('class_category_fee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- FEE OPTION --}}
                        <div class="col-md-6">
                            <label for="class_category_fee_option_id" class="nx-label">
                                Fee Option <span style="color: var(--nx-red);">*</span>
                            </label>

                            <select name="class_category_fee_option_id" id="class_category_fee_option_id"
                                class="form-select nx-input @error('class_category_fee_option_id') is-invalid @enderror"
                                required disabled>
                                <option value="">-- Select Category First --</option>
                            </select>

                            @error('class_category_fee_option_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- STATUS --}}
                        <div class="col-md-6">
                            <label for="is_active_display" class="nx-label">Status</label>

                            <select id="is_active_display" class="form-select nx-input" disabled>
                                <option value="1" selected>Active</option>
                            </select>

                            <input type="hidden" name="is_active" id="is_active" value="1">
                        </div>

                        {{-- FREE CARD --}}
                        <div class="col-md-6">
                            <label for="is_free_card" class="nx-label">Free Card</label>

                            <select name="is_free_card" id="is_free_card"
                                class="form-select nx-input @error('is_free_card') is-invalid @enderror">

                                <option value="0" {{ old('is_free_card', 0) == 0 ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_free_card') == 1 ? 'selected' : '' }}>Yes</option>

                            </select>

                            @error('is_free_card')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <small class="cell-sub d-block mt-1">If "Yes", no fee will be charged.</small>
                        </div>

                        {{-- NOTE --}}
                        <div class="col-md-12">
                            <label for="note" class="nx-label">Note</label>

                            <textarea name="note" id="note"
                                class="form-control nx-input @error('note') is-invalid @enderror"
                                rows="3"
                                placeholder="Additional notes about this enrollment">{{ old('note') }}</textarea>

                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- SUBMIT --}}
                        <div class="col-md-12 text-center mt-4 pt-3" style="border-top: 1px solid var(--nx-border);">
                            <button type="submit" class="nx-btn nx-btn-primary" id="submitButton">
                                <i class="bi bi-check-lg"></i> Assign Class
                            </button>

                            <a href="{{ route('student-class-management.index') }}"
                                class="nx-btn nx-btn-light ms-2">
                                <i class="bi bi-x-lg"></i> Cancel
                            </a>
                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection


@push('styles')
<style>
    /* ============================================================
       Assign Class to Student — page-specific styles only.
       ============================================================ */

    /* No page-specific styles — uses shared theme only. */
</style>
@endpush


@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(document).ready(function() {
    /*
    |--------------------------------------------------------------------------
    | Classes Data
    |--------------------------------------------------------------------------
    */
    const classesData = @json($classes->toArray());


    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */
    const classSelect = $('#student_class_id');
    const categorySelect = $('#class_category_fee_id');
    const feeOptionSelect = $('#class_category_fee_option_id');


    /*
    |--------------------------------------------------------------------------
    | Old Values
    |--------------------------------------------------------------------------
    */
    const oldCategoryFeeId = @json(old('class_category_fee_id'));
    const oldFeeOptionId = @json(old('class_category_fee_option_id'));


    /*
    |--------------------------------------------------------------------------
    | Helper: Find Selected Class
    |--------------------------------------------------------------------------
    */
    function getSelectedClass(classId) {
        if (!classId) {
            return null;
        }

        return classesData.find(function(item) {
            return String(item.id) === String(classId);
        }) || null;
    }


    /*
    |--------------------------------------------------------------------------
    | Load Categories
    |--------------------------------------------------------------------------
    */
    function loadCategories(classId, selectedCategoryId = null, selectedFeeOptionId = null) {
        categorySelect
            .empty()
            .append('<option value="">-- Select Category --</option>')
            .prop('disabled', true);

        feeOptionSelect
            .empty()
            .append('<option value="">-- Select Category First --</option>')
            .prop('disabled', true);

        if (!classId) {
            return;
        }

        const selectedClass = getSelectedClass(classId);

        if (!selectedClass) {
            console.error('Class not found:', classId);
            return;
        }

        /*
        | Relationship name: category_fees (snake_case)
        */
        const categoryFees = selectedClass.category_fees || [];

        if (categoryFees.length === 0) {
            categorySelect
                .empty()
                .append('<option value="">-- No Categories Available --</option>')
                .prop('disabled', true);
            return;
        }

        categoryFees.forEach(function(categoryFee) {
            const categoryName = categoryFee.category && categoryFee.category.category_name
                ? categoryFee.category.category_name
                : 'N/A';

            const option = $('<option></option>');
            option.val(categoryFee.id);
            option.text(categoryName);
            categorySelect.append(option);
        });

        categorySelect.prop('disabled', false);

        if (selectedCategoryId) {
            categorySelect.val(selectedCategoryId);

            const selectedCategory = categoryFees.find(function(item) {
                return String(item.id) === String(selectedCategoryId);
            });

            if (selectedCategory) {
                loadFeeOptions(selectedCategory, selectedFeeOptionId);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Load Fee Options
    |--------------------------------------------------------------------------
    */
    function loadFeeOptions(categoryFee, selectedFeeOptionId = null) {
        feeOptionSelect
            .empty()
            .append('<option value="">-- Select Fee Option --</option>')
            .prop('disabled', true);

        if (!categoryFee) {
            return;
        }

        /*
        | Relationship name: active_fee_options (snake_case)
        */
        const feeOptions = categoryFee.active_fee_options || [];

        if (feeOptions.length === 0) {
            feeOptionSelect
                .empty()
                .append('<option value="">-- No Fee Options Available --</option>')
                .prop('disabled', true);
            return;
        }

        feeOptions.forEach(function(feeOption) {
            const option = $('<option></option>');
            option.val(feeOption.id);

            let text = feeOption.label || 'Fee Option';

            if (feeOption.fee !== undefined && feeOption.fee !== null) {
                text += ' - LKR ' + Number(feeOption.fee).toLocaleString('en-LK', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            if (feeOption.is_default) {
                text += ' (Default)';
            }

            option.text(text);
            feeOptionSelect.append(option);
        });

        feeOptionSelect.prop('disabled', false);

        if (selectedFeeOptionId) {
            feeOptionSelect.val(selectedFeeOptionId);
        } else {
            const defaultOption = feeOptions.find(function(option) {
                return option.is_default === true;
            });

            if (defaultOption) {
                feeOptionSelect.val(defaultOption.id);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Class Changed
    |--------------------------------------------------------------------------
    */
    classSelect.on('change', function() {
        const classId = $(this).val();
        loadCategories(classId);
    });


    /*
    |--------------------------------------------------------------------------
    | Category Changed
    |--------------------------------------------------------------------------
    */
    categorySelect.on('change', function() {
        const categoryFeeId = $(this).val();
        const classId = classSelect.val();

        if (!categoryFeeId || !classId) {
            feeOptionSelect
                .empty()
                .append('<option value="">-- Select Category First --</option>')
                .prop('disabled', true);
            return;
        }

        const selectedClass = getSelectedClass(classId);

        if (!selectedClass) {
            return;
        }

        const categoryFees = selectedClass.category_fees || [];

        const selectedCategory = categoryFees.find(function(item) {
            return String(item.id) === String(categoryFeeId);
        });

        if (!selectedCategory) {
            feeOptionSelect
                .empty()
                .append('<option value="">-- No Fee Options Available --</option>')
                .prop('disabled', true);
            return;
        }

        loadFeeOptions(selectedCategory);
    });


    /*
    |--------------------------------------------------------------------------
    | Initial Load
    |--------------------------------------------------------------------------
    */
    const initialClassId = classSelect.val();

    if (initialClassId) {
        loadCategories(initialClassId, oldCategoryFeeId, oldFeeOptionId);
    }


    /*
    |--------------------------------------------------------------------------
    | Form Validation
    |--------------------------------------------------------------------------
    */
    $('#assignForm').on('submit', function(e) {
        const studentId = $('#student_id').val();
        const classId = $('#student_class_id').val();
        const categoryFeeId = $('#class_category_fee_id').val();
        const feeOptionId = $('#class_category_fee_option_id').val();

        if (!studentId) {
            e.preventDefault();
            alert('Please select a student.');
            return false;
        }

        if (!classId) {
            e.preventDefault();
            alert('Please select a class.');
            return false;
        }

        if (!categoryFeeId) {
            e.preventDefault();
            alert('Please select a class category.');
            return false;
        }

        if (!feeOptionId) {
            e.preventDefault();
            alert('Please select a fee option.');
            return false;
        }

        return true;
    });

});
</script>
@endpush