@extends('layouts.app')

@section('title', 'Classes')
@section('page-title', 'Classes')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Classes</h1>
                <p class="hero-subtitle">Manage student classes, teachers & payment configs.</p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $classes->total() }}</span>
                        <span class="mini-stat-label">Total</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ \App\Models\StudentClass::where('is_active', 1)->count() }}</span>
                        <span class="mini-stat-label">Active</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ \App\Models\StudentClass::where('is_ongoing', 1)->count() }}</span>
                        <span class="mini-stat-label">Ongoing</span>
                    </div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-date-time">
                    <div class="date-box">
                        <i class="bi bi-journal-bookmark-fill"></i>
                        <span>Class Directory</span>
                    </div>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="stats-grid mb-4">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Classes</span>
                <h2 class="stat-number">{{ $classes->total() }}</h2>
                <span class="stat-change neutral">All <span class="stat-change-note">classes</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-play-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Active Classes</span>
                <h2 class="stat-number">{{ \App\Models\StudentClass::where('is_active', 1)->count() }}</h2>
                <span class="stat-change up">Currently <span class="stat-change-note">active</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-collection-play-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Ongoing Classes</span>
                <h2 class="stat-number">{{ \App\Models\StudentClass::where('is_ongoing', 1)->count() }}</h2>
                <span class="stat-change neutral">Running <span class="stat-change-note">classes</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-red"><i class="bi bi-pause-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Inactive Classes</span>
                <h2 class="stat-number">{{ \App\Models\StudentClass::where('is_active', 0)->count() }}</h2>
                <span class="stat-change down">Not <span class="stat-change-note">active</span></span>
            </div>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-journal-bookmark-fill text-primary"></i> Classes Management</h5>
                <p class="nx-card-subtitle">Manage student classes, teachers & payment configs</p>
            </div>
            <div class="header-buttons">
                <a href="{{ route('student-classes.create') }}" class="nx-btn nx-btn-primary"><i class="bi bi-plus-lg"></i> Add Class</a>
                <a href="{{ route('class-categories.index') }}" class="nx-btn nx-btn-light">Categories</a>
                <a href="{{ route('class-halls.index') }}" class="nx-btn nx-btn-amber">Halls</a>
                <a href="{{ route('student-classes.exportExcel') }}" class="nx-btn nx-btn-success"><i class="bi bi-file-earmark-excel-fill"></i> Excel</a>
                <a href="{{ route('student-classes.exportPdf') }}" class="nx-btn nx-btn-danger"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('student-classes.index') }}">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="form-control custom-input"
                                placeholder="Search class / teacher / subject / grade" value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <select name="class_type" class="form-select custom-input">
                            <option value="">All Types</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button class="nx-btn nx-btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                    </div>
                    <div class="col-lg-2">
                        <a href="{{ route('student-classes.index') }}" class="nx-btn nx-btn-light w-100">Reset</a>
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
                        <th>Class</th>
                        <th>Teacher</th>
                        <th>Grade / Subject</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $class)
                        <tr>
                            <td class="row-number">{{ $loop->iteration + ($classes->currentPage() - 1) * $classes->perPage() }}</td>
                            <td>
                                <div class="cell-title">{{ $class->class_name }}</div>
                                <small class="cell-sub">{{ ucfirst($class->class_type) }} / {{ $class->medium }}</small>
                            </td>
                            <td>
                                <div class="cell-title">{{ $class->teacher->full_name ?? 'N/A' }}</div>
                                <small class="cell-sub">{{ $class->teacher->custom_id ?? '' }}</small>
                            </td>
                            <td>
                                <div class="cell-title">{{ $class->grade->grade_name ?? 'N/A' }}</div>
                                <small class="cell-sub">{{ $class->subject->subject_name ?? 'N/A' }}</small>
                            </td>
                            <td>
                                @if ($class->paymentConfig)
                                    <div class="cell-sub">T: {{ $class->paymentConfig->teacher_percentage }}%</div>
                                    <div class="cell-sub">O: {{ $class->paymentConfig->organizer_percentage }}%</div>
                                    <div class="cell-sub">I: {{ $class->paymentConfig->institution_percentage }}%</div>
                                @else
                                    <span class="nx-badge nx-badge-gray">Not Set</span>
                                @endif
                            </td>
                            <td>
                                @if ($class->is_active)
                                    <span class="nx-badge nx-badge-green">Active</span>
                                @else
                                    <span class="nx-badge nx-badge-gray">Inactive</span>
                                @endif
                                <br>
                                @if ($class->is_ongoing)
                                    <span class="nx-badge nx-badge-blue mt-1">Ongoing</span>
                                @else
                                    <span class="nx-badge nx-badge-gray mt-1">Stopped</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="{{ route('student-classes.show', $class) }}" class="action-btn" title="View" aria-label="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="{{ route('student-classes.edit', $class) }}" class="action-btn" title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    @if ($class->is_active)
                                        <a href="{{ route('class-category-fees.create', ['student_class_id' => $class->id]) }}"
                                            class="action-btn" title="Fees" aria-label="Fees">
                                            <i class="bi bi-tags"></i>
                                        </a>
                                    @else
                                        <button class="action-btn" disabled>
                                            <i class="bi bi-cash-stack"></i>
                                        </button>
                                    @endif
                                    <form method="POST" action="{{ route('student-classes.toggleActive', $class) }}">
                                        @csrf @method('PATCH')
                                        <button class="action-btn" title="Toggle Active" aria-label="Toggle Active">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('student-classes.toggleOngoing', $class) }}">
                                        @csrf @method('PATCH')
                                        <button class="action-btn" title="Toggle Ongoing" aria-label="Toggle Ongoing">
                                            <i class="bi bi-toggle-on"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('student-classes.destroy', $class) }}">
                                        @csrf @method('DELETE')
                                        <button class="action-btn action-btn-danger" title="Delete" aria-label="Delete">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-table">
                                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                                <strong class="d-block">No classes found</strong>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $classes->links() }}
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Classes — page-specific styles only.
       ============================================================ */

    /* No page-specific styles — uses shared theme only. */
</style>
@endpush