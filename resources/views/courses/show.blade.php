@extends('layouts.app')

@section('title', $course->name)
@section('page-title', 'Course')

@section('content')
@php
    $canManage = in_array(strtoupper((string) optional(auth()->user()->userType)->code), ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], true);
    // institute share -> owner: Super Admin + Branch Admin
    $canPayOwner = in_array(strtoupper((string) optional(auth()->user()->userType)->code), ['SUPER_ADMIN', 'BRANCH_ADMIN'], true);
    $fmtTime = fn ($t) => \Carbon\Carbon::parse($t)->format('h:i A');
    $today = today();
@endphp
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section tw:mb-6!">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">{{ $course->code ?: 'Course' }} · {{ $course->branch->name ?? '' }}</span>
                <h1 class="hero-title">{{ $course->name }}</h1>
                <p class="hero-subtitle">
                    {{ $course->start_date->format('d M Y') }} – {{ $course->end_date->format('d M Y') }}<br>
                    {{ $course->scheduleLabel() }}
                </p>
                <div class="hero-stats-mini">
                    <div class="mini-stat">@include('courses.partials.status-badge', ['status' => $course->status()])</div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('courses.index') }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Courses</a>
                    @if ($canManage)
                        <a href="{{ route('courses.edit', $course) }}" class="nx-btn nx-btn-primary"><i class="bi bi-pencil-square"></i> Edit</a>
                        <form method="POST" action="{{ route('courses.toggle-active', $course) }}" onsubmit="return confirm('{{ $course->is_active ? 'Deactivate this course? No new enrollments or attendance.' : 'Activate this course?' }}')">
                            @csrf @method('PATCH')
                            <button class="nx-btn nx-btn-light" type="submit"><i class="bi bi-power"></i> {{ $course->is_active ? 'Deactivate' : 'Activate' }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')

    {{-- KPIs --}}
    <div class="stats-grid tw:mb-6!">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Students</span>
                <h2 class="stat-number">{{ $stats['students'] }}</h2>
                <span class="stat-change neutral">Fee {{ number_format($course->full_fee, 2) }} <span class="stat-change-note">/ once {{ number_format($course->one_time_fee, 2) }}</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-info">
                <span class="stat-label">Collected</span>
                <h2 class="stat-number money">{{ number_format($stats['collected'], 2) }}</h2>
                <span class="stat-change up">Institute <span class="stat-change-note">{{ number_format($instituteEarned, 2) }}</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <span class="stat-label">To collect</span>
                <h2 class="stat-number money">{{ number_format($stats['balance'], 2) }}</h2>
                <span class="stat-change {{ $stats['overdue'] > 0 ? 'down' : 'neutral' }}">Overdue <span class="stat-change-note">{{ number_format($stats['overdue'], 2) }}</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-info">
                <span class="stat-label">Class days</span>
                <h2 class="stat-number">{{ $stats['held'] }} / {{ $stats['sessions'] }}</h2>
                <span class="stat-change neutral">held <span class="stat-change-note">so far</span></span>
            </div>
        </div>
    </div>

    <div class="nx-card tw:mb-6!">
        <ul class="ui-nav nx-tabs" role="tablist">
            <li class="ui-nav-item"><button class="ui-nav-link active" data-nx-toggle="tab" data-nx-target="#students" type="button" role="tab">Students ({{ $stats['students'] }})</button></li>
            <li class="ui-nav-item"><button class="ui-nav-link" data-nx-toggle="tab" data-nx-target="#sessions" type="button" role="tab">Class days</button></li>
            <li class="ui-nav-item"><button class="ui-nav-link" data-nx-toggle="tab" data-nx-target="#payments" type="button" role="tab">Payments</button></li>
            <li class="ui-nav-item"><button class="ui-nav-link" data-nx-toggle="tab" data-nx-target="#teachers" type="button" role="tab">Teachers</button></li>
            @if ($canPayOwner)
                <li class="ui-nav-item">
                    <button class="ui-nav-link" data-nx-toggle="tab" data-nx-target="#owner" type="button" role="tab">
                        <i class="bi bi-building-check"></i> Owner share
                        @if ($institute['balance'] > 0)<span class="ui-badge tw:[background:var(--nx-amber)]! tw:text-white! tw:text-bs-dark! tw:ml-1!">{{ number_format($institute['balance'], 0) }}</span>@endif
                    </button>
                </li>
            @endif
        </ul>

        <div class="ui-tab-content">

            {{-- STUDENTS --}}
            <div class="ui-tab-pane ui-fade show active" id="students" role="tabpanel">
                @if ($course->is_active)
                    <div class="tw:p-4! tw:bs-border-b!">
                        <form method="POST" action="{{ route('courses.enrollments.store', $course) }}" class="inline-form">
                            @csrf
                            <div class="tw:grow!">
                                <label class="nx-label" for="student_code">Enroll a student</label>
                                <input id="student_code" name="student_code" class="ui-form-control nx-input" placeholder="Student ID or scan QR" value="{{ old('student_code') }}" required autocomplete="off">
                            </div>
                            <div>
                                <label class="nx-label" for="payment_plan">Payment</label>
                                <select id="payment_plan" name="payment_plan" class="ui-form-select nx-input">
                                    <option value="installment" {{ old('payment_plan') === 'full' ? '' : 'selected' }}>{{ $course->installment_count }} installments ({{ number_format($course->full_fee, 2) }})</option>
                                    <option value="full" {{ old('payment_plan') === 'full' ? 'selected' : '' }}>Pay at once ({{ number_format($course->one_time_fee, 2) }})</option>
                                </select>
                            </div>
                            <div style="min-width:auto">
                                <button class="nx-btn nx-btn-primary" type="submit"><i class="bi bi-person-plus"></i> Enroll</button>
                            </div>
                            <div class="tw:w-full!">@include('courses.partials.special-fee-fields', ['sfId' => 'show'])</div>
                        </form>
                    </div>
                @endif

                <div class="ui-table-responsive">
                    <table class="ui-table premium-table tw:mb-0!">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Plan</th>
                                <th class="tw:text-right!">Fee</th>
                                <th class="tw:text-right!">Paid</th>
                                <th class="tw:text-right!">Balance</th>
                                <th>Next due</th>
                                <th class="tw:text-right!">Attended</th>
                                <th class="tw:text-right!">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($enrollments as $row)
                                @php
                                    $e = $row['enrollment'];
                                    $f = $row['fees'];
                                @endphp
                                <tr class="{{ $e->isActive() ? '' : 'row-muted' }}">
                                    <td>
                                        <a class="cell-title tw:[text-decoration:none]!" href="{{ route('courses.enrollments.show', [$course, $e]) }}">{{ $e->student->initial_name ?? '—' }}</a>
                                        <small class="cell-sub tw:block!">{{ $e->student->custom_id ?? '' }} @if (!$e->isActive()) · <span class="tw:text-(color:--nx-red)!">Cancelled</span> @endif</small>
                                    </td>
                                    <td>{{ $e->payment_plan === 'full' ? 'At once' : 'Installments' }}</td>
                                    <td class="tw:text-right! money">
                                        {{ number_format($f['total_fee'], 2) }}
                                        @if ($f['special_fee'])<small class="tw:block!"><span class="nx-badge nx-badge-amber" title="{{ $f['fee_reason'] }}">Special</span></small>@endif
                                    </td>
                                    <td class="tw:text-right! money">{{ number_format($f['paid'], 2) }}</td>
                                    <td class="tw:text-right! money">
                                        @if ($f['balance'] <= 0)
                                            <span class="nx-badge nx-badge-green">Paid</span>
                                        @else
                                            {{ number_format($f['balance'], 2) }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($f['next_installment'])
                                            <span class="money">{{ number_format($f['next_installment']['remaining'], 2) }}</span>
                                            <small class="cell-sub tw:block!">{{ \Carbon\Carbon::parse($f['next_installment']['due_date'])->format('d M') }}
                                                @if ($f['next_installment']['status'] === 'overdue') <span class="tw:text-(color:--nx-red)!">· overdue</span> @endif</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="tw:text-right!">{{ $e->attendances_count }}</td>
                                    <td class="tw:text-right!">
                                        <div class="action-buttons">
                                            <a href="{{ route('courses.enrollments.show', [$course, $e]) }}" class="action-btn action-btn-success" title="Payments" aria-label="Payments"><i class="bi bi-cash-coin"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="empty-table"><i class="bi bi-people tw:text-[length:calc(1.325rem_+_0.9vw)]! tw:xl:text-[length:2rem]! tw:block! tw:mb-2!"></i><strong class="tw:block!">No students yet</strong><span class="cell-sub">Enroll a student with their ID or QR code.</span></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SESSIONS --}}
            <div class="ui-tab-pane ui-fade" id="sessions" role="tabpanel">
                @if ($canManage)
                    <div class="tw:p-4! tw:bs-border-b!">
                        <details class="special-fee" {{ $errors->has('session_date') || $errors->has('end_time') ? 'open' : '' }}>
                            <summary>Add a class day</summary>
                            <form method="POST" action="{{ route('courses.sessions.store', $course) }}" class="inline-form tw:mt-2!">
                                @csrf
                                <div><label class="nx-label" for="new_session_date">Date</label><input type="date" id="new_session_date" name="session_date" class="ui-form-control nx-input" min="{{ $today->toDateString() }}" value="{{ old('session_date') }}" required></div>
                                <div><label class="nx-label" for="new_start_time">Start</label><input type="time" id="new_start_time" name="start_time" class="ui-form-control nx-input" value="{{ old('start_time') }}" required></div>
                                <div><label class="nx-label" for="new_end_time">End</label><input type="time" id="new_end_time" name="end_time" class="ui-form-control nx-input" value="{{ old('end_time') }}" required></div>
                                <div>
                                    <label class="nx-label" for="new_hall">Hall</label>
                                    <select id="new_hall" name="class_hall_id" class="ui-form-select nx-input">
                                        <option value="">No hall</option>
                                        @foreach ($halls as $hall)
                                            <option value="{{ $hall->id }}" {{ (string) old('class_hall_id') === (string) $hall->id ? 'selected' : '' }}>{{ $hall->hall_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="min-width:auto"><button class="nx-btn nx-btn-primary" type="submit" onclick="return confirm('Add this class day? Parents will be told.')"><i class="bi bi-calendar-plus"></i> Add</button></div>
                            </form>
                            <small class="cell-sub tw:block! tw:mt-1!">For an extra class outside the weekly days. One class per day.</small>
                        </details>
                    </div>
                @endif
                <div class="ui-table-responsive">
                    <table class="ui-table premium-table tw:mb-0!">
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Hall</th><th class="tw:text-right!">Present</th><th>Status</th><th class="tw:text-right!">Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($sessions as $session)
                                <tr class="{{ $session->isCancelled() ? 'row-muted' : '' }}">
                                    <td>
                                        <span class="cell-title">{{ $session->session_date->format('D, d M Y') }}</span>
                                        @if ($session->is_custom)
                                            <span class="nx-badge nx-badge-amber tw:ml-1!" title="{{ $session->original_date ? 'Moved from ' . $session->original_date->format('d M') : 'Extra class day' }}">{{ $session->original_date ? 'Changed' : 'Extra' }}</span>
                                        @endif
                                        @if ($session->session_date->isSameDay($today)) <span class="nx-badge nx-badge-violet tw:ml-1!">Today</span> @endif
                                    </td>
                                    <td class="money">{{ $fmtTime($session->start_time) }} – {{ $fmtTime($session->end_time) }}</td>
                                    <td>{{ $session->hall->hall_name ?? '—' }}</td>
                                    <td class="tw:text-right!">{{ $session->attendances_count }} / {{ $stats['students'] }}</td>
                                    <td>
                                        @include('courses.partials.status-badge', ['status' => $session->status])
                                        @if ($session->cancel_reason) <small class="cell-sub tw:block!">{{ $session->cancel_reason }}</small> @endif
                                    </td>
                                    <td class="tw:text-right!">
                                        <div class="action-buttons">
                                            <a href="{{ route('courses.sessions.show', [$course, $session]) }}" class="action-btn" title="Attendance" aria-label="Attendance"><i class="bi bi-person-check-fill"></i></a>
                                            @if ($canManage && $session->canEdit())
                                                <a href="{{ route('courses.sessions.show', [$course, $session]) }}#edit" class="action-btn action-btn-warning" title="Change date / time / hall" aria-label="Change class day"><i class="bi bi-pencil-fill"></i></a>
                                            @endif
                                            @if ($canManage)
                                                @if ($session->canRestore())
                                                    <form method="POST" action="{{ route('courses.sessions.status', [$course, $session]) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="scheduled">
                                                        <button class="action-btn action-btn-success" title="Restore class day" aria-label="Restore class day"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                    </form>
                                                @elseif ($session->canCancel())
                                                    <form method="POST" action="{{ route('courses.sessions.status', [$course, $session]) }}" onsubmit="const r = prompt('Reason for cancelling this class day (e.g. holiday). Parents will be told:'); if (r === null) return false; this.cancel_reason.value = r; return true;">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <input type="hidden" name="cancel_reason" value="">
                                                        <button class="action-btn action-btn-danger" title="Cancel class day" aria-label="Cancel class day"><i class="bi bi-x-circle-fill"></i></button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="empty-table">No class days.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PAYMENTS --}}
            <div class="ui-tab-pane ui-fade" id="payments" role="tabpanel">
                <div class="ui-table-responsive">
                    <table class="ui-table premium-table tw:mb-0!">
                        <thead>
                            <tr><th>Receipt</th><th>Date</th><th>Student</th><th>Method</th><th class="tw:text-right!">Amount</th><th>Status</th><th class="tw:text-right!">Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                @include('courses.partials.payment-row', ['payment' => $payment, 'showStudent' => true, 'showCourse' => false])
                            @empty
                                <tr><td colspan="7" class="empty-table">No payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TEACHERS --}}
            <div class="ui-tab-pane ui-fade" id="teachers" role="tabpanel">
                <div class="tw:p-4!">
                    <p class="cell-sub tw:mb-4!">Each teacher earns their % of every course payment. This is separate from the monthly teacher salary.</p>
                    <div class="ui-table-responsive">
                        <table class="ui-table premium-table tw:mb-0!">
                            <thead>
                                <tr><th>Teacher</th><th class="tw:text-right!">Share</th><th class="tw:text-right!">Earned</th><th class="tw:text-right!">Paid to teacher</th><th class="tw:text-right!">Balance</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($teachers as $row)
                                    <tr>
                                        <td><span class="cell-title">{{ $row['teacher']->full_name }}</span><small class="cell-sub tw:block!">{{ $row['teacher']->custom_id }}</small></td>
                                        <td class="tw:text-right!">{{ $row['percentage'] !== null ? rtrim(rtrim(number_format($row['percentage'], 2), '0'), '.') . '%' : 'Removed' }}</td>
                                        <td class="tw:text-right! money">{{ number_format($row['earned'], 2) }}</td>
                                        <td class="tw:text-right! money">{{ number_format($row['paid'], 2) }}</td>
                                        <td class="tw:text-right! money"><strong class="{{ $row['balance'] < 0 ? 'tw:text-(color:--nx-red)!' : '' }}">{{ number_format($row['balance'], 2) }}</strong></td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td><span class="cell-title">Institute</span><small class="cell-sub tw:block!">Paid = handed to the owner</small></td>
                                    <td class="tw:text-right!">{{ rtrim(rtrim(number_format($course->institutePercentage(), 2), '0'), '.') }}%</td>
                                    <td class="tw:text-right! money">{{ number_format($institute['earned'], 2) }}</td>
                                    <td class="tw:text-right! money">{{ number_format($institute['paid'], 2) }}</td>
                                    <td class="tw:text-right! money"><strong class="{{ $institute['balance'] < 0 ? 'tw:text-(color:--nx-red)!' : '' }}">{{ number_format($institute['balance'], 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($canManage && $teachers->where('balance', '>', 0)->count())
                    <div class="tw:p-4! tw:bs-border-t!">
                        <form method="POST" action="{{ route('courses.payouts.store', $course) }}" class="inline-form">
                            @csrf
                            <div>
                                <label class="nx-label" for="payout_teacher">Pay teacher</label>
                                <select id="payout_teacher" name="teacher_id" class="ui-form-select nx-input" required>
                                    @foreach ($teachers->where('balance', '>', 0) as $row)
                                        <option value="{{ $row['teacher']->id }}">{{ $row['teacher']->full_name }} (balance {{ number_format($row['balance'], 2) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="nx-label" for="payout_amount">Amount</label>
                                <input id="payout_amount" type="number" step="0.01" min="1" name="amount" class="ui-form-control nx-input" required>
                            </div>
                            <div>
                                <label class="nx-label" for="payout_method">Method</label>
                                <select id="payout_method" name="payment_method" class="ui-form-select nx-input">
                                    @foreach (\App\Models\CoursePayment::METHODS as $method)
                                        <option value="{{ $method }}">{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tw:grow!">
                                <label class="nx-label" for="payout_note">Note</label>
                                <input id="payout_note" name="note" class="ui-form-control nx-input" maxlength="500">
                            </div>
                            <div style="min-width:auto"><button class="nx-btn nx-btn-primary" type="submit" onclick="return confirm('Save this teacher payment?')"><i class="bi bi-cash"></i> Pay</button></div>
                        </form>
                    </div>
                @endif


                @if ($payouts->count())
                    <div class="ui-table-responsive tw:bs-border-t!">
                        <table class="ui-table premium-table tw:mb-0!">
                            <thead><tr><th>Date</th><th>Teacher</th><th>Method</th><th class="tw:text-right!">Amount</th><th>By</th><th>Status</th><th class="tw:text-right!"></th></tr></thead>
                            <tbody>
                                @foreach ($payouts as $payout)
                                    <tr class="{{ $payout->status === 'cancelled' ? 'row-muted' : '' }}">
                                        <td>{{ $payout->paid_on->format('d M Y') }}</td>
                                        <td>{{ $payout->teacher->full_name ?? '' }}@if ($payout->note)<small class="cell-sub tw:block!">{{ $payout->note }}</small>@endif</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $payout->payment_method)) }}</td>
                                        <td class="tw:text-right! money">{{ number_format($payout->amount, 2) }}</td>
                                        <td><small>{{ $payout->paidBy->name ?? '' }}</small></td>
                                        <td>@include('courses.partials.status-badge', ['status' => $payout->status])</td>
                                        <td class="tw:text-right!">
                                            @if ($canManage && $payout->status === 'paid')
                                                <form method="POST" action="{{ route('courses.payouts.cancel', [$course, $payout]) }}" onsubmit="return confirm('Cancel this teacher payment?')">
                                                    @csrf @method('PATCH')
                                                    <button class="action-btn action-btn-danger" title="Cancel" aria-label="Cancel teacher payment"><i class="bi bi-x-circle-fill"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- OWNER SHARE (Super Admin) --}}
            @if ($canPayOwner)
            <div class="ui-tab-pane ui-fade" id="owner" role="tabpanel">
                <div class="tw:p-4!">
                    <p class="cell-sub tw:mb-4!">
                        The institute's {{ rtrim(rtrim(number_format($course->institutePercentage(), 2), '0'), '.') }}% of every course payment.
                        Save here when it is handed to the owner.
                    </p>
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:mb-1!">
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]"><div class="nx-card tw:p-4!"><small class="cell-sub">Institute share earned</small><div class="tw:text-[length:1.25rem]! tw:font-bold! money">{{ number_format($institute['earned'], 2) }}</div></div></div>
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]"><div class="nx-card tw:p-4!"><small class="cell-sub">Given to the owner</small><div class="tw:text-[length:1.25rem]! tw:font-bold! money">{{ number_format($institute['paid'], 2) }}</div></div></div>
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]"><div class="nx-card tw:p-4!"><small class="cell-sub">Balance (not given yet)</small><div class="tw:text-[length:1.25rem]! tw:font-bold! money {{ $institute['balance'] > 0 ? 'tw:text-(color:--nx-amber)!' : '' }}">{{ number_format($institute['balance'], 2) }}</div></div></div>
                    </div>
                </div>

                @if ($institute['balance'] > 0)
                    <div class="tw:p-4! tw:bs-border-t!">
                        <form method="POST" action="{{ route('courses.owner-payouts.store', $course) }}" class="inline-form">
                            @csrf
                            <div>
                                <label class="nx-label">Pay owner</label>
                                <div class="cell-sub tw:pt-2!">Institute balance {{ number_format($institute['balance'], 2) }}</div>
                            </div>
                            <div>
                                <label class="nx-label" for="owner_amount">Amount</label>
                                <input id="owner_amount" type="number" step="0.01" min="1" max="{{ $institute['balance'] }}" name="amount"
                                    value="{{ $institute['balance'] }}" class="ui-form-control nx-input" required>
                            </div>
                            <div>
                                <label class="nx-label" for="owner_method">Method</label>
                                <select id="owner_method" name="payment_method" class="ui-form-select nx-input">
                                    @foreach (\App\Models\CoursePayment::METHODS as $method)
                                        <option value="{{ $method }}">{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="tw:grow!">
                                <label class="nx-label" for="owner_note">Note</label>
                                <input id="owner_note" name="note" class="ui-form-control nx-input" maxlength="500">
                            </div>
                            <div style="min-width:auto"><button class="nx-btn nx-btn-primary" type="submit" onclick="return confirm('Save: institute share given to the owner?')"><i class="bi bi-building-check"></i> Paid to owner</button></div>
                        </form>
                    </div>
                @endif

                @if ($institute['balance'] <= 0)
                    <div class="tw:p-4! tw:bs-border-t! cell-sub">Nothing to give to the owner now.</div>
                @endif

                @if ($ownerPayouts->count())
                    <div class="ui-table-responsive tw:bs-border-t!">
                        <table class="ui-table premium-table tw:mb-0!">
                            <thead><tr><th>Date</th><th>Paid to owner</th><th>Method</th><th class="tw:text-right!">Amount</th><th>By</th><th>Status</th><th class="tw:text-right!"></th></tr></thead>
                            <tbody>
                                @foreach ($ownerPayouts as $ownerPayout)
                                    <tr class="{{ $ownerPayout->status === 'cancelled' ? 'row-muted' : '' }}">
                                        <td>{{ $ownerPayout->paid_on->format('d M Y') }}</td>
                                        <td>Institute share @if ($ownerPayout->note)<small class="cell-sub tw:block!">{{ $ownerPayout->note }}</small>@endif</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $ownerPayout->payment_method)) }}</td>
                                        <td class="tw:text-right! money">{{ number_format($ownerPayout->amount, 2) }}</td>
                                        <td><small>{{ $ownerPayout->paidBy->name ?? '' }}</small></td>
                                        <td>@include('courses.partials.status-badge', ['status' => $ownerPayout->status])</td>
                                        <td class="tw:text-right!">
                                            @if ($ownerPayout->status === 'paid')
                                                <form method="POST" action="{{ route('courses.owner-payouts.cancel', [$course, $ownerPayout]) }}" onsubmit="return confirm('Cancel this payment to the owner?')">
                                                    @csrf @method('PATCH')
                                                    <button class="action-btn action-btn-danger" title="Cancel" aria-label="Cancel payment to owner"><i class="bi bi-x-circle-fill"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>
@endsection

@include('courses.partials.styles')

@push('scripts')
<script>
    // Open the tab named in the URL (#payments, #teachers ...)
    document.addEventListener('DOMContentLoaded', function () {
        const hash = window.location.hash;
        const tab = hash && document.querySelector('.nx-tabs [data-nx-target="' + hash + '"]');
        if (tab) NxUi.showTab(tab);
        document.querySelectorAll('.nx-tabs [data-nx-toggle="tab"]').forEach((btn) => {
            btn.addEventListener('shown.nx.tab', () => history.replaceState(null, '', btn.dataset.nxTarget));
        });
    });
</script>
@endpush
