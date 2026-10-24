@php
    $canCancel = in_array(strtoupper((string) optional(auth()->user()->userType)->code), ['SUPER_ADMIN', 'ADMIN', 'BRANCH_ADMIN'], true);
@endphp
<tr class="{{ $payment->isCompleted() ? '' : 'row-muted' }}">
    <td><span class="cell-title">{{ $payment->receipt_number }}</span></td>
    <td>{{ $payment->paid_at->format('d M Y') }}<small class="cell-sub d-block">{{ $payment->paid_at->format('h:i A') }}</small></td>
    @if ($showStudent ?? true)
        <td>{{ $payment->student->initial_name ?? '' }}<small class="cell-sub d-block">{{ $payment->student->custom_id ?? '' }}</small></td>
    @endif
    @if ($showCourse ?? false)
        <td>{{ $payment->course->name ?? '' }}</td>
    @endif
    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}<small class="cell-sub d-block">{{ $payment->takenBy->name ?? '' }}</small></td>
    <td class="text-end money"><strong>{{ number_format($payment->amount, 2) }}</strong></td>
    <td>
        @include('courses.partials.status-badge', ['status' => $payment->status])
        @if ($payment->cancel_reason)<small class="cell-sub d-block">{{ $payment->cancel_reason }}</small>@endif
    </td>
    <td class="text-end">
        <div class="action-buttons">
            <a href="{{ route('course-payments.receipt', $payment) }}" class="action-btn" title="Receipt" aria-label="Receipt"><i class="bi bi-receipt"></i></a>
            @if ($canCancel && $payment->isCompleted())
                <form method="POST" action="{{ route('course-payments.cancel', $payment) }}" onsubmit="const r = prompt('Why is payment {{ $payment->receipt_number }} cancelled?'); if (!r) return false; this.cancel_reason.value = r; return true;">
                    @csrf @method('PATCH')
                    <input type="hidden" name="cancel_reason" value="">
                    <button class="action-btn action-btn-danger" title="Cancel payment" aria-label="Cancel payment"><i class="bi bi-x-circle-fill"></i></button>
                </form>
            @endif
        </div>
    </td>
</tr>
