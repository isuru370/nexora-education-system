@extends('layouts.app')

@section('title', 'Fee Options')
@section('page-title', 'Fee Options')

@section('content')
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- HERO --}}
    {{-- ===================================================== --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Fee Options</h1>
                <p class="hero-subtitle">Manage predefined fee options for this class category.</p>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-category-fees.index') }}" class="nx-btn nx-btn-light" title="Back">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>

                    @if ($classCategoryFee->is_active)
                        <a href="{{ route('class-category-fee-options.create', [
                            'classCategoryFeeId' => $classCategoryFee->id,
                        ]) }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-plus-lg"></i> Add Fee Option
                        </a>
                    @else
                        <button type="button" class="nx-btn nx-btn-light" disabled title="Class category fee is inactive">
                            <i class="bi bi-plus-lg"></i> Add Fee Option
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- CLASS CATEGORY INFO --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-info-circle text-primary"></i> Class Category Information</h5>
                <p class="nx-card-subtitle">Context for these fee options</p>
            </div>
        </div>

        <div class="p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <span class="cell-sub">Class</span>
                    <div class="cell-title">{{ $classCategoryFee->studentClass->class_name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <span class="cell-sub">Category</span>
                    <div class="cell-title">{{ $classCategoryFee->category->category_name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <span class="cell-sub">Status</span>
                    <div>
                        @if ($classCategoryFee->is_active)
                            <span class="nx-badge nx-badge-green">Active</span>
                        @else
                            <span class="nx-badge nx-badge-gray">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- ALERTS --}}
    {{-- ===================================================== --}}
    @if (session('success'))
        <div class="nx-alert nx-alert-green mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
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

    {{-- ===================================================== --}}
    {{-- FEE OPTIONS TABLE --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-cash-stack text-primary"></i> Fee Options</h5>
                <p class="nx-card-subtitle">Predefined payment options available for this category.</p>
            </div>
            <span class="nx-badge nx-badge-blue">{{ $feeOptions->count() }} Options</span>
        </div>

        @if ($feeOptions->count() > 0)
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
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
                        @foreach ($feeOptions as $option)
                            <tr>
                                <td class="row-number">{{ $loop->iteration }}</td>

                                {{-- LABEL --}}
                                <td>
                                    <div class="cell-title">{{ $option->label }}</div>
                                    @if ($option->note)
                                        <small class="cell-sub d-block">{{ $option->note }}</small>
                                    @endif
                                </td>

                                {{-- FEE --}}
                                <td>
                                    <span class="cell-title">Rs. {{ number_format((float) $option->fee, 2) }}</span>
                                </td>

                                {{-- STATUS --}}
                                <td>
                                    @if ($option->is_active)
                                        <span class="nx-badge nx-badge-green">Active</span>
                                    @else
                                        <span class="nx-badge nx-badge-gray">Inactive</span>
                                    @endif
                                </td>

                                {{-- DEFAULT --}}
                                <td>
                                    @if ($option->is_default)
                                        <span class="nx-badge nx-badge-blue">
                                            <i class="bi bi-star-fill"></i> Default
                                        </span>
                                    @else
                                        <span class="cell-sub">—</span>
                                    @endif
                                </td>

                                {{-- ACTIONS --}}
                                <td class="text-end">
                                    <div class="action-buttons">

                                        {{-- EDIT --}}
                                        @if ($classCategoryFee->is_active && $option->is_active)
                                            <a href="{{ route('class-category-fee-options.edit', [
                                                'classCategoryFeeId' => $classCategoryFee->id,
                                                'id' => $option->id,
                                            ]) }}" class="action-btn" title="Edit" aria-label="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                        @else
                                            <button type="button" class="action-btn" disabled title="Cannot edit inactive option" aria-label="Cannot edit inactive option">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                        @endif

                                        {{-- SET DEFAULT --}}
                                        @if ($classCategoryFee->is_active && $option->is_active && !$option->is_default)
                                            <form method="POST"
                                                action="{{ route('class-category-fee-options.set-default', [
                                                    'classCategoryFeeId' => $classCategoryFee->id,
                                                    'id' => $option->id,
                                                ]) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="action-btn" title="Set as Default" aria-label="Set as Default">
                                                    <i class="bi bi-star"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="action-btn" disabled
                                                title="{{ $option->is_default ? 'Already Default' : 'Unavailable' }}"
                                                aria-label="{{ $option->is_default ? 'Already Default' : 'Unavailable' }}">
                                                <i class="bi bi-star"></i>
                                            </button>
                                        @endif

                                        {{-- ACTIVATE / DEACTIVATE --}}
                                        @if ($classCategoryFee->is_active)
                                            @if ($option->is_active)
                                                <form method="POST"
                                                    action="{{ route('class-category-fee-options.deactivate', [
                                                        'classCategoryFeeId' => $classCategoryFee->id,
                                                        'id' => $option->id,
                                                    ]) }}" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to deactivate this fee option?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="action-btn action-btn-warning" title="Deactivate" aria-label="Deactivate">
                                                        <i class="bi bi-pause-fill"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST"
                                                    action="{{ route('class-category-fee-options.activate', [
                                                        'classCategoryFeeId' => $classCategoryFee->id,
                                                        'id' => $option->id,
                                                    ]) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="action-btn action-btn-success" title="Activate" aria-label="Activate">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <button type="button" class="action-btn" disabled title="Class category fee is inactive" aria-label="Class category fee is inactive">
                                                <i class="bi bi-pause-fill"></i>
                                            </button>
                                        @endif

                                        {{-- DELETE — commented out --}}
                                        {{-- @if($classCategoryFee->is_active && !$option->enrollments()->exists())
                                            <form method="POST" action="..." class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this fee option?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn action-btn-danger" title="Delete" aria-label="Delete">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        @endif --}}

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-table">
                <i class="bi bi-cash-stack fs-2 d-block mb-3"></i>
                <strong class="d-block mb-2">No Fee Options Found</strong>
                <span class="cell-sub d-block mb-4">
                    No predefined fee options have been created for this class category yet.
                </span>

                @if ($classCategoryFee->is_active)
                    <a href="{{ route('class-category-fee-options.create', [
                        'classCategoryFeeId' => $classCategoryFee->id,
                    ]) }}" class="nx-btn nx-btn-primary">
                        <i class="bi bi-plus-lg"></i> Add First Fee Option
                    </a>
                @endif
            </div>
        @endif

    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Fee Options — page-specific styles only.
       ============================================================ */

    /* No page-specific styles — uses shared theme only. */
</style>
@endpush