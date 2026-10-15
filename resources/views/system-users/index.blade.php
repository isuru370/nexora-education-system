@extends('layouts.app')

@section('title', 'System Users')
@section('page-title', 'System Users')

@section('content')

    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">System Users</h1>
                    <p class="hero-subtitle">Manage user accounts, status, and linked profile details.</p>

                    <div class="hero-stats-mini">
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['total'] ?? 0 }}</span>
                            <span class="mini-stat-label">Total Users</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['active'] ?? 0 }}</span>
                            <span class="mini-stat-label">Active</span>
                        </div>
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="mini-stat-value">{{ $stats['linked'] ?? 0 }}</span>
                            <span class="mini-stat-label">Linked</span>
                        </div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="hero-date-time">
                        <div class="date-box">
                            <i class="bi bi-people-fill"></i>
                            <span>User Directory</span>
                        </div>
                    </div>
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- KPI CARDS --}}
        <div class="stats-grid mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-people-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Users</span>
                    <h2 class="stat-number">{{ $stats['total'] ?? 0 }}</h2>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Active Users</span>
                    <h2 class="stat-number">{{ $stats['active'] ?? 0 }}</h2>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-link-45deg"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Linked Accounts</span>
                    <h2 class="stat-number">{{ $stats['linked'] ?? 0 }}</h2>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-red"><i class="bi bi-pause-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Inactive Users</span>
                    <h2 class="stat-number">{{ $stats['inactive'] ?? 0 }}</h2>
                </div>
            </div>
        </div>

        {{-- MAIN CARD --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-people-fill text-primary"></i> System Users Management</h5>
                    <p class="nx-card-subtitle">Manage user accounts, status, and linked profile details</p>
                </div>

                <div class="header-buttons">
                    <a href="{{ route('admin.system-users.create') }}" class="nx-btn nx-btn-primary">
                        <i class="bi bi-plus-lg"></i> Add User
                    </a>
                    <a href="{{ route('admin.system-users.export.excel') }}" class="nx-btn nx-btn-success">
                        <i class="bi bi-file-earmark-excel-fill"></i> Excel
                    </a>
                    <a href="{{ route('admin.system-users.export.pdf') }}" class="nx-btn nx-btn-danger">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
                </div>
            </div>

            {{-- ALERT --}}
            @if (session('success'))
                <div class="p-3 pb-0">
                    <div class="nx-alert nx-alert-green">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            {{-- SEARCH / FILTER --}}
            <div class="nx-search">
                <form method="GET" action="{{ route('admin.system-users.index') }}">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-5">
                            <div class="search-input-wrapper">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" class="form-control custom-input"
                                    placeholder="Search name / mobile / custom ID / user..."
                                    value="{{ request('search') }}">
                            </div>
                        </div>

                        <div class="col-lg-3">
                            <select name="is_active" class="form-select custom-input">
                                <option value="">All Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <button class="nx-btn nx-btn-primary w-100" type="submit">
                                <i class="bi bi-funnel-fill"></i> Filter
                            </button>
                        </div>

                        <div class="col-lg-2">
                            <a href="{{ route('admin.system-users.index') }}" class="nx-btn nx-btn-light w-100">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Mobile</th>
                            <th>Custom ID</th>
                            <th>Linked Account</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($systemUsers as $systemUser)
                            <tr>
                                <td class="row-number">
                                    {{ $loop->iteration + ($systemUsers->currentPage() - 1) * $systemUsers->perPage() }}
                                </td>

                                {{-- USER --}}
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="user-avatar">
                                            {{ strtoupper(substr($systemUser->full_name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="cell-title">{{ $systemUser->full_name }}</div>
                                            <small class="cell-sub">ID: {{ $systemUser->id }}</small>
                                        </div>
                                    </div>
                                </td>

                                {{-- MOBILE --}}
                                <td>
                                    <span class="cell-title">{{ $systemUser->mobile }}</span>
                                </td>

                                {{-- CUSTOM ID --}}
                                <td>
                                    <span class="id-badge">{{ $systemUser->custom_id }}</span>
                                </td>

                                {{-- LINKED ACCOUNT --}}
                                <td>
                                    @if ($systemUser->user)
                                        <div class="cell-title">{{ $systemUser->user?->name }}</div>
                                        <small class="cell-sub">{{ $systemUser->user?->email }}</small>
                                    @else
                                        <span class="cell-sub fst-italic">No linked account</span>
                                    @endif
                                </td>

                                {{-- STATUS --}}
                                <td>
                                    @if ($systemUser->is_active)
                                        <span class="nx-badge nx-badge-green">Active</span>
                                    @else
                                        <span class="nx-badge nx-badge-gray">Inactive</span>
                                    @endif
                                </td>

                                {{-- ACTIONS --}}
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.system-users.show', $systemUser) }}" class="action-btn"
                                            title="View" aria-label="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        <a href="{{ route('admin.system-users.edit', $systemUser) }}" class="action-btn"
                                            title="Edit" aria-label="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>

                                        {{-- <a href="{{ route('admin.user-permissions.index', $systemUser->id) }}"
                                            class="action-btn" title="permissions" aria-label="permissions">
                                            <i class="bi bi-key-fill"></i>
                                        </a> --}}

                                        <form action="{{ route('admin.system-users.destroy', $systemUser) }}"
                                            method="POST" onsubmit="return confirm('Delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-btn-danger" title="Delete"
                                                aria-label="Delete">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-table">
                                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                                    <strong class="d-block">No Users Found</strong>
                                    <span class="cell-sub">Try adjusting search filters or add a new user</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="p-3 border-top">
                {{ $systemUsers->links() }}
            </div>
        </div>

    </div>
@endsection


@push('styles')
    <style>
        /* ============================================================
               System Users — page-specific styles only.
               ============================================================ */

        .dashboard-page .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--nx-violet-soft);
            color: var(--nx-violet);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .dashboard-page .user-name {
            font-weight: 700;
        }
    </style>
@endpush
