@extends('layouts.app')

@section('title', 'Class Category Fee Details')
@section('page-title', 'Class Category Fee Details')

@section('content')

    @php
        $studentClass = optional($classCategoryFee)->studentClass;
        $category = optional($classCategoryFee)->category;

        $className = optional($studentClass)->class_name ?? '-';
        $gradeName = optional(optional($studentClass)->grade)->grade_name ?? 'N/A';
        $subjectName = optional(optional($studentClass)->subject)->subject_name ?? 'N/A';
        $teacherName = optional(optional($studentClass)->teacher)->full_name ?? 'N/A';

        $categoryName = optional($category)->category_name ?? '-';
        $categoryCode = optional($category)->code ?? '-';

        $classIsActive = optional($studentClass)->is_active ?? false;
        $categoryIsActive = optional($category)->is_active ?? false;
        $feeIsActive = optional($classCategoryFee)->is_active ?? false;
    @endphp


    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h4 class="mb-1">
                    Class Category Fee Details
                </h4>

                <p class="text-muted mb-0">
                    {{ $className }} / {{ $categoryName }}
                </p>
            </div>

            <div class="d-flex gap-2">

                <a
                    href="{{ route('class-category-fees.index') }}"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back
                </a>

                @if($classIsActive && $categoryIsActive)

                    <a
                        href="{{ route('class-category-fees.edit', $classCategoryFee->id) }}"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-pencil"></i>
                        Edit
                    </a>

                @endif

            </div>

        </div>


        {{-- Messages --}}
        @if(session('success'))

            <div class="alert alert-success">
                {{ session('success') }}
            </div>

        @endif


        @if(session('error'))

            <div class="alert alert-danger">
                {{ session('error') }}
            </div>

        @endif


        {{-- Main Details --}}
        <div class="card">

            <div class="card-header">
                <h5 class="mb-0">
                    Class Category Information
                </h5>
            </div>

            <div class="card-body">

                <div class="row g-4">


                    {{-- Class --}}
                    <div class="col-md-6">

                        <div class="border rounded p-3 h-100">

                            <h6 class="mb-3">
                                <i class="bi bi-book"></i>
                                Class Information
                            </h6>

                            <div class="mb-2">
                                <strong>Class:</strong>
                                {{ $className }}
                            </div>

                            <div class="mb-2">
                                <strong>Grade:</strong>
                                {{ $gradeName }}
                            </div>

                            <div class="mb-2">
                                <strong>Subject:</strong>
                                {{ $subjectName }}
                            </div>

                            <div class="mb-3">
                                <strong>Teacher:</strong>
                                {{ $teacherName }}
                            </div>


                            @if($classIsActive)

                                <span class="badge bg-success">
                                    Active
                                </span>

                            @else

                                <span class="badge bg-secondary">
                                    Inactive
                                </span>

                            @endif

                        </div>

                    </div>


                    {{-- Category --}}
                    <div class="col-md-6">

                        <div class="border rounded p-3 h-100">

                            <h6 class="mb-3">
                                <i class="bi bi-tags"></i>
                                Category Information
                            </h6>

                            <div class="mb-2">
                                <strong>Category:</strong>
                                {{ $categoryName }}
                            </div>

                            <div class="mb-3">
                                <strong>Code:</strong>
                                {{ $categoryCode }}
                            </div>


                            @if($categoryIsActive)

                                <span class="badge bg-success">
                                    Active
                                </span>

                            @else

                                <span class="badge bg-secondary">
                                    Inactive
                                </span>

                            @endif

                        </div>

                    </div>


                    {{-- Association Status --}}
                    <div class="col-md-6">

                        <div class="border rounded p-3 h-100">

                            <h6 class="mb-3">
                                <i class="bi bi-link-45deg"></i>
                                Assignment Status
                            </h6>

                            @if($feeIsActive)

                                <span class="badge bg-success mb-2">
                                    Active
                                </span>

                                <div class="text-muted small">
                                    This category is currently assigned to this class.
                                </div>

                            @else

                                <span class="badge bg-secondary mb-2">
                                    Inactive
                                </span>

                                <div class="text-muted small">
                                    This category assignment is currently inactive.
                                </div>

                            @endif

                        </div>

                    </div>


                    {{-- Record Information --}}
                    <div class="col-md-6">

                        <div class="border rounded p-3 h-100">

                            <h6 class="mb-3">
                                <i class="bi bi-info-circle"></i>
                                Record Information
                            </h6>

                            <div class="mb-2">
                                <strong>ID:</strong>
                                #{{ $classCategoryFee->id }}
                            </div>

                            <div class="mb-2">
                                <strong>Created:</strong>

                                {{ $classCategoryFee->created_at
                                    ? $classCategoryFee->created_at->format('d M Y h:i A')
                                    : 'N/A'
                                }}
                            </div>

                            <div>
                                <strong>Updated:</strong>

                                {{ $classCategoryFee->updated_at
                                    ? $classCategoryFee->updated_at->format('d M Y h:i A')
                                    : 'N/A'
                                }}
                            </div>

                        </div>

                    </div>


                    {{-- Note --}}
                    @if($classCategoryFee->note)

                        <div class="col-12">

                            <div class="border rounded p-3">

                                <h6 class="mb-2">
                                    <i class="bi bi-sticky"></i>
                                    Note
                                </h6>

                                <div>
                                    {{ $classCategoryFee->note }}
                                </div>

                            </div>

                        </div>

                    @endif


                    {{-- Fee Options --}}
                    <div class="col-12">

                        <div class="border rounded p-3">

                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <div>
                                    <h6 class="mb-1">
                                        <i class="bi bi-list-ul"></i>
                                        Fee Options
                                    </h6>

                                    <small class="text-muted">
                                        Predefined fee options for this class category.
                                    </small>
                                </div>

                            </div>


                            @if($classCategoryFee->feeOptions->count())

                                <div class="table-responsive">

                                    <table class="table table-sm align-middle mb-0">

                                        <thead>

                                            <tr>
                                                <th>Label</th>
                                                <th>Fee</th>
                                                <th>Default</th>
                                                <th>Status</th>
                                            </tr>

                                        </thead>

                                        <tbody>

                                            @foreach($classCategoryFee->feeOptions as $option)

                                                <tr>

                                                    <td>
                                                        {{ $option->label }}
                                                    </td>

                                                    <td>
                                                        Rs. {{ number_format($option->fee, 2) }}
                                                    </td>

                                                    <td>

                                                        @if($option->is_default)

                                                            <span class="badge bg-primary">
                                                                Default
                                                            </span>

                                                        @else

                                                            <span class="text-muted">
                                                                No
                                                            </span>

                                                        @endif

                                                    </td>

                                                    <td>

                                                        @if($option->is_active)

                                                            <span class="badge bg-success">
                                                                Active
                                                            </span>

                                                        @else

                                                            <span class="badge bg-secondary">
                                                                Inactive
                                                            </span>

                                                        @endif

                                                    </td>

                                                </tr>

                                            @endforeach

                                        </tbody>

                                    </table>

                                </div>

                            @else

                                <div class="text-muted">
                                    No fee options have been created for this category yet.
                                </div>

                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Bottom Actions --}}
        <div class="d-flex justify-content-end gap-2 mt-4">

            <a
                href="{{ route('class-category-fees.index') }}"
                class="btn btn-secondary"
            >
                Back
            </a>

            @if($classIsActive && $categoryIsActive)

                <a
                    href="{{ route('class-category-fees.edit', $classCategoryFee->id) }}"
                    class="btn btn-primary"
                >
                    <i class="bi bi-pencil"></i>
                    Edit
                </a>

            @endif

        </div>

    </div>

@endsection