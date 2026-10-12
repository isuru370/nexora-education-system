@extends('layouts.app')

@section('title', 'Expense Details')
@section('page-title', 'Expense Details')

@push('styles')
<style>
    .expense-details-page {
        animation: fadeIn .4s ease;
    }

    /* =========================
       TOP SECTION
    ========================= */
    .page-heading {
        margin-bottom: 1.5rem;
    }

    .page-heading h4 {
        color: #0f172a;
        font-weight: 800;
    }

    .page-heading p {
        font-size: .85rem;
    }

    .page-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .btn-back,
    .btn-edit {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 12px;
        padding: .6rem 1rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .2s ease;
    }

    .btn-back {
        background: #fff;
        border: 1px solid #cbd5e1;
        color: #475569;
    }

    .btn-back:hover {
        background: #f8fafc;
        color: #334155;
        transform: translateY(-1px);
    }

    .btn-edit {
        background: #f59e0b;
        border: 1px solid #f59e0b;
        color: #fff;
    }

    .btn-edit:hover {
        background: #d97706;
        border-color: #d97706;
        color: #fff;
        transform: translateY(-1px);
    }

    /* =========================
       MAIN CARD
    ========================= */
    .details-card {
        background: #fff;
        border: 1px solid #eef2f7;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .03);
    }

    .details-header {
        padding: 1.5rem;
        background: linear-gradient(
            135deg,
            #0f172a,
            #1e293b
        );
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .details-header::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .03);
        right: -80px;
        top: -100px;
    }

    .details-header-content {
        position: relative;
        z-index: 2;
    }

    .details-header h4 {
        margin: 0;
        font-weight: 800;
    }

    .details-header p {
        margin: .35rem 0 0;
        color: #94a3b8;
        font-size: .85rem;
    }

    .expense-reference {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin-top: .75rem;
        padding: .35rem .7rem;
        background: rgba(255, 255, 255, .08);
        border-radius: 10px;
        font-size: .72rem;
        color: #cbd5e1;
        font-family: monospace;
    }

    .details-body {
        padding: 1.5rem;
    }

    /* =========================
       AMOUNT
    ========================= */
    .amount-box {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 20px;
        padding: 1.5rem;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .amount-label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        font-weight: 700;
        color: #991b1b;
        margin-bottom: .3rem;
    }

    .amount-value {
        font-size: 2.2rem;
        font-weight: 800;
        color: #dc2626;
        font-family: monospace;
        line-height: 1.2;
    }

    /* =========================
       DETAIL ITEMS
    ========================= */
    .detail-item {
        padding: 1rem;
        border: 1px solid #eef2f7;
        border-radius: 16px;
        height: 100%;
        background: #f8fafc;
    }

    .detail-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        margin-bottom: .35rem;
    }

    .detail-value {
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
        word-break: break-word;
    }

    .detail-value-muted {
        color: #64748b;
        font-weight: 500;
    }

    /* =========================
       STATUS
    ========================= */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .8rem;
        border-radius: 20px;
        font-size: .72rem;
        font-weight: 700;
    }

    .badge-paid {
        background: #dcfce7;
        color: #166534;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-approved {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-unknown {
        background: #f1f5f9;
        color: #475569;
    }

    /* =========================
       REASON CODE
    ========================= */
    .reason-code {
        display: inline-flex;
        align-items: center;
        padding: .3rem .65rem;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        color: #475569;
        font-family: monospace;
        font-size: .78rem;
        font-weight: 600;
    }

    /* =========================
       NOTE
    ========================= */
    .note-section {
        margin-top: 1.5rem;
    }

    .note-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.1rem;
        color: #475569;
        line-height: 1.7;
        min-height: 70px;
        white-space: pre-line;
    }

    .empty-note {
        color: #94a3b8;
        font-style: italic;
    }

    /* =========================
       RECORD INFO
    ========================= */
    .record-info {
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid #eef2f7;
    }

    .record-info-title {
        font-size: .8rem;
        font-weight: 800;
        color: #334155;
        margin-bottom: .85rem;
    }

    /* =========================
       RESPONSIVE
    ========================= */
    @media (max-width: 768px) {

        .details-header {
            padding: 1.25rem;
        }

        .details-body {
            padding: 1.25rem;
        }

        .amount-value {
            font-size: 1.75rem;
        }

        .page-heading {
            align-items: flex-start !important;
        }

        .page-actions {
            width: 100%;
        }

        .btn-back,
        .btn-edit {
            flex: 1;
            justify-content: center;
        }
    }
