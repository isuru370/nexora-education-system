@php
    $feeData = isset($classCategoryFee) ? $classCategoryFee : null;

    $selectedStudentClassId = old(
        'student_class_id',
        isset($selectedClassId) ? $selectedClassId : optional($feeData)->student_class_id,
    );

    $selectedCategoryId = old('class_category_id', optional($feeData)->class_category_id);

    $noteValue = old('note', optional($feeData)->note ?? '');

    $isActiveInput = old('is_active', optional($feeData)->is_active ?? true);

    $formAction = $feeData
        ? route('class-category-fees.update', $feeData->id)
        : route('class-category-fees.store');
@endphp

<form method="POST" action="{{ $formAction }}">

    @csrf

    @if ($feeData)
        @method('PUT')
    @endif


    <div class="row g-3">

        {{-- Student Class --}}
        <div class="col-md-6">

            <label for="student_class_id" class="form-label">
                Class
            </label>

            <select name="student_class_id" id="student_class_id"
                class="form-select @error('student_class_id') is-invalid @enderror" required>

                <option value="">
                    Select Class
                </option>

                @foreach ($classes as $class)
                    <option value="{{ $class->id }}"
                        {{ (string) $selectedStudentClassId === (string) $class->id ? 'selected' : '' }}>

                        {{ $class->class_name }}

                        | Grade:
                        {{ optional($class->grade)->grade_name ?? 'N/A' }}

                        | Subject:
                        {{ optional($class->subject)->subject_name ?? 'N/A' }}

                    </option>
                @endforeach

            </select>

            @error('student_class_id')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

        </div>


        {{-- Category --}}
        <div class="col-md-6">

            <label for="class_category_id" class="form-label">
                Category
            </label>

            <select name="class_category_id" id="class_category_id"
                class="form-select @error('class_category_id') is-invalid @enderror" required>

                <option value="">
                    Select Category
                </option>

                @foreach ($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ (string) $selectedCategoryId === (string) $category->id ? 'selected' : '' }}>

                        {{ $category->category_name }}

                        @if ($category->code)
                            - {{ $category->code }}
                        @endif

                    </option>
                @endforeach

            </select>

            @error('class_category_id')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

        </div>


        {{-- Status --}}
        <div class="col-md-6">

            <label class="form-label d-block">
                Status
            </label>

            <input type="hidden" name="is_active" value="0">

            <div class="form-check form-switch">

                <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                    {{ $isActiveInput ? 'checked' : '' }}>

                <label for="is_active" class="form-check-label">
                    Active
                </label>

            </div>

            @error('is_active')
                <div class="text-danger small mt-1">
                    {{ $message }}
                </div>
            @enderror

        </div>


        {{-- Note --}}
        <div class="col-12">

            <label for="note" class="form-label">
                Note
            </label>

            <textarea name="note" id="note" class="form-control @error('note') is-invalid @enderror" rows="4"
                placeholder="Optional note">{{ $noteValue }}</textarea>

            @error('note')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

        </div>


        {{-- Actions --}}
        <div class="col-12">

            <div class="d-flex justify-content-end gap-2">

                <a href="{{ route('class-category-fees.index') }}" class="btn btn-secondary">
                    Cancel
                </a>

                <button type="submit" class="btn btn-primary">
                    {{ $buttonText ?? 'Save' }}
                </button>

            </div>

        </div>

    </div>

</form>
