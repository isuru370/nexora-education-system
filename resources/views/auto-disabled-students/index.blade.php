@extends('layouts.app')

@section('title', 'Auto-disabled Students')
@section('page-title', 'Auto-disabled Students')

@section('content')
    @php
        $range = $from->isSameDay($to) ? $from->format('d M Y') : $from->format('d M Y') . ' - ' . $to->format('d M Y');
    @endphp

    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Auto-disabled Students</h1>
                    <p class="hero-subtitle">
                        Every night at 12:00 AM the system makes a student inactive when they have not come to any
                        class for 2 months, and takes them out of their classes. These are those students. Re-activate
                        puts the student back in the same classes.
                    </p>
                </div>
                <div class="hero-right d-flex flex-column align-items-end gap-2">
                    @if ($lastRun)
                        <small class="hero-updated">
                            Last run: <strong>{{ \Carbon\Carbon::parse($lastRun['date'])->format('d M Y') }}</strong>
                            ({{ $lastRun['count'] }} students)
                        </small>
                    @endif
                    <a href="{{ route('auto-disabled-students.pdf', request()->query()) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-file-earmark-pdf"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="nx-card mb-4">
            <div class="p-3">
                <form method="GET" action="{{ route('auto-disabled-students.index') }}" class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label" for="ads-search">Search student</label>
                        <input type="text" id="ads-search" name="search" class="form-control nx-input"
                            value="{{ $filters['search'] ?? '' }}" placeholder="ID, name or mobile">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="nx-label" for="ads-from">From</label>
                        <input type="date" id="ads-from" name="from" class="form-control nx-input" value="{{ $from->toDateString() }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="nx-label" for="ads-to">To</label>
                        <input type="date" id="ads-to" name="to" class="form-control nx-input" value="{{ $to->toDateString() }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="nx-label" for="ads-status">Status</label>
                        <select id="ads-status" name="status" class="form-select nx-input">
                            <option value="">All</option>
                            <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Still inactive</option>
                            <option value="reactivated" {{ ($filters['status'] ?? '') === 'reactivated' ? 'selected' : '' }}>Re-activated</option>
                        </select>
                    </div>
                    @if ($branches->count() > 1)
                        <div class="col-lg-2 col-md-6">
                            <label class="nx-label" for="ads-branch">Branch</label>
                            <select id="ads-branch" name="branch_id" class="form-select nx-input">
                                <option value="">All</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (int) ($filters['branch_id'] ?? 0) === (int) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-lg-1 col-md-6">
                        <button type="submit" class="nx-btn nx-btn-primary w-100"><i class="bi bi-funnel"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-person-x text-danger"></i> {{ $range }}</h5>
                    <p class="nx-card-subtitle">{{ $records->total() }} students</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table premium-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Mobile</th>
                            <th>Last attendance</th>
                            <th>Last payment</th>
                            <th>Taken out of</th>
                            <th>Disabled</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            @php
                                $student = $record->student;
                                $img = $student && $student->img_url
                                    ? (\Illuminate\Support\Str::startsWith($student->img_url, 'http') ? $student->img_url : asset('storage/' . ltrim($student->img_url, '/')))
                                    : asset('storage/uploads/male.png');
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $img }}" alt="" width="38" height="38" class="rounded-circle object-fit-cover"
                                            onerror="this.style.visibility='hidden'">
                                        <div>
                                            <div class="cell-title">{{ optional($student)->initial_name ?? '-' }}</div>
                                            <small class="cell-sub">{{ optional($student)->custom_id }}
                                                @if (optional(optional($student)->grade)->grade_name)
                                                    &middot; Grade {{ $student->grade->grade_name }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ optional($student)->mobile ?: '-' }}</div>
                                    <small class="cell-sub">Guardian: {{ optional($student)->guardian_mobile ?: '-' }}</small>
                                </td>
                                <td>
                                    @if ($record->last_attendance_at)
                                        <div>{{ $record->last_attendance_at->format('d M Y') }}</div>
                                        <small class="cell-sub">{{ $record->last_attendance_class }}</small>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($record->last_payment_at)
                                        <div>Rs. {{ number_format((float) $record->last_payment_amount, 2) }}
                                            <small class="cell-sub">for {{ optional($record->last_payment_month)->format('M Y') }}</small></div>
                                        <small class="cell-sub">{{ $record->last_payment_at->format('d M Y') }} &middot; {{ $record->last_payment_class }}</small>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($record->classes ?? [] as $class)
                                        <div class="small">{{ $class['class_name'] }}<span class="text-muted">{{ $class['category'] ? ' (' . $class['category'] . ')' : '' }}</span></div>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td>
                                    <div>{{ $record->disabled_at->format('d M Y') }}</div>
                                    @if ($record->reactivated_at)
                                        <span class="nx-badge nx-badge-green">Re-activated {{ $record->reactivated_at->format('d M') }}</span>
                                        <small class="cell-sub d-block">{{ optional($record->reactivatedBy)->name }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if (!$record->reactivated_at)
                                        <form method="POST" action="{{ route('auto-disabled-students.reactivate', $record->id) }}"
                                            onsubmit="return confirm('Re-activate {{ e(optional($student)->initial_name) }} and put them back in their {{ count($record->classes ?? []) }} class(es)?')">
                                            @csrf
                                            <button type="submit" class="nx-btn nx-btn-primary nx-btn-sm">
                                                <i class="bi bi-arrow-counterclockwise"></i> Re-activate
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No students were disabled in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($records->hasPages())
                <div class="p-3">{{ $records->links() }}</div>
            @endif
        </div>
    </div>
@endsection
