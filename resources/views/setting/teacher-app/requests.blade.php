@extends('layouts.app')

@section('title', 'Teacher Requests')
@section('page-title', 'Teacher Requests')

@section('content')
    @php
        $badges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'secondary', 'failed' => 'danger'];
        $actions = ['create' => 'Extra day', 'update' => 'Change', 'cancel' => 'Cancel'];
        $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('h:i A') : '';
    @endphp

    <div class="dashboard-page">
        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Teacher Requests</h1>
                    <p class="hero-subtitle">
                        Class day changes teachers asked for in the staff app. Approve to make the change and tell the parents.
                        @if ($autoApply)
                            <br><strong>"Apply at once" is on</strong>, so new changes are done without approval.
                        @endif
                    </p>
                </div>
                <div class="hero-right">
                    <a href="{{ route('admin.setting.teacher-app') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-sliders"></i> Teacher App settings
                    </a>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="ui-alert ui-alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="ui-alert ui-alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="tw:flex! tw:flex-wrap! tw:gap-2! tw:mb-4!">
            @foreach (['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected', 'failed' => 'Could not apply', 'all' => 'All'] as $key => $label)
                <a href="{{ route('admin.setting.teacher-requests', ['status' => $key]) }}"
                    class="nx-btn {{ $status === $key ? 'nx-btn-primary' : 'nx-btn-light' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="nx-card">
            <div class="ui-table-responsive">
                <table class="ui-table tw:align-middle! tw:mb-0!">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Class / Course</th>
                            <th>Request</th>
                            <th>Now</th>
                            <th>Asked for</th>
                            <th>Status</th>
                            <th class="tw:text-right!"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $r)
                            @php
                                $p = $r->payload ?? [];
                                $isCourse = $r->kind === 'course';
                                $current = $isCourse ? $r->courseSession : $r->classSchedule;
                                $currentDate = $current ? ($isCourse ? $current->session_date : $current->class_date) : null;
                            @endphp
                            <tr>
                                <td>
                                    <div class="tw:font-semibold!">{{ $r->teacher->full_name ?? '-' }}</div>
                                    <small class="tw:text-(color:--nx-muted)!">{{ $r->branch->name ?? '' }} · {{ $r->created_at->format('d M, h:i A') }}</small>
                                </td>
                                <td>
                                    {{ $isCourse ? ($r->course->name ?? 'Course') : ($r->studentClass->class_name ?? 'Class') }}
                                    @if ($isCourse)
                                        <span class="ui-badge tw:bg-bs-info-bg-subtle! tw:text-bs-info!">Course</span>
                                    @elseif ($current && $current->classCategoryFee)
                                        <small class="tw:text-(color:--nx-muted)! tw:block!">{{ $current->classCategoryFee->category->category_name ?? '' }}</small>
                                    @endif
                                </td>
                                <td><span class="tw:font-semibold!">{{ $actions[$r->action] ?? $r->action }}</span></td>
                                <td>
                                    @if ($current)
                                        {{ optional($currentDate)->format('D, d M Y') }}<br>
                                        <small class="tw:text-(color:--nx-muted)!">{{ $fmtTime($current->start_time) }} - {{ $fmtTime($current->end_time) }}
                                            {{ $current->hall ? '· ' . $current->hall->hall_name : '' }}</small>
                                    @else
                                        <span class="tw:text-(color:--nx-muted)!">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($r->action === 'cancel')
                                        <span class="tw:text-(color:--nx-red)!">Cancel</span><br>
                                        <small class="tw:text-(color:--nx-muted)!">{{ $p['reason'] ?? '' }}</small>
                                    @else
                                        {{ !empty($p['date']) ? \Carbon\Carbon::parse($p['date'])->format('D, d M Y') : '' }}<br>
                                        <small class="tw:text-(color:--nx-muted)!">{{ $fmtTime($p['start_time'] ?? null) }} - {{ $fmtTime($p['end_time'] ?? null) }}
                                            {{ !empty($p['class_hall_id']) && isset($halls[$p['class_hall_id']]) ? '· ' . $halls[$p['class_hall_id']] : '' }}</small>
                                        @if (!empty($p['note']))
                                            <small class="tw:block! tw:text-(color:--nx-muted)!">{{ $p['note'] }}</small>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <span class="ui-badge bg-{{ $badges[$r->status] ?? 'secondary' }}">{{ ucfirst($r->status) }}</span>
                                    @if ($r->error)
                                        <small class="tw:block! tw:text-(color:--nx-red)!">{{ $r->error }}</small>
                                    @endif
                                    @if ($r->review_note)
                                        <small class="tw:block! tw:text-(color:--nx-muted)!">{{ $r->review_note }}</small>
                                    @endif
                                    @if ($r->reviewer)
                                        <small class="tw:block! tw:text-(color:--nx-muted)!">{{ $r->reviewer->name }}</small>
                                    @endif
                                </td>
                                <td class="tw:text-right! tw:whitespace-nowrap!">
                                    @if ($r->status === 'pending')
                                        <form method="POST" action="{{ route('admin.setting.teacher-requests.approve', $r->id) }}" class="tw:[display:inline]!">
                                            @csrf
                                            <button class="nx-btn nx-btn-success ui-btn-sm"><i class="bi bi-check-lg"></i> Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.setting.teacher-requests.reject', $r->id) }}" class="tw:[display:inline]!"
                                            onsubmit="const n = prompt('Reason (optional)'); if (n === null) return false; this.review_note.value = n;">
                                            @csrf
                                            <input type="hidden" name="review_note">
                                            <button class="nx-btn nx-btn-light ui-btn-sm"><i class="bi bi-x-lg"></i> Reject</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="tw:text-center! tw:text-(color:--nx-muted)! tw:py-6!">No requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tw:mt-4!">{{ $requests->links() }}</div>
    </div>
@endsection
