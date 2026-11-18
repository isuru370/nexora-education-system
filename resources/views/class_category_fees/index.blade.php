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

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Class Category Fees</h1>
                    <p class="hero-subtitle">Manage categories assigned to classes.</p>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('class-schedules.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-journal-bookmark"></i> Class Schedules
                        </a>
                        <a href="{{ route('student-classes.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-journal-bookmark"></i> Classes
                        </a>
                        <a href="{{ route('class-category-fees.create') }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-plus-lg"></i> Add Category
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel tw:text-(color:--nx-blue)!"></i> Filters</h5>
                    <p class="nx-card-subtitle">Filter by class, category or status</p>
                </div>
            </div>

            <div class="tw:p-4!">
                <form method="GET" action="{{ route('class-category-fees.index') }}">
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                        {{-- Class --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                            <label for="student_class_id" class="nx-label">Class</label>
                            <select name="student_class_id" id="student_class_id" class="ui-form-select nx-input">
                                <option value="">All Classes</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}"
                                        {{ (string) $currentStudentClassId === (string) $class->id ? 'selected' : '' }}>
                                        {{ $class->class_name }}
                                        | Grade: {{ optional($class->grade)->grade_name ?? 'N/A' }}
                                        | Subject: {{ optional($class->subject)->subject_name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Category --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">
                            <label for="class_category_id" class="nx-label">Category</label>
                            <select name="class_category_id" id="class_category_id" class="ui-form-select nx-input">
                                <option value="">All Categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ (string) $currentCategoryId === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->category_name }}
                                        @if ($category->code) - {{ $category->code }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">
                            <label for="is_active" class="nx-label">Status</label>
                            <select name="is_active" id="is_active" class="ui-form-select nx-input">
                                <option value="">All</option>
                                <option value="true" {{ $currentStatus === 'true' ? 'selected' : '' }}>Active</option>
                                <option value="false" {{ $currentStatus === 'false' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        {{-- Per Page --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[8.33333333%]">
                            <label for="per_page" class="nx-label">Rows</label>
                            <select name="per_page" id="per_page" class="ui-form-select nx-input">
                                <option value="10" {{ (string) $currentPerPage === '10' ? 'selected' : '' }}>10</option>
                                <option value="25" {{ (string) $currentPerPage === '25' ? 'selected' : '' }}>25</option>
                                <option value="50" {{ (string) $currentPerPage === '50' ? 'selected' : '' }}>50</option>
                                <option value="100" {{ (string) $currentPerPage === '100' ? 'selected' : '' }}>100</option>
                            </select>
                        </div>

                        {{-- Search + Reset --}}
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%] tw:flex! tw:items-end! tw:gap-2!">
                            <button type="submit" class="nx-btn nx-btn-primary tw:grow!">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <a href="{{ route('class-category-fees.index') }}" class="nx-btn nx-btn-light">
                                Reset
                            </a>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="nx-card tw:mb-6!">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-tags tw:text-(color:--nx-blue)!"></i> Category Assignments</h5>
                    <p class="nx-card-subtitle">Categories assigned to classes with schedule options</p>
                </div>
                <span class="nx-badge nx-badge-blue">
                    <i class="bi bi-list-ul"></i> {{ $fees->total() }} Records
                </span>
            </div>

            <div class="ui-table-responsive">
                <table class="ui-table premium-table tw:mb-0!">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Class</th>
                            <th>Category</th>
                            <th>Class Status</th>
                            <th>Category Fee Status</th>
                            <th class="tw:text-center!">Schedule</th>
                            <th class="tw:text-right!">Actions</th>
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
                                <td class="row-number">
                                    {{ $loop->iteration + ($fees->currentPage() - 1) * $fees->perPage() }}
                                </td>

                                {{-- CLASS --}}
                                <td>
                                    <div class="cell-title">{{ optional($studentClass)->class_name ?? '-' }}</div>
                                    <div class="cell-sub">
                                        Grade: {{ optional(optional($studentClass)->grade)->grade_name ?? 'N/A' }}
                                    </div>
                                    <div class="cell-sub">
                                        Subject: {{ optional(optional($studentClass)->subject)->subject_name ?? 'N/A' }}
                                    </div>
                                    <div class="cell-sub">
                                        Teacher: {{ optional(optional($studentClass)->teacher)->full_name ?? 'N/A' }}
                                    </div>
                                </td>

                                {{-- CATEGORY --}}
                                <td>
                                    <div class="cell-title">{{ optional($category)->category_name ?? '-' }}</div>
                                    @if (optional($category)->code)
                                        <small class="cell-sub tw:block!">{{ $category->code }}</small>
                                    @endif
                                </td>

                                {{-- CLASS STATUS --}}
                                <td>
                                    @if ($classIsActive)
                                        <span class="nx-badge nx-badge-green">Active</span>
                                    @else
                                        <span class="nx-badge nx-badge-gray">Inactive</span>
                                    @endif
                                </td>

                                {{-- CATEGORY FEE STATUS --}}
                                <td>
                                    @if ($fee->is_active)
                                        <span class="nx-badge nx-badge-green">Active</span>
                                    @else
                                        <span class="nx-badge nx-badge-gray">Inactive</span>
                                    @endif
                                </td>

                                {{-- SCHEDULE --}}
                                <td class="tw:text-center!">
                                    <div class="action-buttons">
                                        <a href="{{ route('class-schedules.index', [
                                            'student_class_id' => $fee->student_class_id,
                                            'class_category_id' => $fee->class_category_id,
                                        ]) }}" class="action-btn" title="View Schedules" aria-label="View Schedules">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    </div>
                                </td>

                                {{-- ACTIONS --}}
                                <td class="tw:text-right!">
                                    <div class="action-buttons">

                                        {{-- View --}}
                                        <a href="{{ route('class-category-fees.show', $fee->id) }}" class="action-btn" title="View" aria-label="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        {{-- Fee Options --}}
                                        @if ($classIsActive)
                                            <a href="{{ route('class-category-fee-options.index', $fee->id) }}" class="action-btn" title="Fee Options" aria-label="Fee Options">
                                                <i class="bi bi-cash-stack"></i>
                                            </a>
                                        @else
                                            <button type="button" class="action-btn" disabled title="Class is inactive" aria-label="Class is inactive">
                                                <i class="bi bi-cash-stack"></i>
                                            </button>
                                        @endif

                                        {{-- Edit --}}
                                        @if ($classIsActive)
                                            <a href="{{ route('class-category-fees.edit', $fee->id) }}" class="action-btn" title="Edit" aria-label="Edit">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                        @else
                                            <button type="button" class="action-btn" disabled title="Class is inactive" aria-label="Class is inactive">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                        @endif

                                        {{-- Toggle Active --}}
                                        @if ($classIsActive)
                                            <form method="POST"
                                                action="{{ route('class-category-fees.toggle-active', $fee->id) }}"
                                                class="tw:[display:inline]!">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="action-btn {{ $fee->is_active ? 'action-btn-warning' : 'action-btn-success' }}"
                                                    title="{{ $fee->is_active ? 'Deactivate' : 'Activate' }}"
                                                    aria-label="{{ $fee->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i class="bi {{ $fee->is_active ? 'bi-pause-fill' : 'bi-check-lg' }}"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="action-btn" disabled title="Class is inactive" aria-label="Class is inactive">
                                                <i class="bi bi-pause-fill"></i>
                                            </button>
                                        @endif

                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" class="empty-table">
                                    <i class="bi bi-tags tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:block! tw:mb-2!"></i>
                                    <strong class="tw:block!">No Class Category Fees Found</strong>
                                    <span class="cell-sub">No category assignments match your filters.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($fees->hasPages())
                <div class="tw:p-4! tw:bs-border-t!">
                    {{ $fees->withQueryString()->links() }}
                </div>
            @endif

        </div>

    </div>

@endsection


@push('styles')
<style>
    /* ============================================================
       Class Category Fees — page-specific styles only.
       ============================================================ */

    /* No page-specific styles — uses shared theme only. */
</style>
@endpush