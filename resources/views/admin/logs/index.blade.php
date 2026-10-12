@extends('layouts.app')

@section('title', 'Laravel Logs')
@section('page-title', 'Laravel Logs')

@section('content')

    <div class="logs-page">

        {{-- ============================================= --}}
        {{-- ALERTS --}}
        {{-- ============================================= --}}
        @if (session('success'))
            <div class="alert-premium success">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="alert-close" data-bs-dismiss="alert">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert-premium danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="alert-close" data-bs-dismiss="alert">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @endif

        @if (isset($error) && $error)
            <div class="alert-premium warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ $error }}</span>
                <button type="button" class="alert-close" data-bs-dismiss="alert">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        @endif

        {{-- ============================================= --}}
        {{-- STATS CARDS --}}
        {{-- ============================================= --}}
        <div class="stats-grid" id="logStats">
            <div class="stats-card-premium stats-blue">
                <div class="stats-card-inner">
                    <div class="stats-icon-wrapper">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <div class="stats-info">
                        <span class="stats-label">File Size</span>
                        <h3 class="stats-number" id="fileSize">-</h3>
                        <span class="stats-trend">
                            <i class="bi bi-hdd"></i> Storage
                        </span>
                    </div>
                </div>
                <div class="stats-progress">
                    <div class="stats-progress-bar" style="width: 45%;"></div>
                </div>
            </div>

            <div class="stats-card-premium stats-purple">
                <div class="stats-card-inner">
                    <div class="stats-icon-wrapper">
                        <i class="bi bi-list-ol"></i>
                    </div>
                    <div class="stats-info">
                        <span class="stats-label">Total Lines</span>
                        <h3 class="stats-number" id="totalLines">-</h3>
                        <span class="stats-trend">
                            <i class="bi bi-code-slash"></i> Entries
                        </span>
                    </div>
                </div>
                <div class="stats-progress">
                    <div class="stats-progress-bar" style="width: 70%;"></div>
                </div>
            </div>

            <div class="stats-card-premium stats-red">
                <div class="stats-card-inner">
                    <div class="stats-icon-wrapper">
                        <i class="bi bi-bug"></i>
                    </div>
                    <div class="stats-info">
                        <span class="stats-label">Errors</span>
                        <h3 class="stats-number" id="errorCount">-</h3>
                        <span class="stats-trend down">
                            <i class="bi bi-arrow-up-short"></i> Critical
                        </span>
                    </div>
                </div>
                <div class="stats-progress">
                    <div class="stats-progress-bar" style="width: 20%;"></div>
                </div>
            </div>

            <div class="stats-card-premium stats-orange">
                <div class="stats-card-inner">
                    <div class="stats-icon-wrapper">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stats-info">
                        <span class="stats-label">Warnings</span>
                        <h3 class="stats-number" id="warningCount">-</h3>
                        <span class="stats-trend">
                            <i class="bi bi-flag"></i> Alerts
                        </span>
                    </div>
                </div>
                <div class="stats-progress">
                    <div class="stats-progress-bar" style="width: 35%;"></div>
                </div>
            </div>
        </div>

        {{-- ============================================= --}}
        {{-- MAIN LOG CARD --}}
        {{-- ============================================= --}}
        <div class="logs-card-premium">

            {{-- Header --}}
            <div class="logs-card-header">
                <div class="header-left">
                    <div class="header-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>
                        <h4 class="header-title">
                            Laravel Application Logs
                        </h4>
                        <p class="header-subtitle">
                            <i class="bi bi-clock-history"></i>
                            System errors, exceptions and warnings in real-time
                        </p>
                    </div>
                </div>

                <div class="header-right">
                    <div class="search-wrapper-premium">
                        <i class="bi bi-search"></i>
                        <input type="text" id="logSearch" placeholder="Search logs..." class="search-input-premium">
                        <kbd class="search-shortcut">⌘K</kbd>
                    </div>

                    <button type="button" class="btn-premium btn-download" id="downloadBtn">
                        <i class="bi bi-download"></i>
                        <span>Download</span>
                    </button>

                    <button type="button" class="btn-premium btn-danger" id="clearBtn" data-bs-toggle="modal"
                        data-bs-target="#clearLogsModal">
                        <i class="bi bi-trash"></i>
                        <span>Clear</span>
                    </button>
                </div>
            </div>

            {{-- ============================================= --}}
            {{-- LOG FILE SELECTOR --}}
            {{-- ============================================= --}}
            <div class="log-file-selector-premium">
                <div class="file-selector-left">
                    <div class="file-icon-premium">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <div>
                        <div class="file-selector-title">Log File</div>
                        <div class="file-selector-subtitle">Select a log file to view</div>
                    </div>
                </div>

                <form method="GET" action="{{ route('logs.laravel.index') }}" id="logFileForm"
                    class="file-selector-form">
                    <div class="select-wrapper-premium">
                        <i class="bi bi-chevron-down"></i>
                        <select name="file" id="logFileSelector" class="log-file-select-premium">
                            @forelse ($files ?? [] as $file)
                                <option value="{{ $file['name'] }}"
                                    {{ ($selectedFile ?? '') === $file['name'] ? 'selected' : '' }}>
                                    📄 {{ \Carbon\Carbon::parse($file['date'])->format('d M Y') }}
                                    — {{ $file['name'] }}
                                    ({{ $file['size'] }} KB)
                                </option>
                            @empty
                                <option value="">No log files available</option>
                            @endforelse
                        </select>
                    </div>
                </form>
            </div>

            {{-- ============================================= --}}
            {{-- SELECTED FILE INFO --}}
            {{-- ============================================= --}}
            @if (!empty($selectedFileInfo))
                <div class="selected-file-info-premium">
                    <div class="file-status">
                        <span class="status-dot active"></span>
                        <span class="file-name">
                            <i class="bi bi-file-earmark-code"></i>
                            {{ $selectedFileInfo['name'] }}
                        </span>
                    </div>
                    <div class="file-meta-premium">
                        <span>
                            <i class="bi bi-calendar3"></i>
                            {{ $selectedFileInfo['date']
                                ? \Carbon\Carbon::parse($selectedFileInfo['date'])->format('d M Y')
                                : 'Unknown date' }}
                        </span>
                        <span>
                            <i class="bi bi-hdd"></i>
                            {{ $selectedFileInfo['size'] }} KB
                        </span>
                        @if (!empty($selectedFileInfo['modified_at']))
                            <span>
                                <i class="bi bi-clock"></i>
                                {{ \Carbon\Carbon::parse($selectedFileInfo['modified_at'])->format('h:i A') }}
                            </span>
                        @endif
                        <span class="live-badge">
                            <i class="bi bi-circle-fill"></i>
                            Live
                        </span>
                    </div>
                </div>
            @endif

            {{-- ============================================= --}}
            {{-- TERMINAL --}}
            {{-- ============================================= --}}
            <div class="terminal-container">

                {{-- Terminal Header --}}
                <div class="terminal-header-premium">
                    <div class="terminal-dots">
                        <span class="dot red"></span>
                        <span class="dot yellow"></span>
                        <span class="dot green"></span>
                    </div>
                    <span class="terminal-title-premium">
                        <i class="bi bi-terminal"></i>
                        {{ $selectedFile ?? 'laravel.log' }}
                    </span>
                    <div class="terminal-actions">
                        <button type="button" class="terminal-btn" id="terminalClear" title="Clear terminal">
                            <i class="bi bi-eraser"></i>
                        </button>
                        <button type="button" class="terminal-btn" id="terminalScrollTop" title="Scroll to top">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <button type="button" class="terminal-btn" id="terminalScrollBottom" title="Scroll to bottom">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                    </div>
                    <div class="filter-buttons-premium">
                        <button type="button" class="filter-btn-premium active" data-filter="all">
                            <span class="filter-dot all"></span> All
                        </button>
                        <button type="button" class="filter-btn-premium" data-filter="error">
                            <span class="filter-dot error"></span> Errors
                        </button>
                        <button type="button" class="filter-btn-premium" data-filter="warning">
                            <span class="filter-dot warning"></span> Warnings
                        </button>
                        <button type="button" class="filter-btn-premium" data-filter="exception">
                            <span class="filter-dot exception"></span> Exceptions
                        </button>
                    </div>
                </div>

                {{-- Terminal Body --}}
                <div class="terminal-body-wrapper">
                    <div class="terminal-line-numbers" id="lineNumbers"></div>
                    <div class="terminal-body" id="logContent"></div>
                </div>

                {{-- Terminal Footer --}}
                <div class="terminal-footer-premium">
                    <span class="terminal-status">
                        <span class="status-indicator active"></span>
                        <span id="lineCount">0</span> lines
                    </span>
                    <span class="terminal-auto-refresh">
                        <label class="toggle-label">
                            <input type="checkbox" id="autoRefreshToggle" checked>
                            <span class="toggle-slider"></span>
                            Auto-refresh
                        </label>
                    </span>
                    <span class="terminal-key-hint">
                        <kbd>⌘F</kbd> Search &bull; <kbd>Esc</kbd> Clear search &bull; <kbd>→</kbd> Scroll
                    </span>
                </div>

            </div>

        </div>

    </div>

    {{-- ============================================= --}}
    {{-- CLEAR LOG MODAL --}}
    {{-- ============================================= --}}
    <div class="modal fade" id="clearLogsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content-premium">
                <div class="modal-header-premium">
                    <div class="modal-header-left">
                        <div class="modal-icon danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title-premium">Clear Log</h5>
                            <p class="modal-subtitle-premium">This action cannot be undone</p>
                        </div>
                    </div>
                    <button type="button" class="modal-close-premium" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body-premium">
                    <p class="modal-message">
                        Are you sure you want to clear this Laravel log?
                    </p>

                    <div class="modal-alert-premium warning">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            <strong>{{ $selectedFile ?? 'Selected log file' }}</strong>
                            will be completely emptied.
                            <br>
                            <small>Other daily log files will not be affected.</small>
                        </div>
                    </div>

                    <div class="modal-confirm-input">
                        <label class="confirm-label">
                            Type <strong>{{ $selectedFile ?? 'laravel.log' }}</strong> to confirm
                        </label>
                        <input type="text" id="confirmFileName" class="confirm-input-premium"
                            placeholder="Type the file name...">
                    </div>
                </div>

                <div class="modal-footer-premium">
                    <button type="button" class="btn-premium btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <form action="{{ route('logs.laravel.clear') }}" method="POST" id="clearForm">
                        @csrf
                        <input type="hidden" name="file" value="{{ $selectedFile ?? '' }}">
                        <button type="submit" class="btn-premium btn-danger" id="confirmClearBtn" disabled>
                            <i class="bi bi-trash"></i>
                            Yes, Clear Log
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

