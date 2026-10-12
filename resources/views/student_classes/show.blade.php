@extends('layouts.app')

@section('title', 'Class Details')
@section('page-title', 'Class Details')

@push('styles')
    <style>
        .class-page {
            animation: fadeIn .4s ease;
        }

        .hero-card,
        .info-card {
            background: #fff;
            border: 1px solid #eef2f7;
            border-radius: 24px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .hero-card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .hero-actions {
            display: flex;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .info-card {
            height: 100%;
            padding: 1.4rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1.25rem;
        }

        .section-title i {
            color: #2563eb;
            margin-right: .45rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: .8rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            color: #64748b;
            font-size: .9rem;
        }

        .detail-value {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .custom-btn {
            border-radius: 12px;
            padding: .65rem 1rem;
            font-weight: 600;
        }

        .table thead th {
            white-space: nowrap;
            font-size: .875rem;
        }

        .table tbody td {
            vertical-align: middle;
        }

        .status-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
        }
        .category-fee-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 1.25rem;
}

.fee-option-box {
    background: #fff;
    border-color: #e2e8f0 !important;
    transition: .2s ease;
}

.fee-option-box:hover {
    border-color: #2563eb !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, .08);
}

        @media (max-width: 768px) {
            .hero-content {
                align-items: stretch;
                flex-direction: column;
            }

            .hero-actions {
                width: 100%;
            }

            .hero-actions .btn {
                flex: 1;
            }

            .detail-item {
                gap: .5rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="class-page">

        {{-- Header --}}
        <div class="hero-card">
            <div class="hero-content">
                <div>
                    <h4 class="fw-bold mb-2">
                        {{ $studentClass->class_name }}
                    </h4>

                    <div class="text-muted">
                        {{ ucfirst($studentClass->class_type) }}
                        <span class="mx-1">·</span>
                        {{ $studentClass->medium }}
                    </div>

                    @if ($studentClass->branch)
                        <div class="mt-2">
                            <i class="bi bi-building me-1"></i>
                            <span class="text-muted">Branch:</span>
                            <strong>{{ $studentClass->branch->name }}</strong>
                        </div>
                    @endif
                </div>

                <div class="hero-actions">
                    <a href="{{ route('student-classes.edit', $studentClass) }}" class="btn btn-warning custom-btn">
                        <i class="bi bi-pencil-square me-1"></i>
                        Edit Class
                    </a>

                    <a href="{{ route('student-classes.index') }}" class="btn btn-outline-secondary custom-btn">
                        <i class="bi bi-arrow-left me-1"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">

            {{-- Class Information --}}
            <div class="col-lg-6">
                <div class="info-card">
                    <h6 class="section-title">
                        <i class="bi bi-journal-bookmark"></i>
                        Class Information
                    </h6>

                    <div class="detail-item">
                        <span class="detail-label">Class Name</span>
                        <span class="detail-value">
                            {{ $studentClass->class_name }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Class Type</span>
                        <span class="detail-value">
                            {{ ucfirst($studentClass->class_type) }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Medium</span>
                        <span class="detail-value">
                            {{ $studentClass->medium }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Grade</span>
                        <span class="detail-value">
                            {{ $studentClass->grade->grade_name ?? 'N/A' }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Subject</span>
                        <span class="detail-value">
                            {{ $studentClass->subject->subject_name ?? 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Teacher Information --}}
            <div class="col-lg-6">
                <div class="info-card">
                    <h6 class="section-title">
                        <i class="bi bi-person-badge"></i>
                        Teacher Information
                    </h6>

                    <div class="detail-item">
                        <span class="detail-label">Teacher</span>
                        <span class="detail-value">
                            {{ $studentClass->teacher->full_name ?? 'N/A' }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Teacher ID</span>
                        <span class="detail-value">
                            {{ $studentClass->teacher->custom_id ?? 'N/A' }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Mobile</span>
                        <span class="detail-value">
                            {{ $studentClass->teacher->mobile ?? 'N/A' }}
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">
                            {{ $studentClass->teacher->email ?? 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Payment Configuration --}}
            <div class="col-12">
                <div class="info-card">
                    <h6 class="section-title">
                        <i class="bi bi-cash-coin"></i>
                        Payment Configuration
                    </h6>

                    @if ($studentClass->paymentConfig)
                        @php
                            $paymentConfig = $studentClass->paymentConfig;
                        @endphp

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="border rounded-4 p-3 h-100">
                                    <div class="detail-label">Teacher Percentage</div>
                                    <h4 class="fw-bold mt-2 mb-0">
                                        {{ number_format($paymentConfig->teacher_percentage, 2) }}%
                                    </h4>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="border rounded-4 p-3 h-100">
                                    <div class="detail-label">Organizer Percentage</div>
                                    <h4 class="fw-bold mt-2 mb-0">
                                        {{ number_format($paymentConfig->organizer_percentage, 2) }}%
                                    </h4>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="border rounded-4 p-3 h-100">
                                    <div class="detail-label">Institution Percentage</div>
                                    <h4 class="fw-bold mt-2 mb-0">
                                        {{ number_format($paymentConfig->institution_percentage, 2) }}%
                                    </h4>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="detail-item">
                                    <span class="detail-label">Organizer</span>
                                    <span class="detail-value">
                                        {{ $paymentConfig->organizer->name ?? 'None' }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="detail-item">
                                    <span class="detail-label">Effective From</span>
                                    <span class="detail-value">
                                        {{ $paymentConfig->effective_from
                                            ? \Carbon\Carbon::parse($paymentConfig->effective_from)->format('Y-m-d')
                                            : 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="detail-item">
                                    <span class="detail-label">Effective To</span>
                                    <span class="detail-value">
                                        {{ $paymentConfig->effective_to ? \Carbon\Carbon::parse($paymentConfig->effective_to)->format('Y-m-d') : 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <span class="detail-label me-2">Payment Status:</span>

                                <span class="badge {{ $paymentConfig->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $paymentConfig->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Payment configuration has not been set for this class.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Class Status --}}
            <div class="col-12">
                <div class="info-card">
                    <h6 class="section-title">
                        <i class="bi bi-toggle-on"></i>
                        Class Status
                    </h6>

                    <div class="status-badges">
                        <span class="badge {{ $studentClass->is_active ? 'bg-success' : 'bg-secondary' }} px-3 py-2">
                            <i class="bi bi-circle-fill me-1"></i>
                            {{ $studentClass->is_active ? 'Active' : 'Inactive' }}
                        </span>

                        <span
                            class="badge {{ $studentClass->is_ongoing ? 'bg-primary' : 'bg-light text-dark border' }} px-3 py-2">
                            <i class="bi bi-calendar-check me-1"></i>
                            {{ $studentClass->is_ongoing ? 'Ongoing' : 'Not Ongoing' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Class Category Fees --}}
            {{-- Class Category Fees --}}
            <div class="col-12">
                <div class="info-card">
                    <h6 class="section-title">
                        <i class="bi bi-tags"></i>
                        Class Category Fees
                    </h6>

                    @if ($categoryFees->count())
                        @foreach ($categoryFees as $fee)
                            <div class="category-fee-card mb-4">

                                {{-- Category Header --}}
                                <div
                                    class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3 pb-3 border-bottom">
                                    <div>
                                        <h5 class="fw-bold mb-1">
                                            {{ optional($fee->category)->category_name ?? 'N/A' }}

                                            @if (optional($fee->category)->code)
                                                <span class="badge bg-secondary ms-2">
                                                    {{ $fee->category->code }}
                                                </span>
                                            @endif
                                        </h5>

                                        <div class="text-muted small">
                                            Fee Category #{{ $loop->iteration }}
                                        </div>
                                    </div>

                                    <span class="badge {{ $fee->is_active ? 'bg-success' : 'bg-secondary' }} px-3 py-2">
                                        <i class="bi bi-circle-fill me-1"></i>
                                        {{ $fee->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                {{-- Fee Options --}}
                                <div class="mb-3">
                                    <div class="text-muted small fw-semibold mb-2">
                                        <i class="bi bi-list-ul me-1"></i>
                                        Fee Options
                                    </div>

                                    @if ($fee->activeFeeOptions->count())
                                        <div class="row g-2">
                                            @foreach ($fee->activeFeeOptions as $option)
                                                <div class="col-md-6">
                                                    <div
                                                        class="fee-option-box d-flex justify-content-between align-items-center p-3 rounded-3 border">

                                                        <div>
                                                            <div class="fw-bold">
                                                                {{ $option->label }}
                                                            </div>

                                                            @if ($option->note)
                                                                <div class="text-muted small mt-1">
                                                                    {{ $option->note }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="text-end">
                                                            <div class="fw-bold text-primary">
                                                                Rs. {{ number_format($option->fee, 2) }}
                                                            </div>

                                                            @if ($option->is_default)
                                                                <span class="badge bg-success mt-1">
                                                                    Default
                                                                </span>
                                                            @endif
                                                        </div>

                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0 py-2">
                                            <i class="bi bi-info-circle me-1"></i>
                                            No active fee options for this category.
                                        </div>
                                    @endif
                                </div>

                                {{-- Note --}}
                                @if ($fee->note)
                                    <div class="text-muted small">
                                        <i class="bi bi-sticky me-1"></i>
                                        <strong>Note:</strong>
                                        {{ $fee->note }}
                                    </div>
                                @endif

                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            No category fees have been added for this class.
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
