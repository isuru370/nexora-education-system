@extends('layouts.app')

@section('title', 'Student Classes - ' . $student->full_name)
@section('page-title', 'Student Classes')

@section('content')

    @php
        /** @var \App\Models\Student $student */
    @endphp

    <div class="student-class-show-page">

        <!-- Success/Error Messages -->
        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- STUDENT INFO CARD -->
        <div class="student-info-card mb-4">

            <div class="student-avatar">
                @if ($student->img_url)
                    <img src="{{ asset('storage/' . $student->img_url) }}" alt="{{ $student->full_name }}">
                @else
                    <div class="avatar-placeholder">
                        {{ strtoupper(substr($student->full_name, 0, 2)) }}
                    </div>
                @endif
            </div>

            <div class="student-details">
                <h4 class="mb-1 fw-bold">{{ $student->initial_name }}</h4>

                <div class="student-meta">
                    <span><i class="bi bi-id-card"></i> {{ $student->custom_id }}</span>
                    <span><i class="bi bi-phone"></i> {{ $student->mobile }}</span>
                    <span><i class="bi bi-envelope"></i> {{ $student->email ?? 'N/A' }}</span>

                    {{-- ✅ Branch Badge --}}
                    @php
                        $branchNames = collect($classes)
                            ->pluck('branch_name')
                            ->filter()
                            ->unique()
                            ->values();
                    @endphp

                    @if($branchNames->count() > 0)
                        <span>
                            <i class="bi bi-building"></i>
                            <strong>Branch:</strong>
                            {{ $branchNames->join(', ') }}
                        </span>
                    @endif

                    <span>
                        <i class="bi bi-circle-fill {{ $student->is_active ? 'text-success' : 'text-danger' }}"></i>
                        {{ $student->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            <div class="student-actions">
                <a href="{{ route('student-class-management.assign-form', $student->getKey()) }}"
                    class="btn btn-primary custom-btn">
                    <i class="bi bi-plus-lg"></i> Assign Class
                </a>

                <a href="{{ route('student-class-management.index') }}" class="btn btn-light border custom-btn">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

        </div>

        <!-- CLASSES CARD -->
        <div class="main-card">

            <div class="main-card-header">
                <div>
                    <h4>Enrolled Classes</h4>
                    <p>Manage class enrollments for this student</p>
                </div>

                <div class="header-buttons">
                    @if (count($classes) > 0)
                        <form action="{{ route('student-class-management.toggle-all-classes', $student->getKey()) }}"
                            method="POST" class="d-inline"
                            onsubmit="return confirm('Are you sure you want to toggle all classes status?');">
                            @csrf
                            @method('PATCH')

                            @php
                                $activeCount = $classes->where('is_active', true)->count();
                                $newStatus = $activeCount > 0 ? 0 : 1;
                            @endphp

                            <input type="hidden" name="is_active" value="{{ $newStatus }}">

                            <button type="submit" class="btn btn-warning custom-btn">
                                <i class="bi bi-arrow-repeat"></i> Toggle All
                            </button>
                        </form>
                    @endif

                    <span class="badge bg-primary class-count-badge">{{ count($classes) }} Classes</span>
                </div>
            </div>

            @if (count($classes) > 0)
                <div class="table-responsive">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class Details</th>
                                <th>Branch</th>          {{-- ✅ Branch Column --}}
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

                                    {{-- # --}}
                                    <td>{{ $index + 1 }}</td>

                                    {{-- Class Details --}}
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $class['class_name'] }}
                                        </div>

                                        <div class="text-muted small mt-1">
                                            <i class="bi bi-tag"></i>
                                            {{ $class['category_name'] }}
                                        </div>

                                        <div class="text-muted small">
                                            <i class="bi bi-mortarboard"></i>
                                            {{ $class['grade_name'] }}
                                        </div>

                                        <div class="text-muted small">
                                            <i class="bi bi-person-badge"></i>
                                            {{ $class['teacher_initials'] ?? 'N/A' }}
                                        </div>

                                        <div class="text-muted small">
                                            <i class="bi bi-book"></i>
                                            {{ $class['class_type'] }}
                                            &middot;
                                            <i class="bi bi-globe"></i>
                                            {{ $class['medium'] }}
                                        </div>
                                    </td>

                                    {{-- ✅ Branch --}}
                                    <td>
                                        @if(!empty($class['branch_name']) && $class['branch_name'] !== 'N/A')
                                            <span class="badge bg-info text-dark">
                                                <i class="bi bi-building me-1"></i>
                                                {{ $class['branch_name'] }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    {{-- Fee --}}
                                    <td>
                                        @if ($class['is_free_card'])
                                            <span class="badge bg-success">Free Card</span>
                                        @elseif (!empty($class['fee_option']))
                                            <div class="fw-semibold">
                                                {{ $class['fee_option']['label'] }}
                                            </div>
                                            <div class="text-success fw-bold mt-1">
                                                LKR {{ number_format($class['final_fee'], 2) }}
                                            </div>
                                        @else
                                            <span class="text-muted">No Fee Option</span>
                                        @endif
                                    </td>

                                    {{-- Payment Status --}}
                                    <td>
                                        @if ($class['payment_status'] == 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif ($class['payment_status'] == 'partial')
                                            <span class="badge bg-warning text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        @if ($class['is_active'])
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                            @if ($class['left_at'])
                                                <br>
                                                <small class="text-muted">
                                                    Left: {{ $class['left_at'] }}
                                                </small>
                                            @endif
                                        @endif
                                    </td>

                                    {{-- Enrolled --}}
                                    <td>
                                        <small class="text-muted">
                                            {{ $class['enrolled_at'] ?? 'N/A' }}
                                        </small>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center gap-1">

                                            {{-- Payment --}}
                                            <a href="{{ route('student-class-management.payment', [
                                                'studentId' => $student->getKey(),
                                                'studentClassId' => $class['class_id'],
                                                'enrollmentId' => $class['enrollment_id'],
                                            ]) }}"
                                                class="action-btn payment-btn" title="Payment">
                                                <i class="bi bi-cash-stack"></i>
                                            </a>

                                            {{-- Attendance --}}
                                            <a href="{{ route('student-class-management.attendance', [
                                                'studentId' => $student->getKey(),
                                                'studentClassId' => $class['class_id'],
                                                'enrollmentId' => $class['enrollment_id'],
                                            ]) }}"
                                                class="action-btn attendance-btn" title="Attendance">
                                                <i class="bi bi-calendar-check"></i>
                                            </a>

                                            {{-- Tute --}}
                                            <a href="{{ route('student-class-management.tute', [
                                                'studentId' => $student->getKey(),
                                                'studentClassId' => $class['class_id'],
                                                'enrollmentId' => $class['enrollment_id'],
                                            ]) }}"
                                                class="action-btn tute-btn" title="Tute">
                                                <i class="bi bi-journal-bookmark-fill"></i>
                                            </a>

                                            {{-- Activate / Deactivate --}}
                                            @if ($class['is_active'])
                                                <form method="POST"
                                                    action="{{ route('student-class-management.toggle-class', $class['enrollment_id']) }}"
                                                    class="d-inline" onsubmit="return confirm('Deactivate this class?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="0">
                                                    <button type="submit" class="action-btn deactivate-btn"
                                                        title="Deactivate">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST"
                                                    action="{{ route('student-class-management.toggle-class', $class['enrollment_id']) }}"
                                                    class="d-inline" onsubmit="return confirm('Activate this class?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button type="submit" class="action-btn activate-btn"
                                                        title="Activate">
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
                <div class="empty-state">
                    <i class="bi bi-journal-bookmark"></i>
                    <h5>No Classes Enrolled</h5>
                    <p class="text-muted">This student is not enrolled in any class yet.</p>
                </div>
            @endif

        </div>
    </div>

