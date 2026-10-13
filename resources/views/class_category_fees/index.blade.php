@extends('layouts.app')

@section('title', 'Class Category Fees')
@section('page-title', 'Class Category Fees')

@section('content')

    @php
        $currentStudentClassId = request('student_class_id');
        $currentCategoryId = request('class_category_id');
        $currentStatus = request('is_active');
        $currentPerPage = request('per_page', 10);
    @endphp

    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h4 class="mb-1">
                    Class Category Fees
                </h4>

                <p class="text-muted mb-0">
                    Manage categories assigned to classes.
                </p>
            </div>

            <div class="d-flex gap-2">

                <a href="{{ route('class-schedules.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-journal-bookmark"></i>
                    Class Schedules
                </a>

                <a href="{{ route('student-classes.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-journal-bookmark"></i>
                    Classes
                </a>
                

                <a href="{{ route('class-category-fees.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    Add Category
                </a>

            </div>

        </div>


        {{-- Filters --}}
        <div class="card mb-4">

            <div class="card-body">

                <form method="GET" action="{{ route('class-category-fees.index') }}">

                    <div class="row g-3">

                        {{-- Class --}}
                        <div class="col-md-4">

                            <label for="student_class_id" class="form-label">
                                Class
                            </label>

                            <select name="student_class_id" id="student_class_id" class="form-select">

                                <option value="">
                                    All Classes
                                </option>

                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}"
                                        {{ (string) $currentStudentClassId === (string) $class->id ? 'selected' : '' }}>
                                        {{ $class->class_name }}

                                        | Grade:
                                        {{ optional($class->grade)->grade_name ?? 'N/A' }}

                                        | Subject:
                                        {{ optional($class->subject)->subject_name ?? 'N/A' }}
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Category --}}
                        <div class="col-md-3">

                            <label for="class_category_id" class="form-label">
                                Category
                            </label>

                            <select name="class_category_id" id="class_category_id" class="form-select">

                                <option value="">
                                    All Categories
                                </option>

                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ (string) $currentCategoryId === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->category_name }}

                                        @if ($category->code)
                                            - {{ $category->code }}
                                        @endif
                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Status --}}
                        <div class="col-md-2">

                            <label for="is_active" class="form-label">
                                Status
                            </label>

                            <select name="is_active" id="is_active" class="form-select">

                                <option value="">
                                    All
                                </option>

                                <option value="true" {{ $currentStatus === 'true' ? 'selected' : '' }}>
                                    Active
                                </option>

                                <option value="false" {{ $currentStatus === 'false' ? 'selected' : '' }}>
                                    Inactive
                                </option>

                            </select>

                        </div>


                        {{-- Per Page --}}
                        <div class="col-md-1">

                            <label for="per_page" class="form-label">
                                Rows
                            </label>

                            <select name="per_page" id="per_page" class="form-select">

                                <option value="10" {{ (string) $currentPerPage === '10' ? 'selected' : '' }}>
                                    10
                                </option>

                                <option value="25" {{ (string) $currentPerPage === '25' ? 'selected' : '' }}>
                                    25
                                </option>

                                <option value="50" {{ (string) $currentPerPage === '50' ? 'selected' : '' }}>
                                    50
                                </option>

                                <option value="100" {{ (string) $currentPerPage === '100' ? 'selected' : '' }}>
                                    100
                                </option>

                            </select>

                        </div>


                        {{-- Search --}}
                        <div class="col-md-2 d-flex align-items-end gap-2">

                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="bi bi-search"></i>
                                Search
                            </button>

                            <a href="{{ route('class-category-fees.index') }}" class="btn btn-outline-secondary">
                                Reset
                            </a>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        {{-- Table --}}
        <div class="card">

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Class
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Class Status
                                </th>

                                <th>
                                    Category Fee Status
                                </th>

                                <th class="text-center">
                                    Schedule
                                </th>

                                <th class="text-end">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($fees as $fee)
                                @php
                                    $studentClass = $fee->studentClass;
                                    $category = $fee->category;

                                    $classIsActive = $studentClass && $studentClass->is_active;

                                    $categoryIsActive = $category && $category->is_active;

                                    $canSchedule = $classIsActive && $categoryIsActive && $fee->is_active;
                                @endphp


                                <tr>

                                    {{-- Number --}}
                                    <td>
                                        {{ $loop->iteration + ($fees->currentPage() - 1) * $fees->perPage() }}
                                    </td>


                                    {{-- Class --}}
                                    <td>

                                        <div class="fw-semibold">
                                            {{ optional($studentClass)->class_name ?? '-' }}
                                        </div>

                                        <div class="small text-muted">
                                            Grade:
                                            {{ optional(optional($studentClass)->grade)->grade_name ?? 'N/A' }}
                                        </div>

                                        <div class="small text-muted">
                                            Subject:
                                            {{ optional(optional($studentClass)->subject)->subject_name ?? 'N/A' }}
                                        </div>

                                        <div class="small text-muted">
                                            Teacher:
                                            {{ optional(optional($studentClass)->teacher)->full_name ?? 'N/A' }}
                                        </div>

                                    </td>


                                    {{-- Category --}}
                                    <td>

                                        <div class="fw-semibold">
                                            {{ optional($category)->category_name ?? '-' }}
                                        </div>

                                        @if (optional($category)->code)
                                            <small class="text-muted">
                                                {{ $category->code }}
                                            </small>
                                        @endif

                                    </td>


                                    {{-- Class Status --}}
                                    <td>

                                        @if ($classIsActive)
                                            <span class="badge bg-success">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Inactive
                                            </span>
                                        @endif

                                    </td>


                                    {{-- Category Fee Status --}}
                                    <td>

                                        @if ($fee->is_active)
                                            <span class="badge bg-success">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Inactive
                                            </span>
                                        @endif

                                    </td>


                                    {{-- Schedule --}}
                                    <td class="text-center">

                                        <div class="d-flex justify-content-center gap-1">

                                            @if ($canSchedule)
                                                <a href="{{ route('class-schedules.create', [
                                                    'student_class_id' => $fee->student_class_id,
                                                    'class_category_id' => $fee->class_category_id,
                                                ]) }}"
                                                    class="btn btn-sm btn-outline-success" title="Create Schedule">
                                                    <i class="bi bi-plus-circle"></i>
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                    title="Schedule not allowed">
                                                    <i class="bi bi-plus-circle"></i>
                                                </button>
                                            @endif


                                            <a href="{{ route('class-schedules.index', [
                                                'student_class_id' => $fee->student_class_id,
                                                'class_category_id' => $fee->class_category_id,
                                            ]) }}"
                                                class="btn btn-sm btn-outline-primary" title="View Schedules">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                        </div>

                                    </td>


                                    {{-- Actions --}}
                                    <td class="text-end">

                                        <div class="d-flex justify-content-end gap-1">


                                            {{-- View --}}
                                            <a href="{{ route('class-category-fees.show', $fee->id) }}"
                                                class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            {{-- Fee Options --}}
                                            @if ($classIsActive)
                                                <a href="{{ route('class-category-fee-options.index', $fee->id) }}"
                                                    class="btn btn-sm btn-outline-info" title="Fee Options">
                                                    <i class="bi bi-cash-stack"></i>
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                    title="Class is inactive">
                                                    <i class="bi bi-cash-stack"></i>
                                                </button>
                                            @endif


                                            {{-- Edit --}}
                                            @if ($classIsActive)
                                                <a href="{{ route('class-category-fees.edit', $fee->id) }}"
                                                    class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                    title="Class is inactive">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endif


                                            {{-- Toggle Active --}}
                                            @if ($classIsActive)
                                                <form method="POST"
                                                    action="{{ route('class-category-fees.toggle-active', $fee->id) }}"
                                                    class="d-inline">

                                                    @csrf
                                                    @method('PATCH')

                                                    <button type="submit"
                                                        class="btn btn-sm {{ $fee->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                        title="{{ $fee->is_active ? 'Deactivate' : 'Activate' }}">

                                                        <i
                                                            class="bi {{ $fee->is_active ? 'bi-pause' : 'bi-check-lg' }}"></i>

                                                    </button>

                                                </form>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                    title="Class is inactive">
                                                    <i class="bi bi-pause"></i>
                                                </button>
                                            @endif


                                            {{-- Delete --}}
                                            @if ($classIsActive)
                                                <form method="POST"
                                                    action="{{ route('class-category-fees.destroy', $fee->id) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this class category fee?')">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>

                                                </form>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                    title="Class is inactive">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="7" class="text-center py-5">

                                        <div class="text-muted">

                                            <i class="bi bi-tags fs-1"></i>

                                            <h5 class="mt-3">
                                                No Class Category Fees Found
                                            </h5>

                                            <p class="mb-0">
                                                No category assignments match your filters.
                                            </p>

                                        </div>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Pagination --}}
            @if ($fees->hasPages())
                <div class="card-footer bg-white">

                    {{ $fees->withQueryString()->links() }}

                </div>
            @endif

        </div>

    </div>

@endsection
