@extends('layouts.app')

@section('title', 'Class-wise Payment Summary')
@section('page-title', 'Class-wise Payment Summary')

@section('content')

    @php
        $teacherTotal = $data['teacher_total'] ?? 0;
        $totalStudentCount = $data['total_student_count'] ?? 0;
        $totalClassCount = $data['total_class_count'] ?? 0;

        $reportMonthName = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
    @endphp


    <div class="class-payment-summary-page">

        <!-- HERO -->
        <div class="hero-card tw:mb-6!">

            <div class="hero-content">

                <div>

                    <a href="{{ route('teacher-salaries.show', ['teacher' => $teacherId, 'year' => $year, 'month' => $month, 'branch_id' => $branchId]) }}"
                        class="ui-btn ui-btn-outline-secondary custom-btn tw:mb-4!">

                        <i class="bi bi-arrow-left tw:mr-1!"></i>

                        Back to Salary Report

                    </a>


                    <h3 class="tw:font-bold! tw:mb-1!">
                        Class-wise Payment Summary
                    </h3>


                    <p class="tw:text-bs-secondary-color! tw:mb-0!">

                        Payment details for {{ $reportMonthName }}

                    </p>

                </div>


                <div class="hero-actions">

                    <button onclick="window.print()" class="ui-btn ui-btn-primary custom-btn">

                        <i class="bi bi-printer tw:mr-1!"></i>

                        Print Report

                    </button>

                </div>

            </div>

        </div>


        <!-- STATS -->

        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="summary-card summary-blue">

                    <div class="ui-card-body">

                        <i class="bi bi-cash-stack summary-icon"></i>

                        <h6>
                            Teacher Total
                        </h6>

                        <h3>
                            LKR {{ number_format($teacherTotal, 2) }}
                        </h3>

                        <small class="summary-sub">

                            Teacher income for
                            {{ \Carbon\Carbon::create($year, $month, 1)->format('F') }}

                        </small>

                    </div>

                </div>

            </div>


            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="summary-card summary-green">

                    <div class="ui-card-body">

                        <i class="bi bi-people-fill summary-icon"></i>

                        <h6>
                            Total Students
                        </h6>

                        <h3>
                            {{ number_format($totalStudentCount) }}
                        </h3>

                        <small class="summary-sub">

                            Across {{ $totalClassCount }} classes

                        </small>

                    </div>

                </div>

            </div>


            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="summary-card summary-orange">

                    <div class="ui-card-body">

                        <i class="bi bi-grid-3x3-gap-fill summary-icon"></i>

                        <h6>
                            Total Classes
                        </h6>

                        <h3>
                            {{ number_format($totalClassCount) }}
                        </h3>

                        <small class="summary-sub">
                            Active classes
                        </small>

                    </div>

                </div>

            </div>


            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">

                <div class="summary-card summary-primary">

                    <div class="ui-card-body">

                        <i class="bi bi-calendar-event summary-icon"></i>

                        <h6>
                            Reporting Month
                        </h6>

                        <h3>
                            {{ \Carbon\Carbon::create($year, $month, 1)->format('M Y') }}
                        </h3>

                        <small class="summary-sub">
                            Payment summary
                        </small>

                    </div>

                </div>

            </div>

        </div>


        <!-- MAIN CARD -->

        <div class="main-card">

            <div class="main-card-header">

                <div>

                    <h4>
                        Class Breakdown
                    </h4>

                    <p>
                        Monthly payment summary by class and category
                    </p>

                </div>


                <div class="header-badge">

                    {{ count($data['classes'] ?? []) }} Classes

                </div>

            </div>


            <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3" id="classesGrid">

                @forelse($data['classes'] ?? [] as $class)

                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">

                        <div class="class-card tw:h-full!">


                            <!-- CLASS HEADER -->

                            <div class="class-card-header">

                                <div>

                                    <h5 class="tw:font-bold! tw:mb-1!">

                                        {{ $class['class_name'] }}

                                    </h5>


                                    @if (!empty($class['grade_name']))
                                        <div class="tw:text-bs-secondary-color! tw:font-semibold!">

                                            Grade:
                                            {{ $class['grade_name'] }}

                                        </div>
                                    @endif


                                    <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mb-0! tw:mt-1!">

                                        <i class="bi bi-people-fill tw:mr-1!"></i>

                                        Total Students:
                                        {{ $class['total_students'] }}

                                    </p>

                                </div>


                                <span class="ui-badge tw:bg-bs-primary! custom-badge">

                                    LKR {{ number_format($class['class_total'], 2) }}

                                </span>

                            </div>


                            <!-- CATEGORIES -->

                            @if (!empty($class['categories']) && count($class['categories']) > 0)
                                <div class="tw:mb-4!">

                                    <small class="tw:text-bs-secondary-color! tw:block! tw:mb-2! tw:font-semibold!">

                                        Categories Breakdown

                                    </small>


                                    <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1">

                                        @foreach ($class['categories'] as $category)
                                            @php

                                                /*
                                                |--------------------------------------------------------------------------
                                                | NEW FEE OPTION ARCHITECTURE
                                                |--------------------------------------------------------------------------
                                                */

                                                $feeOptions = $category['fee_options'] ?? [];

                                                $expectedTotal = (float) ($category['expected_total'] ?? 0);

                                                $categoryTotal = (float) ($category['category_total'] ?? 0);

                                                $collectedPercent =
                                                    $expectedTotal > 0 ? ($categoryTotal / $expectedTotal) * 100 : 0;

                                                if ($collectedPercent > 100) {
                                                    $collectedPercent = 100;
                                                }

                                            @endphp


                                            <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[100%]">

                                                <div class="category-item">


                                                    <!-- CATEGORY NAME -->

                                                    <div class="tw:flex! tw:justify-between! tw:items-center! tw:mb-2!">

                                                        <strong class="tw:text-[.875em]">

                                                            {{ $category['category_name'] }}

                                                        </strong>


                                                        <span class="ui-badge tw:bg-bs-secondary! custom-badge">

                                                            {{ $category['student_count'] }}
                                                            Students

                                                        </span>

                                                    </div>


                                                    <!-- PAYMENT STATUS -->

                                                    <div class="tw:flex! tw:flex-wrap! tw:gap-1! tw:mb-2!">


                                                        @if (($category['paid_students'] ?? 0) > 0)
                                                            <span class="ui-badge tw:bg-bs-success! custom-badge">

                                                                <i class="bi bi-check-circle-fill tw:mr-1!"></i>

                                                                Paid:
                                                                {{ $category['paid_students'] }}

                                                            </span>
                                                        @endif


                                                        @if (($category['partial_students'] ?? 0) > 0)
                                                            <span class="ui-badge tw:bg-bs-warning! tw:text-bs-dark! custom-badge">

                                                                <i class="bi bi-exclamation-triangle-fill tw:mr-1!"></i>

                                                                Partial:
                                                                {{ $category['partial_students'] }}

                                                            </span>
                                                        @endif


                                                        @if (($category['free_card_students'] ?? 0) > 0)
                                                            <span class="ui-badge tw:bg-bs-info! custom-badge">

                                                                <i class="bi bi-gift-fill tw:mr-1!"></i>

                                                                Free:
                                                                {{ $category['free_card_students'] }}

                                                            </span>
                                                        @endif


                                                        @if (($category['unpaid_students'] ?? 0) > 0)
                                                            <span class="ui-badge tw:bg-bs-danger! custom-badge">

                                                                <i class="bi bi-x-circle-fill tw:mr-1!"></i>

                                                                Unpaid:
                                                                {{ $category['unpaid_students'] }}

                                                            </span>
                                                        @endif

                                                    </div>


                                                    <!-- FEE OPTIONS -->

                                                    @if (count($feeOptions) > 0)
                                                        <div class="fee-options-box tw:mb-2!">

                                                            <div class="tw:text-[.875em] tw:text-bs-secondary-color! tw:font-semibold! tw:mb-2!">

                                                                Fee Options

                                                            </div>


                                                            @foreach ($feeOptions as $option)
                                                                <div class="fee-option-row">

                                                                    <span>

                                                                        {{ $option['label'] ?? 'Fee Option' }}

                                                                    </span>


                                                                    <strong>

                                                                        LKR
                                                                        {{ number_format((float) ($option['fee'] ?? 0), 2) }}

                                                                    </strong>

                                                                </div>
                                                            @endforeach

                                                        </div>
                                                    @else
                                                        <div class="ui-alert ui-alert-warning tw:py-2! tw:px-4! tw:mb-2!">

                                                            <small>

                                                                No active fee options found.

                                                            </small>

                                                        </div>
                                                    @endif


                                                    <!-- COLLECTION -->

                                                    <div class="tw:flex! tw:justify-between! tw:text-[.875em] tw:text-bs-secondary-color!">

                                                        <span>

                                                            Expected:
                                                            LKR {{ number_format($expectedTotal, 2) }}

                                                        </span>


                                                        <span class="tw:text-bs-success! tw:font-semibold!">

                                                            Collected:
                                                            LKR {{ number_format($categoryTotal, 2) }}

                                                        </span>

                                                    </div>


                                                    <!-- PROGRESS -->

                                                    <div class="ui-progress tw:mt-2!" style="height: 6px;">

                                                        <div class="ui-progress-bar tw:bg-bs-success!" role="progressbar"
                                                            style="width: {{ $collectedPercent }}%">

                                                        </div>

                                                    </div>


                                                    <!-- VIEW STUDENTS -->

                                                    <div class="tw:mt-4!">

                                                        <a href="{{ route('student-class-enrollments.category-wise-payment', [
                                                            'class' => $class['class_id'],
                                                            'classCategoryFee' => $category['category_fee_id'],
                                                            'year' => $year,
                                                            'month' => $month,
                                                        ]) }}"
                                                            class="ui-btn ui-btn-outline-info ui-btn-sm tw:w-full! custom-btn">

                                                            <i class="bi bi-eye tw:mr-1!"></i>

                                                            View
                                                            {{ $category['category_name'] }}
                                                            Students

                                                        </a>

                                                    </div>


                                                </div>

                                            </div>
                                        @endforeach

                                    </div>

                                </div>
                            @else
                                <div class="ui-alert ui-alert-light tw:text-center! tw:py-4! tw:mb-4!">

                                    <i class="bi bi-folder2-open tw:mr-1!"></i>

                                    <small class="tw:text-bs-secondary-color! tw:block!">

                                        No categories assigned

                                    </small>

                                </div>
                            @endif


                            <!-- CLASS COLLECTION -->

                            @php

                                $totalPaid = collect($class['categories'] ?? [])->sum('paid_students');

                                $totalPartial = collect($class['categories'] ?? [])->sum('partial_students');

                                $totalWithPayment = $totalPaid + $totalPartial;

                                $classStudentCount = (int) ($class['total_students'] ?? 0);

                                $paidPercent =
                                    $classStudentCount > 0 ? ($totalWithPayment / $classStudentCount) * 100 : 0;

                                if ($paidPercent > 100) {
                                    $paidPercent = 100;
                                }
                            @endphp


                            <div class="class-footer">

                                <div class="tw:flex! tw:justify-between! tw:text-[.875em] tw:mb-1!">

                                    <span class="tw:font-semibold!">

                                        Overall Collection Rate

                                    </span>


                                    <span class="tw:font-semibold!">

                                        {{ number_format($paidPercent, 1) }}%

                                    </span>

                                </div>


                                <div class="ui-progress" style="height: 8px;">

                                    <div class="ui-progress-bar tw:bg-bs-success!" role="progressbar"
                                        style="width: {{ $paidPercent }}%">

                                    </div>

                                </div>


                                <div class="tw:flex! tw:justify-between! tw:text-[.875em] tw:mt-2! tw:flex-wrap! tw:gap-2!">

                                    <span class="tw:text-bs-success!">

                                        <i class="bi bi-check-circle-fill tw:mr-1!"></i>

                                        Paid:
                                        {{ $totalPaid }}

                                    </span>


                                    <span class="tw:text-bs-warning!">

                                        <i class="bi bi-exclamation-triangle-fill tw:mr-1!"></i>

                                        Partial:
                                        {{ $totalPartial }}

                                    </span>


                                    <span class="tw:text-bs-danger!">

                                        <i class="bi bi-x-circle-fill tw:mr-1!"></i>

                                        Unpaid:
                                        {{ max($classStudentCount - $totalWithPayment, 0) }}

                                    </span>

                                </div>

                            </div>


                        </div>

                    </div>


                @empty

                    <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">

                        <div class="empty-card">

                            <i class="bi bi-inbox"></i>

                            <h5>
                                No active classes found for this teacher
                            </h5>

                            <p class="tw:mb-0! tw:text-bs-secondary-color!">

                                There are no class records available for
                                the selected period.

                            </p>

                        </div>

                    </div>
                @endforelse

            </div>


            <!-- GUIDE -->

            <div class="tw:mt-6!">

                <div class="ui-alert ui-alert-info custom-alert tw:mb-0!">

                    <i class="bi bi-info-circle-fill tw:mr-2!"></i>

                    <strong>
                        Payment Status Guide:
                    </strong>


                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:mt-4!">


                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">

                            <span class="ui-badge tw:bg-bs-success! custom-badge">

                                ✓ Paid

                            </span>

                            <small class="tw:block! tw:text-bs-secondary-color! tw:mt-1!">

                                Full payment completed

                            </small>

                        </div>


                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">

                            <span class="ui-badge tw:bg-bs-warning! tw:text-bs-dark! custom-badge">

                                ⚠ Partial

                            </span>

                            <small class="tw:block! tw:text-bs-secondary-color! tw:mt-1!">

                                Partial payment received

                            </small>

                        </div>


                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">

                            <span class="ui-badge tw:bg-bs-info! custom-badge">

                                🎫 Free Card

                            </span>

                            <small class="tw:block! tw:text-bs-secondary-color! tw:mt-1!">

                                Free student (no fee)

                            </small>

                        </div>


                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%]">

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

    </div>