@endsection


@push('styles')
    <style>
        .student-class-show-page {
            animation: fadeIn .4s ease;
        }

        .student-info-card {
            background: #fff;
            border-radius: 24px;
            padding: 1.5rem;
            display: flex;
            gap: 1.5rem;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .04);
            border: 1px solid #eef2f7;
            flex-wrap: wrap;
        }

        .student-avatar {
            flex-shrink: 0;
        }

        .student-avatar img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #eef2f7;
        }

        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .student-details {
            flex: 1;
            min-width: 200px;
        }

        .student-details h4 {
            margin: 0;
        }

        .student-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: .5rem;
        }

        .student-meta span {
            font-size: .9rem;
            color: #64748b;
        }

        .student-meta span i {
            margin-right: .3rem;
        }

        .student-actions {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
        }

        .main-card {
            background: #fff;
            border-radius: 28px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
        }

        .main-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .main-card-header h4 {
            margin: 0;
            font-weight: 700;
        }

        .main-card-header p {
            margin: 0;
            color: #64748b;
        }

        .header-buttons {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .class-count-badge {
            font-size: 1rem;
            padding: .5rem 1rem;
            border-radius: 12px;
        }

        .custom-btn {
            border-radius: 14px;
            padding: .7rem 1.2rem;
            font-weight: 600;
            border: none;
            transition: .2s ease;
        }

        .custom-btn:hover {
            transform: translateY(-2px);
        }

        .custom-table thead th {
            border: none;
            background: #f8fafc;
            color: #475569;
            font-size: .82rem;
            text-transform: uppercase;
            padding: 1rem;
        }

        .custom-table tbody tr {
            transition: .2s ease;
        }

        .custom-table tbody tr:hover {
            background: #f8fafc;
        }

        .custom-table tbody td {
            padding: 1rem;
            border-color: #f1f5f9;
            vertical-align: middle;
        }

        .badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
        }

        .action-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all .25s ease;
            cursor: pointer;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Payment Button */
        .payment-btn { background: #ecfdf5; color: #059669; }
        .payment-btn:hover { background: #059669; color: #fff; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35); }

        /* Attendance Button */
        .attendance-btn { background: #eff6ff; color: #2563eb; }
        .attendance-btn:hover { background: #2563eb; color: #fff; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35); }

        /* Tute Button */
        .tute-btn { background: #f5f3ff; color: #7c3aed; }
        .tute-btn:hover { background: #7c3aed; color: #fff; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.35); }

        /* Deactivate Button */
        .deactivate-btn { background: #fef2f2; color: #ef4444; }
        .deactivate-btn:hover { background: #ef4444; color: #fff; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35); }

        /* Activate Button */
        .activate-btn { background: #ecfdf5; color: #10b981; }
        .activate-btn:hover { background: #10b981; color: #fff; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35); }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-state h5 {
            font-weight: 700;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media(max-width: 992px) {
            .custom-table { font-size: 0.85rem; }
            .action-btn {
                width: 34px; height: 34px;
                font-size: 0.85rem;
                border-radius: 10px;
            }
            .custom-table thead th { font-size: 0.7rem; padding: 0.7rem; }
            .custom-table tbody td { padding: 0.7rem; }
        }

        @media(max-width: 768px) {
            .student-info-card {
                flex-direction: column;
                text-align: center;
            }
            .student-meta { justify-content: center; }
            .student-actions {
                justify-content: center;
                width: 100%;
            }
            .main-card-header {
                flex-direction: column;
                align-items: stretch;
            }
            .header-buttons { width: 100%; }
            .custom-table { font-size: 0.78rem; }
            .action-btn {
                width: 32px; height: 32px;
                font-size: 0.8rem;
                border-radius: 8px;
            }
            .custom-table thead th { font-size: 0.6rem; padding: 0.5rem 0.4rem; }
            .custom-table tbody td { padding: 0.5rem 0.4rem; }
            .badge { font-size: 0.6rem; padding: 0.3rem 0.5rem; }
        }

        @media(max-width: 576px) {
            .custom-table { font-size: 0.7rem; }
            .action-btn {
                width: 28px; height: 28px;
                font-size: 0.7rem;
                border-radius: 6px;
            }
            .custom-table thead th { font-size: 0.55rem; padding: 0.4rem 0.3rem; }
            .custom-table tbody td { padding: 0.4rem 0.3rem; }
            .badge { font-size: 0.5rem; padding: 0.2rem 0.4rem; border-radius: 6px; }
            .class-count-badge { font-size: 0.75rem; padding: 0.3rem 0.6rem; }
        }
    </style>
@endpush