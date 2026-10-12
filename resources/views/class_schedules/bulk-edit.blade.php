@extends('layouts.app')

@section('title', 'Bulk Update Schedule')
@section('page-title', 'Bulk Update Schedule')

@section('content')

    <div class="container-fluid">

        {{-- Header --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center flex-wrap">

                    <div>
                        <h4 class="fw-bold mb-1">
                            {{ $studentClass->class_name ?? 'Class' }}
                        </h4>

                        <div class="text-muted">

                            <span class="me-3">
                                <strong>Teacher :</strong>
                                {{ optional($studentClass->teacher)->full_name ?? 'N/A' }}
                            </span>

                            <span class="me-3">
                                <strong>Subject :</strong>
                                {{ optional($studentClass->subject)->subject_name ?? 'N/A' }}
                            </span>

                            <span class="me-3">
                                <strong>Grade :</strong>
                                {{ optional($studentClass->grade)->grade_name ?? 'N/A' }}
                            </span>

                        </div>

                    </div>

                    <a href="{{ route('class-schedules.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i>
                        Back
                    </a>

                </div>

            </div>
        </div>


        {{-- Category Info --}}
        <div class="card shadow-sm border-0 mb-4">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6">

                        <h6 class="text-muted">
                            Category
                        </h6>

                        <h5>
                            {{ optional($categoryFee->category)->category_name ?? 'N/A' }}

                            @if(optional($categoryFee->category)->code)
                                <span class="badge bg-secondary ms-2">
                                    {{ $categoryFee->category->code }}
                                </span>
                            @endif
                        </h5>

                    </div>

                    <div class="col-md-6">

                        <h6 class="text-muted">
                            Fee Options
                        </h6>

                        @if($categoryFee->activeFeeOptions->count())

                            @foreach($categoryFee->activeFeeOptions as $option)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-1">

                                    <div>
                                        <span class="fw-semibold">{{ $option->label }}</span>

                                        @if($option->is_default)
                                            <span class="badge bg-success ms-1">Default</span>
                                        @endif

                                        @if($option->note)
                                            <small class="text-muted d-block">
                                                {{ $option->note }}
                                            </small>
                                        @endif
                                    </div>

                                    <div class="fw-bold text-primary">
                                        Rs. {{ number_format($option->fee, 2) }}
                                    </div>

                                </div>
                            @endforeach

                        @else
                            <div class="text-muted small">
                                No active fee options
                            </div>
                        @endif

                    </div>

                </div>

            </div>

        </div>


        {{-- Pattern List --}}
        <div class="card shadow-sm border-0">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    Schedule Patterns
                </h5>

                <span class="badge bg-primary">
                    {{ $patterns->count() }} pattern(s)
                </span>

            </div>

            <div class="card-body p-0">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>Day</th>

                            <th>Time</th>

                            <th>Hall</th>

                            <th>Start Date</th>

                            <th>End Date</th>

                            <th>Status</th>

                            <th width="120">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($patterns as $pattern)
                            <tr>

                                <td class="text-capitalize">
                                    <span class="badge bg-info text-dark">
                                        {{ $pattern->class_day ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    @if($pattern->start_time && $pattern->end_time)
                                        {{ \Carbon\Carbon::parse($pattern->start_time)->format('h:i A') }}
                                        -
                                        {{ \Carbon\Carbon::parse($pattern->end_time)->format('h:i A') }}
                                    @else
                                        N/A
                                    @endif
                                </td>

                                <td>
                                    {{ optional($pattern->hall)->hall_name ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ $pattern->start_date ? \Carbon\Carbon::parse($pattern->start_date)->format('Y-m-d') : 'N/A' }}
                                </td>

                                <td>
                                    {{ $pattern->end_date ? \Carbon\Carbon::parse($pattern->end_date)->format('Y-m-d') : 'N/A' }}
                                </td>

                                <td>
                                    @if($pattern->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>

                                <td>

                                    <a href="{{ route('class-schedules.editBulkSchedule', $pattern->id) }}"
                                        class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                        Edit
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7" class="text-center py-5">

                                    <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>

                                    <div class="fw-semibold">
                                        No Schedule Pattern Found
                                    </div>

                                    <div class="text-muted small mt-1">
                                        Create a recurring schedule pattern first
                                    </div>

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection