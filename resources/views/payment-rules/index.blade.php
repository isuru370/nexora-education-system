@extends('layouts.app')

@section('title', 'Payment & Attendance Rules')
@section('page-title', 'Payment & Attendance Rules')

@section('content')
    @php
        $attendance = \App\Support\AttendanceRules::all();
        $cards = [
            'fee_option' => [
                'icon' => 'bi-list-check text-primary',
                'title' => 'Choose the fee option at payment',
                'text' => 'The payment screen lists the fee options of the student\'s class category (e.g. Normal 2,000 / Half 1,000). The cashier picks one for that month, looking at the attendance count. The student\'s own option does not change; the part not paid is saved as a discount.',
            ],
            'custom_amount' => [
                'icon' => 'bi-pencil-square text-primary',
                'title' => 'Cashier can type the amount',
                'text' => 'Any amount can be taken. Less than the fee is saved as a discount and the month is paid.',
            ],
            'no_attendance' => [
                'icon' => 'bi-calendar-x text-secondary',
                'title' => 'No attendance = no payment',
                'text' => 'A student who did not attend a class in a month is shown as "No Attendance" (not unpaid) in the monthly report and gets no payment reminder. The payment screen warns the cashier.',
            ],
            'tute' => [
                'icon' => 'bi-journal-text text-warning',
                'title' => 'Tute with the payment',
                'text' => 'The payment screen shows whether that month\'s tute was given, with an optional "Give tute" tick.',
            ],
        ];
    @endphp

    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Payment &amp; Attendance Rules</h1>
                    <p class="hero-subtitle">
                        Class fee options for this institute. They change the payment screens on the web and in the
                        staff app. Everything is off by default. The attendance count for the month is always shown.
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

        <h5 class="mb-3"><i class="bi bi-cash-coin text-primary"></i> Payments</h5>

        <form method="POST" action="{{ route('payment-rules.update') }}">
            @csrf
            @method('PUT')

            @foreach ($cards as $key => $card)
                <div class="nx-card mb-3">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi {{ $card['icon'] }}"></i> {{ $card['title'] }}</h5>
                            <p class="nx-card-subtitle">{{ $card['text'] }}</p>
                        </div>
                        <div class="form-check form-switch fs-4 mb-0">
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="{{ $key }}" value="1"
                                id="{{ $key }}" {{ (old($key, $rules[$key])) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            @endforeach

            <h5 class="mt-4 mb-3"><i class="bi bi-qr-code-scan text-success"></i> Attendance</h5>

            <div class="nx-card mb-3">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-clock text-success"></i> When QR attendance can be marked</h5>
                        <p class="nx-card-subtitle">
                            Example with 120 / 120: a 4:00 - 6:00 PM class can be marked from 2:00 PM to 8:00 PM.
                            Default: 60 minutes before the start until the end.
                        </p>
                    </div>
                </div>
                <div class="p-3">
                    <div class="row g-3" style="max-width: 560px">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="before_minutes">Minutes before the class starts</label>
                            <input type="number" class="form-control" id="before_minutes" name="before_minutes" min="0" max="720"
                                value="{{ old('before_minutes', $attendance['before_minutes']) }}">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold" for="after_minutes">Minutes after the class ends</label>
                            <input type="number" class="form-control" id="after_minutes" name="after_minutes" min="0" max="720"
                                value="{{ old('after_minutes', $attendance['after_minutes']) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="nx-card mb-3">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-list-ul text-success"></i> No time limit (pick the class)</h5>
                        <p class="nx-card-subtitle">
                            Ignore the times above. A scan lists the student's classes today and the cashier picks one
                            (a single class today is marked straight away). Only today's classes.
                        </p>
                    </div>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input type="hidden" name="no_time_limit" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="no_time_limit" value="1"
                            id="no_time_limit" {{ (old('no_time_limit', $attendance['no_time_limit'])) ? 'checked' : '' }}>
                    </div>
                </div>
            </div>

            <button type="submit" class="nx-btn nx-btn-primary mt-2">
                <i class="bi bi-check-circle"></i> Save
            </button>
        </form>
    </div>
@endsection
