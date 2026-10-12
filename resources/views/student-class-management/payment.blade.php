@extends('layouts.app')

@section('title', 'Payment Details - ' . ($data['student']->full_name ?? 'Student'))
@section('page-title', 'Payment Details')

@section('content')

<div class="details-page">

    {{-- ============================================================
         HEADER
    ============================================================= --}}
    <div class="top-card">

        <div>

            <div class="eyebrow">
                <i class="bi bi-cash-stack"></i>
                Student Payment
            </div>

            <h3>
                {{ $data['student']->initial_name ?? $data['student']->full_name ?? 'Student' }}
            </h3>

            <p>
                {{ $data['class']->class_name ?? 'N/A' }}

                <span class="dot">•</span>

                {{ $data['category']->category_name ?? 'N/A' }}
            </p>

        </div>


        <a
            href="{{ route('admin.student-class-management.show', $data['student']->id) }}"
            class="btn btn-light border custom-btn"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Classes
        </a>

    </div>


    {{-- ============================================================
         SUMMARY CARDS
    ============================================================= --}}
    <div class="info-grid">

        {{-- Total Expected --}}
        <div class="info-card">

            <span>
                Total Expected
            </span>

            <strong>
                Rs.
                {{ number_format((float) ($data['expected_fee'] ?? 0), 2) }}
            </strong>

            <small>
                From enrollment month
            </small>

        </div>


        {{-- Total Paid --}}
        <div class="info-card success">

            <span>
                Total Paid
            </span>

            <strong>
                Rs.
                {{ number_format((float) ($data['paid_amount'] ?? 0), 2) }}
            </strong>

            <small>
                Total payments
            </small>

        </div>


        {{-- Balance --}}
        <div class="info-card warning">

            <span>
                Total Balance
            </span>

            <strong>
                Rs.
                {{ number_format((float) ($data['balance'] ?? 0), 2) }}
            </strong>

            <small>
                Remaining amount
            </small>

        </div>


        {{-- Status --}}
        <div class="info-card">

            <span>
                Overall Status
            </span>

            @php
                $status = $data['payment_status'] ?? 'unpaid';
            @endphp

            @if ($status === 'paid')

                <strong class="status-text paid">
                    Paid
                </strong>

            @else

                <strong class="status-text unpaid">
                    Unpaid
                </strong>

            @endif

            <small>
                Current payment status
            </small>

        </div>

    </div>


    {{-- ============================================================
         MONTH-WISE PAYMENT
    ============================================================= --}}
    <div class="main-card">

        <div class="section-header">

            <div>

                <h4>
                    Month-wise Payment
                </h4>

                <p>
                    Payment status from the student's enrollment month
                </p>

            </div>


            <span class="count-badge">

                {{ ($data['monthly_payments'] ?? collect())->count() }}

                Months

            </span>

        </div>


        @if (($data['monthly_payments'] ?? collect())->count() > 0)

            <div class="table-responsive">

                <table class="table details-table align-middle mb-0">

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Payment Month
                            </th>

                            <th>
                                Expected Fee
                            </th>

                            <th>
                                Paid Amount
                            </th>

                            <th>
                                Balance
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Payment Details
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    @foreach ($data['monthly_payments'] as $index => $month)

                        @php

                            $expected =
                                (float) ($month['expected_fee'] ?? 0);

                            $paid =
                                (float) ($month['paid_amount'] ?? 0);

                            $balance =
                                max($expected - $paid, 0);

                            /*
                            |--------------------------------------------------
                            | Paid / Unpaid ONLY
                            |--------------------------------------------------
                            */

                            $monthStatus =
                                $paid >= $expected
                                    ? 'paid'
                                    : 'unpaid';

                        @endphp


                        {{-- =================================================
                             MONTH ROW
                        ================================================== --}}
                        <tr>

                            {{-- Number --}}
                            <td>
                                {{ $index + 1 }}
                            </td>


                            {{-- Payment Month --}}
                            <td>

                                <span class="month-pill">

                                    <i class="bi bi-calendar3"></i>

                                    {{ $month['month_name'] ?? 'N/A' }}

                                </span>

                            </td>


                            {{-- Expected Fee --}}
                            <td class="fw-semibold">

                                Rs.
                                {{ number_format($expected, 2) }}

                            </td>


                            {{-- Paid Amount --}}
                            <td class="fw-bold text-success">

                                Rs.
                                {{ number_format($paid, 2) }}

                            </td>


                            {{-- Balance --}}
                            <td>

                                @if ($balance > 0)

                                    <span class="text-danger fw-bold">

                                        Rs.
                                        {{ number_format($balance, 2) }}

                                    </span>

                                @else

                                    <span class="text-success fw-bold">

                                        Rs. 0.00

                                    </span>

                                @endif

                            </td>


                            {{-- Status --}}
                            <td>

                                @if ($monthStatus === 'paid')

                                    <span class="status-badge paid-badge">

                                        <i class="bi bi-check-circle-fill"></i>

                                        Paid

                                    </span>

                                @else

                                    <span class="status-badge unpaid-badge">

                                        <i class="bi bi-x-circle-fill"></i>

                                        Unpaid

                                    </span>

                                @endif

                            </td>


                            {{-- Payment Details --}}
                            <td>

                                @if (($month['payments'] ?? collect())->count() > 0)

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-light border payment-count-btn"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#monthPayments{{ $index }}"
                                        aria-expanded="false"
                                        aria-controls="monthPayments{{ $index }}"
                                    >

                                        <i class="bi bi-receipt"></i>

                                        {{ $month['payments']->count() }}

                                        {{ $month['payments']->count() === 1 ? 'Payment' : 'Payments' }}

                                        <i class="bi bi-chevron-down ms-1"></i>

                                    </button>

                                @else

                                    <span class="text-muted">

                                        No Payment

                                    </span>

                                @endif

                            </td>

                        </tr>


                        {{-- =================================================
                             PAYMENT RECORDS
                        ================================================== --}}
                        @if (($month['payments'] ?? collect())->count() > 0)

                            <tr class="payment-details-row">

                                <td
                                    colspan="7"
                                    class="p-0"
                                >

                                    <div
                                        class="collapse"
                                        id="monthPayments{{ $index }}"
                                    >

                                        <div class="monthly-payment-panel">

                                            <div class="panel-title">

                                                <i class="bi bi-receipt-cutoff"></i>

                                                Payment Records -

                                                {{ $month['month_name'] ?? 'N/A' }}

                                            </div>


                                            <div class="table-responsive">

                                                <table class="table inner-table mb-0">

                                                    <thead>

                                                        <tr>

                                                            <th>
                                                                #
                                                            </th>

                                                            <th>
                                                                Receipt No
                                                            </th>

                                                            <th>
                                                                Paid At
                                                            </th>

                                                            <th>
                                                                Amount
                                                            </th>

                                                            <th>
                                                                Discount
                                                            </th>

                                                            <th>
                                                                Payment Method
                                                            </th>

                                                            <th>
                                                                Reference No
                                                            </th>

                                                            <th>
                                                                Mark Method
                                                            </th>

                                                        </tr>

                                                    </thead>


                                                    <tbody>

                                                    @foreach ($month['payments'] as $paymentIndex => $payment)

                                                        <tr>

                                                            {{-- # --}}
                                                            <td>
                                                                {{ $paymentIndex + 1 }}
                                                            </td>


                                                            {{-- Receipt --}}
                                                            <td>

                                                                @if ($payment->receipt_number)

                                                                    <span class="receipt-pill">

                                                                        <i class="bi bi-receipt"></i>

                                                                        {{ $payment->receipt_number }}

                                                                    </span>

                                                                @else

                                                                    <span class="text-muted">
                                                                        —
                                                                    </span>

                                                                @endif

                                                            </td>


                                                            {{-- Paid At --}}
                                                            <td>

                                                                @if ($payment->paid_at)

                                                                    {{ $payment->paid_at->format('Y-m-d h:i A') }}

                                                                @else

                                                                    —

                                                                @endif

                                                            </td>


                                                            {{-- Amount --}}
                                                            <td class="fw-bold text-success">

                                                                Rs.

                                                                {{ number_format(
                                                                    (float) ($payment->amount ?? 0),
                                                                    2
                                                                ) }}

                                                            </td>


                                                            {{-- Discount --}}
                                                            <td>

                                                                Rs.

                                                                {{ number_format(
                                                                    (float) ($payment->discount_amount ?? 0),
                                                                    2
                                                                ) }}

                                                            </td>


                                                            {{-- Payment Method --}}
                                                            <td>

                                                                {{ $payment->payment_method ?? 'N/A' }}

                                                            </td>


                                                            {{-- Reference Number --}}
                                                            <td>

                                                                {{ $payment->reference_number ?? '—' }}

                                                            </td>


                                                            {{-- Mark Method --}}
                                                            <td>

                                                                {{ $payment->mark_method ?? '—' }}

                                                            </td>

                                                        </tr>


                                                        {{-- Note --}}
                                                        @if ($payment->note)

                                                            <tr class="payment-note-row">

                                                                <td colspan="8">

                                                                    <div class="payment-note">

                                                                        <i class="bi bi-chat-left-text"></i>

                                                                        <strong>
                                                                            Note:
                                                                        </strong>

                                                                        {{ $payment->note }}

                                                                    </div>

                                                                </td>

                                                            </tr>

                                                        @endif

                                                    @endforeach

                                                    </tbody>

                                                </table>

                                            </div>

                                        </div>

                                    </div>

                                </td>

                            </tr>

                        @endif

                    @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="empty-state">

                <i class="bi bi-wallet2"></i>

                <h5>
                    No Payment Information
                </h5>

                <p>
                    No payment information was found from the enrollment month.
                </p>

            </div>

        @endif

    </div>


    {{-- ============================================================
         ALL PAYMENT TRANSACTIONS
    ============================================================= --}}
    <div class="main-card">

        <div class="section-header">

            <div>

                <h4>
                    Payment Transactions
                </h4>

                <p>
                    All payment transactions for this enrollment
                </p>

            </div>


            <span class="count-badge">

                {{ ($data['payments'] ?? collect())->count() }}

                Records

            </span>

        </div>


        @if (($data['payments'] ?? collect())->count() > 0)

            <div class="table-responsive">

                <table class="table details-table align-middle mb-0">

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Payment Month
                            </th>

                            <th>
                                Receipt No
                            </th>

                            <th>
                                Paid At
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Payment Method
                            </th>

                            <th>
                                Reference No
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    @foreach ($data['payments'] as $index => $payment)

                        <tr>

                            <td>
                                {{ $index + 1 }}
                            </td>


                            {{-- Payment Month --}}
                            <td>

                                <span class="month-pill">

                                    <i class="bi bi-calendar3"></i>

                                    {{ $payment->payment_month?->format('F Y') ?? 'N/A' }}

                                </span>

                            </td>


                            {{-- Receipt --}}
                            <td>

                                @if ($payment->receipt_number)

                                    <span class="receipt-pill">

                                        <i class="bi bi-receipt"></i>

                                        {{ $payment->receipt_number }}

                                    </span>

                                @else

                                    <span class="text-muted">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Paid At --}}
                            <td>

                                {{ $payment->paid_at?->format('Y-m-d h:i A') ?? 'N/A' }}

                            </td>


                            {{-- Amount --}}
                            <td class="fw-bold text-success">

                                Rs.

                                {{ number_format(
                                    (float) ($payment->amount ?? 0),
                                    2
                                ) }}

                            </td>


                            {{-- Payment Method --}}
                            <td>

                                {{ $payment->payment_method ?? 'N/A' }}

                            </td>


                            {{-- Reference --}}
                            <td>

                                {{ $payment->reference_number ?? '—' }}

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="empty-state">

                <i class="bi bi-wallet2"></i>

                <h5>
                    No Payments Found
                </h5>

                <p>
                    No payment records were found for this enrollment.
                </p>

            </div>

        @endif

    </div>


    {{-- ============================================================
         ENROLLMENT INFORMATION
    ============================================================= --}}
    <div class="main-card">

        <div class="section-header">

            <div>

                <h4>
                    Enrollment Information
                </h4>

                <p>
                    Class and enrollment details
                </p>

            </div>

        </div>


        <div class="detail-grid">

            <div>

                <span>
                    Student ID
                </span>

                <strong>
                    {{ $data['student']->custom_id ?? 'N/A' }}
                </strong>

            </div>


            <div>

                <span>
                    Class
                </span>

                <strong>
                    {{ $data['class']->class_name ?? 'N/A' }}
                </strong>

            </div>


            <div>

                <span>
                    Category
                </span>

                <strong>
                    {{ $data['category']->category_name ?? 'N/A' }}
                </strong>

            </div>


            <div>

                <span>
                    Grade
                </span>

                <strong>
                    {{ $data['grade']->grade_name ?? 'N/A' }}
                </strong>

            </div>


            <div>

                <span>
                    Teacher
                </span>

                <strong>
                    {{ $data['teacher']->initials ?? 'N/A' }}
                </strong>

            </div>


            <div>

                <span>
                    Enrolled Date
                </span>

                <strong>
                    {{ $data['enrolled_at']?->format('Y-m-d') ?? 'N/A' }}
                </strong>

            </div>


            @if (!empty($data['left_at']))

                <div>

                    <span>
                        Left Date
                    </span>

                    <strong>
                        {{ $data['left_at']?->format('Y-m-d') }}
                    </strong>

                </div>

            @endif

        </div>

    </div>