@endsection


@push('styles')
    <style>
        .class-payment-summary-page {
            animation: fadeIn .4s ease;
        }


        .hero-card,
        .main-card,
        .empty-card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
            border: 1px solid #eef2f7;
            padding: 1.35rem 1.5rem;
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


        .custom-btn:hover {
            transform: translateY(-2px);
        }


        .summary-card {
            border-radius: 24px;
            overflow: hidden;
            color: #fff;
            position: relative;
            min-height: 145px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .06);
            border: none;
        }


        .summary-card .ui-card-body {
            padding: 1.4rem;
            position: relative;
        }


        .summary-card h6 {
            opacity: .9;
            margin-bottom: .6rem;
        }


        .summary-card h3 {
            margin: 0;
            font-weight: 800;
            word-break: break-word;
        }


        .summary-sub {
            opacity: .85;
        }


        .summary-icon {
            position: absolute;
            right: 18px;
            top: 18px;
            font-size: 2rem;
            opacity: .18;
        }


        .summary-blue {
            background: linear-gradient(135deg, #0f172a, #334155);
        }


        .summary-green {
            background: linear-gradient(135deg, #10b981, #34d399);
        }


        .summary-orange {
            background: linear-gradient(135deg, #ea580c, #f97316);
        }


        .summary-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }


        .main-card {
            padding: 1.5rem;
        }


        .main-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
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


        .header-badge {
            display: inline-flex;
            align-items: center;
            padding: .55rem .9rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
        }


        .class-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid #eef2f7;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .04);
            transition: .2s ease;
            padding: 1.25rem;
            height: 100%;
        }


        .class-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .08);
        }


        .class-card-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 1rem;
        }


        .category-item {
            transition: all 0.2s ease;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: .85rem;
        }


        .category-item:hover {
            background-color: #eef2f7;
            transform: translateX(2px);
        }


        /* NEW FEE OPTION UI */

        .fee-options-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: .65rem .75rem;
        }


        .fee-option-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            padding: .45rem .5rem;
            border-bottom: 1px dashed #e2e8f0;
            font-size: .8rem;
        }


        .fee-option-row:last-child {
            border-bottom: none;
        }


        .fee-option-row strong {
            white-space: nowrap;
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


        .custom-badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
            font-weight: 600;
        }


        .class-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eef2f7;
        }


        .empty-card {
            text-align: center;
            padding: 4rem 1rem;
        }


        .empty-card i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }


        .empty-card h5 {
            font-weight: 700;
        }


        .custom-alert {
            border-radius: 18px;
        }


        @media (max-width: 768px) {

            .hero-content {
                flex-direction: column;
                align-items: stretch;
            }


            .hero-actions {
                width: 100%;
            }


            .hero-actions .ui-btn {
                flex: 1;
            }


            .class-card-header {
                flex-direction: column;
                align-items: stretch;
            }

        }


        @media print {

            .ui-btn,
            .ui-alert,
            .sidebar,
            .navbar {
                display: none !important;
            }


            body {
                background: #fff !important;
            }


            .hero-card,
            .main-card,
            .class-card,
            .empty-card {
                box-shadow: none !important;
            }

        }
    </style>
@endpush
