@extends('layouts.app')

@section('title', 'Payment Rules')
@section('page-title', 'Payment Rules')

@section('content')
    @php
        $tierRows = collect(old('tiers', collect($rules['tiers'])->map(fn ($percent, $count) => ['attendance' => $count, 'percent' => $percent])->values()->all()));
    @endphp

    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Payment Rules</h1>
                    <p class="hero-subtitle">
                        Class fee options for this institute. They change the payment screens on the web and in the
                        staff app. Everything is off by default.
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">Super Admin only</small>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('payment-rules.update') }}">
            @csrf
            @method('PUT')

            {{-- 1. Custom amount --}}
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-pencil-square text-primary"></i> Cashier can type the amount</h5>
                        <p class="nx-card-subtitle">
                            When the cashier takes less than the fee, the rest is saved as a discount and the month is paid.
                        </p>
                    </div>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input type="hidden" name="custom_amount" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="custom_amount" value="1"
                            id="custom_amount" @checked(old('custom_amount', $rules['custom_amount']))>
                    </div>
                </div>
            </div>

            {{-- 2. Attendance rule --}}
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-calendar-check text-success"></i> Fee by attendance</h5>
                        <p class="nx-card-subtitle">
                            The fee for a month follows how many times the student attended that class in that month.
                            Counts not listed pay the full fee. The cashier can still change the amount when
                            "Cashier can type the amount" is on.
                        </p>
                    </div>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input type="hidden" name="attendance_rule" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="attendance_rule" value="1"
                            id="attendance_rule" @checked(old('attendance_rule', $rules['attendance_rule']))>
                    </div>
                </div>
                <div class="p-3">
                    <table class="table align-middle mb-2" style="max-width: 520px">
                        <thead>
                            <tr>
                                <th>Attendance in the month</th>
                                <th>Fee %</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tierRows">
                            @foreach ($tierRows as $i => $row)
                                <tr>
                                    <td>
                                        <input type="number" class="form-control" min="0" max="60"
                                            name="tiers[{{ $i }}][attendance]" value="{{ $row['attendance'] }}" required>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" class="form-control" min="0" max="100"
                                                name="tiers[{{ $i }}][percent]" value="{{ $row['percent'] }}" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-light border remove-tier" title="Remove">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="nx-btn nx-btn-light" id="addTier">
                        <i class="bi bi-plus-lg"></i> Add row
                    </button>
                    <div class="small text-muted mt-2">
                        Example: 0 → 0 % (no fee), 1 → 50 %, 2 or more → full fee. A 0 % month is not shown as unpaid
                        and gets no payment reminder.
                    </div>
                </div>
            </div>

            {{-- 3. Tute --}}
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-journal-text text-warning"></i> Tute with the payment</h5>
                        <p class="nx-card-subtitle">
                            The payment screen shows whether that month's tute was given, with an optional
                            "Give tute" tick.
                        </p>
                    </div>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input type="hidden" name="tute" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="tute" value="1"
                            id="tute" @checked(old('tute', $rules['tute']))>
                    </div>
                </div>
            </div>

            <button type="submit" class="nx-btn nx-btn-primary">
                <i class="bi bi-check-circle"></i> Save
            </button>
        </form>
    </div>

    <template id="tierTemplate">
        <tr>
            <td><input type="number" class="form-control" min="0" max="60" data-name="attendance" required></td>
            <td>
                <div class="input-group">
                    <input type="number" class="form-control" min="0" max="100" data-name="percent" value="100" required>
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-light border remove-tier" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        (function () {
            const body = document.getElementById('tierRows');
            const template = document.getElementById('tierTemplate');
            let next = {{ $tierRows->count() }};

            document.getElementById('addTier').addEventListener('click', function () {
                const row = template.content.firstElementChild.cloneNode(true);
                row.querySelectorAll('[data-name]').forEach(function (input) {
                    input.name = 'tiers[' + next + '][' + input.dataset.name + ']';
                });
                next++;
                body.appendChild(row);
            });

            body.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-tier');
                if (button) button.closest('tr').remove();
            });
        })();
    </script>
@endpush
