<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salary Slip - {{ $data['teacher_name'] ?? 'Teacher' }} - {{ $data['month_year_display'] ?? '' }}</title>

    <style>
        @page {
            size: A4;
            margin: 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1e293b;
            background: #f1f5f9;
            padding: 24px;
            line-height: 1.45;
        }

        .toolbar {
            max-width: 820px;
            margin: 0 auto 14px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .toolbar button {
            border: none;
            border-radius: 10px;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            background: #2563eb;
            color: #fff;
        }

        .toolbar button.secondary {
            background: #e2e8f0;
            color: #0f172a;
        }

        .slip {
            position: relative;
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }

        /* Header */
        .slip-header {
            background: #1e40af;
            color: #fff;
            padding: 20px 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .slip-header h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: .5px;
        }

        .slip-header .contact {
            font-size: 11px;
            opacity: .85;
            margin-top: 2px;
        }

        .slip-header .doc-title {
            text-align: right;
        }

        .slip-header .doc-title .label {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .slip-header .doc-title .period {
            font-size: 12px;
            opacity: .9;
        }

        .slip-body {
            padding: 22px 26px;
        }

        /* Draft banner */
        .draft-banner {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 700;
            margin-bottom: 16px;
            text-align: center;
        }

        .watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-25deg);
            font-size: 90px;
            font-weight: 900;
            color: rgba(220, 38, 38, .08);
            pointer-events: none;
            white-space: nowrap;
        }

        /* Info */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .info-table td {
            width: 33.33%;
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            vertical-align: top;
        }

        .info-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 700;
            color: #64748b;
        }

        .info-value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .info-value.paid {
            color: #15803d;
        }

        .info-value.unpaid {
            color: #dc2626;
        }

        /* Salary table */
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .salary-table th {
            background: #0f172a;
            color: #fff;
            padding: 9px 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            text-align: left;
        }

        .salary-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .salary-table td.split {
            border-left: 2px solid #e2e8f0;
        }

        .salary-table .total-row td {
            background: #eff6ff;
            font-weight: 800;
            color: #1e3a8a;
            border-top: 2px solid #bfdbfe;
        }

        .salary-table .net-row td {
            background: #1e40af;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
        }

        .text-right {
            text-align: right;
        }

        .amount {
            font-family: 'DejaVu Sans Mono', Consolas, monospace;
            font-weight: 700;
            white-space: nowrap;
        }

        .income {
            color: #166534;
        }

        .deduction {
            color: #991b1b;
        }

        .muted {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }

        /* Words */
        .words {
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .words .info-label {
            margin-bottom: 2px;
        }

        .words .text {
            font-weight: 700;
        }

        /* Breakdown */
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .breakdown-table th,
        .breakdown-table td {
            border: 1px solid #e2e8f0;
            padding: 7px 10px;
        }

        .breakdown-table th {
            background: #f1f5f9;
            font-size: 10px;
            text-transform: uppercase;
            color: #475569;
            text-align: left;
            width: 25%;
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            gap: 40px;
            margin-top: 40px;
        }

        .signature {
            flex: 1;
            text-align: center;
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            font-size: 11px;
            color: #475569;
        }

        .slip-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 10px 20px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }

        /* Print */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .slip {
                box-shadow: none;
                border: none;
                border-radius: 0;
                max-width: none;
            }

            .slip-header,
            .salary-table th,
            .salary-table .total-row td,
            .salary-table .net-row td,
            .draft-banner {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    @php
        $data = $data ?? [];

        $earnings = $data['earnings'] ?? [];
        $deductions = $data['deductions'] ?? [];
        $rowCount = max(count($earnings), count($deductions), 1);

        $isPaid = (bool) ($data['is_salary_paid'] ?? false);
        $money = fn ($value) => number_format((float) $value, 2);
    @endphp

    <div class="toolbar">
        <button type="button" class="secondary" onclick="window.close()">Close</button>
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="slip">

        @unless ($isPaid)
            <div class="watermark">DRAFT</div>
        @endunless

        {{-- Header --}}
        <div class="slip-header">
            <div>
                <h1>{{ config('institution.name') }}</h1>
                <div class="contact">
                    {{ config('institution.location') }}
                    @if ($data['branch_name'] ?? null)
                        • {{ $data['branch_name'] }} Branch
                    @endif
                </div>
                <div class="contact">
                    @if (config('institution.phone'))
                        Tel: {{ config('institution.phone') }}
                    @endif
                    @if (config('institution.email'))
                        &nbsp;|&nbsp; {{ config('institution.email') }}
                    @endif
                </div>
            </div>

            <div class="doc-title">
                <div class="label">SALARY SLIP</div>
                <div class="period">{{ $data['month_year_display'] ?? '' }}</div>
            </div>
        </div>

        <div class="slip-body">

            @unless ($isPaid)
                <div class="draft-banner">
                    DRAFT — this salary has not been paid yet. Amounts may still change.
                </div>
            @endunless

            {{-- Teacher information --}}
            <table class="info-table">
                <tr>
                    <td>
                        <div class="info-label">Teacher Code</div>
                        <div class="info-value">{{ $data['teacher_custom_id'] ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="info-label">Teacher Name</div>
                        <div class="info-value">{{ $data['teacher_name'] ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="info-label">Salary Period</div>
                        <div class="info-value">{{ $data['month_year_display'] ?? '-' }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="info-label">Payment Status</div>
                        <div class="info-value {{ $isPaid ? 'paid' : 'unpaid' }}">
                            {{ $isPaid ? 'PAID' : 'NOT PAID' }}
                        </div>
                    </td>
                    <td>
                        <div class="info-label">Paid On</div>
                        <div class="info-value">{{ $data['paid_at'] ?? '-' }}</div>
                    </td>
                    <td>
                        <div class="info-label">Payment Method</div>
                        <div class="info-value">{{ $data['payment_method'] ?? '-' }}</div>
                    </td>
                </tr>
            </table>

            {{-- Earnings & deductions --}}
            <table class="salary-table">
                <thead>
                    <tr>
                        <th style="width: 38%;">Earnings</th>
                        <th style="width: 16%;" class="text-right">Amount (Rs.)</th>
                        <th style="width: 30%;">Deductions</th>
                        <th style="width: 16%;" class="text-right">Amount (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < $rowCount; $i++)
                        @php
                            $earning = $earnings[$i] ?? null;
                            $deduction = $deductions[$i] ?? null;
                        @endphp
                        <tr>
                            <td>
                                @if ($earning)
                                    <strong>{{ $earning['description'] ?? '' }}</strong>
                                    @isset($earning['class_total'])
                                        <div class="muted">
                                            Class collection: Rs. {{ $money($earning['class_total']) }}
                                            • Teacher share {{ rtrim(rtrim(number_format((float) ($earning['teacher_percentage'] ?? 0), 2), '0'), '.') }}%
                                        </div>
                                    @endisset
                                @elseif ($i === 0)
                                    <span class="muted">No class earnings for this month</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($earning)
                                    <span class="amount income">{{ $money($earning['amount'] ?? 0) }}</span>
                                @endif
                            </td>
                            <td class="split">
                                @if ($deduction)
                                    {{ $deduction['description'] ?? '' }}
                                @elseif ($i === 0)
                                    <span class="muted">No deductions</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($deduction)
                                    <span class="amount deduction">{{ $money($deduction['amount'] ?? 0) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endfor

                    <tr class="total-row">
                        <td>Total Earnings</td>
                        <td class="text-right amount">{{ $money($data['total_addition'] ?? 0) }}</td>
                        <td class="split">Total Deductions</td>
                        <td class="text-right amount">{{ $money($data['total_deductions'] ?? 0) }}</td>
                    </tr>

                    <tr class="net-row">
                        <td colspan="3">NET SALARY</td>
                        <td class="text-right amount">Rs. {{ $money($data['net_salary'] ?? 0) }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- Amount in words --}}
            <div class="words">
                <div class="info-label">Amount in words</div>
                <div class="text">{{ $data['net_salary_words'] ?? '' }}</div>
            </div>

            {{-- Class income breakdown --}}
            <table class="breakdown-table">
                <tr>
                    <th>Total Class Collection</th>
                    <td class="text-right amount">Rs. {{ $money($data['total_class_amount'] ?? 0) }}</td>
                    <th>Teacher Earnings</th>
                    <td class="text-right amount">Rs. {{ $money($data['total_teacher_earnings'] ?? 0) }}</td>
                </tr>
                <tr>
                    <th>Organizer Share</th>
                    <td class="text-right amount">Rs. {{ $money($data['total_organize_cut'] ?? 0) }}</td>
                    <th>Institute Share</th>
                    <td class="text-right amount">Rs. {{ $money($data['total_institution_cut'] ?? 0) }}</td>
                </tr>
            </table>

            {{-- Signatures --}}
            <div class="signatures">
                <div class="signature">Teacher Signature</div>
                <div class="signature">Authorized Signature</div>
            </div>

        </div>

        <div class="slip-footer">
            Generated on {{ $data['date_generated'] ?? now()->format('d M Y, h:i A') }}
            by {{ config('institution.name') }}.
            For any questions about this slip, please contact the office.
        </div>

    </div>

    <script>
        window.addEventListener('load', function () {
            const params = new URLSearchParams(window.location.search);

            if (params.get('autoPrint') === 'true') {
                setTimeout(function () {
                    window.print();
                }, 600);
            }
        });
    </script>

</body>

</html>