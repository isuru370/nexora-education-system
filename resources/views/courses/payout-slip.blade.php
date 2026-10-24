<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teacher payment {{ $payout->id }}</title>
    <style>
        @page { size: 58mm auto; margin: 0; }
        body { margin: 0; background: #eef1f6; font-family: monospace; font-size: 11px; color: #000; }
        .paper { width: 58mm; margin: 16px auto; padding: 3mm; background: #fff; box-sizing: border-box; }
        .center { text-align: center; } .big { font-size: 14px; font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; gap: 6px; line-height: 1.6; }
        .cancelled { border: 2px solid #000; padding: 2px; font-weight: bold; text-align: center; margin: 6px 0; }
        .sign { margin-top: 28px; border-top: 1px solid #000; text-align: center; padding-top: 2px; }
        .tools { text-align: center; margin: 12px; font-family: system-ui, sans-serif; }
        .tools a, .tools button { display: inline-block; margin: 4px; padding: 8px 14px; border-radius: 8px; border: 1px solid #94a3b8; background: #fff; color: #0f172a; text-decoration: none; font-size: 14px; cursor: pointer; }
        .tools button { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        @media print { body { background: #fff; } .paper { margin: 0; } .tools { display: none; } }
    </style>
</head>
<body>
    <div class="tools">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('course-teacher-payments.show', $payout->teacher_id) }}">Back</a>
    </div>
    <div class="paper">
        <div class="center big">{{ config('institution.name', config('app.name')) }}</div>
        <div class="center">COURSE TEACHER PAYMENT</div>
        <div class="line"></div>
        @if ($payout->status !== 'paid')<div class="cancelled">CANCELLED</div>@endif
        <div class="row"><span>No</span><span>CTP-{{ str_pad($payout->id, 6, '0', STR_PAD_LEFT) }}</span></div>
        <div class="row"><span>Date</span><span>{{ $payout->paid_on->format('Y-m-d') }}</span></div>
        <div>{{ $payout->teacher->full_name }} ({{ $payout->teacher->custom_id }})</div>
        <div class="line"></div>
        <div>{{ $payout->course->name }}</div>
        <div class="row"><span>Method</span><span>{{ ucwords(str_replace('_', ' ', $payout->payment_method)) }}</span></div>
        <div class="row big"><span>Paid</span><span>{{ number_format($payout->amount, 2) }}</span></div>
        @if ($row)
            <div class="line"></div>
            <div class="row"><span>Earned</span><span>{{ number_format($row['earned'], 2) }}</span></div>
            <div class="row"><span>Total paid</span><span>{{ number_format($row['paid'], 2) }}</span></div>
            <div class="row"><span>Balance</span><span>{{ number_format($row['balance'], 2) }}</span></div>
        @endif
        @if ($payout->note)<div class="line"></div><div>{{ $payout->note }}</div>@endif
        <div class="line"></div>
        <div class="center">By {{ $payout->paidBy->name ?? '' }}</div>
        <div class="sign">Teacher's signature</div>
    </div>
</body>
</html>
