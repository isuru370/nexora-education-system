@extends('layouts.app')

@section('content')

<div class="payment-collection-page">

    {{-- ============================================= --}}
    {{-- HEADER SECTION --}}
    {{-- ============================================= --}}
    <div class="page-header-wrapper">
        <div class="page-header">

            <div class="header-left">

                <div class="header-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>

                <div>

                    <h1 class="page-title">
                        Payment Collection Report

                        <span class="title-badge">
                            {{ $report['month'] ?? 'Monthly' }}
                        </span>
                    </h1>

                    <p class="page-subtitle">
                        <i class="bi bi-calendar3 me-1"></i>
                        Monthly student payment collection summary & analytics
                    </p>

                </div>

            </div>


            <div class="header-right">

                {{-- Month Filter --}}
                <form method="GET"
                      action="{{ route('admin.payment-collection-report.index') }}"
                      class="filter-form">

                    <div class="filter-group">

                        <i class="bi bi-calendar-range"></i>

                        <select name="payment_month"
                                class="filter-select"
                                onchange="this.form.submit()">

                            @foreach($availableMonths as $value => $label)

                                <option value="{{ $value }}"
                                    {{ $paymentMonth === $value ? 'selected' : '' }}>

                                    {{ $label }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </form>


                {{-- Export Button --}}
                <a href="{{ route('admin.payment-collection-report.export', ['payment_month' => $paymentMonth]) }}"
                   class="btn-export">

                    <i class="bi bi-file-earmark-excel"></i>

                    <span>Export Excel</span>

                </a>

            </div>

        </div>
    </div>


    {{-- ============================================= --}}
    {{-- SUMMARY STATS CARDS --}}
    {{-- ============================================= --}}
    <div class="stats-grid">

        {{-- Expected --}}
        <div class="stats-card stats-card-expected">

            <div class="stats-card-inner">

                <div class="stats-icon">
                    <i class="bi bi-wallet2"></i>
                </div>

                <div class="stats-content">

                    <span class="stats-label">
                        Expected Collection
                    </span>

                    <h3 class="stats-value">
                        Rs. {{ number_format($report['summary']['expected'], 2) }}
                    </h3>

                    <div class="stats-meta">

                        <span class="meta-badge">

                            <i class="bi bi-people"></i>

                            {{ $report['summary']['student_count'] ?? 0 }}
                            Students

                        </span>

                    </div>

                </div>

            </div>

            <div class="stats-progress-bar">

                <div class="progress-fill"
                     style="width: 100%"></div>

            </div>

        </div>


        {{-- Collected --}}
        <div class="stats-card stats-card-collected">

            <div class="stats-card-inner">

                <div class="stats-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>

                <div class="stats-content">

                    <span class="stats-label">
                        Collected
                    </span>

                    <h3 class="stats-value">
                        Rs. {{ number_format($report['summary']['collected'], 2) }}
                    </h3>

                    <div class="stats-meta">

                        <span class="meta-badge success">

                            <i class="bi bi-graph-up-arrow"></i>

                            {{ number_format($report['summary']['collection_percentage'], 1) }}%
                            Rate

                        </span>

                    </div>

                </div>

            </div>

            <div class="stats-progress-bar">

                <div class="progress-fill"
                     style="width: {{ min($report['summary']['collection_percentage'], 100) }}%"></div>

            </div>

        </div>


        {{-- Due --}}
        <div class="stats-card stats-card-due">

            <div class="stats-card-inner">

                <div class="stats-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>

                <div class="stats-content">

                    <span class="stats-label">
                        Due Amount
                    </span>

                    <h3 class="stats-value">
                        Rs. {{ number_format($report['summary']['due'], 2) }}
                    </h3>

                    <div class="stats-meta">

                        <span class="meta-badge danger">

                            <i class="bi bi-arrow-down"></i>

                            {{ number_format($report['summary']['due_percentage'], 1) }}%
                            Pending

                        </span>

                    </div>

                </div>

            </div>

            <div class="stats-progress-bar">

                <div class="progress-fill"
                     style="width: {{ min($report['summary']['due_percentage'], 100) }}%;
                            background: linear-gradient(90deg, #f59e0b, #ef4444);"></div>

            </div>

        </div>


        {{-- Collection Rate --}}
        <div class="stats-card stats-card-rate">

            <div class="stats-card-inner">

                <div class="stats-icon">
                    <i class="bi bi-pie-chart-fill"></i>
                </div>

                <div class="stats-content">

                    <span class="stats-label">
                        Collection Rate
                    </span>

                    <h3 class="stats-value">
                        {{ number_format($report['summary']['collection_percentage'], 1) }}%
                    </h3>

                    <div class="stats-meta">

                        <span class="meta-badge info">

                            <i class="bi bi-clock-history"></i>

                            {{ $report['month'] ?? 'Current Month' }}

                        </span>

                    </div>

                </div>

            </div>

            <div class="stats-progress-bar">

                <div class="progress-fill"
                     style="width: {{ min($report['summary']['collection_percentage'], 100) }}%;
                            background: linear-gradient(90deg, #8b5cf6, #6d28d9);"></div>

            </div>

        </div>

    </div>


    {{-- ============================================= --}}
    {{-- FINANCIAL SPLIT CARDS --}}
    {{-- ============================================= --}}
    <div class="split-section">

        <div class="split-header">

            <h5 class="split-title">
                <i class="bi bi-people"></i>
                Financial Distribution
            </h5>

            <span class="split-subtitle">

                <i class="bi bi-info-circle"></i>

                Based on collected payments

            </span>

        </div>


        <div class="split-grid">

            {{-- Teacher --}}
            <div class="split-card split-card-teacher">

                <div class="split-card-icon">
                    <i class="bi bi-person-badge"></i>
                </div>

                <div class="split-card-content">

                    <span class="split-label">
                        Teacher Share
                    </span>

                    <h4 class="split-amount">
                        Rs. {{ number_format($report['summary']['teacher_amount'], 2) }}
                    </h4>

                    <span class="split-percentage">

                        {{
                            $report['summary']['collected'] > 0
                                ? number_format(
                                    ($report['summary']['teacher_amount'] /
                                    $report['summary']['collected']) * 100,
                                    1
                                )
                                : 0
                        }}%

                    </span>

                </div>

            </div>


            {{-- Organizer --}}
            <div class="split-card split-card-organizer">

                <div class="split-card-icon">
                    <i class="bi bi-person-workspace"></i>
                </div>

                <div class="split-card-content">

                    <span class="split-label">
                        Organizer Share
                    </span>

                    <h4 class="split-amount">
                        Rs. {{ number_format($report['summary']['organizer_amount'], 2) }}
                    </h4>

                    <span class="split-percentage">

                        {{
                            $report['summary']['collected'] > 0
                                ? number_format(
                                    ($report['summary']['organizer_amount'] /
                                    $report['summary']['collected']) * 100,
                                    1
                                )
                                : 0
                        }}%

                    </span>

                </div>

            </div>


            {{-- Institute --}}
            <div class="split-card split-card-institute">

                <div class="split-card-icon">
                    <i class="bi bi-building"></i>
                </div>

                <div class="split-card-content">

                    <span class="split-label">
                        Institute Share
                    </span>

                    <h4 class="split-amount">
                        Rs. {{ number_format($report['summary']['institution_amount'], 2) }}
                    </h4>

                    <span class="split-percentage">

                        {{
                            $report['summary']['collected'] > 0
                                ? number_format(
                                    ($report['summary']['institution_amount'] /
                                    $report['summary']['collected']) * 100,
                                    1
                                )
                                : 0
                        }}%

                    </span>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================= --}}
    {{-- PROGRESS BAR SECTION --}}
    {{-- ============================================= --}}
    <div class="progress-section">

        <div class="progress-header">

            <div>

                <h6 class="progress-title">

                    <i class="bi bi-bar-chart-fill"></i>

                    Collection Progress

                </h6>

                <small class="text-muted">

                    {{ $report['month'] ?? 'Current Month' }}
                    •
                    {{ $report['summary']['student_count'] ?? 0 }}
                    students enrolled

                </small>

            </div>

            <div class="progress-stats">

                <span class="progress-label">
                    Collected
                </span>

                <strong class="progress-percentage">

                    {{ number_format($report['summary']['collection_percentage'], 1) }}%

                </strong>

            </div>

        </div>


        <div class="progress-track">

            <div class="progress-bar-filled"
                 style="width: {{ min($report['summary']['collection_percentage'], 100) }}%;">

                <span class="progress-value">

                    {{ number_format($report['summary']['collection_percentage'], 1) }}%

                </span>

            </div>

        </div>


        <div class="progress-footer">

            <div class="progress-item">

                <span class="dot collected"></span>

                Collected:

                <strong>
                    Rs. {{ number_format($report['summary']['collected'], 2) }}
                </strong>

            </div>

            <div class="progress-item">

                <span class="dot due"></span>

                Due:

                <strong>
                    Rs. {{ number_format($report['summary']['due'], 2) }}
                </strong>

            </div>

            <div class="progress-item">

                <span class="dot expected"></span>

                Expected:

                <strong>
                    Rs. {{ number_format($report['summary']['expected'], 2) }}
                </strong>

            </div>

        </div>

    </div>


    {{-- ============================================= --}}
    {{-- DETAILED TABLE SECTION --}}
    {{-- ============================================= --}}
    <div class="table-section">

        <div class="table-header">

            <div>

                <h5 class="table-title">

                    <i class="bi bi-table"></i>

                    Class & Category Collection Details

                </h5>

                <p class="table-subtitle">

                    Detailed breakdown of payments by class,
                    grade, category and fee option

                </p>

            </div>


            <div class="table-actions">

                <span class="record-count">

                    <i class="bi bi-list-ul"></i>

                    {{ count($report['rows']) }} records

                </span>


                <a href="{{ route('admin.payment-collection-report.export', ['payment_month' => $paymentMonth]) }}"
                   class="btn-sm-export">

                    <i class="bi bi-file-earmark-excel"></i>

                    Export

                </a>

            </div>

        </div>


        <div class="table-wrapper">

            <table class="report-table">

                <thead>

                    <tr>

                        <th class="col-index">
                            #
                        </th>

                        <th class="col-class">
                            Class
                        </th>

                        <th class="col-grade">
                            Grade
                        </th>

                        <th class="col-category">
                            Category
                        </th>

                        <th class="col-fee-options">
                            Fee Options
                        </th>

                        <th class="col-students text-center">
                            Students
                        </th>

                        <th class="col-expected text-end">
                            Expected
                        </th>

                        <th class="col-collected text-end">
                            Collected
                        </th>

                        <th class="col-due text-end">
                            Due
                        </th>

                        <th class="col-collection text-center">
                            Collection %
                        </th>

                        <th class="col-due-percent text-center">
                            Due %
                        </th>

                        <th class="col-teacher text-end">
                            Teacher
                        </th>

                        <th class="col-organizer text-end">
                            Organizer
                        </th>

                        <th class="col-institute text-end">
                            Institute
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($report['rows'] as $index => $row)

                        <tr class="{{ $row['collection_percentage'] < 50
                            ? 'low-collection'
                            : ($row['collection_percentage'] < 75
                                ? 'medium-collection'
                                : 'high-collection') }}">

                            {{-- Index --}}
                            <td class="col-index">
                                {{ $index + 1 }}
                            </td>


                            {{-- Class --}}
                            <td class="col-class">

                                <div class="class-name">

                                    <span class="class-badge">
                                        {{ substr($row['class_name'], 0, 2) }}
                                    </span>

                                    {{ $row['class_name'] }}

                                </div>

                            </td>


                            {{-- Grade --}}
                            <td class="col-grade">

                                <span class="grade-tag">

                                    {{ $row['grade_name'] ?? 'Unknown Grade' }}

                                </span>

                            </td>


                            {{-- Category --}}
                            <td class="col-category">

                                <span class="category-tag">

                                    {{ $row['category_name'] }}

                                </span>

                            </td>


                            {{-- Fee Options --}}
                            <td class="col-fee-options">

                                @if(
                                    isset($row['fee_options']) &&
                                    is_array($row['fee_options']) &&
                                    count($row['fee_options']) > 0
                                )

                                    <div class="fee-options-list">

                                        @foreach($row['fee_options'] as $option)

                                            <div class="fee-option-item">

                                                <span class="fee-option-label">

                                                    {{ $option['label'] ?? 'Unknown Option' }}

                                                </span>

                                                <span class="fee-option-fee">

                                                    Rs.
                                                    {{ number_format((float) ($option['fee'] ?? 0), 2) }}

                                                </span>

                                            </div>

                                        @endforeach

                                    </div>

                                @else

                                    <span class="text-muted">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Students --}}
                            <td class="col-students text-center">

                                <span class="student-count">

                                    {{ $row['student_count'] }}

                                </span>

                            </td>


                            {{-- Expected --}}
                            <td class="col-expected text-end">

                                <span class="amount expected">

                                    Rs.
                                    {{ number_format($row['expected'], 2) }}

                                </span>

                            </td>


                            {{-- Collected --}}
                            <td class="col-collected text-end">

                                <span class="amount collected">

                                    Rs.
                                    {{ number_format($row['collected'], 2) }}

                                </span>

                            </td>


                            {{-- Due --}}
                            <td class="col-due text-end">

                                <span class="amount due">

                                    Rs.
                                    {{ number_format($row['due'], 2) }}

                                </span>

                            </td>


                            {{-- Collection Percentage --}}
                            <td class="col-collection text-center">

                                <span class="status-badge collection-{{ $row['collection_percentage'] >= 75
                                    ? 'high'
                                    : ($row['collection_percentage'] >= 50
                                        ? 'medium'
                                        : 'low') }}">

                                    {{ number_format($row['collection_percentage'], 1) }}%

                                </span>

                            </td>


                            {{-- Due Percentage --}}
                            <td class="col-due-percent text-center">

                                <span class="status-badge due-{{ $row['due_percentage'] > 50
                                    ? 'high'
                                    : ($row['due_percentage'] > 25
                                        ? 'medium'
                                        : 'low') }}">

                                    {{ number_format($row['due_percentage'], 1) }}%

                                </span>

                            </td>


                            {{-- Teacher --}}
                            <td class="col-teacher text-end">

                                <span class="split-amount teacher">

                                    Rs.
                                    {{ number_format($row['teacher_amount'], 2) }}

                                </span>

                            </td>


                            {{-- Organizer --}}
                            <td class="col-organizer text-end">

                                <span class="split-amount organizer">

                                    Rs.
                                    {{ number_format($row['organizer_amount'], 2) }}

                                </span>

                            </td>


                            {{-- Institute --}}
                            <td class="col-institute text-end">

                                <span class="split-amount institute">

                                    Rs.
                                    {{ number_format($row['institution_amount'], 2) }}

                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="14" class="empty-state">

                                <div class="empty-state-content">

                                    <i class="bi bi-inbox"></i>

                                    <h6>
                                        No payment data found
                                    </h6>

                                    <p>
                                        There are no enrollments for
                                        {{ $report['month'] ?? 'this month' }}.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>


                {{-- TOTAL FOOTER --}}
                @if(count($report['rows']) > 0)

                    <tfoot>

                        <tr class="total-row">

                            <td colspan="5"
                                class="total-label">

                                TOTAL

                            </td>


                            <td class="text-center total-student">

                                {{ $report['summary']['student_count'] }}

                            </td>


                            <td class="text-end total-expected">

                                Rs.
                                {{ number_format($report['summary']['expected'], 2) }}

                            </td>


                            <td class="text-end total-collected">

                                Rs.
                                {{ number_format($report['summary']['collected'], 2) }}

                            </td>


                            <td class="text-end total-due">

                                Rs.
                                {{ number_format($report['summary']['due'], 2) }}

                            </td>


                            <td class="text-center total-collection">

                                {{ number_format($report['summary']['collection_percentage'], 1) }}%

                            </td>


                            <td class="text-center total-due-percent">

                                {{ number_format($report['summary']['due_percentage'], 1) }}%

                            </td>


                            <td class="text-end total-teacher">

                                Rs.
                                {{ number_format($report['summary']['teacher_amount'], 2) }}

                            </td>


                            <td class="text-end total-organizer">

                                Rs.
                                {{ number_format($report['summary']['organizer_amount'], 2) }}

                            </td>


                            <td class="text-end total-institute">

                                Rs.
                                {{ number_format($report['summary']['institution_amount'], 2) }}

                            </td>

                        </tr>

                    </tfoot>

                @endif

            </table>

        </div>

    </div>

