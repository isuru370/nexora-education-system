@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')

    @php
        $total = $stats['total'] ?? 0;
        $pending = $stats['pending'] ?? 0;
        $sent = $stats['sent'] ?? 0;
        $failed = $stats['failed'] ?? 0;
        $unread = $stats['unread'] ?? 0;
        $today = $stats['today'] ?? 0;
    @endphp

    <div class="notifications-page">

        {{-- STATS --}}
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="stats-card">
                    <div class="stats-icon purple">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $total }}</h3>
                        <p>Total Notifications</p>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="stats-card">
                    <div class="stats-icon orange">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h3>{{ $pending }}</h3>
                        <p>Pending</p>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="stats-card">
                    <div class="stats-icon green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $sent }}</h3>
                        <p>Sent</p>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                <div class="stats-card">
                    <div class="stats-icon red">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $failed }}</h3>
                        <p>Failed</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECOND ROW STATS --}}
        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[33.33333333%]">
                <div class="stats-card">
                    <div class="stats-icon blue">
                        <i class="bi bi-envelope-open-fill"></i>
                    </div>
                    <div>
                        <h3>{{ $unread }}</h3>
                        <p>Unread</p>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[33.33333333%]">
                <div class="stats-card">
                    <div class="stats-icon teal">
                        <i class="bi bi-calendar-today"></i>
                    </div>
                    <div>
                        <h3>{{ $today }}</h3>
                        <p>Today's Notifications</p>
                    </div>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:xl:flex-[0_0_auto] tw:xl:w-[33.33333333%]">
                <div class="stats-card">
                    <div class="stats-icon pink">
                        <i class="bi bi-percent"></i>
                    </div>
                    <div>
                        <h3>{{ $total > 0 ? round(($sent / $total) * 100, 1) : 0 }}%</h3>
                        <p>Success Rate</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- MAIN CARD --}}
        <div class="main-card">

            {{-- HEADER --}}
            <div class="main-card-header">
                <div>
                    <h4>Notification Management</h4>
                    <p>View and manage all system notifications</p>
                </div>

                <div class="header-buttons">
                    {{-- Send Notification --}}
                    <a href="{{ route('admin.notifications.create') }}" class="ui-btn ui-btn-primary custom-btn">
                        <i class="bi bi-plus-lg"></i>
                        Send Notification
                    </a>

                    {{-- Mark All Read --}}
                    <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" class="tw:[display:inline]!">
                        @csrf
                        <button type="submit" class="ui-btn ui-btn-info custom-btn">
                            <i class="bi bi-check2-all"></i>
                            Mark All Read
                        </button>
                    </form>

                    {{-- Device Tokens (NEW) --}}
                    <a href="{{ route('admin.fcm-tokens.index') }}" class="ui-btn btn-purple custom-btn">
                        <i class="bi bi-phone"></i>
                        Device Tokens
                        @php
                            $activeDeviceCount = App\Models\FcmToken::active()->count();
                        @endphp
                        @if ($activeDeviceCount > 0)
                            <span class="ui-badge tw:bg-bs-white! tw:text-bs-dark! tw:ml-1!">{{ $activeDeviceCount }}</span>
                        @endif
                    </a>

                    {{-- Export --}}
                    <a href="{{ route('admin.notifications.export', request()->query()) }}"
                        class="ui-btn ui-btn-success custom-btn">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                        Export
                    </a>

                    {{-- Cleanup --}}
                    <button type="button" class="ui-btn ui-btn-danger custom-btn" data-nx-toggle="modal"
                        data-nx-target="#cleanupModal">
                        <i class="bi bi-trash-fill"></i>
                        Cleanup
                    </button>
                </div>
            </div>

            {{-- ALERT --}}
            @if (session('success'))
                <div class="ui-alert ui-alert-success custom-alert">
                    <i class="bi bi-check-circle-fill tw:mr-2!"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="ui-alert ui-alert-danger custom-alert">
                    <i class="bi bi-exclamation-circle-fill tw:mr-2!"></i>
                    {{ session('error') }}
                </div>
            @endif

            {{-- SEARCH / FILTER --}}
            <div class="search-card">
                <form method="GET" action="{{ route('admin.notifications.index') }}">
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-center!">
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
                            <div class="search-input-wrapper">
                                <i class="bi bi-search"></i>
                                <input type="text" name="search" class="ui-form-control custom-input"
                                    placeholder="Search by title, body, student..." value="{{ request('search') }}">
                            </div>
                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                            <select name="status" class="ui-form-select custom-input">
                                <option value="">All Status</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}"
                                        {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                            <select name="type" class="ui-form-select custom-input">
                                <option value="">All Types</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                            <button class="ui-btn ui-btn-primary tw:w-full! custom-btn" type="submit">
                                <i class="bi bi-funnel-fill"></i>
                                Filter
                            </button>
                        </div>

                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                            <a href="{{ route('admin.notifications.index') }}"
                                class="ui-btn ui-btn-light tw:bs-border! tw:w-full! custom-btn">
                                Reset
                            </a>
                        </div>
                    </div>

                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:mt-2!">
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                            <input type="date" name="date_from" class="ui-form-control custom-input" placeholder="From Date"
                                value="{{ request('date_from') }}">
                        </div>
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                            <input type="date" name="date_to" class="ui-form-control custom-input" placeholder="To Date"
                                value="{{ request('date_to') }}">
                        </div>
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                            <small class="tw:text-bs-secondary-color!">Filter by date range</small>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABLE --}}
            <div class="ui-table-responsive">
                <table class="ui-table custom-table tw:align-middle!">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Notification</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="tw:text-right!">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($notifications as $notification)
                            <tr class="{{ is_null($notification->read_at) ? 'unread-row' : '' }}">
                                <td>
                                    {{ $loop->iteration + ($notifications->currentPage() - 1) * $notifications->perPage() }}
                                </td>

                                <td>
                                    <div class="tw:flex! tw:items-center! tw:gap-4!">
                                        <div class="user-avatar"
                                            style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                                            {{ strtoupper(substr($notification->student->initial_name ?? 'S', 0, 1)) }}
                                        </div>

                                        <div>
                                            <div class="user-name">
                                                {{ $notification->student->initial_name ?? 'Unknown Student' }}
                                            </div>
                                            <small class="tw:text-bs-secondary-color!">
                                                ID: {{ $notification->student->custom_id ?? $notification->student_id }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="notification-content">
                                        <a href="{{ route('admin.notifications.show', $notification->id) }}"
                                            class="notification-title {{ is_null($notification->read_at) ? 'tw:font-bold!' : '' }}">
                                            {{ Str::limit($notification->title, 50) }}
                                        </a>
                                        <br>
                                        <small class="tw:text-bs-secondary-color!">{{ Str::limit($notification->body, 60) }}</small>
                                        @if (is_null($notification->read_at))
                                            <span class="ui-badge tw:bg-bs-primary! tw:ml-1!" style="font-size: 8px;">NEW</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <span class="ui-badge custom-badge {{ $notification->type }}-badge">
                                        <i class="bi tw:mr-1! {{ $notification->type_icon }}"></i>
                                        {{ $notification->type_label }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'sent' => 'success',
                                            'failed' => 'danger',
                                            'cancelled' => 'secondary',
                                        ];
                                        $statusIcons = [
                                            'pending' => 'bi-clock',
                                            'processing' => 'bi-arrow-repeat',
                                            'sent' => 'bi-check-circle',
                                            'failed' => 'bi-x-circle',
                                            'cancelled' => 'bi-ban',
                                        ];
                                    @endphp
                                    <span
                                        class="ui-badge custom-badge status-badge bg-{{ $statusColors[$notification->status] ?? 'secondary' }}">
                                        <i class="bi tw:mr-1! {{ $statusIcons[$notification->status] ?? 'bi-circle' }}"></i>
                                        {{ $notification->status_label }}
                                    </span>
                                    @if ($notification->wasFailed() && $notification->error_message)
                                        <br>
                                        <small class="tw:text-bs-danger!" title="{{ $notification->error_message }}">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                            {{ Str::limit($notification->error_message, 30) }}
                                        </small>
                                    @endif
                                </td>

                                <td>
                                    <div>
                                        {{ $notification->created_at->format('Y-m-d') }}
                                        <br>
                                        <small class="tw:text-bs-secondary-color!">{{ $notification->created_at->format('H:i') }}</small>
                                    </div>
                                </td>

                                <td class="tw:text-right!">
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.notifications.show', $notification->id) }}"
                                            class="action-btn view-btn" title="View">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>

                                        @if ($notification->isPending())
                                            <form action="{{ route('admin.notifications.cancel', $notification->id) }}"
                                                method="POST" class="tw:[display:inline]!">
                                                @csrf
                                                <button type="submit" class="action-btn cancel-btn"
                                                    onclick="return confirm('Cancel this notification?')" title="Cancel">
                                                    <i class="bi bi-ban-fill"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if ($notification->wasFailed() && $notification->canRetry())
                                            <form action="{{ route('admin.notifications.retry', $notification->id) }}"
                                                method="POST" class="tw:[display:inline]!">
                                                @csrf
                                                <button type="submit" class="action-btn retry-btn" title="Retry">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if (is_null($notification->read_at))
                                            <form action="{{ route('admin.notifications.mark-read', $notification->id) }}"
                                                method="POST" class="tw:[display:inline]!">
                                                @csrf
                                                <button type="submit" class="action-btn read-btn" title="Mark as Read">
                                                    <i class="bi bi-check2-circle"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if (!$notification->wasSent())
                                            <form action="{{ route('admin.notifications.destroy', $notification->id) }}"
                                                method="POST" class="tw:[display:inline]!">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn delete-btn"
                                                    onclick="return confirm('Delete this notification?')" title="Delete">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="tw:text-center! tw:py-12! tw:text-bs-secondary-color!">
                                    <div class="empty-state">
                                        <i class="bi bi-bell-slash"></i>
                                        <h5>No Notifications Found</h5>
                                        <p>Try adjusting search filters or send a new notification</p>
                                        <a href="{{ route('admin.notifications.create') }}" class="ui-btn ui-btn-primary tw:mt-4!">
                                            <i class="bi bi-plus-lg"></i> Send Notification
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="tw:mt-6!">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>

    {{-- CLEANUP MODAL --}}
    <div class="ui-modal ui-fade" id="cleanupModal" tabindex="-1">
        <div class="ui-modal-dialog">
            <div class="ui-modal-content">
                <form action="{{ route('admin.notifications.cleanup') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="ui-modal-header">
                        <h5 class="ui-modal-title">
                            <i class="bi bi-trash-fill tw:text-bs-danger! tw:mr-2!"></i>
                            Delete Old Notifications
                        </h5>
                        <button type="button" class="ui-btn-close" data-nx-dismiss="modal"></button>
                    </div>
                    <div class="ui-modal-body">
                        <p>Delete notifications older than:</p>
                        <div class="form-group">
                            <input type="number" name="days" class="ui-form-control custom-input" value="30"
                                min="1" max="365">
                            <small class="tw:text-bs-secondary-color!">Enter number of days (e.g., 30 for last 30 days)</small>
                        </div>
                        <div class="ui-alert ui-alert-danger tw:mt-4!">
                            <i class="bi bi-exclamation-triangle-fill tw:mr-2!"></i>
                            This will delete all sent, failed, and cancelled notifications older than specified days.
                        </div>
                    </div>
                    <div class="ui-modal-footer">
                        <button type="button" class="ui-btn ui-btn-light tw:bs-border! custom-btn"
                            data-nx-dismiss="modal">Cancel</button>
                        <button type="submit" class="ui-btn ui-btn-danger custom-btn">
                            <i class="bi bi-trash-fill tw:mr-1!"></i>
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .notifications-page {
            animation: fadeIn 0.4s ease;
        }

        .stats-card {
            background: #fff;
            border-radius: 24px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f7;
            transition: .3s ease;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            flex-shrink: 0;
        }

        .blue {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
        }

        .green {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .orange {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }

        .red {
            background: linear-gradient(135deg, #ef4444, #f87171);
        }

        .purple {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
        }

        .teal {
            background: linear-gradient(135deg, #14b8a6, #2dd4bf);
        }

        .pink {
            background: linear-gradient(135deg, #ec4899, #f472b6);
        }

        .stats-card h3 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .stats-card p {
            margin: 0;
            color: #64748b;
        }

        .main-card {
            background: #fff;
            border-radius: 28px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .main-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .main-card-header h4 {
            margin: 0;
            font-weight: 700;
        }

        .main-card-header p {
            margin: 0;
            color: #64748b;
        }

        .header-buttons {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
        }

        .custom-btn {
            border-radius: 14px;
            padding: .72rem 1.2rem;
            font-weight: 600;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            transition: .3s ease;
            font-size: .9rem;
        }

        .custom-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .custom-alert {
            border-radius: 16px;
            border: 1px solid #bbf7d0;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
        }

        .search-card {
            background: #f8fafc;
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 1.25rem;
            border: 1px solid #eef2f7;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }

        .custom-input {
            min-height: 48px;
            border-radius: 14px !important;
            border: 1px solid #e2e8f0;
            padding-left: 42px;
            box-shadow: none !important;
            transition: .3s ease;
        }

        .custom-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        select.custom-input {
            padding-left: 16px;
            padding-right: 40px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
        }

        .custom-table thead th {
            border: none;
            background: #f8fafc;
            color: #475569;
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            padding: 1rem;
            white-space: nowrap;
        }

        .custom-table tbody tr {
            transition: .2s ease;
        }

        .custom-table tbody tr:hover {
            background: #f8fafc;
        }

        .custom-table tbody td {
            padding: 1rem;
            border-color: #f1f5f9;
            vertical-align: middle;
        }

        .unread-row {
            background: #f0f7ff;
        }

        .unread-row:hover {
            background: #e3eefb !important;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .user-name {
            font-weight: 700;
        }

        .notification-title {
            color: #1e293b;
            text-decoration: none;
            transition: .2s ease;
        }

        .notification-title:hover {
            color: #2563eb;
        }

        .custom-badge {
            border-radius: 10px;
            padding: .5rem .7rem;
            font-size: .75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
        }

        .general-badge {
            background: #e0e7ff;
            color: #4338ca;
        }

        .reminder-badge {
            background: #fef3c7;
            color: #d97706;
        }

        .exam-badge {
            background: #dbeafe;
            color: #2563eb;
        }

        .announcement-badge {
            background: #d1fae5;
            color: #065f46;
        }

        .attendance-badge {
            background: #ede9fe;
            color: #6d28d9;
        }

        .grade-badge {
            background: #fce4ec;
            color: #be185d;
        }

        .payment-badge {
            background: #d1fae5;
            color: #065f46;
        }

        .result-badge {
            background: #e0e7ff;
            color: #4338ca;
        }

        .status-badge {
            min-width: 80px;
            justify-content: center;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: .2s ease;
            cursor: pointer;
            font-size: .9rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .view-btn {
            background: #eff6ff;
            color: #2563eb;
        }

        .edit-btn {
            background: #fef3c7;
            color: #d97706;
        }

        .delete-btn {
            background: #fef2f2;
            color: #ef4444;
        }

        .cancel-btn {
            background: #fef3c7;
            color: #d97706;
        }

        .retry-btn {
            background: #d1fae5;
            color: #065f46;
        }

        .read-btn {
            background: #e0e7ff;
            color: #4338ca;
        }

        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .empty-state h5 {
            font-weight: 700;
            margin-bottom: .35rem;
        }

        .empty-state p {
            margin: 0;
            color: #64748b;
        }

        .btn-purple.custom-btn {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            color: #fff;
        }

        .btn-purple.custom-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
        }

        .btn-purple.custom-btn .ui-badge {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .main-card-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-buttons {
                width: 100%;
            }

            .header-buttons a,
            .header-buttons button {
                flex: 1;
                justify-content: center;
            }

            .action-buttons {
                justify-content: flex-start;
            }

            .stats-card {
                padding: 1rem;
            }

            .stats-icon {
                width: 48px;
                height: 48px;
                font-size: 1.2rem;
            }

            .stats-card h3 {
                font-size: 1.2rem;
            }
        }

        /* Pagination Custom */
        .ui-pagination {
            gap: 4px;
        }

        .ui-pagination .ui-page-link {
            border-radius: 10px !important;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .ui-pagination .ui-page-link:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .ui-pagination .active .ui-page-link {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
    </style>
@endpush
