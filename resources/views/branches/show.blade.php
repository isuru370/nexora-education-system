@extends('layouts.app')

@section('title', 'Teacher Details')
@section('page-title', 'Teacher Details')

@push('styles')
<style>
    .teacher-show-page {
        animation: fadeIn .4s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ============================================
       HERO CARD
    ============================================ */
    .hero-card {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 30%, #2563eb 100%);
        border-radius: 24px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(37, 99, 235, 0.20);
    }

    .hero-card::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
    }

    .hero-card::after {
        content: '';
        position: absolute;
        bottom: -80px;
        left: -40px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(52, 211, 153, 0.06);
    }

    .hero-avatar {
        width: 100px;
        height: 100px;
        border-radius: 24px;
        background: linear-gradient(135deg, #2563eb, #60a5fa);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 2.5rem;
        font-weight: 700;
        box-shadow: 0 15px 40px rgba(37, 99, 235, 0.4);
        flex-shrink: 0;
        position: relative;
        z-index: 2;
    }

    .hero-card h1 {
        color: #fff;
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
        position: relative;
        z-index: 2;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 0.35rem 0.9rem;
        border-radius: 20px;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        position: relative;
        z-index: 2;
    }

    .hero-meta-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.35rem 0.85rem;
        border-radius: 20px;
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.75rem;
        font-weight: 600;
    }

    .hero-meta-badge.code {
        font-family: monospace;
    }

    .hero-actions .btn-hero {
        border-radius: 12px;
        padding: 0.6rem 1.25rem;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.25s ease;
        border: 1px solid transparent;
    }

    .hero-actions .btn-hero-glass {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(10px);
        border-color: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .hero-actions .btn-hero-glass:hover {
        background: rgba(255, 255, 255, 0.22);
        border-color: rgba(255, 255, 255, 0.3);
        color: #fff;
        transform: translateY(-2px);
    }

    .hero-actions .btn-hero-light {
        background: #fff;
        color: #2563eb;
    }

    .hero-actions .btn-hero-light:hover {
        background: #eff6ff;
        color: #1d4ed8;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(255, 255, 255, 0.25);
    }

    /* ============================================
       INFO CARDS
    ============================================ */
    .info-card {
        background: #fff;
        border-radius: 20px;
        border: 1px solid #eef2f7;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        overflow: hidden;
        height: 100%;
        transition: all 0.25s ease;
    }

    .info-card:hover {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
    }

    .info-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .info-card-header .icon-wrap {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .info-card-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
    }

    .info-card-header small {
        display: block;
        color: #94a3b8;
        font-size: 0.75rem;
        margin-top: 0.1rem;
    }

    /* Icon color variants */
    .icon-blue { background: #eff6ff; color: #2563eb; }
    .icon-green { background: #ecfdf5; color: #10b981; }
    .icon-amber { background: #fffbeb; color: #f59e0b; }
    .icon-purple { background: #f5f3ff; color: #8b5cf6; }
    .icon-cyan { background: #ecfeff; color: #06b6d4; }
    .icon-rose { background: #fff1f2; color: #f43f5e; }
    .icon-slate { background: #f1f5f9; color: #475569; }

    /* ============================================
       DETAIL ROWS
    ============================================ */
    .detail-row {
        display: flex;
        align-items: flex-start;
        padding: 0.85rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        gap: 1rem;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-row .detail-label {
        flex-shrink: 0;
        width: 120px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding-top: 0.15rem;
    }

    .detail-row .detail-value {
        flex-grow: 1;
        font-size: 0.9rem;
        font-weight: 500;
        color: #334155;
        word-break: break-word;
        min-width: 0;
    }

    .detail-row .detail-value.empty {
        color: #cbd5e1;
        font-style: italic;
        font-weight: 400;
    }

    .detail-value a {
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
    }

    .detail-value a:hover {
        text-decoration: underline;
    }

    /* ============================================
       BRANCH CHIP (IMPROVED - MORE VISIBLE)
    ============================================ */
    .branch-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.6rem 1rem;
        border-radius: 12px;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 1px solid #bfdbfe;
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e40af;
        margin: 0.3rem 0.3rem 0.3rem 0;
        transition: all 0.2s ease;
    }

    .branch-chip:hover {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(37, 99, 235, 0.15);
    }

    .branch-chip i {
        color: #2563eb;
        font-size: 1rem;
    }

    .branch-chip .code {
        font-family: monospace;
        background: #fff;
        padding: 0.15rem 0.5rem;
        border-radius: 6px;
        font-size: 0.7rem;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    /* Branch Card Grid (Alternative - bigger cards) */
    .branch-card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 0.75rem;
        padding: 1.25rem 1.5rem;
    }

    .branch-card-item {
        padding: 1rem;
        border-radius: 14px;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 1.5px solid #bfdbfe;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }

    .branch-card-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #2563eb, #60a5fa);
    }

    .branch-card-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.15);
        border-color: #2563eb;
    }

    .branch-card-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: linear-gradient(135deg, #2563eb, #60a5fa);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
        box-shadow: 0 6px 15px rgba(37, 99, 235, 0.25);
    }

    .branch-card-info {
        flex-grow: 1;
        min-width: 0;
    }

    .branch-card-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e3a8a;
        margin-bottom: 0.2rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .branch-card-code {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-family: monospace;
        font-size: 0.7rem;
        font-weight: 600;
        color: #2563eb;
        background: #fff;
        padding: 0.15rem 0.5rem;
        border-radius: 6px;
        border: 1px solid #bfdbfe;
    }

    /* ============================================
       STATUS BADGES
    ============================================ */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.85rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .status-badge.active {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    .status-badge.inactive {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #cbd5e1;
    }

    /* ============================================
       CLASS ITEM
    ============================================ */
    .class-item {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.85rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s ease;
    }

    .class-item:last-child {
        border-bottom: none;
    }

    .class-item:hover {
        background: #f8fafc;
    }

    .class-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #f5f3ff, #ede9fe);
        color: #8b5cf6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    /* ============================================
       ACTION BUTTONS
    ============================================ */
    .action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.7rem 1rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        border: 1px solid;
        text-decoration: none;
    }

    .action-btn:hover {
        transform: translateY(-2px);
    }

    .action-btn.edit {
        background: #eff6ff;
        color: #2563eb;
        border-color: #bfdbfe;
    }

    .action-btn.edit:hover {
        background: #2563eb;
        color: #fff;
    }

    .action-btn.login {
        background: #f5f3ff;
        color: #7c3aed;
        border-color: #ddd6fe;
    }

    .action-btn.login:hover {
        background: #8b5cf6;
        color: #fff;
    }

    .action-btn.activate {
        background: #ecfdf5;
        color: #065f46;
        border-color: #a7f3d0;
    }

    .action-btn.activate:hover {
        background: #10b981;
        color: #fff;
    }

    .action-btn.deactivate {
        background: #fef3c7;
        color: #92400e;
        border-color: #fcd34d;
    }

    .action-btn.deactivate:hover {
        background: #f59e0b;
        color: #fff;
    }

    .action-btn.delete {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    .action-btn.delete:hover {
        background: #ef4444;
        color: #fff;
    }

    .danger-divider {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0.5rem 0;
    }

    .danger-divider hr {
        flex-grow: 1;
        margin: 0;
        opacity: 0.5;
    }

    .danger-divider span {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: #94a3b8;
    }

    /* ============================================
       EMPTY STATE
    ============================================ */
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
    }

    .empty-state i {
        font-size: 2.5rem;
        color: #cbd5e1;
        opacity: 0.6;
    }

    .empty-state p {
        color: #94a3b8;
        font-size: 0.85rem;
        margin: 0.5rem 0 0;
    }

    /* ============================================
       RESPONSIVE
    ============================================ */
    @media (max-width: 768px) {
        .hero-card { padding: 1.5rem; }
        .hero-card h1 { font-size: 1.35rem; }
        .hero-avatar { width: 75px; height: 75px; font-size: 2rem; }

        .detail-row {
            flex-direction: column;
            gap: 0.35rem;
            padding: 0.75rem 1.25rem;
        }

        .detail-row .detail-label {
            width: 100%;
        }

        .branch-card-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush


@section('content')
<div class="teacher-show-page">

    {{-- ============================================
         HERO SECTION
    ============================================ --}}
    <div class="hero-card">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 position-relative" style="z-index: 2;">

            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="hero-avatar">
                    {{ strtoupper(substr($teacher->full_name ?? 'T', 0, 1)) }}
                </div>

                <div>
                    <span class="hero-badge">
                        <i class="bi bi-person-badge-fill"></i>
                        Teacher Profile
                    </span>

                    <h1>{{ $teacher->full_name ?? 'Unknown Teacher' }}</h1>

                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @if($teacher->custom_id)
                            <span class="hero-meta-badge code">
                                <i class="bi bi-hash"></i>{{ $teacher->custom_id }}
                            </span>
                        @endif

                        @if($teacher->initials)
                            <span class="hero-meta-badge">
                                <i class="bi bi-type"></i>{{ $teacher->initials }}
                            </span>
                        @endif

                        {{-- Branch Count Badge --}}
                        @if($teacher->branches && $teacher->branches->count() > 0)
                            <span class="hero-meta-badge" style="background: rgba(96,165,250,0.2); color: #93c5fd;">
                                <i class="bi bi-diagram-3-fill"></i>
                                {{ $teacher->branches->count() }} {{ Str::plural('Branch', $teacher->branches->count()) }}
                            </span>
                        @endif

                        @if($teacher->is_active)
                            <span class="hero-meta-badge" style="background: rgba(16,185,129,0.2); color: #34d399;">
                                <span class="d-inline-block rounded-circle" style="width: 6px; height: 6px; background: #34d399;"></span>
                                Active
                            </span>
                        @else
                            <span class="hero-meta-badge" style="background: rgba(148,163,184,0.2); color: #cbd5e1;">
                                <span class="d-inline-block rounded-circle" style="width: 6px; height: 6px; background: #94a3b8;"></span>
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="hero-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('teachers.index') }}" class="btn btn-hero btn-hero-glass">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                @if(Route::has('teachers.edit'))
                    <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-hero btn-hero-light">
                        <i class="bi bi-pencil-square me-1"></i> Edit Teacher
                    </a>
                @endif
            </div>
        </div>
    </div>


    {{-- ============================================
         MAIN CONTENT
    ============================================ --}}
    <div class="row g-4">

        {{-- ============================================
             LEFT COLUMN (8 cols)
        ============================================ --}}
        <div class="col-lg-8 d-flex flex-column gap-4">

            {{-- ========================================
                 EDUCATION BRANCHES (PROMINENT - AT TOP)
            ======================================== --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="icon-wrap icon-green">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5>Education Branches</h5>
                        <small>Branches where this teacher works</small>
                    </div>
                    @if($teacher->branches && $teacher->branches->count() > 0)
                        <span class="badge rounded-pill" style="background: #ecfdf5; color: #059669; padding: 6px 14px; font-weight: 700; font-size: 0.75rem;">
                            <i class="bi bi-check-circle-fill me-1"></i>{{ $teacher->branches->count() }} Assigned
                        </span>
                    @else
                        <span class="badge rounded-pill" style="background: #fef3c7; color: #92400e; padding: 6px 14px; font-weight: 700; font-size: 0.75rem;">
                            <i class="bi bi-exclamation-circle-fill me-1"></i>None
                        </span>
                    @endif
                </div>

                @if($teacher->branches && $teacher->branches->count() > 0)
                    {{-- ✅ Branch Cards Grid - Beautiful & Visible --}}
                    <div class="branch-card-grid">
                        @foreach($teacher->branches as $branch)
                            <div class="branch-card-item">
                                <div class="branch-card-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div class="branch-card-info">
                                    <div class="branch-card-name" title="{{ $branch->name }}">
                                        {{ $branch->name }}
                                    </div>
                                    @if($branch->code)
                                        <span class="branch-card-code">
                                            <i class="bi bi-hash"></i>{{ $branch->code }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-diagram-3"></i>
                        <p>This teacher is not assigned to any branch yet.</p>
                    </div>
                @endif
            </div>


            {{-- ========================================
                 PERSONAL INFORMATION
            ======================================== --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="icon-wrap icon-blue">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div>
                        <h5>Personal Information</h5>
                        <small>Basic personal details</small>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="detail-row">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value">{{ $teacher->full_name ?? '—' }}</div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Initials</div>
                        <div class="detail-value">{{ $teacher->initials ?? '—' }}</div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">
                            @if($teacher->email)
                                <a href="mailto:{{ $teacher->email }}">
                                    <i class="bi bi-envelope-fill me-1"></i>{{ $teacher->email }}
                                </a>
                            @else
                                <span class="empty">Not provided</span>
                            @endif
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Mobile</div>
                        <div class="detail-value">
                            @if($teacher->mobile)
                                <a href="tel:{{ $teacher->mobile }}">
                                    <i class="bi bi-telephone-fill me-1"></i>{{ $teacher->mobile }}
                                </a>
                            @else
                                <span class="empty">Not provided</span>
                            @endif
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">NIC</div>
                        <div class="detail-value">{{ $teacher->nic ?? '—' }}</div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Gender</div>
                        <div class="detail-value">
                            @if($teacher->gender)
                                <i class="bi bi-gender-ambiguous me-1"></i>
                                {{ ucfirst($teacher->gender) }}
                            @else
                                <span class="empty">Not provided</span>
                            @endif
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">Birthday</div>
                        <div class="detail-value">
                            @if($teacher->bday)
                                <i class="bi bi-calendar-event me-1"></i>
                                {{ $teacher->bday->format('d M Y') }}
                                <span class="text-muted ms-2 small">({{ $teacher->bday->age }} years)</span>
                            @else
                                <span class="empty">Not provided</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>


            {{-- ========================================
                 ADDRESS INFORMATION
            ======================================== --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="icon-wrap icon-amber">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                        <h5>Address Information</h5>
                        <small>Residential address details</small>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if($teacher->address1 || $teacher->address2 || $teacher->address3)
                        <div class="detail-row">
                            <div class="detail-label">Address</div>
                            <div class="detail-value" style="line-height: 1.7;">
                                @if($teacher->address1) <div>{{ $teacher->address1 }}</div> @endif
                                @if($teacher->address2) <div>{{ $teacher->address2 }}</div> @endif
                                @if($teacher->address3) <div>{{ $teacher->address3 }}</div> @endif
                            </div>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-geo-alt"></i>
                            <p>No address information available</p>
                        </div>
                    @endif
                </div>
            </div>


            {{-- ========================================
                 QUALIFICATIONS
            ======================================== --}}
            @if($teacher->graduation_details || $teacher->experience)
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="icon-wrap icon-cyan">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <div>
                            <h5>Qualifications & Experience</h5>
                            <small>Educational background</small>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        @if($teacher->graduation_details)
                            <div class="detail-row">
                                <div class="detail-label">Graduation</div>
                                <div class="detail-value" style="line-height: 1.7;">
                                    {!! nl2br(e($teacher->graduation_details)) !!}
                                </div>
                            </div>
                        @endif

                        @if($teacher->experience)
                            <div class="detail-row">
                                <div class="detail-label">Experience</div>
                                <div class="detail-value" style="line-height: 1.7;">
                                    {!! nl2br(e($teacher->experience)) !!}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif


            {{-- ========================================
                 CLASSES
            ======================================== --}}
            @if(isset($teacher->classes) && $teacher->classes->count() > 0)
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="icon-wrap icon-purple">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5>Classes</h5>
                            <small>Assigned classes</small>
                        </div>
                        <span class="badge rounded-pill" style="background: #f5f3ff; color: #7c3aed; padding: 6px 14px; font-weight: 700; font-size: 0.75rem;">
                            {{ $teacher->classes->count() }}
                        </span>
                    </div>

                    <div class="card-body p-0">
                        @foreach($teacher->classes as $class)
                            <div class="class-item">
                                <div class="class-icon">
                                    <i class="bi bi-book"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate" style="color: #0f172a; font-size: 0.9rem;">
                                        {{ $class->subject->name ?? 'Unknown Subject' }}
                                    </div>
                                    <div class="small text-muted text-truncate">
                                        <i class="bi bi-award me-1"></i>{{ $class->grade->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>


        {{-- ============================================
             RIGHT COLUMN (4 cols)
        ============================================ --}}
        <div class="col-lg-4 d-flex flex-column gap-4">

            {{-- ========================================
                 STATUS CARD
            ======================================== --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="icon-wrap {{ $teacher->is_active ? 'icon-green' : 'icon-amber' }}">
                        <i class="bi bi-toggles"></i>
                    </div>
                    <h5>Account Status</h5>
                </div>

                <div class="card-body p-3">
                    @if($teacher->is_active)
                        <div class="p-3 rounded-3 d-flex align-items-start gap-3"
                             style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width: 40px; height: 40px; background: #10b981; color: white;">
                                <i class="bi bi-check-lg" style="font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1" style="color: #065f46;">Active</h6>
                                <p class="small mb-0" style="color: #047857; line-height: 1.5;">
                                    This teacher is active and available for teaching.
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="p-3 rounded-3 d-flex align-items-start gap-3"
                             style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #fcd34d;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width: 40px; height: 40px; background: #f59e0b; color: white;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1" style="color: #78350f;">Inactive</h6>
                                <p class="small mb-0" style="color: #92400e; line-height: 1.5;">
                                    This teacher is currently inactive.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>


            {{-- ========================================
                 BANK INFORMATION
            ======================================== --}}
            @if($teacher->bankBranch || $teacher->account_number)
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="icon-wrap icon-purple">
                            <i class="bi bi-bank2"></i>
                        </div>
                        <div>
                            <h5>Bank Information</h5>
                            <small>Salary payment details</small>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        @if($teacher->bankBranch)
                            <div class="detail-row" style="flex-direction: column; gap: 0.25rem;">
                                <div class="detail-label">Bank</div>
                                <div class="detail-value">
                                    <i class="bi bi-building me-1"></i>
                                    {{ $teacher->bankBranch->bank->bank_name ?? 'N/A' }}
                                </div>
                            </div>

                            <div class="detail-row" style="flex-direction: column; gap: 0.25rem;">
                                <div class="detail-label">Branch</div>
                                <div class="detail-value">
                                    {{ $teacher->bankBranch->branch_name ?? 'N/A' }}
                                </div>
                            </div>
                        @endif

                        @if($teacher->account_number)
                            <div class="detail-row" style="flex-direction: column; gap: 0.25rem;">
                                <div class="detail-label">Account No</div>
                                <div class="detail-value" style="font-family: monospace; font-weight: 700; color: #2563eb;">
                                    {{ $teacher->account_number }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif


            {{-- ========================================
                 RECORD INFORMATION
            ======================================== --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="icon-wrap icon-slate">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5>Record Info</h5>
                </div>

                <div class="card-body p-0">
                    <div class="detail-row" style="flex-direction: column; gap: 0.25rem;">
                        <div class="detail-label">Created</div>
                        <div class="detail-value small">
                            {{ $teacher->created_at ? $teacher->created_at->format('d M Y, h:i A') : '—' }}
                        </div>
                    </div>

                    <div class="detail-row" style="flex-direction: column; gap: 0.25rem;">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value small">
                            {{ $teacher->updated_at ? $teacher->updated_at->format('d M Y, h:i A') : '—' }}
                        </div>
                    </div>

                    @if($teacher->deleted_at)
                        <div class="detail-row" style="flex-direction: column; gap: 0.25rem; background: #fef2f2;">
                            <div class="detail-label" style="color: #ef4444;">Deleted</div>
                            <div class="detail-value small text-danger">
                                {{ $teacher->deleted_at->format('d M Y, h:i A') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>


            {{-- ========================================
                 ACTIONS
            ======================================== --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="icon-wrap icon-slate">
                        <i class="bi bi-gear-fill"></i>
                    </div>
                    <h5>Actions</h5>
                </div>

                <div class="card-body p-3 d-flex flex-column gap-2">

                    @if(Route::has('teachers.edit'))
                        <a href="{{ route('teachers.edit', $teacher) }}" class="action-btn edit">
                            <i class="bi bi-pencil-square"></i> Edit Teacher
                        </a>
                    @endif

                    @if(Route::has('teachers.loginDetails'))
                        <a href="{{ route('teachers.loginDetails', $teacher) }}" class="action-btn login">
                            <i class="bi bi-key-fill"></i> Login Details
                        </a>
                    @endif

                    @if(Route::has('teachers.toggleActive'))
                        <form action="{{ route('teachers.toggleActive', $teacher) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="action-btn {{ $teacher->is_active ? 'deactivate' : 'activate' }}"
                                    onclick="return confirm('{{ $teacher->is_active ? 'Deactivate' : 'Activate' }} this teacher?')">
                                @if($teacher->is_active)
                                    <i class="bi bi-pause-circle"></i> Deactivate Teacher
                                @else
                                    <i class="bi bi-check-circle"></i> Activate Teacher
                                @endif
                            </button>
                        </form>
                    @endif

                    <div class="danger-divider">
                        <hr>
                        <span>DANGER ZONE</span>
                        <hr>
                    </div>

                    @if(Route::has('teachers.destroy'))
                        <form action="{{ route('teachers.destroy', $teacher) }}" method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this teacher? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="action-btn delete">
                                <i class="bi bi-trash3"></i> Delete Teacher
                            </button>
                        </form>
                    @endif

                </div>
            </div>

        </div>

    </div>

</div>
@endsection