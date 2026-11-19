@php
    $teacher = $report['teacher'];
    $summary = $report['summary'];
    $filter = $report['filter'];
    $monthStart = \Carbon\Carbon::create($year, $month, 1);
    $monthLabel = $monthStart->format('F Y');
    $monthShort = $monthStart->format('M');
    $institute = $instituteName ?? config('institution.name', config('app.name'));
    $statusLabels = ['paid' => 'Paid', 'partial' => 'Partly paid', 'unpaid' => 'Unpaid', 'freecard' => 'Free Card', 'noattend' => 'No Attendance'];
    $hasNoAttend = ($summary['noattend_students'] ?? 0) > 0;
    // Collection date = the payments of the teacher's salary (any fee month)
    $bySalary = ($filter['basis'] ?? null) === 'paid-date';
    $showPhones = $report['show_phones'] ?? false;
    $columns = ($bySalary ? 10 : 11) + ($showPhones ? 1 : 0);
    $money = fn ($v) => number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Student Payments - {{ $teacher['custom_id'] }} - {{ $monthLabel }}</title>
    <style>
        @page { margin: 18px 20px 26px 20px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8.5px; color: #1e293b; }

        .header { width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 6px; margin-bottom: 8px; }
        .header td { vertical-align: bottom; }
        .company { font-size: 11px; font-weight: bold; color: #2563eb; }
        h1 { font-size: 15px; color: #0f172a; }
        .meta { text-align: right; color: #64748b; font-size: 8px; line-height: 1.5; }

        table.cards { width: 100%; border-collapse: separate; border-spacing: 4px 0; margin: 0 -4px 10px -4px; }
        table.cards td { border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px; text-align: center; width: 16.66%; }
        .card-value { font-size: 13px; font-weight: bold; }
        .card-label { font-size: 7px; color: #64748b; text-transform: uppercase; }

        .class-block { margin-bottom: 12px; }
        table.class-head { width: 100%; border-collapse: collapse; background: #0f172a; color: #fff; }
        table.class-head td { padding: 5px 6px; }
        .class-name { font-size: 10px; font-weight: bold; }
        .class-sub { font-size: 7.5px; color: #cbd5e1; }
        .counts { text-align: right; white-space: nowrap; }
        .pill { display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 7.5px; font-weight: bold; margin-left: 3px; }

        .pill-total { background: #e2e8f0; color: #0f172a; }
        .pill-paid { background: #dcfce7; color: #166534; }
        .pill-unpaid { background: #fee2e2; color: #991b1b; }
        .pill-freecard { background: #dbeafe; color: #1e40af; }
        .pill-noattend { background: #f1f5f9; color: #475569; }
        .status-noattend { color: #475569; font-weight: bold; }
        .pill-inactive { background: #fef3c7; color: #92400e; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        table.data th { background: #f1f5f9; color: #334155; padding: 4px; font-size: 7px; text-transform: uppercase; text-align: left; border: 1px solid #e2e8f0; }
        table.data td { padding: 3.5px 4px; border: 1px solid #e2e8f0; vertical-align: top; }
        table.data tfoot td { background: #eff6ff; font-weight: bold; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }

        .status-paid { color: #166534; font-weight: bold; }
        .status-unpaid { color: #b91c1c; font-weight: bold; }
        .status-freecard { color: #1d4ed8; font-weight: bold; }
        .status-partial { color: #b45309; font-weight: bold; }
        .muted { color: #94a3b8; }
        .note { border: 1px solid #e2e8f0; background: #f8fafc; padding: 5px 7px; margin-bottom: 9px; color: #475569; font-size: 7.5px; line-height: 1.5; }
        .tag { display: inline-block; padding: 0 4px; border-radius: 6px; background: #ede9fe; color: #5b21b6; font-size: 6.5px; font-weight: bold; }

        .empty { text-align: center; padding: 10px; color: #64748b; }
        .footer { margin-top: 10px; text-align: center; color: #94a3b8; font-size: 7px; }
    </style>
</head>

<body>
    <table class="header">
        <tr>
            <td>
                <div class="company">{{ $institute }}</div>
                <h1>Student Payment Report</h1>
                <div>{{ $teacher['custom_id'] }} - {{ $teacher['full_name'] ?? $teacher['initials'] }}</div>
            </td>
            <td class="meta">
                Month: <strong>{{ $monthLabel }}</strong><br>
                By {{ $filter['basis_label'] ?? '' }}<br>
                @if (!empty($branchName))
                    Branch: {{ $branchName }}<br>
                @endif
                Printed by: {{ optional(auth()->user())->name ?? 'System' }}
            </td>
        </tr>
    </table>

    @if ($bySalary)
        <table class="cards">
            <tr>
                <td><div class="card-value">{{ $summary['payments'] }}</div><div class="card-label">Payments</div></td>
                <td><div class="card-value">{{ $money($summary['total_collected']) }}</div><div class="card-label">Received (Rs.)</div></td>
                <td><div class="card-value" style="color:#b45309">{{ $money($summary['hall_collected']) }}</div><div class="card-label">Hall fees (Rs.)</div></td>
                <td><div class="card-value">{{ $money($summary['class_collected']) }}</div><div class="card-label">Class fees (Rs.)</div></td>
                <td><div class="card-value" style="color:#166534">{{ $money($summary['teacher_share']) }}</div><div class="card-label">Teacher's share (Rs.)</div></td>
                <td><div class="card-value">{{ $summary['paid_students'] }} / {{ $summary['unpaid_students'] }}</div><div class="card-label">{{ $monthShort }} fee paid / unpaid</div></td>
            </tr>
        </table>
        <div class="note">
            The money the teacher's {{ $monthLabel }} salary is made of: class fees <strong>received</strong> in {{ $monthLabel }}
            (up to the time the salary was paid), whatever month each fee was for, plus earlier months' fees received after their salary
            (<span class="tag">AFTER SALARY</span>). The hall fee is taken first from each payment; the teacher's share is the teacher %
            of the rest. Advances and deductions are on the salary slip. <strong>Status</strong> = the student's {{ $monthLabel }} fee.
        </div>
    @else
        <table class="cards">
            <tr>
                <td><div class="card-value">{{ $summary['total_students'] }}</div><div class="card-label">Students</div></td>
                <td><div class="card-value" style="color:#166534">{{ $summary['paid_students'] }}</div><div class="card-label">Paid</div></td>
                <td><div class="card-value" style="color:#b91c1c">{{ $summary['unpaid_students'] + $summary['partial_students'] }}</div><div class="card-label">Unpaid{{ $summary['partial_students'] ? ' (' . $summary['partial_students'] . ' partly)' : '' }}</div></td>
                <td><div class="card-value" style="color:#1d4ed8">{{ $summary['freecard_students'] }}{{ $hasNoAttend ? ' / ' . $summary['noattend_students'] : '' }}</div><div class="card-label">Free Card{{ $hasNoAttend ? ' / No Attend.' : '' }}</div></td>
                <td><div class="card-value">{{ $money($summary['total_payable']) }}</div><div class="card-label">To collect (Rs.)</div></td>
                <td><div class="card-value" style="color:#166534">{{ $money($summary['total_collected']) }}</div><div class="card-label">Collected (Rs.)</div></td>
                <td><div class="card-value" style="color:#b91c1c">{{ $money($summary['total_balance']) }}</div><div class="card-label">Balance (Rs.)</div></td>
            </tr>
        </table>
        <div class="note">
            Fees <strong>for</strong> {{ $monthLabel }}, whatever day they were paid. To pay = class fee + hall fee
            (collected Rs. {{ $money($summary['class_collected']) }} class fee + Rs. {{ $money($summary['hall_collected']) }} hall fee).
            A payment pays the hall fee first.
        </div>
    @endif

    @forelse ($report['classes'] as $class)
        @foreach ($class['categories'] as $category)
            @php
                $students = collect(['paid', 'unpaid', 'freecard', 'noattend'])
                    ->flatMap(fn ($group) => $category['students'][$group] ?? []);
                // by collection date: students who paid something first
                if ($bySalary) {
                    $students = $students->sortByDesc(fn ($s) => $s['paid_amount'] > 0)->values();
                }
                $ct = $category['totals'];
            @endphp

            <div class="class-block">
                <table class="class-head">
                    <tr>
                        <td>
                            <div class="class-name">
                                {{ $class['class_name'] }}
                                @if (!($class['is_active'] ?? true))
                                    <span class="pill pill-inactive">Inactive</span>
                                @endif
                            </div>
                            <div class="class-sub">
                                {{ $class['grade_name'] ?? '' }}{{ $category['category_name'] ? ' | ' . $category['category_name'] : '' }}
                                @if ($bySalary)
                                    | Received Rs. {{ $money($ct['collected']) }} | Teacher Rs. {{ $money($ct['teacher']) }}
                                @else
                                    | Collected Rs. {{ $money($ct['collected']) }} of {{ $money($ct['payable']) }}
                                @endif
                            </div>
                        </td>
                        <td class="counts">
                            @if ($bySalary) <span class="class-sub">{{ $monthShort }} fee:</span> @endif
                            <span class="pill pill-total">Total {{ $category['total_students'] }}</span>
                            <span class="pill pill-paid">Paid {{ $category['paid_count'] }}</span>
                            <span class="pill pill-unpaid">Unpaid {{ $category['unpaid_count'] }}</span>
                            <span class="pill pill-freecard">Free Card {{ $category['freecard_count'] }}</span>
                            @if ($category['noattend_count'] ?? 0)
                                <span class="pill pill-noattend">No Attendance {{ $category['noattend_count'] }}</span>
                            @endif
                        </td>
                    </tr>
                </table>

                <table class="data">
                    <thead>
                        <tr>
                            <th style="width:3%">#</th>
                            <th style="width:9%">Code</th>
                            <th>Student Name</th>
                            @if ($showPhones)<th style="width:9%">Guardian Mobile</th>@endif
                            @if ($bySalary)
                                <th style="width:12%">Fee For</th>
                                <th style="width:11%">Received On</th>
                                <th class="right" style="width:8%">Received</th>
                                <th class="right" style="width:7%">Hall Fee</th>
                                <th class="right" style="width:8%">Class Fee</th>
                                <th class="right" style="width:8%">Teacher Share</th>
                                <th class="center" style="width:8%">{{ $monthShort }} Fee</th>
                            @else
                                <th style="width:9%">Fee Option</th>
                                <th style="width:9%">Paid On</th>
                                <th class="right" style="width:7%">Class Fee</th>
                                <th class="right" style="width:6%">Hall Fee</th>
                                <th class="right" style="width:7%">To Pay</th>
                                <th class="right" style="width:7%">Paid</th>
                                <th class="right" style="width:7%">Balance</th>
                                <th class="center" style="width:8%">Status</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $student['student_code'] ?? '-' }}</td>
                                <td>{{ $student['initial_name'] ?? '-' }}</td>
                                @if ($showPhones)<td>{{ $student['guardian_mobile'] ?? '-' }}</td>@endif
                                @if ($bySalary)
                                    <td>
                                        {{ $student['paid_for'] ?: '-' }}
                                        @if ($student['carried']) <span class="tag">AFTER SALARY</span> @endif
                                    </td>
                                    <td>{{ $student['paid_on'] ?: '-' }}</td>
                                    <td class="right {{ $student['paid_amount'] > 0 ? '' : 'muted' }}">{{ $money($student['paid_amount']) }}</td>
                                    <td class="right {{ $student['paid_amount'] > 0 ? '' : 'muted' }}">{{ $money($student['hall_collected']) }}</td>
                                    <td class="right {{ $student['paid_amount'] > 0 ? '' : 'muted' }}">{{ $money($student['class_collected']) }}</td>
                                    <td class="right {{ $student['paid_amount'] > 0 ? '' : 'muted' }}"><strong>{{ $money($student['teacher_amount']) }}</strong></td>
                                @else
                                    <td>{{ $student['fee_option']['label'] ?? '-' }}</td>
                                    <td>{{ $student['paid_on'] ?: '-' }}</td>
                                    <td class="right">{{ $money($student['class_fee']) }}</td>
                                    <td class="right">{{ $money($student['hall_fee']) }}</td>
                                    <td class="right"><strong>{{ $money($student['payable']) }}</strong></td>
                                    <td class="right">{{ $money($student['paid_amount']) }}{{ $student['discount'] > 0 ? ' (+' . $money($student['discount']) . ' disc.)' : '' }}</td>
                                    <td class="right">{{ $money($student['balance']) }}</td>
                                @endif
                                <td class="center status-{{ $student['status'] }}">{{ $statusLabels[$student['status']] ?? $student['status'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $columns }}" class="empty">No active students.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($students->isNotEmpty())
                        <tfoot>
                            <tr>
                                @if ($bySalary)
                                    <td colspan="{{ $showPhones ? 6 : 5 }}">Total ({{ $students->count() }} students)</td>
                                    <td class="right">{{ $money($ct['collected']) }}</td>
                                    <td class="right">{{ $money($ct['hall']) }}</td>
                                    <td class="right">{{ $money($ct['class']) }}</td>
                                    <td class="right">{{ $money($ct['teacher']) }}</td>
                                    <td></td>
                                @else
                                    <td colspan="{{ $showPhones ? 6 : 5 }}">Total ({{ $students->count() }} students)</td>
                                    <td class="right">{{ $money($students->sum('class_fee')) }}</td>
                                    <td class="right">{{ $money($students->sum('hall_fee')) }}</td>
                                    <td class="right">{{ $money($ct['payable']) }}</td>
                                    <td class="right">{{ $money($ct['collected']) }}</td>
                                    <td class="right">{{ $money($ct['balance']) }}</td>
                                    <td></td>
                                @endif
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endforeach
    @empty
        <div class="empty">No classes found for this teacher.</div>
    @endforelse

    <div class="footer">
        Generated by {{ $institute }} on {{ now()->format('d M Y, h:i A') }}
        @if (!empty($poweredBy))
            <br>{{ $poweredBy }}
        @endif
    </div>
</body>

</html>
