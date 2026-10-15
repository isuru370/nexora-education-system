@extends('layouts.app')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('content')
    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Activity Logs</h1>
                    <p class="hero-subtitle">Track and monitor all system activities and changes.</p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $logs->total() }}</span>
                            <span class="mini-stat-label">Total Activities</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $logs->where('action', 'created')->count() }}</span>
                            <span class="mini-stat-label">Created</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $logs->where('action', 'updated')->count() }}</span>
                            <span class="mini-stat-label">Updated</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-actions">
                        <button type="button" class="nx-btn nx-btn-light" onclick="window.location.reload()">
                            <i class="bi bi-arrow-repeat"></i> Refresh
                        </button>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ALERTS --}}
        {{-- ===================================================== --}}
        @if (session('success'))
            <div class="nx-alert nx-alert-green mb-4">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="nx-alert nx-alert-red mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- ===================================================== --}}
        {{-- KPI CARDS --}}
        {{-- ===================================================== --}}
        <div class="stats-grid mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-activity"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Activities</span>
                    <h2 class="stat-number">{{ $logs->total() }}</h2>
                    <span class="stat-change neutral">All <span class="stat-change-note">activity logs</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-plus-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Created</span>
                    <h2 class="stat-number">{{ $logs->where('action', 'created')->count() }}</h2>
                    <span class="stat-change up">New <span class="stat-change-note">records</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-violet"><i class="bi bi-pencil-square"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Updated</span>
                    <h2 class="stat-number">{{ $logs->where('action', 'updated')->count() }}</h2>
                    <span class="stat-change neutral">Modified <span class="stat-change-note">records</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-trash-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Deleted</span>
                    <h2 class="stat-number">
                        {{ $logs->where('action', 'deleted')->count() + $logs->where('action', 'force_deleted')->count() }}
                    </h2>
                    <span class="stat-change down">Removed <span class="stat-change-note">records</span></span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- MAIN CARD --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-clock-history text-primary"></i> Activity Logs</h5>
                    <p class="nx-card-subtitle">Track and monitor all system activities and changes</p>
                </div>

                <div class="header-buttons">
                    <a href="{{ route('admin.activity-logs.export', request()->query()) }}" class="nx-btn nx-btn-success">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                    <button type="button" class="nx-btn nx-btn-danger"
                        data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                        <i class="bi bi-trash-fill"></i> Clear Old
                    </button>
                </div>
            </div>

            {{-- FILTER --}}
            <div class="nx-search">
                <form method="GET" action="{{ route('admin.activity-logs.index') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <div class="search-input-wrapper">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" class="form-control custom-input"
                                    placeholder="Search by user, table or record ID..." value="{{ request('search') }}">
                            </div>
                        </div>

                        <div class="col-lg-2">
                            <select name="action" class="form-select custom-input">
                                <option value="">All Actions</option>
                                @foreach ($actions as $action)
                                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $action)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <select name="table_name" class="form-select custom-input">
                                <option value="">All Tables</option>
                                @foreach ($tables as $table)
                                    <option value="{{ $table }}" {{ request('table_name') == $table ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $table)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <select name="user_id" class="form-select custom-input">
                                <option value="">All Users</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <input type="date" name="date_from" class="form-control custom-input"
                                placeholder="From Date" value="{{ request('date_from') }}">
                        </div>

                        <div class="col-lg-1">
                            <input type="date" name="date_to" class="form-control custom-input"
                                placeholder="To Date" value="{{ request('date_to') }}">
                        </div>

                        <div class="col-lg-12">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="nx-btn nx-btn-primary">
                                    <i class="bi bi-funnel"></i> Apply Filters
                                </button>
                                <a href="{{ route('admin.activity-logs.index') }}" class="nx-btn nx-btn-light">
                                    <i class="bi bi-arrow-repeat"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 160px;">Date &amp; Time</th>
                            <th style="width: 180px;">User</th>
                            <th style="width: 130px;">Action</th>
                            <th>Table</th>
                            <th style="width: 90px;">Record ID</th>
                            <th style="width: 100px;" class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>
                                    <div class="cell-title">
                                        <i class="bi bi-calendar3 me-1 text-muted"></i>
                                        {{ $log->created_at?->format('Y-m-d') }}
                                    </div>
                                    <small class="cell-sub">
                                        <i class="bi bi-clock"></i>
                                        {{ $log->created_at?->format('h:i A') }}
                                    </small>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="log-avatar">
                                            {{ strtoupper(substr($log->user?->name ?? 'S', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="cell-title">{{ $log->user?->name ?? 'System' }}</div>
                                            <small class="cell-sub">ID: {{ $log->user_id ?? '-' }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @php
                                        $actionColors = [
                                            'created'       => ['class' => 'nx-badge-green',  'icon' => 'bi-plus-circle-fill', 'label' => 'CREATED'],
                                            'updated'       => ['class' => 'nx-badge-blue',   'icon' => 'bi-pencil-square',    'label' => 'UPDATED'],
                                            'deleted'       => ['class' => 'nx-badge-red',    'icon' => 'bi-trash-fill',       'label' => 'DELETED'],
                                            'force_deleted' => ['class' => 'nx-badge-amber',  'icon' => 'bi-trash3-fill',      'label' => 'FORCE DELETED'],
                                        ];
                                        $action = strtolower($log->action);
                                        $actionStyle = $actionColors[$action] ?? ['class' => 'nx-badge-gray', 'icon' => 'bi-record-circle', 'label' => strtoupper($action)];
                                    @endphp
                                    <span class="nx-badge {{ $actionStyle['class'] }}">
                                        <i class="bi {{ $actionStyle['icon'] }}"></i>
                                        {{ $actionStyle['label'] }}
                                    </span>
                                </td>

                                <td>
                                    <span class="id-badge">
                                        <i class="bi bi-table"></i>
                                        {{ $log->table_name }}
                                    </span>
                                </td>

                                <td>
                                    <span class="id-badge">#{{ $log->record_id }}</span>
                                </td>

                                <td class="text-end">
                                    <div class="action-buttons">
                                        <button type="button" class="action-btn" data-bs-toggle="modal"
                                            data-bs-target="#logDetailsModal" data-id="{{ $log->id }}"
                                            data-date="{{ $log->created_at?->format('Y-m-d h:i A') }}"
                                            data-user="{{ $log->user?->name ?? 'System' }}"
                                            data-userid="{{ $log->user_id ?? '-' }}" data-action="{{ $log->action }}"
                                            data-table="{{ $log->table_name }}" data-record="{{ $log->record_id }}"
                                            data-old='@json($log->old_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)'
                                            data-new='@json($log->new_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)'
                                            title="View details" aria-label="View details">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-table">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    <strong class="d-block">No Activity Logs Found</strong>
                                    <span class="cell-sub">Try adjusting your search filters</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="p-3 border-top">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- ===================================================== --}}
    {{-- CLEAR LOGS MODAL --}}
    {{-- ===================================================== --}}
    <div class="modal fade" id="clearLogsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                        Clear Old Logs
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('admin.activity-logs.clear') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p>Delete activity logs older than:</p>
                        <select name="days" class="form-select">
                            <option value="30">30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                            <option value="180">180 days</option>
                            <option value="365">1 year</option>
                        </select>
                        <div class="alert alert-warning mt-3 mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>This action cannot be undone.</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Clear Logs</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===================================================== --}}
    {{-- LOG DETAILS MODAL — Terminal/Code Viewer --}}
    {{-- ===================================================== --}}
    <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">
                            <i class="bi bi-code-square me-2"></i>
                            Activity Log Details
                        </h5>
                        <small class="text-muted" id="modalLogDate"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    {{-- INFO CARDS --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="log-info-card">
                                <div class="log-info-icon">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="log-info-content">
                                    <span class="log-info-label">User</span>
                                    <span class="log-info-value" id="modalUser"></span>
                                    <span class="log-info-sub">
                                        User ID: <span id="modalUserId"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="log-info-card">
                                <div class="log-info-icon log-info-icon-warning">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                </div>
                                <div class="log-info-content">
                                    <span class="log-info-label">Action Details</span>
                                    <span class="log-info-value" id="modalAction"></span>
                                    <span class="log-info-sub">
                                        Table: <span id="modalTable"></span> |
                                        Record ID: <span id="modalRecord"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- CODE VIEWERS --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="code-card">
                                <div class="code-card-header">
                                    <div class="code-header-left">
                                        <i class="bi bi-archive-fill"></i>
                                        <span>Old Values</span>
                                        <span class="json-badge">JSON</span>
                                    </div>
                                    <div class="code-header-right">
                                        <button class="copy-btn" data-target="modalOldValues">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                        <span class="line-count"></span>
                                    </div>
                                </div>
                                <div class="code-container">
                                    <pre class="code-content" id="modalOldValues">-</pre>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="code-card">
                                <div class="code-card-header">
                                    <div class="code-header-left">
                                        <i class="bi bi-file-text-fill"></i>
                                        <span>New Values</span>
                                        <span class="json-badge json-badge-new">JSON</span>
                                    </div>
                                    <div class="code-header-right">
                                        <button class="copy-btn" data-target="modalNewValues">
                                            <i class="bi bi-clipboard"></i> Copy
                                        </button>
                                        <span class="line-count"></span>
                                    </div>
                                </div>
                                <div class="code-container">
                                    <pre class="code-content" id="modalNewValues">-</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection


@push('styles')
<style>
    /* ============================================================
       Activity Logs — page-specific styles.
       Shared nx-* theme comes from layouts/partials/nexora-theme.
       ============================================================ */

    /* Log row avatar */
    .dashboard-page .log-avatar {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: var(--nx-violet-soft);
        color: var(--nx-violet);
        display: grid;
        place-items: center;
        font-weight: 700;
        font-size: .82rem;
        flex-shrink: 0;
    }

    /* ============================================================
       ✅ MODAL STYLES — scoped to IDs
       (Bootstrap teleports .modal to <body>, outside .dashboard-page,
        so we can't use .dashboard-page scope here.)
       ============================================================ */

    /* ---------- Modal container ---------- */
    #clearLogsModal .modal-content,
    #logDetailsModal .modal-content {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(15, 23, 42, .15);
        overflow: hidden;
    }

    #clearLogsModal .modal-header,
    #logDetailsModal .modal-header {
        border-bottom: 1px solid #d1d8e3;
        padding: 1rem 1.25rem;
        background: #fafbfd;
    }

    #clearLogsModal .modal-title,
    #logDetailsModal .modal-title {
        font-weight: 700;
        font-size: .95rem;
        color: #0f172a;
    }

    #clearLogsModal .modal-body,
    #logDetailsModal .modal-body {
        padding: 1.25rem;
        background: #f4f6fa;
    }

    #clearLogsModal .modal-footer,
    #logDetailsModal .modal-footer {
        border-top: 1px solid #d1d8e3;
        padding: .85rem 1.25rem;
        background: #fafbfd;
    }

    /* ---------- Modal XL width ---------- */
    #logDetailsModal .modal-dialog {
        max-width: 1140px;
    }

    /* Prevent grid columns from blowing out */
    #logDetailsModal .modal-body .row > [class*="col-"] {
        min-width: 0;
    }

    /* ---------- Info cards ---------- */
    #logDetailsModal .log-info-card {
        background: #fff;
        border: 1px solid #d1d8e3;
        border-radius: 10px;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        height: 100%;
    }

    #logDetailsModal .log-info-icon {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        background: #dbe6ff;
        color: #1d4ed8;
        display: grid;
        place-items: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    #logDetailsModal .log-info-icon-warning {
        background: #fef3c7;
        color: #b45309;
    }

    #logDetailsModal .log-info-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    #logDetailsModal .log-info-label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #475569;
        font-weight: 700;
    }

    #logDetailsModal .log-info-value {
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: .1rem;
        word-break: break-word;
    }

    #logDetailsModal .log-info-sub {
        font-size: .72rem;
        color: #475569;
        margin-top: 2px;
        word-break: break-word;
    }

    /* ---------- Code card / terminal ---------- */
    #logDetailsModal .code-card {
        background: #1e1e2e;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .15);
        display: flex;
        flex-direction: column;
        height: 100%;
        max-height: 520px;
    }

    #logDetailsModal .code-card-header {
        background: #181825;
        padding: .7rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #313244;
        gap: .5rem;
        flex-wrap: wrap;
        flex-shrink: 0;
    }

    #logDetailsModal .code-header-left {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .78rem;
        font-weight: 600;
        color: #cdd6f4;
    }

    #logDetailsModal .code-header-left i {
        color: #89b4fa;
        font-size: .95rem;
    }

    #logDetailsModal .json-badge {
        background: #89b41e;
        color: #1e1e2e;
        padding: .15rem .45rem;
        border-radius: 4px;
        font-size: .6rem;
        font-weight: 700;
        letter-spacing: .5px;
    }

    #logDetailsModal .json-badge-new {
        background: #f9e2af;
        color: #1e1e2e;
    }

    #logDetailsModal .code-header-right {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    #logDetailsModal .copy-btn {
        background: #313244;
        color: #cdd6f4;
        border: none;
        padding: .25rem .6rem;
        border-radius: 6px;
        font-size: .68rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        cursor: pointer;
        transition: all .2s ease;
    }

    #logDetailsModal .copy-btn:hover {
        background: #45475a;
        transform: translateY(-1px);
    }

    #logDetailsModal .copy-btn.copied {
        background: #89b41e;
        color: #1e1e2e;
    }

    #logDetailsModal .line-count {
        font-size: .62rem;
        color: #6c7086;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    }

    /* ✅ Code container — fixed max-height + proper wrapping */
    #logDetailsModal .code-container {
        position: relative;
        flex: 1;
        min-height: 0;
        max-height: 420px;
        overflow: auto;
        background: #1e1e2e;
    }

    #logDetailsModal .code-content {
        margin: 0;
        padding: 1rem;
        font-family: 'Fira Code', 'Monaco', 'Menlo', 'Cascadia Code', 'Consolas', ui-monospace, monospace;
        font-size: .72rem;
        line-height: 1.55;
        color: #cdd6f4;
        background: #1e1e2e;

        /* ✅ Critical: force wrapping */
        white-space: pre-wrap;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: anywhere;
        max-width: 100%;
    }

    /* ✅ Child spans inherit wrapping */
    #logDetailsModal .code-content span {
        white-space: pre-wrap;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    /* JSON syntax colors */
    #logDetailsModal .code-content .json-key     { color: #89b4fa; }
    #logDetailsModal .code-content .json-string  { color: #a6e3a1; }
    #logDetailsModal .code-content .json-number  { color: #fab387; }
    #logDetailsModal .code-content .json-boolean { color: #cba6f7; }
    #logDetailsModal .code-content .json-null    { color: #f38ba8; }

    /* Scrollbar inside code viewer */
    #logDetailsModal .code-container::-webkit-scrollbar { width: 8px; height: 8px; }
    #logDetailsModal .code-container::-webkit-scrollbar-track { background: #181825; }
    #logDetailsModal .code-container::-webkit-scrollbar-thumb {
        background: #45475a;
        border-radius: 4px;
    }
    #logDetailsModal .code-container::-webkit-scrollbar-thumb:hover { background: #6c7086; }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 768px) {
        .dashboard-page .premium-table { min-width: 900px; }

        .dashboard-page .header-buttons {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }

        #logDetailsModal .modal-dialog {
            max-width: calc(100% - 1rem);
            margin: .5rem auto;
        }
        #logDetailsModal .code-card { max-height: 380px; }
        #logDetailsModal .code-container { max-height: 280px; }
        #logDetailsModal .log-info-card { flex-direction: row; }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('logDetailsModal');
        if (!modal) return;

        /* -----------------------------------------------------------
         | JSON syntax highlighter
         * --------------------------------------------------------- */
        function syntaxHighlight(json) {
            if (json === 'No old values available' || json === 'No new values available' || json === '-') {
                return json;
            }

            try {
                const obj = typeof json === 'string' ? JSON.parse(json) : json;
                let jsonStr = JSON.stringify(obj, null, 2);

                jsonStr = jsonStr
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                return jsonStr.replace(
                    /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g,
                    function (match) {
                        let cls = 'json-number';

                        if (/^"/.test(match)) {
                            if (/:$/.test(match)) {
                                cls = 'json-key';
                                match = match.replace(/:/g, '');
                                return '<span class="' + cls + '">' + match + '</span>:';
                            }
                            cls = 'json-string';
                        } else if (/true|false/.test(match)) {
                            cls = 'json-boolean';
                        } else if (/null/.test(match)) {
                            cls = 'json-null';
                        }

                        return '<span class="' + cls + '">' + match + '</span>';
                    }
                );
            } catch (e) {
                return json;
            }
        }

        /* -----------------------------------------------------------
         | Copy to clipboard
         * --------------------------------------------------------- */
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(function () {
                const originalHtml = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                button.classList.add('copied');

                setTimeout(function () {
                    button.innerHTML = originalHtml;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(function () {
                button.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Failed!';
                setTimeout(function () {
                    button.innerHTML = '<i class="bi bi-clipboard"></i> Copy';
                    button.classList.remove('copied');
                }, 2000);
            });
        }

        /* -----------------------------------------------------------
         | Line count
         * --------------------------------------------------------- */
        function countLines(text) {
            if (!text || text === '-') return 0;
            return text.split(/\r\n|\r|\n/).length;
        }

        function updateLineCount(element, targetId) {
            const text = element.textContent;
            const lines = countLines(text);
            const btn = document.querySelector(`.copy-btn[data-target="${targetId}"]`);

            if (btn) {
                const header = btn.closest('.code-card-header');
                const lineCountSpan = header.querySelector('.line-count');
                if (lineCountSpan) {
                    lineCountSpan.textContent = lines > 0 ? `${lines} lines` : '';
                }
            }
        }

        /* -----------------------------------------------------------
         | Modal open — populate details
         * --------------------------------------------------------- */
        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const date   = button.getAttribute('data-date') || '-';
            const user   = button.getAttribute('data-user') || '-';
            const userId = button.getAttribute('data-userid') || '-';
            let action   = button.getAttribute('data-action') || '-';
            const table  = button.getAttribute('data-table') || '-';
            const record = button.getAttribute('data-record') || '-';

            let oldValues = button.getAttribute('data-old') || '{}';
            let newValues = button.getAttribute('data-new') || '{}';

            switch (action.toLowerCase()) {
                case 'created':       action = 'CREATED';       break;
                case 'updated':       action = 'UPDATED';       break;
                case 'deleted':       action = 'DELETED';       break;
                case 'force_deleted': action = 'FORCE DELETED'; break;
                default:              action = action.toUpperCase();
            }

            try {
                const oldParsed = JSON.parse(oldValues);
                oldValues = (Object.keys(oldParsed).length === 0 || !oldParsed)
                    ? 'No old values available'
                    : JSON.stringify(oldParsed, null, 2);
            } catch (e) {
                oldValues = oldValues || 'No old values available';
            }

            try {
                const newParsed = JSON.parse(newValues);
                newValues = (Object.keys(newParsed).length === 0 || !newParsed)
                    ? 'No new values available'
                    : JSON.stringify(newParsed, null, 2);
            } catch (e) {
                newValues = newValues || 'No new values available';
            }

            document.getElementById('modalLogDate').textContent = date;
            document.getElementById('modalUser').textContent    = user;
            document.getElementById('modalUserId').textContent  = userId;
            document.getElementById('modalAction').textContent  = action;
            document.getElementById('modalTable').textContent   = table;
            document.getElementById('modalRecord').textContent  = record;

            const oldElement = document.getElementById('modalOldValues');
            if (oldValues !== 'No old values available') {
                oldElement.innerHTML = syntaxHighlight(oldValues);
            } else {
                oldElement.textContent = oldValues;
            }
            updateLineCount(oldElement, 'modalOldValues');

            const newElement = document.getElementById('modalNewValues');
            if (newValues !== 'No new values available') {
                newElement.innerHTML = syntaxHighlight(newValues);
            } else {
                newElement.textContent = newValues;
            }
            updateLineCount(newElement, 'modalNewValues');
        });

        /* -----------------------------------------------------------
         | Copy buttons
         * --------------------------------------------------------- */
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const targetElement = document.getElementById(targetId);
                let textToCopy = targetElement.textContent;

                if (textToCopy === 'No old values available' || textToCopy === 'No new values available') {
                    textToCopy = '';
                }

                copyToClipboard(textToCopy, this);
            });
        });
    });
</script>
@endpush