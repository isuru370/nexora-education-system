@php
    $teacher = $report['teacher'];
    $summary = $report['summary'];
    $filter = $report['filter'];
    $monthLabel = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
    $institute = config('institution.name', config('app.name'));
    $statusLabels = ['paid' => 'Paid', 'unpaid' => 'Unpaid', 'freecard' => 'Free Card'];
    // By collection date the paid fee month can differ from the report month
    $showPaidFor = ($filter['basis'] ?? null) === 'paid-date';
    $columns = $showPaidFor ? 11 : 10;
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
                Printed by: {{ optional(auth()->user())->name ?? 'System' }}
            </td>
        </tr>
    </table>

    <table class="cards">
        <tr>
            <td><div class="card-value">{{ $summary['total_classes'] }}</div><div class="card-label">Classes</div></td>
            <td><div class="card-value">{{ $summary['total_students'] }}</div><div class="card-label">Students</div></td>
            <td><div class="card-value" style="color:#166534">{{ $summary['paid_students'] }}</div><div class="card-label">Paid</div></td>
            <td><div class="card-value" style="color:#b91c1c">{{ $summary['unpaid_students'] }}</div><div class="card-label">Unpaid</div></td>
            <td><div class="card-value" style="color:#1d4ed8">{{ $summary['freecard_students'] }}</div><div class="card-label">Free Card</div></td>
            <td><div class="card-value">{{ number_format($summary['total_collected'], 2) }}</div><div class="card-label">Collected (Rs.)</div></td>
        </tr>
    </table>

    @forelse ($report['classes'] as $class)
        @forelse ($class['categories'] as $category)
            @php
                $students = collect(['paid', 'unpaid', 'freecard'])
                    ->flatMap(fn ($group) => $category['students'][$group] ?? []);
                $collected = $students->sum('paid_amount');
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
                                | Collected Rs. {{ number_format($collected, 2) }}
                            </div>
                        </td>
                        <td class="counts">
                            <span class="pill pill-total">Total {{ $category['total_students'] }}</span>
                            <span class="pill pill-paid">Paid {{ $category['paid_count'] }}</span>
                            <span class="pill pill-unpaid">Unpaid {{ $category['unpaid_count'] }}</span>
                            <span class="pill pill-freecard">Free Card {{ $category['freecard_count'] }}</span>
                        </td>
                    </tr>
                </table>

                <table class="data">
                    <thead>
                        <tr>
                            <th style="width:4%">#</th>
                            <th style="width:11%">Code</th>
                            <th>Student Name</th>
                            <th style="width:12%">Guardian Mobile</th>
                            <th style="width:10%">Fee Option</th>
                            @if ($showPaidFor)
                                <th style="width:9%">Paid For</th>
                            @endif
                            <th style="width:9%">Paid On</th>
                            <th class="right" style="width:8%">Fee</th>
                            <th class="right" style="width:8%">Paid</th>
                            <th class="right" style="width:8%">Balance</th>
                            <th class="center" style="width:8%">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $student['student_code'] ?? '-' }}</td>
                                <td>{{ $student['initial_name'] ?? '-' }}</td>
                                <td>{{ $student['guardian_mobile'] ?? '-' }}</td>
                                <td>{{ $student['fee_option']['label'] ?? '-' }}</td>
                                @if ($showPaidFor)
                                    <td>{{ $student['paid_for'] ?: '-' }}</td>
                                @endif
                                <td>{{ $student['paid_on'] ?: '-' }}</td>
                                <td class="right">{{ number_format((float) $student['final_fee'], 2) }}</td>
                                <td class="right">{{ number_format((float) $student['paid_amount'], 2) }}</td>
                                <td class="right">{{ number_format((float) $student['balance'], 2) }}</td>
                                <td class="center status-{{ $student['status'] }}">{{ $statusLabels[$student['status']] ?? $student['status'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $columns }}" class="empty">No active students.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($students->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="{{ $columns - 4 }}">Total ({{ $students->count() }})</td>
                                <td class="right">{{ number_format($students->sum('final_fee'), 2) }}</td>
                                <td class="right">{{ number_format($collected, 2) }}</td>
                                <td class="right">{{ number_format($students->sum('balance'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @empty
            <div class="class-block">
                <table class="class-head">
                    <tr><td><div class="class-name">{{ $class['class_name'] }}</div><div class="class-sub">{{ $class['grade_name'] ?? '' }}</div></td></tr>
                </table>
                <div class="empty">No fee categories for this class.</div>
            </div>
        @endforelse
    @empty
        <div class="empty">No classes found for this teacher.</div>
    @endforelse

    <div class="footer">
        Generated by {{ $institute }} on {{ now()->format('d M Y, h:i A') }}
    </div>
</body>

</html>
