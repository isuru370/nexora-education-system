@extends('layouts.app')

@section('title', 'Teacher Details')
@section('page-title', 'Teacher Details')

@section('content')
<div class="teacher-details-page">

    {{-- =========================================================
        HERO
    ========================================================== --}}
    <div class="hero-card mb-4">
        <div class="hero-content">
            <div class="d-flex align-items-center gap-3">
                <div class="profile-avatar">
                    {{ strtoupper(substr($teacher->full_name, 0, 1)) }}
                </div>

                <div>
                    <h4 class="mb-1 fw-bold">{{ $teacher->full_name }}</h4>
                    <p class="mb-0 text-muted">
                        {{ $teacher->custom_id }}
                        @if($teacher->initials)
                            · {{ $teacher->initials }}
                        @endif
                    </p>

                    {{-- Branch count badge --}}
                    @if($teacher->branches && $teacher->branches->isNotEmpty())
                        <span class="badge mt-2" style="background: #eff6ff; color: #2563eb; font-size: 0.75rem; font-weight: 700; padding: 0.35rem 0.7rem; border-radius: 8px;">
                            <i class="bi bi-diagram-3-fill me-1"></i>
                            {{ $teacher->branches->count() }}
                            {{ Str::plural('Branch', $teacher->branches->count()) }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="hero-actions">
                <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-warning custom-btn">
                    <i class="bi bi-pencil-fill me-1"></i>
                    Edit
                </a>

                <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary custom-btn">
                    <i class="bi bi-arrow-left me-1"></i>
                    Back
                </a>
            </div>
        </div>
    </div>


    {{-- =========================================================
        SUMMARY CARDS
    ========================================================== --}}
    <div class="row g-4 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-icon blue">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div>
                    <h3>{{ $teacher->custom_id }}</h3>
                    <p>Teacher ID</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-icon green">
                    <i class="bi bi-phone-fill"></i>
                </div>
                <div>
                    <h3>{{ $teacher->mobile ?? 'N/A' }}</h3>
                    <p>Mobile</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-icon orange">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <div>
                    <h3 class="text-truncate" title="{{ $teacher->email }}">{{ $teacher->email ?? 'N/A' }}</h3>
                    <p>Email</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-icon red">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <h3>
                        <span class="badge {{ $teacher->is_active ? 'bg-success' : 'bg-secondary' }} custom-badge">
                            {{ $teacher->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </h3>
                    <p>Status</p>
                </div>
            </div>
        </div>

    </div>


    {{-- =========================================================
        CONTENT
    ========================================================== --}}
    <div class="row g-4">

        {{-- ============================
             PERSONAL INFORMATION
        ============================= --}}
        <div class="col-lg-6">
            <div class="info-card h-100">
                <div class="section-header">
                    <h6 class="mb-0 fw-bold">Personal Information</h6>
                    <span class="section-pill">Profile</span>
                </div>

                <div class="info-list">
                    <div class="info-row">
                        <span class="label">Full Name</span>
                        <span class="value">{{ $teacher->full_name }}</span>
                    </div>

                    <div class="info-row">
                        <span class="label">Initials</span>
                        <span class="value">{{ $teacher->initials ?? 'N/A' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="label">NIC</span>
                        <span class="value">{{ $teacher->nic ?? 'N/A' }}</span>
                    </div>

                    <div class="info-row">
                        <span class="label">Birthday</span>
                        <span class="value">
                            {{ !empty($teacher->bday) ? \Carbon\Carbon::parse($teacher->bday)->format('Y-m-d') : '-' }}
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="label">Gender</span>
                        <span class="value">{{ ucfirst($teacher->gender ?? '-') }}</span>
                    </div>
                </div>
            </div>
        </div>


        {{-- ============================
             CONTACT INFORMATION
        ============================= --}}
        <div class="col-lg-6">
            <div class="info-card h-100">
                <div class="section-header">
                    <h6 class="mb-0 fw-bold">Contact Information</h6>
                    <span class="section-pill">Reach</span>
                </div>

                <div class="info-list">
                    <div class="info-row">
                        <span class="label">Mobile</span>
                        <span class="value">
                            @if($teacher->mobile)
                                <a href="tel:{{ $teacher->mobile }}" class="text-decoration-none" style="color: #2563eb;">
                                    {{ $teacher->mobile }}
                                </a>
                            @else
                                N/A
                            @endif
                        </span>
                    </div>

                    <div class="info-row">
                        <span class="label">Email</span>
                        <span class="value">
                            @if($teacher->email)
                                <a href="mailto:{{ $teacher->email }}" class="text-decoration-none" style="color: #2563eb;">
                                    {{ $teacher->email }}
                                </a>
                            @else
                                N/A
                            @endif
                        </span>
                    </div>

                    <div class="info-row align-start">
                        <span class="label">Address</span>
                        <span class="value">
                            @if($teacher->address1 || $teacher->address2 || $teacher->address3)
                                {{ $teacher->address1 ?? '' }}
                                @if($teacher->address2) <br>{{ $teacher->address2 }} @endif
                                @if($teacher->address3) <br>{{ $teacher->address3 }} @endif
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>


        {{-- ============================
             ✅ BANK & INSTITUTE INFORMATION
        ============================= --}}
        <div class="col-lg-6">
            <div class="info-card h-100">

                <div class="section-header">
                    <h6 class="mb-0 fw-bold">
                        Bank & Institute Information
                    </h6>
                    <span class="section-pill">Payments</span>
                </div>

                <div class="info-list">

                    {{-- BANK --}}
                    <div class="info-row">
                        <span class="label">
                            <i class="bi bi-bank me-1 text-muted"></i> Bank
                        </span>
                        <span class="value">
                            {{ $teacher->bankBranch->bank->bank_name ?? 'N/A' }}
                        </span>
                    </div>

                    {{-- BANK BRANCH --}}
                    <div class="info-row">
                        <span class="label">
                            <i class="bi bi-geo-alt me-1 text-muted"></i> Bank Branch
                        </span>
                        <span class="value">
                            {{ $teacher->bankBranch->branch_name ?? 'N/A' }}
                        </span>
                    </div>

                    {{-- ACCOUNT NUMBER --}}
                    <div class="info-row">
                        <span class="label">
                            <i class="bi bi-credit-card me-1 text-muted"></i> Account Number
                        </span>
                        <span class="value" style="font-family: monospace; color: #2563eb;">
                            {{ $teacher->account_number ?? 'N/A' }}
                        </span>
                    </div>

                    {{-- ✅ INSTITUTE BRANCH --}}
                    <div class="info-row align-start">
                        <span class="label">
                            <i class="bi bi-diagram-3 me-1" style="color: #2563eb;"></i>
                            Institute Branch
                        </span>
                        <span class="value" style="text-align: right;">

                            @if($teacher->branches && $teacher->branches->isNotEmpty())

                                {{-- Branch Names --}}
                                <div class="fw-semibold" style="color: #2563eb;">
                                    {{ $teacher->branches->pluck('name')->implode(', ') }}
                                </div>

                                {{-- Branch Codes --}}
                                <div class="mt-1 d-flex flex-wrap justify-content-end gap-1">
                                    @foreach($teacher->branches as $branch)
                                        @if($branch->code)
                                            <span class="badge" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-family: monospace; font-size: 0.65rem; padding: 2px 6px;">
                                                {{ $branch->code }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>

                            @else

                                <span class="text-muted fst-italic">
                                    No branch assigned
                                </span>

                            @endif

                        </span>
                    </div>

                </div>

            </div>
        </div>


        {{-- ============================
             QUALIFICATION
        ============================= --}}
        <div class="col-lg-6">
            <div class="info-card h-100">
                <div class="section-header">
                    <h6 class="mb-0 fw-bold">Qualification</h6>
                    <span class="section-pill">Academic</span>
                </div>

                <div class="info-list">
                    <div class="info-row align-start">
                        <span class="label">Graduation</span>
                        <span class="value">
                            {{ $teacher->graduation_details ?? '-' }}
                        </span>
                    </div>

                    <div class="info-row align-start">
                        <span class="label">Experience</span>
                        <span class="value">
                            {{ $teacher->experience ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>


        {{-- ============================
             STATUS
        ============================= --}}
        <div class="col-12">
            <div class="info-card">
                <div class="section-header">
                    <h6 class="mb-0 fw-bold">Status</h6>
                    <span class="section-pill">Account</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ $teacher->is_active ? 'bg-success' : 'bg-secondary' }} custom-badge">
                        {{ $teacher->is_active ? 'Active' : 'Inactive' }}
                    </span>

                    <span class="text-muted small">
                        Teacher account visibility and access status
                    </span>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection


@push('styles')
<style>
    .teacher-details-page {
        animation: fadeIn .4s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .hero-card,
    .stats-card,
    .info-card {
        background: #fff;
        border-radius: 28px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
        border: 1px solid #eef2f7;
    }

    .hero-card {
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.5rem;
    }

    .hero-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .hero-actions {
        display: flex;
        gap: .7rem;
        flex-wrap: wrap;
    }

    .custom-btn {
        border-radius: 14px;
        padding: .7rem 1.15rem;
        font-weight: 600;
        border: none;
        transition: .2s ease;
    }

    .custom-btn:hover {
        transform: translateY(-2px);
    }

    .profile-avatar {
        width: 64px;
        height: 64px;
        border-radius: 20px;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.6rem;
        box-shadow: 0 10px 20px rgba(79, 70, 229, .22);
        flex-shrink: 0;
    }

    .stats-card {
        padding: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        border-radius: 24px;
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

    .blue { background: linear-gradient(135deg, #2563eb, #3b82f6); }
    .green { background: linear-gradient(135deg, #10b981, #34d399); }
    .orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .red { background: linear-gradient(135deg, #ef4444, #f87171); }

    .stats-card h3 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        word-break: break-word;
    }

    .stats-card p {
        margin: 0;
        color: #64748b;
        font-size: .92rem;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding-bottom: 1rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #eef2f7;
    }

    .section-pill {
        display: inline-flex;
        align-items: center;
        padding: .35rem .75rem;
        border-radius: 999px;
        background: #eff6ff;
        color: #2563eb;
        font-weight: 600;
        font-size: .82rem;
        white-space: nowrap;
    }

    .info-card {
        padding: 1.25rem;
        height: 100%;
    }

    .info-list {
        display: flex;
        flex-direction: column;
        gap: .9rem;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        background: #f8fafc;
        border: 1px solid #eef2f7;
        border-radius: 18px;
        padding: .9rem 1rem;
        transition: all 0.2s ease;
    }

    .info-row:hover {
        background: #f1f5f9;
    }

    .info-row.align-start {
        align-items: flex-start;
    }

    .label {
        color: #64748b;
        font-weight: 600;
        min-width: 140px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
    }

    .value {
        font-weight: 600;
        color: #0f172a;
        text-align: right;
        word-break: break-word;
        flex-grow: 1;
    }

    .custom-badge {
        border-radius: 10px;
        padding: .5rem .75rem;
        font-size: .75rem;
    }

    /* Truncate Email */
    .stats-card h3.text-truncate {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .hero-content {
            flex-direction: column;
            align-items: stretch;
        }

        .hero-actions {
            width: 100%;
        }

        .hero-actions .btn {
            flex: 1;
        }

        .info-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .value {
            text-align: left;
        }

        .label {
            min-width: auto;
        }

        .value .d-flex.justify-content-end {
            justify-content: flex-start !important;
        }
    }
</style>
@endpush