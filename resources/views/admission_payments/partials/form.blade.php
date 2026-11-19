@php
    $admissionPayment = $admissionPayment ?? null;

    $selectedStudentId   = old('student_id', $admissionPayment?->student_id ?? '');
    $selectedAdmissionId = old('admission_id', $admissionPayment?->admission_id ?? '');
    $selectedStatus      = old('status', $admissionPayment?->status ?? 'paid');
    $selectedMethod      = old('payment_method', $admissionPayment?->payment_method ?? 'cash');
@endphp

<div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">

    {{-- ===================================================== --}}
    {{-- CREATE ONLY --}}
    {{-- ===================================================== --}}
    @if (!isset($admissionPayment))

        {{-- STUDENT --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
            <label class="nx-label">
                Student <span style="color: var(--nx-red);">*</span>
            </label>

            <select name="student_id"
                class="ui-form-select nx-input @error('student_id') ui-is-invalid @enderror"
                required>

                <option value="">Select Student</option>

                @foreach ($students as $student)
                    <option value="{{ $student->id }}"
                        {{ (string) $selectedStudentId === (string) $student->id ? 'selected' : '' }}>
                        {{ $student->custom_id }} - {{ $student->initial_name }}
                    </option>
                @endforeach
            </select>

            @error('student_id')
                <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
            @enderror
        </div>

        {{-- ADMISSION --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
            <label class="nx-label">
                Admission <span style="color: var(--nx-red);">*</span>
            </label>

            <select name="admission_id" id="admissionSelect"
                class="ui-form-select nx-input @error('admission_id') ui-is-invalid @enderror"
                required>

                <option value="">Select Admission</option>

                @foreach ($admissions as $admission)
                    <option value="{{ $admission->id }}"
                        data-amount="{{ $admission->amount }}"
                        {{ (string) $selectedAdmissionId === (string) $admission->id ? 'selected' : '' }}>
                        {{ $admission->name }} - Rs. {{ number_format($admission->amount, 2) }}
                    </option>
                @endforeach
            </select>

            @error('admission_id')
                <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
            @enderror
        </div>

    @else

        {{-- STUDENT READONLY --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
            <label class="nx-label">Student</label>
            <input type="text"
                class="ui-form-control nx-input readonly-box"
                value="{{ $admissionPayment->student->custom_id ?? '-' }} - {{ $admissionPayment->student->initial_name ?? '-' }}"
                readonly>
        </div>

        {{-- ADMISSION READONLY --}}
        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
            <label class="nx-label">Admission</label>
            <input type="text"
                class="ui-form-control nx-input readonly-box"
                value="{{ $admissionPayment->admission->name ?? '-' }}"
                readonly>
        </div>

    @endif

    {{-- ===================================================== --}}
    {{-- AMOUNT --}}
    {{-- ===================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
        <label class="nx-label">
            Amount <span style="color: var(--nx-red);">*</span>
        </label>

        <input type="number" step="0.01" name="amount" id="amountInput"
            value="{{ old('amount', $admissionPayment?->amount ?? '') }}"
            class="ui-form-control nx-input @error('amount') ui-is-invalid @enderror"
            required>

        @error('amount')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    {{-- ===================================================== --}}
    {{-- PAYMENT METHOD --}}
    {{-- ===================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
        <label class="nx-label">
            Payment Method <span style="color: var(--nx-red);">*</span>
        </label>

        <select name="payment_method"
            class="ui-form-select nx-input @error('payment_method') ui-is-invalid @enderror"
            required>

            <option value="cash"          {{ ($selectedMethod === 'cash') ? 'selected' : '' }}>Cash</option>
            <option value="card"          {{ ($selectedMethod === 'card') ? 'selected' : '' }}>Card</option>
            <option value="bank_transfer" {{ ($selectedMethod === 'bank_transfer') ? 'selected' : '' }}>Bank Transfer</option>
            <option value="online"        {{ ($selectedMethod === 'online') ? 'selected' : '' }}>Online</option>
            <option value="other"         {{ ($selectedMethod === 'other') ? 'selected' : '' }}>Other</option>
        </select>

        @error('payment_method')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    {{-- ===================================================== --}}
    {{-- STATUS --}}
    {{-- ===================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
        <label class="nx-label">
            Status <span style="color: var(--nx-red);">*</span>
        </label>

        <select name="status"
            class="ui-form-select nx-input @error('status') ui-is-invalid @enderror"
            required>

            <option value="pending"   {{ ($selectedStatus === 'pending') ? 'selected' : '' }}>Pending</option>
            <option value="paid"      {{ ($selectedStatus === 'paid') ? 'selected' : '' }}>Paid</option>
            <option value="cancelled" {{ ($selectedStatus === 'cancelled') ? 'selected' : '' }}>Cancelled</option>
            <option value="refunded"  {{ ($selectedStatus === 'refunded') ? 'selected' : '' }}>Refunded</option>
        </select>

        @error('status')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

    {{-- ===================================================== --}}
    {{-- NOTE --}}
    {{-- ===================================================== --}}
    <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%]">
        <label class="nx-label">Note</label>

        <textarea name="note" rows="4"
            class="ui-form-control nx-input @error('note') ui-is-invalid @enderror">{{ old('note', $admissionPayment?->note ?? '') }}</textarea>

        @error('note')
            <div class="ui-invalid-feedback tw:block!">{{ $message }}</div>
        @enderror
    </div>

</div>


@push('styles')
<style>
    /* ============================================================
       Admission Payment — Shared form partial (page-scoped)
       ============================================================ */

    .dashboard-page .readonly-box {
        background: #f4f6fa;
        color: var(--nx-muted);
        cursor: not-allowed;
    }

    .dashboard-page textarea.nx-input {
        min-height: auto;
        padding-top: .55rem;
    }
</style>
@endpush


@if (!isset($admissionPayment))

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const admissionSelect = document.getElementById('admissionSelect');
            const amountInput     = document.getElementById('amountInput');

            if (!admissionSelect || !amountInput) return;

            function updateAmount() {
                const selectedOption = admissionSelect.options[admissionSelect.selectedIndex];
                const amount = selectedOption.getAttribute('data-amount');
                amountInput.value = amount || '';
            }

            admissionSelect.addEventListener('change', updateAmount);
            updateAmount();
        });
    </script>
    @endpush

@endif