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
        <div class="hero-section mb-4">
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
            <div class="nx-alert nx-alert-red mb-4"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="nx-alert nx-alert-red mb-4">
                <div>
                    @foreach ($errors->all() as $error)
                        <div><i class="bi bi-exclamation-circle-fill me-1"></i>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ONE FILTER FOR EVERY REPORT --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Report Month</h5>
                    <p class="nx-card-subtitle">Choose the month once - every download below uses it</p>
                </div>
            </div>
            <div class="p-3">
                <form method="GET" action="{{ route('monthly-report.index') }}" class="row g-3 align-items-end" id="monthlyReportFilter">
                    @if ($branches->count() > 1)
                        <div class="col-lg-3 col-md-6">
                            <label class="nx-label">Branch</label>
                            <select name="branch_id" class="form-select nx-input">
                                @foreach ($branches as $branchOption)
                                    <option value="{{ $branchOption->id }}" {{ (int) $branchId === (int) $branchOption->id ? 'selected' : '' }}>
                                        {{ $branchOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                    @endif

                    <div class="col-lg-2 col-md-3">
                        <label class="nx-label">Year</label>
                        <select name="year" class="form-select nx-input">
                            @for ($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-3">
                        <label class="nx-label">Month</label>
                        <select name="month" class="form-select nx-input">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-8">
                        <label class="nx-label">Teacher <span class="text-muted fw-normal text-lowercase">(for teacher reports)</span></label>
                        <select name="teacher_id" class="form-select nx-input">
                            <option value="">-- Select teacher --</option>
                            @foreach ($teachers as $teacherItem)
                                <option value="{{ $teacherItem->id }}" {{ (int) $teacherId === (int) $teacherItem->id ? 'selected' : '' }}>
                                    {{ $teacherItem->custom_id }} - {{ $teacherItem->initials }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <button type="submit" class="nx-btn nx-btn-primary w-100"><i class="bi bi-check2-circle"></i> Apply</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- BRANCH REPORT --}}
        <div class="section-heading mb-3">
            <h6 class="mb-0"><i class="bi bi-building text-primary"></i> Branch report</h6>
            <small class="cell-sub">{{ $selectedBranch->name ?? 'Branch' }} · {{ $periodLabel }}</small>
        </div>

        <div class="nx-card report-tile mb-4">
            <div class="report-tile-body">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-cash-stack"></i></div>
                <div class="flex-grow-1">
                    <h5 class="nx-card-title mb-1">Teacher Salary Summary</h5>
                    <p class="nx-card-subtitle mb-0">
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
        <div class="section-heading mb-3">
            <h6 class="mb-0"><i class="bi bi-person-badge text-primary"></i> Teacher reports</h6>
            <small class="cell-sub">
                @if ($selectedTeacher)
                    {{ $selectedTeacher->custom_id }} - {{ $selectedTeacher->initials }} · {{ $periodLabel }}
                @else
                    Select a teacher in the filter above
                @endif
            </small>
        </div>

        @if (!$teacherId)
            <div class="nx-alert nx-alert-amber mb-4">
                <i class="bi bi-info-circle-fill"></i>
                <span>Select a teacher above and click <strong>Apply</strong> to enable the teacher reports.</span>
            </div>
        @endif

        <div class="row g-3 mb-4">
            {{-- Salary slip --}}
            <div class="col-lg-6">
                <div class="nx-card report-tile h-100 {{ $teacherId ? '' : 'is-disabled' }}">
                    <div class="report-tile-body">
                        <div class="stat-icon-wrapper icon-violet"><i class="bi bi-receipt"></i></div>
                        <div class="flex-grow-1">
                            <h5 class="nx-card-title mb-1">Salary Slip</h5>
                            <p class="nx-card-subtitle mb-0">Print the teacher's salary slip for the month.</p>
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
                <div class="col-lg-6">
                    <div class="nx-card report-tile h-100 {{ $teacherId ? '' : 'is-disabled' }}">
                        <div class="report-tile-body">
                            <div class="stat-icon-wrapper {{ $item['color'] }}"><i class="bi {{ $item['icon'] }}"></i></div>
                            <div class="flex-grow-1">
                                <h5 class="nx-card-title mb-1">{{ $item['title'] }}</h5>
                                <p class="nx-card-subtitle mb-0">{{ $item['text'] }}</p>
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

        <p class="text-center cell-sub mb-0">
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
    .dashboard-page .report-tile-body > .flex-grow-1 { min-width: 0; }
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