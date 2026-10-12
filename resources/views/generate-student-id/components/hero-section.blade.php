@php
    // ==================== DYNAMIC VALUES ====================
    $totalStudents = $totalStudents ?? 0;
    $currentDate   = now()->format('d M Y');
    $currentTime   = now()->format('h:i A');
    $currentDay    = now()->format('d');
    $currentMonth  = strtoupper(now()->format('M'));
    $currentYear   = now()->format('Y');
    $currentWeekday = now()->format('D');

    // ==================== USER INFO ====================
    $user     = auth()->user();
    $userName = $user?->name ?? 'User';
@endphp

<div class="hero-card mb-4">
    <div class="hero-grid">

        {{-- ==================== LEFT SIDE ==================== --}}
        <div class="hero-left">

            {{-- Top Badge --}}
            <div class="hero-badge mb-3">
                <i class="fas fa-id-card"></i>
                Student ID Management
            </div>

            {{-- Title --}}
            <h2 class="hero-title mb-2">
                <i class="fas fa-credit-card me-2"></i>
                Generate Student ID Cards
            </h2>

            {{-- Description --}}
            <p class="hero-description mb-3">
                Preview, manage and bulk download student ID cards
                <span class="hero-dimension-badge">
                    <i class="fas fa-ruler-combined me-1"></i>
                    CR80: 3.375" × 2.125"
                </span>
            </p>

            {{-- Quick Stats --}}
            <div class="hero-quick-stats">

                <div class="hero-stat-item">
                    <div class="hero-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="hero-stat-content">
                        <div class="hero-stat-value">{{ number_format($totalStudents) }}</div>
                        <div class="hero-stat-label">Total Students</div>
                    </div>
                </div>

                <div class="hero-stat-divider"></div>

                <div class="hero-stat-item">
                    <div class="hero-stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="hero-stat-content">
                        <div class="hero-stat-value">{{ $currentDate }}</div>
                        <div class="hero-stat-label">{{ $currentTime }}</div>
                    </div>
                </div>

            </div>

        </div>

        {{-- ==================== RIGHT SIDE ==================== --}}
        <div class="hero-right">

            {{-- User Badge --}}
            <div class="hero-badge hero-user-badge">
                <i class="fas fa-user-circle"></i>
                {{ $userName }}
            </div>

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
    /* ==================================================
       HERO CARD
    ================================================== */
    .hero-card {
        position: relative;
        overflow: hidden;
        border-radius: 30px;
        padding: 1.75rem 2rem;
        background: linear-gradient(135deg, #0f172a 0%, #0f4bb5 52%, #0f766e 100%);
        color: #fff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
    }

    .hero-card::before,
    .hero-card::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .hero-card::before {
        width: 280px;
        height: 280px;
        background: rgba(255, 255, 255, 0.08);
        top: -120px;
        right: -90px;
    }

    .hero-card::after {
        width: 180px;
        height: 180px;
        background: rgba(255, 255, 255, 0.06);
        bottom: -70px;
        left: -70px;
    }

    /* ==================================================
       HERO GRID LAYOUT
    ================================================== */
    .hero-grid {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 2rem;
        position: relative;
        z-index: 2;
        flex-wrap: wrap;
    }

    .hero-left {
        flex: 1 1 500px;
        min-width: 0;
    }

    .hero-right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 1rem;
        flex-shrink: 0;
    }

    /* ==================================================
       HERO BADGE
    ================================================== */
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.12);
        font-weight: 700;
        font-size: 0.82rem;
        letter-spacing: 0.3px;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        color: #fff;
    }

    .hero-badge i {
        font-size: 0.9rem;
    }

    .hero-user-badge {
        background: rgba(255, 255, 255, 0.18);
        font-weight: 800;
    }

    /* ==================================================
       HERO TITLE & DESCRIPTION
    ================================================== */
    .hero-title {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.3px;
        color: #fff;
        margin: 0.75rem 0;
    }

    .hero-description {
        font-size: 0.92rem;
        line-height: 1.5;
        color: rgba(255, 255, 255, 0.75);
        max-width: 560px;
    }

    .hero-dimension-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.65rem;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 700;
        margin-left: 0.3rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    /* ==================================================
       HERO QUICK STATS
    ================================================== */
    .hero-quick-stats {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        width: fit-content;
        max-width: 100%;
        flex-wrap: wrap;
    }

    .hero-stat-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .hero-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
        color: #fff;
    }

    .hero-stat-content {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .hero-stat-value {
        font-size: 1.1rem;
        font-weight: 800;
        letter-spacing: -0.2px;
        color: #fff;
        white-space: nowrap;
    }

    .hero-stat-label {
        font-size: 0.72rem;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .hero-stat-divider {
        width: 1px;
        height: 40px;
        background: rgba(255, 255, 255, 0.15);
    }

    /* ==================================================
       HERO DATE CARD
    ================================================== */
    .hero-date-card {
        display: flex;
        align-items: stretch;
        gap: 0.85rem;
        padding: 0.85rem 1.1rem;
        background: rgba(255, 255, 255, 0.12);
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        min-width: 200px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.1);
    }

    .hero-date-day {
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

    .hero-date-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        border-left: 1px solid rgba(255, 255, 255, 0.2);
        padding-left: 0.85rem;
        gap: 0.15rem;
    }

    .hero-date-month {
        font-size: 0.95rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        line-height: 1;
        color: #fff;
    }

    .hero-date-year {
        font-size: 0.75rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7);
        letter-spacing: 0.5px;
    }

    .hero-date-weekday {
        font-size: 0.65rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.55);
        letter-spacing: 2px;
        text-transform: uppercase;
        writing-mode: vertical-rl;
        text-orientation: mixed;
        transform: rotate(180deg);
        border-left: 1px solid rgba(255, 255, 255, 0.2);
        padding-left: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ==================================================
       RESPONSIVE - Tablet
    ================================================== */
    @media (max-width: 992px) {
        .hero-card {
            padding: 1.5rem;
        }

        .hero-title {
            font-size: 1.5rem;
        }

        .hero-right {
            align-items: flex-start;
        }

        .hero-grid {
            flex-direction: column;
            gap: 1.5rem;
        }
    }

    /* ==================================================
       RESPONSIVE - Mobile
    ================================================== */
    @media (max-width: 768px) {
        .hero-card {
            padding: 1.25rem;
            border-radius: 22px;
        }

        .hero-title {
            font-size: 1.25rem;
        }

        .hero-description {
            font-size: 0.85rem;
        }

        .hero-quick-stats {
            flex-direction: column;
            align-items: stretch;
            gap: 0.85rem;
            padding: 0.85rem 1rem;
            width: 100%;
        }

        .hero-stat-divider {
            width: 100%;
            height: 1px;
        }

        .hero-stat-item {
            justify-content: flex-start;
        }

        .hero-right {
            width: 100%;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .hero-date-card {
            min-width: auto;
            padding: 0.7rem 1rem;
        }

        .hero-date-day {
            font-size: 1.5rem;
        }

        .hero-date-weekday {
            display: none;
        }
    }

    /* ==================================================
       RESPONSIVE - Small Mobile
    ================================================== */
    @media (max-width: 480px) {
        .hero-card {
            padding: 1rem;
            border-radius: 18px;
        }

        .hero-title {
            font-size: 1.05rem;
        }

        .hero-badge {
            font-size: 0.72rem;
            padding: 0.45rem 0.85rem;
        }

        .hero-stat-icon {
            width: 38px;
            height: 38px;
            font-size: 0.95rem;
        }

        .hero-stat-value {
            font-size: 0.95rem;
        }

        .hero-stat-label {
            font-size: 0.68rem;
        }

        .hero-date-day {
            font-size: 1.3rem;
        }

        .hero-date-month {
            font-size: 0.82rem;
        }
    }
</style>
@endpush