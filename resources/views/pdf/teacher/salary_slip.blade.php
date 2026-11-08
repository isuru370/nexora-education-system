{{-- Salary slip PDF emailed to the teacher ($slip = TeacherSalaryService::salarySlip) --}}
@php
    $money = fn ($value) => number_format((float) $value, 2);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Salary Slip - {{ $slip['teacher_custom_id'] }} - {{ $slip['month_year_display'] }}</title>
    <style>
        @page { margin: 26px 30px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1e293b; }

        .header { width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 8px; margin-bottom: 12px; }
        .header td { vertical-align: bottom; }
        .company { font-size: 15px; font-weight: bold; color: #2563eb; }
        h1 { font-size: 13px; color: #0f172a; margin-top: 2px; }
        .meta { text-align: right; color: #64748b; font-size: 9px; line-height: 1.6; }

        table.info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.info td { padding: 4px 6px; border: 1px solid #e2e8f0; }
        table.info .label { width: 22%; background: #f8fafc; color: #64748b; }

        h2 { font-size: 11px; margin: 0 0 5px; color: #0f172a; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.data th { background: #f1f5f9; color: #334155; padding: 5px 6px; font-size: 8.5px; text-transform: uppercase; text-align: left; border: 1px solid #e2e8f0; }
        table.data td { padding: 5px 6px; border: 1px solid #e2e8f0; }
        table.data tfoot td { background: #eff6ff; font-weight: bold; }
        .right { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .muted { color: #64748b; }

        table.net { width: 100%; border-collapse: collapse; background: #0f172a; color: #fff; }
        table.net td { padding: 10px 12px; }
        .net-label { font-size: 11px; }
        .net-value { font-size: 17px; font-weight: bold; text-align: right; }
        .words { margin-top: 6px; font-style: italic; color: #475569; }

        .paid { display: inline-block; padding: 2px 8px; border-radius: 8px; background: #dcfce7; color: #166534; font-weight: bold; }
        .footer { margin-top: 26px; text-align: center; color: #94a3b8; font-size: 8px; line-height: 1.6; }
    </style>
</head>

<body>
    <table class="header">
        <tr>
            <td>
                <div class="company">{{ $institute }}</div>
                <h1>Salary Slip - {{ $slip['month_year_display'] }}</h1>
            </td>
            <td class="meta">
                @if ($slip['is_salary_paid'])
                    <span class="paid">PAID</span><br>
                @endif
                Paid on: <strong>{{ $slip['paid_at'] ?? '-' }}</strong><br>
                Generated: {{ $slip['date_generated'] }}
            </td>
        </tr>
    </table>

    <table class="info">
        <tr>
            <td class="label">Teacher</td>
            <td>{{ $slip['teacher_name'] }}</td>
            <td class="label">Teacher ID</td>
            <td>{{ $slip['teacher_custom_id'] }}</td>
        </tr>
        <tr>
            <td class="label">Branch</td>
            <td>{{ $slip['branch_name'] ?? '-' }}</td>
            <td class="label">Month</td>
            <td>{{ $slip['month_year_display'] }}</td>
        </tr>
    </table>

    <h2>Earnings</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Class</th>
                <th class="right">Collected (Rs.)</th>
                <th class="center">Teacher %</th>
                <th class="right">Amount (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($slip['earnings'] as $row)
                <tr>
                    <td>{{ $row['description'] }}</td>
                    <td class="right">{{ $money($row['class_total']) }}</td>
                    <td class="center">{{ rtrim(rtrim(number_format($row['teacher_percentage'], 2), '0'), '.') }}%</td>
                    <td class="right">{{ $money($row['amount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center muted">No class payments this month.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total earnings</td>
                <td class="right">{{ $money($slip['total_addition']) }}</td>
            </tr>
        </tfoot>
    </table>

    <h2>Deductions</h2>
    <table class="data">
        <tbody>
            @forelse ($slip['deductions'] as $row)
                <tr>
                    <td>{{ $row['description'] }}</td>
                    <td class="right" style="width: 30%">{{ $money($row['amount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="center muted">No deductions.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total deductions</td>
                <td class="right">{{ $money($slip['total_deductions']) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="net">
        <tr>
            <td class="net-label">Net salary paid</td>
            <td class="net-value">LKR {{ $money($slip['net_salary']) }}</td>
        </tr>
    </table>
    @if (!empty($slip['net_salary_words']))
        <div class="words">{{ $slip['net_salary_words'] }}</div>
    @endif

    <div class="footer">
        {{ $institute }}<br>
        {{ $poweredBy }}
    </div>
</body>

</html>
