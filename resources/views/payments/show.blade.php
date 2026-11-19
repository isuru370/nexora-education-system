@extends('layouts.app')

@section('title', 'Payment ' . ($payment->receipt_number ?? '#' . $payment->id))
@section('page-title', 'Payment Details')

@include('reports.partials.styles')

@section('content')
    @php
        $enrollment = $payment->enrollment;
        $class = optional($enrollment)->studentClass;
        $student = $payment->student;
        $snapshot = $payment->splitSnapshot;
        $option = optional($enrollment)->classCategoryFeeOption;
    @endphp

    <div class="report-page">

        {{-- HERO --}}
        <div class="report-hero">
            <div class="tw:flex! tw:justify-between! tw:items-start! tw:flex-wrap! tw:gap-4!">
                <div>
                    <h3 class="tw:font-bold! tw:mb-1!">
                        Receipt {{ $payment->receipt_number ?? '#' . $payment->id }}
                        @if ($payment->trashed())
                            <span class="ui-badge tw:bg-bs-danger! tw:align-middle! tw:text-[length:1rem]!">Deleted</span>
                        @endif
                    </h3>
                    <div class="sub">
                        Rs. {{ number_format((float) $payment->amount, 2) }}
                        &nbsp;•&nbsp; {{ optional($payment->paid_at)->format('d M Y h:i A') ?? 'Not paid' }}
                    </div>
                </div>
                <div class="tw:flex! tw:gap-2! tw:flex-wrap!">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('payments.index') }}"
                        class="ui-btn ui-btn-light btn-export">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    @if (!$payment->trashed() && $payment->status === 'completed')
                        <a href="{{ route('payments.receipt', $payment->id) }}" target="_blank" rel="noopener" class="ui-btn ui-btn-light btn-export">
                            <i class="bi bi-printer tw:text-bs-primary!"></i> Print receipt
                        </a>
                    @endif
                    @if ($row['can_delete'])
                        <button type="button" class="ui-btn ui-btn-light btn-export" data-nx-toggle="modal" data-nx-target="#deleteModal">
                            <i class="bi bi-trash tw:text-bs-danger!"></i> Delete
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="ui-alert ui-alert-success" style="border-radius: 16px;">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="ui-alert ui-alert-danger" style="border-radius: 16px;">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="ui-alert ui-alert-danger" style="border-radius: 16px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($payment->trashed())
            <div class="ui-alert ui-alert-warning" style="border-radius: 16px;">
                <i class="bi bi-exclamation-triangle"></i>
                Deleted on {{ $payment->deleted_at->format('d M Y h:i A') }}
                by {{ optional($payment->deletedBy)->name ?? 'unknown' }}.
                <div class="tw:mt-1!"><strong>Reason:</strong> {{ $payment->delete_reason ?? '-' }}</div>
            </div>
        @endif

        <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="report-card tw:h-full!">
                    <h6 class="tw:mb-4!"><i class="bi bi-person"></i> Student</h6>
                    <table class="tw:w-full! mini-table">
                        <tr><td>Student ID</td><td class="tw:text-right!">{{ $row['student_code'] ?? '-' }}</td></tr>
                        <tr><td>Name</td><td class="tw:text-right!">{{ optional($student)->initial_name ?? '-' }}</td></tr>
                        <tr><td>Guardian mobile</td><td class="tw:text-right!">{{ optional($student)->guardian_mobile ?? '-' }}</td></tr>
                        <tr><td>Branch</td><td class="tw:text-right!">{{ optional($payment->branch)->name ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="report-card tw:h-full!">
                    <h6 class="tw:mb-4!"><i class="bi bi-easel"></i> Class</h6>
                    <table class="tw:w-full! mini-table">
                        <tr><td>Class</td><td class="tw:text-right!">{{ optional($class)->class_name ?? '-' }}</td></tr>
                        <tr><td>Subject / Grade</td><td class="tw:text-right!">{{ optional(optional($class)->subject)->subject_name ?? '-' }} / {{ optional(optional($class)->grade)->grade_name ?? '-' }}</td></tr>
                        <tr><td>Teacher</td><td class="tw:text-right!">{{ optional(optional($class)->teacher)->full_name ?? '-' }}</td></tr>
                        <tr><td>Category</td><td class="tw:text-right!">{{ $row['category_name'] ?? '-' }}</td></tr>
                        <tr><td>Fee option</td><td class="tw:text-right!">{{ $option ? $option->label . ' (Rs. ' . number_format((float) $option->fee, 2) . ')' : '-' }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="report-card tw:h-full!">
                    <h6 class="tw:mb-4!"><i class="bi bi-receipt"></i> Payment</h6>
                    <table class="tw:w-full! mini-table">
                        <tr><td>Fee month</td><td class="tw:text-right!">{{ $row['fee_month'] ?? '-' }}</td></tr>
                        <tr><td>Amount</td><td class="tw:text-right! amount tw:font-bold!">Rs. {{ number_format((float) $payment->amount, 2) }}</td></tr>
                        @if ((float) $payment->discount_amount > 0)
                            <tr><td>Discount</td><td class="tw:text-right! amount">Rs. {{ number_format((float) $payment->discount_amount, 2) }}</td></tr>
                        @endif
                        <tr><td>Method</td><td class="tw:text-right!">{{ $label($payment->payment_method) }}</td></tr>
                        @if ($payment->reference_number)
                            <tr><td>Reference</td><td class="tw:text-right!">{{ $payment->reference_number }}</td></tr>
                        @endif
                        <tr><td>Status</td><td class="tw:text-right!">{{ $row['status'] }}</td></tr>
                        <tr><td>Paid at</td><td class="tw:text-right!">{{ optional($payment->paid_at)->format('Y-m-d H:i') ?? '-' }}</td></tr>
                        <tr><td>Collected by</td><td class="tw:text-right!">{{ optional($payment->collectedBy)->name ?? '-' }}</td></tr>
                        <tr><td>Recorded at</td><td class="tw:text-right!">{{ optional($payment->created_at)->format('Y-m-d H:i') }}</td></tr>
                        @if ($payment->note)
                            <tr><td>Note</td><td class="tw:text-right!">{{ $payment->note }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
                <div class="report-card tw:h-full!">
                    <h6 class="tw:mb-4!"><i class="bi bi-pie-chart"></i> Fee split</h6>
                    @if ($snapshot)
                        <table class="tw:w-full! mini-table">
                            <tr>
                                <td>Teacher ({{ rtrim(rtrim(number_format((float) $snapshot->teacher_percentage, 2), '0'), '.') }}%)</td>
                                <td class="tw:text-right! amount">Rs. {{ number_format((float) $snapshot->teacher_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Organizer{{ $snapshot->organizer ? ' - ' . $snapshot->organizer->name : '' }} ({{ rtrim(rtrim(number_format((float) $snapshot->organizer_percentage, 2), '0'), '.') }}%)</td>
                                <td class="tw:text-right! amount">Rs. {{ number_format((float) $snapshot->organizer_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Institute ({{ rtrim(rtrim(number_format((float) $snapshot->institution_percentage, 2), '0'), '.') }}%{{ (float) $snapshot->hall_fee > 0 ? ' + hall fee' : '' }})</td>
                                <td class="tw:text-right! amount">Rs. {{ number_format((float) $snapshot->institution_amount, 2) }}</td>
                            </tr>
                            @if ((float) $snapshot->hall_fee > 0)
                                <tr>
                                    <td class="tw:text-bs-secondary-color!">Hall fee (in institute share)</td>
                                    <td class="tw:text-right! amount tw:text-bs-secondary-color!">Rs. {{ number_format((float) $snapshot->hall_fee, 2) }}</td>
                                </tr>
                            @endif
                        </table>
                    @else
                        <p class="tw:text-bs-secondary-color! tw:mb-0!">No fee split was saved for this payment.</p>
                    @endif
                </div>
            </div>
        </div>

        @if (!$payment->trashed() && !$row['can_delete'] && $deleteUntil)
            <p class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mt-4!">
                <i class="bi bi-info-circle"></i>
                Payments can only be deleted within {{ \App\Services\Web\Payment\PaymentDeleteService::DELETE_WINDOW_DAYS }} days
                (until {{ $deleteUntil->format('d M Y') }}).
            </p>
        @endif
    </div>

    @if ($row['can_delete'])
        <div class="ui-modal ui-fade" id="deleteModal" tabindex="-1" aria-hidden="true">
            <div class="ui-modal-dialog">
                <form method="POST" action="{{ route('payments.destroy', $payment->id) }}" class="ui-modal-content"
                    onsubmit="this.querySelector('[type=submit]').disabled = true;">
                    @csrf
                    @method('DELETE')
                    <div class="ui-modal-header">
                        <h5 class="ui-modal-title tw:text-bs-danger!">Delete payment {{ $payment->receipt_number }}</h5>
                        <button type="button" class="ui-btn-close" data-nx-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="ui-modal-body">
                        <p class="tw:mb-2!">
                            Rs. {{ number_format((float) $payment->amount, 2) }} for {{ $row['fee_month'] }} will be removed from
                            receipts and reports. The same class and month can then be paid again.
                        </p>
                        <label for="deleteReason" class="report-label">Reason</label>
                        <textarea name="reason" id="deleteReason" class="ui-form-control" rows="3" minlength="5" maxlength="500"
                            required placeholder="Example: wrong student selected">{{ old('reason') }}</textarea>
                    </div>
                    <div class="ui-modal-footer">
                        <button type="button" class="ui-btn ui-btn-light" data-nx-dismiss="modal">Cancel</button>
                        <button type="submit" class="ui-btn ui-btn-danger"><i class="bi bi-trash"></i> Delete</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
