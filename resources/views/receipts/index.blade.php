@extends('layouts.app')

@section('title', 'Receipts - ' . config('app.name', 'EDU NEXORA'))
@section('page-title', 'Receipts')

@php
    $currentStatus = request('status', 'active');

    $typeStyles = [
        'Student Payment' => ['type-student', 'bi-mortarboard'],
        'Admission Payment' => ['type-admission', 'bi-journal-bookmark'],
        'Extra Income' => ['type-extra', 'bi-cash'],
    ];

    $hasFilters = request()->hasAny(['receipt_number', 'type', 'from_date', 'to_date'])
        && collect(request()->only(['receipt_number', 'type', 'from_date', 'to_date']))->filter()->isNotEmpty();
@endphp

@section('content')
    <div class="receipts-page">

        {{-- ===================================================== --}}
        {{-- STATS --}}
        {{-- ===================================================== --}}
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="min-w-0">
                        <h3>Rs. {{ number_format($totalAmount ?? 0, 2) }}</h3>
                        <p>Active Total</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <h3>{{ number_format($activeReceipts ?? 0) }}</h3>
                        <p>Active Receipts</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <div class="min-w-0">
                        <h3>{{ number_format($deletedReceipts ?? 0) }}</h3>
                        <p>Deleted Receipts</p>
                        @if (($deletedAmount ?? 0) > 0)
                            <small class="text-muted">Rs. {{ number_format($deletedAmount, 2) }}</small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon purple">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <div>
                        <h3>{{ number_format($totalReceipts ?? 0) }}</h3>
                        <p>Shown in this list</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- FILTERS --}}
        {{-- ===================================================== --}}
        <div class="filter-card">
            <div class="filter-header">
                <div class="filter-icon">
                    <i class="bi bi-funnel-fill"></i>
                </div>
                <div>
                    <h5>Filter Receipts</h5>
                    <p>Search and filter your receipts</p>
                </div>
            </div>

            <form method="GET" action="{{ route('receipts.index') }}">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label" for="receipt_number">
                            <i class="bi bi-receipt"></i> Receipt Number
                        </label>
                        <input type="text" id="receipt_number" name="receipt_number" class="form-control custom-input"
                            value="{{ request('receipt_number') }}" placeholder="e.g. REC-000123">
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="type">
                            <i class="bi bi-tag"></i> Type
                        </label>
                        <select id="type" name="type" class="form-select custom-input">
                            <option value="">All Types</option>
                            @foreach (array_keys($typeStyles) as $type)
                                <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="status">
                            <i class="bi bi-funnel"></i> Status
                        </label>
                        <select id="status" name="status" class="form-select custom-input">
                            <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="deleted" {{ $currentStatus === 'deleted' ? 'selected' : '' }}>Deleted</option>
                            <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="from_date">
                            <i class="bi bi-calendar"></i> From Date
                        </label>
                        <input type="date" id="from_date" name="from_date" class="form-control custom-input"
                            value="{{ request('from_date') }}">
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="to_date">
                            <i class="bi bi-calendar"></i> To Date
                        </label>
                        <input type="date" id="to_date" name="to_date" class="form-control custom-input"
                            value="{{ request('to_date') }}">
                    </div>

                    <div class="col-lg-1 col-md-12">
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary" title="Search">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="{{ route('receipts.index') }}" class="btn btn-secondary" title="Reset">
                                <i class="bi bi-arrow-repeat"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- ===================================================== --}}
        {{-- RECEIPTS TABLE --}}
        {{-- ===================================================== --}}
        <div class="receipts-card">
            <div class="receipts-header">
                <div>
                    <h4>
                        <i class="bi bi-receipt"></i> Receipt List
                    </h4>
                    <p>
                        @if ($currentStatus === 'deleted')
                            Deleted receipts (audit view)
                        @elseif ($currentStatus === 'all')
                            All receipts, including deleted
                        @else
                            Active payment receipts
                        @endif
                    </p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-print" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>

                    <a href="{{ route('receipts.export.excel', request()->query()) }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>

                    <a href="{{ route('receipts.export.pdf', request()->query()) }}" class="btn btn-danger">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>

            <div class="receipts-body">
                <div class="table-responsive">
                    <table class="receipts-table">
                        <thead>
                            <tr>
                                <th>Receipt No</th>
                                <th>Type</th>
                                <th>Student / Class</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th>Date &amp; Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($receipts as $receipt)
                                @php
                                    $isActive = $receipt['status'] === 'Active';
                                    [$typeClass, $typeIcon] = $typeStyles[$receipt['type']] ?? ['type-default', 'bi-receipt'];
                                    $hasStudent = ($receipt['student_name'] ?? 'N/A') !== 'N/A';
                                    $hasClass = !empty($receipt['class_name']) && $receipt['class_name'] !== 'N/A';
                                @endphp

                                <tr class="{{ $isActive ? '' : 'row-deleted' }}">
                                    <td>
                                        <div class="receipt-number">
                                            <i class="bi bi-receipt-cutoff"></i>

                                            @if ($isActive && $receipt['url'])
                                                <a href="{{ $receipt['url'] }}" class="receipt-link">
                                                    {{ $receipt['receipt_number'] ?: '-' }}
                                                </a>
                                            @else
                                                <span class="fw-bold {{ $isActive ? '' : 'text-danger' }}">
                                                    {{ $receipt['receipt_number'] ?: '-' }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <span class="type-badge {{ $typeClass }}">
                                            <i class="bi {{ $typeIcon }} me-1"></i>
                                            {{ $receipt['type'] }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($hasStudent)
                                            <div class="fw-semibold">{{ $receipt['student_name'] }}</div>
                                            <small class="text-muted">
                                                {{ $receipt['student_id'] }}
                                                @if ($hasClass)
                                                    · {{ $receipt['class_name'] }}
                                                @endif
                                                @if (!empty($receipt['payment_month']))
                                                    · {{ \Carbon\Carbon::parse($receipt['payment_month'])->format('M Y') }}
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="amount-cell text-end">
                                        <span class="currency">Rs.</span>
                                        <span class="amount {{ $isActive ? '' : 'amount-void' }}">
                                            {{ number_format((float) $receipt['amount'], 2) }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($isActive)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">{{ $receipt['status'] }}</span>

                                            @if (!empty($receipt['delete_reason']))
                                                <div class="delete-info">
                                                    {{ $receipt['delete_reason'] }}
                                                    @if (!empty($receipt['deleted_by']))
                                                        <br>by {{ $receipt['deleted_by'] }}
                                                    @endif
                                                    @if (!empty($receipt['deleted_at']))
                                                        · {{ \Carbon\Carbon::parse($receipt['deleted_at'])->format('Y-m-d h:i A') }}
                                                    @endif
                                                </div>
                                            @endif
                                        @endif
                                    </td>

                                    <td>
                                        @if ($receipt['date'])
                                            <div class="date-time-cell">
                                                <div class="date">
                                                    <i class="bi bi-calendar3"></i>
                                                    {{ \Carbon\Carbon::parse($receipt['date'])->format('Y-m-d') }}
                                                </div>
                                                <div class="time">
                                                    <i class="bi bi-clock"></i>
                                                    {{ \Carbon\Carbon::parse($receipt['date'])->format('h:i A') }}
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="action-buttons">
                                            @if ($isActive && $receipt['url'])
                                                <a href="{{ $receipt['url'] }}" class="action-btn view-btn" title="View receipt">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @elseif (!$isActive)
                                                <span class="action-btn locked-btn" title="Deleted receipts cannot be opened">
                                                    <i class="bi bi-slash-circle"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <h5>No Receipts Found</h5>
                                            <p>
                                                @if ($hasFilters || $currentStatus !== 'active')
                                                    No receipts match your search criteria
                                                @else
                                                    No receipts have been issued yet
                                                @endif
                                            </p>
                                            @if ($hasFilters || $currentStatus !== 'active')
                                                <a href="{{ route('receipts.index') }}" class="btn btn-primary mt-2">
                                                    <i class="bi bi-arrow-repeat"></i> Clear Filters
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .receipts-page {
            animation: fadeInUp 0.4s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .min-w-0 { min-width: 0; }

        /* ---------- Stats ---------- */
        .stats-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            height: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f7;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.08);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.6rem;
            flex-shrink: 0;
        }

        .stats-icon.blue { background: linear-gradient(135deg, #2563eb, #3b82f6); }
        .stats-icon.green { background: linear-gradient(135deg, #10b981, #34d399); }
        .stats-icon.purple { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .stats-icon.red { background: linear-gradient(135deg, #ef4444, #f87171); }

        .stats-card h3 {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 700;
            color: #1e293b;
            word-break: break-word;
        }

        .stats-card p {
            margin: 0;
            color: #64748b;
            font-weight: 500;
        }

        /* ---------- Filters ---------- */
        .filter-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f7;
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eef2f7;
        }

        .filter-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filter-icon i { font-size: 1.2rem; color: white; }

        .filter-header h5 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }

        .filter-header p {
            margin: 0;
            font-size: 0.75rem;
            color: #64748b;
        }

        .receipts-page .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .custom-input {
            border-radius: 12px !important;
            border: 1px solid #e2e8f0;
            padding: 0.6rem 1rem;
            transition: all 0.2s ease;
        }

        .custom-input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.8rem;
        }

        .filter-actions .btn {
            flex: 1;
            padding: 0.6rem;
            border-radius: 12px;
            font-weight: 600;
        }

        .receipts-page .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border: none;
        }

        .receipts-page .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: none;
        }

        .receipts-page .btn-secondary:hover { background: #e2e8f0; }

        /* ---------- Table card ---------- */
        .receipts-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f7;
            overflow: hidden;
        }

        .receipts-header {
            padding: 1.2rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            border-bottom: 1px solid #eef2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .receipts-header h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }

        .receipts-header p {
            margin: 4px 0 0;
            font-size: 0.75rem;
            color: #64748b;
        }

        .header-actions {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .header-actions .btn {
            border-radius: 10px;
            font-weight: 600;
        }

        .btn-print {
            background: #f1f5f9;
            color: #475569;
            border: none;
            padding: 0.5rem 1rem;
        }

        .btn-print:hover { background: #e2e8f0; }

        /* ---------- Table ---------- */
        .receipts-table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipts-table thead th {
            background: #f8fafc;
            padding: 1rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            border-bottom: 1px solid #eef2f7;
            white-space: nowrap;
        }

        .receipts-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }

        .receipts-table tbody tr:hover { background: #f8fafc; }

        .receipts-table tbody tr.row-deleted { background: #fef7f7; }

        .receipts-table tbody td {
            padding: 1rem;
            font-size: 0.85rem;
            color: #334155;
            vertical-align: middle;
        }

        .receipt-number {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .receipt-number i { color: #10b981; }

        .receipt-link {
            font-weight: 600;
            color: #4f46e5;
            text-decoration: none;
        }

        .receipt-link:hover { color: #7c3aed; text-decoration: underline; }

        .type-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.8rem;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .type-student { background: #e0e7ff; color: #4338ca; }
        .type-admission { background: #d1fae5; color: #059669; }
        .type-extra { background: #fef3c7; color: #d97706; }
        .type-default { background: #f3f4f6; color: #4b5563; }

        .amount-cell {
            font-weight: 700;
            white-space: nowrap;
        }

        .currency {
            color: #64748b;
            font-size: 0.7rem;
            margin-right: 0.2rem;
        }

        .amount { color: #1e293b; }

        .amount-void {
            color: #94a3b8;
            text-decoration: line-through;
        }

        .delete-info {
            margin-top: 0.35rem;
            max-width: 220px;
            font-size: 0.72rem;
            color: #64748b;
            white-space: normal;
        }

        .date-time-cell {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            white-space: nowrap;
        }

        .date,
        .time {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
        }

        .date i,
        .time i {
            color: #64748b;
            font-size: 0.7rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.4rem;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .view-btn { background: #eff6ff; color: #2563eb; }
        .view-btn:hover { background: #2563eb; color: white; transform: translateY(-2px); }

        .locked-btn { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }

        .empty-state { text-align: center; padding: 3rem; }
        .empty-state i { font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: inline-block; }
        .empty-state h5 { font-weight: 600; color: #64748b; margin-bottom: 0.5rem; }
        .empty-state p { color: #94a3b8; font-size: 0.8rem; }

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {
            .stats-card { padding: 1rem; }
            .stats-icon { width: 45px; height: 45px; font-size: 1.2rem; }
            .stats-card h3 { font-size: 1.2rem; }
            .filter-actions { margin-top: 0; }
            .receipts-header { flex-direction: column; align-items: flex-start; }
            .header-actions { width: 100%; }
            .header-actions .btn { flex: 1; text-align: center; }
        }

        /* ---------- Print ---------- */
        @media print {
            .filter-card,
            .header-actions,
            .action-buttons,
            .sidebar,
            .top-navbar {
                display: none !important;
            }

            .stats-card,
            .receipts-card {
                box-shadow: none;
            }
        }
    </style>
@endpush