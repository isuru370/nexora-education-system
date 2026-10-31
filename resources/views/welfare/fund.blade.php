@extends('layouts.app')

@section('title', 'Welfare Fund')
@section('page-title', 'Welfare Fund')

@section('content')
    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Welfare Fund</h1>
                    <p class="hero-subtitle">
                        {{ $month_label }}{{ $branch ? ' • ' . $branch->name : '' }}.
                        Money taken from teacher salaries for welfare and what was paid out of it.
                        This is not institute income.
                    </p>
                </div>
                <div class="hero-right d-flex flex-column align-items-end gap-2">
                    <small class="hero-updated">Fund balance now: <strong>LKR {{ number_format($balance, 2) }}</strong></small>
                    <div class="d-flex gap-2">
                        <a href="{{ route('welfare.index', ['branch_id' => $branchId, 'year' => $year, 'month' => $month]) }}"
                            class="nx-btn nx-btn-light"><i class="bi bi-heart-pulse"></i> Teacher Welfare</a>
                        <a href="{{ route('welfare.fund.pdf', ['branch_id' => $branchId, 'year' => $year, 'month' => $month]) }}"
                            class="nx-btn nx-btn-light"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                    </div>
                </div>
            </div>
        </div>

        @include('welfare.partials.filters', ['action' => route('welfare.fund')])

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="stats-grid mb-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
            @foreach ([
                ['Opening balance', $opening, 'bi-box-arrow-in-right', 'icon-violet'],
                ['Taken from salaries', $collected_total, 'bi-arrow-down-circle', 'icon-green'],
                ['Paid out', $expenses_total, 'bi-arrow-up-circle', 'icon-red'],
                ['Closing balance', $closing, 'bi-piggy-bank', 'icon-blue'],
            ] as [$label, $value, $icon, $color])
                <div class="nx-card stat-card">
                    <div class="stat-icon-wrapper {{ $color }}"><i class="bi {{ $icon }}"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">{{ $label }}</span>
                        <h2 class="stat-number">LKR {{ number_format($value, 2) }}</h2>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                {{-- ================= Expenses ================= --}}
                <div class="nx-card mb-4">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-arrow-up-circle text-danger"></i> Paid out ({{ $expenses->count() }})</h5>
                            <p class="nx-card-subtitle">By date paid. A wrong entry can be cancelled.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table premium-table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>For</th>
                                    <th>Paid to</th>
                                    <th class="text-end">Amount</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expenses as $expense)
                                    <tr>
                                        <td>{{ $expense->expense_date->format('d M Y') }}</td>
                                        <td>
                                            <div class="cell-title">{{ $expense->description }}</div>
                                            @if ($expense->note)
                                                <small class="cell-sub">{{ $expense->note }}</small>
                                            @endif
                                            <small class="cell-sub d-block">By {{ optional($expense->createdBy)->name ?? '-' }}</small>
                                        </td>
                                        <td>{{ $expense->paid_to ?: '-' }}</td>
                                        <td class="text-end cell-title">{{ number_format($expense->amount, 2) }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('welfare.expenses.cancel', $expense->id) }}"
                                                onsubmit="return confirm('Cancel this expense? The money goes back to the fund.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-sm text-danger p-0">Cancel</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Nothing paid out this month.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ================= Collected ================= --}}
                <div class="nx-card mb-4">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-arrow-down-circle text-success"></i> Taken from salaries ({{ $collected->count() }})</h5>
                            <p class="nx-card-subtitle">{{ $month_label }} salaries.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table premium-table mb-0">
                            <thead><tr><th>Teacher</th><th class="text-end">Amount</th></tr></thead>
                            <tbody>
                                @forelse ($collected as $item)
                                    <tr>
                                        <td>
                                            <div class="cell-title">{{ optional($item->teacher)->full_name }}</div>
                                            <small class="cell-sub">{{ optional($item->teacher)->custom_id }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-4">No welfare taken for this month.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                {{-- ================= Add expense ================= --}}
                <div class="nx-card">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi bi-plus-circle text-primary"></i> Pay out of the fund</h5>
                            <p class="nx-card-subtitle">Available: LKR {{ number_format($balance, 2) }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('welfare.expenses.store') }}" class="p-3">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <div class="mb-3">
                            <label class="nx-label" for="exp-date">Date</label>
                            <input type="date" class="form-control nx-input" id="exp-date" name="expense_date"
                                value="{{ old('expense_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="nx-label" for="exp-desc">For (what)</label>
                            <input type="text" class="form-control nx-input" id="exp-desc" name="description" maxlength="200"
                                value="{{ old('description') }}" placeholder="e.g. Funeral help - Mr. X" required>
                        </div>
                        <div class="mb-3">
                            <label class="nx-label" for="exp-to">Paid to (optional)</label>
                            <input type="text" class="form-control nx-input" id="exp-to" name="paid_to" maxlength="150"
                                value="{{ old('paid_to') }}">
                        </div>
                        <div class="mb-3">
                            <label class="nx-label" for="exp-amount">Amount (LKR)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control nx-input" id="exp-amount" name="amount"
                                value="{{ old('amount') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="nx-label" for="exp-note">Note (optional)</label>
                            <textarea class="form-control nx-input" id="exp-note" name="note" rows="2" maxlength="1000">{{ old('note') }}</textarea>
                        </div>
                        <button type="submit" class="nx-btn nx-btn-primary w-100"><i class="bi bi-check-circle"></i> Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
