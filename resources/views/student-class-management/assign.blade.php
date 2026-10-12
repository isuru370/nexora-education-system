@extends('layouts.app')

@section('title', 'Assign Class to Student')
@section('page-title', 'Assign Class')

@section('content')

    @php
        /** @var \App\Models\Student|null $student */
        /** @var \Illuminate\Support\Collection<int, \App\Models\StudentClass> $classes */
    @endphp

    <div class="assign-class-page">

        <div class="main-card">

            <div class="main-card-header">

                <div>
                    <h4>Assign Class to Student</h4>
                    <p>Enroll a student in a new class</p>
                </div>

                <div class="header-buttons">
                    <a href="{{ route('student-class-management.index') }}"
                       class="btn btn-light border custom-btn">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>

            </div>

            <div class="form-container">

                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        Please fix the following errors:

                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger border-0 shadow-sm">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('student-class-management.assign') }}"
                      method="POST"
                      id="assignForm">

                    @csrf

                    <div class="row g-4">

                        {{-- STUDENT --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="student_id_display" class="form-label fw-semibold">
                                    Student <span class="text-danger">*</span>
                                </label>

                                <select id="student_id_display"
                                        class="form-select custom-input"
                                        disabled>
                                    <option value="{{ $student ? $student->getKey() : '' }}" selected>
                                        {{ $student
                                            ? ($student->custom_id . ' - ' . $student->initial_name)
                                            : '-- Select Student --' }}
                                    </option>
                                </select>

                                <input type="hidden"
                                       name="student_id"
                                       id="student_id"
                                       value="{{ $student ? $student->getKey() : old('student_id') }}">
                            </div>
                        </div>


                        {{-- CLASS --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="student_class_id" class="form-label fw-semibold">
                                    Class <span class="text-danger">*</span>
                                </label>

                                <select name="student_class_id"
                                        id="student_class_id"
                                        class="form-select custom-input @error('student_class_id') is-invalid @enderror"
                                        required>

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
                        </div>


                        {{-- CATEGORY --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="class_category_fee_id" class="form-label fw-semibold">
                                    Class Category <span class="text-danger">*</span>
                                </label>

                                <select name="class_category_fee_id"
                                        id="class_category_fee_id"
                                        class="form-select custom-input @error('class_category_fee_id') is-invalid @enderror"
                                        required>
                                    <option value="">-- Select Category --</option>
                                </select>

                                @error('class_category_fee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        {{-- FEE OPTION --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="class_category_fee_option_id" class="form-label fw-semibold">
                                    Fee Option <span class="text-danger">*</span>
                                </label>

                                <select name="class_category_fee_option_id"
                                        id="class_category_fee_option_id"
                                        class="form-select custom-input @error('class_category_fee_option_id') is-invalid @enderror"
                                        required
                                        disabled>
                                    <option value="">-- Select Category First --</option>
                                </select>

                                @error('class_category_fee_option_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        {{-- STATUS --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="is_active_display" class="form-label fw-semibold">
                                    Status
                                </label>

                                <select id="is_active_display"
                                        class="form-select custom-input"
                                        disabled>
                                    <option value="1" selected>Active</option>
                                </select>

                                <input type="hidden" name="is_active" id="is_active" value="1">
                            </div>
                        </div>


                        {{-- FREE CARD --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="is_free_card" class="form-label fw-semibold">
                                    Free Card
                                </label>

                                <select name="is_free_card"
                                        id="is_free_card"
                                        class="form-select custom-input @error('is_free_card') is-invalid @enderror">

                                    <option value="0" {{ old('is_free_card', 0) == 0 ? 'selected' : '' }}>
                                        No
                                    </option>

                                    <option value="1" {{ old('is_free_card') == 1 ? 'selected' : '' }}>
                                        Yes
                                    </option>
                                </select>

                                @error('is_free_card')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <small class="text-muted">
                                    If "Yes", no fee will be charged.
                                </small>
                            </div>
                        </div>


                        {{-- NOTE --}}
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="note" class="form-label fw-semibold">
                                    Note
                                </label>

                                <textarea name="note"
                                          id="note"
                                          class="form-control custom-input @error('note') is-invalid @enderror"
                                          rows="3"
                                          placeholder="Additional notes about this enrollment">{{ old('note') }}</textarea>

                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        {{-- SUBMIT --}}
                        <div class="col-md-12 text-center mt-3">
                            <button type="submit"
                                    class="btn btn-primary custom-btn btn-lg"
                                    id="submitButton">
                                <i class="bi bi-check-lg"></i>
                                Assign Class
                            </button>

                            <a href="{{ route('student-class-management.index') }}"
                               class="btn btn-light border custom-btn btn-lg ms-2">
                                <i class="bi bi-x-lg"></i>
                                Cancel
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
    .assign-class-page {
        animation: fadeIn .4s ease;
    }

    .main-card {
        background: #fff;
        border-radius: 28px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
    }

    .main-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .main-card-header h4 {
        margin: 0;
        font-weight: 700;
    }

    .main-card-header p {
        margin: 0;
        color: #64748b;
    }

    .header-buttons {
        display: flex;
        gap: .7rem;
        flex-wrap: wrap;
    }

    .custom-btn {
        border-radius: 14px;
        padding: .7rem 1.2rem;
        font-weight: 600;
        border: none;
        transition: .2s ease;
    }

    .custom-btn:hover {
        transform: translateY(-2px);
    }

    .custom-input {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0;
        min-height: 48px;
        padding: .6rem 1rem;
    }

    .custom-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
    }

    .form-group {
        margin-bottom: .5rem;
    }

    .form-label {
        font-weight: 600;
        margin-bottom: .4rem;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media(max-width:768px) {
        .main-card-header {
            flex-direction: column;
            align-items: stretch;
        }

        .header-buttons {
            width: 100%;
        }

        .header-buttons a {
            flex: 1;
        }
    }
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