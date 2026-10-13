@php
    $student = $receipt['student_details'] ?? [];
    $rows = $receipt['classes'] ?? [];
    $summary = $receipt['summary'] ?? [];
    $totalHallFee = (float) ($summary['total_hall_fee'] ?? 0);
    $totalFee = (float) ($summary['total_final_fee'] ?? ($receipt['total'] ?? 0));
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        @page { size: 58mm auto; margin: 0; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; }
        body { font-family: monospace; font-size: 11px; }
        .receipt-paper { width: 58mm; box-sizing: border-box; padding: 3mm; margin: 0 auto; }
        .receipt-line { border-top: 1px dashed #000; margin: 6px 0; }
        .receipt-item, .receipt-summary { line-height: 1.5; word-break: break-word; }
        .text-center { text-align: center; }
        .reprint { font-size: 9px; }
        .no-print { text-align: center; margin: 12px 0; font-family: sans-serif; }
        .no-print button { padding: 6px 14px; font-size: 13px; cursor: pointer; }
        @media print { .no-print { display: none; } }
    </style>
</head>

<body>
    <div class="receipt-paper">
        <div class="text-center">
            <strong>NEXORA EDU</strong><br>
            PAYMENT RECEIPT<br>
            <span class="reprint">(COPY)</span>
        </div>
        <div class="receipt-line"></div>
        Student ID: {{ $student['custom_id'] ?? '-' }}<br>
        Student: {{ $student['name'] ?? '-' }}<br>
        Date: {{ $receipt['payment_date'] ?? '-' }}<br>
        Time: {{ $receipt['payment_time'] ?? '-' }}
        <div class="receipt-line"></div>

        @foreach ($rows as $row)
            <div class="receipt-item">
                <strong>{{ $row['class_name'] ?? '-' }}</strong><br>
                Category: {{ $row['category_name'] ?? '-' }}<br>
                Grade: {{ $row['grade'] ?? '-' }}<br>
                Teacher: {{ $row['teacher_name'] ?? '-' }}<br>
                Month: {{ $row['payment_month'] ?? '-' }}<br>
                Receipt: {{ $row['receipt_no'] ?? $payment->receipt_number }}<br>
                Class Fee: Rs. {{ number_format((float) ($row['class_fee'] ?? 0), 2) }}<br>
                @if ((float) ($row['hall_fee'] ?? 0) > 0)
                    Hall Fee: Rs. {{ number_format((float) $row['hall_fee'], 2) }}<br>
                @endif
            </div>
            <div class="receipt-line"></div>
        @endforeach

        <div class="receipt-summary">
            @if ($totalHallFee > 0)
                Hall Fee: Rs. {{ number_format($totalHallFee, 2) }}<br>
            @endif
            <strong>TOTAL FEE: Rs. {{ number_format($totalFee, 2) }}</strong>
        </div>
        <div class="receipt-line"></div>
        Payment: {{ $receipt['payment_method'] ?? '-' }}<br>
        Printed: {{ now()->format('Y-m-d H:i') }}<br><br>
        <div class="text-center">Thank You!</div>
    </div>

    <div class="no-print">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>

</html>
