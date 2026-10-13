@push('styles')
    <style>
        .report-page {
            animation: fadeIn .4s ease;
        }

        .report-hero {
            border-radius: 28px;
            padding: 1.6rem 1.8rem;
            background: linear-gradient(135deg, #2563eb, #1d4ed8, #1e40af);
            box-shadow: 0 20px 45px rgba(37, 99, 235, .22);
            color: #fff;
            margin-bottom: 1.25rem;
        }

        .report-hero .sub {
            color: rgba(255, 255, 255, .8);
        }

        .report-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid #eef2f7;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
            padding: 1.25rem 1.4rem;
            margin-bottom: 1.25rem;
        }

        .report-card h6 {
            font-weight: 800;
            color: #0f172a;
        }

        .report-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: .35rem;
            display: block;
        }

        .report-input {
            border-radius: 12px !important;
            border: 1px solid #e2e8f0;
            min-height: 44px;
        }

        .stat-card {
            border-radius: 20px;
            padding: 1.1rem 1.2rem;
            color: #fff;
            height: 100%;
        }

        .stat-card .label {
            font-size: .78rem;
            opacity: .9;
        }

        .stat-card .value {
            font-size: 1.35rem;
            font-weight: 800;
            word-break: break-word;
        }

        .stat-blue { background: linear-gradient(135deg, #0f172a, #334155); }
        .stat-green { background: linear-gradient(135deg, #059669, #10b981); }
        .stat-red { background: linear-gradient(135deg, #dc2626, #f87171); }
        .stat-primary { background: linear-gradient(135deg, #2563eb, #3b82f6); }
        .stat-amber { background: linear-gradient(135deg, #d97706, #f59e0b); }

        .report-tabs .nav-link {
            border-radius: 12px;
            font-weight: 600;
            color: #475569;
            padding: .55rem 1rem;
        }

        .report-tabs .nav-link.active {
            background: #2563eb;
            color: #fff;
        }

        .report-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: .75rem;
            text-transform: uppercase;
            border: none;
            padding: .8rem;
            white-space: nowrap;
        }

        .report-table tbody td,
        .report-table tfoot td {
            padding: .7rem .8rem;
            border-color: #f1f5f9;
            font-size: .88rem;
        }

        .report-table tfoot td {
            background: #eff6ff;
        }

        .report-table .amount {
            font-family: 'DejaVu Sans Mono', Consolas, monospace;
            white-space: nowrap;
        }

        .mini-table td {
            padding: .45rem .2rem;
            border-bottom: 1px dashed #e2e8f0;
        }

        .btn-export {
            border-radius: 12px;
            font-weight: 600;
        }

        .progress-thin {
            height: 8px;
            border-radius: 999px;
        }
    </style>
@endpush
