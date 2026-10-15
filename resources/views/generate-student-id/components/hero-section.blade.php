@php
    // ==================== DYNAMIC VALUES ====================
    $totalStudents  = $totalStudents ?? 0;
    $currentDate    = now()->format('d M Y');
    $currentTime    = now()->format('h:i A');
    $currentDay     = now()->format('d');
    $currentMonth   = strtoupper(now()->format('M'));
    $currentYear    = now()->format('Y');
    $currentWeekday = now()->format('D');

    // ==================== USER INFO ====================
    $user     = auth()->user();
    $userName = $user?->name ?? 'User';
@endphp

<div class="hero-section hero-section-id mb-4">
    <div class="hero-content">

        {{-- ==================== LEFT SIDE ==================== --}}
        <div class="hero-left">

            {{-- Top Badge --}}
            <span class="greeting-badge">
                <i class="bi bi-person-vcard"></i>
                Student ID Management
            </span>

            {{-- Title --}}
            <h1 class="hero-title">
                <i class="bi bi-credit-card-2-front-fill"></i>
                Generate Student ID Cards
            </h1>

            {{-- Description --}}
            <p class="hero-subtitle">
                Preview, manage and bulk download student ID cards.
                <span class="hero-dimension-badge">
                    <i class="bi bi-rulers"></i>
                    CR80: 3.375" × 2.125"
                </span>
            </p>

            {{-- Quick Stats --}}
            <div class="hero-stats-mini">

                <div class="mini-stat">
                    <span class="mini-stat-value">{{ number_format($totalStudents) }}</span>
                    <span class="mini-stat-label">Total Students</span>
                </div>

                <div class="mini-stat-divider"></div>

                <div class="mini-stat">
                    <span class="mini-stat-value">{{ $currentDate }}</span>
                    <span class="mini-stat-label">{{ $currentTime }}</span>
                </div>

            </div>

        </div>

        {{-- ==================== RIGHT SIDE ==================== --}}
        <div class="hero-right">

            {{-- User Badge --}}
            <span class="hero-user-badge">
                <i class="bi bi-person-circle"></i>
                {{ $userName }}
            </span>

            {{-- Date Card --}}
            <div class="hero-date-card">
                <div class="hero-date-day">{{ $currentDay }}</div>

                <div class="hero-date-meta">
                    <div class="hero-date-month">{{ $currentMonth }}</div>
                    <div class="hero-date-year">{{ $currentYear }}</div>
                </div>

                <div class="hero-date-weekday">{{ $currentWeekday }}</div>
            </div>

        </div>

    </div>
</div>


@push('styles')
<style>
    /* ============================================================
       Generate Student ID — Hero Section (page-scoped)
       Shared nx-* theme from layouts/partials/nexora-theme.
       Uses .dashboard-page scope for --nx-* tokens.
       ============================================================ */

    /* ---------- HERO VARIANT ---------- */
    .dashboard-page .hero-section-id {
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 52%, #047857 100%);
        position: relative;
        overflow: hidden;
    }

    .dashboard-page .hero-section-id::before,
    .dashboard-page .hero-section-id::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .dashboard-page .hero-section-id::before {
        width: 280px;
        height: 280px;
        background: rgba(255, 255, 255, 0.08);
        top: -120px;
        right: -90px;
    }

    .dashboard-page .hero-section-id::after {
        width: 180px;
        height: 180px;
        background: rgba(255, 255, 255, 0.06);
        bottom: -70px;
        left: -70px;
    }

    .dashboard-page .hero-section-id .hero-content {
        position: relative;
        z-index: 2;
    }

    /* ---------- DIMENSION BADGE (inline) ---------- */
    .dashboard-page .hero-dimension-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .55rem;
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .12);
        border-radius: 6px;
        font-size: .72rem;
        font-weight: 700;
        margin-left: .3rem;
        color: #fff;
        white-space: nowrap;
    }

    .dashboard-page .hero-dimension-badge i {
        font-size: .75rem;
    }

    /* ---------- HERO TITLE ICON SPACING ---------- */
    .dashboard-page .hero-section-id .hero-title i {
        font-size: 1.35rem;
        opacity: .85;
        margin-right: .35rem;
        vertical-align: middle;
    }

    /* ---------- USER BADGE ---------- */
    .dashboard-page .hero-user-badge {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .5rem .95rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .14);
        font-weight: 700;
        font-size: .8rem;
        letter-spacing: .3px;
        color: #fff;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .dashboard-page .hero-user-badge i {
        font-size: .95rem;
    }

    /* ---------- DATE CARD ---------- */
    .dashboard-page .hero-date-card {
        display: flex;
        align-items: stretch;
        gap: .85rem;
        padding: .85rem 1.1rem;
        background: rgba(255, 255, 255, .12);
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, .15);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        min-width: 200px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
    }

    .dashboard-page .hero-date-day {
        font-size: 2.2rem;
        font-weight: 900;
        line-height: 1;
        letter-spacing: -1px;
        background: linear-gradient(135deg, #ffffff, #bae6fd);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        display: flex;
        align-items: center;
    }

    .dashboard-page .hero-date-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        border-left: 1px solid rgba(255, 255, 255, .2);
        padding-left: .85rem;
        gap: .15rem;
    }

    .dashboard-page .hero-date-month {
        font-size: .95rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        line-height: 1;
        color: #fff;
    }

    .dashboard-page .hero-date-year {
        font-size: .75rem;
        font-weight: 600;
        color: rgba(255, 255, 255, .7);
        letter-spacing: .5px;
    }

    .dashboard-page .hero-date-weekday {
        font-size: .65rem;
        font-weight: 700;
        color: rgba(255, 255, 255, .55);
        letter-spacing: 2px;
        text-transform: uppercase;
        writing-mode: vertical-rl;
        text-orientation: mixed;
        transform: rotate(180deg);
        border-left: 1px solid rgba(255, 255, 255, .2);
        padding-left: .85rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 992px) {
        .dashboard-page .hero-section-id .hero-right {
            align-items: flex-start;
        }
    }

    @media (max-width: 768px) {
        .dashboard-page .hero-section-id {
            padding: 1.25rem;
        }

        .dashboard-page .hero-section-id .hero-title {
            font-size: 1.25rem;
        }

        .dashboard-page .hero-section-id .hero-title i {
            font-size: 1.1rem;
        }

        .dashboard-page .hero-dimension-badge {
            font-size: .68rem;
            margin-left: 0;
            display: inline-block;
            margin-top: .3rem;
        }

        .dashboard-page .hero-section-id .hero-right {
            width: 100%;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .dashboard-page .hero-date-card {
            min-width: auto;
            padding: .7rem 1rem;
        }

        .dashboard-page .hero-date-day {
            font-size: 1.5rem;
        }

        .dashboard-page .hero-date-weekday {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .dashboard-page .hero-section-id {
            padding: 1rem;
            border-radius: 12px;
        }

        .dashboard-page .hero-section-id .hero-title {
            font-size: 1.05rem;
        }

        .dashboard-page .hero-user-badge {
            font-size: .72rem;
            padding: .4rem .8rem;
        }

        .dashboard-page .hero-date-day {
            font-size: 1.3rem;
        }

        .dashboard-page .hero-date-month {
            font-size: .82rem;
        }
    }
</style>
@endpush