{{-- ============================================= --}}
{{-- STYLES --}}
{{-- ============================================= --}}
@push('styles')
    <style>
        .logs-page {
            animation: fadeIn 0.5s ease;
            padding: 1.5rem;
            max-width: 1600px;
            margin: 0 auto;
            background: #f8fafc;
            min-height: 100vh;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================= */
        /* ALERT PREMIUM */
        /* ============================================= */
        .alert-premium {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
            position: relative;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-premium.success {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-color: #10b981;
            color: #065f46;
        }

        .alert-premium.danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-color: #ef4444;
            color: #991b1b;
        }

        .alert-premium.warning {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border-color: #f59e0b;
            color: #92400e;
        }

        .alert-premium i {
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .alert-premium .alert-close {
            background: none;
            border: none;
            margin-left: auto;
            opacity: 0.5;
            cursor: pointer;
            transition: opacity 0.2s;
            color: inherit;
            padding: 0.25rem;
        }

        .alert-premium .alert-close:hover {
            opacity: 1;
        }

        /* ============================================= */
        /* STATS GRID */
        /* ============================================= */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stats-card-premium {
            background: #fff;
            border-radius: 20px;
            padding: 1.5rem 1.75rem;
            border: 1px solid #f1f5f9;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .stats-card-premium:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
            border-color: transparent;
        }

        .stats-card-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stats-blue::before {
            background: linear-gradient(90deg, #2563eb, #60a5fa);
        }
        .stats-purple::before {
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
        }
        .stats-red::before {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }
        .stats-orange::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .stats-card-inner {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .stats-icon-wrapper {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stats-blue .stats-icon-wrapper {
            background: #eff6ff;
            color: #2563eb;
        }
        .stats-purple .stats-icon-wrapper {
            background: #f5f3ff;
            color: #8b5cf6;
        }
        .stats-red .stats-icon-wrapper {
            background: #fef2f2;
            color: #ef4444;
        }
        .stats-orange .stats-icon-wrapper {
            background: #fffbeb;
            color: #f59e0b;
        }

        .stats-info {
            flex: 1;
            min-width: 0;
        }

        .stats-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .stats-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0.1rem 0 0.2rem;
            line-height: 1.2;
        }

        .stats-trend {
            font-size: 0.7rem;
            font-weight: 600;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
        }

        .stats-trend.down {
            color: #ef4444;
        }

        .stats-progress {
            margin-top: 1rem;
            height: 3px;
            background: #f1f5f9;
            border-radius: 10px;
            overflow: hidden;
        }

        .stats-progress-bar {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #2563eb, #60a5fa);
            transition: width 1s ease;
        }

        .stats-purple .stats-progress-bar {
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
        }
        .stats-red .stats-progress-bar {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }
        .stats-orange .stats-progress-bar {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        /* ============================================= */
        /* MAIN LOG CARD */
        /* ============================================= */
        .logs-card-premium {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #f1f5f9;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        /* ============================================= */
        /* HEADER */
        /* ============================================= */
        .logs-card-header {
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            background: #fafcfd;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.3rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .header-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .header-subtitle {
            font-size: 0.8rem;
            color: #94a3b8;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        /* ============================================= */
        /* SEARCH */
        /* ============================================= */
        .search-wrapper-premium {
            position: relative;
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .search-wrapper-premium:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .search-wrapper-premium i {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .search-input-premium {
            border: none;
            background: transparent;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            min-width: 220px;
            font-size: 0.85rem;
            color: #0f172a;
            border-radius: 12px;
        }

        .search-input-premium:focus {
            outline: none;
        }

        .search-input-premium::placeholder {
            color: #94a3b8;
        }

        .search-shortcut {
            position: absolute;
            right: 10px;
            font-size: 0.6rem;
            font-weight: 600;
            color: #94a3b8;
            background: #f1f5f9;
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        /* ============================================= */
        /* BUTTONS */
        /* ============================================= */
        .btn-premium {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .btn-download {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: #fff;
        }

        .btn-download:hover {
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.35);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: #fff;
        }

        .btn-danger:hover {
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.35);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* ============================================= */
        /* LOG FILE SELECTOR */
        /* ============================================= */
        .log-file-selector-premium {
            padding: 1rem 1.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .file-selector-left {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .file-icon-premium {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .file-selector-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 0.9rem;
        }

        .file-selector-subtitle {
            color: #94a3b8;
            font-size: 0.75rem;
            margin-top: 2px;
        }

        .select-wrapper-premium {
            position: relative;
            min-width: 300px;
        }

        .select-wrapper-premium i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.8rem;
        }

        .log-file-select-premium {
            width: 100%;
            padding: 0.6rem 2.5rem 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            color: #0f172a;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            appearance: none;
            transition: all 0.2s ease;
        }

        .log-file-select-premium:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* ============================================= */
        /* SELECTED FILE INFO */
        /* ============================================= */
        .selected-file-info-premium {
            padding: 0.7rem 1.75rem;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .file-status {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.active {
            background: #10b981;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%,
            100% {
                opacity: 1;
            }
            50% {
                opacity: 0.3;
            }
        }

        .file-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .file-meta-premium {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #94a3b8;
            font-size: 0.8rem;
            flex-wrap: wrap;
        }

        .file-meta-premium span {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.65rem;
            font-weight: 700;
            color: #10b981;
            background: #ecfdf5;
            padding: 0.1rem 0.6rem;
            border-radius: 20px;
        }

        .live-badge i {
            font-size: 0.4rem;
        }

        /* ============================================= */
        /* TERMINAL */
        /* ============================================= */
        .terminal-container {
            background: #0a0c10;
            overflow: hidden;
        }

        .terminal-header-premium {
            background: #1a1d24;
            padding: 10px 20px;
            border-bottom: 1px solid #2d313a;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .terminal-dots {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot.red {
            background: #ff5f56;
        }
        .dot.yellow {
            background: #ffbd2e;
        }
        .dot.green {
            background: #27c93f;
        }

        .terminal-title-premium {
            color: #8b949e;
            font-size: 0.8rem;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .terminal-title-premium i {
            font-size: 0.9rem;
        }

        .terminal-actions {
            display: flex;
            gap: 0.3rem;
            margin-left: auto;
        }

        .terminal-btn {
            background: transparent;
            border: none;
            color: #8b949e;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.8rem;
        }

        .terminal-btn:hover {
            background: #2d313a;
            color: #fff;
        }

        .filter-buttons-premium {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .filter-btn-premium {
            background: transparent;
            border: 1px solid #2d313a;
            color: #8b949e;
            padding: 0.25rem 0.8rem;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .filter-btn-premium:hover {
            background: #2d313a;
            color: #fff;
        }

        .filter-btn-premium.active {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
        }

        .filter-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .filter-dot.all {
            background: #8b949e;
        }
        .filter-dot.error {
            background: #f85149;
        }
        .filter-dot.warning {
            background: #d29922;
        }
        .filter-dot.exception {
            background: #be8fff;
        }

        /* ============================================= */
        /* TERMINAL BODY */
        /* ============================================= */
        .terminal-body-wrapper {
            display: flex;
            position: relative;
            max-height: 600px;
            overflow: auto;
            background: #0a0c10;
        }

        /* ============================================= */
        /* LINE NUMBERS */
        /* ============================================= */
        .terminal-line-numbers {
            padding: 16px 8px;
            background: #0d1117;
            color: #2d313a;
            font-size: 11px;
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            text-align: right;
            min-width: 40px;
            user-select: none;
            border-right: 1px solid #1a1d24;
            flex-shrink: 0;
            line-height: 1.6;
            overflow: hidden;
            white-space: pre;
            font-feature-settings: "tnum";
        }

        .terminal-line-numbers .line-num {
            display: block;
            color: #2d313a;
            padding: 4px 0;
            font-size: 11px;
            line-height: 1.6;
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            transition: color 0.15s ease;
        }

        .terminal-line-numbers .line-num.active {
            color: #8b949e;
        }

        .terminal-line-numbers .line-num.hidden {
            display: none;
        }

        /* ============================================= */
        /* TERMINAL BODY - SCROLLING */
        /* ============================================= */
        .terminal-body {
            margin: 0;
            padding: 16px 20px;
            background: #0a0c10;
            color: #e6edf3;
            font-size: 12px;
            line-height: 1.6;
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            height: 600px;
            max-height: 600px;
            overflow-x: auto;
            overflow-y: auto;
            white-space: normal;
            flex: 1;
            min-width: 0;
        }

        /* ============================================= */
        /* LOG LINE */
        /* ============================================= */
        .log-line {
            display: block;
            width: max-content;
            min-width: 100%;
            padding: 4px 12px;
            margin: 0 0 2px 0;
            border-left: 3px solid transparent;
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            font-size: 11px;
            line-height: 1.6;
            white-space: pre;
            word-break: normal;
            overflow: visible;
            transition: background 0.15s ease;
        }

        .log-line:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        /* ============================================= */
        /* LOG LINE COLORS */
        /* ============================================= */
        .log-error {
            color: #f85149;
            border-left-color: #f85149;
            background: rgba(248, 81, 73, 0.04);
        }

        .log-warning {
            color: #d29922;
            border-left-color: #d29922;
            background: rgba(210, 153, 34, 0.04);
        }

        .log-exception {
            color: #be8fff;
            border-left-color: #be8fff;
            background: rgba(190, 143, 255, 0.04);
        }

        .log-info {
            color: #79c0ff;
            border-left-color: #79c0ff;
        }

        .log-debug {
            color: #8b949e;
            border-left-color: #8b949e;
        }

        .log-default {
            color: #e6edf3;
        }

        .log-timestamp {
            color: #58a6ff;
            font-weight: 600;
        }

        /* ============================================= */
        /* SCROLLBAR */
        /* ============================================= */
        .terminal-body-wrapper::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .terminal-body-wrapper::-webkit-scrollbar-track {
            background: #1a1d24;
        }

        .terminal-body-wrapper::-webkit-scrollbar-thumb {
            background: #2d313a;
            border-radius: 4px;
        }

        .terminal-body-wrapper::-webkit-scrollbar-thumb:hover {
            background: #3d4250;
        }

        .terminal-body::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .terminal-body::-webkit-scrollbar-track {
            background: #1a1d24;
        }

        .terminal-body::-webkit-scrollbar-thumb {
            background: #2d313a;
            border-radius: 4px;
        }

        .terminal-body::-webkit-scrollbar-thumb:hover {
            background: #3d4250;
        }

        /* ============================================= */
        /* TERMINAL FOOTER */
        /* ============================================= */
        .terminal-footer-premium {
            background: #1a1d24;
            padding: 6px 20px;
            border-top: 1px solid #2d313a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.7rem;
            color: #8b949e;
        }

        .terminal-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-indicator {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-indicator.active {
            background: #10b981;
            animation: pulse-dot 2s infinite;
        }

        .toggle-label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .toggle-label input {
            display: none;
        }

        .toggle-slider {
            width: 32px;
            height: 18px;
            background: #2d313a;
            border-radius: 20px;
            position: relative;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .toggle-slider::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 14px;
            height: 14px;
            background: #8b949e;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .toggle-label input:checked+.toggle-slider {
            background: #4f46e5;
        }

        .toggle-label input:checked+.toggle-slider::after {
            left: 16px;
            background: #fff;
        }

        .terminal-key-hint kbd {
            background: #2d313a;
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            font-size: 0.6rem;
            font-weight: 600;
            color: #8b949e;
            border: 1px solid #3d4250;
        }

        /* ============================================= */
        /* MODAL */
        /* ============================================= */
        .modal-content-premium {
            background: #fff;
            border-radius: 24px;
            border: none;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .modal-header-premium {
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .modal-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .modal-icon.danger {
            background: #fef2f2;
            color: #ef4444;
        }

        .modal-title-premium {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .modal-subtitle-premium {
            font-size: 0.8rem;
            color: #94a3b8;
            margin: 0;
        }

        .modal-close-premium {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.25rem;
            transition: color 0.2s;
        }

        .modal-close-premium:hover {
            color: #0f172a;
        }

        .modal-body-premium {
            padding: 1.75rem;
        }

        .modal-message {
            font-size: 0.95rem;
            color: #475569;
            margin-bottom: 1rem;
        }

        .modal-alert-premium {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 12px;
            background: #fffbeb;
            border: 1px solid #f59e0b;
            color: #92400e;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }

        .modal-alert-premium i {
            font-size: 1.2rem;
            color: #f59e0b;
            margin-top: 0.1rem;
        }

        .modal-alert-premium small {
            color: #92400e;
            opacity: 0.7;
        }

        .modal-confirm-input {
            margin-top: 0.5rem;
        }

        .confirm-label {
            display: block;
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 0.4rem;
        }

        .confirm-input-premium {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .confirm-input-premium:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .modal-footer-premium {
            padding: 1rem 1.75rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            background: #fafcfd;
        }

        /* ============================================= */
        /* RESPONSIVE */
        /* ============================================= */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .logs-card-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-left {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-right {
                flex-wrap: wrap;
                width: 100%;
            }

            .search-wrapper-premium {
                flex: 1;
                min-width: 200px;
            }

            .search-input-premium {
                width: 100%;
                min-width: 0;
            }

            .search-shortcut {
                display: none;
            }

            .log-file-selector-premium {
                flex-direction: column;
                align-items: stretch;
            }

            .select-wrapper-premium {
                min-width: 0;
                width: 100%;
            }

            .filter-buttons-premium {
                margin-left: 0;
                width: 100%;
            }

            .terminal-actions {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .logs-page {
                padding: 0.75rem;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }

            .stats-card-premium {
                padding: 1rem 1.25rem;
            }

            .stats-number {
                font-size: 1.3rem;
            }

            .stats-icon-wrapper {
                width: 44px;
                height: 44px;
                font-size: 1.1rem;
            }

            .logs-card-header {
                padding: 1rem;
            }

            .log-file-selector-premium {
                padding: 0.75rem 1rem;
            }

            .selected-file-info-premium {
                padding: 0.5rem 1rem;
                flex-direction: column;
                align-items: flex-start;
            }

            .file-meta-premium {
                gap: 0.5rem;
            }

            .terminal-header-premium {
                padding: 8px 12px;
                flex-direction: column;
                align-items: stretch;
            }

            .terminal-dots {
                display: none;
            }

            .terminal-body {
                padding: 12px 16px;
                font-size: 10px;
                height: 400px;
                max-height: 400px;
            }

            .log-line {
                font-size: 9px;
                padding: 3px 10px;
            }

            .terminal-line-numbers {
                display: none;
            }

            .terminal-body-wrapper {
                max-height: 400px;
            }

            .terminal-footer-premium {
                flex-direction: column;
                align-items: stretch;
                gap: 0.3rem;
                padding: 6px 12px;
            }

            .modal-body-premium {
                padding: 1.25rem;
            }

            .modal-header-premium {
                padding: 1rem 1.25rem;
            }

            .modal-footer-premium {
                padding: 0.75rem 1.25rem;
                flex-direction: column;
            }

            .modal-footer-premium .btn-premium {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.6rem;
            }

            .stats-card-premium {
                padding: 0.8rem 1rem;
                border-radius: 16px;
            }

            .stats-number {
                font-size: 1.1rem;
            }

            .stats-icon-wrapper {
                width: 38px;
                height: 38px;
                font-size: 0.9rem;
                border-radius: 10px;
            }

            .header-right {
                flex-direction: column;
            }

            .header-right .btn-premium {
                width: 100%;
                justify-content: center;
            }

            .filter-btn-premium {
                font-size: 0.55rem;
                padding: 0.15rem 0.5rem;
            }

            .terminal-body {
                font-size: 8px;
                padding: 8px 12px;
                height: 300px;
                max-height: 300px;
            }

            .log-line {
                font-size: 8px;
                padding: 2px 8px;
            }

            .terminal-body-wrapper {
                max-height: 300px;
            }
        }
    </style>
@endpush

{{-- ============================================= --}}
{{-- SCRIPTS --}}
{{-- ============================================= --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logContent = document.getElementById('logContent');
            const searchInput = document.getElementById('logSearch');
            const downloadBtn = document.getElementById('downloadBtn');
            const logFileSelector = document.getElementById('logFileSelector');
            const clearForm = document.getElementById('clearForm');
            const confirmInput = document.getElementById('confirmFileName');
            const confirmBtn = document.getElementById('confirmClearBtn');
            const autoRefreshToggle = document.getElementById('autoRefreshToggle');
            const terminalClearBtn = document.getElementById('terminalClear');
            const terminalScrollTopBtn = document.getElementById('terminalScrollTop');
            const terminalScrollBottomBtn = document.getElementById('terminalScrollBottom');
            const lineNumbers = document.getElementById('lineNumbers');

            const rawLogContent = @json($content ?? '');

            let currentFilter = 'all';
            let originalHtml = '';
            let autoRefresh = true;
            let refreshInterval;
            let currentRawContent = @json($content ?? '');

            const selectedFile = @json($selectedFile ?? '');

            // =============================================
            // PARSE LOG LINES
            // =============================================
            function parseLogLines(content) {
                if (!content || content.trim() === '') {
                    return `
                        <div style="color: #8b949e; padding: 2rem; text-align: center; font-size: 0.9rem;">
                            <i class="bi bi-file-earmark-x" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                            No log entries found.
                            <br>
                            <small style="color: #5c6470;">The selected log file is empty.</small>
                        </div>
                    `;
                }

                const lines = content.split('\n');
                let html = '';

                for (let line of lines) {
                    if (line.trim() === '') continue;

                    let logClass = 'log-default';
                    const lowerLine = line.toLowerCase();

                    if (lowerLine.includes('exception') || lowerLine.includes('throwable')) {
                        logClass = 'log-exception';
                    } else if (lowerLine.includes('[error]') || lowerLine.includes(' error ')) {
                        logClass = 'log-error';
                    } else if (lowerLine.includes('[warning]') || lowerLine.includes(' warning ')) {
                        logClass = 'log-warning';
                    } else if (lowerLine.includes('[info]')) {
                        logClass = 'log-info';
                    } else if (lowerLine.includes('[debug]')) {
                        logClass = 'log-debug';
                    }

                    let escapedLine = line
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');

                    escapedLine = escapedLine.replace(
                        /(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/,
                        '<span class="log-timestamp">$1</span>'
                    );

                    html += `
                        <div class="log-line ${logClass}" data-raw="${encodeURIComponent(line)}">${escapedLine}</div>
                    `;
                }

                return html;
            }

            // =============================================
            // RENDER LINE NUMBERS
            // =============================================
            function renderLineNumbers() {
                if (!lineNumbers) return;

                const lines = document.querySelectorAll('.log-line');
                let html = '';
                let count = 0;
                let visibleCount = 0;

                lines.forEach((line, index) => {
                    const isVisible = line.style.display !== 'none';
                    if (isVisible) {
                        count++;
                        visibleCount++;
                        const isActive = line.classList.contains('log-error') ||
                            line.classList.contains('log-exception');
                        html += `<span class="line-num ${isActive ? 'active' : ''}">${count}</span>`;
                    } else {
                        html += `<span class="line-num hidden">${index + 1}</span>`;
                    }
                });

                lineNumbers.innerHTML = html;
                document.getElementById('lineCount').textContent = visibleCount;
            }

            // =============================================
            // FILTER LOGS
            // =============================================
            function filterLogs() {
                if (!originalHtml) return;

                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const lines = document.querySelectorAll('.log-line');

                lines.forEach(function(line) {
                    const rawLine = decodeURIComponent(line.getAttribute('data-raw') || '').toLowerCase();
                    const logClass = line.className;

                    let showByType = true;
                    if (currentFilter === 'error') {
                        showByType = logClass.includes('log-error') || logClass.includes('log-exception');
                    } else if (currentFilter === 'warning') {
                        showByType = logClass.includes('log-warning');
                    } else if (currentFilter === 'exception') {
                        showByType = logClass.includes('log-exception');
                    }

                    let showBySearch = true;
                    if (searchTerm) {
                        showBySearch = rawLine.includes(searchTerm);
                    }

                    line.style.display = (showByType && showBySearch) ? 'block' : 'none';
                });

                renderLineNumbers();
            }

            // =============================================
            // RENDER LOGS
            // =============================================
            function renderLogs(content) {
                currentRawContent = content;
                originalHtml = parseLogLines(content);

                if (logContent) {
                    logContent.innerHTML = originalHtml;
                }

                filterLogs();
                renderLineNumbers();
            }

            // =============================================
            // GET DISPLAYED RAW CONTENT
            // =============================================
            function getDisplayedRawContent() {
                const lines = document.querySelectorAll('#logContent .log-line');

                if (!lines.length) {
                    return '';
                }

                return Array.from(lines)
                    .map(function(line) {
                        return decodeURIComponent(line.getAttribute('data-raw') || '');
                    })
                    .join('\n');
            }

            // =============================================
            // INITIALIZE LOG
            // =============================================
            if (logContent) {
                renderLogs(rawLogContent);
            }

            // =============================================
            // SEARCH
            // =============================================
            if (searchInput) {
                searchInput.addEventListener('input', filterLogs);

                document.addEventListener('keydown', function(e) {
                    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                        e.preventDefault();
                        searchInput.focus();
                        searchInput.select();
                    }
                    if (e.key === 'Escape') {
                        searchInput.value = '';
                        filterLogs();
                        searchInput.blur();
                    }
                });
            }

            // =============================================
            // FILTER BUTTONS
            // =============================================
            document.querySelectorAll('.filter-btn-premium').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.filter-btn-premium').forEach(function(b) {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');
                    currentFilter = this.getAttribute('data-filter');
                    filterLogs();
                });
            });

            // =============================================
            // TERMINAL CLEAR
            // =============================================
            if (terminalClearBtn) {
                terminalClearBtn.addEventListener('click', function() {
                    if (logContent) {
                        logContent.innerHTML = '';
                        if (lineNumbers) lineNumbers.innerHTML = '';
                        document.getElementById('lineCount').textContent = '0';
                    }
                });
            }

            // =============================================
            // TERMINAL SCROLL
            // =============================================
            if (terminalScrollTopBtn) {
                terminalScrollTopBtn.addEventListener('click', function() {
                    const wrapper = document.querySelector('.terminal-body');
                    if (wrapper) wrapper.scrollTop = 0;
                });
            }

            if (terminalScrollBottomBtn) {
                terminalScrollBottomBtn.addEventListener('click', function() {
                    const wrapper = document.querySelector('.terminal-body');
                    if (wrapper) wrapper.scrollTop = wrapper.scrollHeight;
                });
            }

            // =============================================
            // FILE SELECTOR
            // =============================================
            if (logFileSelector) {
                logFileSelector.addEventListener('change', function() {
                    const file = this.value;
                    if (!file) return;
                    const url = new URL('{{ route('logs.laravel.index') }}', window.location.origin);
                    url.searchParams.set('file', file);
                    window.location.href = url.toString();
                });
            }

            // =============================================
            // DOWNLOAD
            // =============================================
            if (downloadBtn) {
                downloadBtn.addEventListener('click', function() {
                    if (!selectedFile) {
                        alert('No log file selected.');
                        return;
                    }
                    const url = new URL('{{ route('logs.laravel.download') }}', window.location.origin);
                    url.searchParams.set('file', selectedFile);
                    window.location.href = url.toString();
                });
            }

            // =============================================
            // CONFIRM CLEAR
            // =============================================
            if (confirmInput && confirmBtn) {
                confirmInput.addEventListener('input', function() {
                    const fileName = this.value.trim();
                    const expected = selectedFile || 'laravel.log';
                    confirmBtn.disabled = fileName !== expected;
                });
            }

            // =============================================
            // AUTO REFRESH - JSON API
            // =============================================
            if (autoRefreshToggle) {
                autoRefreshToggle.addEventListener('change', function() {
                    autoRefresh = this.checked;
                    if (autoRefresh) {
                        startAutoRefresh();
                    } else {
                        clearInterval(refreshInterval);
                    }
                });
            }

            function startAutoRefresh() {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }

                refreshInterval = setInterval(function() {
                    if (!autoRefresh || document.hidden || !selectedFile) {
                        return;
                    }

                    const url = new URL(
                        '{{ route('logs.laravel.content') }}',
                        window.location.origin
                    );

                    url.searchParams.set('file', selectedFile);

                    fetch(url.toString(), {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Log content request failed');
                            }
                            return response.json();
                        })
                        .then(function(data) {
                            if (!data.success || typeof data.content !== 'string') {
                                return;
                            }

                            const newContent = data.content;
                            const displayedContent = getDisplayedRawContent();

                            if (newContent !== displayedContent) {
                                renderLogs(newContent);
                                loadStats();
                            }
                        })
                        .catch(function(error) {
                            console.error('Log auto-refresh failed:', error);
                        });

                }, 30000);
            }

            // =============================================
            // LOAD STATS
            // =============================================
            function loadStats() {
                if (!selectedFile) return;

                const url = new URL('{{ route('logs.laravel.stats') }}', window.location.origin);
                url.searchParams.set('file', selectedFile);

                fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        const fileSize = document.getElementById('fileSize');
                        const totalLines = document.getElementById('totalLines');
                        const errorCount = document.getElementById('errorCount');
                        const warningCount = document.getElementById('warningCount');

                        if (fileSize) fileSize.textContent = data.size + ' KB';
                        if (totalLines) totalLines.textContent = Number(data.lines || 0).toLocaleString();
                        if (errorCount) errorCount.textContent = Number(data.errors || 0).toLocaleString();
                        if (warningCount) warningCount.textContent = Number(data.warnings || 0)
                            .toLocaleString();
                    })
                    .catch(() => {});
            }

            // =============================================
            // INITIAL STATS & AUTO REFRESH
            // =============================================
            loadStats();
            startAutoRefresh();

            // =============================================
            // VISIBILITY CHANGE
            // =============================================
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    autoRefresh = false;
                } else {
                    autoRefresh = autoRefreshToggle ? autoRefreshToggle.checked : true;
                    if (autoRefresh) {
                        loadStats();
                        startAutoRefresh();
                    }
                }
            });
        });
    </script>
@endpush