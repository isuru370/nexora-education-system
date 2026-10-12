@extends('layouts.app')

@section('title', 'Enrollment Details')
@section('page-title', 'Enrollment Details')

@section('content')

    <div class="container-fluid">

        {{-- =========================================================
        HEADER
    ========================================================== --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

            <div>
                <h4 class="fw-bold mb-1">
                    Enrollment Details
                </h4>

                <p class="text-muted mb-0">
                    {{ optional($studentClassEnrollment->student)->custom_id ?? '-' }}
                    -
                    {{ optional($studentClassEnrollment->student)->initial_name ?? '-' }}
                </p>
            </div>


            <div class="d-flex gap-2 flex-wrap">

                {{-- Toggle Active --}}
                <form method="POST" action="{{ route('student-class-enrollments.toggle-active', $studentClassEnrollment) }}"
                    class="d-inline">
                    @csrf
                    @method('PATCH')

                    <button type="submit"
                        class="btn {{ $studentClassEnrollment->is_active ? 'btn-warning' : 'btn-success' }}">
                        <i class="bi bi-{{ $studentClassEnrollment->is_active ? 'pause-circle' : 'play-circle' }} me-1"></i>
                        {{ $studentClassEnrollment->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>


                {{-- Leave (only if active) --}}
                @if ($studentClassEnrollment->is_active)
                    <form method="POST" action="{{ route('student-class-enrollments.leave', $studentClassEnrollment) }}"
                        class="d-inline" onsubmit="return confirm('Mark this student as left from class?');">
                        @csrf
                        @method('PATCH')

                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-box-arrow-right me-1"></i>
                            Leave
                        </button>
                    </form>
                @endif


                {{-- Edit --}}
                <a href="{{ route('student-class-enrollments.edit', $studentClassEnrollment) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i>
                    Edit
                </a>


                {{-- Delete --}}
                <form method="POST" action="{{ route('student-class-enrollments.destroy', $studentClassEnrollment) }}"
                    class="d-inline" onsubmit="return confirm('Are you sure you want to delete this enrollment?');">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-1"></i>
                        Delete
                    </button>
                </form>


                {{-- Back --}}
                <a href="{{ route('student-class-enrollments.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>
                    Back
                </a>

            </div>
        </div>


        {{-- =========================================================
        STUDENT / CLASS INFORMATION
    ========================================================== --}}
        <div class="row g-4">


            {{-- =====================================================
            BRANCH INFORMATION
        ====================================================== --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-building text-info me-2"></i>
                            Branch Information
                        </h5>
                    </div>

                    <div class="card-body px-4">
                        <div class="info-row">
                            <span class="info-label">Branch</span>
                            <span class="info-value">
                                @if ($studentClassEnrollment->branch)
                                    <span class="badge bg-info text-dark">
                                        {{ $studentClassEnrollment->branch->name }}
                                    </span>

                                    @if ($studentClassEnrollment->branch->code)
                                        <span class="badge bg-light text-dark border ms-1">
                                            {{ $studentClassEnrollment->branch->code }}
                                        </span>
                                    @endif
                                @else
                                    -
                                @endif
                            </span>
                        </div>

                        @if ($studentClassEnrollment->branch)
                            <div class="info-row">
                                <span class="info-label">Branch ID</span>
                                <span class="info-value">
                                    {{ $studentClassEnrollment->branch_id }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>


            {{-- =====================================================
            STUDENT INFORMATION
        ====================================================== --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-person-circle text-primary me-2"></i>
                            Student Information
                        </h5>
                    </div>

                    <div class="card-body px-4">
                        <div class="info-row">
                            <span class="info-label">Student ID</span>
                            <span class="info-value">
                                {{ optional($studentClassEnrollment->student)->custom_id ?? '-' }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Name</span>
                            <span class="info-value">
                                {{ optional($studentClassEnrollment->student)->full_name ??
                                    (optional($studentClassEnrollment->student)->initial_name ?? '-') }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Mobile</span>
                            <span class="info-value">
                                {{ optional($studentClassEnrollment->student)->mobile ?? '-' }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Enrollment Status</span>
                            <span class="info-value">
                                @if ($studentClassEnrollment->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Free Card</span>
                            <span class="info-value">
                                @if ($studentClassEnrollment->is_free_card)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-light text-dark border">No</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>


            {{-- =====================================================
            CLASS INFORMATION
        ====================================================== --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-journal-bookmark-fill text-primary me-2"></i>
                            Class Information
                        </h5>
                    </div>

                    <div class="card-body px-4">
                        <div class="info-row">
                            <span class="info-label">Class</span>
                            <span class="info-value">
                                {{ optional($studentClassEnrollment->studentClass)->class_name ?? '-' }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Grade</span>
                            <span class="info-value">
                                {{ optional(optional($studentClassEnrollment->studentClass)->grade)->grade_name ?? '-' }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Subject</span>
                            <span class="info-value">
                                {{ optional(optional($studentClassEnrollment->studentClass)->subject)->subject_name ?? '-' }}
                            </span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Teacher</span>
                            <span class="info-value">
                                {{ optional(optional($studentClassEnrollment->studentClass)->teacher)->initials ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>


            {{-- =====================================================
            FEE INFORMATION
        ====================================================== --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-cash-stack text-success me-2"></i>
                            Fee Information
                        </h5>
                    </div>

                    <div class="card-body px-4">
                        {{-- Category --}}
                        <div class="info-row">
                            <span class="info-label">Category</span>
                            <span class="info-value">
                                {{ optional(optional($studentClassEnrollment->classCategoryFee)->category)->category_name ?? '-' }}

                                @if (optional(optional($studentClassEnrollment->classCategoryFee)->category)->code)
                                    <span class="badge bg-light text-dark border ms-1">
                                        {{ $studentClassEnrollment->classCategoryFee->category->code }}
                                    </span>
                                @endif
                            </span>
                        </div>

                        {{-- Fee Option --}}
                        <div class="info-row">
                            <span class="info-label">Fee Option</span>
                            <span class="info-value">
                                @if ($studentClassEnrollment->classCategoryFeeOption)
                                    <span class="badge bg-primary">
                                        {{ $studentClassEnrollment->classCategoryFeeOption->label }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </span>
                        </div>

                        {{-- Original Fee Option Amount --}}
                        @if ($studentClassEnrollment->classCategoryFeeOption)
                            <div class="info-row">
                                <span class="info-label">Fee Option Amount</span>
                                <span class="info-value">
                                    Rs.
                                    {{ number_format((float) $studentClassEnrollment->classCategoryFeeOption->fee, 2) }}
                                </span>
                            </div>
                        @endif

                        {{-- Final Fee --}}
                        <div class="info-row">
                            <span class="info-label">Final Fee</span>
                            <span class="info-value fw-bold text-primary">
                                Rs. {{ number_format((float) $studentClassEnrollment->final_fee, 2) }}
                            </span>
                        </div>

                        {{-- Free Card Alert --}}
                        @if ($studentClassEnrollment->is_free_card)
                            <div class="alert alert-success mt-3 mb-0">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                This student has a free card.
                                <strong>No fee is required.</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>


            {{-- =====================================================
            PAYMENT INFORMATION
        ====================================================== --}}
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-wallet2 text-warning me-2"></i>
                            Payment Information
                        </h5>
                    </div>

                    <div class="card-body px-4">
                        {{-- Paid --}}
                        <div class="info-row">
                            <span class="info-label">Paid Amount</span>
                            <span class="info-value text-success fw-bold">
                                Rs. {{ number_format((float) $studentClassEnrollment->paid_amount, 2) }}
                            </span>
                        </div>

                        {{-- Balance --}}
                        <div class="info-row">
                            <span class="info-label">Balance</span>
                            <span class="info-value text-danger fw-bold">
                                Rs. {{ number_format((float) $studentClassEnrollment->balance, 2) }}
                            </span>
                        </div>

                        {{-- Payment Status --}}
                        <div class="info-row">
                            <span class="info-label">Payment Status</span>
                            <span class="info-value">
                                @php
                                    $paymentStatus = $studentClassEnrollment->payment_status;
                                @endphp

                                @if ($paymentStatus === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($paymentStatus === 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @elseif($paymentStatus === 'unpaid')
                                    <span class="badge bg-danger">Unpaid</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($paymentStatus) }}</span>
                                @endif
                            </span>
                        </div>

                        {{-- Payments Count --}}
                        @if ($studentClassEnrollment->relationLoaded('payments'))
                            <div class="info-row">
                                <span class="info-label">Payments Count</span>
                                <span class="info-value">
                                    <span class="badge bg-primary">
                                        {{ $studentClassEnrollment->payments->count() }}
                                    </span>
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>


            {{-- =====================================================
            ENROLLMENT INFORMATION
        ====================================================== --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 px-4">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-calendar-check text-primary me-2"></i>
                            Enrollment Information
                        </h5>
                    </div>

                    <div class="card-body px-4">
                        <div class="row">
                            {{-- Enrollment ID --}}
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Enrollment ID</span>
                                    <span class="info-value">
                                        #{{ $studentClassEnrollment->id }}
                                    </span>
                                </div>
                            </div>

                            {{-- Enrolled At --}}
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Enrolled At</span>
                                    <span class="info-value">
                                        {{ $studentClassEnrollment->enrolled_at ? $studentClassEnrollment->enrolled_at->format('Y-m-d') : '-' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Left At --}}
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Left At</span>
                                    <span class="info-value">
                                        {{ $studentClassEnrollment->left_at ? $studentClassEnrollment->left_at->format('Y-m-d') : '-' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Created At --}}
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Created At</span>
                                    <span class="info-value">
                                        {{ $studentClassEnrollment->created_at ? $studentClassEnrollment->created_at->format('Y-m-d H:i') : '-' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Updated At --}}
                            <div class="col-md-4">
                                <div class="info-row">
                                    <span class="info-label">Updated At</span>
                                    <span class="info-value">
                                        {{ $studentClassEnrollment->updated_at ? $studentClassEnrollment->updated_at->format('Y-m-d H:i') : '-' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Deleted At --}}
                            @if ($studentClassEnrollment->deleted_at)
                                <div class="col-md-4">
                                    <div class="info-row">
                                        <span class="info-label">Deleted At</span>
                                        <span class="info-value">
                                            <span class="badge bg-danger">
                                                {{ $studentClassEnrollment->deleted_at->format('Y-m-d H:i') }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            @endif

                            {{-- Note --}}
                            <div class="col-12 mt-3">
                                <div class="info-row align-items-start">
                                    <span class="info-label">Note</span>
                                    <span class="info-value">
                                        {{ $studentClassEnrollment->note ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

@endsection


{{-- =============================================================
    STYLES
============================================================= --}}
@push('styles')
    <style>
        .card {
            border-radius: 18px;
        }

        .card-header {
            border-radius: 18px 18px 0 0 !important;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: .85rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-size: .9rem;
            font-weight: 500;
        }

        .info-value {
            color: #0f172a;
            font-size: .95rem;
            font-weight: 600;
            text-align: right;
        }

        .badge {
            border-radius: 8px;
            padding: .45rem .7rem;
            font-size: .75rem;
        }

        @media(max-width:768px) {
            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-value {
                text-align: left;
            }
        }
    </style>
@endpush
