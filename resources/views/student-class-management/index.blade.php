@extends('layouts.app')

@section('title', 'Student Class Management')
@section('page-title', 'Student Class Management')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">Student Class Management</h1>
                <p class="hero-subtitle">Search students and manage their class enrollments.</p>
                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ number_format($stats['total_students'] ?? 0) }}</span>
                        <span class="mini-stat-label">Total Students</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ number_format($stats['active_enrollments'] ?? 0) }}</span>
                        <span class="mini-stat-label">Active</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ number_format($stats['inactive_enrollments'] ?? 0) }}</span>
                        <span class="mini-stat-label">Inactive</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ number_format($stats['active_classes'] ?? 0) }}</span>
                        <span class="mini-stat-label">Classes</span>
                    </div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-date-time">
                    <div class="date-box"><i class="bi bi-person-workspace"></i><span>Class Enrollments</span></div>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="stats-grid mb-4">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Students</span>
                <h2 class="stat-number">{{ number_format($stats['total_students'] ?? 0) }}</h2>
                <span class="stat-change neutral">All <span class="stat-change-note">students</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Active Enrollments</span>
                <h2 class="stat-number">{{ number_format($stats['active_enrollments'] ?? 0) }}</h2>
                <span class="stat-change up">Currently <span class="stat-change-note">active</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Inactive Enrollments</span>
                <h2 class="stat-number">{{ number_format($stats['inactive_enrollments'] ?? 0) }}</h2>
                <span class="stat-change down">Not <span class="stat-change-note">active</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Active Classes</span>
                <h2 class="stat-number">{{ number_format($stats['active_classes'] ?? 0) }}</h2>
                <span class="stat-change neutral">Running <span class="stat-change-note">classes</span></span>
            </div>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-person-workspace text-primary"></i> Search & Manage</h5>
                <p class="nx-card-subtitle">Search students by name, mobile number, custom ID or QR code and manage their class enrollments</p>
            </div>
        </div>

        {{-- ALERTS --}}
        <div class="p-3">
            @if (session('success'))
                <div class="nx-alert nx-alert-green mb-3"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
            @endif
            @if (session('error'))
                <div class="nx-alert nx-alert-red mb-3"><i class="bi bi-exclamation-circle-fill"></i><span>{{ session('error') }}</span></div>
            @endif
            @if (session('info'))
                <div class="nx-alert nx-alert-blue mb-3"><i class="bi bi-info-circle-fill"></i><span>{{ session('info') }}</span></div>
            @endif
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="POST" action="{{ route('student-class-management.search') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-10">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search"
                                class="form-control custom-input @error('search') is-invalid @enderror"
                                placeholder="Search by Name, Mobile, Custom ID or QR code..."
                                value="{{ old('search') }}" autocomplete="off" autofocus required>
                            @error('search')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <button type="submit" class="nx-btn nx-btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="cell-sub">
                        <i class="bi bi-info-circle"></i>
                        Search using <strong>Student Name</strong>, <strong>Mobile Number</strong>,
                        <strong>WhatsApp Number</strong>, <strong>Guardian Mobile</strong>,
                        <strong>Custom ID</strong> or <strong>Temporary QR Code</strong>.
                    </small>
                </div>
            </form>
        </div>

        {{-- QUICK ACTIONS --}}
        <div class="p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="{{ route('student-class-management.assign-form') }}" class="nx-quick-action">
                        <div class="qa-icon icon-blue"><i class="bi bi-person-plus"></i></div>
                        <div class="qa-content"><h6>Assign Class</h6><small>Enroll student in a class</small></div>
                        <i class="bi bi-chevron-right qa-arrow"></i>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('students.index') }}" class="nx-quick-action">
                        <div class="qa-icon icon-green"><i class="bi bi-list-ul"></i></div>
                        <div class="qa-content"><h6>All Students</h6><small>View all students</small></div>
                        <i class="bi bi-chevron-right qa-arrow"></i>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('student-classes.index') }}" class="nx-quick-action">
                        <div class="qa-icon icon-violet"><i class="bi bi-journal-bookmark-fill"></i></div>
                        <div class="qa-content"><h6>All Classes</h6><small>View all classes</small></div>
                        <i class="bi bi-chevron-right qa-arrow"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Student Class Management — page-specific styles only.
       ============================================================ */

    /* No page-specific styles — uses shared theme only. */
</style>
@endpush