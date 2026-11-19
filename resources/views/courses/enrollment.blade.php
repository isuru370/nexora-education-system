@extends('layouts.app')

@section('title', ($enrollment->student->initial_name ?? 'Student') . ' - ' . $course->name)
@section('page-title', 'Course Student')

@section('content')
@php
    $role = strtoupper((string) optional(auth()->user()->userType)->code);
    $canManage = in_array($role, ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], true);
    $canChangeFee = in_array($role, ['SUPER_ADMIN', 'ADMIN'], true);
    $student = $enrollment->student;
    $next = $fees['next_installment'];
@endphp
<div class="dashboard-page">

    <div class="hero-section tw:mb-6!">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">{{ $course->name }}</span>
                <h1 class="hero-title">{{ $student->initial_name ?? '—' }}</h1>
                <p class="hero-subtitle">{{ $student->custom_id }} · {{ $enrollment->payment_plan === 'full' ? 'Pays at once' : $course->installment_count . ' installments' }} · enrolled {{ $enrollment->enrolled_at->format('d M Y') }}</p>
                @if (!$enrollment->isActive())
                    <div class="hero-stats-mini"><div class="mini-stat">@include('courses.partials.status-badge', ['status' => 'cancelled'])</div></div>
                @endif
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('courses.show', $course) }}" class="nx-btn nx-btn-light"><i class="bi bi-arrow-left"></i> Course</a>
                    @if ($canManage && $enrollment->isActive() && $fees['paid'] <= 0)
                        <form method="POST" action="{{ route('courses.enrollments.cancel', [$course, $enrollment]) }}" onsubmit="return confirm('Remove this student from the course?')">
                            @csrf @method('PATCH')
                            <button class="nx-btn nx-btn-light" type="submit"><i class="bi bi-person-x"></i> Cancel enrollment</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('courses.partials.alerts')

    <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3 tw:mb-6!">
        {{-- FEES + PAY --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[41.66666667%]">
            <div class="nx-card tw:h-full!">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-wallet2 tw:text-(color:--nx-blue)!"></i> Fees</h5></div>
                <div class="tw:p-4!">
                    <dl class="kv tw:mb-4!">
                        @if ($fees['special_fee'])
                            <dt>Course fee</dt><dd class="money tw:text-(color:--nx-muted)!"><s>Rs. {{ number_format($fees['standard_fee'], 2) }}</s></dd>
                            <dt>Special fee</dt><dd class="money">Rs. {{ number_format($fees['total_fee'], 2) }} <span class="nx-badge nx-badge-amber">{{ $fees['fee_reason'] }}</span></dd>
                        @else
                            <dt>Course fee</dt><dd class="money">Rs. {{ number_format($fees['total_fee'], 2) }}</dd>
                        @endif
                        <dt>Paid</dt><dd class="money tw:text-(color:--nx-green)!">Rs. {{ number_format($fees['paid'], 2) }}</dd>
                        <dt>Balance</dt><dd class="money">Rs. {{ number_format($fees['balance'], 2) }}</dd>
                        @if ($fees['overdue'] > 0)
                            <dt>Overdue</dt><dd class="money tw:text-(color:--nx-red)!">Rs. {{ number_format($fees['overdue'], 2) }}</dd>
                        @endif
                        <dt>Attendance</dt><dd>{{ $attendance['attended'] }} / {{ $attendance['held'] }} ({{ $attendance['percentage'] }}%)</dd>
                    </dl>

                    @if ($enrollment->isActive() && $fees['balance'] > 0)
                        <form method="POST" action="{{ route('courses.payments.store', [$course, $enrollment]) }}" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                            @csrf
                            <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1">
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">
                                    <label class="nx-label" for="amount">Amount *</label>
                                    <input id="amount" type="number" step="0.01" min="1" max="{{ $fees['balance'] }}" name="amount" class="ui-form-control nx-input" required
                                        value="{{ old('amount', $next ? $next['remaining'] : $fees['balance']) }}">
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">
                                    <label class="nx-label" for="payment_method">Method</label>
                                    <select id="payment_method" name="payment_method" class="ui-form-select nx-input">
                                        @foreach ($methods as $method)
                                            <option value="{{ $method }}" {{ old('payment_method', 'cash') === $method ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[100%]">
                                    <label class="nx-label" for="note">Note</label>
                                    <input id="note" name="note" class="ui-form-control nx-input" maxlength="500" value="{{ old('note') }}">
                                </div>
                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:flex-[0_0_auto] tw:w-[100%] tw:flex! tw:flex-wrap! tw:gap-2! tw:mt-2!">
                                    @if ($next)
                                        <button type="button" class="nx-btn nx-btn-light" onclick="document.getElementById('amount').value='{{ $next['remaining'] }}'">Installment {{ $next['installment_no'] }}: {{ number_format($next['remaining'], 2) }}</button>
                                    @endif
                                    <button type="button" class="nx-btn nx-btn-light" onclick="document.getElementById('amount').value='{{ $fees['balance'] }}'">Full balance</button>
                                    <button type="submit" class="nx-btn nx-btn-primary tw:ml-auto!"><i class="bi bi-cash-coin"></i> Take payment</button>
                                </div>
                            </div>
                        </form>
                    @elseif ($fees['balance'] <= 0)
                        <div class="nx-alert nx-alert-green tw:mb-0!"><i class="bi bi-check-circle-fill"></i><span>{{ $fees['total_fee'] > 0 ? 'Fully paid.' : 'Free - no payment.' }}</span></div>
                    @endif

                    @if ($canChangeFee && $enrollment->isActive())
                        <details class="special-fee tw:mt-4! tw:pt-4! tw:bs-border-t!" {{ $errors->has('fee') || $errors->has('fee_reason') ? 'open' : '' }}>
                            <summary>Change fee</summary>
                            <form method="POST" action="{{ route('courses.enrollments.fee', [$course, $enrollment]) }}" class="tw:mt-2!" onsubmit="return confirm('Change this student\'s fee to Rs. ' + this.fee.value + '?')">
                                @csrf @method('PATCH')
                                <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1">
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[41.66666667%]">
                                        <label class="nx-label" for="new_fee">New fee</label>
                                        <input id="new_fee" type="number" step="0.01" min="{{ $fees['paid'] }}" name="fee" class="ui-form-control nx-input" value="{{ old('fee', $fees['total_fee']) }}" required>
                                    </div>
                                    <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[58.33333333%]">
                                        <label class="nx-label" for="new_fee_reason">Reason</label>
                                        <input id="new_fee_reason" name="fee_reason" class="ui-form-control nx-input" maxlength="255" value="{{ old('fee_reason', $fees['fee_reason']) }}" required placeholder="Scholarship from December">
                                    </div>
                                </div>
                                <small class="cell-sub tw:block! tw:mt-1!">Paid Rs. {{ number_format($fees['paid'], 2) }} stays. The rest is spread over the installments not paid yet.</small>
                                <button class="nx-btn nx-btn-primary tw:mt-2!" type="submit">Save new fee</button>
                            </form>
                        </details>
                    @endif
                </div>
            </div>
        </div>

        {{-- INSTALLMENTS --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[58.33333333%]">
            <div class="nx-card tw:h-full!">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-calendar3 tw:text-(color:--nx-blue)!"></i> {{ $enrollment->payment_plan === 'full' ? 'Payment' : 'Installments' }}</h5></div>
                <div class="ui-table-responsive">
                    <table class="ui-table premium-table tw:mb-0!">
                        <thead><tr><th>#</th><th>Due</th><th class="tw:text-right!">Amount</th><th class="tw:text-right!">Paid</th><th class="tw:text-right!">Left</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($fees['installments'] as $row)
                                <tr>
                                    <td>{{ $row['installment_no'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($row['due_date'])->format('d M Y') }}</td>
                                    <td class="tw:text-right! money">{{ number_format($row['amount'], 2) }}</td>
                                    <td class="tw:text-right! money">{{ number_format($row['paid'], 2) }}</td>
                                    <td class="tw:text-right! money">{{ number_format($row['remaining'], 2) }}</td>
                                    <td>@include('courses.partials.status-badge', ['status' => $row['status']])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[66.66666667%]">
            <div class="nx-card">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-receipt tw:text-(color:--nx-blue)!"></i> Payments</h5></div>
                <div class="ui-table-responsive">
                    <table class="ui-table premium-table tw:mb-0!">
                        <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th class="tw:text-right!">Amount</th><th>Status</th><th class="tw:text-right!"></th></tr></thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                @include('courses.partials.payment-row', ['payment' => $payment, 'showStudent' => false])
                            @empty
                                <tr><td colspan="6" class="empty-table">No payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%]">
            <div class="nx-card">
                <div class="nx-card-header"><h5 class="nx-card-title"><i class="bi bi-person-check tw:text-(color:--nx-blue)!"></i> Attended</h5></div>
                <ul class="ui-list-group ui-list-group-flush">
                    @forelse ($attended as $row)
                        <li class="ui-list-group-item tw:flex! tw:justify-between!"><span>{{ $row->session->session_date->format('D, d M Y') }}</span><small class="cell-sub">{{ $row->attended_at->format('h:i A') }}</small></li>
                    @empty
                        <li class="ui-list-group-item cell-sub">No attendance yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@include('courses.partials.styles')
