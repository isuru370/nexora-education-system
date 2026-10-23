<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        @page { size: 58mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef1f6; font-family: monospace; font-size: 11px; color: #000; }
        .paper { width: 58mm; margin: 16px auto; padding: 3mm; background: #fff; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; gap: 6px; line-height: 1.6; }
        .row span:last-child { text-align: right; }
        .big { font-size: 14px; font-weight: bold; }
        .cancelled { border: 2px solid #000; padding: 2px; font-weight: bold; text-align: center; margin: 6px 0; }
        .tools { text-align: center; margin: 12px; font-family: system-ui, sans-serif; }
        .tools a, .tools button { display: inline-block; margin: 4px; padding: 8px 14px; border-radius: 8px; border: 1px solid #94a3b8; background: #fff; color: #0f172a; text-decoration: none; font-size: 14px; cursor: pointer; }
        .tools button { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
        @media print { body { background: #fff; } .paper { margin: 0; } .tools { display: none; } }
    </style>
</head>
<body>
    <div class="tools">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('courses.enrollments.show', [$payment->course_id, $payment->course_enrollment_id]) }}">Back to student</a>
    </div>

    <div class="paper">
        <div class="center big">{{ config('institution.name', config('app.name')) }}</div>
        <div class="center">{{ $payment->branch->name ?? '' }}</div>
        <div class="center">COURSE PAYMENT</div>
        <div class="line"></div>

        @if (!$payment->isCompleted())
            <div class="cancelled">CANCELLED</div>
        @endif

        <div class="row"><span>Receipt</span><span>{{ $payment->receipt_number }}</span></div>
        <div class="row"><span>Date</span><span>{{ $payment->paid_at->format('Y-m-d h:i A') }}</span></div>
        <div class="row"><span>Student</span><span>{{ $payment->student->custom_id ?? '' }}</span></div>
        <div>{{ $payment->student->initial_name ?? '' }}</div>
        <div class="line"></div>
        <div>{{ $payment->course->name ?? '' }}</div>
        <div class="row"><span>Method</span><span>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</span></div>
        <div class="row big"><span>Paid</span><span>{{ number_format($payment->amount, 2) }}</span></div>
        <div class="line"></div>
        @if ($fees['special_fee'])
            <div class="row"><span>Special fee</span><span>{{ number_format($fees['total_fee'], 2) }}</span></div>
            <div>({{ $fees['fee_reason'] }})</div>
        @else
            <div class="row"><span>Course fee</span><span>{{ number_format($fees['total_fee'], 2) }}</span></div>
        @endif
        <div class="row"><span>Total paid</span><span>{{ number_format($fees['paid'], 2) }}</span></div>
        <div class="row"><span>Balance</span><span>{{ number_format($fees['balance'], 2) }}</span></div>
        @if ($fees['next_installment'])
            <div class="row"><span>Next due</span><span>{{ \Carbon\Carbon::parse($fees['next_installment']['due_date'])->format('Y-m-d') }}</span></div>
        @endif
        <div class="line"></div>
        <div class="center">By {{ $payment->takenBy->name ?? '' }}</div>
        <div class="center">Thank you!</div>
    </div>
</body>
</html>
