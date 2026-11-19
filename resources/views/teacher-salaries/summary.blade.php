@extends('layouts.app')

@section('title', 'Salary Breakdown')
@section('page-title', 'Salary Breakdown')

@section('content')
    @include('partials.report-page-styles')

    @php
        $teacher = $data['teacher'];
        $t = $data['totals'];
        $money = fn ($value) => number_format((float) $value, 2);
        $routeParams = ['teacher' => $teacherId, 'year' => $year, 'month' => $month, 'branch_id' => $branchId];
        $monthStart = \Carbon\Carbon::create($year, $month, 1);
    @endphp

    <div class="rp-page tw:space-y-6">

        {{-- HEADER --}}
        <div class="rp-card">
            <div class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-4">
                <div class="tw:flex tw:items-center tw:gap-4">
                    <div class="rp-avatar">{{ strtoupper(substr($teacher->full_name, 0, 1)) }}</div>
                    <div>
                        <h3 class="rp-title">{{ $teacher->full_name }}</h3>
                        <p class="rp-sub">
                            Salary breakdown • {{ $data['month_label'] }}
                            @if ($data['branch']) • {{ $data['branch']->name }} @endif
                            •
                            @if ($data['is_paid'])
                                <span class="rp-chip rp-chip-paid">Paid {{ $data['paid_at']?->format('d M Y, h:i A') }}</span>
                            @else
                                <span class="rp-chip rp-chip-partial">Not paid yet</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="tw:flex tw:flex-wrap tw:gap-2 rp-no-print">
                    <a href="{{ route('teacher-salaries.show', $routeParams) }}" class="rp-btn"><i class="bi bi-arrow-left"></i> Salary report</a>
                    <a href="{{ route('teacher-salaries.payment-summary', ['year' => $monthStart->copy()->subMonth()->year, 'month' => $monthStart->copy()->subMonth()->month] + $routeParams) }}" class="rp-btn" title="Previous month"><i class="bi bi-chevron-left"></i></a>
                    <a href="{{ route('teacher-salaries.payment-summary', ['year' => $monthStart->copy()->addMonth()->year, 'month' => $monthStart->copy()->addMonth()->month] + $routeParams) }}" class="rp-btn" title="Next month"><i class="bi bi-chevron-right"></i></a>
                    <button type="button" onclick="window.print()" class="rp-btn rp-btn-primary"><i class="bi bi-printer"></i> Print</button>
                </div>
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="tw:grid tw:grid-cols-1 tw:sm:grid-cols-2 tw:xl:grid-cols-4 tw:gap-4">
            <div class="rp-stat rp-dark">
                <i class="bi bi-cash-stack rp-stat-icon"></i>
                <div class="rp-stat-label">Fees in this salary</div>
                <div class="rp-stat-value">Rs. {{ $money($t['collected']) }}</div>
                <div class="rp-stat-note">{{ $t['payments'] }} payments received</div>
            </div>

            <div class="rp-stat rp-amber">
                <i class="bi bi-building rp-stat-icon"></i>
                <div class="rp-stat-label">Hall fees (institute)</div>
                <div class="rp-stat-value">Rs. {{ $money($t['hall_fee']) }}</div>
                <div class="rp-stat-note">Taken first from each payment</div>
            </div>

            <div class="rp-stat rp-blue">
                <i class="bi bi-journal-text rp-stat-icon"></i>
                <div class="rp-stat-label">Class fees</div>
                <div class="rp-stat-value">Rs. {{ $money($t['class_fee']) }}</div>
                <div class="rp-stat-note">
                    Institute Rs. {{ $money($t['institute'] - $t['hall_fee']) }}
                    @if ($t['organizer'] > 0) · Organizer Rs. {{ $money($t['organizer']) }} @endif
                </div>
            </div>

            <div class="rp-stat rp-green">
                <i class="bi bi-person-check rp-stat-icon"></i>
                <div class="rp-stat-label">Teacher's share</div>
                <div class="rp-stat-value">Rs. {{ $money($t['teacher']) }}</div>
                <div class="rp-stat-note">
                    @if ($data['carried']['payments'] > 0)
                        Incl. Rs. {{ $money($data['carried']['teacher']) }} from earlier months
                    @else
                        Before advances and deductions
                    @endif
                </div>
            </div>
        </div>

        <div class="rp-note">
            This salary is made of the class fees <strong>received</strong>
            @if ($data['is_paid'])
                from 1 {{ $monthStart->format('M') }} until the salary was paid ({{ $data['paid_at']?->format('d M Y, h:i A') }})
            @else
                in {{ $data['month_label'] }}
            @endif
            , whatever month each fee was for, plus earlier months' fees received after their salary was paid.
            The teacher's share is the teacher % of the class fee (the payment without the hall fee).
            Advances and deductions are on the <a href="{{ route('teacher-salaries.show', $routeParams) }}">salary report</a>.
        </div>

        {{-- CLASSES --}}
        @forelse ($data['classes'] as $class)
            <div class="rp-card">
                <div class="tw:flex tw:flex-wrap tw:items-start tw:justify-between tw:gap-3 tw:mb-4">
                    <div>
                        <h4 class="rp-section-title">
                            {{ $class['class_name'] }}
                            @unless ($class['is_active']) <span class="rp-chip">Inactive</span> @endunless
                        </h4>
                        <p class="rp-sub">@if ($class['grade_name']) Grade {{ $class['grade_name'] }} • @endif {{ $class['totals']['payments'] }} payments in this salary</p>
                    </div>
                    <div class="rp-mini">
                        <span>Received <b>{{ $money($class['totals']['collected']) }}</b></span>
                        <span>Hall <b>{{ $money($class['totals']['hall_fee']) }}</b></span>
                        <span>Class <b>{{ $money($class['totals']['class_fee']) }}</b></span>
                        <span>Teacher <b class="tw:text-emerald-700">{{ $money($class['totals']['teacher']) }}</b></span>
                    </div>
                </div>

                @foreach ($class['categories'] as $category)
                    @php $fs = $category['fee_status']; @endphp
                    <div class="tw:mb-5 tw:last:mb-0">
                        <div class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-2 tw:mb-2">
                            <div class="tw:flex tw:flex-wrap tw:items-center tw:gap-2">
                                <span class="rp-strong">{{ $category['category_name'] }}</span>
                                <span class="rp-sub">{{ $category['students'] }} students · {{ $monthStart->format('M') }} fee:</span>
                                <span class="rp-chip rp-chip-paid">Paid {{ $fs['paid'] }}</span>
                                @if ($fs['partial']) <span class="rp-chip rp-chip-partial">Partly {{ $fs['partial'] }}</span> @endif
                                <span class="rp-chip rp-chip-unpaid">Unpaid {{ $fs['unpaid'] }}</span>
                                @if ($fs['freecard']) <span class="rp-chip rp-chip-freecard">Free card {{ $fs['freecard'] }}</span> @endif
                            </div>
                            <a class="rp-btn rp-btn-sm rp-no-print"
                                href="{{ route('student-class-enrollments.category-wise-payment', ['class' => $class['class_id'], 'classCategoryFee' => $category['category_fee_id'], 'year' => $year, 'month' => $month]) }}">
                                {{ $monthStart->format('M') }} fee status <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>

                        @if (count($category['payments']))
                            <div class="rp-table-wrap">
                                <table class="rp-table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Fee for</th>
                                            <th>Received on</th>
                                            <th class="rp-num">Amount</th>
                                            <th class="rp-num">Hall fee</th>
                                            <th class="rp-num">Class fee</th>
                                            <th class="rp-num">Teacher %</th>
                                            <th class="rp-num">Teacher's share</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($category['payments'] as $row)
                                            <tr>
                                                <td>
                                                    <div class="rp-strong">{{ $row['student_name'] }}</div>
                                                    <div class="rp-muted tw:text-xs">{{ $row['student_code'] }}@if ($row['receipt']) · {{ $row['receipt'] }}@endif</div>
                                                </td>
                                                <td>
                                                    {{ $row['paid_for'] }}
                                                    @if ($row['carried']) <span class="rp-chip rp-chip-carried" title="Received after that month's salary was paid">After last salary</span> @endif
                                                </td>
                                                <td>{{ $row['paid_on'] }}</td>
                                                <td class="rp-num">{{ $money($row['amount']) }}</td>
                                                <td class="rp-num">{{ $money($row['hall_fee']) }}</td>
                                                <td class="rp-num">{{ $money($row['class_fee']) }}</td>
                                                <td class="rp-num">{{ rtrim(rtrim(number_format($row['teacher_percentage'], 2), '0'), '.') }}%</td>
                                                <td class="rp-num rp-strong tw:text-emerald-700">{{ $money($row['teacher_amount']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3">{{ $category['totals']['payments'] }} payments</td>
                                            <td class="rp-num">{{ $money($category['totals']['collected']) }}</td>
                                            <td class="rp-num">{{ $money($category['totals']['hall_fee']) }}</td>
                                            <td class="rp-num">{{ $money($category['totals']['class_fee']) }}</td>
                                            <td></td>
                                            <td class="rp-num">{{ $money($category['totals']['teacher']) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <p class="rp-sub">No fees received for this category in this salary.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @empty
            <div class="rp-card tw:text-center rp-sub">No classes or payments for this teacher in {{ $data['month_label'] }}.</div>
        @endforelse

        {{-- FEES AFTER THE SALARY --}}
        @if ($data['after_salary']['totals']['payments'] > 0)
            @php $after = $data['after_salary']; @endphp
            <div class="rp-card">
                <h4 class="rp-section-title">Received after this salary was paid</h4>
                <p class="rp-sub tw:mb-4">
                    These {{ $after['totals']['payments'] }} payments (teacher's share Rs. {{ $money($after['totals']['teacher']) }}) are not in this salary.
                    They go into the next salary.
                </p>
                <div class="rp-table-wrap">
                    <table class="rp-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Fee for</th>
                                <th>Received on</th>
                                <th class="rp-num">Amount</th>
                                <th class="rp-num">Hall fee</th>
                                <th class="rp-num">Class fee</th>
                                <th class="rp-num">Teacher's share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($after['payments'] as $row)
                                <tr>
                                    <td><span class="rp-strong">{{ $row['student_name'] }}</span> <span class="rp-muted tw:text-xs">{{ $row['student_code'] }}</span></td>
                                    <td>{{ $row['paid_for'] }}</td>
                                    <td>{{ $row['paid_on'] }}</td>
                                    <td class="rp-num">{{ $money($row['amount']) }}</td>
                                    <td class="rp-num">{{ $money($row['hall_fee']) }}</td>
                                    <td class="rp-num">{{ $money($row['class_fee']) }}</td>
                                    <td class="rp-num">{{ $money($row['teacher_amount']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
