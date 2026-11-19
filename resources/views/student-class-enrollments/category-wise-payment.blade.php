@extends('layouts.app')

@section('title', 'Class Fee Status')
@section('page-title', 'Class Fee Status')

@section('content')
    @include('partials.report-page-styles')

    @php
        $monthStart = \Carbon\Carbon::create($year, $month, 1);
        $monthLabel = $monthStart->format('F Y');
        $s = $summary;
        $money = fn ($value) => number_format((float) $value, 2);
        $linkFor = fn (\Carbon\Carbon $date) => route('student-class-enrollments.category-wise-payment', [
            'class' => $class['id'],
            'classCategoryFee' => $classCategoryFee,
            'year' => $date->year,
            'month' => $date->month,
            'per_page' => $perPage,
        ]);
        $rowNumber = ($students->currentPage() - 1) * $students->perPage();
    @endphp

    <div class="rp-page tw:space-y-6">

        {{-- HEADER --}}
        <div class="rp-card">
            <div class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-4">
                <div class="tw:flex tw:items-center tw:gap-4">
                    <div class="rp-avatar"><i class="bi bi-people"></i></div>
                    <div>
                        <h3 class="rp-title">{{ $class['class_name'] }} · {{ $category_name }}</h3>
                        <p class="rp-sub">
                            @if ($class['grade_name']) Grade {{ $class['grade_name'] }} • @endif
                            @if ($class['teacher_name']) {{ $class['teacher_name'] }} • @endif
                            Fee for <strong>{{ $monthLabel }}</strong>
                        </p>
                    </div>
                </div>

                <div class="tw:flex tw:flex-wrap tw:gap-2 rp-no-print">
                    <a href="{{ url()->previous() }}" class="rp-btn"><i class="bi bi-arrow-left"></i> Back</a>
                    <a href="{{ $linkFor($monthStart->copy()->subMonth()) }}" class="rp-btn" title="Previous month"><i class="bi bi-chevron-left"></i></a>
                    <a href="{{ $linkFor($monthStart->copy()->addMonth()) }}" class="rp-btn" title="Next month"><i class="bi bi-chevron-right"></i></a>
                    <button type="button" onclick="window.print()" class="rp-btn rp-btn-primary"><i class="bi bi-printer"></i> Print</button>
                </div>
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="tw:grid tw:grid-cols-1 tw:sm:grid-cols-2 tw:xl:grid-cols-4 tw:gap-4">
            <div class="rp-stat rp-dark">
                <i class="bi bi-people rp-stat-icon"></i>
                <div class="rp-stat-label">Students</div>
                <div class="rp-stat-value">{{ $s['students'] }}</div>
                <div class="rp-stat-note">
                    Paid {{ $s['paid'] }} · Unpaid {{ $s['unpaid'] }}
                    @if ($s['partial']) · Partly {{ $s['partial'] }} @endif
                    @if ($s['freecard']) · Free card {{ $s['freecard'] }} @endif
                </div>
            </div>

            <div class="rp-stat rp-blue">
                <i class="bi bi-receipt rp-stat-icon"></i>
                <div class="rp-stat-label">To collect</div>
                <div class="rp-stat-value">Rs. {{ $money($s['payable']) }}</div>
                <div class="rp-stat-note">Class fee {{ $money($s['class_fee']) }} + Hall fee {{ $money($s['hall_fee']) }}</div>
            </div>

            <div class="rp-stat rp-green">
                <i class="bi bi-cash-coin rp-stat-icon"></i>
                <div class="rp-stat-label">Collected</div>
                <div class="rp-stat-value">Rs. {{ $money($s['collected']) }}</div>
                <div class="rp-stat-note">
                    Class fee {{ $money($s['class_paid']) }} + Hall fee {{ $money($s['hall_paid']) }}
                    @if ($s['discount'] > 0) · Discount {{ $money($s['discount']) }} @endif
                </div>
            </div>

            <div class="rp-stat rp-red">
                <i class="bi bi-hourglass-split rp-stat-icon"></i>
                <div class="rp-stat-label">Still to collect</div>
                <div class="rp-stat-value">Rs. {{ $money($s['balance']) }}</div>
                <div class="rp-stat-note">
                    {{ $s['payable'] > 0 ? round(($s['collected'] + $s['discount']) / $s['payable'] * 100) : 100 }}% collected
                </div>
            </div>
        </div>

        {{-- STUDENTS --}}
        <div class="rp-card">
            <div class="tw:flex tw:flex-wrap tw:items-center tw:justify-between tw:gap-3 tw:mb-4">
                <div>
                    <h4 class="rp-section-title">Students</h4>
                    <p class="rp-sub">Payments for {{ $monthLabel }}, whatever day they were paid</p>
                </div>

                @if ($students->total() > 25)
                    <form method="GET" class="rp-no-print tw:flex tw:items-center tw:gap-2">
                        <label for="perPage" class="rp-sub">Show</label>
                        <select id="perPage" name="per_page" class="rp-select" onchange="this.form.submit()">
                            @foreach ([25, 50, 100, 200] as $size)
                                <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            <div class="rp-table-wrap">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Fee option</th>
                            <th class="rp-num">Class fee</th>
                            <th class="rp-num">Hall fee</th>
                            <th class="rp-num">To pay</th>
                            <th class="rp-num">Paid</th>
                            <th class="rp-num">Balance</th>
                            <th>Paid on</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $row)
                            <tr>
                                <td class="rp-muted">{{ ++$rowNumber }}</td>
                                <td>
                                    <div class="rp-strong tw:whitespace-nowrap">{{ $row['student_name'] }}</div>
                                    <div class="rp-muted tw:text-xs">
                                        {{ $row['student_code'] }}@if ($showPhones && $row['mobile']) · {{ $row['mobile'] }}@endif
                                    </div>
                                </td>
                                <td>
                                    @if ($row['is_free_card'])
                                        <span class="rp-chip rp-chip-freecard">Free card</span>
                                    @elseif ($row['fee_option'])
                                        {{ $row['fee_option']['label'] }}
                                    @else
                                        <span class="rp-muted">-</span>
                                    @endif
                                </td>
                                <td class="rp-num">{{ $money($row['class_fee']) }}</td>
                                <td class="rp-num">{{ $money($row['hall_fee']) }}</td>
                                <td class="rp-num rp-strong">{{ $money($row['payable']) }}</td>
                                <td class="rp-num">
                                    {{ $money($row['paid']) }}
                                    @if ($row['discount'] > 0)
                                        <div class="rp-muted tw:text-xs">+ discount {{ $money($row['discount']) }}</div>
                                    @endif
                                </td>
                                <td class="rp-num {{ $row['balance'] > 0 ? 'tw:text-rose-600' : 'rp-muted' }}">{{ $money($row['balance']) }}</td>
                                <td class="tw:whitespace-nowrap">
                                    {{ $row['paid_on'] ?: '-' }}
                                    @if ($row['receipts'])
                                        <div class="rp-muted tw:text-xs">{{ $row['receipts'] }}</div>
                                    @endif
                                </td>
                                <td><span class="rp-chip rp-chip-{{ $row['status'] }}">{{ \App\Support\MonthFee::label($row['status']) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="tw:text-center tw:py-10 rp-muted">No active students in this category.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($students->total() > 0)
                        <tfoot>
                            <tr>
                                <td colspan="3">All {{ $s['students'] }} students</td>
                                <td class="rp-num">{{ $money($s['class_fee']) }}</td>
                                <td class="rp-num">{{ $money($s['hall_fee']) }}</td>
                                <td class="rp-num">{{ $money($s['payable']) }}</td>
                                <td class="rp-num">{{ $money($s['collected']) }}</td>
                                <td class="rp-num">{{ $money($s['balance']) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @if ($students->hasPages())
                <div class="tw:mt-4 rp-no-print">{{ $students->links() }}</div>
            @endif
        </div>

        <div class="rp-note">
            <strong>To pay</strong> = class fee (the student's fee option; 0 for a free card) + the hall fee. A payment pays the hall fee
            first, the rest is class fee. <strong>Paid</strong> counts completed payments for {{ $monthLabel }} only (cancelled ones are left
            out). A month that was paid keeps the hall fee of the day it was paid.
        </div>
    </div>
@endsection
