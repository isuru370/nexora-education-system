@extends('layouts.app')

@section('title', 'Exams')
@section('page-title', 'Exams')

@section('content')
<div class="dashboard-page">

    {{-- ===================================================== --}}
    {{-- STATS (Component — kept as-is) --}}
    {{-- ===================================================== --}}
    @include('exams.partials.stats')

    {{-- ===================================================== --}}
    {{-- MAIN CARD --}}
    {{-- ===================================================== --}}
    <div class="nx-card mb-4">

        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-journal-bookmark-fill text-primary"></i> Exam Management</h5>
                <p class="nx-card-subtitle">Manage all exams, schedules, and results</p>
            </div>

            <div class="header-buttons">
                <a href="{{ route('admin.exams.create') }}" class="nx-btn nx-btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Exam
                </a>
                <a href="{{ route('admin.exams.upcoming') }}" class="nx-btn nx-btn-light">
                    <i class="bi bi-calendar-event"></i> Upcoming
                </a>
                <a href="{{ route('admin.exams.export.excel') }}" class="nx-btn nx-btn-success">
                    <i class="bi bi-file-earmark-excel-fill"></i> Excel
                </a>
                <a href="{{ route('admin.exams.export.pdf') }}" class="nx-btn nx-btn-danger">
                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                </a>
            </div>
        </div>

        {{-- UPCOMING EXAMS ALERT --}}
        @if (isset($upcomingExams) && count($upcomingExams) > 0)
            <div class="p-3 pb-0">
                <div class="nx-alert nx-alert-blue">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        <strong>Upcoming Exams:</strong>
                        @foreach ($upcomingExams as $exam)
                            <span class="nx-badge nx-badge-blue ms-1">{{ $exam->title }}</span>
                            <small class="ms-1">({{ $exam->exam_date->format('d M Y') }})</small>
                            @if (!$loop->last) | @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('admin.exams.index') }}">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="form-control custom-input"
                                placeholder="Search exam title..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-lg-2">
                        <select name="status" class="form-select custom-input">
                            <option value="">All Status</option>
                            <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <select name="student_class_id" class="form-select custom-input">
                            <option value="">All Classes</option>
                            @foreach ($classes ?? [] as $class)
                                <option value="{{ $class->id }}" {{ request('student_class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <input type="date" name="exam_date_from" class="form-control custom-input"
                            value="{{ request('exam_date_from') }}" placeholder="Date From">
                    </div>

                    <div class="col-lg-1">
                        <button type="submit" class="nx-btn nx-btn-primary w-100" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-lg-1">
                        <a href="{{ route('admin.exams.index') }}" class="nx-btn nx-btn-light w-100">
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
                        <th>Exam Title</th>
                        <th>Class</th>
                        <th>Category</th>
                        <th>Hall</th>
                        <th>Date &amp; Time</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exams as $exam)
                        @php
                            $status   = $exam->status;
                            $isToday  = $exam->exam_date->isToday();
                            $isFuture = $exam->exam_date->isFuture();

                            $showEdit      = false;
                            $showDelete    = false;
                            $showCancel    = false;
                            $showMarkEntry = false;
                            $showResults   = false;

                            switch ($status) {
                                case 'completed':
                                    $showResults = true;
                                    break;

                                case 'scheduled':
                                    if ($isToday) {
                                        $showEdit = true;
                                        $showCancel = true;
                                        $showMarkEntry = false;
                                        $showDelete = false;
                                    } elseif ($isFuture) {
                                        $showEdit = false;
                                        $showDelete = true;
                                        $showCancel = true;
                                    } else {
                                        $showEdit = false;
                                        $showDelete = true;
                                        $showCancel = false;
                                    }
                                    break;

                                case 'ongoing':
                                    $showMarkEntry = true;
                                    break;

                                case 'cancelled':
                                    break;

                                default:
                                    break;
                            }

                            $statusBadgeMap = [
                                'scheduled' => 'nx-badge-blue',
                                'ongoing'   => 'nx-badge-amber',
                                'completed' => 'nx-badge-green',
                                'cancelled' => 'nx-badge-red',
                            ];
                            $statusClass = $statusBadgeMap[$status] ?? 'nx-badge-gray';
                        @endphp

                        <tr>
                            <td class="row-number">
                                {{ $loop->iteration + ($exams->currentPage() - 1) * $exams->perPage() }}
                            </td>

                            {{-- TITLE --}}
                            <td>
                                <div class="cell-title">{{ $exam->title }}</div>
                                @if ($exam->note)
                                    <small class="cell-sub d-block">
                                        <i class="bi bi-pencil"></i> {{ Str::limit($exam->note, 30) }}
                                    </small>
                                @endif
                            </td>

                            {{-- CLASS --}}
                            <td>
                                <div class="cell-title">{{ $exam->studentClass->class_name ?? 'N/A' }}</div>
                                <small class="cell-sub">{{ $exam->studentClass->grade->grade_name ?? '' }}</small>
                            </td>

                            {{-- CATEGORY --}}
                            <td>
                                <span class="nx-badge nx-badge-blue">
                                    {{ $exam->category->category_name ?? 'N/A' }}
                                </span>
                            </td>

                            {{-- HALL --}}
                            <td class="cell-title">{{ $exam->hall->hall_name ?? 'N/A' }}</td>

                            {{-- DATE & TIME --}}
                            <td>
                                <div class="cell-title">
                                    {{ $exam->exam_date->format('d M Y') }}
                                    @if ($isToday)
                                        <span class="nx-badge nx-badge-green ms-1">Today</span>
                                    @endif
                                </div>
                                <small class="cell-sub d-block">
                                    <i class="bi bi-clock"></i>
                                    {{ $exam->start_time }} – {{ $exam->end_time }}
                                </small>
                            </td>

                            {{-- STATUS --}}
                            <td>
                                <span class="nx-badge {{ $statusClass }}">{{ ucfirst($status) }}</span>

                                @if ($status == 'cancelled' && $exam->cancel_reason)
                                    <small class="cell-sub text-danger d-block mt-1"
                                        data-bs-toggle="tooltip"
                                        title="{{ $exam->cancel_reason }}">
                                        <i class="bi bi-info-circle"></i> Cancelled
                                    </small>
                                @endif
                            </td>

                            {{-- ACTIONS --}}
                            <td class="text-end">
                                @include('exams.partials.actions', [
                                    'exam' => $exam,
                                    'showEdit' => $showEdit,
                                    'showDelete' => $showDelete,
                                    'showCancel' => $showCancel,
                                    'showMarkEntry' => $showMarkEntry,
                                    'showResults' => $showResults,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-table">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                <strong class="d-block">No exams found</strong>
                                <span class="cell-sub">Try adjusting your search filters or create a new exam.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="p-3 border-top">
            {{ $exams->appends(request()->query())->links() }}
        </div>
    </div>

</div>

{{-- ===================================================== --}}
{{-- SINGLE CANCEL MODAL --}}
{{-- ===================================================== --}}
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                        Cancel Exam
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel <strong id="cancelExamTitle">"Exam Name"</strong>?</p>

                    <div class="mt-3">
                        <label class="nx-label">Reason for cancellation</label>
                        <textarea name="cancel_reason" class="form-control nx-input" rows="3" required
                            placeholder="Enter cancellation reason..."></textarea>
                        <small class="cell-sub d-block mt-1">This reason will be visible to students and staff.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="nx-btn nx-btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Close
                    </button>
                    <button type="submit" class="nx-btn nx-btn-danger">
                        <i class="bi bi-x-circle"></i> Cancel Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Exams Index — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    /* Inline cell title inside exam row */
    .dashboard-page .cell-title { font-size: .85rem; }

    /* Responsive */
    @media (max-width: 992px) {
        .dashboard-page .premium-table { min-width: 1100px; }
    }

    @media (max-width: 768px) {
        .dashboard-page .header-buttons {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }
    }

    /* Print */
    @media print {
        .dashboard-page .action-buttons,
        .dashboard-page .header-buttons,
        .dashboard-page .nx-search,
        .dashboard-page .nx-alert { display: none !important; }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        'use strict';

        /* 1. Tooltips */
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el, { placement: 'top', trigger: 'hover' });
        });

        /* 2. Auto-submit on select / date change */
        document.querySelectorAll('.nx-search select, .nx-search input[type="date"]').forEach(function (el) {
            el.addEventListener('change', function () {
                this.closest('form').submit();
            });
        });

        /* 3. Delete confirmation (Swal) */
        document.querySelectorAll('.action-btn-danger').forEach(function (button) {
            var form = button.closest('form');
            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: '⚠️ Delete Exam?',
                    text: 'Are you sure you want to delete this exam? This action cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    showLoaderOnConfirm: true,
                    preConfirm: () => { form.submit(); }
                });
            });
        });

        /* 4. Single cancel modal population */
        var cancelModal = document.getElementById('cancelModal');
        if (cancelModal) {
            cancelModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var examTitle = button.getAttribute('data-exam-title');
                var examRoute = button.getAttribute('data-exam-route');

                var titleEl = document.getElementById('cancelExamTitle');
                if (titleEl) titleEl.textContent = '"' + examTitle + '"';

                var form = document.getElementById('cancelForm');
                if (form) form.action = examRoute;

                var textarea = form.querySelector('textarea[name="cancel_reason"]');
                if (textarea) textarea.value = '';
            });
        }

        /* 5. Keyboard shortcut — Ctrl+F → search */
        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                var input = document.querySelector('.nx-search input[name="search"]');
                if (input) { input.focus(); input.select(); }
            }
        });

        /* 6. Toast notifications */
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        @endif

        @if (session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '{{ session('error') }}',
                timer: 4000,
                showConfirmButton: true,
                toast: true,
                position: 'top-end'
            });
        @endif

        /* 7. Filter indicator */
        var params = new URLSearchParams(window.location.search);
        if (params.toString().length > 0) {
            var badge = document.createElement('span');
            badge.className = 'nx-badge nx-badge-blue ms-2';
            badge.textContent = 'Filters Active';
            var headerTitle = document.querySelector('.nx-card-header h5');
            if (headerTitle) headerTitle.appendChild(badge);
        }
    });
</script>
@endpush