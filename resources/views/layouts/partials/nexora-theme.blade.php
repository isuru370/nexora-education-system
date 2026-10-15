{{-- ============================================================
     nexoraEDU — Shared Theme (Darkened)
     Dashboard-aligned design system (nx-*).
     Used across all pages via layouts.app include.
     ============================================================ --}}

<style>
    /* ============================================================
       PAGE WRAPPER
    ============================================================ */
    .dashboard-page {
        /* Core palette — darker & richer */
        --nx-navy: #0f172a;
        --nx-blue: #1d4ed8;
        --nx-blue-soft: #dbe6ff;

        --nx-green: #047857;
        --nx-green-soft: #d1fae5;

        --nx-amber: #b45309;
        --nx-amber-soft: #fef3c7;

        --nx-red: #b91c1c;
        --nx-red-soft: #fee2e2;

        --nx-violet: #6d28d9;
        --nx-violet-soft: #ede9fe;

        --nx-bg: #eef1f6;
        --nx-border: #d1d8e3;
        --nx-text: #0f172a;
        --nx-muted: #475569;

        /* Extra tokens */
        --nx-card-bg: #ffffff;
        --nx-head-bg: #e5eaf2;
        --nx-shadow: 0 1px 2px rgba(15, 23, 42, .06), 0 1px 3px rgba(15, 23, 42, .04);
        --nx-shadow-hover: 0 4px 12px rgba(15, 23, 42, .08);

        --nx-radius: 12px;

        color: var(--nx-text);
        background: var(--nx-bg);
        padding: 1.5rem;
        max-width: 1440px;
        margin: 0 auto;
    }

    /* ============================================================
       CARDS
    ============================================================ */
    .dashboard-page .nx-card {
        background: var(--nx-card-bg);
        border: 1px solid var(--nx-border);
        border-radius: var(--nx-radius);
        box-shadow: var(--nx-shadow);
        overflow: hidden;
    }

    .dashboard-page .nx-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--nx-border);
        background: #fafbfd;
    }

    .dashboard-page .nx-card-title {
        font-size: .95rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: .4rem;
        color: var(--nx-text);
    }

    .dashboard-page .nx-card-subtitle {
        font-size: .75rem;
        color: var(--nx-muted);
        margin: 0;
    }

    /* ============================================================
       HERO
    ============================================================ */
    .dashboard-page .hero-section {
        background: var(--nx-navy) radial-gradient(circle at 100% 0, rgba(29, 78, 216, .40), transparent 45%);
        color: #f1f4fb;
        border-radius: var(--nx-radius);
        padding: 1.75rem;
        box-shadow: 0 4px 16px rgba(15, 23, 42, .12);
    }

    .dashboard-page .hero-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .dashboard-page .hero-left { flex: 1; min-width: 280px; }

    .dashboard-page .greeting-badge {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        opacity: .75;
    }

    .dashboard-page .hero-title {
        font-size: 1.7rem;
        font-weight: 700;
        margin: .25rem 0;
        color: #fff;
    }

    .dashboard-page .hero-subtitle {
        font-size: .875rem;
        opacity: .8;
        margin: 0;
    }

    .dashboard-page .hero-stats-mini {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-top: 1.1rem;
        flex-wrap: wrap;
    }

    .dashboard-page .mini-stat { display: flex; flex-direction: column; }
    .dashboard-page .mini-stat-value { font-size: 1.15rem; font-weight: 700; }
    .dashboard-page .mini-stat-label { font-size: .75rem; opacity: .7; }
    .dashboard-page .mini-stat-divider {
        width: 1px; height: 28px; background: rgba(255, 255, 255, .2);
    }

    .dashboard-page .hero-right {
        display: flex; flex-direction: column; align-items: flex-end; gap: .5rem;
    }

    .dashboard-page .hero-date-time { display: flex; gap: .5rem; flex-wrap: wrap; }

    .dashboard-page .date-box {
        background: rgba(255, 255, 255, .14);
        border-radius: 8px;
        padding: .5rem .75rem;
        font-size: .875rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }

    .dashboard-page .hero-updated { font-size: .7rem; opacity: .65; }
    .dashboard-page .hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }

    /* ============================================================
       KPI / STATS GRID
    ============================================================ */
    .dashboard-page .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }

    .dashboard-page .stats-grid-3 { grid-template-columns: repeat(3, 1fr); }

    .dashboard-page .stat-card {
        display: flex;
        gap: .85rem;
        align-items: flex-start;
        padding: 1.1rem;
    }

    .dashboard-page .stat-icon-wrapper {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: grid; place-items: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .dashboard-page .icon-blue { background: var(--nx-blue-soft); color: var(--nx-blue); }
    .dashboard-page .icon-green { background: var(--nx-green-soft); color: var(--nx-green); }
    .dashboard-page .icon-amber { background: var(--nx-amber-soft); color: var(--nx-amber); }
    .dashboard-page .icon-red { background: var(--nx-red-soft); color: var(--nx-red); }
    .dashboard-page .icon-violet { background: var(--nx-violet-soft); color: var(--nx-violet); }

    .dashboard-page .stat-info { flex: 1; min-width: 0; }

    .dashboard-page .stat-label {
        font-size: .75rem;
        color: var(--nx-muted);
        display: block;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .dashboard-page .stat-number {
        font-size: 1.35rem;
        font-weight: 800;
        margin: .15rem 0;
        line-height: 1.25;
        color: var(--nx-text);
    }

    .dashboard-page .stat-change { font-size: .75rem; font-weight: 600; }
    .dashboard-page .stat-change.up { color: var(--nx-green); }
    .dashboard-page .stat-change.down { color: var(--nx-red); }
    .dashboard-page .stat-change.neutral { color: var(--nx-muted); }
    .dashboard-page .stat-change-note {
        font-size: .72rem; font-weight: 400; color: var(--nx-muted);
    }

    /* ============================================================
       BUTTONS
    ============================================================ */
    .dashboard-page .header-buttons { display: flex; gap: .5rem; flex-wrap: wrap; }

    .dashboard-page .nx-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        padding: .5rem .85rem;
        font-size: .82rem;
        font-weight: 600;
        border-radius: 8px;
        border: 1px solid var(--nx-border);
        background: #fff;
        color: var(--nx-text);
        text-decoration: none;
        line-height: 1.4;
        min-height: 38px;
        transition: all .15s ease;
        cursor: pointer;
    }

    .dashboard-page .nx-btn:hover:not(:disabled) {
        filter: brightness(.96);
        box-shadow: var(--nx-shadow);
    }
    .dashboard-page .nx-btn:disabled { opacity: .5; cursor: not-allowed; }

    .dashboard-page .nx-btn-primary {
        background: var(--nx-blue); border-color: var(--nx-blue); color: #fff;
    }

    .dashboard-page .nx-btn-success {
        background: #fff; border-color: var(--nx-border); color: var(--nx-text);
    }
    .dashboard-page .nx-btn-success i { color: var(--nx-green); }

    .dashboard-page .nx-btn-danger {
        background: #fff; border-color: var(--nx-border); color: var(--nx-text);
    }
    .dashboard-page .nx-btn-danger i { color: var(--nx-red); }

    .dashboard-page .nx-btn-amber {
        background: #fff;
        border-color: var(--nx-border);
        color: var(--nx-text);
    }
    .dashboard-page .nx-btn-amber i { color: var(--nx-amber); }

    .dashboard-page .nx-btn-light {
        background: #fff; border-color: var(--nx-border); color: var(--nx-muted);
    }

    /* Inside hero — light variant becomes translucent */
    .dashboard-page .hero-section .nx-btn-light {
        background: rgba(255, 255, 255, .12);
        border-color: rgba(255, 255, 255, .25);
        color: #fff;
    }
    .dashboard-page .hero-section .nx-btn-light:hover {
        background: rgba(255, 255, 255, .2);
    }

    /* ============================================================
       SEARCH / FORM
    ============================================================ */
    .dashboard-page .nx-search {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--nx-border);
        background: #f4f6fa;
    }

    .dashboard-page .nx-label {
        display: block;
        font-size: .75rem;
        font-weight: 700;
        color: var(--nx-muted);
        margin-bottom: .35rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .dashboard-page .search-input-wrapper { position: relative; }

    .dashboard-page .search-input-wrapper i {
        position: absolute;
        top: 50%; left: 13px;
        transform: translateY(-50%);
        color: var(--nx-muted);
        pointer-events: none;
        z-index: 5;
    }

    .dashboard-page .custom-input {
        min-height: 40px;
        border-radius: 8px !important;
        border: 1px solid var(--nx-border);
        font-size: .85rem;
        color: var(--nx-text);
        background-color: #fff;
    }

    .dashboard-page .custom-input::placeholder { color: #94a3b8; }
    .dashboard-page .search-input-wrapper .custom-input { padding-left: 38px; }

    .dashboard-page .custom-input:focus {
        border-color: var(--nx-blue);
        box-shadow: 0 0 0 3px var(--nx-blue-soft) !important;
    }

    .dashboard-page .custom-input.is-invalid { border-color: var(--nx-red); }

    .dashboard-page .invalid-feedback {
        font-size: .75rem;
        color: var(--nx-red);
        margin-top: .3rem;
    }

    /* ============================================================
       TABLE
    ============================================================ */
    .dashboard-page .premium-table { font-size: .82rem; }

    .dashboard-page .premium-table thead th {
        background: var(--nx-head-bg);
        color: #334155;
        font-size: .7rem;
        font-weight: 700;
        padding: .6rem .85rem;
        border: 0;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    .dashboard-page .premium-table tbody td {
        padding: .75rem .85rem;
        border-top: 1px solid var(--nx-border);
        vertical-align: middle;
    }

    .dashboard-page .premium-table tbody tr:hover { background: #f1f5fb; }
    .dashboard-page .premium-table tbody tr.row-muted td { opacity: .55; }

    .dashboard-page .row-number {
        color: var(--nx-muted);
        text-align: center;
        font-size: .78rem;
        font-weight: 600;
    }

    .dashboard-page .cell-title {
        font-weight: 700;
        color: var(--nx-text);
        text-decoration: none;
    }

    .dashboard-page .cell-sub {
        font-size: .74rem;
        color: var(--nx-muted);
    }

    .dashboard-page .id-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .72rem;
        background: #e5eaf2;
        padding: .15rem .45rem;
        border-radius: 4px;
        color: #334155;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ============================================================
       BADGES
    ============================================================ */
    .dashboard-page .nx-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .2rem .55rem;
        border-radius: 6px;
        font-size: .72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .dashboard-page .nx-badge-blue { background: var(--nx-blue-soft); color: var(--nx-blue); }
    .dashboard-page .nx-badge-green { background: var(--nx-green-soft); color: var(--nx-green); }
    .dashboard-page .nx-badge-amber { background: var(--nx-amber-soft); color: var(--nx-amber); }
    .dashboard-page .nx-badge-red { background: var(--nx-red-soft); color: var(--nx-red); }
    .dashboard-page .nx-badge-violet { background: var(--nx-violet-soft); color: var(--nx-violet); }
    .dashboard-page .nx-badge-gray { background: #e5eaf2; color: #475569; }

    /* ============================================================
       ACTION BUTTONS
    ============================================================ */
    .dashboard-page .action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 4px;
        flex-wrap: nowrap;
        min-width: 170px;
    }

    .dashboard-page .action-buttons form { margin: 0; }

    .dashboard-page .action-btn {
        width: 32px; height: 32px;
        border-radius: 6px;
        border: 1px solid var(--nx-border);
        display: grid; place-items: center;
        background: #fff;
        color: var(--nx-muted);
        text-decoration: none;
        flex-shrink: 0;
        font-size: .85rem;
        transition: background-color .15s, color .15s, border-color .15s;
        cursor: pointer;
    }

    .dashboard-page .action-btn:hover {
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        border-color: var(--nx-blue);
    }

    .dashboard-page .action-btn-success { color: var(--nx-green); }
    .dashboard-page .action-btn-success:hover {
        background: var(--nx-green-soft);
        color: var(--nx-green);
        border-color: var(--nx-green);
    }

    .dashboard-page .action-btn-warning { color: var(--nx-amber); }
    .dashboard-page .action-btn-warning:hover {
        background: var(--nx-amber-soft);
        color: var(--nx-amber);
        border-color: var(--nx-amber);
    }

    .dashboard-page .action-btn-danger { color: var(--nx-red); }
    .dashboard-page .action-btn-danger:hover {
        background: var(--nx-red-soft);
        color: var(--nx-red);
        border-color: var(--nx-red);
    }

    /* ============================================================
       ALERTS
    ============================================================ */
    .dashboard-page .nx-alert {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .75rem 1rem;
        border-radius: 8px;
        font-size: .85rem;
        border: 1px solid transparent;
        font-weight: 500;
    }

    .dashboard-page .nx-alert-green {
        background: var(--nx-green-soft);
        color: var(--nx-green);
        border-color: rgba(4, 120, 87, .25);
    }

    .dashboard-page .nx-alert-red {
        background: var(--nx-red-soft);
        color: var(--nx-red);
        border-color: rgba(185, 28, 28, .25);
    }

    .dashboard-page .nx-alert-blue {
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
        border-color: rgba(29, 78, 216, .25);
    }

    .dashboard-page .nx-alert-amber {
        background: var(--nx-amber-soft);
        color: var(--nx-amber);
        border-color: rgba(180, 83, 9, .25);
    }

    /* ============================================================
       EMPTY STATE
    ============================================================ */
    .dashboard-page .empty-table {
        text-align: center;
        padding: 2.5rem 1rem;
        color: var(--nx-muted);
    }

    /* ============================================================
       PAGINATION
    ============================================================ */
    .dashboard-page .pagination { margin-bottom: 0; gap: 4px; flex-wrap: wrap; }

    .dashboard-page .pagination .page-link {
        border-radius: 6px;
        background: #fff;
        border-color: var(--nx-border);
        color: var(--nx-blue);
        font-size: .8rem;
        box-shadow: none;
        font-weight: 600;
    }

    .dashboard-page .pagination .active .page-link {
        background: var(--nx-blue);
        color: #fff;
        border-color: var(--nx-blue);
    }

    .dashboard-page .pagination .disabled .page-link {
        color: var(--nx-muted);
        background: #e5eaf2;
    }

    /* ============================================================
       QUICK ACTIONS
    ============================================================ */
    .dashboard-page .nx-quick-action {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .9rem 1rem;
        border-radius: 10px;
        border: 1px solid var(--nx-border);
        background: #f4f6fa;
        color: var(--nx-text);
        text-decoration: none;
        transition: all .15s ease;
        height: 100%;
    }

    .dashboard-page .nx-quick-action:hover {
        border-color: var(--nx-blue);
        background: var(--nx-blue-soft);
        color: var(--nx-text);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(29, 78, 216, .12);
    }

    .dashboard-page .qa-icon {
        width: 42px; height: 42px;
        border-radius: 10px;
        display: grid; place-items: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .dashboard-page .qa-content { flex: 1; min-width: 0; }

    .dashboard-page .qa-content h6 {
        margin: 0;
        font-weight: 700;
        font-size: .88rem;
        color: var(--nx-text);
    }

    .dashboard-page .qa-content small {
        color: var(--nx-muted);
        font-size: .74rem;
    }

    .dashboard-page .qa-arrow {
        color: var(--nx-muted);
        font-size: 1rem;
        transition: all .2s ease;
        flex-shrink: 0;
    }

    .dashboard-page .nx-quick-action:hover .qa-arrow {
        color: var(--nx-blue);
        transform: translateX(3px);
    }

    /* ============================================================
       PROGRESS
    ============================================================ */
    .dashboard-page .nx-progress {
        height: 6px;
        background: #dde3ec;
        border-radius: 99px;
        overflow: hidden;
    }

    .dashboard-page .nx-progress-fill {
        height: 100%;
        border-radius: 99px;
        transition: width .6s ease;
    }

    .dashboard-page .bg-green { background: var(--nx-green); }
    .dashboard-page .bg-blue { background: var(--nx-blue); }
    .dashboard-page .bg-amber { background: var(--nx-amber); }

    /* ============================================================
       SECTION HEADER
    ============================================================ */
    .dashboard-page .nx-section-header { padding: .5rem 0; }

    .dashboard-page .nx-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--nx-text);
        margin: 0 0 .25rem;
        display: flex;
        align-items: center;
        gap: .4rem;
    }

    .dashboard-page .nx-section-subtitle {
        color: var(--nx-muted);
        font-size: .82rem;
        margin: 0;
    }

    /* ============================================================
       BOOTSTRAP OVERRIDES (darkened)
    ============================================================ */
    .dashboard-page .text-success { color: var(--nx-green) !important; }
    .dashboard-page .text-danger  { color: var(--nx-red) !important; }
    .dashboard-page .text-primary { color: var(--nx-blue) !important; }
    .dashboard-page .text-warning { color: var(--nx-amber) !important; }
    .dashboard-page .text-muted   { color: var(--nx-muted) !important; }

    .dashboard-page .badge.bg-success { background: var(--nx-green) !important; }
    .dashboard-page .badge.bg-warning { background: var(--nx-amber) !important; color: #fff !important; }
    .dashboard-page .badge.bg-danger  { background: var(--nx-red) !important; }
    .dashboard-page .badge.bg-secondary { background: var(--nx-muted) !important; }
    .dashboard-page .badge.bg-primary { background: var(--nx-blue) !important; }

    /* ============================================================
       RESPONSIVE
    ============================================================ */
    @media (max-width: 1200px) {
        .dashboard-page .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 992px) {
        .dashboard-page .stats-grid,
        .dashboard-page .stats-grid-3 { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
        .dashboard-page { padding: 1rem; }
        .dashboard-page .hero-section { padding: 1.25rem; }
        .dashboard-page .hero-title { font-size: 1.35rem; }
        .dashboard-page .hero-right { align-items: flex-start; width: 100%; }
        .dashboard-page .hero-actions { width: 100%; }
        .dashboard-page .hero-actions .nx-btn { flex: 1; }

        .dashboard-page .header-buttons {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }
        .dashboard-page .nx-btn { width: 100%; }

        .dashboard-page .premium-table thead th,
        .dashboard-page .premium-table tbody td { padding: .5rem .6rem; }
    }

    @media (max-width: 576px) {
        .dashboard-page .stats-grid,
        .dashboard-page .stats-grid-3 { grid-template-columns: 1fr; }
        .dashboard-page .mini-stat-divider { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .dashboard-page *, .dashboard-page *::before, .dashboard-page *::after {
            animation: none !important;
            transition: none !important;
        }
    }
</style>