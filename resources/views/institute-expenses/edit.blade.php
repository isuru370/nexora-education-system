@extends('layouts.app')

@section('title', 'Edit Expense')
@section('page-title', 'Edit Expense')

@push('styles')
<style>

    .expense-edit-page {
        animation: fadeIn .4s ease;
    }

    /* =========================
       TOP ACTION
    ========================= */
    .top-actions {
        margin-bottom: 1.25rem;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        background: #64748b;
        color: #fff;
        border: none;
        border-radius: 12px;
        padding: .65rem 1rem;
        font-weight: 600;
        text-decoration: none;
        transition: all .2s ease;
    }

    .btn-back:hover {
        background: #475569;
        color: #fff;
        transform: translateY(-1px);
    }

    /* =========================
       FORM CARD
    ========================= */
    .form-card {
        background: #fff;
        border-radius: 28px;
        border: 1px solid #eef2f7;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
    }

    .form-header {
        padding: 1.5rem 1.75rem;
        border-bottom: 1px solid #eef2f7;
        background: linear-gradient(
            135deg,
            #f8fafc,
            #fff
        );
    }

    .form-header-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: #dbeafe;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-right: .75rem;
    }

    .form-header h4 {
        color: #0f172a;
    }

    .form-subtitle {
        color: #64748b;
        font-size: .85rem;
    }

    .form-body {
        padding: 1.75rem;
    }

    /* =========================
       INFO CARD
    ========================= */
    .expense-info {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }

    .expense-info-label {
        font-size: .68rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        font-weight: 700;
        margin-bottom: .2rem;
    }

    .expense-info-value {
        font-size: .9rem;
        font-weight: 700;
        color: #0f172a;
    }

    .expense-id {
        color: #2563eb;
        font-family: monospace;
    }

    /* =========================
       LABELS / INPUTS
    ========================= */
    .form-label-custom {
        font-weight: 700;
        color: #475569;
        margin-bottom: .5rem;
        font-size: .85rem;
    }

    .required-star {
        color: #dc2626;
    }

    .form-control-custom {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: .7rem 1rem;
        min-height: 44px;
        transition: all .2s ease;
        background: #fff;
    }

    .form-control-custom:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(
            37,
            99,
            235,
            .1
        );
        outline: none;
    }

    textarea.form-control-custom {
        min-height: 110px;
        resize: vertical;
    }

    .form-control-custom:disabled {
        background: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
    }

    .field-help {
        font-size: .72rem;
        color: #94a3b8;
        margin-top: .35rem;
    }

    /* =========================
       WARNING
    ========================= */
    .warning-alert {
        border-radius: 16px;
        border: 1px solid #fde68a;
        background: #fffbeb;
        color: #92400e;
        padding: 1rem 1.1rem;
        margin-bottom: 1.5rem;
    }

    .warning-alert i {
        color: #f59e0b;
    }

    /* =========================
       CURRENT MONTH
    ========================= */
    .current-month-alert {
        border-radius: 16px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e40af;
        padding: 1rem 1.1rem;
        margin-bottom: 1.5rem;
    }

    .current-month-alert i {
        color: #2563eb;
    }

    /* =========================
       BUTTONS
    ========================= */
    .form-actions {
        margin-top: 1.75rem;
        padding-top: 1.25rem;
        border-top: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
    }

    .btn-update {
        background: linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );
        border: none;
        border-radius: 12px;
        padding: .7rem 1.5rem;
        font-weight: 600;
        color: white;
        transition: all .2s ease;
    }

    .btn-update:hover {
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(
            37,
            99,
            235,
            .25
        );
    }

    .btn-cancel {
        background: #64748b;
        border: none;
        border-radius: 12px;
        padding: .7rem 1.5rem;
        font-weight: 600;
        color: white;
        text-decoration: none;
        transition: all .2s ease;
    }

    .btn-cancel:hover {
        background: #475569;
        color: white;
        transform: translateY(-1px);
    }

    /* =========================
       DISABLED STATE
    ========================= */
    .form-disabled {
        opacity: .72;
    }

    .edit-disabled-message {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        padding: 1rem;
        color: #64748b;
        margin-bottom: 1.5rem;
    }

    /* =========================
       RESPONSIVE
    ========================= */
    @media (max-width: 768px) {

        .form-header {
            padding: 1.25rem;
        }

        .form-body {
            padding: 1.25rem;
        }

        .form-header h4 {
            font-size: 1.1rem;
        }

        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-update,
        .btn-cancel {
            width: 100%;
            text-align: center;
            justify-content: center;
        }

    }