</div>


{{-- ============================================= --}}
{{-- STYLES --}}
{{-- ============================================= --}}
<style>

    /* ==========================================
       PAGE CONTAINER
       ========================================== */

    .payment-collection-page {
        animation: fadeIn 0.5s ease;
        padding: 1.5rem;
        max-width: 1600px;
        margin: 0 auto;
    }


    @keyframes fadeIn {

        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }

    }


    /* ==========================================
       HEADER
       ========================================== */

    .page-header-wrapper {
        margin-bottom: 2rem;
    }


    .page-header {

        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;

        background: #ffffff;

        padding: 1.5rem 2rem;

        border-radius: 20px;

        border: 1px solid #f1f5f9;

        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }


    .header-left {

        display: flex;
        align-items: center;
        gap: 1.25rem;

    }


    .header-icon {

        width: 56px;
        height: 56px;

        background:
            linear-gradient(
                135deg,
                #4f46e5,
                #7c3aed
            );

        border-radius: 16px;

        display: flex;
        align-items: center;
        justify-content: center;

        color: #fff;

        font-size: 1.6rem;

        flex-shrink: 0;

        box-shadow:
            0 4px 12px
            rgba(79, 70, 229, 0.3);

    }


    .page-title {

        font-size: 1.5rem;
        font-weight: 800;

        margin: 0;

        display: flex;
        align-items: center;

        gap: 0.75rem;

        color: #0f172a;

    }


    .title-badge {

        font-size: 0.7rem;
        font-weight: 600;

        background: #eef2ff;

        color: #4f46e5;

        padding: 0.2rem 0.8rem;

        border-radius: 20px;

        letter-spacing: 0.3px;

    }


    .page-subtitle {

        margin: 0;

        color: #64748b;

        font-size: 0.9rem;

    }


    .header-right {

        display: flex;

        align-items: center;

        gap: 0.75rem;

        flex-wrap: wrap;

    }


    .filter-form {

        margin: 0;

    }


    .filter-group {

        position: relative;

        background: #f8fafc;

        border-radius: 12px;

        border: 1px solid #e2e8f0;

        transition: all 0.2s ease;

    }


    .filter-group:focus-within {

        border-color: #4f46e5;

        box-shadow:
            0 0 0 3px
            rgba(79, 70, 229, 0.1);

        background: #ffffff;

    }


    .filter-group i {

        position: absolute;

        left: 14px;

        top: 50%;

        transform: translateY(-50%);

        color: #94a3b8;

        pointer-events: none;

        z-index: 2;

    }


    .filter-select {

        min-height: 44px;

        min-width: 200px;

        padding: 0.5rem 2.5rem 0.5rem 2.8rem;

        background: transparent;

        border: none;

        border-radius: 12px;

        font-size: 0.9rem;

        color: #0f172a;

        cursor: pointer;

        appearance: none;

        background-image:
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");

        background-repeat: no-repeat;

        background-position: right 14px center;

        padding-right: 2.5rem;

    }


    .filter-select:focus {

        outline: none;

    }


    .btn-export {

        display: inline-flex;

        align-items: center;

        gap: 0.5rem;

        background:
            linear-gradient(
                135deg,
                #059669,
                #10b981
            );

        color: #ffffff;

        padding: 0.6rem 1.4rem;

        border-radius: 12px;

        text-decoration: none;

        font-weight: 600;

        font-size: 0.9rem;

        border: none;

        transition: all 0.25s ease;

        box-shadow:
            0 2px 8px
            rgba(5, 150, 105, 0.3);

    }


    .btn-export:hover {

        transform: translateY(-2px);

        box-shadow:
            0 4px 16px
            rgba(5, 150, 105, 0.4);

        color: #fff;

    }


    /* ==========================================
       STATS GRID
       ========================================== */

    .stats-grid {

        display: grid;

        grid-template-columns:
            repeat(4, 1fr);

        gap: 1.25rem;

        margin-bottom: 1.5rem;

    }


    .stats-card {

        background: #ffffff;

        border-radius: 20px;

        padding: 1.5rem 1.75rem;

        border: 1px solid #f1f5f9;

        transition:
            all 0.3s
            cubic-bezier(0.4, 0, 0.2, 1);

        position: relative;

        overflow: hidden;

        box-shadow:
            0 1px 3px
            rgba(0,0,0,0.04);

    }


    .stats-card:hover {

        transform: translateY(-4px);

        box-shadow:
            0 12px 32px
            rgba(0,0,0,0.08);

        border-color: transparent;

    }


    .stats-card::before {

        content: '';

        position: absolute;

        top: 0;
        left: 0;
        right: 0;

        height: 4px;

    }


    .stats-card-expected::before {

        background:
            linear-gradient(
                90deg,
                #4f46e5,
                #7c3aed
            );

    }


    .stats-card-collected::before {

        background:
            linear-gradient(
                90deg,
                #059669,
                #34d399
            );

    }


    .stats-card-due::before {

        background:
            linear-gradient(
                90deg,
                #ea580c,
                #f87171
            );

    }


    .stats-card-rate::before {

        background:
            linear-gradient(
                90deg,
                #8b5cf6,
                #6d28d9
            );

    }


    .stats-card-inner {

        display: flex;

        align-items: flex-start;

        gap: 1rem;

        position: relative;

        z-index: 1;

    }


    .stats-icon {

        width: 48px;
        height: 48px;

        border-radius: 14px;

        display: flex;

        align-items: center;
        justify-content: center;

        font-size: 1.3rem;

        flex-shrink: 0;

    }


    .stats-card-expected .stats-icon {

        background: #eef2ff;
        color: #4f46e5;

    }


    .stats-card-collected .stats-icon {

        background: #ecfdf5;
        color: #059669;

    }


    .stats-card-due .stats-icon {

        background: #fff7ed;
        color: #ea580c;

    }


    .stats-card-rate .stats-icon {

        background: #f3e8ff;
        color: #7c3aed;

    }


    .stats-content {

        flex: 1;

        min-width: 0;

    }


    .stats-label {

        font-size: 0.78rem;

        font-weight: 600;

        color: #94a3b8;

        text-transform: uppercase;

        letter-spacing: 0.5px;

        display: block;

    }


    .stats-value {

        font-size: 1.6rem;

        font-weight: 800;

        color: #0f172a;

        margin:
            0.2rem 0 0.3rem;

        line-height: 1.2;

    }


    .stats-meta {

        display: flex;

        gap: 0.5rem;

        flex-wrap: wrap;

    }


    .meta-badge {

        font-size: 0.7rem;

        font-weight: 600;

        padding:
            0.15rem 0.6rem;

        border-radius: 20px;

        background: #f1f5f9;

        color: #475569;

        display: inline-flex;

        align-items: center;

        gap: 0.25rem;

    }


    .meta-badge.success {

        background: #d1fae5;
        color: #065f46;

    }


    .meta-badge.danger {

        background: #fee2e2;
        color: #991b1b;

    }


    .meta-badge.info {

        background: #dbeafe;
        color: #1e40af;

    }


    .stats-progress-bar {

        margin-top: 1rem;

        height: 3px;

        background: #f1f5f9;

        border-radius: 10px;

        overflow: hidden;

        position: relative;

        z-index: 1;

    }


    .progress-fill {

        height: 100%;

        border-radius: 10px;

        background:
            linear-gradient(
                90deg,
                #4f46e5,
                #7c3aed
            );

        transition: width 1s ease;

    }


    /* ==========================================
       SPLIT SECTION
       ========================================== */

    .split-section {

        margin-bottom: 1.5rem;

    }


    .split-header {

        display: flex;

        align-items: center;

        justify-content: space-between;

        margin-bottom: 1rem;

        flex-wrap: wrap;

        gap: 0.5rem;

    }


    .split-title {

        font-weight: 700;

        margin: 0;

        color: #0f172a;

        font-size: 1.1rem;

    }


    .split-title i {

        color: #4f46e5;

    }


    .split-subtitle {

        font-size: 0.8rem;

        color: #94a3b8;

    }


    .split-grid {

        display: grid;

        grid-template-columns:
            repeat(3, 1fr);

        gap: 1.25rem;

    }


    .split-card {

        background: #ffffff;

        border-radius: 16px;

        padding: 1.5rem 1.75rem;

        border: 1px solid #f1f5f9;

        display: flex;

        align-items: center;

        gap: 1.25rem;

        transition:
            all 0.3s ease;

    }


    .split-card:hover {

        transform: translateY(-2px);

        box-shadow:
            0 8px 24px
            rgba(0,0,0,0.06);

    }


    .split-card-icon {

        width: 50px;
        height: 50px;

        border-radius: 14px;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 1.3rem;

        flex-shrink: 0;

    }


    .split-card-teacher .split-card-icon {

        background: #eef2ff;
        color: #4f46e5;

    }


    .split-card-organizer .split-card-icon {

        background: #fef3c7;
        color: #d97706;

    }


    .split-card-institute .split-card-icon {

        background: #dbeafe;
        color: #2563eb;

    }


    .split-card-content {

        flex: 1;

    }


    .split-label {

        font-size: 0.8rem;

        color: #94a3b8;

        font-weight: 500;

        display: block;

    }


    .split-amount {

        font-size: 1.3rem;

        font-weight: 800;

        color: #0f172a;

        margin:
            0.1rem 0 0.2rem;

    }


    .split-percentage {

        font-size: 0.75rem;

        font-weight: 600;

        color: #94a3b8;

        background: #f8fafc;

        padding:
            0.1rem 0.6rem;

        border-radius: 12px;

    }


    /* ==========================================
       PROGRESS SECTION
       ========================================== */

    .progress-section {

        background: #ffffff;

        border-radius: 20px;

        padding: 1.5rem 2rem;

        border: 1px solid #f1f5f9;

        margin-bottom: 1.5rem;

        box-shadow:
            0 1px 3px
            rgba(0,0,0,0.04);

    }


    .progress-header {

        display: flex;

        justify-content: space-between;

        align-items: center;

        margin-bottom: 1rem;

        flex-wrap: wrap;

        gap: 0.5rem;

    }


    .progress-title {

        font-weight: 700;

        margin: 0;

        color: #0f172a;

        font-size: 1rem;

    }


    .progress-title i {

        color: #4f46e5;

    }


    .progress-stats {

        display: flex;

        align-items: center;

        gap: 0.75rem;

    }


    .progress-label {

        font-size: 0.85rem;

        color: #64748b;

    }


    .progress-percentage {

        font-size: 1.2rem;

        font-weight: 800;

        color: #059669;

    }


    .progress-track {

        height: 16px;

        background: #f1f5f9;

        border-radius: 20px;

        overflow: hidden;

        position: relative;

    }


    .progress-bar-filled {

        height: 100%;

        border-radius: 20px;

        background:
            linear-gradient(
                90deg,
                #059669,
                #34d399
            );

        transition:
            width 1.5s ease;

        display: flex;

        align-items: center;

        justify-content: flex-end;

        padding-right: 8px;

        position: relative;

    }


    .progress-value {

        font-size: 0.6rem;

        font-weight: 700;

        color: #fff;

        text-shadow:
            0 1px 2px
            rgba(0,0,0,0.2);

    }


    .progress-footer {

        display: flex;

        justify-content: space-around;

        margin-top: 1rem;

        gap: 1rem;

        flex-wrap: wrap;

    }


    .progress-item {

        display: flex;

        align-items: center;

        gap: 0.5rem;

        font-size: 0.85rem;

        color: #475569;

    }


    .progress-item .dot {

        width: 10px;
        height: 10px;

        border-radius: 50%;

        display: inline-block;

    }


    .dot.collected {
        background: #059669;
    }

    .dot.due {
        background: #ef4444;
    }

    .dot.expected {
        background: #4f46e5;
    }


    /* ==========================================
       TABLE SECTION
       ========================================== */

    .table-section {

        background: #ffffff;

        border-radius: 20px;

        border: 1px solid #f1f5f9;

        overflow: hidden;

        box-shadow:
            0 1px 3px
            rgba(0,0,0,0.04);

    }


    .table-header {

        display: flex;

        justify-content: space-between;

        align-items: center;

        padding: 1.25rem 1.75rem;

        border-bottom:
            1px solid #f1f5f9;

        flex-wrap: wrap;

        gap: 0.75rem;

    }


    .table-title {

        font-weight: 700;

        margin: 0;

        color: #0f172a;

        font-size: 1.05rem;

    }


    .table-title i {

        color: #4f46e5;

    }


    .table-subtitle {

        font-size: 0.85rem;

        color: #94a3b8;

        margin: 0;

    }


    .table-actions {

        display: flex;

        align-items: center;

        gap: 0.75rem;

    }


    .record-count {

        font-size: 0.8rem;

        color: #94a3b8;

        background: #f8fafc;

        padding:
            0.3rem 0.8rem;

        border-radius: 20px;

    }


    .btn-sm-export {

        display: inline-flex;

        align-items: center;

        gap: 0.35rem;

        background: #f8fafc;

        color: #059669;

        padding:
            0.35rem 1rem;

        border-radius: 10px;

        font-size: 0.8rem;

        font-weight: 600;

        text-decoration: none;

        border:
            1px solid #e2e8f0;

        transition:
            all 0.2s ease;

    }


    .btn-sm-export:hover {

        background: #ecfdf5;

        border-color: #059669;

        color: #059669;

    }


    /* ==========================================
       TABLE
       ========================================== */

    .table-wrapper {

        overflow-x: auto;

        padding:
            0 0.25rem 0.25rem;

    }


    .report-table {

        width: 100%;

        min-width: 1450px;

        border-collapse: collapse;

        font-size: 0.88rem;

    }


    .report-table thead th {

        background: #f8fafc;

        color: #475569;

        font-size: 0.7rem;

        font-weight: 700;

        text-transform: uppercase;

        letter-spacing: 0.5px;

        padding:
            0.9rem 1rem;

        white-space: nowrap;

        border-bottom:
            2px solid #e2e8f0;

        position: sticky;

        top: 0;

        z-index: 5;

    }


    .report-table tbody td {

        padding:
            0.85rem 1rem;

        border-bottom:
            1px solid #f1f5f9;

        vertical-align: middle;

    }


    .report-table tbody tr {

        transition:
            all 0.2s ease;

    }


    .report-table tbody tr:hover {

        background: #f8fafc;

    }


    /* ==========================================
       ROW COLOR CODING
       ========================================== */

    .low-collection {

        border-left:
            4px solid #ef4444;

    }


    .medium-collection {

        border-left:
            4px solid #f59e0b;

    }


    .high-collection {

        border-left:
            4px solid #10b981;

    }


    /* ==========================================
       TABLE CELL STYLES
       ========================================== */

    .col-index {

        width: 40px;

        font-weight: 600;

        color: #94a3b8;

    }


    .class-name {

        display: flex;

        align-items: center;

        gap: 0.6rem;

        font-weight: 600;

    }


    .class-badge {

        display: inline-flex;

        align-items: center;

        justify-content: center;

        width: 30px;
        height: 30px;

        background:
            linear-gradient(
                135deg,
                #4f46e5,
                #7c3aed
            );

        color: #fff;

        border-radius: 8px;

        font-size: 0.7rem;

        font-weight: 700;

        flex-shrink: 0;

    }


    /* ==========================================
       GRADE
       ========================================== */

    .grade-tag {

        display: inline-block;

        background: #eff6ff;

        color: #2563eb;

        padding:
            0.25rem 0.75rem;

        border-radius: 20px;

        font-size: 0.78rem;

        font-weight: 600;

        white-space: nowrap;

    }


    /* ==========================================
       CATEGORY
       ========================================== */

    .category-tag {

        display: inline-block;

        background: #f1f5f9;

        color: #334155;

        padding:
            0.25rem 0.8rem;

        border-radius: 20px;

        font-size: 0.8rem;

        font-weight: 600;

        white-space: nowrap;

    }


    /* ==========================================
       FEE OPTIONS
       ========================================== */

    .fee-options-list {

        display: flex;

        flex-direction: column;

        gap: 0.35rem;

        min-width: 190px;

    }


    .fee-option-item {

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 0.75rem;

        background: #f8fafc;

        border:
            1px solid #e2e8f0;

        border-radius: 8px;

        padding:
            0.3rem 0.55rem;

        white-space: nowrap;

    }


    .fee-option-label {

        color: #475569;

        font-size: 0.74rem;

        font-weight: 600;

        overflow: hidden;

        text-overflow: ellipsis;

    }


    .fee-option-fee {

        color: #059669;

        font-size: 0.72rem;

        font-weight: 700;

        flex-shrink: 0;

    }


    .student-count {

        display: inline-block;

        background: #eef2ff;

        color: #4f46e5;

        padding:
            0.1rem 0.8rem;

        border-radius: 20px;

        font-weight: 700;

        font-size: 0.85rem;

    }


    .amount {

        font-weight: 600;

        white-space: nowrap;

    }


    .amount.expected {
        color: #475569;
    }

    .amount.collected {
        color: #059669;
    }

    .amount.due {
        color: #ef4444;
    }


    .status-badge {

        display: inline-block;

        padding:
            0.2rem 0.8rem;

        border-radius: 20px;

        font-size: 0.75rem;

        font-weight: 700;

        min-width: 60px;

    }


    .status-badge.collection-high {

        background: #d1fae5;

        color: #065f46;

    }


    .status-badge.collection-medium {

        background: #fef3c7;

        color: #92400e;

    }


    .status-badge.collection-low {

        background: #fee2e2;

        color: #991b1b;

    }


    .status-badge.due-high {

        background: #fee2e2;

        color: #991b1b;

    }


    .status-badge.due-medium {

        background: #fef3c7;

        color: #92400e;

    }


    .status-badge.due-low {

        background: #d1fae5;

        color: #065f46;

    }


    .split-amount {

        font-weight: 600;

        font-size: 0.85rem;

        white-space: nowrap;

    }


    .split-amount.teacher {
        color: #4f46e5;
    }

    .split-amount.organizer {
        color: #d97706;
    }

    .split-amount.institute {
        color: #2563eb;
    }


    /* ==========================================
       TABLE FOOTER
       ========================================== */

    .total-row {

        background: #f8fafc;

        font-weight: 700;

        border-top:
            2px solid #e2e8f0;

    }


    .total-row td {

        padding: 1rem;

        font-weight: 700;

        color: #0f172a;

    }


    .total-label {

        font-size: 0.9rem;

        letter-spacing: 1px;

        text-transform: uppercase;

        color: #475569 !important;

    }


    .total-student {

        font-size: 1.1rem;

        color: #4f46e5 !important;

    }


    .total-expected {
        color: #475569 !important;
    }

    .total-collected {
        color: #059669 !important;
    }

    .total-due {
        color: #ef4444 !important;
    }

    .total-collection {
        color: #059669 !important;
    }

    .total-due-percent {
        color: #ef4444 !important;
    }

    .total-teacher {
        color: #4f46e5 !important;
    }

    .total-organizer {
        color: #d97706 !important;
    }

    .total-institute {
        color: #2563eb !important;
    }


    /* ==========================================
       EMPTY STATE
       ========================================== */

    .empty-state {

        text-align: center;

        padding:
            3rem 1.5rem;

    }


    .empty-state-content i {

        font-size: 3.5rem;

        color: #cbd5e1;

        display: block;

        margin-bottom: 1rem;

    }


    .empty-state-content h6 {

        font-weight: 700;

        color: #0f172a;

        margin-bottom: 0.25rem;

    }


    .empty-state-content p {

        color: #94a3b8;

        margin: 0;

    }


    /* ==========================================
       RESPONSIVE
       ========================================== */

    @media (max-width: 1200px) {

        .stats-grid {

            grid-template-columns:
                repeat(2, 1fr);

        }


        .split-grid {

            grid-template-columns:
                repeat(2, 1fr);

        }

    }


    @media (max-width: 768px) {

        .payment-collection-page {

            padding: 0.75rem;

        }


        .page-header {

            padding: 1.25rem;

            flex-direction: column;

            align-items: stretch;

        }


        .header-left {

            flex-direction: column;

            text-align: center;

        }


        .header-icon {

            width: 48px;

            height: 48px;

            font-size: 1.2rem;

        }


        .page-title {

            font-size: 1.2rem;

            flex-wrap: wrap;

            justify-content: center;

        }


        .header-right {

            justify-content: stretch;

        }


        .filter-group {

            flex: 1;

        }


        .filter-select {

            min-width: 0;

            width: 100%;

        }


        .btn-export {

            flex: 1;

            justify-content: center;

        }


        .stats-grid {

            grid-template-columns:
                1fr 1fr;

            gap: 0.75rem;

        }


        .stats-card {

            padding: 1rem;

        }


        .stats-value {

            font-size: 1.1rem;

        }


        .split-grid {

            grid-template-columns: 1fr;

            gap: 0.75rem;

        }


        .split-card {

            padding: 1rem;

        }


        .progress-section {

            padding: 1.25rem;

        }


        .progress-footer {

            flex-direction: column;

            align-items: flex-start;

            gap: 0.4rem;

        }


        .table-header {

            flex-direction: column;

            align-items: stretch;

            padding: 1rem;

        }


        .table-actions {

            justify-content: stretch;

        }


        .report-table thead th {

            font-size: 0.6rem;

            padding: 0.6rem 0.5rem;

        }


        .report-table tbody td {

            padding: 0.6rem 0.5rem;

            font-size: 0.8rem;

        }


        .class-badge {

            display: none;

        }

    }


    @media (max-width: 480px) {

        .stats-grid {

            grid-template-columns: 1fr;

            gap: 0.6rem;

        }


        .stats-card {

            padding: 0.85rem;

        }


        .stats-icon {

            width: 40px;

            height: 40px;

            font-size: 1rem;

        }


        .stats-value {

            font-size: 1rem;

        }


        .report-table {

            font-size: 0.75rem;

        }


        .report-table thead th,
        .report-table tbody td {

            padding:
                0.4rem 0.4rem;

        }


        .category-tag,
        .grade-tag {

            font-size: 0.65rem;

            padding:
                0.1rem 0.5rem;

        }


        .status-badge {

            font-size: 0.6rem;

            padding:
                0.1rem 0.5rem;

            min-width: 40px;

        }


        .amount {

            font-size: 0.75rem;

        }

    }


    /* ==========================================
       PRINT STYLES
       ========================================== */

    @media print {

        .btn-export,
        .btn-sm-export,
        .filter-form {

            display: none !important;

        }


        .stats-card {

            break-inside: avoid;

            border:
                1px solid #ddd !important;

            box-shadow:
                none !important;

        }


        .report-table {

            font-size: 0.7rem;

        }


        .report-table thead th {

            background: #e9ecef !important;

        }

    }

</style>

@endsection