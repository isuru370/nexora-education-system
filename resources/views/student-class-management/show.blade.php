@extends('layouts.app')

@section('title', 'Student Classes - ' . $student->full_name)
@section('page-title', 'Student Classes')

@section('content')
    @php
        /** @var \App\Models\Student $student */
    @endphp

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Student Classes</h1>
                    <p class="hero-subtitle">Manage class enrollments for {{ $student->initial_name }}.</p>
                </div>
                <div class="hero-right">
                    <div class="hero-date-time">
                        <div class="date-box"><i class="bi bi-person-badge"></i><span>{{ $student->custom_id }}</span></div>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="nx-alert nx-alert-green mb-4"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
        @endif
        @if (session('error'))
            <div class="nx-alert nx-alert-red mb-4"><i class="bi bi-exclamation-circle-fill"></i><span>{{ session('error') }}</span></div>
        @endif

        {{-- STUDENT INFO --}}
        <div class="nx-card mb-4">
            <div class="p-3 p-md-4">
                <div class="student-info-header">
                    <div class="student-avatar">
                        @if ($student->img_url)
                            <img src="{{ asset('storage/' . $student->img_url) }}" alt="{{ $student->full_name }}">
                        @else
                            <div class="avatar-placeholder">{{ strtoupper(substr($student->full_name, 0, 2)) }}</div>
                        @endif
                    </div>

                    <div class="student-details">
                        <h4 class="student-name">{{ $student->initial_name }}</h4>
                        @php
                            $branchNames = collect($classes)->pluck('branch_name')->filter()->unique()->values();
                        @endphp
                        <div class="student-meta">
                            <span><i class="bi bi-id-card"></i> {{ $student->custom_id }}</span>
                            <span><i class="bi bi-phone"></i> {{ $student->mobile }}</span>
                            <span><i class="bi bi-envelope"></i> {{ $student->email ?? 'N/A' }}</span>
                            @if ($branchNames->count() > 0)
                                <span><i class="bi bi-building"></i> <strong>Branch:</strong> {{ $branchNames->join(', ') }}</span>
                            @endif
                            <span>
                                <i class="bi bi-circle-fill {{ $student->is_active ? 'text-success' : 'text-danger' }}"></i>
                                {{ $student->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>

                    <div class="student-actions">
                        <a href="{{ route('student-class-management.assign-form', $student->getKey()) }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-plus-lg"></i> Assign Class
                        </a>
                        <a href="{{ route('student-class-management.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- CLASSES CARD --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-journal-bookmark-fill text-primary"></i> Enrolled Classes</h5>
                    <p class="nx-card-subtitle">Manage class enrollments for this student</p>
                </div>
                <div class="header-buttons">
                    @if (count($classes) > 0)
                        <form action="{{ route('student-class-management.toggle-all-classes', $student->getKey()) }}"
                            method="POST" class="d-inline"
                            onsubmit="return confirm('Are you sure you want to toggle all classes status?');">
                            @csrf @method('PATCH')
                            @php
                                $activeCount = $classes->where('is_active', true)->count();
                                $newStatus = $activeCount > 0 ? 0 : 1;
                            @endphp
                            <input type="hidden" name="is_active" value="{{ $newStatus }}">
                            <button type="submit" class="nx-btn nx-btn-amber"><i class="bi bi-arrow-repeat"></i> Toggle All</button>
                        </form>
                    @endif
                    <span class="nx-badge nx-badge-blue"><i class="bi bi-journal-bookmark-fill"></i> {{ count($classes) }} Classes</span>
                </div>
            </div>

            @if (count($classes) > 0)
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class Details</th>
                                <th>Branch</th>
                                <th>Fee (LKR)</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Enrolled</th>
                                <th width="210" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classes as $index => $class)
                                <tr>
                                    <td class="row-number">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="cell-title">{{ $class['class_name'] }}</div>
                                        <div class="cell-sub mt-1"><i class="bi bi-tag"></i> {{ $class['category_name'] }}</div>
                                        <div class="cell-sub"><i class="bi bi-mortarboard"></i> {{ $class['grade_name'] }}</div>
                                        <div class="cell-sub"><i class="bi bi-person-badge"></i> {{ $class['teacher_initials'] ?? 'N/A' }}</div>
                                        <div class="cell-sub">
                                            <i class="bi bi-book"></i> {{ $class['class_type'] }}
                                            · <i class="bi bi-globe"></i> {{ $class['medium'] }}
                                        </div>
                                    </td>
                                    <td>
                                        @if (!empty($class['branch_name']) && $class['branch_name'] !== 'N/A')
                                            <span class="nx-badge nx-badge-blue"><i class="bi bi-building"></i> {{ $class['branch_name'] }}</span>
                                        @else
                                            <span class="cell-sub">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($class['is_free_card'])
                                            <span class="nx-badge nx-badge-green">Free Card</span>
                                        @elseif (!empty($class['fee_option']))
                                            <div class="cell-title">{{ $class['fee_option']['label'] }}</div>
                                            <div class="fee-amount">LKR {{ number_format($class['final_fee'], 2) }}</div>
                                        @else
                                            <span class="cell-sub">No Fee Option</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($class['payment_status'] == 'paid')
                                            <span class="nx-badge nx-badge-green">Paid</span>
                                        @elseif ($class['payment_status'] == 'partial')
                                            <span class="nx-badge nx-badge-amber">Partial</span>
                                        @else
                                            <span class="nx-badge nx-badge-red">Unpaid</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($class['is_active'])
                                            <span class="nx-badge nx-badge-green">Active</span>
                                        @else
                                            <span class="nx-badge nx-badge-red">Inactive</span>
                                            @if ($class['left_at'])
                                                <small class="cell-sub d-block mt-1">Left: {{ $class['left_at'] }}</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td><small class="cell-sub">{{ $class['enrolled_at'] ?? 'N/A' }}</small></td>
                                    <td class="text-end">
                                        <div class="action-buttons">
                                            <a href="{{ route('student-class-management.payment', [
                                                'studentId' => $student->getKey(),
                                                'studentClassId' => $class['class_id'],
                                                'enrollmentId' => $class['enrollment_id'],
                                            ]) }}" class="action-btn" title="Payment" aria-label="Payment">
                                                <i class="bi bi-cash-stack"></i>
                                            </a>
                                            <a href="{{ route('student-class-management.attendance', [
                                                'studentId' => $student->getKey(),
                                                'studentClassId' => $class['class_id'],
                                                'enrollmentId' => $class['enrollment_id'],
                                            ]) }}" class="action-btn" title="Attendance" aria-label="Attendance">
                                                <i class="bi bi-calendar-check"></i>
                                            </a>
                                            <a href="{{ route('student-class-management.tute', [
                                                'studentId' => $student->getKey(),
                                                'studentClassId' => $class['class_id'],
                                                'enrollmentId' => $class['enrollment_id'],
                                            ]) }}" class="action-btn" title="Tute" aria-label="Tute">
                                                <i class="bi bi-journal-bookmark-fill"></i>
                                            </a>
                                            @if ($class['is_active'])
                                                <form method="POST"
                                                    action="{{ route('student-class-management.toggle-class', $class['enrollment_id']) }}"
                                                    class="d-inline" onsubmit="return confirm('Deactivate this class?');">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="is_active" value="0">
                                                    <button type="submit" class="action-btn action-btn-danger" title="Deactivate" aria-label="Deactivate">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST"
                                                    action="{{ route('student-class-management.toggle-class', $class['enrollment_id']) }}"
                                                    class="d-inline" onsubmit="return confirm('Activate this class?');">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button type="submit" class="action-btn action-btn-success" title="Activate" aria-label="Activate">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-table">
                    <i class="bi bi-journal-bookmark fs-2 d-block mb-2"></i>
                    <strong class="d-block">No Classes Enrolled</strong>
                    <span class="cell-sub">This student is not enrolled in any class yet.</span>
                </div>
            @endif
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Student Classes (show) — page-specific styles only.
       ============================================================ */

    .dashboard-page .student-info-header {
        display: flex;
        gap: 1.25rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .dashboard-page .student-avatar { flex-shrink: 0; }
    .dashboard-page .student-avatar img {
        width: 80px; height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--nx-border);
    }
    .dashboard-page .avatar-placeholder {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        font-weight: 700;
    }
    .dashboard-page .student-details { flex: 1; min-width: 200px; }
    .dashboard-page .student-name {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--nx-text);
        margin: 0 0 .35rem;
    }
    .dashboard-page .student-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem 1rem;
        font-size: .82rem;
        color: var(--nx-muted);
    }
    .dashboard-page .student-meta span i { margin-right: .3rem; }
    .dashboard-page .student-meta .text-success { color: var(--nx-green) !important; }
    .dashboard-page .student-meta .text-danger { color: var(--nx-red) !important; }
    .dashboard-page .student-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .dashboard-page .fee-amount {
        color: var(--nx-green);
        font-weight: 700;
        margin-top: .15rem;
        font-size: .82rem;
    }
    .dashboard-page .nx-btn-amber {
        background: #fff;
        border-color: var(--nx-border);
        color: var(--nx-amber);
    }
    .dashboard-page .nx-btn-amber i { color: var(--nx-amber); }

    @media (max-width: 992px) {
        .dashboard-page .student-info-header { flex-direction: column; text-align: center; }
        .dashboard-page .student-meta { justify-content: center; }
        .dashboard-page .student-actions { justify-content: center; width: 100%; }
        .dashboard-page .premium-table { min-width: 1100px; }
    }
    @media (max-width: 576px) {
        .dashboard-page .avatar-placeholder,
        .dashboard-page .student-avatar img { width: 64px; height: 64px; font-size: 1.3rem; }
        .dashboard-page .student-name { font-size: 1rem; }
        .dashboard-page .student-meta { font-size: .75rem; }
    }
</style>
@endpush