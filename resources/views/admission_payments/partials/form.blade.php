@php
    $admissionPayment = $admissionPayment ?? null;

    $selectedStudentId   = old('student_id', $admissionPayment?->student_id ?? '');
    $selectedAdmissionId = old('admission_id', $admissionPayment?->admission_id ?? '');
    $selectedStatus      = old('status', $admissionPayment?->status ?? 'paid');
    $selectedMethod      = old('payment_method', $admissionPayment?->payment_method ?? 'cash');
@endphp

<div class="row g-3">

    {{-- ===================================================== --}}
    {{-- CREATE ONLY --}}
    {{-- ===================================================== --}}
    @if (!isset($admissionPayment))

        {{-- STUDENT --}}
        <div class="col-lg-6">
            <label class="nx-label">
                Student <span style="color: var(--nx-red);">*</span>
            </label>

            <select name="student_id"
                class="form-select nx-input @error('student_id') is-invalid @enderror"
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
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        {{-- ADMISSION --}}
        <div class="col-lg-6">
            <label class="nx-label">
                Admission <span style="color: var(--nx-red);">*</span>
            </label>

            <select name="admission_id" id="admissionSelect"
                class="form-select nx-input @error('admission_id') is-invalid @enderror"
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
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

    @else

        {{-- STUDENT READONLY --}}
        <div class="col-lg-6">
            <label class="nx-label">Student</label>
            <input type="text"
                class="form-control nx-input readonly-box"
                value="{{ $admissionPayment->student->custom_id ?? '-' }} - {{ $admissionPayment->student->initial_name ?? '-' }}"
                readonly>
        </div>

        {{-- ADMISSION READONLY --}}
        <div class="col-lg-6">
            <label class="nx-label">Admission</label>
            <input type="text"
                class="form-control nx-input readonly-box"
                value="{{ $admissionPayment->admission->name ?? '-' }}"
                readonly>
        </div>

    @endif

    {{-- ===================================================== --}}
    {{-- AMOUNT --}}
    {{-- ===================================================== --}}
    <div class="col-lg-6">
        <label class="nx-label">
            Amount <span style="color: var(--nx-red);">*</span>
        </label>

        <input type="number" step="0.01" name="amount" id="amountInput"
            value="{{ old('amount', $admissionPayment?->amount ?? '') }}"
            class="form-control nx-input @error('amount') is-invalid @enderror"
            required>

        @error('amount')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- ===================================================== --}}
    {{-- PAYMENT METHOD --}}
    {{-- ===================================================== --}}
    <div class="col-lg-6">
        <label class="nx-label">
            Payment Method <span style="color: var(--nx-red);">*</span>
        </label>

        <select name="payment_method"
            class="form-select nx-input @error('payment_method') is-invalid @enderror"
            required>

            <option value="cash"          @selected($selectedMethod === 'cash')>Cash</option>
            <option value="card"          @selected($selectedMethod === 'card')>Card</option>
            <option value="bank_transfer" @selected($selectedMethod === 'bank_transfer')>Bank Transfer</option>
            <option value="online"        @selected($selectedMethod === 'online')>Online</option>
            <option value="other"         @selected($selectedMethod === 'other')>Other</option>
        </select>

        @error('payment_method')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- ===================================================== --}}
    {{-- STATUS --}}
    {{-- ===================================================== --}}
    <div class="col-lg-6">
        <label class="nx-label">
            Status <span style="color: var(--nx-red);">*</span>
        </label>

        <select name="status"
            class="form-select nx-input @error('status') is-invalid @enderror"
            required>

            <option value="pending"   @selected($selectedStatus === 'pending')>Pending</option>
            <option value="paid"      @selected($selectedStatus === 'paid')>Paid</option>
            <option value="cancelled" @selected($selectedStatus === 'cancelled')>Cancelled</option>
            <option value="refunded"  @selected($selectedStatus === 'refunded')>Refunded</option>
        </select>

        @error('status')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- ===================================================== --}}
    {{-- NOTE --}}
    {{-- ===================================================== --}}
    <div class="col-12">
        <label class="nx-label">Note</label>

        <textarea name="note" rows="4"
            class="form-control nx-input @error('note') is-invalid @enderror">{{ old('note', $admissionPayment?->note ?? '') }}</textarea>

        @error('note')
            <div class="invalid-feedback d-block">{{ $message }}</div>
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