<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>
        Teacher Report - {{ config('app.name', 'EDU NEXORA') }}
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
           HEADER
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
           COMPANY
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
           SUMMARY
        ========================== */

        .summary-table {
            width: 70%;
            margin: 0 auto 25px auto;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 8px 10px;
            text-align: center;
            width: 33.33%;
        }

        .summary-table .total-card {
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
        }

        .summary-table .active-card {
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
        }

        .summary-table .inactive-card {
            background: #f8fafc;
        }

        .card-value {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .total-card .card-value {
            color: #2563eb;
        }

        .active-card .card-value {
            color: #10b981;
        }

        .inactive-card .card-value {
            color: #ef4444;
        }

        .card-label {
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .card-sub {
            font-size: 7px;
            color: #94a3b8;
        }

        /* =========================
           DATA TABLE
        ========================== */

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table th {
            background: #0f172a;
            color: white;
            padding: 8px 6px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #334155;
        }

        .data-table td {
            padding: 6px;
            font-size: 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .data-table tr:nth-child(even) {
            background: #f8fafc;
        }

        /* =========================
           BADGES
        ========================== */

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: 600;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        /* =========================
           SMALL TEXT
        ========================== */

        .small {
            font-size: 7px;
            color: #64748b;
        }

        /* =========================
           FOOTER
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

    {{-- =========================
         HEADER
    ========================== --}}

    <div class="header">

        <h1>
            Teacher Report
        </h1>

        <div class="date">
            Generated on: {{ now()->format('d F Y, h:i A') }}
        </div>

    </div>


    {{-- =========================
         COMPANY
    ========================== --}}

    <div class="company-info">

        <div class="company-name">
            {{ config('app.name', 'EDU NEXORA') }}
        </div>

    </div>


    {{-- =========================
         SUMMARY
    ========================== --}}

    @php

        $totalTeachers = $teachers->count();

        $activeTeachers = $teachers
            ->where('is_active', true)
            ->count();

        $inactiveTeachers = $teachers
            ->where('is_active', false)
            ->count();

    @endphp


    <table class="summary-table">

        <tr>

            {{-- TOTAL --}}

            <td class="total-card">

                <div class="card-value">
                    {{ $totalTeachers }}
                </div>

                <div class="card-label">
                    Total Teachers
                </div>

                <div class="card-sub">
                    All registered
                </div>

            </td>


            {{-- ACTIVE --}}

            <td class="active-card">

                <div class="card-value">
                    {{ $activeTeachers }}
                </div>

                <div class="card-label">
                    Active
                </div>

                <div class="card-sub">
                    Currently working
                </div>

            </td>


            {{-- INACTIVE --}}

            <td class="inactive-card">

                <div class="card-value">
                    {{ $inactiveTeachers }}
                </div>

                <div class="card-label">
                    Inactive
                </div>

                <div class="card-sub">
                    Not active
                </div>

            </td>

        </tr>

    </table>


    {{-- =========================
         TEACHER TABLE
    ========================== --}}

    <table class="data-table">

        <thead>

            <tr>

                <th style="width: 5%;">
                    #
                </th>

                <th style="width: 20%;">
                    Teacher Information
                </th>

                <th style="width: 15%;">
                    Contact Details
                </th>

                <th style="width: 15%;">
                    Personal Information
                </th>

                <th style="width: 20%;">
                    Bank & Institute
                </th>

                <th style="width: 15%;">
                    Qualification
                </th>

                <th style="width: 10%;">
                    Status
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse($teachers as $teacher)

                <tr>

                    {{-- =========================
                         NUMBER
                    ========================== --}}

                    <td>
                        {{ $loop->iteration }}
                    </td>


                    {{-- =========================
                         TEACHER INFORMATION
                    ========================== --}}

                    <td>

                        <strong>
                            {{ $teacher->full_name }}
                        </strong>

                        @if($teacher->initials)

                            <div class="small">
                                Initials: {{ $teacher->initials }}
                            </div>

                        @endif

                        <div class="small">
                            ID:
                            {{ $teacher->custom_id ?? '#' . $teacher->id }}
                        </div>

                    </td>


                    {{-- =========================
                         CONTACT DETAILS
                    ========================== --}}

                    <td>

                        <div>
                            {{ $teacher->mobile ?? '-' }}
                        </div>

                        <div class="small">
                            {{ $teacher->email ?? '-' }}
                        </div>

                    </td>


                    {{-- =========================
                         PERSONAL INFORMATION
                    ========================== --}}

                    <td>

                        @if($teacher->nic)

                            <div>
                                NIC: {{ $teacher->nic }}
                            </div>

                        @endif


                        @if($teacher->gender)

                            <div class="small">
                                Gender: {{ ucfirst($teacher->gender) }}
                            </div>

                        @endif


                        @if($teacher->bday)

                            <div class="small">

                                Birth:
                                {{ $teacher->bday->format('Y-m-d') }}

                            </div>

                        @endif

                    </td>


                    {{-- =========================
                         BANK & INSTITUTE DETAILS
                    ========================== --}}

                    <td>

                        {{-- BANK --}}

                        @if($teacher->bankBranch)

                            <strong>
                                {{ $teacher->bankBranch->bank->bank_name ?? 'N/A' }}
                            </strong>

                            {{-- BANK BRANCH --}}

                            <div class="small">

                                Bank Branch:
                                {{ $teacher->bankBranch->branch_name ?? 'N/A' }}

                            </div>

                        @else

                            <div class="small">
                                Bank: N/A
                            </div>

                            <div class="small">
                                Bank Branch: N/A
                            </div>

                        @endif


                        {{-- ACCOUNT NUMBER --}}

                        <div class="small">

                            Account:
                            {{ $teacher->account_number ?? 'N/A' }}

                        </div>


                        {{-- INSTITUTE BRANCH --}}

                        <div
                            class="small"
                            style="margin-top: 3px;"
                        >

                            <strong>
                                Institute Branch:
                            </strong>

                            @if($teacher->branches->isNotEmpty())

                                {{ $teacher->branches->pluck('name')->implode(', ') }}

                            @else

                                N/A

                            @endif

                        </div>

                    </td>


                    {{-- =========================
                         QUALIFICATION
                    ========================== --}}

                    <td>

                        <div class="small">

                            {{ \Illuminate\Support\Str::limit(
                                $teacher->graduation_details ?? '-',
                                50
                            ) }}

                        </div>


                        @if($teacher->experience)

                            <div class="small">

                                Exp:

                                {{ \Illuminate\Support\Str::limit(
                                    $teacher->experience,
                                    40
                                ) }}

                            </div>

                        @endif

                    </td>


                    {{-- =========================
                         STATUS
                    ========================== --}}

                    <td>

                        @if($teacher->is_active)

                            <span class="badge badge-success">
                                Active
                            </span>

                        @else

                            <span class="badge badge-danger">
                                Inactive
                            </span>

                        @endif


                        @if($teacher->created_at)

                            <div class="small">

                                Joined:
                                {{ $teacher->created_at->format('Y-m-d') }}

                            </div>

                        @endif

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="7"
                        style="text-align: center; padding: 20px;"
                    >
                        No teachers found.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>


    {{-- =========================
         FOOTER
    ========================== --}}

    <div class="footer">

        Generated by
        {{ config('app.name', 'EDU NEXORA') }}
        System

    </div>

</body>

</html>