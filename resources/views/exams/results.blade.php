@extends('layouts.app')

@section('title', 'Exam Results')
@section('page-title', 'Exam Results')

@php
    $studentCode = fn ($s) => $s?->permanent_qr_active ? $s->custom_id : ($s?->temporary_qr_code ?: $s?->custom_id);
    $fmtNum = fn ($n) => $n === null ? '-' : rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $gradeBadge = ['A' => 'nx-badge-green', 'B' => 'nx-badge-blue', 'C' => 'nx-badge-violet', 'S' => 'nx-badge-amber', 'F' => 'nx-badge-red'];
    $gradeBar = ['A' => 'var(--nx-green)', 'B' => 'var(--nx-blue)', 'C' => 'var(--nx-violet)', 'S' => 'var(--nx-amber)', 'F' => 'var(--nx-red)'];
    $maxGradeCount = max(1, max($summary['grades'] ?: [0]));
    $isPublished = $exam->status === 'completed';
@endphp

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">
                    <i class="bi bi-bar-chart-fill"></i> Results
                </span>
                <h1 class="hero-title">{{ $exam->title }}</h1>
                <p class="hero-subtitle">
                    {{ $exam->studentClass->class_name ?? 'N/A' }}
                    - {{ $exam->category->category_name ?? 'N/A' }}
                    - {{ $exam->exam_date->format('d M Y') }}
                </p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="nx-badge {{ $isPublished ? 'nx-badge-green' : 'nx-badge-amber' }}">
                            <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                            {{ $isPublished ? 'Published' : 'Not published' }}
                        </span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $summary['marked'] }}</span>
                        <span class="mini-stat-label">Sat the exam</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value">{{ $summary['absent'] }}</span>
                        <span class="mini-stat-label">Absent</span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('admin.exams.show', $exam->id) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    @if ($exam->status !== 'cancelled')
                        <a href="{{ route('admin.exams.mark-entry', $exam->id) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-pencil"></i> Edit Marks
                        </a>
                    @endif
                    <a href="{{ route('admin.exams.results.excel', $exam->id) }}" class="nx-btn nx-btn-success">
                        <i class="bi bi-file-earmark-excel-fill"></i> Excel
                    </a>
                    <a href="{{ route('admin.exams.results.pdf', $exam->id) }}" class="nx-btn nx-btn-danger">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
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

    @if (!$isPublished && $results->isNotEmpty() && $exam->status !== 'cancelled')
        <div class="nx-alert nx-alert-blue mb-4">
            <i class="bi bi-megaphone-fill"></i>
            <div class="flex-grow-1">
                <strong>Marks are in.</strong>
                Publish to finalise the ranks and mark the exam as completed.
            </div>
            <form method="POST" action="{{ route('admin.exams.recalculate-ranks', $exam->id) }}">
                @csrf
                <button type="submit" class="nx-btn nx-btn-primary">
                    <i class="bi bi-check2-circle"></i> Publish Results
                </button>
            </form>
        </div>
    @endif

    {{-- STATS --}}
    <div class="stats-grid mb-4">
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
            <div class="stat-icon-wrapper icon-red"><i class="bi bi-x-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Failed</span>
                <h2 class="stat-number">{{ $summary['failed'] }}</h2>
                <span class="stat-change down">Below 35% <span class="stat-change-note">of marks</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-graph-up"></i></div>
            <div class="stat-info">
                <span class="stat-label">Average</span>
                <h2 class="stat-number">{{ $summary['average'] !== null ? $fmtNum($summary['average']) : '-' }}</h2>
                <span class="stat-change neutral">Of students <span class="stat-change-note">who sat</span></span>
            </div>
        </div>

        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-trophy-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Highest</span>
                <h2 class="stat-number">{{ $summary['highest'] !== null ? $fmtNum($summary['highest']) : '-' }}</h2>
                <span class="stat-change neutral">Lowest {{ $summary['lowest'] !== null ? $fmtNum($summary['lowest']) : '-' }}</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">

        {{-- RESULTS TABLE --}}
        <div class="col-xl-9">
            <div class="nx-card h-100">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-list-ol text-primary"></i> Rank List</h5>
                        <p class="nx-card-subtitle">Highest marks first. Same marks share a rank.</p>
                    </div>
                    <div class="search-input-wrapper results-filter">
                        <i class="bi bi-search"></i>
                        <input type="text" id="resultFilter" class="form-control custom-input" placeholder="Find a student...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table premium-table mb-0" id="resultsTable">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Rank</th>
                                <th>Student</th>
                                <th class="text-end">Marks</th>
                                <th class="text-end">%</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($results as $result)
                                @php
                                    $s = $result->student;
                                    $g = $result->getRawOriginal('grade');
                                @endphp
                                <tr class="result-row {{ $result->is_absent || $result->status === 'pending' ? 'row-muted' : '' }}"
                                    data-search="{{ strtolower(($s->initial_name ?? '') . ' ' . $studentCode($s)) }}">
                                    <td>
                                        @if ($result->rank)
                                            <span class="rank-chip {{ $result->rank <= 3 ? 'rank-top rank-' . $result->rank : '' }}">{{ $result->rank }}</span>
                                        @else
                                            <span class="cell-sub">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="cell-title">{{ $s->initial_name ?? 'Deleted student' }}</div>
                                        <small class="cell-sub">{{ $studentCode($s) }}</small>
                                    </td>
                                    <td class="text-end cell-title">
                                        @if ($result->is_absent)
                                            -
                                        @else
                                            {{ $fmtNum($result->marks) }}
                                            <small class="cell-sub">/ {{ $fmtNum($result->max_marks) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $result->is_absent || $result->percentage === null ? '-' : $fmtNum($result->percentage) }}</td>
                                    <td>
                                        @if ($g && !$result->is_absent)
                                            <span class="nx-badge {{ $gradeBadge[$g] ?? 'nx-badge-gray' }}">{{ $g }}</span>
                                        @else
                                            <span class="cell-sub">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($result->status)
                                            @case('passed') <span class="nx-badge nx-badge-green">Passed</span> @break
                                            @case('failed') <span class="nx-badge nx-badge-red">Failed</span> @break
                                            @case('absent') <span class="nx-badge nx-badge-amber">Absent</span> @break
                                            @default <span class="nx-badge nx-badge-gray">Not entered</span>
                                        @endswitch
                                    </td>
                                    <td><small class="cell-sub">{{ $result->remark ?: '' }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="empty-table">
                                        <i class="bi bi-clipboard-x fs-2 d-block mb-2"></i>
                                        <strong class="d-block">No marks entered yet</strong>
                                        @if ($exam->status !== 'cancelled')
                                            <a href="{{ route('admin.exams.mark-entry', $exam->id) }}" class="nx-btn nx-btn-primary mt-2">
                                                <i class="bi bi-journal-check"></i> Enter Marks
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- GRADE SPREAD --}}
        <div class="col-xl-3">
            <div class="nx-card h-100">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-bar-chart-steps text-primary"></i> Grades</h5>
                        <p class="nx-card-subtitle">Students per grade</p>
                    </div>
                </div>

                <div class="p-3">
                    @foreach ($summary['grades'] as $g => $count)
                        <div class="grade-row">
                            <span class="nx-badge {{ $gradeBadge[$g] }} grade-letter">{{ $g }}</span>
                            <div class="nx-progress flex-grow-1">
                                <div class="nx-progress-fill" style="width: {{ round($count / $maxGradeCount * 100) }}%; background: {{ $gradeBar[$g] }};"></div>
                            </div>
                            <span class="grade-count">{{ $count }}</span>
                        </div>
                    @endforeach

                    <small class="cell-sub d-block mt-3">A 75+, B 65+, C 50+, S 35+, F below 35</small>

                    @if ($isPublished && $results->isNotEmpty())
                        <form method="POST" action="{{ route('admin.exams.recalculate-ranks', $exam->id) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="nx-btn nx-btn-light w-100">
                                <i class="bi bi-arrow-repeat"></i> Recalculate Ranks
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    .dashboard-page .results-filter { min-width: 240px; }

    .dashboard-page .rank-chip {
        display: inline-grid;
        place-items: center;
        min-width: 32px;
        height: 32px;
        padding: 0 .4rem;
        border-radius: 999px;
        font-weight: 800;
        font-size: .85rem;
        background: var(--nx-head-bg);
        color: var(--nx-text);
    }
    .dashboard-page .rank-top.rank-1 { background: #fde68a; color: #78350f; }
    .dashboard-page .rank-top.rank-2 { background: #e2e8f0; color: #334155; }
    .dashboard-page .rank-top.rank-3 { background: #fed7aa; color: #7c2d12; }

    .dashboard-page .grade-row {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .45rem 0;
    }
    .dashboard-page .grade-letter { min-width: 30px; justify-content: center; }
    .dashboard-page .grade-count { min-width: 24px; text-align: right; font-weight: 700; font-size: .85rem; }

    .dashboard-page .result-row.is-hidden { display: none; }

    .dashboard-page .nx-alert form { margin: 0; }

    @media (max-width: 992px) {
        .dashboard-page #resultsTable { min-width: 760px; }
    }

    @media print {
        .dashboard-page .hero-actions,
        .dashboard-page .results-filter,
        .dashboard-page .nx-alert,
        .dashboard-page form { display: none !important; }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var filter = document.getElementById('resultFilter');
        if (!filter) return;

        var rows = document.querySelectorAll('.result-row');
        filter.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            rows.forEach(function (row) {
                row.classList.toggle('is-hidden', q !== '' && row.dataset.search.indexOf(q) === -1);
            });
        });
    });
</script>
@endpush
