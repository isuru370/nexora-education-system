@extends('layouts.app')

@section('title', 'Teacher Salary Report')
@section('page-title', 'Teacher Salary Report')

@section('content')

    @php
        $teacher = $data['teacher'];
        $selectedYear = $year;
        $selectedMonth = $month;
        $monthLabel = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y');

        $isPaid = ($data['salary_status'] ?? 'pending') === 'paid';
        $deductionTotal = $data['total_deductions'] ?? 0;
        $availableLimit = (float) ($data['available_limit'] ?? 0);

        // Same route parameters for every link on this page
        $routeParams = [
            'teacher' => $teacher->id,
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'branch_id' => $branchId,
        ];
    @endphp

    <div class="salary-details-page">

        <!-- HERO -->
        <div class="hero-card tw:mb-6!">
            <div class="hero-content">

                <div class="tw:flex! tw:items-center! tw:gap-4!">
                    <div class="profile-avatar">
                        {{ strtoupper(substr($teacher->full_name, 0, 1)) }}
                    </div>

                    <div>
                        <h3 class="tw:mb-1! tw:font-bold!">
                            {{ $teacher->full_name }}
                        </h3>
                        <p class="tw:mb-0! tw:text-bs-secondary-color!">
                            Salary Report • {{ $monthLabel }}
                            @if ($data['branch'])
                                • <i class="bi bi-building"></i> {{ $data['branch']->name }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="hero-actions">
                    <a href="{{ route('teacher-salaries.index', ['year' => $selectedYear, 'month' => $selectedMonth, 'branch_id' => $branchId]) }}"
                        class="ui-btn ui-btn-outline-secondary custom-btn">
                        <i class="bi bi-arrow-left tw:mr-1!"></i>
                        Back
                    </a>

                    <a href="{{ route('teacher-salaries.show', $routeParams) }}"
                        class="ui-btn ui-btn-primary custom-btn">
                        <i class="bi bi-arrow-clockwise tw:mr-1!"></i>
                        Refresh
                    </a>
                </div>

            </div>
        </div>

        <!-- FILTER CARD -->
        <div class="filter-card tw:mb-6!">
            <form method="GET"
                action="{{ route('teacher-salaries.show', ['teacher' => $teacher->id, 'year' => $selectedYear, 'month' => $selectedMonth]) }}"
                class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-end!">

                @if ($branches->count() > 1)
                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                        <label class="ui-form-label tw:font-semibold!">Branch</label>
                        <select name="branch_id" class="ui-form-select custom-input">
                            @foreach ($branches as $branchOption)
                                <option value="{{ $branchOption->id }}" {{ (int) $branchId === (int) $branchOption->id ? 'selected' : '' }}>
                                    {{ $branchOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full" type="hidden" name="branch_id" value="{{ $branchId }}">
                @endif

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                    <label class="ui-form-label tw:font-semibold!">Year</label>
                    <select name="year" class="ui-form-select custom-input">
                        @for($y = now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                    <label class="ui-form-label tw:font-semibold!">Month</label>
                    <select name="month" class="ui-form-select custom-input">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                    <button type="submit" class="ui-btn ui-btn-primary tw:w-full! custom-btn">
                        <i class="bi bi-search tw:mr-1!"></i>
                        Search
                    </button>
                </div>
            </form>
        </div>

        <!-- VALIDATION ERRORS -->
        @if ($errors->any())
            <div class="ui-alert ui-alert-danger custom-alert tw:mb-6!">
                <i class="bi bi-exclamation-triangle-fill tw:mr-2!"></i>
                <strong>Please fix the following:</strong>
                <ul class="tw:mb-0! tw:mt-2!">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- SUMMARY -->
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-blue">
                    <div class="ui-card-body">
                        <i class="bi bi-building summary-icon"></i>
                        <h6>Class Income</h6>
                        <h3>
                            LKR {{ number_format($data['total_class_income'] ?? 0, 2) }}
                        </h3>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-green">
                    <div class="ui-card-body">
                        <i class="bi bi-cash-stack summary-icon"></i>
                        <h6>Teacher Earnings</h6>
                        <h3>
                            LKR {{ number_format($data['teacher_earnings'] ?? 0, 2) }}
                        </h3>
                        @if (($data['carried'] ?? 0) > 0)
                            <small class="summary-sub tw:block!">
                                + LKR {{ number_format($data['carried'], 2) }} balance from
                                {{ collect(array_keys($data['carried_months'] ?? []))->map(fn ($p) => \Carbon\Carbon::parse($p . '-01')->format('M Y'))->implode(', ') ?: 'earlier months' }}
                            </small>
                        @endif
                        @if (($data['paid_after_salary'] ?? 0) > 0)
                            <small class="summary-sub tw:block!">
                                LKR {{ number_format($data['paid_after_salary'], 2) }} paid after this salary: goes to the next salary
                            </small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-red">
                    <div class="ui-card-body">
                        <i class="bi bi-dash-circle summary-icon"></i>
                        <h6>Deductions</h6>
                        <h3>
                            LKR {{ number_format($deductionTotal, 2) }}
                        </h3>
                        @if (($data['welfare'] ?? 0) > 0)
                            <small class="summary-sub">incl. welfare LKR {{ number_format($data['welfare'], 2) }}</small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="summary-card summary-primary">
                    <div class="ui-card-body">
                        <i class="bi bi-wallet2 summary-icon"></i>
                        <h6>Net Payable</h6>
                        <h3>
                            LKR {{ number_format($data['net_payable'] ?? 0, 2) }}
                        </h3>
                    </div>
                </div>
            </div>

        </div>

        <!-- STATUS -->
        <div class="status-card tw:mb-6!">
            <div class="tw:flex! tw:justify-between! tw:items-center! tw:flex-wrap! tw:gap-4!">
                <div>
                    <h5 class="tw:mb-1! tw:font-bold!">Payment Status</h5>

                    @if($isPaid)
                        <span class="ui-badge tw:bg-bs-success! custom-badge">Paid</span>
                        <div class="tw:mt-2! tw:text-bs-success! tw:font-semibold!">
                            Paid Amount: LKR {{ number_format($data['salary_paid'] ?? 0, 2) }}
                            @if ($data['paid_at'])
                                <span class="tw:text-bs-secondary-color! tw:font-normal!">
                                    on {{ $data['paid_at']->format('d M Y, h:i A') }}
                                </span>
                            @endif
                        </div>
                    @else
                        <span class="ui-badge tw:bg-bs-warning! tw:text-bs-dark! custom-badge">Pending</span>
                    @endif
                </div>

                <div class="tw:text-[.875em] tw:text-bs-secondary-color!">
                    {{ $monthLabel }}
                </div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="main-card tw:mb-6!">
            <div class="main-card-header">
                <div>
                    <h4>Salary Actions</h4>
                    <p>Process salary, print slip, or record advance and deduction</p>
                </div>

                <div class="header-badge">
                    {{ $isPaid ? 'PAID' : 'PENDING' }}
                </div>
            </div>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-stretch!">

                <!-- PAY -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                    @if($isPaid)
                        <button class="ui-btn ui-btn-outline-success action-btn-full" disabled>
                            <i class="bi bi-check-circle-fill tw:mr-1!"></i>
                            Salary Paid
                        </button>
                    @elseif($data['can_pay'])
                        <form method="POST" action="{{ route('teacher-salaries.pay', $teacher->id) }}"
                            onsubmit="return confirm('Pay LKR {{ number_format($data['net_payable'], 2) }} to {{ addslashes($teacher->full_name) }} for {{ $monthLabel }}?');">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                            <input type="hidden" name="year" value="{{ $selectedYear }}">
                            <input type="hidden" name="month" value="{{ $selectedMonth }}">
                            <input type="hidden" name="amount" value="{{ $data['net_payable'] }}">

                            <button type="submit" class="ui-btn ui-btn-success action-btn-full">
                                <i class="bi bi-cash-coin tw:mr-1!"></i>
                                Pay Salary (LKR {{ number_format($data['net_payable'], 2) }})
                            </button>
                        </form>
                    @else
                        <button class="ui-btn ui-btn-secondary action-btn-full" disabled title="{{ $data['pay_block_reason'] }}">
                            <i class="bi bi-lock-fill tw:mr-1!"></i>
                            Payment Unavailable
                        </button>
                    @endif
                </div>

                <!-- SLIP -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                    @if($isPaid)
                        <a href="{{ route('teacher-salaries.slip', $routeParams + ['autoPrint' => 'true']) }}"
                            target="_blank" class="ui-btn ui-btn-primary action-btn-full">
                            <i class="bi bi-printer-fill tw:mr-1!"></i>
                            Print Salary Slip
                        </a>
                    @else
                        <button class="ui-btn ui-btn-secondary action-btn-full" disabled>
                            <i class="bi bi-printer-fill tw:mr-1!"></i>
                            Print Slip (Pay first)
                        </button>
                    @endif
                </div>

                <!-- ADVANCE / DEDUCTION -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                    @if($data['can_add_payment'])
                        <button class="ui-btn ui-btn-warning action-btn-full" data-nx-toggle="modal" data-nx-target="#advanceModal">
                            <i class="bi bi-wallet2 tw:mr-1!"></i>
                            Advance / Deduction
                        </button>
                    @else
                        <button class="ui-btn ui-btn-outline-secondary action-btn-full" disabled
                            title="{{ $data['payment_block_reason'] }}">
                            <i class="bi bi-lock-fill tw:mr-1!"></i>
                            Advance / Deduction Locked
                        </button>
                    @endif
                </div>

                <!-- EXPENSES -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                    <a href="{{ route('teacher-salaries.payment-details', $routeParams) }}"
                        class="ui-btn ui-btn-outline-danger action-btn-full">
                        <i class="bi bi-graph-down tw:mr-1!"></i>
                        Expenses
                    </a>
                </div>

            </div>

            <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:mt-1!">
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                    <a href="{{ route('teacher-salaries.payment-summary', $routeParams) }}"
                        class="ui-btn ui-btn-outline-info action-btn-full">
                        <i class="bi bi-eye tw:mr-1!"></i>
                        Details
                    </a>
                </div>

                <!-- EMAIL SLIP (salary slip + student payments PDF to the teacher) -->
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                    @php
                        $teacherEmail = \App\Support\TeacherSalaryEmail::addressOf($teacher->email);
                    @endphp
                    @if($isPaid && $teacherEmail)
                        <form method="POST" action="{{ route('teacher-salaries.email-slip', $routeParams) }}"
                            onsubmit="return confirm('Email the {{ $monthLabel }} salary slip to {{ $teacherEmail }}?');">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                            <button type="submit" class="ui-btn ui-btn-outline-primary action-btn-full" title="{{ $teacherEmail }}">
                                <i class="bi bi-envelope-fill tw:mr-1!"></i>
                                Email Slip
                            </button>
                        </form>
                    @else
                        <button class="ui-btn ui-btn-outline-secondary action-btn-full" disabled
                            title="{{ $isPaid ? 'Add the teacher\'s email first' : 'Pay the salary first' }}">
                            <i class="bi bi-envelope tw:mr-1!"></i>
                            {{ $isPaid ? 'Email Slip (no email)' : 'Email Slip (Pay first)' }}
                        </button>
                    @endif
                </div>
            </div>

            <div class="tw:mt-6!">
                @if($isPaid)
                    <div class="ui-alert ui-alert-success custom-alert tw:mb-0!">
                        <i class="bi bi-check-circle-fill tw:mr-2!"></i>
                        Salary has been paid. Advances and deductions for {{ $monthLabel }} are locked.
                    </div>
                @elseif($data['can_pay'])
                    <div class="ui-alert ui-alert-success custom-alert tw:mb-0!">
                        <i class="bi bi-check-circle-fill tw:mr-2!"></i>
                        Payment is available. Please check the amounts before paying.
                    </div>
                @elseif($data['pay_block_reason'])
                    <div class="ui-alert ui-alert-info custom-alert tw:mb-0!">
                        <i class="bi bi-info-circle-fill tw:mr-2!"></i>
                        <strong>{{ $data['pay_block_reason'] }}</strong>
                        @if($data['can_add_payment'])
                            <br>You can still add advances and deductions for this month.
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- ADVANCE MODAL -->
        <div class="ui-modal ui-fade" id="advanceModal" tabindex="-1" aria-hidden="true">
            <div class="ui-modal-dialog ui-modal-dialog-centered">
                <div class="ui-modal-content tw:[border:0]! tw:shadow-bs-lg! tw:rounded-bs-xl!">
                    <form method="POST" action="{{ route('teacher-salaries.payment.store', $teacher->id) }}"
                        id="advanceForm">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">

                        <div class="ui-modal-header tw:[border:0]!">
                            <div>
                                <h5 class="ui-modal-title tw:font-bold!">Add Advance / Deduction</h5>
                                <small class="tw:text-bs-secondary-color!">
                                    {{ $teacher->full_name }}
                                </small>
                            </div>

                            <button type="button" class="ui-btn-close" data-nx-dismiss="modal"></button>
                        </div>

                        <div class="ui-modal-body tw:pt-0!">

                            <div class="tw:mb-4!">
                                <label class="ui-form-label tw:font-semibold!">Transaction Type</label>
                                <select name="payment_type" class="ui-form-select custom-input" required>
                                    @foreach (['advance' => 'Advance', 'deduction' => 'Deduction', 'other' => 'Other'] as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" {{ old('payment_type') === $typeValue ? 'selected' : '' }}>
                                            {{ $typeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="tw:mb-4!">
                                <label class="ui-form-label tw:font-semibold!">Amount (LKR)</label>
                                <input type="number" name="amount" step="0.01" class="ui-form-control custom-input" required
                                    id="advanceAmount" min="0.01" max="{{ $availableLimit }}" value="{{ old('amount') }}"
                                    placeholder="Enter amount">

                                <small class="tw:text-bs-secondary-color! tw:block! tw:mt-1!">
                                    Maximum allowed: LKR {{ number_format($availableLimit, 2) }}
                                </small>

                                <div class="tw:text-bs-danger! tw:text-[.875em] tw:mt-2! tw:hidden!" id="amountWarning"></div>
                            </div>

                            <div class="tw:mb-4!">
                                <label class="ui-form-label tw:font-semibold!">Payment Date</label>
                                <input type="date" name="payment_date" class="ui-form-control custom-input"
                                    min="{{ $data['month_start'] }}" max="{{ $data['month_end'] }}"
                                    value="{{ old('payment_date', min(now()->toDateString(), $data['month_end'])) }}" required>
                                <small class="tw:text-bs-secondary-color! tw:block! tw:mt-1!">
                                    Must be a date in {{ $monthLabel }}.
                                </small>
                            </div>

                            <div class="tw:mb-4!">
                                <label class="ui-form-label tw:font-semibold!">Reason / Description</label>
                                <input type="text" name="reason" class="ui-form-control custom-input" maxlength="150"
                                    value="{{ old('reason') }}" placeholder="Optional: Specify reason for this transaction">
                            </div>

                            <div class="tw:mb-0!">
                                <label class="ui-form-label tw:font-semibold!">Additional Notes</label>
                                <textarea name="note" class="ui-form-control custom-input" rows="3" maxlength="1000"
                                    placeholder="Optional: Any additional information">{{ old('note') }}</textarea>
                            </div>
                        </div>

                        <div class="ui-modal-footer tw:[border:0]! tw:pt-0!">
                            <button type="button" class="ui-btn ui-btn-outline-secondary custom-btn" data-nx-dismiss="modal">
                                Cancel
                            </button>

                            <button type="submit" class="ui-btn ui-btn-success custom-btn">
                                <i class="bi bi-save tw:mr-1!"></i>
                                Save Transaction
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        {{-- AUTO SLIP OPEN --}}
        @if(session()->has('slip_open'))
            <script>
                window.addEventListener('load', function () {
                    const url = "{!! route('teacher-salaries.slip', [
                        'teacher' => session('slip_teacher'),
                        'year' => session('slip_year'),
                        'month' => session('slip_month'),
                        'branch_id' => session('slip_branch'),
                        'autoPrint' => 'true',
                    ]) !!}";
                    setTimeout(() => {
                        window.open(url, '_blank');
                    }, 500);
                });
            </script>
        @endif

    </div>
@endsection

@push('styles')
    <style>
        .salary-details-page {
            animation: fadeIn .4s ease;
        }

        .hero-card,
        .filter-card,
        .main-card,
        .status-card {
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

        .custom-input {
            border-radius: 14px !important;
            border: 1px solid #e2e8f0;
            min-height: 48px;
            box-shadow: none;
        }

        .custom-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
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

        .summary-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }

        .summary-red {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

        .summary-green {
            background: linear-gradient(135deg, #10b981, #34d399);
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

        .action-btn-full {
            width: 100%;
            border-radius: 16px;
            padding: .9rem 1rem;
            font-weight: 700;
            transition: .2s ease;
        }

        .action-btn-full:hover {
            transform: translateY(-2px);
        }

        .custom-alert {
            border-radius: 18px;
        }

        .custom-badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
            font-weight: 600;
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
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Re-open the modal when the form came back with errors
            @if ($errors->any() && old('payment_type'))
                const advanceModal = document.getElementById('advanceModal');
                if (advanceModal && window.bootstrap) {
                    NxUi.Modal.getOrCreateInstance(advanceModal).show();
                }
            @endif

            const amountInput = document.getElementById('advanceAmount');
            const form = document.getElementById('advanceForm');
            const warningDiv = document.getElementById('amountWarning');
            const availableLimit = {{ $availableLimit }};

            if (amountInput && form && warningDiv) {
                amountInput.addEventListener('input', function () {
                    const value = parseFloat(this.value || '0');

                    if (value > availableLimit) {
                        warningDiv.textContent = 'Amount cannot exceed the available limit (LKR ' + availableLimit.toFixed(2) + ')';
                        warningDiv.classList.remove('tw:hidden!');
                        this.classList.add('ui-is-invalid');
                    } else {
                        warningDiv.classList.add('tw:hidden!');
                        warningDiv.textContent = '';
                        this.classList.remove('ui-is-invalid');
                    }
                });

                form.addEventListener('submit', function (e) {
                    const value = parseFloat(amountInput.value || '0');

                    if (value > availableLimit) {
                        e.preventDefault();
                        warningDiv.textContent = 'Amount cannot exceed the available limit (LKR ' + availableLimit.toFixed(2) + ')';
                        warningDiv.classList.remove('tw:hidden!');
                        amountInput.classList.add('ui-is-invalid');
                    }
                });
            }
        });
    </script>
@endpush