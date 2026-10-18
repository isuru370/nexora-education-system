@extends('layouts.app')

@section('title', 'Mark Entry')
@section('page-title', 'Mark Entry')

@php
    $resultsByStudent = $exam->results->keyBy('student_id');
    $studentCode = fn ($s) => $s?->permanent_qr_active ? $s->custom_id : ($s?->temporary_qr_code ?: $s?->custom_id);
    $fmtNum = fn ($n) => $n === null ? '' : rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    $maxMarksValue = old('max_marks', $fmtNum($maxMarks ?? 100));
@endphp

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">
                    <i class="bi bi-journal-check"></i> Mark Entry
                </span>
                <h1 class="hero-title">{{ $exam->title }}</h1>
                <p class="hero-subtitle">
                    {{ $exam->studentClass->class_name ?? 'N/A' }}
                    - {{ $exam->category->category_name ?? 'N/A' }}
                    - {{ $exam->exam_date->format('d M Y') }}
                </p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="mini-stat-value" id="countEntered">0</span>
                        <span class="mini-stat-label">Entered</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value" id="countAbsent">0</span>
                        <span class="mini-stat-label">Absent</span>
                    </div>
                    <div class="mini-stat-divider"></div>
                    <div class="mini-stat">
                        <span class="mini-stat-value" id="countPending">{{ $students->count() }}</span>
                        <span class="mini-stat-label">Not entered</span>
                    </div>
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('admin.exams.show', $exam->id) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    @if ($exam->results->isNotEmpty())
                        <a href="{{ route('admin.exams.results', $exam->id) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-bar-chart-fill"></i> Results
                        </a>
                    @endif
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if (session('error'))
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Marks were not saved.</strong> Fix the highlighted rows and save again.
                <ul class="mb-0 mt-1 ps-3">
                    @foreach (collect($errors->all())->unique()->take(5) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if ($exam->status === 'completed')
        <div class="nx-alert nx-alert-amber mb-4">
            <i class="bi bi-info-circle-fill"></i>
            <span>Results for this exam are already published. Saving again updates the marks and ranks.</span>
        </div>
    @endif

    <form action="{{ route('admin.exams.save-marks', $exam->id) }}" method="POST" id="markEntryForm" novalidate>
        @csrf

        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-people-fill text-primary"></i> Students</h5>
                    <p class="nx-card-subtitle">Leave marks empty if not entered yet. Press Enter to go to the next student.</p>
                </div>

                <div class="max-marks-box">
                    <label for="maxMarks" class="nx-label mb-0">Out of</label>
                    <input type="number" id="maxMarks" name="max_marks" min="1" max="1000" step="0.5" required
                        class="form-control custom-input @error('max_marks') is-invalid @enderror"
                        value="{{ $maxMarksValue }}">
                </div>
            </div>

            @if ($students->isEmpty())
                <div class="empty-table">
                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                    <strong class="d-block">No students in this class and category</strong>
                    <span class="cell-sub">Add students to {{ $exam->studentClass->class_name ?? 'the class' }} first.</span>
                </div>
            @else
                <div class="nx-search py-2">
                    <div class="search-input-wrapper">
                        <i class="bi bi-search"></i>
                        <input type="text" id="studentFilter" class="form-control custom-input"
                            placeholder="Find a student by name or ID...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table premium-table mb-0" id="marksTable">
                        <thead>
                            <tr>
                                <th style="width: 48px;">#</th>
                                <th>Student</th>
                                <th style="width: 150px;">Marks</th>
                                <th style="width: 110px;">Absent</th>
                                <th style="width: 120px;">Result</th>
                                <th>Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $enrollment)
                                @php
                                    $s = $enrollment->student;
                                    $r = $resultsByStudent->get($s->id);
                                    $markVal = old("marks.{$s->id}.marks", $r && !$r->is_absent ? $fmtNum($r->marks) : '');
                                    $absentVal = (bool) old("marks.{$s->id}.is_absent", $r?->is_absent ?? false);
                                    $remarkVal = old("marks.{$s->id}.remark", $r?->remark);
                                    $rowError = $errors->first("marks.{$s->id}.marks");
                                @endphp
                                <tr class="mark-row {{ $absentVal ? 'row-muted' : '' }}"
                                    data-search="{{ strtolower($s->initial_name . ' ' . $studentCode($s)) }}">
                                    <td class="row-number">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="cell-title">{{ $s->initial_name }}</div>
                                        <small class="cell-sub">{{ $studentCode($s) }}</small>
                                    </td>
                                    <td>
                                        <input type="number" inputmode="decimal" step="0.01" min="0"
                                            name="marks[{{ $s->id }}][marks]"
                                            class="form-control custom-input mark-input {{ $rowError ? 'is-invalid' : '' }}"
                                            value="{{ $markVal }}" {{ $absentVal ? 'disabled' : '' }}
                                            aria-label="Marks for {{ $s->initial_name }}">
                                        @if ($rowError)
                                            <div class="invalid-feedback d-block">{{ $rowError }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="hidden" name="marks[{{ $s->id }}][is_absent]" value="0">
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input absent-toggle" type="checkbox" role="switch"
                                                name="marks[{{ $s->id }}][is_absent]" value="1"
                                                id="absent{{ $s->id }}" {{ $absentVal ? 'checked' : '' }}>
                                            <label class="form-check-label cell-sub" for="absent{{ $s->id }}">Absent</label>
                                        </div>
                                    </td>
                                    <td class="result-cell"><span class="nx-badge nx-badge-gray">-</span></td>
                                    <td>
                                        <input type="text" maxlength="255" name="marks[{{ $s->id }}][remark]"
                                            class="form-control custom-input remark-input" value="{{ $remarkVal }}"
                                            placeholder="Optional" aria-label="Remark for {{ $s->initial_name }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <small class="cell-sub">
                        Grades: A 75+, B 65+, C 50+, S 35+, F below 35. Ranks are worked out when you save.
                    </small>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.exams.show', $exam->id) }}" class="nx-btn nx-btn-light">Cancel</a>
                        <button type="submit" class="nx-btn nx-btn-primary" id="saveBtn">
                            <i class="bi bi-save"></i> Save Marks
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </form>

</div>
@endsection


@push('styles')
<style>
    .dashboard-page .max-marks-box {
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .dashboard-page .max-marks-box .custom-input { width: 110px; font-weight: 700; }

    .dashboard-page .mark-input { font-weight: 700; max-width: 130px; }
    .dashboard-page .remark-input { min-width: 160px; }

    .dashboard-page .premium-table tbody tr.row-muted .mark-input { background: transparent; }

    .dashboard-page .mark-row.is-hidden { display: none; }

    @media (max-width: 992px) {
        .dashboard-page #marksTable { min-width: 820px; }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        'use strict';

        var form = document.getElementById('markEntryForm');
        var maxInput = document.getElementById('maxMarks');
        var rows = Array.prototype.slice.call(document.querySelectorAll('.mark-row'));
        if (!form || !rows.length) return;

        var dirty = false;

        function grade(pct) {
            if (pct >= 75) return 'A';
            if (pct >= 65) return 'B';
            if (pct >= 50) return 'C';
            if (pct >= 35) return 'S';
            return 'F';
        }

        function refreshRow(row) {
            var input = row.querySelector('.mark-input');
            var absent = row.querySelector('.absent-toggle').checked;
            var cell = row.querySelector('.result-cell');
            var max = parseFloat(maxInput.value) || 0;

            input.disabled = absent;
            row.classList.toggle('row-muted', absent);
            input.classList.remove('is-invalid');

            if (absent) {
                cell.innerHTML = '<span class="nx-badge nx-badge-amber">Absent</span>';
                return 'absent';
            }

            if (input.value === '') {
                cell.innerHTML = '<span class="nx-badge nx-badge-gray">-</span>';
                return 'pending';
            }

            var mark = parseFloat(input.value);
            if (isNaN(mark) || mark < 0 || (max > 0 && mark > max)) {
                input.classList.add('is-invalid');
                cell.innerHTML = '<span class="nx-badge nx-badge-red">Check</span>';
                return 'invalid';
            }

            var pct = max > 0 ? (mark / max) * 100 : 0;
            var g = grade(pct);
            var cls = pct >= 35 ? 'nx-badge-green' : 'nx-badge-red';
            cell.innerHTML = '<span class="nx-badge ' + cls + '">' + g + ' &middot; ' + Math.round(pct) + '%</span>';
            return 'entered';
        }

        function refreshAll() {
            var counts = { entered: 0, absent: 0, pending: 0, invalid: 0 };
            rows.forEach(function (row) { counts[refreshRow(row)]++; });
            document.getElementById('countEntered').textContent = counts.entered;
            document.getElementById('countAbsent').textContent = counts.absent;
            document.getElementById('countPending').textContent = counts.pending;
            return counts;
        }

        rows.forEach(function (row, index) {
            var input = row.querySelector('.mark-input');

            input.addEventListener('input', function () { dirty = true; refreshAll(); });
            row.querySelector('.absent-toggle').addEventListener('change', function () { dirty = true; refreshAll(); });
            row.querySelector('.remark-input').addEventListener('input', function () { dirty = true; });

            // Enter -> next student's marks
            input.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                for (var i = index + 1; i < rows.length; i++) {
                    var next = rows[i].querySelector('.mark-input');
                    if (!next.disabled && !rows[i].classList.contains('is-hidden')) { next.focus(); next.select(); return; }
                }
                document.getElementById('saveBtn').focus();
            });
        });

        maxInput.addEventListener('input', refreshAll);

        // Find a student
        var filter = document.getElementById('studentFilter');
        if (filter) {
            filter.addEventListener('input', function () {
                var q = this.value.trim().toLowerCase();
                rows.forEach(function (row) {
                    row.classList.toggle('is-hidden', q !== '' && row.dataset.search.indexOf(q) === -1);
                });
            });
        }

        form.addEventListener('submit', function (e) {
            var counts = refreshAll();
            if (counts.invalid > 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Check the marks',
                    text: counts.invalid + ' row(s) have marks below 0 or above ' + maxInput.value + '.'
                });
                return;
            }
            dirty = false;
            var btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
        });

        window.addEventListener('beforeunload', function (e) {
            if (!dirty) return;
            e.preventDefault();
            e.returnValue = '';
        });

        refreshAll();
        var first = rows[0].querySelector('.mark-input:not([disabled])');
        if (first) first.focus();
    });
</script>
@endpush
