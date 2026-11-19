@extends('layouts.app')

@section('title', 'Extra Income Details')
@section('page-title', 'Extra Income Details')

@section('content')

    <div class="extra-income-show-page">

        <!-- HERO -->
        <div class="hero-card tw:mb-6!">
            <div class="hero-content">

                <div>
                    <h3 class="tw:font-bold! tw:mb-1!">Extra Income Details</h3>

                    <p class="tw:text-bs-secondary-color! tw:mb-0!">
                        View full income record information
                    </p>
                </div>

                <div class="hero-actions">

                    <a href="{{ route('admin.extra-incomes.edit', $extraIncome) }}" class="ui-btn ui-btn-warning custom-btn">

                        <i class="bi bi-pencil-square tw:mr-1!"></i>
                        Edit

                    </a>

                    <a href="{{ route('admin.extra-incomes.index') }}" class="ui-btn ui-btn-outline-secondary custom-btn">

                        <i class="bi bi-arrow-left tw:mr-1!"></i>
                        Back

                    </a>

                    <button type="button" class="ui-btn ui-btn-outline-primary custom-btn" onclick="window.print()">

                        <i class="bi bi-printer tw:mr-1!"></i>
                        Print

                    </button>

                    <!-- Future Feature -->
                    <button type="button" class="ui-btn ui-btn-outline-success custom-btn" disabled>

                        <i class="bi bi-download tw:mr-1!"></i>
                        Export PDF

                    </button>

                </div>

            </div>
        </div>

        <!-- SUMMARY -->
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-blue">
                    <div class="ui-card-body">

                        <i class="bi bi-cash-stack summary-icon"></i>

                        <small class="summary-label">Amount</small>

                        <h3 class="summary-value">
                            Rs. {{ number_format($extraIncome->amount, 2) }}
                        </h3>

                        <small class="summary-sub">
                            Total recorded income
                        </small>

                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-green">
                    <div class="ui-card-body">

                        <i class="bi bi-calendar-event summary-icon"></i>

                        <small class="summary-label">Income Date</small>

                        <h3 class="summary-value">
                            {{ $extraIncome->income_date->format('d') }}
                        </h3>

                        <small class="summary-sub">
                            {{ $extraIncome->income_date->format('F Y') }}
                        </small>

                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-warning">
                    <div class="ui-card-body">

                        <i class="bi bi-tags summary-icon"></i>

                        <small class="summary-label">Income Type</small>

                        <h3 class="summary-value">
                            {{ ucfirst($extraIncome->income_type) }}
                        </h3>

                        <small class="summary-sub">
                            Income category
                        </small>

                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-info">
                    <div class="ui-card-body">

                        <i class="bi bi-check-circle summary-icon"></i>

                        <small class="summary-label">Status</small>

                        <h3 class="summary-value">
                            {{ ucfirst($extraIncome->status) }}
                        </h3>

                        <small class="summary-sub">
                            Current payment status
                        </small>

                    </div>
                </div>
            </div>

        </div>

        <!-- DETAILS -->
        <div class="main-card">

            <div class="main-card-header">

                <div>
                    <h4 class="tw:mb-1! tw:font-bold!">
                        Income Information
                    </h4>

                    <p class="tw:mb-0! tw:text-bs-secondary-color!">
                        Detailed information about this income record
                    </p>
                </div>

                <div class="header-badge">
                    Record #{{ $extraIncome->id }}
                </div>

            </div>

            <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">

                <!-- LEFT -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">

                    <div class="detail-card">

                        <h5 class="section-title">
                            <i class="bi bi-info-circle tw:mr-2!"></i>
                            Basic Information
                        </h5>

                        <div class="detail-item">
                            <span class="detail-label">Amount</span>
                            <span class="detail-value tw:font-bold! tw:text-bs-success!">
                                Rs. {{ number_format($extraIncome->amount, 2) }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Income Date</span>
                            <span class="detail-value">
                                {{ $extraIncome->income_date->format('Y-m-d') }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Income Type</span>
                            <span class="detail-value">
                                <span class="ui-badge tw:bg-bs-info! custom-badge">
                                    {{ ucfirst($extraIncome->income_type) }}
                                </span>
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Status</span>

                            <span class="detail-value">

                                @if($extraIncome->status == 'received')
                                    <span class="ui-badge tw:bg-bs-success! custom-badge">
                                        Received
                                    </span>
                                @elseif($extraIncome->status == 'approved')
                                    <span class="ui-badge tw:bg-bs-primary! custom-badge">
                                        Approved
                                    </span>
                                @elseif($extraIncome->status == 'pending')
                                    <span class="ui-badge tw:bg-bs-warning! tw:text-bs-dark! custom-badge">
                                        Pending
                                    </span>
                                @else
                                    <span class="ui-badge tw:bg-bs-danger! custom-badge">
                                        Cancelled
                                    </span>
                                @endif

                            </span>
                        </div>

                    </div>

                </div>

                <!-- RIGHT -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">

                    <div class="detail-card">

                        <h5 class="section-title">
                            <i class="bi bi-person-circle tw:mr-2!"></i>
                            Record Information
                        </h5>

                        <div class="detail-item">
                            <span class="detail-label">Created By</span>
                            <span class="detail-value">
                                {{ $extraIncome->createdBy->name ?? '-' }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Created At</span>
                            <span class="detail-value">
                                {{ $extraIncome->created_at->format('Y-m-d h:i A') }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Last Updated</span>
                            <span class="detail-value">
                                {{ $extraIncome->updated_at->format('Y-m-d h:i A') }}
                            </span>
                        </div>

                        <div class="detail-item">
                            <span class="detail-label">Record ID</span>
                            <span class="detail-value">
                                #{{ $extraIncome->id }}
                            </span>
                        </div>

                    </div>

                </div>

                <!-- FULL WIDTH -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">

                    <div class="detail-card">

                        <h5 class="section-title">
                            <i class="bi bi-chat-left-text tw:mr-2!"></i>
                            Reason & Notes
                        </h5>

                        <div class="tw:mb-6!">

                            <label class="detail-label tw:block! tw:mb-2!">
                                Reason
                            </label>

                            <div class="note-box">
                                {{ $extraIncome->reason ?? '-' }}
                            </div>

                        </div>

                        <div>

                            <label class="detail-label tw:block! tw:mb-2!">
                                Notes
                            </label>

                            <div class="note-box">
                                {{ $extraIncome->note ?? 'No additional notes available.' }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
@endsection

@push('styles')
    <style>
        .extra-income-show-page {
            animation: fadeIn .4s ease;
        }

        .hero-card,
        .main-card,
        .detail-card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
            border: 1px solid #eef2f7;
        }

        .hero-card,
        .main-card {
            padding: 1.5rem;
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
            gap: .75rem;
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

        .summary-card {
            border-radius: 24px;
            overflow: hidden;
            color: #fff;
            position: relative;
            min-height: 150px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .06);
        }

        .summary-card .ui-card-body {
            padding: 1.5rem;
            position: relative;
        }

        .summary-label {
            display: block;
            opacity: .9;
            margin-bottom: .4rem;
            font-size: .85rem;
            text-transform: uppercase;
        }

        .summary-value {
            margin: 0;
            font-weight: 800;
        }

        .summary-sub {
            opacity: .85;
            font-size: .8rem;
        }

        .summary-icon {
            position: absolute;
            right: 18px;
            top: 18px;
            font-size: 2rem;
            opacity: .18;
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

        .summary-info {
            background: linear-gradient(135deg, #0ea5e9, #38bdf8);
        }

        .main-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
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

        .detail-card {
            padding: 1.5rem;
            height: 100%;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: #0f172a;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #64748b;
            font-weight: 600;
        }

        .detail-value {
            text-align: right;
            color: #0f172a;
        }

        .custom-badge {
            border-radius: 10px;
            padding: .45rem .7rem;
            font-size: .75rem;
            font-weight: 600;
        }

        .note-box {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            line-height: 1.7;
            color: #334155;
        }

        @media (max-width: 768px) {

            .hero-content,
            .main-card-header,
            .detail-item {
                flex-direction: column;
                align-items: stretch;
            }

            .detail-value {
                text-align: left;
            }

            .hero-actions {
                width: 100%;
            }

            .hero-actions .ui-btn {
                flex: 1;
            }

        }

        @media print {

            .ui-btn,
            .sidebar,
            .navbar {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .hero-card,
            .main-card,
            .detail-card,
            .summary-card {
                box-shadow: none !important;
            }

        }
    </style>
@endpush