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

        .ui-table thead th {
            white-space: nowrap;
            font-size: .875rem;
        }

        .ui-table tbody td {
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

            .hero-actions .ui-btn {
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
                    <h4 class="tw:font-bold! tw:mb-2!">
                        {{ $studentClass->class_name }}
                    </h4>

                    <div class="tw:text-bs-secondary-color!">
                        {{ ucfirst($studentClass->class_type) }}
                        <span class="tw:mx-1!">·</span>
                        {{ $studentClass->medium }}
                    </div>

                    @if ($studentClass->branch)
                        <div class="tw:mt-2!">
                            <i class="bi bi-building tw:mr-1!"></i>
                            <span class="tw:text-bs-secondary-color!">Branch:</span>
                            <strong>{{ $studentClass->branch->name }}</strong>
                        </div>
                    @endif
                </div>

                <div class="hero-actions">
                    <a href="{{ route('student-classes.edit', $studentClass) }}" class="ui-btn ui-btn-warning custom-btn">
                        <i class="bi bi-pencil-square tw:mr-1!"></i>
                        Edit Class
                    </a>

                    <a href="{{ route('student-classes.index') }}" class="ui-btn ui-btn-outline-secondary custom-btn">
                        <i class="bi bi-arrow-left tw:mr-1!"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>

        <div class="tw:flex tw:flex-wrap tw:-mt-6 tw:-mx-3">

            {{-- Class Information --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
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
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:w-full tw:lg:flex-[0_0_auto] tw:lg:w-[50%]">
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
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="info-card">
                    <h6 class="section-title">
                        <i class="bi bi-cash-coin"></i>
                        Payment Configuration
                    </h6>

                    @if ($studentClass->paymentConfig)
                        @php
                            $paymentConfig = $studentClass->paymentConfig;
                        @endphp

                        <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">
                                    <div class="detail-label">Teacher Percentage</div>
                                    <h4 class="tw:font-bold! tw:mt-2! tw:mb-0!">
                                        {{ number_format($paymentConfig->teacher_percentage, 2) }}%
                                    </h4>
                                </div>
                            </div>

                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">
                                    <div class="detail-label">Organizer Percentage</div>
                                    <h4 class="tw:font-bold! tw:mt-2! tw:mb-0!">
                                        {{ number_format($paymentConfig->organizer_percentage, 2) }}%
                                    </h4>
                                </div>
                            </div>

                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                <div class="tw:bs-border! tw:rounded-bs-xl! tw:p-4! tw:h-full!">
                                    <div class="detail-label">Institution Percentage</div>
                                    <h4 class="tw:font-bold! tw:mt-2! tw:mb-0!">
                                        {{ number_format($paymentConfig->institution_percentage, 2) }}%
                                    </h4>
                                </div>
                            </div>

                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                <div class="detail-item">
                                    <span class="detail-label">Organizer</span>
                                    <span class="detail-value">
                                        {{ $paymentConfig->organizer->name ?? 'None' }}
                                    </span>
                                </div>
                            </div>

                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                <div class="detail-item">
                                    <span class="detail-label">Effective From</span>
                                    <span class="detail-value">
                                        {{ $paymentConfig->effective_from
                                            ? \Carbon\Carbon::parse($paymentConfig->effective_from)->format('Y-m-d')
                                            : 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[33.33333333%]">
                                <div class="detail-item">
                                    <span class="detail-label">Effective To</span>
                                    <span class="detail-value">
                                        {{ $paymentConfig->effective_to ? \Carbon\Carbon::parse($paymentConfig->effective_to)->format('Y-m-d') : 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:flex-[0_0_auto] tw:w-[100%] tw:mt-4!">
                                <span class="detail-label tw:mr-2!">Payment Status:</span>

                                <span class="ui-badge {{ $paymentConfig->is_active ? 'tw:bg-bs-success!' : 'tw:bg-bs-secondary!' }}">
                                    {{ $paymentConfig->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="ui-alert ui-alert-warning tw:mb-0!">
                            <i class="bi bi-exclamation-triangle tw:mr-1!"></i>
                            Payment configuration has not been set for this class.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Class Status --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="info-card">
                    <h6 class="section-title">
                        <i class="bi bi-toggle-on"></i>
                        Class Status
                    </h6>

                    <div class="status-badges">
                        <span class="ui-badge tw:px-4! tw:py-2! {{ $studentClass->is_active ? 'tw:bg-bs-success!' : 'tw:bg-bs-secondary!' }}">
                            <i class="bi bi-circle-fill tw:mr-1!"></i>
                            {{ $studentClass->is_active ? 'Active' : 'Inactive' }}
                        </span>

                        <span
                            class="ui-badge tw:px-4! tw:py-2! {{ $studentClass->is_ongoing ? 'tw:bg-bs-primary!' : 'tw:bg-bs-light! tw:text-bs-dark! tw:bs-border!' }}">
                            <i class="bi bi-calendar-check tw:mr-1!"></i>
                            {{ $studentClass->is_ongoing ? 'Ongoing' : 'Not Ongoing' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Class Category Fees --}}
            {{-- Class Category Fees --}}
            <div class="tw:shrink-0 tw:max-w-full tw:px-3 tw:mt-6 tw:flex-[0_0_auto] tw:w-[100%]">
                <div class="info-card">
                    <h6 class="section-title">
                        <i class="bi bi-tags"></i>
                        Class Category Fees
                    </h6>

                    @if ($categoryFees->count())
                        @foreach ($categoryFees as $fee)
                            <div class="category-fee-card tw:mb-6!">

                                {{-- Category Header --}}
                                <div
                                    class="tw:flex! tw:justify-between! tw:items-start! tw:flex-wrap! tw:gap-2! tw:mb-4! tw:pb-4! tw:bs-border-b!">
                                    <div>
                                        <h5 class="tw:font-bold! tw:mb-1!">
                                            {{ optional($fee->category)->category_name ?? 'N/A' }}

                                            @if (optional($fee->category)->code)
                                                <span class="ui-badge tw:bg-bs-secondary! tw:ml-2!">
                                                    {{ $fee->category->code }}
                                                </span>
                                            @endif
                                        </h5>

                                        <div class="tw:text-bs-secondary-color! tw:text-[.875em]">
                                            Fee Category #{{ $loop->iteration }}
                                        </div>
                                    </div>

                                    <span class="ui-badge tw:px-4! tw:py-2! {{ $fee->is_active ? 'tw:bg-bs-success!' : 'tw:bg-bs-secondary!' }}">
                                        <i class="bi bi-circle-fill tw:mr-1!"></i>
                                        {{ $fee->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>

                                {{-- Fee Options --}}
                                <div class="tw:mb-4!">
                                    <div class="tw:text-bs-secondary-color! tw:text-[.875em] tw:font-semibold! tw:mb-2!">
                                        <i class="bi bi-list-ul tw:mr-1!"></i>
                                        Fee Options
                                    </div>

                                    @if ($fee->activeFeeOptions->count())
                                        <div class="tw:flex tw:flex-wrap tw:-mt-2 tw:-mx-1">
                                            @foreach ($fee->activeFeeOptions as $option)
                                                <div class="tw:shrink-0 tw:max-w-full tw:px-1 tw:mt-2 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%]">
                                                    <div
                                                        class="fee-option-box tw:flex! tw:justify-between! tw:items-center! tw:p-4! tw:rounded-bs-lg! tw:bs-border!">

                                                        <div>
                                                            <div class="tw:font-bold!">
                                                                {{ $option->label }}
                                                            </div>

                                                            @if ($option->note)
                                                                <div class="tw:text-bs-secondary-color! tw:text-[.875em] tw:mt-1!">
                                                                    {{ $option->note }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="tw:text-right!">
                                                            <div class="tw:font-bold! tw:text-bs-primary!">
                                                                Rs. {{ number_format($option->fee, 2) }}
                                                            </div>

                                                            @if ($option->is_default)
                                                                <span class="ui-badge tw:bg-bs-success! tw:mt-1!">
                                                                    Default
                                                                </span>
                                                            @endif
                                                        </div>

                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="ui-alert ui-alert-warning tw:mb-0! tw:py-2!">
                                            <i class="bi bi-info-circle tw:mr-1!"></i>
                                            No active fee options for this category.
                                        </div>
                                    @endif
                                </div>

                                {{-- Note --}}
                                @if ($fee->note)
                                    <div class="tw:text-bs-secondary-color! tw:text-[.875em]">
                                        <i class="bi bi-sticky tw:mr-1!"></i>
                                        <strong>Note:</strong>
                                        {{ $fee->note }}
                                    </div>
                                @endif

                            </div>
                        @endforeach
                    @else
                        <div class="ui-alert ui-alert-warning tw:mb-0!">
                            <i class="bi bi-info-circle tw:mr-1!"></i>
                            No category fees have been added for this class.
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
