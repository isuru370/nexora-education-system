@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-4">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


<div class="row">

    {{-- =========================================================
        STUDENT
    ========================================================== --}}
    <div class="col-md-6 mb-3">

        <label for="student_id" class="form-label">
            Student <span class="text-danger">*</span>
        </label>

        @if ($enrollment->exists)

            <input type="hidden"
                   name="student_id"
                   value="{{ $enrollment->student_id }}">

            <input type="text"
                   class="form-control"
                   value="{{ $enrollment->student?->custom_id ?? '-' }} - {{ $enrollment->student?->initial_name ?? '-' }}"
                   readonly>

        @else

            <select name="student_id"
                    id="student_id"
                    class="form-control @error('student_id') is-invalid @enderror"
                    required>

                @if ($enrollment->student)

                    <option value="{{ $enrollment->student_id }}" selected>
                        {{ $enrollment->student?->custom_id ?? '-' }}
                        -
                        {{ $enrollment->student?->initial_name ?? '-' }}
                    </option>

                @endif

            </select>

            @error('student_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

        @endif

    </div>


    {{-- =========================================================
        CLASS
    ========================================================== --}}
    <div class="col-md-6 mb-3">

        <label for="student_class_id" class="form-label">
            Class <span class="text-danger">*</span>
        </label>

        @if ($enrollment->exists)

            <input type="hidden"
                   name="student_class_id"
                   value="{{ $enrollment->student_class_id }}">

            <input type="text"
                   class="form-control"
                   value="{{ $enrollment->studentClass?->class_name ?? '-' }} | Grade: {{ optional($enrollment->studentClass?->grade)->grade_name ?? '-' }} | Subject: {{ optional($enrollment->studentClass?->subject)->subject_name ?? '-' }} | Teacher: {{ optional($enrollment->studentClass?->teacher)->initials ?? '-' }}"
                   readonly>

        @else

            <select name="student_class_id"
                    id="student_class_id"
                    class="form-control @error('student_class_id') is-invalid @enderror"
                    required>

                @if ($enrollment->studentClass)

                    <option value="{{ $enrollment->student_class_id }}" selected>
                        {{ $enrollment->studentClass?->class_name ?? '-' }}
                        | Grade: {{ optional($enrollment->studentClass?->grade)->grade_name ?? '-' }}
                        | Subject: {{ optional($enrollment->studentClass?->subject)->subject_name ?? '-' }}
                        | Teacher: {{ optional($enrollment->studentClass?->teacher)->initials ?? '-' }}
                    </option>

                @endif

            </select>

            @error('student_class_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

        @endif

    </div>


    {{-- =========================================================
        CATEGORY
    ========================================================== --}}
    <div class="col-md-6 mb-3">

        <label for="class_category_fee_id" class="form-label">
            Category <span class="text-danger">*</span>
        </label>

        <select name="class_category_fee_id"
                id="class_category_fee_id"
                class="form-control @error('class_category_fee_id') is-invalid @enderror"
                required>

            @if ($enrollment->classCategoryFee)

                <option value="{{ $enrollment->class_category_fee_id }}" selected>
                    {{ optional($enrollment->classCategoryFee?->category)->category_name ?? '-' }}
                </option>

            @else

                <option value="">Select class first</option>

            @endif

        </select>

        @error('class_category_fee_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>


    {{-- =========================================================
        FEE OPTION
    ========================================================== --}}
    <div class="col-md-6 mb-3">

        <label for="class_category_fee_option_id" class="form-label">
            Fee Option <span class="text-danger">*</span>
        </label>

        <select name="class_category_fee_option_id"
                id="class_category_fee_option_id"
                class="form-control @error('class_category_fee_option_id') is-invalid @enderror"
                required>

            <option value="">Select category first</option>

            @if (isset($feeOptions))

                @foreach ($feeOptions as $option)

                    <option value="{{ $option->id }}"
                            data-fee="{{ $option->fee }}"
                        {{ old('class_category_fee_option_id', $enrollment->class_category_fee_option_id) == $option->id ? 'selected' : '' }}>

                        {{ $option->label }} - Rs. {{ number_format($option->fee, 2) }}
                        @if ($option->is_default)
                            (Default)
                        @endif

                    </option>

                @endforeach

            @endif

        </select>

        @error('class_category_fee_option_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

    </div>


    {{-- =========================================================
        SELECTED FEE
    ========================================================== --}}
    <div class="col-md-3 mb-3">

        <label for="selected_fee" class="form-label">
            Selected Fee
        </label>

        <input type="text"
               id="selected_fee"
               class="form-control"
               value="0.00"
               readonly>

    </div>


    {{-- =========================================================
        FREE CARD
    ========================================================== --}}
    <div class="col-md-3 mb-3">

        <label for="is_free_card" class="form-label">
            Free Card
        </label>

        <select name="is_free_card"
                id="is_free_card"
                class="form-control">

            <option value="0"
                {{ old('is_free_card', $enrollment->is_free_card) == 0 ? 'selected' : '' }}>
                No
            </option>

            <option value="1"
                {{ old('is_free_card', $enrollment->is_free_card) == 1 ? 'selected' : '' }}>
                Yes
            </option>

        </select>

    </div>


    {{-- =========================================================
        ENROLLED AT
    ========================================================== --}}
    <div class="col-md-3 mb-3">

        <label for="enrolled_at" class="form-label">
            Enrolled At
        </label>

        <input type="date"
               name="enrolled_at"
               id="enrolled_at"
               class="form-control"
               value="{{ old('enrolled_at', optional($enrollment->enrolled_at)->format('Y-m-d')) }}">

    </div>


    {{-- =========================================================
        ACTIVE STATUS (Edit only)
    ========================================================== --}}
    @if ($enrollment->exists)

        <div class="col-md-3 mb-3">

            <label for="is_active" class="form-label">
                Active Status
            </label>

            <select name="is_active"
                    id="is_active"
                    class="form-control">

                <option value="1"
                    {{ old('is_active', $enrollment->is_active) == 1 ? 'selected' : '' }}>
                    Active
                </option>

                <option value="0"
                    {{ old('is_active', $enrollment->is_active) == 0 ? 'selected' : '' }}>
                    Inactive
                </option>

            </select>

        </div>

    @endif


    {{-- =========================================================
        LEFT AT (Edit only)
    ========================================================== --}}
    @if ($enrollment->exists)

        <div class="col-md-3 mb-3">

            <label for="left_at" class="form-label">
                Left At
            </label>

            <input type="date"
                   name="left_at"
                   id="left_at"
                   class="form-control"
                   value="{{ old('left_at', optional($enrollment->left_at)->format('Y-m-d')) }}">

        </div>

    @endif


    {{-- =========================================================
        NOTE
    ========================================================== --}}
    <div class="col-md-12 mb-3">

        <label for="note" class="form-label">
            Note
        </label>

        <textarea name="note"
                  id="note"
                  class="form-control"
                  rows="3">{{ old('note', $enrollment->note) }}</textarea>

    </div>

</div>


{{-- =============================================================
    BUTTONS
============================================================= --}}
<div class="d-flex gap-2">

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i>
        {{ $buttonText ?? 'Save' }}
    </button>

    <a href="{{ route('student-class-enrollments.index') }}"
       class="btn btn-secondary">
        Cancel
    </a>

</div>


{{-- =============================================================
    SELECT2 CSS
============================================================= --}}
@push('styles')

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
          rel="stylesheet">

@endpush


{{-- =============================================================
    SCRIPTS
============================================================= --}}
@push('scripts')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        /*
        |--------------------------------------------------------------------------
        | Variables
        |--------------------------------------------------------------------------
        */

        var categoryUrl = "{{ url('class-category-fees/by-class') }}";

        var selectedClassId = "{{ old('student_class_id', $enrollment->student_class_id) }}";
        var selectedCategoryId = "{{ old('class_category_fee_id', $enrollment->class_category_fee_id) }}";
        var selectedOptionId = "{{ old('class_category_fee_option_id', $enrollment->class_category_fee_option_id) }}";

        var classCategoryData = [];

        /*
        |--------------------------------------------------------------------------
        | Escape HTML
        |--------------------------------------------------------------------------
        */
        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return $('<div>').text(value).html();
        }

        /*
        |--------------------------------------------------------------------------
        | Update Selected Fee
        |--------------------------------------------------------------------------
        */
        function updateSelectedFee() {
            var selectedOption = $('#class_category_fee_option_id option:selected');
            var fee = parseFloat(selectedOption.attr('data-fee')) || 0;
            var isFree = $('#is_free_card').val() == '1';

            if (isFree) {
                $('#selected_fee').val('0.00');
            } else {
                $('#selected_fee').val(fee.toFixed(2));
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Load Fee Options
        |--------------------------------------------------------------------------
        */
        function loadFeeOptionsFromData(feeOptions, selectedOptionId) {
            var $feeOption = $('#class_category_fee_option_id');

            $feeOption.html('<option value="">Select Fee Option</option>');
            $('#selected_fee').val('0.00');

            if (!feeOptions || feeOptions.length === 0) {
                $feeOption.html('<option value="">No fee options available</option>');
                return;
            }

            $.each(feeOptions, function(index, item) {
                var selected = String(selectedOptionId) === String(item.id) ? 'selected' : '';
                var defaultText = item.is_default ? ' (Default)' : '';

                $feeOption.append(
                    '<option value="' + escapeHtml(item.id) + '" ' +
                    'data-fee="' + escapeHtml(item.fee) + '" ' +
                    selected + '>' +
                    escapeHtml(item.label) + ' - Rs. ' +
                    parseFloat(item.fee).toFixed(2) + defaultText +
                    '</option>'
                );
            });

            updateSelectedFee();
        }

        /*
        |--------------------------------------------------------------------------
        | Render Categories
        |--------------------------------------------------------------------------
        */
        function renderCategories(selectedCategoryId, selectedOptionId) {
            var $category = $('#class_category_fee_id');

            $category.html('<option value="">Select Category</option>');

            if (!classCategoryData || classCategoryData.length === 0) {
                $('#class_category_fee_option_id').html('<option value="">No categories available</option>');
                $('#selected_fee').val('0.00');
                return;
            }

            $.each(classCategoryData, function(index, item) {
                var selected = String(selectedCategoryId) === String(item.id) ? 'selected' : '';

                $category.append(
                    '<option value="' + escapeHtml(item.id) + '" ' + selected + '>' +
                    escapeHtml(item.category_name) +
                    '</option>'
                );
            });

            var selectedCategory = classCategoryData.find(function(item) {
                return String(item.id) === String(selectedCategoryId);
            });

            if (selectedCategory) {
                loadFeeOptionsFromData(selectedCategory.fee_options || [], selectedOptionId);
            } else {
                $('#class_category_fee_option_id').html('<option value="">Select category first</option>');
                $('#selected_fee').val('0.00');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Load Categories
        |--------------------------------------------------------------------------
        */
        function loadCategories(classId, selectedCategoryId, selectedOptionId) {
            $('#class_category_fee_id').html('<option value="">Loading...</option>');
            $('#class_category_fee_option_id').html('<option value="">Select category first</option>');
            $('#selected_fee').val('0.00');
            classCategoryData = [];

            if (!classId) {
                $('#class_category_fee_id').html('<option value="">Select class first</option>');
                return;
            }

            $.ajax({
                url: categoryUrl + '/' + classId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    classCategoryData = data || [];
                    renderCategories(selectedCategoryId, selectedOptionId);
                },
                error: function(xhr) {
                    console.error('Failed to load class categories:', xhr);
                    $('#class_category_fee_id').html('<option value="">Failed to load categories</option>');
                    $('#class_category_fee_option_id').html('<option value="">Select category first</option>');
                    $('#selected_fee').val('0.00');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Category Changed
        |--------------------------------------------------------------------------
        */
        $('#class_category_fee_id').on('change', function() {
            var categoryFeeId = $(this).val();

            if (!categoryFeeId) {
                $('#class_category_fee_option_id').html('<option value="">Select category first</option>');
                $('#selected_fee').val('0.00');
                return;
            }

            var selectedCategory = classCategoryData.find(function(item) {
                return String(item.id) === String(categoryFeeId);
            });

            if (!selectedCategory) {
                $('#class_category_fee_option_id').html('<option value="">No fee options available</option>');
                $('#selected_fee').val('0.00');
                return;
            }

            loadFeeOptionsFromData(selectedCategory.fee_options || [], null);
        });

        /*
        |--------------------------------------------------------------------------
        | Fee Option Changed
        |--------------------------------------------------------------------------
        */
        $('#class_category_fee_option_id').on('change', function() {
            updateSelectedFee();
        });

        /*
        |--------------------------------------------------------------------------
        | Free Card Changed
        |--------------------------------------------------------------------------
        */
        $('#is_free_card').on('change', function() {
            updateSelectedFee();
        });

        /*
        |--------------------------------------------------------------------------
        | Document Ready
        |--------------------------------------------------------------------------
        */
        $(document).ready(function() {

            @if (!$enrollment->exists)

                // Student Select2
                $('#student_id').select2({
                    placeholder: 'Search by Student ID, QR, Full Name, Initial Name',
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url: "{{ route('students.search') }}",
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return { q: params.term || '' };
                        },
                        processResults: function(data) {
                            return { results: data };
                        }
                    }
                });

                // Class Select2
                $('#student_class_id').select2({
                    placeholder: 'Search class',
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url: "{{ route('student-classes.search') }}",
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return { q: params.term || '' };
                        },
                        processResults: function(data) {
                            return { results: data };
                        }
                    }
                });

                // Class Changed
                $('#student_class_id').on('change', function() {
                    var classId = $(this).val();
                    loadCategories(classId, null, null);
                });

            @endif

            // Existing / Old Data
            if (selectedClassId) {
                loadCategories(selectedClassId, selectedCategoryId, selectedOptionId);
            } else {
                updateSelectedFee();
            }

        });
    </script>

@endpush