</div>

@endsection


{{-- ================================================================
     STYLES
================================================================ --}}
@push('styles')

<style>

    .details-page {
        animation: fadeIn .35s ease;
    }


    /* ============================================================
       TOP CARD
    ============================================================ */

    .top-card,
    .main-card,
    .info-card {
        background: #fff;
        border: 1px solid #eef2f7;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
    }


    .top-card {
        border-radius: 24px;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }


    .eyebrow {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #64748b;
        font-weight: 700;
    }


    .top-card h3 {
        margin: .3rem 0;
        font-weight: 800;
    }


    .top-card p {
        margin: 0;
        color: #64748b;
    }


    .dot {
        margin: 0 .35rem;
    }


    .custom-btn {
        border-radius: 13px;
        padding: .65rem 1rem;
        font-weight: 600;
    }


    /* ============================================================
       SUMMARY
    ============================================================ */

    .info-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }


    .info-card {
        border-radius: 20px;
        padding: 1.25rem;
    }


    .info-card span {
        display: block;
        color: #64748b;
        font-size: .8rem;
    }


    .info-card strong {
        display: block;
        font-size: 1.35rem;
        margin: .35rem 0;
    }


    .info-card small {
        color: #94a3b8;
    }


    .info-card.success {
        border-left: 4px solid #10b981;
    }


    .info-card.warning {
        border-left: 4px solid #f59e0b;
    }


    .status-text {
        font-size: 1.15rem !important;
        text-transform: capitalize;
    }


    .status-text.paid {
        color: #059669;
    }


    .status-text.unpaid {
        color: #dc2626;
    }


    /* ============================================================
       MAIN CARD
    ============================================================ */

    .main-card {
        border-radius: 24px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }


    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.2rem;
    }


    .section-header h4 {
        margin: 0;
        font-weight: 750;
    }


    .section-header p {
        margin: .2rem 0 0;
        color: #64748b;
    }


    .count-badge {
        background: #f8fafc;
        border-radius: 10px;
        padding: .45rem .7rem;
        color: #475569;
        font-size: .8rem;
        white-space: nowrap;
    }


    /* ============================================================
       TABLE
    ============================================================ */

    .details-table {
        min-width: 900px;
    }


    .details-table thead th {
        background: #f8fafc;
        border: 0;
        color: #64748b;
        font-size: .76rem;
        text-transform: uppercase;
        padding: .9rem;
        white-space: nowrap;
    }


    .details-table td {
        padding: .9rem;
        border-color: #f1f5f9;
    }


    .details-table tbody tr {
        transition: .2s ease;
    }


    .details-table tbody tr:hover {
        background: #fbfdff;
    }


    /* ============================================================
       MONTH
    ============================================================ */

    .month-pill {
        display: inline-flex;
        gap: .35rem;
        align-items: center;
        background: #f8fafc;
        border-radius: 10px;
        padding: .45rem .7rem;
        color: #475569;
        font-size: .8rem;
        white-space: nowrap;
    }


    /* ============================================================
       RECEIPT
    ============================================================ */

    .receipt-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        background: #eff6ff;
        color: #2563eb;
        border-radius: 9px;
        padding: .4rem .6rem;
        font-size: .78rem;
        font-weight: 700;
        white-space: nowrap;
    }


    /* ============================================================
       STATUS
    ============================================================ */

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 10px;
        padding: .45rem .7rem;
        font-size: .75rem;
        font-weight: 700;
        white-space: nowrap;
    }


    .paid-badge {
        background: #ecfdf5;
        color: #059669;
    }


    .unpaid-badge {
        background: #fef2f2;
        color: #dc2626;
    }


    /* ============================================================
       PAYMENT COUNT
    ============================================================ */

    .payment-count-btn {
        border-radius: 10px;
        font-weight: 600;
        white-space: nowrap;
    }


    /* ============================================================
       EXPANDED PAYMENT PANEL
    ============================================================ */

    .payment-details-row > td {
        background: #f8fafc;
        border-top: 0;
    }


    .monthly-payment-panel {
        padding: 1rem 1.2rem 1.2rem;
    }


    .panel-title {
        font-weight: 700;
        color: #334155;
        margin-bottom: .75rem;
    }


    .inner-table {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        min-width: 950px;
    }


    .inner-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: .7rem;
        text-transform: uppercase;
        padding: .7rem;
        border: 0;
        white-space: nowrap;
    }


    .inner-table td {
        padding: .7rem;
        border-color: #f1f5f9;
        font-size: .82rem;
        white-space: nowrap;
    }


    .payment-note-row td {
        background: #fff !important;
        padding-top: 0 !important;
    }


    .payment-note {
        background: #fffbeb;
        border-radius: 10px;
        padding: .65rem .8rem;
        color: #92400e;
        font-size: .8rem;
    }


    /* ============================================================
       DETAIL GRID
    ============================================================ */

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }


    .detail-grid > div {
        background: #f8fafc;
        border-radius: 14px;
        padding: 1rem;
    }


    .detail-grid span {
        display: block;
        color: #64748b;
        font-size: .8rem;
    }


    .detail-grid strong {
        display: block;
        margin-top: .25rem;
    }


    /* ============================================================
       EMPTY STATE
    ============================================================ */

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #64748b;
    }


    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
    }


    .empty-state h5 {
        color: #334155;
        font-weight: 700;
        margin: .8rem 0 .3rem;
    }


    /* ============================================================
       ANIMATION
    ============================================================ */

    @keyframes fadeIn {

        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }

    }


    /* ============================================================
       RESPONSIVE
    ============================================================ */

    @media (max-width: 1100px) {

        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .detail-grid {
            grid-template-columns: repeat(2, 1fr);
        }

    }


    @media (max-width: 768px) {

        .top-card {
            flex-direction: column;
            align-items: stretch;
        }

        .main-card {
            padding: 1rem;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }

        .section-header {
            flex-direction: column;
            align-items: flex-start;
        }

    }


    @media (max-width: 576px) {

        .top-card {
            padding: 1rem;
        }

        .info-card {
            padding: 1rem;
        }

        .details-table {
            min-width: 900px;
        }

    }

</style>

@endpush