</style>
@endpush


@section('content')

@php

    $paymentDate = Carbon\Carbon::parse(
        $instituteExpense->payment_date
    );

    $isCurrentMonth =
        $paymentDate->month === now()->month
        &&
        $paymentDate->year === now()->year;

    $status = strtolower(
        $instituteExpense->status ?: 'pending'
    );

@endphp


<div class="expense-details-page">


    {{-- =========================================================
         PAGE HEADING
    ========================================================== --}}
    <div class="page-heading d-flex justify-content-between align-items-center flex-wrap gap-3">

        <div>

            <h4 class="mb-1">
                <i class="bi bi-receipt-cutoff me-2 text-primary"></i>
                Expense Details
            </h4>

            <p class="text-muted mb-0">
                View institute expense transaction details
            </p>

        </div>


        <div class="page-actions">

            {{-- Back --}}
            <a
                href="{{ route(
                    'admin.institute-expenses.index'
                ) }}"
                class="btn-back"
            >
                <i class="bi bi-arrow-left"></i>
                Back
            </a>


            {{-- Edit only current month --}}
            @if($isCurrentMonth)

                <a
                    href="{{ route(
                        'admin.institute-expenses.edit',
                        $instituteExpense->id
                    ) }}"
                    class="btn-edit"
                >
                    <i class="bi bi-pencil"></i>
                    Edit
                </a>

            @endif

        </div>

    </div>


    {{-- =========================================================
         DETAILS CARD
    ========================================================== --}}
    <div class="details-card">


        {{-- Header --}}
        <div class="details-header">

            <div class="details-header-content">

                <h4>
                    <i class="bi bi-wallet2 me-2"></i>
                    Institute Expense
                </h4>

                <p>
                    Expense transaction details
                </p>

                <div class="expense-reference">

                    <i class="bi bi-hash"></i>

                    EXP-{{
                        str_pad(
                            $instituteExpense->id,
                            6,
                            '0',
                            STR_PAD_LEFT
                        )
                    }}

                </div>

            </div>

        </div>


        {{-- Body --}}
        <div class="details-body">


            {{-- =================================================
                 AMOUNT
            ================================================== --}}
            <div class="amount-box">

                <div class="amount-label">
                    Expense Amount
                </div>

                <div class="amount-value">
                    Rs.
                    {{ number_format(
                        (float) $instituteExpense->amount,
                        2
                    ) }}
                </div>

            </div>


            {{-- =================================================
                 MAIN DETAILS
            ================================================== --}}
            <div class="row g-3">


                {{-- Payment Date --}}
                <div class="col-md-6">

                    <div class="detail-item">

                        <div class="detail-label">
                            Payment Date
                        </div>

                        <div class="detail-value">

                            {{ $paymentDate->format('d M Y') }}

                            @if($isCurrentMonth)

                                <span class="badge bg-primary ms-1">
                                    Current Month
                                </span>

                            @else

                                <span class="badge bg-secondary ms-1">
                                    Past Month
                                </span>

                            @endif

                        </div>

                    </div>

                </div>


                {{-- Status --}}
                <div class="col-md-6">

                    <div class="detail-item">

                        <div class="detail-label">
                            Status
                        </div>

                        <div class="detail-value">

                            @if($status === 'paid')

                                <span class="status-badge badge-paid">

                                    <i class="bi bi-check-circle-fill"></i>

                                    Paid

                                </span>

                            @elseif($status === 'pending')

                                <span class="status-badge badge-pending">

                                    <i class="bi bi-clock-fill"></i>

                                    Pending

                                </span>

                            @elseif($status === 'approved')

                                <span class="status-badge badge-approved">

                                    <i class="bi bi-check2-circle"></i>

                                    Approved

                                </span>

                            @elseif($status === 'cancelled')

                                <span class="status-badge badge-cancelled">

                                    <i class="bi bi-x-circle-fill"></i>

                                    Cancelled

                                </span>

                            @else

                                <span class="status-badge badge-unknown">

                                    {{ ucfirst($status) }}

                                </span>

                            @endif

                        </div>

                    </div>

                </div>


                {{-- Reason --}}
                <div class="col-md-6">

                    <div class="detail-item">

                        <div class="detail-label">
                            Reason
                        </div>

                        <div class="detail-value">

                            {{ optional($instituteExpense->paymentReason)->name ?: ($instituteExpense->reason ?: '-') }}

                        </div>

                    </div>

                </div>


                {{-- Reason Code --}}
                <div class="col-md-6">

                    <div class="detail-item">

                        <div class="detail-label">
                            Reason Code
                        </div>

                        <div class="detail-value">

                            @if($instituteExpense->reason_code)

                                <span class="reason-code">

                                    {{ $instituteExpense->reason_code }}

                                </span>

                            @else

                                <span class="detail-value-muted">
                                    -
                                </span>

                            @endif

                        </div>

                    </div>

                </div>


                {{-- Recorded By --}}
                <div class="col-md-6">

                    <div class="detail-item">

                        <div class="detail-label">
                            Recorded By
                        </div>

                        <div class="detail-value">

                            {{
                                optional(
                                    $instituteExpense->user
                                )->name
                                ?: 'System'
                            }}

                        </div>

                    </div>

                </div>


                {{-- Payment Type --}}
                <div class="col-md-6">

                    <div class="detail-item">

                        <div class="detail-label">
                            Payment Type
                        </div>

                        <div class="detail-value">

                            {{ ucfirst(
                                $instituteExpense->payment_type
                            ) }}

                        </div>

                    </div>

                </div>


                {{-- Created At --}}
                <div class="col-md-6">

                    <div class="detail-item">

                        <div class="detail-label">
                            Created At
                        </div>

                        <div class="detail-value">

                            {{
                                optional(
                                    $instituteExpense->created_at
                                )->format(
                                    'd M Y, h:i A'
                                )
                                ?: '-'
                            }}

                        </div>

                    </div>

                </div>


                {{-- Updated At --}}
                <div class="col-md-6">

                    <div class="detail-item">

                        <div class="detail-label">
                            Last Updated
                        </div>

                        <div class="detail-value">

                            {{
                                optional(
                                    $instituteExpense->updated_at
                                )->format(
                                    'd M Y, h:i A'
                                )
                                ?: '-'
                            }}

                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 NOTE
            ================================================== --}}
            <div class="note-section">

                <div class="detail-label mb-2">
                    Note
                </div>

                <div class="note-box">

                    @if($instituteExpense->note)

                        {{ $instituteExpense->note }}

                    @else

                        <span class="empty-note">
                            No note available.
                        </span>

                    @endif

                </div>

            </div>


            {{-- =================================================
                 RECORD INFORMATION
            ================================================== --}}
            <div class="record-info">

                <div class="record-info-title">
                    <i class="bi bi-info-circle me-1"></i>
                    Record Information
                </div>

                <div class="row g-3">

                    <div class="col-md-4">

                        <div class="detail-item">

                            <div class="detail-label">
                                Database ID
                            </div>

                            <div class="detail-value">
                                #{{ $instituteExpense->id }}
                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-item">

                            <div class="detail-label">
                                Expense Type
                            </div>

                            <div class="detail-value">
                                Institute Expense
                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="detail-item">

                            <div class="detail-label">
                                Record Status
                            </div>

                            <div class="detail-value">

                                @if($instituteExpense->deleted_at)

                                    <span class="status-badge badge-cancelled">
                                        <i class="bi bi-trash"></i>
                                        Deleted
                                    </span>

                                @else

                                    <span class="status-badge badge-paid">
                                        <i class="bi bi-check-circle"></i>
                                        Active
                                    </span>

                                @endif

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection