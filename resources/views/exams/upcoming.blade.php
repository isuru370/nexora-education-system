@extends('layouts.app')

@section('title', 'Upcoming Exams')
@section('page-title', 'Upcoming Exams')

@php
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('h:i A') : '-';
    $thisWeek = $upcomingExams->filter(fn ($e) => $e->exam_date->lte(today()->addDays(7)))->count();
@endphp

@section('content')
<div class="dashboard-page">

    {{-- STATS --}}
    <div class="stats-grid mb-4">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-info">
                <span class="stat-label">Upcoming</span>
                <h2 class="stat-number">{{ $stats['upcoming'] ?? 0 }}</h2>
                <span class="stat-change up">From today <span class="stat-change-note">onwards</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-calendar-week"></i></div>
            <div class="stat-info">
                <span class="stat-label">Next 7 Days</span>
                <h2 class="stat-number">{{ $thisWeek }}</h2>
                <span class="stat-change neutral">Coming <span class="stat-change-note">this week</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-play-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Ongoing</span>
                <h2 class="stat-number">{{ $stats['ongoing'] ?? 0 }}</h2>
                <span class="stat-change neutral">Waiting <span class="stat-change-note">for marks</span></span>
            </div>
        </div>
    </div>

    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-calendar-event text-primary"></i> Upcoming Exams</h5>
                <p class="nx-card-subtitle">Scheduled exams from today, nearest first</p>
            </div>

            <div class="header-buttons">
                <a href="{{ route('admin.exams.index') }}" class="nx-btn nx-btn-light">
                    <i class="bi bi-arrow-left"></i> All Exams
                </a>
                <a href="{{ route('admin.exams.create') }}" class="nx-btn nx-btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Exam
                </a>
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="nx-search">
            <form method="GET" action="{{ route('admin.exams.upcoming') }}">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="text" name="search" class="form-control custom-input"
                                placeholder="Search exam title..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <select name="student_class_id" class="form-select custom-input">
                            <option value="">All Classes</option>
                            @foreach ($classes ?? [] as $class)
                                <option value="{{ $class->id }}" {{ request('student_class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-1">
                        <button type="submit" class="nx-btn nx-btn-primary w-100" aria-label="Search">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-lg-1">
                        <a href="{{ route('admin.exams.upcoming') }}" class="nx-btn nx-btn-light w-100">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Exam Title</th>
                        <th>Class</th>
                        <th>Category</th>
                        <th>Hall</th>
                        <th>Time</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exams as $exam)
                        @php
                            $days = (int) today()->diffInDays($exam->exam_date, false);
                            [$whenText, $whenClass] = match (true) {
                                $days <= 0 => ['Today', 'nx-badge-green'],
                                $days === 1 => ['Tomorrow', 'nx-badge-amber'],
                                $days <= 7 => ["In {$days} days", 'nx-badge-blue'],
                                default => ["In {$days} days", 'nx-badge-gray'],
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="date-chip">
                                    <span class="date-chip-day">{{ $exam->exam_date->format('d') }}</span>
                                    <span class="date-chip-month">{{ $exam->exam_date->format('M') }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">{{ $exam->title }}</div>
                                <span class="nx-badge {{ $whenClass }} mt-1">{{ $whenText }}</span>
                            </td>
                            <td>
                                <div class="cell-title">{{ $exam->studentClass->class_name ?? 'N/A' }}</div>
                                <small class="cell-sub">{{ $exam->studentClass->grade->grade_name ?? '' }}</small>
                            </td>
                            <td><span class="nx-badge nx-badge-blue">{{ $exam->category->category_name ?? 'N/A' }}</span></td>
                            <td class="cell-title">{{ $exam->hall->hall_name ?? '-' }}</td>
                            <td>
                                <div class="cell-title">{{ $exam->exam_date->format('l') }}</div>
                                <small class="cell-sub"><i class="bi bi-clock"></i> {{ $fmtTime($exam->start_time) }} - {{ $fmtTime($exam->end_time) }}</small>
                            </td>
                            <td class="text-end">
                                @include('exams.partials.actions', [
                                    'exam' => $exam,
                                    'showEdit' => true,
                                    'showDelete' => false,
                                    'showCancel' => true,
                                    'showMarkEntry' => false,
                                    'showResults' => false,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-table">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                <strong class="d-block">No upcoming exams</strong>
                                <span class="cell-sub">Scheduled exams from today will show here.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            {{ $exams->links() }}
        </div>
    </div>

</div>

{{-- CANCEL MODAL (filled from the clicked row) --}}
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Cancel Exam
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Cancel <strong id="cancelExamTitle"></strong>?</p>
                    <label class="nx-label" for="cancel_reason">Reason for cancellation</label>
                    <textarea id="cancel_reason" name="cancel_reason" class="form-control custom-input" rows="3" required
                        placeholder="e.g. Hall not available"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="nx-btn nx-btn-light" data-bs-dismiss="modal">Close</button>
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
    .dashboard-page .date-chip {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        border-radius: 10px;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        line-height: 1;
    }
    .dashboard-page .date-chip-day { font-size: 1.15rem; font-weight: 800; }
    .dashboard-page .date-chip-month { font-size: .7rem; font-weight: 700; margin-top: 2px; }

    @media (max-width: 992px) {
        .dashboard-page .premium-table { min-width: 1000px; }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.nx-search select').forEach(function (el) {
            el.addEventListener('change', function () { this.closest('form').submit(); });
        });

        var modal = document.getElementById('cancelModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                document.getElementById('cancelExamTitle').textContent = button.getAttribute('data-exam-title');
                var form = document.getElementById('cancelForm');
                form.action = button.getAttribute('data-exam-route');
                form.querySelector('textarea').value = '';
            });
        }

        @if (session('success'))
            Swal.fire({ icon: 'success', title: @json(session('success')), timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
        @endif
        @if (session('error'))
            Swal.fire({ icon: 'error', title: @json(session('error')), timer: 4000, toast: true, position: 'top-end' });
        @endif
    });
</script>
@endpush
