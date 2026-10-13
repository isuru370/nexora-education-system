<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Receipts Report - {{ config('app.name', 'EDU NEXORA') }}</title>

    <style>
        @page {
            margin: 18px 20px 30px 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #1e293b;
            line-height: 1.35;
        }

        /* Header */
        .header {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .header td {
            vertical-align: bottom;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
        }

        .company-name {
            font-size: 11px;
            font-weight: bold;
            color: #2563eb;
        }

        .meta {
            font-size: 8px;
            color: #64748b;
            text-align: right;
        }

        /* Filters line */
        .filters {
            font-size: 8px;
            color: #475569;
            margin-bottom: 10px;
        }

        .filters strong {
            color: #0f172a;
        }

        /* Summary */
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 12px;
        }

        .summary-table td {
            padding: 8px 10px;
            text-align: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            width: 25%;
        }

        .summary-label {
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
            color: #64748b;
            margin-bottom: 3px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
        }

        .c-blue { color: #2563eb; }
        .c-green { color: #166534; }
        .c-red { color: #dc2626; }

        /* Data table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            display: table-header-group; /* repeat header on each page */
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table th {
            background: #0f172a;
            color: #ffffff;
            padding: 6px 5px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: left;
            border: 1px solid #334155;
        }

        .data-table td {
            padding: 5px;
            font-size: 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .data-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .data-table tbody tr.deleted-row td {
            background: #fef2f2;
            color: #7f1d1d;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .receipt-code {
            font-family: 'DejaVu Sans Mono', monospace;
            font-weight: bold;
        }

        .muted {
            color: #64748b;
            font-size: 7px;
        }

        .amount {
            font-family: 'DejaVu Sans Mono', monospace;
            font-weight: bold;
            white-space: nowrap;
        }

        .amount-void {
            text-decoration: line-through;
            color: #94a3b8;
        }

        .type-badge {
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 7px;
            font-weight: bold;
            white-space: nowrap;
        }

        .type-payment { background: #dbeafe; color: #1e40af; }
        .type-admission { background: #dcfce7; color: #166534; }
        .type-extra { background: #fef3c7; color: #92400e; }

        .status-active,
        .status-deleted {
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 7px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-active { background: #dcfce7; color: #166534; }
        .status-deleted { background: #fee2e2; color: #991b1b; }

        /* Total */
        .footer-total {
            margin-top: 12px;
            padding: 8px 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            text-align: right;
        }

        .footer-total .label {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
            color: #64748b;
            margin-right: 8px;
        }

        .footer-total .value {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
        }

        .footer {
            margin-top: 12px;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    @php
        $filters = $filters ?? [];
        $statusLabel = [
            'deleted' => 'Deleted only',
            'all' => 'All (active + deleted)',
        ][$filters['status'] ?? ''] ?? 'Active only';

        $typeClass = [
            'Student Payment' => 'type-payment',
            'Admission Payment' => 'type-admission',
            'Extra Income' => 'type-extra',
        ];
    @endphp

    {{-- Header --}}
    <table class="header">
        <tr>
            <td>
                <div class="company-name">{{ config('app.name', 'EDU NEXORA') }}</div>
                <h1>Receipts Report</h1>
            </td>
            <td class="meta">
                Generated: {{ now()->format('d M Y, h:i A') }}<br>
                By: {{ optional(auth()->user())->name ?? 'System' }}
            </td>
        </tr>
    </table>

    {{-- Filters used --}}
    <div class="filters">
        <strong>Period:</strong>
        {{ !empty($filters['from_date']) ? \Carbon\Carbon::parse($filters['from_date'])->format('d M Y') : 'Start' }}
        –
        {{ !empty($filters['to_date']) ? \Carbon\Carbon::parse($filters['to_date'])->format('d M Y') : 'Today' }}
        &nbsp;|&nbsp;
        <strong>Type:</strong> {{ $filters['type'] ?? '' ?: 'All' }}
        &nbsp;|&nbsp;
        <strong>Status:</strong> {{ $statusLabel }}
        @if (!empty($filters['receipt_number']))
            &nbsp;|&nbsp; <strong>Receipt:</strong> {{ $filters['receipt_number'] }}
        @endif
    </div>

    {{-- Summary --}}
    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Receipts in report</div>
                <div class="summary-value c-blue">{{ number_format($totalReceipts ?? 0) }}</div>
            </td>
            <td>
                <div class="summary-label">Active</div>
                <div class="summary-value c-green">{{ number_format($activeReceipts ?? 0) }}</div>
            </td>
            <td>
                <div class="summary-label">Deleted</div>
                <div class="summary-value c-red">{{ number_format($deletedReceipts ?? 0) }}</div>
            </td>
            <td>
                <div class="summary-label">Active total</div>
                <div class="summary-value c-green">Rs. {{ number_format($totalAmount ?? 0, 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- Receipts --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 12%;">Receipt No</th>
                <th style="width: 11%;">Type</th>
                <th style="width: 18%;">Student</th>
                <th style="width: 17%;">Class / Month</th>
                <th style="width: 10%;" class="text-right">Amount (Rs.)</th>
                <th style="width: 16%;">Status</th>
                <th style="width: 12%;">Date</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($receipts as $receipt)
                @php
                    $isActive = ($receipt['status'] ?? 'Active') === 'Active';
                    $hasStudent = ($receipt['student_name'] ?? 'N/A') !== 'N/A';
                    $hasClass = !empty($receipt['class_name']) && $receipt['class_name'] !== 'N/A';
                @endphp

                <tr class="{{ $isActive ? '' : 'deleted-row' }}">
                    <td class="text-center">{{ $loop->iteration }}</td>

                    <td>
                        <span class="receipt-code">{{ $receipt['receipt_number'] ?: '-' }}</span>
                    </td>

                    <td>
                        <span class="type-badge {{ $typeClass[$receipt['type']] ?? 'type-payment' }}">
                            {{ $receipt['type'] }}
                        </span>
                    </td>

                    <td>
                        @if ($hasStudent)
                            {{ $receipt['student_name'] }}<br>
                            <span class="muted">{{ $receipt['student_id'] }}</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>

                    <td>
                        @if ($hasClass)
                            {{ $receipt['class_name'] }}
                            @if (!empty($receipt['category']) && $receipt['category'] !== 'N/A')
                                <span class="muted">({{ $receipt['category'] }})</span>
                            @endif
                            <br>
                        @endif
                        @if (!empty($receipt['payment_month']))
                            <span class="muted">{{ \Carbon\Carbon::parse($receipt['payment_month'])->format('F Y') }}</span>
                        @elseif (!$hasClass)
                            <span class="muted">—</span>
                        @endif
                    </td>

                    <td class="text-right">
                        <span class="amount {{ $isActive ? '' : 'amount-void' }}">
                            {{ number_format((float) $receipt['amount'], 2) }}
                        </span>
                    </td>

                    <td>
                        @if ($isActive)
                            <span class="status-active">ACTIVE</span>
                        @else
                            <span class="status-deleted">{{ strtoupper($receipt['status']) }}</span>
                            @if (!empty($receipt['delete_reason']))
                                <br><span class="muted">{{ $receipt['delete_reason'] }}</span>
                            @endif
                            @if (!empty($receipt['deleted_by']))
                                <br><span class="muted">by {{ $receipt['deleted_by'] }}</span>
                            @endif
                        @endif
                    </td>

                    <td>
                        @if (!empty($receipt['date']))
                            {{ \Carbon\Carbon::parse($receipt['date'])->format('d M Y') }}<br>
                            <span class="muted">{{ \Carbon\Carbon::parse($receipt['date'])->format('h:i A') }}</span>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No receipts found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Total --}}
    <div class="footer-total">
        <span class="label">Active receipts total:</span>
        <span class="value">Rs. {{ number_format($totalAmount ?? 0, 2) }}</span>
    </div>

    <div class="footer">
        Generated by {{ config('app.name', 'EDU NEXORA') }} on {{ now()->format('d M Y, h:i A') }}.
        Deleted receipts are shown for audit only and are not included in the total.
    </div>
</body>

</html>