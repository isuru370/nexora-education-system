@extends('layouts.app')

@section('title', 'Settings - ' . config('app.name', 'EDU NEXORA'))
@section('page-title', 'Settings')

@section('content')
    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Settings</h1>
                    <p class="hero-subtitle">Manage your database backups securely.</p>
                </div>
                <div class="hero-right">
                    <div class="hero-date-time">
                        <div class="date-box">
                            <i class="bi bi-database"></i>
                            <span>Backup Management</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALERTS --}}
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

        {{-- SECTION HEADER --}}
        <div class="nx-section-header mb-3">
            <h2 class="nx-section-title"><i class="bi bi-database text-primary"></i> Database Backup Management</h2>
            <p class="nx-section-subtitle">Manage your database backups securely</p>
        </div>

        {{-- BACKUP CARDS --}}
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="nx-card h-100">
                    <div class="p-4 d-flex flex-column h-100">
                        <div class="backup-card-icon icon-export mb-3"><i class="bi bi-download"></i></div>
                        <h4 class="backup-card-title">Export Backup</h4>
                        <p class="backup-card-desc">
                            Create and download a complete database backup instantly.
                            The backup will include all your data in a compressed ZIP format.
                        </p>
                        <div class="mt-auto">
                            <a href="{{ route('admin.setting.backup.export') }}" class="nx-btn nx-btn-success">
                                <i class="bi bi-download"></i> Export Database
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="nx-card h-100">
                    <div class="p-4 d-flex flex-column h-100">
                        <div class="backup-card-icon icon-import mb-3"><i class="bi bi-upload"></i></div>
                        <h4 class="backup-card-title">Import Backup</h4>
                        <p class="backup-card-desc">
                            Restore your database from a previously created backup ZIP file.
                            This will replace your current data.
                        </p>
                        <form action="{{ route('admin.setting.backup.import') }}" method="POST"
                            enctype="multipart/form-data" id="importForm" class="mt-auto">
                            @csrf
                            <div class="file-upload-wrapper mb-3">
                                <div class="file-upload-area" onclick="document.getElementById('backup_file').click()">
                                    <i class="bi bi-cloud-upload"></i>
                                    <p>Click to choose backup file</p>
                                    <small>ZIP files only (Max 50MB)</small>
                                    <input type="file" name="backup_file" id="backup_file" class="d-none" accept=".zip" required>
                                </div>
                                <div id="selectedFileName" class="selected-file-name mt-2" style="display: none;">
                                    <i class="bi bi-file-zip"></i>
                                    <span></span>
                                </div>
                            </div>
                            @error('backup_file')
                                <div class="nx-error mb-3"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                            @enderror
                            <button type="submit" class="nx-btn nx-btn-primary w-100" onclick="return confirmImport()">
                                <i class="bi bi-upload"></i> Import Database
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- BACKUP LOGS --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-clock-history text-primary"></i> Backup Activity Logs</h5>
                    <p class="nx-card-subtitle">Track all backup and restore operations</p>
                </div>
                <div class="logs-stats">
                    <span class="nx-badge nx-badge-green"><i class="bi bi-check-circle"></i> {{ \App\Models\BackupLog::where('status', 'success')->count() }} Success</span>
                    <span class="nx-badge nx-badge-red"><i class="bi bi-x-circle"></i> {{ \App\Models\BackupLog::where('status', 'failed')->count() }} Failed</span>
                </div>
            </div>

            @php
                $logs = \App\Models\BackupLog::latest()->paginate(20);
            @endphp

            @if ($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>File Name</th>
                                <th>IP Address</th>
                                <th>Message</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="row-number">{{ $log->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar">{{ strtoupper(substr(optional($log->user)->name ?? 'S', 0, 1)) }}</div>
                                            <span class="cell-title">{{ optional($log->user)->name ?? 'System' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="nx-badge {{ $log->action == 'export' ? 'nx-badge-green' : 'nx-badge-blue' }}">
                                            <i class="bi bi-{{ $log->action == 'export' ? 'download' : 'upload' }}"></i>
                                            {{ strtoupper($log->action) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($log->status == 'success')
                                            <span class="nx-badge nx-badge-green"><i class="bi bi-check-circle"></i> Success</span>
                                        @else
                                            <span class="nx-badge nx-badge-red"><i class="bi bi-x-circle"></i> Failed</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="id-badge" title="{{ $log->file_name }}">
                                            <i class="bi bi-file-zip"></i> {{ Str::limit($log->file_name, 30) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="id-badge">
                                            <i class="bi bi-wifi"></i> {{ $log->ip_address ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="cell-sub message-text" title="{{ $log->message }}">
                                            {{ Str::limit($log->message, 40) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-time">
                                            <span class="cell-title d-block">
                                                <i class="bi bi-calendar"></i> {{ $log->created_at?->format('Y-m-d') }}
                                            </span>
                                            <small class="cell-sub">{{ $log->created_at?->format('H:i:s') }}</small>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="empty-table">
                                        <i class="bi bi-database fs-2 d-block mb-2"></i>
                                        <strong class="d-block">No Backup Logs Found</strong>
                                        <span class="cell-sub">Backup activities will appear here</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">{{ $logs->links() }}</div>
            @else
                <div class="empty-table">
                    <i class="bi bi-database fs-2 d-block mb-2"></i>
                    <strong class="d-block">No Backup Logs Found</strong>
                    <span class="cell-sub">Backup activities will appear here</span>
                </div>
            @endif
        </div>

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Settings — page-specific styles only.
       ============================================================ */

    .dashboard-page .backup-card-icon {
        width: 56px; height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .dashboard-page .icon-export { background: var(--nx-green-soft); color: var(--nx-green); }
    .dashboard-page .icon-import { background: var(--nx-blue-soft); color: var(--nx-blue); }

    .dashboard-page .backup-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--nx-text);
        margin: 0 0 .5rem;
    }
    .dashboard-page .backup-card-desc {
        color: var(--nx-muted);
        font-size: .82rem;
        line-height: 1.5;
        margin: 0 0 1.25rem;
    }

    .dashboard-page .file-upload-wrapper { width: 100%; }
    .dashboard-page .file-upload-area {
        border: 2px dashed var(--nx-border);
        border-radius: 10px;
        padding: 1.25rem;
        text-align: center;
        cursor: pointer;
        transition: all .15s ease;
        background: #fafbfd;
    }
    .dashboard-page .file-upload-area:hover {
        border-color: var(--nx-blue);
        background: var(--nx-blue-soft);
    }
    .dashboard-page .file-upload-area i {
        font-size: 1.75rem;
        color: var(--nx-blue);
        margin-bottom: .4rem;
        display: block;
    }
    .dashboard-page .file-upload-area p {
        margin: 0;
        font-weight: 600;
        color: var(--nx-text);
        font-size: .85rem;
    }
    .dashboard-page .file-upload-area small {
        color: var(--nx-muted);
        font-size: .7rem;
    }
    .dashboard-page .selected-file-name {
        background: #f1f3f7;
        padding: .5rem .85rem;
        border-radius: 8px;
        font-size: .78rem;
        color: var(--nx-text);
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .dashboard-page .nx-error {
        color: var(--nx-red);
        font-size: .75rem;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    .dashboard-page .logs-stats {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .dashboard-page .user-avatar {
        width: 30px; height: 30px;
        border-radius: 8px;
        background: var(--nx-violet-soft);
        color: var(--nx-violet);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .dashboard-page .message-text {
        max-width: 200px;
        line-height: 1.4;
    }

    .dashboard-page .date-time { line-height: 1.3; }

    @media (max-width: 768px) {
        .dashboard-page .message-text { max-width: 150px; }
        .dashboard-page .premium-table { min-width: 900px; }
    }
</style>
@endpush