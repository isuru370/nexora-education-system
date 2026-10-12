@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- =========================================================
        Page Header
    ========================================================== --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <div class="d-flex align-items-center gap-2 mb-1">

                <a href="{{ route('class-category-fees.index') }}"
                   class="btn btn-sm btn-light"
                   title="Back">
                    <i class="bi bi-arrow-left"></i>
                </a>

                <h4 class="mb-0">Fee Options</h4>
            </div>

            <p class="text-muted mb-0">
                Manage predefined fee options for this class category.
            </p>
        </div>

        {{-- Add Fee Option --}}
        @if($classCategoryFee->is_active)
            <a href="{{ route('class-category-fee-options.create', [
                    'classCategoryFeeId' => $classCategoryFee->id,
               ]) }}"
               class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>
                Add Fee Option
            </a>
        @else
            <button type="button"
                    class="btn btn-secondary"
                    disabled
                    title="Class category fee is inactive">
                <i class="bi bi-plus-lg me-1"></i>
                Add Fee Option
            </button>
        @endif

    </div>


    {{-- =========================================================
        Class Category Information
    ========================================================== --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">

                {{-- Class --}}
                <div class="col-md-4">
                    <div class="text-muted small mb-1">Class</div>
                    <div class="fw-semibold">
                        {{ $classCategoryFee->studentClass->class_name ?? 'N/A' }}
                    </div>
                </div>

                {{-- Category --}}
                <div class="col-md-4">
                    <div class="text-muted small mb-1">Category</div>
                    <div class="fw-semibold">
                        {{ $classCategoryFee->category->category_name ?? 'N/A' }}
                    </div>
                </div>

                {{-- Status --}}
                <div class="col-md-4">
                    <div class="text-muted small mb-1">Status</div>
                    @if($classCategoryFee->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </div>

            </div>
        </div>
    </div>


    {{-- =========================================================
        Alerts
    ========================================================== --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">Please fix the following errors:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- =========================================================
        Fee Options Table
    ========================================================== --}}
    <div class="card">

        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Fee Options</h5>
                    <small class="text-muted">
                        Predefined payment options available for this category.
                    </small>
                </div>

                <span class="badge bg-light text-dark">
                    {{ $feeOptions->count() }} Options
                </span>
            </div>
        </div>

        <div class="card-body p-0">

            @if($feeOptions->count() > 0)

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Fee Option</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Default</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($feeOptions as $option)
                                <tr>

                                    {{-- Number --}}
                                    <td>{{ $loop->iteration }}</td>

                                    {{-- Label --}}
                                    <td>
                                        <div class="fw-semibold">{{ $option->label }}</div>
                                        @if($option->note)
                                            <small class="text-muted">{{ $option->note }}</small>
                                        @endif
                                    </td>

                                    {{-- Fee --}}
                                    <td>
                                        <span class="fw-semibold">
                                            Rs. {{ number_format((float) $option->fee, 2) }}
                                        </span>
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        @if($option->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>

                                    {{-- Default --}}
                                    <td>
                                        @if($option->is_default)
                                            <span class="badge bg-primary">
                                                <i class="bi bi-star-fill me-1"></i>
                                                Default
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">

                                            {{-- ==========================================
                                                 ✅ EDIT — Fixed
                                            ========================================== --}}
                                            @if($classCategoryFee->is_active && $option->is_active)
                                                <a href="{{ route('class-category-fee-options.edit', [
                                                        'classCategoryFeeId' => $classCategoryFee->id,
                                                        'id' => $option->id,
                                                    ]) }}"
                                                   class="btn btn-sm btn-outline-warning"
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @else
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        disabled
                                                        title="Cannot edit inactive option">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endif


                                            {{-- ==========================================
                                                 ✅ SET DEFAULT — Fixed
                                            ========================================== --}}
                                            @if($classCategoryFee->is_active && $option->is_active && !$option->is_default)
                                                <form method="POST"
                                                      action="{{ route('class-category-fee-options.set-default', [
                                                          'classCategoryFeeId' => $classCategoryFee->id,
                                                          'id' => $option->id,
                                                      ]) }}"
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-primary"
                                                            title="Set as Default">
                                                        <i class="bi bi-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        disabled
                                                        title="{{ $option->is_default ? 'Already Default' : 'Unavailable' }}">
                                                    <i class="bi bi-star"></i>
                                                </button>
                                            @endif


                                            {{-- ==========================================
                                                 ✅ ACTIVATE / DEACTIVATE — Fixed
                                            ========================================== --}}
                                            @if($classCategoryFee->is_active)

                                                @if($option->is_active)
                                                    {{-- Deactivate --}}
                                                    <form method="POST"
                                                          action="{{ route('class-category-fee-options.deactivate', [
                                                              'classCategoryFeeId' => $classCategoryFee->id,
                                                              'id' => $option->id,
                                                          ]) }}"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to deactivate this fee option?')">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline-warning"
                                                                title="Deactivate">
                                                            <i class="bi bi-pause"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    {{-- Activate --}}
                                                    <form method="POST"
                                                          action="{{ route('class-category-fee-options.activate', [
                                                              'classCategoryFeeId' => $classCategoryFee->id,
                                                              'id' => $option->id,
                                                          ]) }}"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline-success"
                                                                title="Activate">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                            @else
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        disabled
                                                        title="Class category fee is inactive">
                                                    <i class="bi bi-pause"></i>
                                                </button>
                                            @endif


                                            {{-- ==========================================
                                                 ✅ DELETE — Fixed
                                            ========================================== --}}
                                            @if($classCategoryFee->is_active && !$option->enrollments()->exists())
                                                <form method="POST"
                                                      action="{{ route('class-category-fee-options.destroy', [
                                                          'classCategoryFeeId' => $classCategoryFee->id,
                                                          'id' => $option->id,
                                                      ]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this fee option?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-danger"
                                                            title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        disabled
                                                        title="This fee option is already used or the class category fee is inactive">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endif

                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>

            @else

                {{-- Empty State --}}
                <div class="text-center py-5 px-3">
                    <div class="mb-3">
                        <i class="bi bi-cash-stack text-muted" style="font-size: 3rem;"></i>
                    </div>

                    <h5 class="mb-2">No Fee Options Found</h5>

                    <p class="text-muted mb-4">
                        No predefined fee options have been created
                        for this class category yet.
                    </p>

                    @if($classCategoryFee->is_active)
                        <a href="{{ route('class-category-fee-options.create', [
                                'classCategoryFeeId' => $classCategoryFee->id,
                           ]) }}"
                           class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>
                            Add First Fee Option
                        </a>
                    @endif
                </div>

            @endif

        </div>
    </div>

</div>

@endsection