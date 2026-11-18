@extends('layouts.app')

@section('title', 'Category Wise Student Payment Details')
@section('page-title', 'Category Wise Student Payment Details')

@section('content')

    <div class="category-payment-page">

        @php
            $selectedYear = $year ?? request()->year;
            $selectedMonth = $month ?? request()->month;

            $reportMonth = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y');

            $studentsArray = $students ?? [];
            $totalStudents = count($studentsArray);

            $paginationCurrentPage = isset($pagination['current_page'])
                ? (int) $pagination['current_page']
                : 1;

            $paginationPerPage = isset($pagination['per_page'])
                ? (int) $pagination['per_page']
                : 50;

            $paginationTotal = isset($pagination['total'])
                ? (int) $pagination['total']
                : $totalStudents;

            $paginationFrom = $paginationTotal > 0
                ? (($paginationCurrentPage - 1) * $paginationPerPage) + 1
                : 0;

            $paginationTo = min(
                $paginationCurrentPage * $paginationPerPage,
                $paginationTotal
            );

            /*
            |--------------------------------------------------------------------------
            | PAYMENT SUMMARY
            |--------------------------------------------------------------------------
            */

            $totalPaid = collect($studentsArray)
                ->filter(function ($student) {
                    return !($student['is_free_card'] ?? false) &&
                        ($student['paid_amount'] ?? 0) >= ($student['final_fee'] ?? 0);
                })
                ->count();

            $totalUnpaid = collect($studentsArray)
                ->filter(function ($student) {
                    return !($student['is_free_card'] ?? false) && ($student['paid_amount'] ?? 0) <= 0;
                })
                ->count();

            $totalFreeCard = collect($studentsArray)
                ->filter(function ($student) {
                    return ($student['is_free_card'] ?? false) == true;
                })
                ->count();

            $totalCollected = collect($studentsArray)->sum('monthly_paid_amount');

            $totalExpected = collect($studentsArray)->sum('final_fee');

            $collectionPercent = $totalExpected > 0 ? ($totalCollected / $totalExpected) * 100 : 0;

            /*
            |--------------------------------------------------------------------------
            | Prevent progress bar overflow
            |--------------------------------------------------------------------------
            */

            $collectionPercent = min(100, max(0, $collectionPercent));
        @endphp


        {{-- ========================================================= --}}
        {{-- HERO --}}
        {{-- ========================================================= --}}

        <div class="hero-card tw:mb-6!">

            <div class="hero-content">

                <div>

                    <a href="{{ url()->previous() }}" class="ui-btn ui-btn-outline-secondary custom-btn tw:mb-4!">

                        <i class="bi bi-arrow-left tw:mr-1!"></i>

                        Back to Summary

                    </a>

                    <h3 class="tw:font-bold! tw:mb-1!">
                        Category Wise Student Payment Details
                    </h3>

                    <p class="tw:text-bs-secondary-color! tw:mb-0!">
                        {{ $reportMonth }} Payment Details
                    </p>

                </div>


                <div class="hero-actions">

                    <button onclick="window.print()" class="ui-btn ui-btn-primary custom-btn">

                        <i class="bi bi-printer tw:mr-1!"></i>

                        Print Report

                    </button>


                    <button class="ui-btn ui-btn-outline-success custom-btn" disabled>

                        <i class="bi bi-file-earmark-excel tw:mr-1!"></i>

                        Export Excel

                    </button>


                    <button class="ui-btn ui-btn-outline-danger custom-btn" disabled>

                        <i class="bi bi-file-earmark-pdf tw:mr-1!"></i>

                        Export PDF

                    </button>


                    <button class="ui-btn ui-btn-outline-dark custom-btn" disabled>

                        <i class="bi bi-download tw:mr-1!"></i>

                        Download

                    </button>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- SUMMARY CARDS --}}
        {{-- ========================================================= --}}

        <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:mb-6!">

            {{-- Total Students --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                <div class="summary-card summary-blue">

                    <div class="ui-card-body tw:text-center!">

                        <small class="summary-label">
                            Total Students
                        </small>

                        <h4 class="summary-value">
                            {{ number_format($totalStudents) }}
                        </h4>

                    </div>

                </div>

            </div>


            {{-- Paid --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                <div class="summary-card summary-green">

                    <div class="ui-card-body tw:text-center!">

                        <small class="summary-label">
                            Paid
                        </small>

                        <h4 class="summary-value">
                            {{ number_format($totalPaid) }}
                        </h4>

                    </div>

                </div>

            </div>


            


            {{-- Unpaid --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                <div class="summary-card summary-red">

                    <div class="ui-card-body tw:text-center!">

                        <small class="summary-label">
                            Unpaid
                        </small>

                        <h4 class="summary-value">
                            {{ number_format($totalUnpaid) }}
                        </h4>

                    </div>

                </div>

            </div>


            {{-- Free Card --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                <div class="summary-card summary-info">

                    <div class="ui-card-body tw:text-center!">

                        <small class="summary-label">
                            Free Card
                        </small>

                        <h4 class="summary-value">
                            {{ number_format($totalFreeCard) }}
                        </h4>

                    </div>

                </div>

            </div>


            {{-- Collection --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[50%] tw:md:flex-[0_0_auto] tw:md:w-[16.66666667%]">

                <div class="summary-card summary-dark">

                    <div class="ui-card-body tw:text-center!">

                        <small class="summary-label">
                            Collection
                        </small>

                        <h4 class="summary-value">

                            {{ number_format($totalCollected, 0) }}

                        </h4>

                        <small class="summary-sub">

                            /
                            {{ number_format($totalExpected, 0) }}

                        </small>


                        <div class="ui-progress tw:mt-2!" style="height: 4px;">

                            <div class="ui-progress-bar tw:bg-bs-success!" style="width: {{ $collectionPercent }}%">
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- MAIN CARD --}}
        {{-- ========================================================= --}}

        <div class="main-card">

            <div class="main-card-header">

                <div>

                    <h4 class="tw:mb-1! tw:font-bold!">
                        Student Payment Details
                    </h4>

                    <p class="tw:mb-0! tw:text-bs-secondary-color!">
                        Monthly collection breakdown by student
                    </p>

                </div>


                <div class="header-actions">

                    <button class="ui-btn ui-btn-outline-primary custom-btn" disabled>

                        <i class="bi bi-funnel tw:mr-1!"></i>

                        Advanced Filter

                    </button>


                    <button class="ui-btn ui-btn-outline-warning custom-btn" disabled>

                        <i class="bi bi-bell tw:mr-1!"></i>

                        Send Reminder

                    </button>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- TABLE --}}
            {{-- ===================================================== --}}

            <div class="ui-table-responsive">

                <table class="ui-table custom-table tw:align-middle! tw:mb-0!">

                    <thead>

                        <tr>

                            <th width="5%">#</th>

                            <th width="10%">Student ID</th>

                            <th width="15%">Student Name</th>

                            <th width="10%">Mobile</th>

                            <th width="16%">Fee Option</th>

                            <th width="10%">Final Fee</th>

                            <th width="10%">Paid Amount</th>

                            <th width="10%">Status</th>

                            <th width="7%">Free Card</th>

                            <th width="7%">Actions</th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($studentsArray as $student)
                            @php

                                $paidAmount = $student['paid_amount'] ?? 0;

                                $finalFee = $student['final_fee'] ?? 0;

                                /*
                                |--------------------------------------------------------------------------
                                | Fee Option
                                |--------------------------------------------------------------------------
                                |
                                | Controller/service can provide:
                                |
                                | fee_option_label
                                |
                                | OR
                                |
                                | class_category_fee_option.label
                                |
                                */

                                $feeOptionLabel = isset($student['fee_option']['label'])
                                    ? $student['fee_option']['label']
                                    : 'Selected Fee Option';

                                $feeOptionFee = isset($student['fee_option']['fee'])
                                    ? (float) $student['fee_option']['fee']
                                    : (float) $finalFee;

                                if (empty($feeOptionLabel)) {
                                    $feeOptionLabel = 'Selected Fee Option';
                                }

                                $feeOptionFee = is_null($feeOptionFee) ? $finalFee : (float) $feeOptionFee;

                            @endphp


                            <tr>

                                {{-- # --}}
                                <td>

                                    {{ $loop->iteration + (request('page', 1) - 1) * request('per_page', 50) }}

                                </td>


                                {{-- Student ID --}}
                                <td>
                                    {{ isset($student['student']['custom_id']) ? $student['student']['custom_id'] : 'N/A' }}
                                </td>


                                {{-- Student Name --}}
                                <td>
                                    <strong>
                                        {{ isset($student['student']['initial_name'])
                                            ? $student['student']['initial_name']
                                            : (isset($student['student']['full_name']) ? $student['student']['full_name'] : 'N/A') }}
                                    </strong>
                                </td>


                                {{-- Mobile --}}
                                <td>
                                    {{ isset($student['student']['mobile']) ? $student['student']['mobile'] : 'N/A' }}
                                </td>


                                {{-- Fee Option --}}
                                <td>

                                    @if ($student['is_free_card'] ?? false)
                                        <span class="ui-badge tw:bg-bs-dark! custom-badge">

                                            <i class="bi bi-ticket-perforated tw:mr-1!"></i>

                                            Free Card

                                        </span>
                                    @else
                                        <div>

                                            <span class="ui-badge tw:bg-bs-primary! custom-badge">

                                                <i class="bi bi-tag tw:mr-1!"></i>

                                                {{ $feeOptionLabel }}

                                            </span>

                                            <small class="tw:block! tw:text-bs-secondary-color! tw:mt-1!">

                                                Option Fee:
                                                LKR {{ number_format($feeOptionFee, 2) }}

                                            </small>

                                        </div>
                                    @endif

                                </td>


                                {{-- Final Fee --}}
                                <td class="tw:text-right!">

                                    @if ($student['is_free_card'] ?? false)
                                        <span class="tw:text-bs-secondary-color!">
                                            LKR 0.00
                                        </span>
                                    @else
                                        <strong>

                                            LKR
                                            {{ number_format($finalFee, 2) }}

                                        </strong>
                                    @endif

                                </td>


                                {{-- Paid Amount --}}
                                <td class="tw:text-right!">

                                    @if ($paidAmount > 0)
                                        <span class="tw:text-bs-success! tw:font-bold!">

                                            LKR
                                            {{ number_format($paidAmount, 2) }}

                                        </span>
                                    @else
                                        <span class="tw:text-bs-danger!">

                                            LKR 0.00

                                        </span>
                                    @endif

                                </td>


                                {{-- Status --}}
                                <td class="tw:text-center!">

                                    @if ($student['is_free_card'] ?? false)
                                        <span class="ui-badge tw:bg-bs-info! custom-badge">

                                            🎫 Free Card

                                        </span>
                                    @elseif($paidAmount >= $finalFee)
                                        <span class="ui-badge tw:bg-bs-success! custom-badge">

                                            ✓ Paid

                                        </span>
                                    
                                        <span class="ui-badge tw:bg-bs-danger! custom-badge">

                                            ✗ Unpaid

                                        </span>
                                    @endif

                                </td>


                                {{-- Free Card --}}
                                <td class="tw:text-center!">

                                    @if ($student['is_free_card'] ?? false)
                                        <span class="ui-badge tw:bg-bs-info! custom-badge">

                                            Yes

                                        </span>
                                    @else
                                        <span class="ui-badge tw:bg-bs-secondary! custom-badge">

                                            No

                                        </span>
                                    @endif

                                </td>


                                {{-- Actions --}}
                                <td class="tw:text-center!">

                                    <div class="tw:flex! tw:justify-center! tw:gap-1! tw:flex-wrap!">

                                        {{-- Payment History --}}
                                        <button type="button"
                                            class="ui-btn ui-btn-sm ui-btn-outline-success action-btn view-payments-btn"
                                            data-student-name="{{ isset($student['student']['initial_name']) ? $student['student']['initial_name'] : (isset($student['student']['full_name']) ? $student['student']['full_name'] : 'Student') }}"
                                            data-payments='@json($student['payments'] ?? [])' title="View Payment History">

                                            <i class="bi bi-receipt"></i>

                                        </button>


                                        {{-- Student Profile --}}
                                        <button type="button" class="ui-btn ui-btn-sm ui-btn-outline-primary action-btn" disabled
                                            title="Future Feature">

                                            <i class="bi bi-person"></i>

                                        </button>


                                        {{-- SMS Reminder --}}
                                        <button type="button" class="ui-btn ui-btn-sm ui-btn-outline-warning action-btn" disabled
                                            title="SMS Reminder - Future Feature">

                                            <i class="bi bi-chat-dots"></i>

                                        </button>


                                        {{-- Receipt --}}
                                        <button type="button" class="ui-btn ui-btn-sm ui-btn-outline-dark action-btn" disabled
                                            title="Receipt Download - Future Feature">

                                            <i class="bi bi-download"></i>

                                        </button>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="10" class="tw:text-center! tw:py-12! tw:text-bs-secondary-color!">

                                    <i class="bi bi-inbox tw:text-[length:calc(1.375rem_+_1.5vw)]! tw:xl:text-[length:2.5rem]! tw:block! tw:mb-2!">
                                    </i>

                                    No students found for this category.

                                </td>

                            </tr>
                        @endforelse

                    </tbody>


                    {{-- ================================================= --}}
                    {{-- TOTAL --}}
                    {{-- ================================================= --}}

                    @if ($totalStudents > 0)
                        <tfoot class="ui-table-light">

                            <tr>

                                <td colspan="5" class="tw:font-bold! tw:text-right!">

                                    Total:

                                </td>


                                <td class="tw:font-bold! tw:text-right!">

                                    LKR
                                    {{ number_format($totalExpected, 2) }}

                                </td>


                                <td class="tw:font-bold! tw:text-right!">

                                    LKR
                                    {{ number_format($totalCollected, 2) }}

                                </td>


                                <td colspan="3"></td>

                            </tr>

                        </tfoot>
                    @endif

                </table>

            </div>


            {{-- ========================================================= --}}
            {{-- PAGINATION --}}
            {{-- ========================================================= --}}

            @if (isset($pagination) && $pagination['total'] > $pagination['per_page'])

                <div class="ui-card-footer tw:bg-bs-white! tw:py-4!">

                    <div class="tw:flex! tw:justify-between! tw:items-center! tw:flex-wrap! tw:gap-2!">

                        <div class="tw:text-bs-secondary-color! tw:text-[.875em]">

                            Showing
                            {{ $paginationFrom }}

                            to
                            {{ $paginationTo }}

                            of
                            {{ $pagination['total'] }}

                            students

                        </div>


                        <nav aria-label="Page navigation">

                            <ul class="ui-pagination ui-pagination-sm tw:mb-0!">

                                {{-- Previous --}}
                                <li
                                    class="ui-page-item {{ $pagination['current_page'] == 1 ? 'disabled' : '' }}">

                                    <a class="ui-page-link"
                                        href="{{ $pagination['current_page'] > 1
                                            ? url()->current() . '?page=' . ($pagination['current_page'] - 1) . '&per_page=' . $pagination['per_page']
                                            : '#' }}">

                                        <i class="bi bi-chevron-left"></i>

                                        Previous

                                    </a>

                                </li>


                                @php

                                    $start = max(1, $pagination['current_page'] - 2);

                                    $end = min($pagination['last_page'], $pagination['current_page'] + 2);

                                @endphp


                                {{-- First Page --}}
                                @if ($start > 1)

                                    <li class="ui-page-item">

                                        <a class="ui-page-link"
                                            href="{{ url()->current() }}?page=1&per_page={{ $pagination['per_page'] }}">

                                            1

                                        </a>

                                    </li>


                                    @if ($start > 2)
                                        <li class="ui-page-item disabled">

                                            <span class="ui-page-link">
                                                ...
                                            </span>

                                        </li>
                                    @endif

                                @endif


                                {{-- Pages --}}
                                @for ($i = $start; $i <= $end; $i++)
                                    <li
                                        class="ui-page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">

                                        <a class="ui-page-link"
                                            href="{{ url()->current() }}?page={{ $i }}&per_page={{ $pagination['per_page'] }}">

                                            {{ $i }}

                                        </a>

                                    </li>
                                @endfor


                                {{-- Last Page --}}
                                @if ($end < $pagination['last_page'])

                                    @if ($end < $pagination['last_page'] - 1)
                                        <li class="ui-page-item disabled">

                                            <span class="ui-page-link">
                                                ...
                                            </span>

                                        </li>
                                    @endif


                                    <li class="ui-page-item">

                                        <a class="ui-page-link"
                                            href="{{ url()->current() }}?page={{ $pagination['last_page'] }}&per_page={{ $pagination['per_page'] }}">

                                            {{ $pagination['last_page'] }}

                                        </a>

                                    </li>

                                @endif


                                {{-- Next --}}
                                <li
                                    class="ui-page-item {{ $pagination['current_page'] == $pagination['last_page'] ? 'disabled' : '' }}">

                                    <a class="ui-page-link"
                                        href="{{ $pagination['current_page'] < $pagination['last_page']
                                            ? url()->current() . '?page=' . ($pagination['current_page'] + 1) . '&per_page=' . $pagination['per_page']
                                            : '#' }}">

                                        Next

                                        <i class="bi bi-chevron-right"></i>

                                    </a>

                                </li>

                            </ul>

                        </nav>

                    </div>

                </div>

            @endif

        </div>


        {{-- ========================================================= --}}
        {{-- PAYMENT HISTORY MODAL --}}
        {{-- ========================================================= --}}

        <div class="ui-modal ui-fade" id="paymentModal" tabindex="-1" aria-hidden="true">

            <div class="ui-modal-dialog ui-modal-lg ui-modal-dialog-centered">

                <div class="ui-modal-content tw:[border:0]! tw:shadow-bs-lg! tw:rounded-bs-xl!">

                    <div class="ui-modal-header tw:bg-bs-success! tw:text-bs-white!">

                        <h5 class="ui-modal-title tw:font-bold!">

                            <i class="bi bi-receipt tw:mr-2!"></i>

                            Payment History -

                            <span id="paymentStudentName"></span>

                        </h5>


                        <button type="button" class="ui-btn-close ui-btn-close-white" data-nx-dismiss="modal">
                        </button>

                    </div>


                    <div class="ui-modal-body">

                        <div class="ui-table-responsive">

                            <table class="ui-table ui-table-sm ui-table-bordered tw:align-middle!">

                                <thead class="ui-table-light">

                                    <tr>

                                        <th>
                                            Payment Date
                                        </th>

                                        <th>
                                            Amount (LKR)
                                        </th>

                                        <th>
                                            Payment Month
                                        </th>

                                        <th>
                                            Method
                                        </th>

                                        <th>
                                            Receipt Number
                                        </th>

                                        <th>
                                            Reference Number
                                        </th>

                                    </tr>

                                </thead>


                                <tbody id="paymentModalBody">

                                    <tr>

                                        <td colspan="6" class="tw:text-center! tw:text-bs-secondary-color! tw:py-6!">

                                            Select a student to view payments

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    <div class="ui-modal-footer">

                        <button type="button" class="ui-btn ui-btn-secondary custom-btn" data-nx-dismiss="modal">

                            Close

                        </button>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- PAYMENT GUIDE --}}
        {{-- ========================================================= --}}

        <div class="tw:mt-6!">

            <div class="ui-alert ui-alert-info custom-alert tw:mb-0!">

                <i class="bi bi-info-circle-fill tw:mr-2!"></i>

                <strong>
                    Payment Status Guide:
                </strong>


                <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1 tw:mt-2!">

                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">

                        <span class="ui-badge tw:bg-bs-success! custom-badge">
                            ✓ Paid
                        </span>

                        <small class="tw:block! tw:text-bs-secondary-color! tw:mt-1!">
                            Full payment completed
                        </small>

                    </div>


                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">

                        <span class="ui-badge tw:bg-bs-info! custom-badge">
                            🎫 Free Card
                        </span>

                        <small class="tw:block! tw:text-bs-secondary-color! tw:mt-1!">
                            Free student (no fee)
                        </small>

                    </div>


                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">

                        <span class="ui-badge tw:bg-bs-danger! custom-badge">
                            ✗ Unpaid
                        </span>

                        <small class="tw:block! tw:text-bs-secondary-color! tw:mt-1!">
                            No payment received
                        </small>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection


{{-- ================================================================ --}}
{{-- STYLES --}}
{{-- ================================================================ --}}

@push('styles')
    <style>
        .category-payment-page {
            animation: fadeIn .4s ease;
        }


        .hero-card,
        .main-card,
        .summary-card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
            border: 1px solid #eef2f7;
            overflow: hidden;
        }


        .hero-card {
            padding: 1.35rem 1.5rem;
            margin-bottom: 1.5rem;
        }


        .hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }


        .hero-actions {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
        }


        .custom-btn {
            border-radius: 14px;
            padding: .7rem 1.15rem;
            font-weight: 600;
            transition: .2s ease;
        }


        .custom-btn:hover:not(:disabled) {
            transform: translateY(-2px);
        }


        .custom-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }


        .summary-card {
            color: #fff;
            min-height: 130px;
        }


        .summary-card .ui-card-body {
            padding: 1rem;
        }


        .summary-label {
            display: block;
            opacity: .9;
            margin-bottom: .35rem;
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: .02em;
        }


        .summary-value {
            margin: 0;
            font-weight: 800;
        }


        .summary-sub {
            opacity: .85;
            font-size: .8rem;
        }


        .summary-blue {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }


        .summary-green {
            background: linear-gradient(135deg, #10b981, #34d399);
        }


        .summary-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
            color: #111827;
        }


        .summary-red {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }


        .summary-info {
            background: linear-gradient(135deg, #0ea5e9, #38bdf8);
        }


        .summary-dark {
            background: linear-gradient(135deg, #0f172a, #334155);
        }


        .main-card {
            padding: 1.5rem;
        }


        .main-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }


        .main-card-header h4 {
            margin: 0;
            font-weight: 700;
        }


        .main-card-header p {
            margin: 0;
            color: #64748b;
        }


        .header-actions {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
        }


        .custom-table thead th {
            border: none;
            background: #f8fafc;
            color: #475569;
            font-size: .82rem;
            text-transform: uppercase;
            padding: 1rem;
            vertical-align: middle;
            white-space: nowrap;
        }


        .custom-table tbody tr {
            transition: .2s ease;
        }


        .custom-table tbody tr:hover {
            background: #f8fafc;
        }


        .custom-table tbody td {
            padding: 1rem;
            border-color: #f1f5f9;
            vertical-align: middle;
        }


        .ui-badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
            font-weight: 600;
        }


        .custom-badge {
            border-radius: 10px;
            padding: .45rem .65rem;
            font-size: .75rem;
            font-weight: 600;
        }


        .action-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .05);
        }


        .action-btn:hover:not(:disabled) {
            transform: translateY(-2px);
        }


        .empty-state {
            text-align: center;
        }


        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }


        .empty-state h5 {
            font-weight: 700;
        }


        .custom-alert {
            border-radius: 18px;
        }


        .ui-progress {
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }


        .ui-progress-bar {
            border-radius: 10px;
            transition: width 0.5s ease;
        }


        .ui-table-responsive {
            border-radius: 20px;
            overflow: auto;
        }


        .ui-pagination {
            gap: .35rem;
        }


        .ui-page-link {
            border: none;
            min-width: 42px;
            height: 42px;
            border-radius: 12px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #334155;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .04);
        }


        .ui-page-item.active .ui-page-link {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }


        .ui-page-link:hover {
            background: #eff6ff;
            color: #2563eb;
        }


        @media (max-width: 768px) {

            .hero-content,
            .main-card-header {
                flex-direction: column;
                align-items: stretch;
            }


            .hero-actions,
            .header-actions {
                width: 100%;
            }


            .hero-actions .ui-btn,
            .header-actions .ui-btn {
                flex: 1;
            }

        }


        @media print {

            .ui-btn,
            .ui-badge,
            .ui-alert,
            .ui-pagination,
            .custom-alert,
            .sidebar,
            .navbar {
                display: none !important;
            }


            body {
                background: #fff !important;
            }


            .hero-card,
            .main-card,
            .summary-card {
                box-shadow: none !important;
            }


            .hero-card,
            .main-card {
                border: 1px solid #ddd !important;
            }

        }
    </style>
