@extends('layouts.app')

@section('title', 'Offline Sync')
@section('page-title', 'Offline Sync')

@section('content')
    @include('partials.report-page-styles')

    @php
        $money = fn ($v) => number_format((float) $v, 2);
        $statusLabel = ['synced' => 'Saved', 'duplicate' => 'Already saved', 'conflict' => 'Conflict', 'failed' => 'Refused'];
        $chip = ['synced' => 'paid', 'duplicate' => 'freecard', 'conflict' => 'partial', 'failed' => 'unpaid'];
    @endphp

    <div class="rp-page tw:space-y-6">
        <div class="rp-card">
            <div class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-4">
                <div class="tw:flex tw:items-center tw:gap-4">
                    <div class="rp-avatar"><i class="bi bi-cloud-arrow-up"></i></div>
                    <div>
                        <h3 class="rp-title">Offline Sync</h3>
                        <p class="rp-sub">Attendance and payments the staff app recorded without internet</p>
                    </div>
                </div>
                <div class="tw:flex tw:gap-2">
                    <a href="{{ route('offline-sync.index', ['show' => 'review']) }}" class="rp-btn {{ $show === 'review' ? 'rp-btn-primary' : '' }}">
                        To check @if ($waiting) <span class="rp-chip rp-chip-unpaid">{{ $waiting }}</span> @endif
                    </a>
                    <a href="{{ route('offline-sync.index', ['show' => 'all']) }}" class="rp-btn {{ $show === 'all' ? 'rp-btn-primary' : '' }}">All</a>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="rp-note">{{ session('success') }}</div>
        @endif

        <div class="rp-note">
            <strong>Conflict</strong>: saved meanwhile somewhere else - most often the same month was paid on two phones (or on a phone and the web).
            The phone's payment was NOT saved; if the cashier took the money, give it back or use it for another month.
            <strong>Refused</strong>: a rule did not allow it (e.g. less than the fee, phone time wrong) - enter it on the web if it is right.
        </div>

        <div class="rp-card">
            <div class="rp-table-wrap">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Type</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Month</th>
                            <th class="rp-num">Amount</th>
                            <th>Receipt</th>
                            <th>By / phone</th>
                            <th>Result</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $p = $item->payload;
                                $student = $students->get($p['student_id'] ?? 0);
                                $enrollment = $enrollments->get($p['enrollment_id'] ?? 0);
                            @endphp
                            <tr>
                                <td class="tw:whitespace-nowrap">{{ $item->happened_at->format('d M Y, h:i A') }}</td>
                                <td>{{ ucfirst($item->type) }}</td>
                                <td>
                                    <div class="rp-strong tw:whitespace-nowrap">{{ $student ? ($student->initial_name ?: $student->full_name) : '-' }}</div>
                                    <div class="rp-muted tw:text-xs">{{ optional($student)->custom_id }}</div>
                                </td>
                                <td>{{ optional(optional($enrollment)->studentClass)->class_name ?? '-' }}</td>
                                <td>{{ isset($p['payment_month']) ? \Carbon\Carbon::parse($p['payment_month'] . '-01')->format('M Y') : '-' }}</td>
                                <td class="rp-num">{{ isset($p['amount']) ? $money($p['amount']) : '-' }}</td>
                                <td class="tw:whitespace-nowrap">{{ $p['receipt_number'] ?? optional($item->payment)->receipt_number ?? '-' }}</td>
                                <td>
                                    {{ optional($item->user)->name ?? '-' }}
                                    <div class="rp-muted tw:text-xs">{{ $item->device_id }}</div>
                                </td>
                                <td>
                                    <span class="rp-chip rp-chip-{{ $chip[$item->status] ?? 'unpaid' }}">{{ $statusLabel[$item->status] ?? $item->status }}</span>
                                    @if ($item->message)
                                        <div class="rp-muted tw:text-xs tw:mt-1">{{ $item->message }}</div>
                                    @endif
                                </td>
                                <td class="tw:whitespace-nowrap">
                                    @if (in_array($item->status, \App\Models\OfflineSyncItem::NEEDS_REVIEW, true))
                                        @if ($item->reviewed_at)
                                            <span class="rp-muted tw:text-xs">Checked {{ $item->reviewed_at->format('d M') }}</span>
                                        @else
                                            <form method="POST" action="{{ route('offline-sync.reviewed', $item) }}">
                                                @csrf
                                                <button type="submit" class="rp-btn rp-btn-sm">Mark checked</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="tw:text-center tw:py-10 rp-muted">{{ $show === 'review' ? 'Nothing to check.' : 'No offline items yet.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="tw:mt-4">{{ $items->links() }}</div>
            @endif
        </div>
    </div>
@endsection
