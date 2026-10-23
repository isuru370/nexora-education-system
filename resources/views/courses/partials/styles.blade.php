@push('styles')
<style>
    /* Courses pages */
    .dashboard-page .nx-input { border: 1px solid var(--nx-border); border-radius: 10px; padding: .55rem .8rem; font-size: .92rem; }
    .dashboard-page .nx-input:focus { border-color: var(--nx-blue); box-shadow: 0 0 0 3px var(--nx-blue-soft); }
    .dashboard-page .nx-error { color: var(--nx-red); font-size: .82rem; margin-top: .3rem; }
    .dashboard-page .form-section { padding: 1.25rem; border-top: 1px solid var(--nx-border); }
    .dashboard-page .form-section:first-child { border-top: 0; }
    .dashboard-page .form-section-title { font-weight: 700; font-size: 1rem; margin-bottom: .2rem; display: flex; gap: .5rem; align-items: center; }
    .dashboard-page .form-section-sub { color: var(--nx-muted); font-size: .85rem; margin-bottom: 1rem; }
    .dashboard-page .day-picks { display: flex; flex-wrap: wrap; gap: .5rem; }
    .dashboard-page .day-pick { position: relative; }
    .dashboard-page .day-pick input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .dashboard-page .day-pick input:focus-visible + span { outline: 2px solid var(--nx-blue); outline-offset: 2px; }
    .dashboard-page .day-pick span { display: inline-block; padding: .45rem .9rem; border: 1px solid var(--nx-border); border-radius: 999px; cursor: pointer; font-size: .88rem; user-select: none; }
    .dashboard-page .day-pick input:checked + span { background: var(--nx-blue); border-color: var(--nx-blue); color: #fff; }
    .dashboard-page .day-row { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.4fr) 44px; gap: .6rem; align-items: center; margin-bottom: .6rem; }
    .dashboard-page .day-row.day-head { margin-bottom: .2rem; }
    .dashboard-page .share-row { display: grid; grid-template-columns: 1fr 140px 44px; gap: .6rem; align-items: center; margin-bottom: .6rem; }
    .dashboard-page .share-total { display: flex; flex-wrap: wrap; gap: 1rem; padding: .8rem 1rem; border-radius: 10px; background: var(--nx-head-bg); font-size: .9rem; }
    .dashboard-page .share-total strong { font-variant-numeric: tabular-nums; }
    .dashboard-page .preview-box { padding: .8rem 1rem; border-radius: 10px; background: var(--nx-blue-soft); color: var(--nx-navy); font-size: .9rem; }
    .dashboard-page .kv { display: grid; grid-template-columns: max-content 1fr; gap: .45rem 1.2rem; font-size: .92rem; }
    .dashboard-page .kv dt { color: var(--nx-muted); font-weight: 500; }
    .dashboard-page .kv dd { margin: 0; font-weight: 600; }
    .dashboard-page .nx-tabs { display: flex; gap: .25rem; padding: .5rem .75rem 0; border-bottom: 1px solid var(--nx-border); flex-wrap: wrap; }
    .dashboard-page .nx-tabs .nav-link { border: 0; border-bottom: 3px solid transparent; border-radius: 0; color: var(--nx-muted); font-weight: 600; padding: .7rem 1rem; }
    .dashboard-page .nx-tabs .nav-link.active { color: var(--nx-blue); border-bottom-color: var(--nx-blue); background: transparent; }
    .dashboard-page .money { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .dashboard-page .inline-form { display: flex; flex-wrap: wrap; gap: .6rem; align-items: flex-end; }
    .dashboard-page .inline-form > div { min-width: 160px; }
    .dashboard-page .scan-box { font-size: 1.1rem; padding: .8rem 1rem; }
    .dashboard-page tr.row-muted td { opacity: .55; }
    .dashboard-page .student-strip { display: flex; gap: .8rem; align-items: center; padding: .75rem 1rem; border-radius: 10px; background: var(--nx-head-bg); }
    .dashboard-page .student-avatar { width: 42px; height: 42px; border-radius: 50%; background: var(--nx-blue-soft); color: var(--nx-blue); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
    .dashboard-page .desk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
    .dashboard-page .desk-card { border: 1px solid var(--nx-border); border-radius: var(--nx-radius); padding: 1rem; background: var(--nx-card-bg); display: flex; flex-direction: column; gap: .6rem; }
    .dashboard-page .desk-card h6 { margin: 0; font-weight: 700; }
    .dashboard-page .special-fee summary { cursor: pointer; color: var(--nx-blue); font-weight: 600; font-size: .88rem; }
    @media (max-width: 576px) {
        .dashboard-page .share-row { grid-template-columns: 1fr 100px 40px; }
        .dashboard-page .day-row {
            grid-template-columns: minmax(0, 1fr) 30px minmax(0, 1fr) 40px;
            grid-template-areas: "day day day rm" "start start end end" "hall hall hall hall";
            padding-bottom: .6rem; border-bottom: 1px dashed var(--nx-border);
        }
        .dashboard-page .day-row > .day-select { grid-area: day; }
        .dashboard-page .day-row > .start-input { grid-area: start; }
        .dashboard-page .day-row > .end-input { grid-area: end; }
        .dashboard-page .day-row > .hall-select { grid-area: hall; }
        .dashboard-page .day-row > .remove-day { grid-area: rm; }
        .dashboard-page .day-row > .start-input, .dashboard-page .day-row > .end-input { padding-left: .5rem; padding-right: .4rem; }
        .dashboard-page .kv { grid-template-columns: 1fr; gap: .1rem; }
        .dashboard-page .kv dd { margin-bottom: .5rem; }
    }
</style>
@endpush
