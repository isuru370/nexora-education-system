@extends('layouts.app')

@section('title', 'Payments History')
@section('page-title', 'Payments History')

@section('content')
    @php
        $query = $filter->toQuery() + array_filter(['search' => $search, 'status' => $status]);
    @endphp

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Payments History</h1>
                    <p class="hero-subtitle">Class fee payments • {{ $filter->dateLabel() }}</p>
                </div>
                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('new-payment.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-plus-circle"></i> New Payment
                        </a>
                        <a href="{{ route('payments.export', ['format' => 'excel'] + $query) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                        </a>
                        <a href="{{ route('payments.export', ['format' => 'pdf'] + $query) }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-filetype-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('success'))
            <div class="nx-alert nx-alert-green mb-4"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
        @endif
        @if (session('error'))
            <div class="nx-alert nx-alert-red mb-4"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="nx-alert nx-alert-red mb-4">
                <div>
                    @foreach ($errors->all() as $error)
                        <div><i class="bi bi-exclamation-circle-fill me-1"></i>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- FILTER --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Filters</h5>
                    <p class="nx-card-subtitle">Narrow down payments by date, branch, collector or method</p>
                </div>
            </div>
            <div class="p-3">
                <form method="GET" action="{{ route('payments.index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Search</label>
                        <input type="search" name="search" value="{{ $search }}" class="form-control nx-input"
                            placeholder="Student ID, name, mobile, receipt, class" maxlength="100">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="nx-label">From</label>
                        <input type="date" name="from" class="form-control nx-input"
                            value="{{ $filter->from->toDateString() }}" max="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="nx-label">To</label>
                        <input type="date" name="to" class="form-control nx-input"
                            value="{{ $filter->to->toDateString() }}" max="{{ now()->toDateString() }}">
                    </div>
                    @if ($branches->count() > 1)
                        <div class="col-lg-2 col-md-4">
                            <label class="nx-label">Branch</label>
                            <select name="branch_id" class="form-select nx-input">
                                <option value="">All branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (int) $filter->branchId === (int) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-lg-2 col-md-4">
                        <label class="nx-label">Collected by</label>
                        <select name="collected_by" class="form-select nx-input">
                            <option value="">Everyone</option>
                            @foreach ($collectors as $collector)
                                <option value="{{ $collector->id }}" {{ (int) $filter->collectedBy === (int) $collector->id ? 'selected' : '' }}>{{ $collector->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="nx-label">Method</label>
                        <select name="payment_method" class="form-select nx-input">
                            <option value="">All methods</option>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method }}" {{ $filter->paymentMethod === $method ? 'selected' : '' }}>{{ $label($method) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="nx-label">Show</label>
                        <select name="status" class="form-select nx-input">
                            @foreach ($statuses as $value => $text)
                                <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 d-flex gap-2">
                        <button type="submit" class="nx-btn nx-btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                        <a href="{{ route('payments.index') }}" class="nx-btn nx-btn-light" title="Today"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>

        {{-- STATS --}}
        <div class="stats-grid stats-grid-3 mb-4">
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Collected · {{ $summary['completed'] }} payments</span>
                    <h2 class="stat-number">Rs. {{ number_format($summary['amount'], 2) }}</h2>
                    <span class="stat-change up">Completed <span class="stat-change-note">payments</span></span>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-receipt"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Payments in List</span>
                    <h2 class="stat-number">{{ number_format($summary['total']) }}</h2>
                    <span class="stat-change neutral">Matching <span class="stat-change-note">filters</span></span>
                </div>
            </div>
            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-trash-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Deleted in List</span>
                    <h2 class="stat-number">{{ number_format($summary['deleted']) }}</h2>
                    <span class="stat-change down">Removed <span class="stat-change-note">payments</span></span>
                </div>
            </div>
        </div>

        {{-- LIST --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-receipt text-primary"></i> Payment Records</h5>
                    <p class="nx-card-subtitle">Showing {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }}</p>
                </div>
            </div>

            @if ($payments->isEmpty())
                <div class="empty-table">
                    <i class="bi bi-receipt fs-2 d-block mb-2"></i>
                    <strong class="d-block">No payments found</strong>
                    <span class="cell-sub">Try adjusting your filters</span>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
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
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $row)
                                <tr class="{{ $row['status'] === 'Deleted' ? 'row-muted' : '' }}">
                                    <td class="cell-title">{{ $row['receipt_number'] ?? '-' }}</td>
                                    <td class="text-nowrap cell-sub">{{ $row['paid_at'] ?? '-' }}</td>
                                    <td>
                                        <div class="cell-title">{{ $row['student_name'] ?? '-' }}</div>
                                        <small class="cell-sub">{{ $row['student_code'] }}</small>
                                    </td>
                                    <td>
                                        <div class="cell-title">{{ $row['class_name'] ?? '-' }}</div>
                                        <small class="cell-sub">{{ $row['category_name'] }}</small>
                                    </td>
                                    <td class="text-nowrap cell-sub">{{ $row['fee_month'] ?? '-' }}</td>
                                    <td class="cell-sub">{{ $row['payment_method'] }}</td>
                                    <td class="cell-sub">{{ $row['collected_by'] ?? '-' }}</td>
                                    <td class="text-center">
                                        @if ($row['status'] === 'Deleted')
                                            <span class="nx-badge nx-badge-red" title="{{ $row['delete_reason'] }}">Deleted</span>
                                        @elseif ($row['status'] === 'Completed')
                                            <span class="nx-badge nx-badge-green">Completed</span>
                                        @else
                                            <span class="nx-badge nx-badge-amber">{{ $row['status'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end amount {{ $row['status'] === 'Deleted' ? 'text-decoration-line-through' : '' }}">
                                        {{ number_format($row['amount'], 2) }}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="action-buttons">
                                            <a href="{{ route('payments.show', $row['id']) }}" class="action-btn" title="Details" aria-label="Details">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                            @if ($row['status'] === 'Completed')
                                                <a href="{{ route('payments.receipt', $row['id']) }}" target="_blank" rel="noopener"
                                                    class="action-btn" title="Print receipt" aria-label="Print receipt">
                                                    <i class="bi bi-printer-fill"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Payments History — page-specific styles only.
       ============================================================ */

    .dashboard-page .amount { font-weight: 600; }
</style>
@endpush