@extends('layouts.app')

@section('title', 'Payments History')
@section('page-title', 'Payments History')

@include('reports.partials.styles')

@section('content')
    @php
        $query = $filter->toQuery() + array_filter(['search' => $search, 'status' => $status]);
    @endphp

    <div class="report-page">

        {{-- HERO --}}
        <div class="report-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1">Payments History</h3>
                    <div class="sub">
                        Class fee payments &nbsp;•&nbsp; <i class="bi bi-calendar-event"></i> {{ $filter->dateLabel() }}
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('new-payment.index') }}" class="btn btn-light btn-export">
                        <i class="bi bi-plus-circle text-primary"></i> New Payment
                    </a>
                    <a href="{{ route('payments.export', ['format' => 'excel'] + $query) }}" class="btn btn-light btn-export">
                        <i class="bi bi-file-earmark-spreadsheet text-success"></i> Excel
                    </a>
                    <a href="{{ route('payments.export', ['format' => 'pdf'] + $query) }}" class="btn btn-light btn-export">
                        <i class="bi bi-filetype-pdf text-danger"></i> PDF
                    </a>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success" style="border-radius: 16px;">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" style="border-radius: 16px;">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 16px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- FILTER --}}
        <div class="report-card">
            <form method="GET" action="{{ route('payments.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="report-label">Search</label>
                    <input type="search" name="search" value="{{ $search }}" class="form-control report-input"
                        placeholder="Student ID, name, mobile, receipt, class" maxlength="100">
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
                    <div class="col-lg-2 col-md-4">
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
                    <label class="report-label">Collected by</label>
                    <select name="collected_by" class="form-select report-input">
                        <option value="">Everyone</option>
                        @foreach ($collectors as $collector)
                            <option value="{{ $collector->id }}" {{ (int) $filter->collectedBy === (int) $collector->id ? 'selected' : '' }}>
                                {{ $collector->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="report-label">Method</label>
                    <select name="payment_method" class="form-select report-input">
                        <option value="">All methods</option>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method }}" {{ $filter->paymentMethod === $method ? 'selected' : '' }}>{{ $label($method) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="report-label">Show</label>
                    <select name="status" class="form-select report-input">
                        @foreach ($statuses as $value => $text)
                            <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 report-input">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary report-input" title="Today">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- STATS --}}
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="stat-card stat-green">
                    <div class="label">Collected ({{ $summary['completed'] }} payments)</div>
                    <div class="value">Rs. {{ number_format($summary['amount'], 2) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-blue">
                    <div class="label">Payments in list</div>
                    <div class="value">{{ number_format($summary['total']) }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card stat-red">
                    <div class="label">Deleted in list</div>
                    <div class="value">{{ number_format($summary['deleted']) }}</div>
                </div>
            </div>
        </div>

        {{-- LIST --}}
        <div class="report-card">
            @if ($payments->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-receipt fs-1 d-block mb-2"></i>
                    No payments found for this filter.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table report-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Receipt</th>
                                <th>Paid At</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Fee Month</th>
                                <th>Method</th>
                                <th>Collected By</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Amount</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $row)
                                <tr class="{{ $row['status'] === 'Deleted' ? 'text-muted' : '' }}">
                                    <td class="fw-semibold">{{ $row['receipt_number'] ?? '-' }}</td>
                                    <td class="text-nowrap">{{ $row['paid_at'] ?? '-' }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $row['student_name'] ?? '-' }}</div>
                                        <small class="text-muted">{{ $row['student_code'] }}</small>
                                    </td>
                                    <td>
                                        {{ $row['class_name'] ?? '-' }}
                                        <small class="text-muted d-block">{{ $row['category_name'] }}</small>
                                    </td>
                                    <td class="text-nowrap">{{ $row['fee_month'] ?? '-' }}</td>
                                    <td>{{ $row['payment_method'] }}</td>
                                    <td>{{ $row['collected_by'] ?? '-' }}</td>
                                    <td class="text-center">
                                        @if ($row['status'] === 'Deleted')
                                            <span class="badge bg-danger" title="{{ $row['delete_reason'] }}">Deleted</span>
                                        @elseif ($row['status'] === 'Completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">{{ $row['status'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end amount {{ $row['status'] === 'Deleted' ? 'text-decoration-line-through' : '' }}">
                                        {{ number_format($row['amount'], 2) }}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('payments.show', $row['id']) }}" class="btn btn-sm btn-outline-primary" title="Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if ($row['status'] === 'Completed')
                                            <a href="{{ route('payments.receipt', $row['id']) }}" target="_blank" rel="noopener"
                                                class="btn btn-sm btn-outline-secondary" title="Print receipt">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                    <small class="text-muted">
                        Showing {{ $payments->firstItem() }}-{{ $payments->lastItem() }} of {{ $payments->total() }}
                    </small>
                    {{ $payments->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
