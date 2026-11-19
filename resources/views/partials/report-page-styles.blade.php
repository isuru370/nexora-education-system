{{-- Look of the payment report pages (same cards as the Teacher Salary page). --}}
<style>
    .rp-page { animation: rpFade .3s ease; }
    @keyframes rpFade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

    .rp-card {
        background: #fff;
        border: 1px solid #eef2f7;
        border-radius: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
        padding: 1.35rem 1.5rem;
    }

    .rp-avatar {
        width: 60px; height: 60px; border-radius: 18px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff; font-size: 1.5rem; font-weight: 800;
        box-shadow: 0 10px 20px rgba(79, 70, 229, .2);
    }

    .rp-title { margin: 0 0 .2rem; font-size: 1.35rem; font-weight: 800; color: #0f172a; }
    .rp-sub { margin: 0; color: #64748b; font-size: .92rem; }

    .rp-btn {
        display: inline-flex; align-items: center; gap: .4rem;
        border-radius: 14px; padding: .62rem 1.05rem; font-weight: 600; font-size: .9rem;
        border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .rp-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(15, 23, 42, .08); color: #0f172a; }
    .rp-btn-primary { background: #2563eb; border-color: #2563eb; color: #fff; }
    .rp-btn-primary:hover { color: #fff; }
    .rp-btn-sm { padding: .4rem .75rem; font-size: .8rem; border-radius: 11px; }

    .rp-stat {
        border-radius: 22px; color: #fff; padding: 1.2rem 1.3rem; min-height: 132px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .07); position: relative; overflow: hidden;
    }
    .rp-stat .rp-stat-label { font-size: .85rem; opacity: .9; margin-bottom: .45rem; }
    .rp-stat .rp-stat-value { font-size: 1.55rem; font-weight: 800; line-height: 1.15; word-break: break-word; }
    .rp-stat .rp-stat-note { font-size: .8rem; opacity: .9; margin-top: .45rem; line-height: 1.4; }
    .rp-stat i.rp-stat-icon { position: absolute; right: 16px; top: 14px; font-size: 1.9rem; opacity: .2; }
    .rp-dark { background: linear-gradient(135deg, #0f172a, #334155); }
    .rp-blue { background: linear-gradient(135deg, #2563eb, #3b82f6); }
    .rp-green { background: linear-gradient(135deg, #059669, #34d399); }
    .rp-red { background: linear-gradient(135deg, #e11d48, #fb7185); }
    .rp-amber { background: linear-gradient(135deg, #d97706, #fbbf24); }

    .rp-section-title { margin: 0; font-size: 1.1rem; font-weight: 800; color: #0f172a; }

    .rp-chip {
        display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap;
        border-radius: 999px; padding: .2rem .6rem; font-size: .74rem; font-weight: 700;
        background: #f1f5f9; color: #475569;
    }
    .rp-chip-paid { background: #dcfce7; color: #166534; }
    .rp-chip-partial { background: #fef3c7; color: #92400e; }
    .rp-chip-unpaid { background: #ffe4e6; color: #9f1239; }
    .rp-chip-freecard { background: #e0f2fe; color: #075985; }
    .rp-chip-carried { background: #ede9fe; color: #5b21b6; }

    .rp-table-wrap { overflow-x: auto; border: 1px solid #eef2f7; border-radius: 16px; }
    .rp-table { width: 100%; border-collapse: collapse; font-size: .86rem; }
    .rp-table th {
        background: #f8fafc; color: #64748b; font-size: .72rem; font-weight: 700; letter-spacing: .03em;
        text-transform: uppercase; text-align: left; padding: .65rem .6rem; white-space: nowrap;
        border-bottom: 1px solid #eef2f7;
    }
    .rp-table td { padding: .58rem .6rem; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .rp-table tbody tr:hover td { background: #fafcff; }
    .rp-table tfoot td { background: #f8fafc; font-weight: 800; color: #0f172a; border-bottom: 0; }
    .rp-table .rp-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .rp-table .rp-muted { color: #94a3b8; }
    .rp-strong { font-weight: 700; color: #0f172a; }

    .rp-mini { display: flex; flex-wrap: wrap; gap: .35rem 1.2rem; font-size: .85rem; color: #475569; }
    .rp-mini b { color: #0f172a; font-variant-numeric: tabular-nums; }

    .rp-note { background: #f0f9ff; border: 1px solid #e0f2fe; color: #0c4a6e; border-radius: 16px; padding: .85rem 1.1rem; font-size: .85rem; line-height: 1.55; }

    .rp-select { border: 1px solid #e2e8f0; border-radius: 12px; padding: .4rem .7rem; font-size: .85rem; background: #fff; }

    @media print {
        .rp-no-print, .sidebar, .main-header, header, nav { display: none !important; }
        .rp-card, .rp-stat { box-shadow: none !important; }
        .rp-stat { color: #0f172a !important; background: #fff !important; border: 1px solid #e2e8f0; }
    }
</style>
