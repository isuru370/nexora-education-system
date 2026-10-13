@extends('layouts.app')

@section('title', 'Teacher Collection Report')
@section('page-title', 'Teacher Collection Report')

@include('reports.partials.styles')

@section('content')
    @php
        $query = array_filter($filter->toQuery() + ['teacher_id' => $teacherId]);
    @endphp

    <div class="report-page">

        {{-- HERO --}}
        <div class="report-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1">Teacher Collection Report</h3>
                    <div class="sub">
                        Class fees collected for a teacher's classes
                        &nbsp;•&nbsp; <i class="bi bi-calendar-event"></i> {{ $filter->dateLabel() }}
                    </div>
                </div>
                @if ($report)
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('reports.teacher-collection.export', ['format' => 'excel'] + $query) }}"
                            class="btn btn-light btn-export">
                            <i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel
                        </a>
                        <a href="{{ route('reports.teacher-collection.export', ['format' => 'pdf'] + $query) }}"
                            class="btn btn-light btn-export">
                            <i class="bi bi-filetype-pdf text-danger"></i> PDF
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 16px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- FILTER --}}
        <div class="report-card">
            <form method="GET" action="{{ route('reports.teacher-collection.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="report-label">Teacher</label>
                    <select name="teacher_id" class="form-select report-input" required>
                        <option value="">-- Select teacher --</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ (int) $teacherId === (int) $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->custom_id }} - {{ $teacher->full_name ?: $teacher->initials }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="report-label">From</label>
                    <input type="date" name="from" class="form-control report-input" value="{{ $filter->from->toDateString() }}"
                        max="{{ now()->toDateString() }}">
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="report-label">To</label>
                    <input type="date" name="to" class="form-control report-input" value="{{ $filter->to->toDateString() }}"
                        max="{{ now()->toDateString() }}">
                </div>

                @if ($branches->count() > 1)
                    <div class="col-lg-3 col-md-6">
                        <label class="report-label">Branch</label>
                        <select name="branch_id" class="form-select report-input">
                            <option value="">All branches</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (int) $filter->branchId === (int) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-lg-2 col-md-6">
                    <button type="submit" class="btn btn-primary w-100 report-input">
                        <i class="bi bi-funnel"></i> Show
                    </button>
                </div>
            </form>
        </div>

        @if (!$report)
            <div class="report-card text-center text-muted py-5">
                <i class="bi bi-person-badge fs-1 d-block mb-2"></i>
                Select a teacher and a date range to see the collection.
            </div>
        @else
            @php $summary = $report['summary']; @endphp

            {{-- STATS --}}
            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-blue">
                        <div class="label">Teacher</div>
                        <div class="value" style="font-size: 1.1rem;">
                            {{ $report['teacher']->custom_id }} - {{ $report['teacher']->full_name }}
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-green">
                        <div class="label">Collected ({{ $summary['payments'] }} payments)</div>
                        <div class="value">Rs. {{ number_format($summary['amount'], 2) }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-primary">
                        <div class="label">Teacher share</div>
                        <div class="value">Rs. {{ number_format($summary['teacher_amount'], 2) }}</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card stat-amber">
                        <div class="label">Students / Classes</div>
                        <div class="value">{{ $summary['students'] }} / {{ $summary['classes'] }}</div>
                    </div>
                </div>
            </div>

            {{-- PER CLASS --}}
            <div class="report-card">
                <h6 class="mb-3">By class & category</h6>
                @include('reports.partials.table', [
                    'table' => [
                        'headings' => ['Class', 'Grade', 'Category', 'Students', 'Payments', 'Collected (Rs.)', 'Teacher Share (Rs.)'],
                        'columns' => ['class_name', 'grade_name', 'category_name', 'students', 'payments', 'amount', 'teacher_amount'],
                        'money' => ['amount', 'teacher_amount'],
                        'total' => 'amount',
                    ],
                    'rows' => $report['classes'],
                ])
            </div>

            {{-- PAYMENTS --}}
            <div class="report-card">
                <h6 class="mb-3">Payments</h6>
                @include('reports.partials.table', ['table' => $table, 'rows' => $report['rows']])
            </div>
        @endif

    </div>
@endsection