</style>
@endpush


@section('content')

@php

    $paymentDate = Carbon\Carbon::parse(
        $instituteExpense->payment_date
    );

    $isCurrentMonth =
        $paymentDate->month === now()->month
        &&
        $paymentDate->year === now()->year;

@endphp


<div class="expense-edit-page">

    {{-- =========================================================
         BACK BUTTON
    ========================================================== --}}
    <div class="top-actions">

        <a
            href="{{ route('admin.institute-expenses.index') }}"
            class="btn-back"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Expenses
        </a>

    </div>


    {{-- =========================================================
         FORM CARD
    ========================================================== --}}
    <div class="form-card">

        {{-- Header --}}
        <div class="form-header">

            <div class="d-flex align-items-center">

                <div class="form-header-icon">
                    <i class="bi bi-pencil-square"></i>
                </div>

                <div>

                    <h4 class="fw-bold mb-1">
                        Edit Expense
                    </h4>

                    <div class="form-subtitle">
                        Update institute expense transaction details
                    </div>

                </div>

            </div>

        </div>


        {{-- Body --}}
        <div class="form-body">


            {{-- =================================================
                 EXPENSE INFO
            ================================================== --}}
            <div class="expense-info">

                <div class="row g-3">

                    <div class="col-md-3">

                        <div class="expense-info-label">
                            Expense ID
                        </div>

                        <div class="expense-info-value expense-id">
                            EXP-{{ str_pad(
                                $instituteExpense->id,
                                6,
                                '0',
                                STR_PAD_LEFT
                            ) }}
                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="expense-info-label">
                            Original Date
                        </div>

                        <div class="expense-info-value">
                            {{ $paymentDate->format('d M Y') }}
                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="expense-info-label">
                            Current Status
                        </div>

                        <div class="expense-info-value">

                            {{ ucfirst(
                                $instituteExpense->status ?: 'paid'
                            ) }}

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="expense-info-label">
                            Recorded By
                        </div>

                        <div class="expense-info-value">

                            {{
                                optional($instituteExpense->user)->name
                                ?: 'System'
                            }}

                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 CURRENT MONTH MESSAGE
            ================================================== --}}
            @if($isCurrentMonth)

                <div class="current-month-alert">

                    <i class="bi bi-info-circle-fill me-2"></i>

                    <strong>Current Month Record:</strong>

                    This expense can be edited because it belongs
                    to the current month.

                </div>

            @else

                <div class="warning-alert">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    <strong>Editing Restricted:</strong>

                    This expense belongs to a past month.
                    Past month expenses cannot be modified.

                </div>

                <div class="edit-disabled-message">

                    <i class="bi bi-lock-fill me-2"></i>

                    This record is read-only because only
                    current-month expense records can be edited.

                </div>

            @endif


            {{-- =================================================
                 VALIDATION ERRORS
            ================================================== --}}
            @if($errors->any())

                <div class="alert alert-danger rounded-4 mb-4">

                    <div class="fw-bold mb-2">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                        Please fix the following errors:
                    </div>

                    <ul class="mb-0">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- =================================================
                 FORM
            ================================================== --}}
            <form
                action="{{ route(
                    'admin.institute-expenses.update',
                    $instituteExpense->id
                ) }}"
                method="POST"
            >

                @csrf

                @method('PUT')


                <fieldset
                    {{ !$isCurrentMonth ? 'disabled' : '' }}
                >

                    <div class="row g-4">

                        {{-- =====================================
                             AMOUNT
                        ====================================== --}}
                        <div class="col-md-6">

                            <label class="form-label-custom">

                                Amount

                                <span class="required-star">
                                    *
                                </span>

                            </label>

                            <div class="input-group">

                                <span class="input-group-text bg-light border-end-0"
                                      style="border-radius:12px 0 0 12px;">
                                    Rs.
                                </span>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    name="amount"
                                    class="form-control form-control-custom border-start-0"
                                    value="{{ old(
                                        'amount',
                                        $instituteExpense->amount
                                    ) }}"
                                    required
                                >

                            </div>

                            @error('amount')

                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>

                            @enderror

                            <div class="field-help">
                                Enter the total expense amount.
                            </div>

                        </div>


                        {{-- =====================================
                             PAYMENT DATE
                        ====================================== --}}
                        <div class="col-md-6">

                            <label class="form-label-custom">

                                Payment Date

                                <span class="required-star">
                                    *
                                </span>

                            </label>

                            <input
                                type="date"
                                name="payment_date"
                                class="form-control form-control-custom"
                                value="{{ old(
                                    'payment_date',
                                    $paymentDate->format('Y-m-d')
                                ) }}"
                                required
                            >

                            @error('payment_date')

                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>

                            @enderror

                            <div class="field-help">
                                Payment date must remain within the current month.
                            </div>

                        </div>


                        {{-- =====================================
                             REASON CODE
                        ====================================== --}}
                        <div class="col-md-6">

                            <label class="form-label-custom">
                                Reason Code
                            </label>

                            <select
                                name="reason_code"
                                class="form-control form-control-custom"
                            >

                                <option value="">
                                    Select Reason Code
                                </option>

                                @foreach($reasons as $reason)

                                    <option
                                        value="{{ $reason->reason_code }}"
                                        {{
                                            old(
                                                'reason_code',
                                                $instituteExpense->reason_code
                                            )
                                            ==
                                            $reason->reason_code
                                            ? 'selected'
                                            : ''
                                        }}
                                    >

                                        {{ $reason->reason_code }}
                                        -
                                        {{ $reason->name }}

                                    </option>

                                @endforeach

                            </select>

                            @error('reason_code')

                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>


                        {{-- =====================================
                             REASON
                        ====================================== --}}
                        <div class="col-md-6">

                            <label class="form-label-custom">
                                Reason
                            </label>

                            <input
                                type="text"
                                name="reason"
                                class="form-control form-control-custom"
                                value="{{ old(
                                    'reason',
                                    $instituteExpense->reason
                                ) }}"
                                placeholder="Enter reason for expense"
                                maxlength="150"
                            >

                            @error('reason')

                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>

                            @enderror

                            <div class="field-help">
                                Maximum 150 characters.
                            </div>

                        </div>


                        {{-- =====================================
                             NOTE
                        ====================================== --}}
                        <div class="col-12">

                            <label class="form-label-custom">
                                Note
                            </label>

                            <textarea
                                name="note"
                                rows="4"
                                class="form-control form-control-custom"
                                placeholder="Additional notes..."
                            >{{ old(
                                'note',
                                $instituteExpense->note
                            ) }}</textarea>

                            <div class="field-help">
                                Additional information about this expense.
                            </div>

                        </div>

                    </div>

                </fieldset>


                {{-- =================================================
                     ACTIONS
                ================================================== --}}
                <div class="form-actions">

                    @if($isCurrentMonth)

                        <button
                            type="submit"
                            class="btn-update"
                        >
                            <i class="bi bi-check2-circle me-1"></i>
                            Update Expense
                        </button>

                    @else

                        <button
                            type="button"
                            class="btn-update opacity-50"
                            disabled
                        >
                            <i class="bi bi-lock-fill me-1"></i>
                            Update Disabled
                        </button>

                    @endif


                    <a
                        href="{{ route(
                            'admin.institute-expenses.index'
                        ) }}"
                        class="btn-cancel"
                    >
                        <i class="bi bi-x-circle me-1"></i>
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection