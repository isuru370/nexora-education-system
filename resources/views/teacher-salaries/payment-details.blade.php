@extends('layouts.app')

@section('title', 'Teacher Payment Details')
@section('page-title', 'Teacher Payment Details')

@section('content')

    @php
        $monthName = $month_name ?? \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->format('F');
        $periodLabel = $monthName . ' ' . $year;

        $isPaid = isset($salary_status) && $salary_status === 'paid';
        $data = $data ?? [];
        $totalPayments = $payments_count ?? 0;
        $totalAll = $totals['all'] ?? 0;
        $totalAdvance = $totals['advance'] ?? 0;
        $totalDeduction = $totals['deduction'] ?? 0;
        $totalOther = $totals['other'] ?? 0;
        $totalWelfare = $totals['welfare'] ?? 0;
    @endphp

    <div class="teacher-payment-details-page">

        <!-- HERO -->
        <div class="hero-card tw:mb-6!">
            <div class="hero-content">

                <div class="tw:flex! tw:items-center! tw:gap-4!">
                    <div class="profile-avatar">
                        {{ strtoupper(substr($teacher->full_name, 0, 1)) }}
                    </div>

                    <div>
                        <h3 class="tw:mb-1! tw:font-bold!">{{ $teacher->full_name }}</h3>
                        <p class="tw:mb-0! tw:text-bs-secondary-color!">
                            Payment Details • {{ $periodLabel }}
                            @if ($branch)
                                • <i class="bi bi-building"></i> {{ $branch->name }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="hero-actions">
                    <a href="{{ route('teacher-salaries.show', [
                        'teacher' => $teacher->id,
                        'year' => $year,
                        'month' => $month,
                        'branch_id' => $branch_id,
                    ]) }}"
                        class="ui-btn ui-btn-outline-secondary custom-btn">
                        <i class="bi bi-arrow-left tw:mr-1!"></i>
                        Back to Salary Details
                    </a>
                </div>

            </div>
        </div>

        <!-- STATUS / SUMMARY -->
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-blue">
                    <div class="ui-card-body">
                        <i class="bi bi-receipt-cutoff summary-icon"></i>
                        <h6>Total Payments</h6>
                        <h3>LKR {{ number_format($totalAll, 2) }}</h3>
                        <small class="summary-sub">
                            {{ $totalPayments }} transactions
                        </small>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-orange">
                    <div class="ui-card-body">
                        <i class="bi bi-arrow-up-circle summary-icon"></i>
                        <h6>Advances</h6>
                        <h3>LKR {{ number_format($totalAdvance, 2) }}</h3>
                        <small class="summary-sub">
                            Advance transactions
                        </small>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-red">
                    <div class="ui-card-body">
                        <i class="bi bi-arrow-down-circle summary-icon"></i>
                        <h6>Deductions</h6>
                        <h3>LKR {{ number_format($totalDeduction, 2) }}</h3>
                        <small class="summary-sub">
                            Deduction transactions
                        </small>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-green">
                    <div class="ui-card-body">
                        <i class="bi bi-three-dots summary-icon"></i>
                        <h6>Other</h6>
                        <h3>LKR {{ number_format($totalOther, 2) }}</h3>
                        <small class="summary-sub">
                            Other adjustments
                            @if ($totalWelfare > 0)
                                <br>Welfare: LKR {{ number_format($totalWelfare, 2) }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>

        </div>

        <!-- TEACHER INFO -->
        <div class="info-card tw:mb-6!">
            <div class="section-header">
                <h6 class="tw:mb-0! tw:font-bold!">Teacher Information</h6>
                <span class="section-pill">{{ $periodLabel }}</span>
            </div>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                    <div class="info-box">
                        <small class="tw:text-bs-secondary-color! tw:block!">Teacher Name</small>
                        <div class="tw:font-bold!">{{ $teacher->full_name }}</div>
                    </div>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                    <div class="info-box">
                        <small class="tw:text-bs-secondary-color! tw:block!">Teacher ID</small>
                        <div class="tw:font-bold!">{{ $teacher->custom_id }}</div>
                    </div>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                    <div class="info-box">
                        <small class="tw:text-bs-secondary-color! tw:block!">Salary Status</small>
                        <div>
                            @if ($isPaid)
                                <span class="ui-badge tw:bg-bs-success! custom-badge">Paid</span>
                            @else
                                <span class="ui-badge tw:bg-bs-warning! tw:text-bs-dark! custom-badge">Pending</span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- INCOME BREAKDOWN -->
        <div class="main-card tw:mb-6!">
            <div class="main-card-header">
                <div>
                    <h4>Income Breakdown</h4>
                    <p>Detailed split of class earnings and shares</p>
                </div>
            </div>

            <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                    <div class="mini-stat">
                        <small class="tw:text-bs-secondary-color!">Total Class Income</small>
                        <h5 class="tw:text-bs-primary! tw:mb-0!">LKR {{ number_format($data['total_class_income'] ?? 0, 2) }}</h5>
                    </div>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                    <div class="mini-stat">
                        <small class="tw:text-bs-secondary-color!">Teacher Earnings</small>
                        <h5 class="tw:text-bs-success! tw:mb-0!">LKR {{ number_format($data['teacher_earnings'] ?? 0, 2) }}</h5>
                    </div>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                    <div class="mini-stat">
                        <small class="tw:text-bs-secondary-color!">Institute Earnings</small>
                        <h5 class="tw:text-bs-dark! tw:mb-0!">LKR {{ number_format($data['institute_earnings'] ?? 0, 2) }}</h5>
                    </div>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                    <div class="mini-stat">
                        <small class="tw:text-bs-secondary-color!">Organizer Earnings</small>
                        <h5 class="tw:text-bs-dark! tw:mb-0!">LKR {{ number_format($data['organizer_earnings'] ?? 0, 2) }}</h5>
                    </div>
                </div>

            </div>
        </div>

        <!-- PAYMENT HISTORY -->
        <div class="main-card tw:mb-6!">

            <div class="main-card-header">
                <div>
                    <h4>Transaction History</h4>
                    <p>Advance, deduction, and other payment records</p>
                </div>

                <div class="header-badge">
                    {{ $totalPayments }} Records
                </div>
            </div>

            <div class="ui-table-responsive">
                <table class="ui-table custom-table tw:align-middle! tw:mb-0!">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount (LKR)</th>
                            <th>Reason</th>
                            <th>Note</th>
                            <th class="tw:text-center!">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse(($payments ?? []) as $index => $payment)
                            <tr>
                                <td>{{ $index + 1 }}</td>

                                <td>
                                    {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                                </td>

                                <td>
                                    @if ($payment->payment_type == 'advance')
                                        <span class="ui-badge tw:bg-bs-warning! tw:text-bs-dark! custom-badge">
                                            <i class="bi bi-arrow-up-circle tw:mr-1!"></i>
                                            Advance
                                        </span>
                                    @elseif($payment->payment_type == 'deduction')
                                        <span class="ui-badge tw:bg-bs-danger! custom-badge">
                                            <i class="bi bi-arrow-down-circle tw:mr-1!"></i>
                                            Deduction
                                        </span>
                                    @else
                                        <span class="ui-badge tw:bg-bs-info! custom-badge">
                                            <i class="bi bi-three-dots tw:mr-1!"></i>
                                            Other
                                        </span>
                                    @endif
                                </td>

                                <td class="tw:font-semibold!">
                                    @if ($payment->payment_type == 'advance')
                                        <span class="tw:text-bs-warning!">+ LKR {{ number_format($payment->amount, 2) }}</span>
                                    @elseif($payment->payment_type == 'deduction')
                                        <span class="tw:text-bs-danger!">- LKR {{ number_format($payment->amount, 2) }}</span>
                                    @else
                                        <span class="tw:text-bs-info!">LKR {{ number_format($payment->amount, 2) }}</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $payment->reason ?? '—' }}
                                </td>

                                <td>
                                    @if ($payment->note)
                                        <span class="note-icon" title="{{ $payment->note }}">
                                            <i class="bi bi-info-circle-fill tw:text-bs-secondary-color!"></i>
                                        </span>
                                    @else
                                        <span class="tw:text-bs-secondary-color!">—</span>
                                    @endif
                                </td>

                                <td class="tw:text-center!">
                                    @if ($isPaid)
                                        <button class="ui-btn ui-btn-sm ui-btn-secondary action-btn" disabled
                                            title="Record is locked because salary is paid">
                                            <i class="bi bi-lock-fill tw:mr-1!"></i>
                                            Delete
                                        </button>
                                    @elseif (!auth()->user()->isAdmin())
                                        <button class="ui-btn ui-btn-sm ui-btn-secondary action-btn" disabled
                                            title="Only an Admin can delete this record">
                                            <i class="bi bi-lock-fill tw:mr-1!"></i>
                                            Admin only
                                        </button>
                                    @else
                                        <form method="POST"
                                            action="{{ route('teacher-salaries.payment-delete', $payment->id) }}"
                                            class="tw:[display:inline]!"
                                            onsubmit="return confirm('Are you sure you want to delete this payment record? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="ui-btn ui-btn-sm ui-btn-outline-danger action-btn"
                                                title="Delete payment record">
                                                <i class="bi bi-trash-fill tw:mr-1!"></i>
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="tw:text-center! tw:py-12! tw:text-bs-secondary-color!">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <h5>No payment records found for this month</h5>
                                        <p class="tw:mb-0!">There are no teacher payment adjustments in this period.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($totalPayments > 0)
                        <tfoot>
                            <tr class="ui-table-light">
                                <td colspan="3" class="tw:text-right! tw:font-bold!">Total:</td>
                                <td class="tw:font-bold!">LKR {{ number_format($totalAll, 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- FINAL SUMMARY -->
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                <div class="final-card">
                    <small class="tw:text-bs-secondary-color! tw:block!">Gross Earnings</small>
                    <h4 class="tw:text-bs-success! tw:mb-0!">LKR {{ number_format($data['teacher_earnings'] ?? 0, 2) }}</h4>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                <div class="final-card">
                    <small class="tw:text-bs-secondary-color! tw:block!">Total Deductions</small>
                    <h4 class="tw:text-bs-danger! tw:mb-0!">LKR {{ number_format($totalAdvance + $totalDeduction + $totalOther + $totalWelfare, 2) }}
                    </h4>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                <div class="final-card highlight">
                    <small class="tw:text-bs-secondary-color! tw:block!">Net Payable</small>
                    <h3 class="tw:text-bs-primary! tw:mb-0!">LKR {{ number_format($data['net_payable'] ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>

        <!-- STATUS ALERT -->
        <div class="tw:mt-6!">
            @if ($isPaid)
                <div class="ui-alert ui-alert-secondary custom-alert tw:mb-0!">
                    <i class="bi bi-lock-fill tw:mr-2!"></i>
                    <strong>Records Locked:</strong> This salary has been paid. Payment records cannot be modified or
                    deleted.
                </div>
            @else
                <div class="ui-alert ui-alert-info custom-alert tw:mb-0!">
                    <i class="bi bi-info-circle-fill tw:mr-2!"></i>
                    <strong>Delete Available:</strong> You can delete payment records while salary is unpaid.
                </div>
            @endif
        </div>

    </div>

@endsection

@push('styles')
    <style>
        .teacher-payment-details-page {
            animation: fadeIn .4s ease;
        }

        .hero-card,
        .info-card,
        .main-card,
        .final-card {
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

        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.6rem;
            box-shadow: 0 10px 20px rgba(79, 70, 229, .20);
            flex-shrink: 0;
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

        .summary-orange {
            background: linear-gradient(135deg, #ea580c, #f97316);
        }

        .summary-red {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

        .summary-green {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .section-header,
        .main-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .section-header {
            padding-bottom: 1rem;
            border-bottom: 1px solid #eef2f7;
        }

        .main-card-header h4 {
            margin: 0;
            font-weight: 700;
        }

        .main-card-header p {
            margin: 0;
            color: #64748b;
        }

        .section-pill,
        .header-badge {
            display: inline-flex;
            align-items: center;
            padding: .55rem .9rem;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
            font-size: .85rem;
        }

        .info-box,
        .mini-stat,
        .final-card {
            background: #f8fafc;
            border: 1px solid #eef2f7;
            border-radius: 18px;
            padding: 1rem 1.1rem;
        }

        .mini-stat h5,
        .final-card h4,
        .final-card h3 {
            font-weight: 800;
        }

        .final-card.highlight {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border: 1px solid #bfdbfe;
        }

        .custom-table thead th {
            border: none;
            background: #f8fafc;
            color: #475569;
            font-size: .82rem;
            text-transform: uppercase;
            padding: 1rem;
            white-space: nowrap;
        }

        .custom-table tbody td {
            padding: 1rem;
            border-color: #f1f5f9;
            vertical-align: middle;
        }

        .custom-table tbody tr {
            transition: .2s ease;
        }

        .custom-table tbody tr:hover {
            background: #f8fafc;
        }

        .ui-badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
        }

        .custom-badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
            font-weight: 600;
        }

        .action-btn {
            border-radius: 14px;
            padding: .7rem 1rem;
            font-weight: 600;
        }

        .note-icon {
            cursor: help;
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

        @media (max-width: 768px) {

            .hero-content,
            .section-header,
            .main-card-header {
                flex-direction: column;
                align-items: stretch;
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
            .ui-alert,
            .sidebar,
            .navbar {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .hero-card,
            .info-card,
            .main-card,
            .final-card {
                box-shadow: none !important;
            }
        }
    </style>
@endpush

