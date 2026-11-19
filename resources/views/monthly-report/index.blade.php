@extends('layouts.app')

@section('title', 'Monthly Financial Reports')
@section('page-title', 'Monthly Financial Reports')

@section('content')
    @php
        $period = \Carbon\Carbon::create($year, $month, 1);
        $periodLabel = $period->format('F Y');

        $selectedTeacher = $teacherId ? $teachers->firstWhere('id', $teacherId) : null;
        $selectedBranch = $branches->firstWhere('id', $branchId);

        // Query strings used by the download buttons
        $branchQuery = ['year' => $year, 'month' => $month, 'branch_id' => $branchId];
        $teacherQuery = ['teacher_id' => $teacherId, 'year' => $year, 'month' => $month];

        // Reports that need a teacher
        $teacherReports = [
            [
                'icon' => 'bi-people',
                'color' => 'icon-green',
                'title' => 'Student Payments by Fee Month',
                'text' => 'Students whose payment is for the selected month, whatever day they paid.',
                'excel' => route('monthly-report.student-payments.excel', ['basis' => 'fee-month'] + $teacherQuery),
                'pdf' => route('monthly-report.student-payments.pdf', ['basis' => 'fee-month'] + $teacherQuery),
            ],
            [
                'icon' => 'bi-calendar-check',
                'color' => 'icon-amber',
                'title' => 'Student Payments by Collection Date',
                'text' => 'Students who actually paid during the selected month, whatever fee month it was for.',
                'excel' => route('monthly-report.student-payments.excel', ['basis' => 'paid-date'] + $teacherQuery),
                'pdf' => route('monthly-report.student-payments.pdf', ['basis' => 'paid-date'] + $teacherQuery),
            ],
            [
                'icon' => 'bi-wallet2',
                'color' => 'icon-red',
                'title' => 'Teacher Expense Summary',
                'text' => 'All advances and other payments recorded for the teacher in the selected month.',
                'excel' => route('monthly-report.teacher-expense.export', ['format' => 'excel'] + $teacherQuery),
                'pdf' => route('monthly-report.teacher-expense.export', ['format' => 'pdf'] + $teacherQuery),
            ],
        ];
    @endphp

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Monthly Financial Reports</h1>
                    <p class="hero-subtitle">
                        Salary, student payment and expense reports • {{ $periodLabel }}
                        @if ($selectedBranch)
                            • {{ $selectedBranch->name }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('error'))
            <div class="nx-alert nx-alert-red tw:mb-6!"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="nx-alert nx-alert-red tw:mb-6!">
                <div>
                    @foreach ($errors->all() as $error)
                        <div><i class="bi bi-exclamation-circle-fill tw:mr-1!"></i>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ONE FILTER FOR EVERY REPORT --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel tw:text-(color:--nx-blue)!"></i> Report Month</h5>
                    <p class="nx-card-subtitle">Choose the month once - every download below uses it</p>
                </div>
            </div>
            <div class="tw:p-4!">
                <form method="GET" action="{{ route('monthly-report.index') }}" class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-end!" id="monthlyReportFilter">
                    @if ($branches->count() > 1)
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                            <label class="nx-label">Branch</label>
                            <select name="branch_id" class="ui-form-select nx-input">
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

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                        <label class="nx-label">Year</label>
                        <select name="year" class="ui-form-select nx-input">
                            @for ($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[25%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                        <label class="nx-label">Month</label>
                        <select name="month" class="ui-form-select nx-input">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[66.66666667%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                        <label class="nx-label">Teacher <span class="tw:text-(color:--nx-muted)! tw:font-normal! tw:lowercase!">(for teacher reports)</span></label>
                        <select name="teacher_id" class="ui-form-select nx-input">
                            <option value="">-- Select teacher --</option>
                            @foreach ($teachers as $teacherItem)
                                <option value="{{ $teacherItem->id }}" {{ (int) $teacherId === (int) $teacherItem->id ? 'selected' : '' }}>
                                    {{ $teacherItem->custom_id }} - {{ $teacherItem->initials }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                        <button type="submit" class="nx-btn nx-btn-primary tw:w-full!"><i class="bi bi-check2-circle"></i> Apply</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- BRANCH REPORT --}}
        <div class="section-heading tw:mb-4!">
            <h6 class="tw:mb-0!"><i class="bi bi-building tw:text-(color:--nx-blue)!"></i> Branch report</h6>
            <small class="cell-sub">{{ $selectedBranch->name ?? 'Branch' }} · {{ $periodLabel }}</small>
        </div>

        <div class="nx-card report-tile tw:mb-6!">
            <div class="report-tile-body">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-cash-stack"></i></div>
                <div class="bs-flex-grow-1 tw:grow!">
                    <h5 class="nx-card-title tw:mb-1!">Teacher Salary Summary</h5>
                    <p class="nx-card-subtitle tw:mb-0!">
                        Earnings, deductions and net salary of every teacher in the branch.
                        Same numbers as the Teacher Salary page.
                    </p>
                </div>
                <div class="report-tile-actions">
                    <a href="{{ route('monthly-report.teacher-salary.excel', $branchQuery) }}" class="nx-btn nx-btn-success">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                    </a>
                    <a href="{{ route('monthly-report.teacher-salary.pdf', $branchQuery) }}" class="nx-btn nx-btn-danger">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- TEACHER REPORTS --}}
        <div class="section-heading tw:mb-4!">
            <h6 class="tw:mb-0!"><i class="bi bi-person-badge tw:text-(color:--nx-blue)!"></i> Teacher reports</h6>
            <small class="cell-sub">
                @if ($selectedTeacher)
                    {{ $selectedTeacher->custom_id }} - {{ $selectedTeacher->initials }} · {{ $periodLabel }}
                @else
                    Select a teacher in the filter above
                @endif
            </small>
        </div>

        @if (!$teacherId)
            <div class="nx-alert nx-alert-amber tw:mb-6!">
                <i class="bi bi-info-circle-fill"></i>
                <span>Select a teacher above and click <strong>Apply</strong> to enable the teacher reports.</span>
            </div>
        @endif

        <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:mb-6!">
            {{-- Salary slip --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="nx-card report-tile tw:h-full! {{ $teacherId ? '' : 'is-disabled' }}">
                    <div class="report-tile-body">
                        <div class="stat-icon-wrapper icon-violet"><i class="bi bi-receipt"></i></div>
                        <div class="bs-flex-grow-1 tw:grow!">
                            <h5 class="nx-card-title tw:mb-1!">Salary Slip</h5>
                            <p class="nx-card-subtitle tw:mb-0!">Print the teacher's salary slip for the month.</p>
                        </div>
                    </div>
                    <div class="report-tile-footer">
                        @if ($teacherId)
                            <a href="{{ route('teacher-salaries.slip', ['teacher' => $teacherId] + $branchQuery + ['autoPrint' => 'true']) }}"
                                target="_blank" rel="noopener" class="nx-btn nx-btn-primary">
                                <i class="bi bi-printer"></i> Print Slip
                            </a>
                        @else
                            <span class="nx-btn nx-btn-light disabled"><i class="bi bi-printer"></i> Print Slip</span>
                        @endif
                    </div>
                </div>
            </div>

            @foreach ($teacherReports as $item)
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                    <div class="nx-card report-tile tw:h-full! {{ $teacherId ? '' : 'is-disabled' }}">
                        <div class="report-tile-body">
                            <div class="stat-icon-wrapper {{ $item['color'] }}"><i class="bi {{ $item['icon'] }}"></i></div>
                            <div class="bs-flex-grow-1 tw:grow!">
                                <h5 class="nx-card-title tw:mb-1!">{{ $item['title'] }}</h5>
                                <p class="nx-card-subtitle tw:mb-0!">{{ $item['text'] }}</p>
                            </div>
                        </div>
                        <div class="report-tile-footer">
                            @if ($teacherId)
                                <a href="{{ $item['excel'] }}" class="nx-btn nx-btn-success">
                                    <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                                </a>
                                <a href="{{ $item['pdf'] }}" target="_blank" rel="noopener" class="nx-btn nx-btn-danger">
                                    <i class="bi bi-filetype-pdf"></i> PDF
                                </a>
                            @else
                                <span class="nx-btn nx-btn-light disabled"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</span>
                                <span class="nx-btn nx-btn-light disabled"><i class="bi bi-filetype-pdf"></i> PDF</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="tw:text-center! cell-sub tw:mb-0!">
            <i class="bi bi-info-circle"></i>
            <strong>Fee month</strong> = the month a payment is for.
            <strong>Collection date</strong> = the day the money was received.
        </p>

    </div>
@endsection

@push('styles')
<style>
    /* Monthly Financial Reports — page-specific styles only */
    .dashboard-page .section-heading {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .25rem 1rem;
    }
    .dashboard-page .section-heading h6 { font-weight: 700; }
    .dashboard-page .report-tile { display: flex; flex-direction: column; }
    .dashboard-page .report-tile-body {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.1rem 1.25rem;
        flex: 1 1 auto;
    }
    .dashboard-page .report-tile-body > .bs-flex-grow-1 { min-width: 0; }
    .dashboard-page .report-tile-body .stat-icon-wrapper { flex-shrink: 0; }
    .dashboard-page .report-tile-actions { display: flex; gap: .5rem; flex-wrap: wrap; flex-shrink: 0; }
    .dashboard-page .report-tile-footer {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        padding: .85rem 1.25rem;
        border-top: 1px solid var(--nx-border);
        background: var(--nx-head-bg);
    }
    .dashboard-page .report-tile.is-disabled .report-tile-body { opacity: .55; }
    .dashboard-page .nx-btn.disabled { pointer-events: none; opacity: .6; }
    @media (max-width: 576px) {
        .dashboard-page .report-tile-body { flex-wrap: wrap; }
        .dashboard-page .report-tile-actions,
        .dashboard-page .report-tile-actions .nx-btn,
        .dashboard-page .report-tile-footer .nx-btn { width: 100%; justify-content: center; }
    }
</style>
@endpush

@push('scripts')
<script>
    // Some pages auto-submit filter selects on change. Here the teacher is
    // applied with the Apply button, so stop the auto-submit for that select.
    document.addEventListener('change', function (event) {
        var target = event.target;

        if (target && target.name === 'teacher_id' && target.closest('#monthlyReportFilter')) {
            event.stopPropagation();
            event.stopImmediatePropagation();
        }
    }, true);
</script>
@endpush