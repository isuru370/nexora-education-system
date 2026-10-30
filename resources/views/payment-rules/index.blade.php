@extends('layouts.app')

@section('title', 'Payment Rules')
@section('page-title', 'Payment Rules')

@section('content')
    @php
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
                    <h1 class="hero-title">Payment Rules</h1>
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
                                id="{{ $key }}" @checked(old($key, $rules[$key]))>
                        </div>
                    </div>
                </div>
            @endforeach

            <button type="submit" class="nx-btn nx-btn-primary mt-2">
                <i class="bi bi-check-circle"></i> Save
            </button>
        </form>
    </div>
@endsection
