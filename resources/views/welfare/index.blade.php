@extends('layouts.app')

@section('title', 'Teacher Welfare')
@section('page-title', 'Teacher Welfare')

@section('content')
    @php
        $monthLabel = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
        $branchName = optional($branches->firstWhere('id', $branchId))->name;
    @endphp

    <div class="dashboard-page">

        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Teacher Welfare</h1>
                    <p class="hero-subtitle">
                        {{ $monthLabel }}{{ $branchName ? ' • ' . $branchName : '' }}.
                        Welfare is taken from the teacher's salary for this branch and month and goes to the
                        branch welfare fund (not institute income). The salary slip shows it.
                    </p>
                </div>
                <div class="hero-right tw:flex! tw:flex-col! tw:items-end! tw:gap-2!">
                    <small class="hero-updated">Fund balance: <strong>LKR {{ number_format($balance, 2) }}</strong></small>
                    <a href="{{ route('welfare.fund', ['branch_id' => $branchId, 'year' => $year, 'month' => $month]) }}"
                        class="nx-btn nx-btn-light"><i class="bi bi-piggy-bank"></i> Welfare Fund</a>
                </div>
            </div>
        </div>

        @include('welfare.partials.filters', ['action' => route('welfare.index')])

        @if ($errors->any())
            <div class="ui-alert ui-alert-danger">{{ $errors->first() }}</div>
        @endif

        {{-- ================= Take welfare for the month ================= --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-heart-pulse tw:text-(color:--nx-red)!"></i> Take welfare: {{ $monthLabel }}</h5>
                    <p class="nx-card-subtitle">
                        Tick the teachers, check the amounts, then "Take welfare". It cannot be taken after the
                        month's salary is paid, or more than the salary. Cancel gives it back (before the salary is paid).
                    </p>
                </div>
                <span class="nx-badge nx-badge-blue">Taken: LKR {{ number_format($total_taken, 2) }}</span>
            </div>

            @if ($month_block)
                <div class="ui-alert ui-alert-warning tw:m-4! tw:mb-0!">{{ $month_block }}</div>
            @endif

            <form method="POST" action="{{ route('welfare.deduct') }}" id="welfareDeductForm">
                @csrf
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">

                <div class="ui-table-responsive">
                    <table class="ui-table premium-table tw:mb-0! tw:align-middle!">
                        <thead>
                            <tr>
                                <th style="width:40px">
                                    <input type="checkbox" class="ui-form-check-input" id="welfareAll" aria-label="Tick all">
                                </th>
                                <th>Teacher</th>
                                <th class="tw:text-right!">Earnings</th>
                                <th class="tw:text-right!">Net salary</th>
                                <th class="tw:text-right!" style="width:170px">Welfare (LKR)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>
                                        @if ($row['can_take'])
                                            <input type="checkbox" class="ui-form-check-input welfare-pick" name="teachers[]"
                                                value="{{ $row['teacher_id'] }}" aria-label="Take from {{ $row['name'] }}"
                                                {{ ($row['welfare_amount'] ?? 0) > 0 ? 'checked' : '' }}>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="cell-title">{{ $row['name'] }}</div>
                                        <small class="cell-sub">{{ $row['custom_id'] }}</small>
                                    </td>
                                    <td class="tw:text-right!">{{ number_format($row['gross'], 2) }}</td>
                                    <td class="tw:text-right!">{{ number_format($row['net'], 2) }}</td>
                                    <td class="tw:text-right!">
                                        @if ($row['can_take'])
                                            <input type="number" step="0.01" min="0" class="ui-form-control ui-form-control-sm tw:text-right!"
                                                name="amounts[{{ $row['teacher_id'] }}]"
                                                value="{{ old('amounts.' . $row['teacher_id'], $row['welfare_amount']) }}"
                                                placeholder="0.00" aria-label="Welfare amount for {{ $row['name'] }}">
                                        @else
                                            <strong>{{ number_format($row['taken'], 2) }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row['deduction_id'])
                                            <span class="nx-badge nx-badge-green">Taken</span>
                                            @if ($row['can_cancel'])
                                                <button type="submit" form="cancelDeduction{{ $row['deduction_id'] }}"
                                                    class="ui-btn ui-btn-link ui-btn-sm tw:text-(color:--nx-red)! tw:p-0! tw:ml-2!">Cancel</button>
                                            @endif
                                        @elseif ($row['block_reason'])
                                            <span class="tw:text-(color:--nx-muted)! tw:text-[.875em]">{{ $row['block_reason'] }}</span>
                                        @else
                                            <span class="tw:text-(color:--nx-muted)! tw:text-[.875em]">Not taken</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="tw:text-center! tw:text-(color:--nx-muted)! tw:py-6!">No teachers in this branch.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($rows->contains('can_take', true))
                    <div class="tw:p-4! tw:text-right!">
                        <button type="submit" class="nx-btn nx-btn-primary"
                            onclick="return confirm('Take welfare from the ticked teachers for {{ $monthLabel }}?')">
                            <i class="bi bi-check2-circle"></i> Take welfare
                        </button>
                    </div>
                @endif
            </form>

            @foreach ($rows->where('can_cancel', true) as $row)
                <form method="POST" action="{{ route('welfare.deductions.cancel', $row['deduction_id']) }}"
                    id="cancelDeduction{{ $row['deduction_id'] }}" class="tw:hidden!"
                    onsubmit="return confirm('Cancel the welfare of {{ e($row['name']) }}? It is added back to the salary.')">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        </div>

        {{-- ================= Monthly amounts ================= --}}
        <div class="nx-card tw:mb-6!">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-sliders tw:text-(color:--nx-blue)!"></i> Monthly welfare amount of each teacher</h5>
                    <p class="nx-card-subtitle">
                        Used as the amount above every month{{ $branchName ? ' (' . $branchName . ')' : '' }}.
                        Leave empty for teachers who do not pay welfare.
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('welfare.amounts') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                <div class="tw:p-4!">
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2">
                        @foreach ($rows as $row)
                            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[33.33333333%] tw:xl:flex-[0_0_auto] tw:xl:w-[25%]">
                                <label class="nx-label" for="amount{{ $row['teacher_id'] }}">{{ $row['name'] }}</label>
                                <input type="number" step="0.01" min="0" class="ui-form-control nx-input" id="amount{{ $row['teacher_id'] }}"
                                    name="amounts[{{ $row['teacher_id'] }}]" value="{{ $row['welfare_amount'] }}" placeholder="No welfare">
                            </div>
                        @endforeach
                    </div>
                    @if ($rows->isNotEmpty())
                        <button type="submit" class="nx-btn nx-btn-primary tw:mt-4!"><i class="bi bi-save"></i> Save amounts</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const all = document.getElementById('welfareAll');
        if (!all) return;
        all.addEventListener('change', () => {
            document.querySelectorAll('.welfare-pick').forEach((box) => { box.checked = all.checked; });
        });
    })();
</script>
@endpush
