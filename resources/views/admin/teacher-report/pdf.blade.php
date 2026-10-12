<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>
        {{ $title ?? 'Teacher Daily Collection Report' }}
        - {{ config('app.name', 'EDU NEXORA') }}
    </title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            padding: 15px;
            line-height: 1.4;
        }

        /* =========================
           Header
        ========================== */

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #2563eb;
        }

        .header h1 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .header .date {
            font-size: 9px;
            color: #64748b;
            margin-top: 5px;
        }

        /* =========================
           Company Info
        ========================== */

        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 12px;
            font-weight: 700;
            color: #2563eb;
            letter-spacing: 0.5px;
        }

        /* =========================
           Teacher Info Card
        ========================== */

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            width: 25%;
            vertical-align: top;
            padding-right: 10px;
        }

        .info-label {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
        }

        /* =========================
           Summary Card
        ========================== */

        .summary-card {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            vertical-align: middle;
        }

        .summary-left {
            width: 70%;
        }

        .summary-right {
            width: 30%;
            text-align: right;
        }

        .summary-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #64748b;
        }

        .summary-value {
            font-size: 16px;
            font-weight: 800;
            color: #1e40af;
        }

        .summary-count {
            background: #dbeafe;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            color: #1e40af;
        }

        /* =========================
           Table
        ========================== */

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        .data-table th {
            background: #0f172a;
            color: white;
            padding: 8px 5px;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #334155;
            vertical-align: middle;
        }

        .data-table td {
            padding: 7px 5px;
            font-size: 7.5px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
            word-wrap: break-word;
        }

        .data-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .amount {
            font-weight: 700;
            font-family: monospace;
        }

        .amount-income {
            color: #166534;
        }

        /* =========================
           Fee Option
        ========================== */

        .fee-option {
            font-weight: 700;
            color: #1e40af;
        }

        .fee-value {
            font-weight: 700;
            color: #475569;
            white-space: nowrap;
        }

        /* =========================
           Payment Method Badges
        ========================== */

        .method-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: 600;
        }

        .method-cash {
            background: #dcfce7;
            color: #166534;
        }

        .method-card {
            background: #dbeafe;
            color: #1e40af;
        }

        .method-bank {
            background: #fef3c7;
            color: #92400e;
        }

        .method-online {
            background: #e0e7ff;
            color: #3730a3;
        }

        /* =========================
           Footer
        ========================== */

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>

