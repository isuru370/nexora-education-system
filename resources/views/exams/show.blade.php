@extends('layouts.app')

@section('title', 'Exam Details')
@section('page-title', 'Exam Details')

@php
    $status = $exam->status;
    $statusBadge = [
        'scheduled' => 'nx-badge-blue',
        'ongoing' => 'nx-badge-amber',
        'completed' => 'nx-badge-green',
        'cancelled' => 'nx-badge-red',
    ][$status] ?? 'nx-badge-gray';

    $isClosed = in_array($status, ['completed', 'cancelled'], true);
    $hasResults = $exam->results->isNotEmpty();
    $canEnterMarks = $status !== 'cancelled';
    $canStart = $status === 'scheduled' && $exam->exam_date->lte(today());

    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('h:i A') : '-';
    $studentCode = fn ($s) => $s?->permanent_qr_active ? $s->custom_id : ($s?->temporary_qr_code ?: $s?->custom_id);
    $resultsByStudent = $exam->results->keyBy('student_id');
@endphp

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">
                    <i class="bi bi-journal-bookmark-fill"></i> Exam
                </span>
                <h1 class="hero-title">{{ $exam->title }}</h1>
                <p class="hero-subtitle">
                    {{ $exam->studentClass->class_name ?? 'N/A' }}
                    @if ($exam->studentClass?->grade)
                        (Grade {{ $exam->studentClass->grade->grade_name }})
                    @endif
                    - {{ $exam->category->category_name ?? 'N/A' }}
                </p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="nx-badge {{ $statusBadge }}">
                            <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                            {{ ucfirst($status) }}
                        </span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $exam->exam_date->format('d M Y') }}</span>
                        <span class="mini-stat-label">
                            {{ $exam->exam_date->isToday() ? 'Today' : $exam->exam_date->diffForHumans(today()->startOfDay(), \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW) }}
                        </span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $fmtTime($exam->start_time) }} - {{ $fmtTime($exam->end_time) }}</span>
                        <span class="mini-stat-label">{{ $exam->hall->hall_name ?? 'No hall' }}</span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('admin.exams.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    @unless ($isClosed)
                        <a href="{{ route('admin.exams.edit', $exam->id) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                    @endunless
                    @if ($canEnterMarks)
                        <a href="{{ route('admin.exams.mark-entry', $exam->id) }}" class="nx-btn nx-btn-primary">
                            <i class="bi bi-journal-check"></i> Enter Marks
                        </a>
                    @endif
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if (session('success'))
        <div class="nx-alert nx-alert-green mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($status === 'cancelled')
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-x-octagon-fill"></i>
            <div>
                <strong>This exam was cancelled</strong>
                @if ($exam->cancelled_at)
                    on {{ $exam->cancelled_at->format('d M Y, h:i A') }}
                @endif
                @if ($exam->cancelledBy)
                    by {{ $exam->cancelledBy->name }}
                @endif
                @if ($exam->cancel_reason)
                    <div class="mt-1">Reason: {{ $exam->cancel_reason }}</div>
                @endif
            </div>
        </div>
    @elseif ($canStart)
        <div class="nx-alert nx-alert-amber mb-4">
            <i class="bi bi-play-circle-fill"></i>
            <div class="flex-grow-1">
                <strong>The exam date has arrived.</strong>
                Mark it as ongoing when students sit the paper.
            </div>
            <form method="POST" action="{{ route('admin.exams.update-status', $exam->id) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="ongoing">
                <button type="submit" class="nx-btn nx-btn-amber">
                    <i class="bi bi-play-fill"></i> Mark as Ongoing
                </button>
            </form>
        </div>
    @endif

    {{-- STATS --}}
    <div class="stats-grid mb-4">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Students</span>
                <h2 class="stat-number">{{ $students->count() }}</h2>
                <span class="stat-change neutral">In this class <span class="stat-change-note">and category</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-journal-check"></i></div>
            <div class="stat-info">
                <span class="stat-label">Marks Entered</span>
                <h2 class="stat-number">{{ $summary['marked'] + $summary['absent'] }}</h2>
                <span class="stat-change {{ $students->count() && ($summary['marked'] + $summary['absent']) >= $students->count() ? 'up' : 'neutral' }}">
                    of {{ $students->count() }} <span class="stat-change-note">students</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Passed</span>
                <h2 class="stat-number">{{ $summary['passed'] }}</h2>
                <span class="stat-change up">
                    {{ $summary['pass_rate'] !== null ? $summary['pass_rate'] . '%' : '-' }}
                    <span class="stat-change-note">pass rate</span>
                </span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-bar-chart-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Average</span>
                <h2 class="stat-number">{{ $summary['average'] ?? '-' }}</h2>
                <span class="stat-change neutral">
                    High {{ $summary['highest'] ?? '-' }} <span class="stat-change-note">/ Low {{ $summary['lowest'] ?? '-' }}</span>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">

        {{-- EXAM INFO --}}
        <div class="col-lg-5">
            <div class="nx-card h-100">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-info-circle-fill text-primary"></i> Exam Information</h5>
                        <p class="nx-card-subtitle">Class, time and place</p>
                    </div>
                </div>

                <div class="p-3">
                    <div class="detail-line">
                        <span class="detail-label">Class</span>
                        <span class="detail-value">{{ $exam->studentClass->class_name ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Grade / Subject</span>
                        <span class="detail-value">
                            {{ $exam->studentClass->grade->grade_name ?? '-' }}
                            / {{ $exam->studentClass->subject->subject_name ?? '-' }}
                        </span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Category</span>
                        <span class="detail-value">
                            <span class="nx-badge nx-badge-blue">{{ $exam->category->category_name ?? 'N/A' }}</span>
                        </span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Date</span>
                        <span class="detail-value">{{ $exam->exam_date->format('l, d M Y') }}</span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Time</span>
                        <span class="detail-value">{{ $fmtTime($exam->start_time) }} - {{ $fmtTime($exam->end_time) }}</span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Hall</span>
                        <span class="detail-value">{{ $exam->hall->hall_name ?? '-' }}</span>
                    </div>
                    @if ($exam->note)
                        <div class="detail-line">
                            <span class="detail-label">Note</span>
                            <span class="detail-value">{{ $exam->note }}</span>
                        </div>
                    @endif
                    <div class="detail-line">
                        <span class="detail-label">Created</span>
                        <span class="detail-value">{{ $exam->created_at?->format('d M Y, h:i A') }}</span>
                    </div>
                </div>

                {{-- STATUS / OTHER ACTIONS --}}
                <div class="p-3 border-top d-flex flex-wrap gap-2">
                    @if ($hasResults)
                        <a href="{{ route('admin.exams.results', $exam->id) }}" class="nx-btn nx-btn-success">
                            <i class="bi bi-bar-chart-fill"></i> View Results
                        </a>
                    @endif

                    @if (in_array($status, ['scheduled', 'ongoing'], true))
                        <button type="button" class="nx-btn nx-btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle"></i> Cancel Exam
                        </button>
                    @endif

                    @if (!$hasResults && $status !== 'ongoing')
                        <form method="POST" action="{{ route('admin.exams.destroy', $exam->id) }}" class="js-delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nx-btn nx-btn-light">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- STUDENTS --}}
        <div class="col-lg-7">
            <div class="nx-card h-100">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-people-fill text-primary"></i> Students</h5>
                        <p class="nx-card-subtitle">Active students in {{ $exam->studentClass->class_name ?? 'this class' }} - {{ $exam->category->category_name ?? '' }}</p>
                    </div>
                    @if ($canEnterMarks && $students->isNotEmpty())
                        <a href="{{ route('admin.exams.mark-entry', $exam->id) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-pencil"></i> Enter Marks
                        </a>
                    @endif
                </div>

                <div class="table-responsive students-scroll">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th class="text-end">Marks</th>
                                <th>Grade</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $enrollment)
                                @php
                                    $s = $enrollment->student;
                                    $r = $resultsByStudent->get($s->id);
                                @endphp
                                <tr>
                                    <td class="row-number">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="cell-title">{{ $s->initial_name }}</div>
                                        <small class="cell-sub">{{ $studentCode($s) }}</small>
                                    </td>
                                    <td class="text-end cell-title">
                                        @if ($r && $r->is_absent)
                                            -
                                        @elseif ($r && $r->marks !== null)
                                            {{ rtrim(rtrim(number_format((float) $r->marks, 2), '0'), '.') }}
                                            <small class="cell-sub">/ {{ rtrim(rtrim(number_format((float) $r->max_marks, 2), '0'), '.') }}</small>
                                        @else
                                            <span class="cell-sub">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $r && $r->marks !== null ? $r->grade : '-' }}</td>
                                    <td>
                                        @if (!$r || $r->status === 'pending')
                                            <span class="nx-badge nx-badge-gray">Not entered</span>
                                        @elseif ($r->is_absent)
                                            <span class="nx-badge nx-badge-amber">Absent</span>
                                        @elseif ($r->status === 'passed')
                                            <span class="nx-badge nx-badge-green">Passed</span>
                                        @else
                                            <span class="nx-badge nx-badge-red">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="empty-table">
                                        <i class="bi bi-people fs-2 d-block mb-2"></i>
                                        <strong class="d-block">No students in this class and category</strong>
                                        <span class="cell-sub">Add students to the class first, then enter marks.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- CANCEL MODAL --}}
