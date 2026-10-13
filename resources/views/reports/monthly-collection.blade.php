@extends('layouts.app')

@section('title', 'Monthly Collection Report')
@section('page-title', 'Monthly Collection Report')

@include('reports.partials.styles')

@section('content')
    @php
        $summary = $report['summary'];
        $query = array_filter(['month' => $report['month'], 'branch_id' => $filter->branchId]);
    @endphp

    <div class="report-page">

        {{-- HERO --}}
        <div class="report-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1">Monthly Collection Report</h3>
                    <div class="sub">
                        Expected vs collected class fees for <strong>{{ $report['month_label'] }}</strong>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('reports.monthly-collection.export', ['format' => 'excel'] + $query) }}"
                        class="btn btn-light btn-export">
                        <i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel
                    </a>
                    <a href="{{ route('reports.monthly-collection.export', ['format' => 'pdf'] + $query) }}"
                        class="btn btn-light btn-export">
                        <i class="bi bi-filetype-pdf text-danger"></i> PDF
                    </a>
                </div>
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
            <form method="GET" action="{{ route('reports.monthly-collection.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-4">
                    <label class="report-label">Fee month</label>
                    <input type="month" name="month" class="form-control report-input" value="{{ $report['month'] }}">
                </div>

                @if ($branches->count() > 1)
                    <div class="col-lg-3 col-md-4">
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

                <div class="col-lg-2 col-md-4">
                    <button type="submit" class="btn btn-primary w-100 report-input">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- STATS --}}
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-blue">
                    <div class="label">Expected ({{ $summary['student_count'] }} enrollments)</div>
                    <div class="value">Rs. {{ number_format($summary['expected'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-green">
                    <div class="label">Collected ({{ $summary['collection_percentage'] }}%)</div>
                    <div class="value">Rs. {{ number_format($summary['collected'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-red">
                    <div class="label">Due ({{ $summary['due_percentage'] }}%)</div>
                    <div class="value">Rs. {{ number_format($summary['due'], 2) }}</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card stat-amber">
                    <div class="label">Students paid</div>
                    <div class="value">{{ $summary['paid_students'] }} / {{ $summary['student_count'] }}</div>
                </div>
            </div>
        </div>

        <div class="report-card">
            <div class="d-flex justify-content-between mb-2">
                <strong>Collection progress</strong>
                <span>{{ $summary['collection_percentage'] }}%</span>
            </div>
            <div class="progress progress-thin mb-3">
                <div class="progress-bar bg-success" style="width: {{ min($summary['collection_percentage'], 100) }}%"></div>
            </div>

            <div class="row text-center g-3">
                <div class="col-md-4">
                    <span class="report-label">Teacher share</span>
                    <strong class="amount">Rs. {{ number_format($summary['teacher_amount'], 2) }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="report-label">Organizer share</span>
                    <strong class="amount">Rs. {{ number_format($summary['organizer_amount'], 2) }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="report-label">Institute share</span>
                    <strong class="amount">Rs. {{ number_format($summary['institution_amount'], 2) }}</strong>
                </div>
            </div>
        </div>

        <div class="report-card">
            <h6 class="mb-3">Class & category breakdown</h6>
            @include('reports.partials.table', ['table' => $table, 'rows' => $report['rows']])
        </div>

    </div>
@endsection
