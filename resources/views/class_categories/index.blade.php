@extends('layouts.app')

@section('title', 'Class Categories')
@section('page-title', 'Class Categories')

@section('content')
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- HERO --}}
    {{-- ===================================================== --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Class Categories</h1>
                <p class="hero-subtitle">Manage Theory, Revision and Paper categories.</p>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-categories.create') }}" class="nx-btn nx-btn-primary">
                        <i class="bi bi-plus-lg"></i> Add Category
                    </a>
                    <a href="{{ route('student-classes.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-journal-bookmark-fill"></i> View Classes
                    </a>
                    <button class="nx-btn nx-btn-light" disabled>
                        <i class="bi bi-graph-up-arrow"></i> Analytics
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- MAIN CARD --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-tags text-primary"></i> Categories Management</h5>
                <p class="nx-card-subtitle">Manage class categories used across the institute</p>
            </div>
            <span class="nx-badge nx-badge-blue">
                <i class="bi bi-list-ul"></i> {{ $categories->total() }} Categories
            </span>
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('class-categories.index') }}">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="form-control custom-input"
                                placeholder="Search category / code" value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <select name="is_active" class="form-select custom-input">
                            <option value="">All Status</option>
                            <option value="true" {{ request('is_active') === 'true' ? 'selected' : '' }}>Active</option>
                            <option value="false" {{ request('is_active') === 'false' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <button class="nx-btn nx-btn-primary w-100" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>

                    <div class="col-lg-2">
                        <a href="{{ route('class-categories.index') }}" class="nx-btn nx-btn-light w-100">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Code</th>
                        <th>Schedulable</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="row-number">
                                {{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}
                            </td>

                            {{-- CATEGORY --}}
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="category-avatar">
                                        {{ strtoupper(substr($category->category_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="cell-title">{{ $category->category_name }}</div>
                                        <small class="cell-sub">Class category item</small>
                                    </div>
                                </div>
                            </td>

                            {{-- CODE --}}
                            <td>
                                <span class="id-badge">{{ $category->code }}</span>
                            </td>

                            {{-- SCHEDULABLE --}}
                            <td>
                                @if ($category->is_schedulable)
                                    <span class="nx-badge nx-badge-blue">Yes</span>
                                @else
                                    <span class="nx-badge nx-badge-gray">No</span>
                                @endif
                            </td>

                            {{-- STATUS --}}
                            <td>
                                @if ($category->is_active)
                                    <span class="nx-badge nx-badge-green">Active</span>
                                @else
                                    <span class="nx-badge nx-badge-gray">Inactive</span>
                                @endif
                            </td>

                            {{-- ACTIONS --}}
                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="{{ route('class-categories.show', $category) }}" class="action-btn" title="View" aria-label="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="{{ route('class-categories.edit', $category) }}" class="action-btn" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>

                                    <form method="POST" action="{{ route('class-categories.toggleActive', $category) }}"
                                        onsubmit="return confirm('Change active status?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="action-btn" title="Toggle Active" aria-label="Toggle Active">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('class-categories.destroy', $category) }}"
                                        onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn action-btn-danger" title="Delete" aria-label="Delete">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-table">
                                <i class="bi bi-tags fs-2 d-block mb-2"></i>
                                <strong class="d-block">No Categories Found</strong>
                                <span class="cell-sub">Try adjusting the search filters</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $categories->links() }}
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Class Categories — page-specific styles only.
       ============================================================ */

    .dashboard-page .category-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: var(--nx-violet-soft);
        color: var(--nx-violet);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        flex-shrink: 0;
    }
</style>
@endpush