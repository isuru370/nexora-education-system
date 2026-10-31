@php
    $institute = config('services.institute.name') ?: config('app.name');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Welfare Fund - {{ $month_label }}</title>
    <style>
        @page { margin: 22px 26px 30px 26px; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9.5px; color: #1e293b; }
        .header { width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 6px; margin-bottom: 10px; }
        .header td { vertical-align: bottom; }
        .company { font-size: 12px; font-weight: bold; color: #2563eb; }
        h1 { font-size: 16px; margin: 2px 0 0; }
        .meta { text-align: right; color: #64748b; font-size: 9px; line-height: 1.5; }
        table.cards { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin: 0 -5px 12px; }
        table.cards td { border: 1px solid #e2e8f0; padding: 7px; text-align: center; width: 25%; }
        .value { font-size: 13px; font-weight: bold; }
        .label { font-size: 7.5px; color: #64748b; text-transform: uppercase; }
        h2 { font-size: 11px; margin: 12px 0 5px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #f1f5f9; padding: 5px; font-size: 8px; text-transform: uppercase; text-align: left; border: 1px solid #e2e8f0; }
        table.data td { padding: 4px 5px; border: 1px solid #e2e8f0; vertical-align: top; }
        table.data tfoot td { background: #eff6ff; font-weight: bold; }
        .right { text-align: right; white-space: nowrap; }
        .muted { color: #64748b; }
        .empty { text-align: center; color: #64748b; padding: 8px; }
        .sign { margin-top: 40px; width: 100%; }
        .sign td { width: 50%; padding-top: 4px; text-align: center; }
        .sign span { border-top: 1px dotted #475569; padding: 3px 30px 0; }
        .footer { margin-top: 18px; text-align: center; color: #94a3b8; font-size: 8px; }
    </style>
</head>

<body>
    <table class="header">
        <tr>
            <td>
                <div class="company">{{ $institute }}</div>
                <h1>Teacher Welfare Fund</h1>
                <div>{{ optional($branch)->name }}</div>
            </td>
            <td class="meta">
                Month: <strong>{{ $month_label }}</strong><br>
                Printed by: {{ optional(auth()->user())->name ?? 'System' }}<br>
                {{ now()->format('d M Y, h:i A') }}
            </td>
        </tr>
    </table>

    <table class="cards">
        <tr>
            <td><div class="value">{{ number_format($opening, 2) }}</div><div class="label">Opening balance</div></td>
            <td><div class="value" style="color:#166534">{{ number_format($collected_total, 2) }}</div><div class="label">Taken from salaries</div></td>
            <td><div class="value" style="color:#b91c1c">{{ number_format($expenses_total, 2) }}</div><div class="label">Paid out</div></td>
            <td><div class="value">{{ number_format($closing, 2) }}</div><div class="label">Closing balance (LKR)</div></td>
        </tr>
    </table>

    <h2>Taken from salaries ({{ $collected->count() }})</h2>
    <table class="data">
        <thead><tr><th style="width:5%">#</th><th style="width:15%">Code</th><th>Teacher</th><th class="right" style="width:18%">Amount</th></tr></thead>
        <tbody>
            @forelse ($collected as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ optional($item->teacher)->custom_id }}</td>
                    <td>{{ optional($item->teacher)->full_name }}</td>
                    <td class="right">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">No welfare taken for this month.</td></tr>
            @endforelse
        </tbody>
        @if ($collected->isNotEmpty())
            <tfoot><tr><td colspan="3">Total</td><td class="right">{{ number_format($collected_total, 2) }}</td></tr></tfoot>
        @endif
    </table>

    <h2>Paid out ({{ $expenses->count() }})</h2>
    <table class="data">
        <thead><tr><th style="width:13%">Date</th><th>For</th><th style="width:22%">Paid to</th><th class="right" style="width:18%">Amount</th></tr></thead>
        <tbody>
            @forelse ($expenses as $expense)
                <tr>
                    <td>{{ $expense->expense_date->format('d M Y') }}</td>
                    <td>{{ $expense->description }}@if ($expense->note)<br><span class="muted">{{ $expense->note }}</span>@endif</td>
                    <td>{{ $expense->paid_to ?: '-' }}</td>
                    <td class="right">{{ number_format($expense->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">Nothing paid out this month.</td></tr>
            @endforelse
        </tbody>
        @if ($expenses->isNotEmpty())
            <tfoot><tr><td colspan="3">Total</td><td class="right">{{ number_format($expenses_total, 2) }}</td></tr></tfoot>
        @endif
    </table>

    <table class="sign">
        <tr>
            <td><span>Prepared by</span></td>
            <td><span>Approved by</span></td>
        </tr>
    </table>

    <div class="footer">The welfare fund is not institute income.</div>
</body>

</html>