@if (in_array($status, ['scheduled', 'ongoing'], true))
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.exams.cancel', $exam->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelModalLabel">
                            <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Cancel Exam
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Cancel <strong>{{ $exam->title }}</strong>?</p>
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
@endif
@endsection


@push('styles')
<style>
    .dashboard-page .detail-line {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: .7rem 0;
        border-bottom: 1px solid var(--nx-border);
        gap: 1rem;
    }
    .dashboard-page .detail-line:last-child { border-bottom: 0; }

    .dashboard-page .detail-label {
        flex-shrink: 0;
        font-size: .72rem;
        font-weight: 700;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .dashboard-page .detail-value {
        flex: 1;
        text-align: right;
        font-size: .85rem;
        font-weight: 600;
        color: var(--nx-text);
        word-break: break-word;
    }

    .dashboard-page .students-scroll { max-height: 520px; overflow-y: auto; }
    .dashboard-page .students-scroll thead th { position: sticky; top: 0; z-index: 1; }

    .dashboard-page .nx-alert form { margin: 0; }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-delete-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Delete this exam?',
                    text: 'This cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#b91c1c',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Delete',
                    reverseButtons: true
                }).then(function (r) { if (r.isConfirmed) form.submit(); });
            });
        });
    });
</script>
@endpush