<body>

    @php
        /*
        |--------------------------------------------------------------------------
        | Safe PDF Variables
        |--------------------------------------------------------------------------
        */

        $reportTitle = $title ?? 'Teacher Daily Collection Report';
        $reportDate = $date ?? now()->format('Y-m-d');

        $teacherIdValue = $teacher_id ?? '-';

        $reportRows = is_array($rows ?? null)
            ? $rows
            : [];

        $totalPaid = (float) ($summary_value ?? 0);

        $totalRecords = count($reportRows);
    @endphp


    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="header">

        <h1>
            {{ $reportTitle }}
        </h1>

        <div class="date">
            Generated on:
            {{ now()->format('d F Y, h:i A') }}
        </div>

    </div>


    {{-- =========================================================
         COMPANY
    ========================================================== --}}

    <div class="company-info">

        <div class="company-name">
            {{ config('app.name', 'EDU NEXORA') }}
        </div>

    </div>


    {{-- =========================================================
         TEACHER INFORMATION
    ========================================================== --}}

    <div class="info-card">

        <table class="info-table">

            <tr>

                <td>

                    <div class="info-label">
                        Teacher ID
                    </div>

                    <div class="info-value">
                        {{ $teacherIdValue }}
                    </div>

                </td>


                <td>

                    <div class="info-label">
                        Report Date
                    </div>

                    <div class="info-value">
                        {{ \Carbon\Carbon::parse($reportDate)->format('d M Y') }}
                    </div>

                </td>


                <td>

                    <div class="info-label">
                        Total Records
                    </div>

                    <div class="info-value">
                        {{ $totalRecords }}
                    </div>

                </td>


                <td>

                    <div class="info-label">
                        Total Paid
                    </div>

                    <div
                        class="info-value"
                        style="color: #166534;"
                    >
                        Rs. {{ number_format($totalPaid, 2) }}
                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- =========================================================
         SUMMARY
    ========================================================== --}}

    <div class="summary-card">

        <table class="summary-table">

            <tr>

                <td class="summary-left">

                    <div class="summary-label">
                        TOTAL PAYMENT AMOUNT
                    </div>

                    <div class="summary-value">
                        Rs. {{ number_format($totalPaid, 2) }}
                    </div>

                </td>


                <td class="summary-right">

                    <span class="summary-count">
                        {{ $totalRecords }} Transactions
                    </span>

                </td>

            </tr>

        </table>

    </div>


    {{-- =========================================================
         PAYMENTS TABLE
    ========================================================== --}}

    <table class="data-table">

        <thead>

            <tr>

                <th style="width: 8%;">
                    Class
                </th>

                <th style="width: 6%;">
                    Grade
                </th>

                <th style="width: 9%;">
                    Category
                </th>

                <th style="width: 11%;">
                    Fee Option
                </th>

                <th style="width: 6%;" class="text-right">
                    Fee
                </th>

                <th style="width: 8%;">
                    Student Code
                </th>

                <th style="width: 11%;">
                    Student Name
                </th>

                <th style="width: 9%;">
                    Guardian Mobile
                </th>

                <th style="width: 6%;">
                    Payment ID
                </th>

                <th style="width: 8%;">
                    Paid At
                </th>

                <th style="width: 8%;" class="text-right">
                    Amount
                </th>

                <th style="width: 8%;">
                    Method
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse($reportRows as $row)

                @php
                    $paymentMethod = strtolower(
                        $row['payment_method'] ?? 'cash'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | PHP 7.4 Compatible Payment Method Class
                    |--------------------------------------------------------------------------
                    */

                    if ($paymentMethod === 'cash') {

                        $methodClass = 'method-cash';

                    } elseif ($paymentMethod === 'card') {

                        $methodClass = 'method-card';

                    } elseif (
                        $paymentMethod === 'bank_transfer' ||
                        $paymentMethod === 'bank'
                    ) {

                        $methodClass = 'method-bank';

                    } elseif ($paymentMethod === 'online') {

                        $methodClass = 'method-online';

                    } else {

                        $methodClass = 'method-cash';
                    }


                    $paymentMethodLabel = $row['payment_method'] ?? 'Cash';


                    /*
                    |--------------------------------------------------------------------------
                    | Fee Option
                    |--------------------------------------------------------------------------
                    */

                    $feeOption = $row['fee_option'] ?? '-';

                    $feeOptionFee = (float) (
                        $row['fee_option_fee'] ?? 0
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Paid Date
                    |--------------------------------------------------------------------------
                    */

                    $paidAt = $row['paid_at'] ?? null;

                    if ($paidAt) {

                        try {

                            $paidAtFormatted = \Carbon\Carbon::parse(
                                $paidAt
                            )->format('d M Y');

                        } catch (\Throwable $e) {

                            $paidAtFormatted = '-';
                        }

                    } else {

                        $paidAtFormatted = '-';
                    }
                @endphp


                <tr>

                    {{-- Class --}}
                    <td>
                        {{ \Illuminate\Support\Str::limit(
                            $row['class_name'] ?? '-',
                            12
                        ) }}
                    </td>


                    {{-- Grade --}}
                    <td>
                        {{ $row['grade_name'] ?? '-' }}
                    </td>


                    {{-- Category --}}
                    <td>
                        {{ \Illuminate\Support\Str::limit(
                            $row['category_name'] ?? '-',
                            12
                        ) }}
                    </td>


                    {{-- Fee Option --}}
                    <td class="fee-option">
                        {{ \Illuminate\Support\Str::limit(
                            $feeOption,
                            18
                        ) }}
                    </td>


                    {{-- Fee --}}
                    <td class="text-right fee-value">
                        Rs. {{ number_format($feeOptionFee, 2) }}
                    </td>


                    {{-- Student Code --}}
                    <td>
                        <strong>
                            {{ $row['student_code'] ?? '-' }}
                        </strong>
                    </td>


                    {{-- Student Name --}}
                    <td>
                        {{ \Illuminate\Support\Str::limit(
                            $row['student_name'] ?? '-',
                            18
                        ) }}
                    </td>


                    {{-- Guardian Mobile --}}
                    <td>
                        {{ $row['guardian_mobile'] ?? '-' }}
                    </td>


                    {{-- Payment ID --}}
                    <td>
                        <code>
                            {{ $row['payment_id'] ?? '-' }}
                        </code>
                    </td>


                    {{-- Paid At --}}
                    <td>
                        {{ $paidAtFormatted }}
                    </td>


                    {{-- Amount --}}
                    <td class="text-right amount amount-income">

                        Rs.
                        {{ number_format(
                            (float) ($row['amount'] ?? 0),
                            2
                        ) }}

                    </td>


                    {{-- Method --}}
                    <td>

                        <span class="method-badge {{ $methodClass }}">
                            {{ ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $paymentMethodLabel
                                )
                            ) }}
                        </span>

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="12"
                        class="text-center"
                    >
                        No records found.
                    </td>

                </tr>

            @endforelse

        </tbody>


        {{-- =====================================================
             TABLE FOOTER
        ====================================================== --}}

        <tfoot>

            <tr
                style="
                    background: #eff6ff;
                    font-weight: 800;
                "
            >

                <td
                    colspan="10"
                    class="text-right"
                >
                    <strong>
                        Total
                    </strong>
                </td>


                <td
                    class="text-right amount"
                >

                    <strong>
                        Rs.
                        {{ number_format(
                            $totalPaid,
                            2
                        ) }}
                    </strong>

                </td>


                <td></td>

            </tr>

        </tfoot>

    </table>


    {{-- =========================================================
         FOOTER
    ========================================================== --}}

    <div class="footer">

        Generated by
        {{ config('app.name', 'EDU NEXORA') }}
        System on
        {{ now()->format('d M Y, h:i A') }}

    </div>


</body>

</html>