@extends('layouts.app')

@section('title', 'Student Card Inventory')
@section('page-title', 'Student Card Inventory')

@section('content')

    <div class="halls-page">

        {{-- ===================== STATS (Global / Branch-wise) ===================== --}}
        <div class="row g-4 mb-4">

            {{-- Total Cards --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="bi bi-person-vcard-fill"></i>
                    </div>
                    <div>
                        <h3>{{ collect($branchCardSummary ?? [])->sum('total_cards') }}</h3>
                        <p>Total Cards</p>
                    </div>
                </div>
            </div>

            {{-- Available --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <h3>{{ collect($branchCardSummary ?? [])->sum('available') }}</h3>
                        <p>Available</p>
                    </div>
                </div>
            </div>

            {{-- Assigned --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <h3>{{ collect($branchCardSummary ?? [])->sum('assigned') }}</h3>
                        <p>Assigned</p>
                    </div>
                </div>
            </div>

            {{-- Lost / Damaged --}}
            <div class="col-xl-3 col-md-6">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <h3>
                            {{ collect($branchCardSummary ?? [])->sum('lost') + collect($branchCardSummary ?? [])->sum('damaged') }}
                        </h3>
                        <p>Lost / Damaged</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- ===================== BRANCH-WISE SUMMARY ===================== --}}
        @if (!empty($branchCardSummary) && count($branchCardSummary) > 0)
            <div class="row g-3 mb-4">

                @foreach ($branchCardSummary as $branch)
                    <div class="col-xl-4 col-md-6">

                        <div class="branch-summary-card">

                            <div class="branch-summary-header">
                                <div class="branch-info">
                                    <div class="branch-icon">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <div>
                                        <h5 class="branch-name">{{ $branch['branch_name'] }}</h5>
                                        <span class="branch-code">{{ $branch['branch_code'] }}</span>
                                    </div>
                                </div>
                                <span class="branch-total-badge">
                                    {{ $branch['total_cards'] }} Cards
                                </span>
                            </div>

                            <div class="branch-stats-grid">

                                <div class="branch-stat">
                                    <span class="stat-dot available"></span>
                                    <span class="stat-label">Available</span>
                                    <span class="stat-value">{{ $branch['available'] }}</span>
                                </div>

                                <div class="branch-stat">
                                    <span class="stat-dot assigned"></span>
                                    <span class="stat-label">Assigned</span>
                                    <span class="stat-value">{{ $branch['assigned'] }}</span>
                                </div>

                                <div class="branch-stat">
                                    <span class="stat-dot lost"></span>
                                    <span class="stat-label">Lost</span>
                                    <span class="stat-value">{{ $branch['lost'] }}</span>
                                </div>

                                <div class="branch-stat">
                                    <span class="stat-dot damaged"></span>
                                    <span class="stat-label">Damaged</span>
                                    <span class="stat-value">{{ $branch['damaged'] }}</span>
                                </div>

                                <div class="branch-stat">
                                    <span class="stat-dot inactive"></span>
                                    <span class="stat-label">Inactive</span>
                                    <span class="stat-value">{{ $branch['inactive'] }}</span>
                                </div>

                                <div class="branch-stat">
                                    <span class="stat-dot total"></span>
                                    <span class="stat-label">Total</span>
                                    <span class="stat-value fw-bold">{{ $branch['total_cards'] }}</span>
                                </div>

                            </div>

                        </div>

                    </div>
                @endforeach

            </div>
        @endif

        {{-- ===================== MAIN CARD ===================== --}}
        <div class="main-card">

            {{-- HEADER --}}
            <div class="main-card-header">

                <div>
                    <h4>Student Card Inventory</h4>
                    <p>Manage all student cards in the system.</p>
                </div>

                <div class="header-buttons">



                    @cardaccess('generate')
                    <a href="{{ route('student-cards.generate') }}" class="btn btn-primary custom-btn">
                        <i class="bi bi-plus-circle"></i>
                        Generate Cards
                    </a>
                    @endcardaccess



                    @cardaccess('download')
                    <a href="{{ route('student-cards.preview') }}" class="btn btn-success custom-btn">
                        <i class="bi bi-eye"></i>
                        Preview
                    </a>
                    @endcardaccess

                </div>

            </div>

            {{-- ===================== ALERTS ===================== --}}

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show custom-alert">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('success') }}
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show custom-alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    {{ session('error') }}
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show custom-alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ session('warning') }}
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- ===================== SEARCH ===================== --}}

            <div class="search-card">

                <form method="GET" action="{{ route('student-cards.index') }}">

                    <div class="row g-3">

                        {{-- Search Input --}}
                        <div class="col-lg-4 col-md-6">

                            <div class="search-input-wrapper">
                                <i class="bi bi-search"></i>

                                <input type="text" name="search" class="form-control custom-input"
                                    placeholder="Search by Card Number or QR Code" value="{{ request('search') }}">
                            </div>

                        </div>

                        {{-- Branch Filter --}}
                        <div class="col-lg-3 col-md-6">

                            <select name="branch_id" class="form-select custom-input">
                                <option value="">All Branches</option>

                                @foreach ($branches ?? [] as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                @endforeach
                            </select>

                        </div>

                        {{-- Status Filter --}}
                        <div class="col-lg-2 col-md-6">

                            <select name="status" class="form-select custom-input">

                                <option value="">All Status</option>

                                <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>
                                    Available
                                </option>

                                <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>
                                    Assigned
                                </option>

                                <option value="lost" {{ request('status') == 'lost' ? 'selected' : '' }}>
                                    Lost
                                </option>

                                <option value="damaged" {{ request('status') == 'damaged' ? 'selected' : '' }}>
                                    Damaged
                                </option>

                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                    Inactive
                                </option>

                            </select>

                        </div>

                        {{-- Search Button --}}
                        <div class="col-lg-2 col-md-6">

                            <button type="submit" class="btn btn-primary w-100 custom-btn">
                                <i class="bi bi-search"></i>
                                Search
                            </button>

                        </div>

                        {{-- Reset Button --}}
                        <div class="col-lg-1 col-md-6">

                            <a href="{{ route('student-cards.index') }}" class="btn btn-light border w-100 custom-btn"
                                title="Reset">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>

                        </div>

                    </div>

                </form>

            </div>

            {{-- ===================== TABLE ===================== --}}

            <div class="table-responsive">

                <table class="table custom-table align-middle">

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Card Number</th>
                            <th>QR Code</th>
                            <th>Branch</th>
                            <th>Student</th>
                            <th>Status</th>
                            <th>Issued Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($cards as $card)
                            <tr>

                                <td>
                                    {{ $cards->firstItem() + $loop->index }}
                                </td>

                                {{-- Card Number --}}
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="card-avatar">
                                            <i class="bi bi-person-vcard"></i>
                                        </div>
                                        <div>
                                            <div class="hall-name">
                                                {{ $card->card_number }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- QR Code --}}
                                <td>
                                    <code class="qr-code">
                                        {{ $card->qr_code }}
                                    </code>
                                </td>

                                {{-- Branch --}}
                                <td>
                                    @if ($card->branch)
                                        <span class="branch-badge">
                                            <i class="bi bi-building"></i>
                                            {{ $card->branch->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                {{-- Student --}}
                                <td>
                                    @if ($card->student)
                                        <a href="{{ route('students.show', $card->student->id) }}"
                                            class="text-decoration-none fw-semibold text-primary">
                                            {{ $card->student->initial_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">
                                            Not Assigned
                                        </span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    @switch($card->status)
                                        @case('available')
                                            <span class="badge bg-success custom-badge">Available</span>
                                        @break

                                        @case('assigned')
                                            <span class="badge bg-primary custom-badge">Assigned</span>
                                        @break

                                        @case('lost')
                                            <span class="badge bg-danger custom-badge">Lost</span>
                                        @break

                                        @case('damaged')
                                            <span class="badge bg-warning text-dark custom-badge">Damaged</span>
                                        @break

                                        @case('inactive')
                                            <span class="badge bg-secondary custom-badge">Inactive</span>
                                        @break

                                        @default
                                            <span class="badge bg-secondary custom-badge">
                                                {{ ucfirst($card->status) }}
                                            </span>
                                    @endswitch
                                </td>

                                {{-- Issued Date --}}
                                <td>
                                    {{ optional($card->issued_at)->format('Y-m-d') ?? '-' }}
                                </td>

                                {{-- Action --}}
                                <td class="text-end">
                                    <a href="{{ route('student-cards.show', $card->id) }}" class="action-btn view-btn"
                                        title="View Card">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                </td>

                            </tr>

                            @empty

                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <h5>No Student Cards Found</h5>
                                            <p class="text-muted">
                                                There are no student cards available.
                                            </p>

                                            @cardaccess('generate')
                                                <a href="{{ route('student-cards.generate') }}"
                                                    class="btn btn-primary custom-btn mt-3">
                                                    <i class="bi bi-plus-circle"></i>
                                                    Generate Cards
                                                </a>
                                            @endcardaccess
                                        </div>
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

                {{-- ===================== PAGINATION ===================== --}}

                @if ($cards->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">

                        <div class="text-muted">
                            Showing
                            {{ $cards->firstItem() }}
                            to
                            {{ $cards->lastItem() }}
                            of
                            {{ $cards->total() }}
                            cards
                        </div>

                        <div>
                            {{ $cards->appends(request()->query())->links() }}
                        </div>

                    </div>
                @endif

            </div>

        </div>

    @endsection

    @push('styles')
        <style>
            .halls-page {
                animation: fadeIn .4s ease;
            }

            /* =========================
                            STATS
                        ==========================*/

            .stats-card {
                background: #fff;
                border-radius: 24px;
                padding: 1.5rem;
                display: flex;
                align-items: center;
                gap: 1rem;
                border: 1px solid #eef2f7;
                box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
                transition: .25s;
            }

            .stats-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 18px 40px rgba(0, 0, 0, .08);
            }

            .stats-icon {
                width: 60px;
                height: 60px;
                border-radius: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 1.5rem;
                flex-shrink: 0;
            }

            .blue {
                background: linear-gradient(135deg, #2563eb, #3b82f6);
            }

            .green {
                background: linear-gradient(135deg, #10b981, #34d399);
            }

            .red {
                background: linear-gradient(135deg, #ef4444, #f87171);
            }

            .stats-card h3 {
                margin: 0;
                font-size: 1.6rem;
                font-weight: 700;
            }

            .stats-card p {
                margin: 0;
                color: #64748b;
            }

            /* =========================
                            BRANCH SUMMARY
                        ==========================*/

            .branch-summary-card {
                background: #fff;
                border-radius: 20px;
                padding: 1.25rem;
                border: 1px solid #eef2f7;
                box-shadow: 0 8px 24px rgba(0, 0, 0, .04);
                transition: .25s;
                height: 100%;
            }

            .branch-summary-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 14px 34px rgba(0, 0, 0, .08);
            }

            .branch-summary-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: .75rem;
                margin-bottom: 1rem;
                padding-bottom: .75rem;
                border-bottom: 1px solid #f1f5f9;
            }

            .branch-info {
                display: flex;
                align-items: center;
                gap: .75rem;
                min-width: 0;
            }

            .branch-icon {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.1rem;
                flex-shrink: 0;
            }

            .branch-name {
                margin: 0;
                font-size: .95rem;
                font-weight: 700;
                color: #0f172a;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .branch-code {
                font-size: .7rem;
                color: #64748b;
                font-weight: 600;
                text-transform: uppercase;
            }

            .branch-total-badge {
                background: #eff6ff;
                color: #2563eb;
                padding: .3rem .7rem;
                border-radius: 10px;
                font-size: .7rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .branch-stats-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: .5rem;
            }

            .branch-stat {
                display: flex;
                align-items: center;
                gap: .4rem;
                padding: .4rem .5rem;
                background: #f8fafc;
                border-radius: 10px;
                font-size: .75rem;
            }

            .stat-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                flex-shrink: 0;
            }

            .stat-dot.available {
                background: #10b981;
            }

            .stat-dot.assigned {
                background: #2563eb;
            }

            .stat-dot.lost {
                background: #ef4444;
            }

            .stat-dot.damaged {
                background: #f59e0b;
            }

            .stat-dot.inactive {
                background: #64748b;
            }

            .stat-dot.total {
                background: #7c3aed;
            }

            .stat-label {
                color: #64748b;
                font-weight: 500;
                flex: 1;
            }

            .stat-value {
                font-weight: 700;
                color: #0f172a;
            }

            /* =========================
                            MAIN CARD
                        ==========================*/

            .main-card {
                background: #fff;
                border-radius: 28px;
                padding: 1.5rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
            }

            .main-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                flex-wrap: wrap;
                margin-bottom: 1.5rem;
            }

            .main-card-header h4 {
                margin: 0;
                font-weight: 700;
            }

            .main-card-header p {
                margin: 0;
                color: #64748b;
            }

            .header-buttons {
                display: flex;
                gap: .75rem;
                flex-wrap: wrap;
            }

            /* =========================
                            BUTTONS
                        ==========================*/

            .custom-btn {
                border: none;
                border-radius: 14px;
                padding: .75rem 1.25rem;
                font-weight: 600;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: .25s;
            }

            .custom-btn:hover {
                transform: translateY(-2px);
            }

            /* =========================
                            ALERT
                        ==========================*/

            .custom-alert {
                border: none;
                border-radius: 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 1.5rem;
            }

            /* =========================
                            SEARCH
                        ==========================*/

            .search-card {
                background: #f8fafc;
                border-radius: 20px;
                padding: 1rem;
                margin-bottom: 1.5rem;
            }

            .search-input-wrapper {
                position: relative;
            }

            .search-input-wrapper i {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #64748b;
                z-index: 2;
            }

            .custom-input {
                min-height: 48px;
                border-radius: 14px !important;
                border: 1px solid #e2e8f0;
            }

            input.custom-input {
                padding-left: 42px;
            }

            .custom-input:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
            }

            /* =========================
                            TABLE
                        ==========================*/

            .custom-table thead th {
                background: #f8fafc;
                border: none;
                color: #475569;
                text-transform: uppercase;
                font-size: .82rem;
                padding: 1rem;
                white-space: nowrap;
            }

            .custom-table tbody td {
                padding: 1rem;
                vertical-align: middle;
                border-color: #f1f5f9;
            }

            .custom-table tbody tr:hover {
                background: #f8fafc;
            }

            .card-avatar {
                width: 50px;
                height: 50px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
                color: #fff;
                font-size: 1.3rem;
                flex-shrink: 0;
            }

            .hall-name {
                font-weight: 700;
                color: #0f172a;
            }

            .qr-code {
                background: #f1f5f9;
                padding: 4px 10px;
                border-radius: 8px;
                font-size: .8rem;
                font-weight: 600;
            }

            .branch-badge {
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                background: #f1f5f9;
                color: #475569;
                padding: .35rem .7rem;
                border-radius: 10px;
                font-size: .75rem;
                font-weight: 600;
            }

            .custom-badge {
                border-radius: 10px;
                padding: .45rem .75rem;
                font-size: .75rem;
                font-weight: 600;
            }

            /* =========================
                            ACTION
                        ==========================*/

            .action-btn {
                width: 38px;
                height: 38px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                text-decoration: none;
                transition: .25s;
            }

            .view-btn {
                background: #eff6ff;
                color: #2563eb;
            }

            .view-btn:hover {
                background: #2563eb;
                color: #fff;
            }

            /* =========================
                            EMPTY
                        ==========================*/

            .empty-state i {
                font-size: 3.5rem;
                color: #cbd5e1;
                display: block;
                margin-bottom: 1rem;
            }

            .empty-state h5 {
                font-weight: 700;
            }

            .empty-state p {
                color: #64748b;
            }

            /* =========================
                            PAGINATION
                        ==========================*/

            .pagination .page-link {
                border: none;
                margin: 0 3px;
                border-radius: 10px !important;
            }

            .pagination .page-item.active .page-link {
                background: #2563eb;
            }

            /* =========================
                            RESPONSIVE
                        ==========================*/

            @media(max-width:768px) {

                .main-card-header {
                    flex-direction: column;
                    align-items: stretch;
                }

                .header-buttons {
                    width: 100%;
                }

                .header-buttons .btn {
                    flex: 1;
                }

                .branch-stats-grid {
                    grid-template-columns: 1fr;
                }

            }

            @keyframes fadeIn {

                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }

            }
        </style>
    @endpush