@endpush


{{-- ================================================================ --}}
{{-- SCRIPTS --}}
{{-- ================================================================ --}}

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /*
            |--------------------------------------------------------------------------
            | Payment Modal
            |--------------------------------------------------------------------------
            */

            const paymentModalEl =
                document.getElementById('paymentModal');


            const paymentModal =
                new NxUi.Modal(paymentModalEl);


            document
                .querySelectorAll('.view-payments-btn')
                .forEach(function(button) {

                    button.addEventListener('click', function(e) {

                        e.preventDefault();


                        const studentName =
                            this.getAttribute('data-student-name') ||
                            'Student';


                        let payments = [];

                        try {

                            payments = JSON.parse(
                                this.getAttribute('data-payments') ||
                                '[]'
                            );

                        } catch (error) {

                            payments = [];

                        }


                        document
                            .getElementById('paymentStudentName')
                            .textContent = studentName;


                        const modalBody =
                            document.getElementById('paymentModalBody');


                        modalBody.innerHTML = '';


                        if (payments.length > 0) {

                            payments.forEach(function(payment) {

                                const row =
                                    modalBody.insertRow();


                                const paymentDate =
                                    payment.paid_at ||
                                    payment.payment_date ||
                                    'N/A';


                                const amount =
                                    Number(
                                        payment.amount || 0
                                    ).toFixed(2);


                                row.insertCell(0)
                                    .textContent = paymentDate;


                                row.insertCell(1)
                                    .className = 'tw:text-right!';


                                row.insertCell(1)
                                    .textContent =
                                    'LKR ' + amount;


                                row.insertCell(2)
                                    .textContent =
                                    payment.payment_month ||
                                    'N/A';


                                row.insertCell(3)
                                    .textContent =
                                    payment.payment_method ||
                                    'N/A';


                                row.insertCell(4)
                                    .textContent =
                                    payment.receipt_number ||
                                    'N/A';


                                row.insertCell(5)
                                    .textContent =
                                    payment.reference_number ||
                                    'N/A';

                            });

                        } else {

                            const row =
                                modalBody.insertRow();


                            const cell =
                                row.insertCell(0);


                            cell.colSpan = 6;


                            cell.className =
                                'tw:text-center! tw:py-6! tw:text-bs-secondary-color!';


                            cell.innerHTML =
                                '<i class="bi bi-inbox tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]! tw:block! tw:mb-2!"></i>' +
                                'No payment records found.';

                        }


                        paymentModal.show();

                    });

                });


            /*
            |--------------------------------------------------------------------------
            | Per Page
            |--------------------------------------------------------------------------
            */

            const perPageSelect =
                document.getElementById('perPageSelect');


            if (perPageSelect) {

                perPageSelect.addEventListener(
                    'change',
                    function() {

                        const url =
                            new URL(window.location.href);


                        url.searchParams.set(
                            'per_page',
                            this.value
                        );


                        url.searchParams.set(
                            'page',
                            1
                        );


                        window.location.href =
                            url.toString();

                    }
                );

            }

        });
    </script>
@endpush
