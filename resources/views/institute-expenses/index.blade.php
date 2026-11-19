@extends('layouts.app')

@section('title', 'Institute Expenses')
@section('page-title', 'Institute Expenses')

@push('styles')
<style>
    .expenses-page {
        animation: fadeIn .4s ease;
    }

    /* =========================
       HEADER
    ========================= */
    .header-card {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        border-radius: 28px;
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .header-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 50%;
    }

    .header-card::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -5%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.02);
        border-radius: 50%;
    }

    .header-title {
        font-size: 1.75rem;
        font-weight: 800;
        margin-bottom: .25rem;
    }

    .header-subtitle {
        color: #94a3b8;
        margin-bottom: 0;
    }

    .stat-badge {
        background: rgba(255, 255, 255, .1);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: .75rem 1.25rem;
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .stat-badge .label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        opacity: .7;
    }

    .stat-badge .value {
        font-size: 1.15rem;
        font-weight: 700;
        margin-top: .2rem;
    }

    /* =========================
       FILTER
    ========================= */
    .filter-card {
        background: #fff;
        border-radius: 24px;
        border: 1px solid #eef2f7;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .02);
    }

    .filter-title {
        font-size: .9rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1rem;
        padding-bottom: .5rem;
        border-bottom: 2px solid #eef2f7;
    }

    .filter-title i {
        color: #2563eb;
        margin-right: .5rem;
    }

    .filter-label {
        font-size: .7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        margin-bottom: .3rem;
    }

    .filter-input {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: .6rem .8rem;
        font-size: .85rem;
        width: 100%;
        transition: all .2s ease;
        background: #fff;
    }

    .filter-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        outline: none;
    }

    .btn-filter,
    .btn-reset {
        border: none;
        border-radius: 12px;
        padding: .6rem 1rem;
        font-weight: 600;
        width: 100%;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        transition: all .2s ease;
    }

    .btn-filter {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
    }

    .btn-filter:hover {
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(37, 99, 235, .25);
    }

    .btn-reset {
        background: #64748b;
        color: white;
    }

    .btn-reset:hover {
        background: #475569;
        color: white;
        transform: translateY(-2px);
    }

    /* =========================
       TABLE
    ========================= */
    .table-card {
        background: #fff;
        border-radius: 24px;
        border: 1px solid #eef2f7;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .02);
    }

    .table-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .table-title {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .table-title i {
        color: #2563eb;
        margin-right: .5rem;
    }

    .result-count {
        font-size: .8rem;
        color: #64748b;
    }

    .expense-table {
        width: 100%;
        border-collapse: collapse;
    }

    .expense-table thead th {
        background: #f8fafc;
        padding: 1rem;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .expense-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: .85rem;
    }

    .expense-table tbody tr:hover {
        background: #f8fafc;
    }

    .amount-expense {
        color: #dc2626;
        font-weight: 700;
        font-family: monospace;
        white-space: nowrap;
    }

    /* =========================
       BADGES
    ========================= */
    .badge-current {
        background: #dbeafe;
        color: #1e40af;
        padding: .2rem .55rem;
        border-radius: 12px;
        font-size: .62rem;
        font-weight: 600;
    }

    .badge-past {
        background: #f1f5f9;
        color: #64748b;
        padding: .2rem .55rem;
        border-radius: 12px;
        font-size: .62rem;
        font-weight: 600;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .3rem .65rem;
        border-radius: 20px;
        font-size: .68rem;
        font-weight: 700;
    }

    .status-paid {
        background: #dcfce7;
        color: #166534;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-approved {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }

    /* =========================
       ACTION BUTTONS
    ========================= */
    .action-btn {
        border: none;
        border-radius: 8px;
        padding: .38rem .55rem;
        font-size: .72rem;
        font-weight: 600;
        color: white;
        transition: all .2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .action-btn:hover {
        color: white;
        transform: translateY(-1px);
    }

    .btn-view {
        background: #0ea5e9;
    }

    .btn-view:hover {
        background: #0284c7;
    }

    .btn-edit {
        background: #f59e0b;
    }

    .btn-edit:hover {
        background: #d97706;
    }

    .btn-delete {
        background: #ef4444;
    }

    .btn-delete:hover {
        background: #dc2626;
    }

    .btn-status {
        background: #8b5cf6;
    }

    .btn-status:hover {
        background: #7c3aed;
    }

    /* =========================
       EMPTY
    ========================= */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem !important;
        color: #64748b;
    }

    .empty-state i {
        display: block;
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }

    .empty-state h5 {
        font-weight: 700;
        color: #475569;
    }

    /* =========================
       PAGINATION
    ========================= */
    .pagination-container {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid #eef2f7;
    }

    .ui-pagination {
        margin: 0;
        gap: .25rem;
    }

    .ui-pagination .ui-page-link {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 500;
        padding: .5rem 1rem;
    }

    .ui-pagination .active .ui-page-link {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border-color: #2563eb;
        color: white;
    }

    /* =========================
       RESPONSIVE
    ========================= */
    @media (max-width: 768px) {
        .header-card {
            padding: 1.25rem;
        }

        .header-title {
            font-size: 1.25rem;
        }

        .expense-table thead th {
            font-size: .65rem;
            padding: .75rem;
        }

        .expense-table tbody td {
            padding: .75rem;
            font-size: .75rem;
        }

        .table-header {
            padding: 1rem;
        }

        .filter-card {
            padding: 1rem;
        }
    }
</style>
@endpush


@section('content')

<div class="expenses-page">

    {{-- =========================================================
        HEADER
    ========================================================== --}}
    <div class="header-card">

        <div class="tw:flex tw:flex-wrap tw:mt-0 tw:-mx-3 tw:items-center! tw:justify-between!">

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[58.33333333%] tw:mb-4! tw:md:mb-0!">

                <div class="header-title">
                    <i class="bi bi-receipt-cutoff tw:mr-2!"></i>
                    Institute Expenses
                </div>

                <p class="header-subtitle">
                    View and manage institute expense transactions
                </p>

                <a href="{{ route('admin.institute-expenses.create') }}" class="ui-btn ui-btn-light tw:font-semibold! tw:rounded-bs-lg! tw:mt-2!">
                    <i class="bi bi-plus-circle tw:mr-1!"></i>
                    Add Expense
                </a>

            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-0 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[41.66666667%]">

                <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1">

                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%]">

                        <div class="stat-badge">

                            <div class="label">
                                Total Paid (all filtered)
                            </div>

                            <div class="value">
                                Rs.
                                {{ number_format($paidTotal ?? 0, 2) }}
                            </div>

                        </div>

                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[50%]">

                        <div class="stat-badge">

                            <div class="label">
                                Total Records
                            </div>

                            <div class="value">
                                {{ $expenses->total() }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        FILTER
    ========================================================== --}}
    <div class="filter-card">

        <div class="filter-title">
            <i class="bi bi-funnel-fill"></i>
            Filter Expenses
        </div>

        <form
            method="GET"
            action="{{ route('admin.institute-expenses.index') }}"
            id="filterForm"
        >

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                {{-- Search --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">

                    <div class="filter-label">
                        Search
                    </div>

                    <input
                        type="text"
                        name="search"
                        class="filter-input"
                        placeholder="Reason, note, code..."
                        value="{{ request('search') }}"
                    >

                </div>


                {{-- From Date --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        From Date
                    </div>

                    <input
                        type="date"
                        name="from_date"
                        class="filter-input"
                        value="{{ request('from_date') }}"
                    >

                </div>


                {{-- To Date --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        To Date
                    </div>

                    <input
                        type="date"
                        name="to_date"
                        class="filter-input"
                        value="{{ request('to_date') }}"
                    >

                </div>


                {{-- User --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        User
                    </div>

                    <select
                        name="user_id"
                        class="filter-input"
                    >

                        <option value="">
                            All Users
                        </option>

                        @foreach($users as $user)

                            <option
                                value="{{ $user->id }}"
                                {{ request('user_id') == $user->id ? 'selected' : '' }}
                            >
                                {{ $user->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Status --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        Status
                    </div>

                    <select
                        name="status"
                        class="filter-input"
                    >

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="paid"
                            {{ request('status') === 'paid' ? 'selected' : '' }}
                        >
                            Paid
                        </option>

                        <option
                            value="pending"
                            {{ request('status') === 'pending' ? 'selected' : '' }}
                        >
                            Pending
                        </option>

                        <option
                            value="approved"
                            {{ request('status') === 'approved' ? 'selected' : '' }}
                        >
                            Approved
                        </option>

                        <option
                            value="cancelled"
                            {{ request('status') === 'cancelled' ? 'selected' : '' }}
                        >
                            Cancelled
                        </option>

                    </select>

                </div>


                {{-- Current Month --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[8.33333333%]">

                    <div class="filter-label">
                        Current
                    </div>

                    <select
                        name="current_month"
                        class="filter-input"
                    >

                        <option value="">
                            No
                        </option>

                        <option
                            value="1"
                            {{ request('current_month') ? 'selected' : '' }}
                        >
                            Yes
                        </option>

                    </select>

                </div>


                {{-- Min Amount --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        Min Amount
                    </div>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="min_amount"
                        class="filter-input"
                        placeholder="0.00"
                        value="{{ request('min_amount') }}"
                    >

                </div>


                {{-- Max Amount --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        Max Amount
                    </div>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="max_amount"
                        class="filter-input"
                        placeholder="0.00"
                        value="{{ request('max_amount') }}"
                    >

                </div>


                {{-- Month --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        Month
                    </div>

                    <select
                        name="month"
                        class="filter-input"
                    >

                        <option value="">
                            All Months
                        </option>

                        @foreach(range(1, 12) as $m)

                            <option
                                value="{{ $m }}"
                                {{ request('month') == $m ? 'selected' : '' }}
                            >
                                {{ Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Year --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        Year
                    </div>

                    <select
                        name="year"
                        class="filter-input"
                    >

                        <option value="">
                            All Years
                        </option>

                        @for($y = 2020; $y <= now()->year; $y++)

                            <option
                                value="{{ $y }}"
                                {{ request('year') == $y ? 'selected' : '' }}
                            >
                                {{ $y }}
                            </option>

                        @endfor

                    </select>

                </div>


                {{-- Filter --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        &nbsp;
                    </div>

                    <button
                        type="submit"
                        class="btn-filter"
                    >
                        <i class="bi bi-search"></i>
                        Filter
                    </button>

                </div>


                {{-- Reset --}}
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                    <div class="filter-label">
                        &nbsp;
                    </div>

                    <a
                        href="{{ route('admin.institute-expenses.index') }}"
                        class="btn-reset"
                    >
                        <i class="bi bi-x-circle"></i>
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- =========================================================
        TABLE
    ========================================================== --}}
    <div class="table-card">

        <div class="table-header">

            <div>

                <h5 class="table-title">
                    <i class="bi bi-cash-stack"></i>
                    Expense Transactions
                </h5>

                <div class="result-count tw:mt-1!">
                    Showing
                    {{ $expenses->firstItem() ?? 0 }}
                    -
                    {{ $expenses->lastItem() ?? 0 }}
                    of
                    {{ $expenses->total() }}
                    records
                </div>

            </div>

        </div>


        <div class="ui-table-responsive">

            <table class="expense-table">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>
                            Payment Date
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Reason
                        </th>

                        <th>
                            Reason Code
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Recorded By
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                @forelse($expenses as $expense)

                    @php

                        $paymentDate = Carbon\Carbon::parse(
                            $expense->payment_date
                        );

                        $isCurrentMonth =
                            $paymentDate->month === now()->month
                            &&
                            $paymentDate->year === now()->year;

                        $status = strtolower(
                            $expense->status ?: 'paid'
                        );

                    @endphp


                    <tr>

                        {{-- Number --}}
                        <td>
                            {{
                                ($expenses->currentPage() - 1)
                                * $expenses->perPage()
                                + $loop->iteration
                            }}
                        </td>


                        {{-- Date --}}
                        <td>

                            <strong>
                                {{ $paymentDate->format('d M Y') }}
                            </strong>

                            @if($isCurrentMonth)

                                <span class="badge-current tw:ml-1!">
                                    Current
                                </span>

                            @else

                                <span class="badge-past tw:ml-1!">
                                    Past
                                </span>

                            @endif

                        </td>


                        {{-- Amount --}}
                        <td class="amount-expense">

                            - Rs.
                            {{ number_format(
                                (float) $expense->amount,
                                2
                            ) }}

                        </td>


                        {{-- Reason --}}
                        <td>

                            <div class="tw:font-semibold!">
                                {{ optional($expense->paymentReason)->name ?: ($expense->reason ?: '-') }}
                            </div>

                            @if($expense->reason_code && optional($expense->paymentReason)->name)

                                <small class="tw:text-bs-secondary-color!">
                                    {{ $expense->reason_code }}
                                </small>

                            @elseif($expense->note)

                                <small class="tw:text-bs-secondary-color!">
                                    {{ Str::limit(
                                        $expense->note,
                                        55
                                    ) }}
                                </small>

                            @endif

                        </td>


                        {{-- Reason Code --}}
                        <td>

                            @if($expense->reason_code)

                                <span class="ui-badge tw:bg-bs-light! tw:text-bs-dark! tw:bs-border!">
                                    {{ $expense->reason_code }}
                                </span>

                            @else

                                <span class="tw:text-bs-secondary-color!">
                                    -
                                </span>

                            @endif

                        </td>


                        {{-- Status --}}
                        <td>

                            @if($status === 'paid')

                                <span class="status-badge status-paid">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Paid
                                </span>

                            @elseif($status === 'pending')

                                <span class="status-badge status-pending">
                                    <i class="bi bi-clock-fill"></i>
                                    Pending
                                </span>

                            @elseif($status === 'approved')

                                <span class="status-badge status-approved">
                                    <i class="bi bi-check2-circle"></i>
                                    Approved
                                </span>

                            @elseif($status === 'cancelled')

                                <span class="status-badge status-cancelled">
                                    <i class="bi bi-x-circle-fill"></i>
                                    Cancelled
                                </span>

                            @else

                                <span class="status-badge status-pending">
                                    {{ ucfirst($status) }}
                                </span>

                            @endif

                        </td>


                        {{-- User --}}
                        <td>

                            <div class="tw:font-semibold!">

                                {{
                                    optional($expense->user)->name
                                    ?: 'System'
                                }}

                            </div>

                            <small class="tw:text-bs-secondary-color!">

                                {{
                                    optional($expense->created_at)
                                        ->format('d M Y')
                                }}

                            </small>

                        </td>


                        {{-- Actions --}}
                        <td>

                            <div class="tw:flex! tw:gap-1!">

                                {{-- View --}}
                                <a
                                    href="{{ route(
                                        'admin.institute-expenses.show',
                                        $expense->id
                                    ) }}"
                                    class="action-btn btn-view"
                                    title="View"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>


                                {{-- Edit --}}
                                @if($isCurrentMonth)

                                    <a
                                        href="{{ route(
                                            'admin.institute-expenses.edit',
                                            $expense->id
                                        ) }}"
                                        class="action-btn btn-edit"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                @else

                                    <button
                                        type="button"
                                        class="action-btn btn-edit tw:[opacity:0.5]!"
                                        disabled
                                        title="Only current month records can be edited"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                @endif


                                {{-- Delete --}}
                                @if($isCurrentMonth)

                                    <button
                                        type="button"
                                        class="action-btn btn-delete"
                                        data-nx-toggle="modal"
                                        data-nx-target="#deleteModal{{ $expense->id }}"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>

                                @else

                                    <button
                                        type="button"
                                        class="action-btn btn-delete tw:[opacity:0.5]!"
                                        disabled
                                        title="Only current month records can be deleted"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>

                                @endif

                            </div>

                        </td>

                    </tr>


                    {{-- =================================================
                         DELETE MODAL
                    ================================================== --}}
                    @if($isCurrentMonth)

                        <div
                            class="ui-modal ui-fade"
                            id="deleteModal{{ $expense->id }}"
                            tabindex="-1"
                            aria-hidden="true"
                        >

                            <div class="ui-modal-dialog ui-modal-dialog-centered">

                                <div class="ui-modal-content tw:[border:0]! tw:shadow-bs! tw:rounded-bs-xl!">

                                    <div class="ui-modal-header">

                                        <h5 class="ui-modal-title tw:font-bold!">
                                            Confirm Delete
                                        </h5>

                                        <button
                                            type="button"
                                            class="ui-btn-close"
                                            data-nx-dismiss="modal"
                                        ></button>

                                    </div>


                                    <div class="ui-modal-body tw:text-center!">

                                        <i
                                            class="bi bi-exclamation-triangle-fill tw:text-bs-danger!"
                                            style="font-size: 3rem;"
                                        ></i>

                                        <h5 class="tw:mt-4!">
                                            Are you sure?
                                        </h5>

                                        <p class="tw:text-bs-secondary-color!">

                                            Delete expense of

                                            <strong>
                                                Rs.
                                                {{
                                                    number_format(
                                                        (float) $expense->amount,
                                                        2
                                                    )
                                                }}
                                            </strong>

                                            from

                                            <strong>
                                                {{ $paymentDate->format('d M Y') }}
                                            </strong>
                                            ?

                                        </p>

                                    </div>


                                    <div class="ui-modal-footer">

                                        <button
                                            type="button"
                                            class="ui-btn ui-btn-outline-secondary"
                                            data-nx-dismiss="modal"
                                        >
                                            Cancel
                                        </button>


                                        <form
                                            action="{{ route(
                                                'admin.institute-expenses.destroy',
                                                $expense->id
                                            ) }}"
                                            method="POST"
                                        >

                                            @csrf

                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="ui-btn ui-btn-danger"
                                            >
                                                <i class="bi bi-trash tw:mr-1!"></i>
                                                Delete Expense
                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        </div>

                    @endif

                @empty

                    <tr>

                        <td
                            colspan="8"
                            class="empty-state"
                        >

                            <i class="bi bi-receipt"></i>

                            <h5>
                                No Expenses Found
                            </h5>

                            <p class="tw:mb-0!">
                                No institute expense records match
                                your current filters.
                            </p>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if($expenses->hasPages())

            <div class="pagination-container">

                {{ $expenses->links() }}

            </div>

        @endif

    </div>

</div>

@endsection