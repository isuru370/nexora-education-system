@extends('layouts.app')

@section('title', 'Payment & Attendance Rules')
@section('page-title', 'Payment & Attendance Rules')

@section('content')
    @php
        $attendance = \App\Support\AttendanceRules::all();
        $cards = [
            'fee_option' => [
                'icon' => 'bi-list-check tw:text-(color:--nx-blue)!',
                'title' => 'Choose the fee option at payment',
                'text' => 'The payment screen lists the fee options of the student\'s class category (e.g. Normal 2,000 / Half 1,000). The cashier picks one for that month, looking at the attendance count. The student\'s own option does not change; the part not paid is saved as a discount.',
            ],
            'custom_amount' => [
                'icon' => 'bi-pencil-square tw:text-(color:--nx-blue)!',
                'title' => 'Cashier can type the amount',
                'text' => 'Any amount can be taken. Less than the fee is saved as a discount and the month is paid.',
            ],
            'no_attendance' => [
                'icon' => 'bi-calendar-x tw:text-bs-secondary!',
                'title' => 'No attendance = no payment',
                'text' => 'A student who did not attend a class in a month is shown as "No Attendance" (not unpaid) in the monthly report and gets no payment reminder. The payment screen warns the cashier.',
            ],
            'tute' => [
                'icon' => 'bi-journal-text tw:text-(color:--nx-amber)!',
                'title' => 'Tute with the payment',
                'text' => 'The payment screen shows whether that month\'s tute was given, with an optional "Give tute" tick.',
            ],
            'print_receipt' => [
                'icon' => 'bi-printer tw:text-(color:--nx-green)!',
                'title' => 'Print receipt after payment',
                'text' => 'The staff app prints a receipt after a class fee payment. Turn off to save the payment without printing. A receipt can still be reprinted from Today Payments. On by default.',
            ],
        ];
    @endphp

    <div class="dashboard-page">

        <div class="hero-section tw:mb-6!">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Payment &amp; Attendance Rules</h1>
                    <p class="hero-subtitle">
                        Class fee options for this institute. They change the payment screens on the web and in the
                        staff app. Everything except receipt printing is off by default. The attendance count for the month is always shown.
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">Super Admin only</small>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="ui-alert ui-alert-danger">{{ $errors->first() }}</div>
        @endif

        <h5 class="tw:mb-4!"><i class="bi bi-cash-coin tw:text-(color:--nx-blue)!"></i> Payments</h5>

        <form method="POST" action="{{ route('payment-rules.update') }}">
            @csrf
            @method('PUT')

            @foreach ($cards as $key => $card)
                <div class="nx-card tw:mb-4!">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi {{ $card['icon'] }}"></i> {{ $card['title'] }}</h5>
                            <p class="nx-card-subtitle">{{ $card['text'] }}</p>
                        </div>
                        <div class="ui-form-check ui-form-switch tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]! tw:mb-0!">
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input class="ui-form-check-input" type="checkbox" role="switch" name="{{ $key }}" value="1"
                                id="{{ $key }}" {{ (old($key, $rules[$key])) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="nx-card tw:mb-4!">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-stack"></i> Multi-class discount</h5>
                        <p class="nx-card-subtitle">
                            A student who pays this many different classes at once gets this amount off the class fee
                            of each of them (the hall fee does not change). Theory and Revision count as 2 classes;
                            free cards and other branches do not count. The teacher's share is reduced by the discount.
                            Example: 4 classes, Rs 100 - a student paying 4 classes of Rs 2,000 pays Rs 1,900 for each.
                        </p>
                    </div>
                    <div class="ui-form-check ui-form-switch tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]! tw:mb-0!">
                        <input type="hidden" name="multi_class_enabled" value="0">
                        <input class="ui-form-check-input" type="checkbox" role="switch" name="multi_class_enabled" value="1"
                            id="multi_class_enabled" {{ old('multi_class_enabled', $multiClass['enabled']) ? 'checked' : '' }}>
                    </div>
                </div>
                <div class="tw:p-4!">
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2" style="max-width: 560px">
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">
                            <label class="ui-form-label tw:font-semibold!" for="multi_class_min">Classes paid at once (at least)</label>
                            <input type="number" class="ui-form-control" id="multi_class_min" name="multi_class_min" min="2" max="20"
                                value="{{ old('multi_class_min', $multiClass['min_classes']) }}">
                        </div>
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">
                            <label class="ui-form-label tw:font-semibold!" for="multi_class_amount">Discount per class (Rs)</label>
                            <input type="number" class="ui-form-control" id="multi_class_amount" name="multi_class_amount" min="1" step="0.01"
                                value="{{ old('multi_class_amount', $multiClass['amount'] > 0 ? $multiClass['amount'] : '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="tw:mt-6! tw:mb-4!"><i class="bi bi-qr-code-scan tw:text-(color:--nx-green)!"></i> Attendance</h5>

            <div class="nx-card tw:mb-4!">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-clock tw:text-(color:--nx-green)!"></i> When QR attendance can be marked</h5>
                        <p class="nx-card-subtitle">
                            Example with 120 / 120: a 4:00 - 6:00 PM class can be marked from 2:00 PM to 8:00 PM.
                            Default: 60 minutes before the start until the end.
                        </p>
                    </div>
                </div>
                <div class="tw:p-4!">
                    <div class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2" style="max-width: 560px">
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">
                            <label class="ui-form-label tw:font-semibold!" for="before_minutes">Minutes before the class starts</label>
                            <input type="number" class="ui-form-control" id="before_minutes" name="before_minutes" min="0" max="720"
                                value="{{ old('before_minutes', $attendance['before_minutes']) }}">
                        </div>
                        <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:sm:flex-[0_0_auto] tw:sm:w-[50%]">
                            <label class="ui-form-label tw:font-semibold!" for="after_minutes">Minutes after the class ends</label>
                            <input type="number" class="ui-form-control" id="after_minutes" name="after_minutes" min="0" max="720"
                                value="{{ old('after_minutes', $attendance['after_minutes']) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="nx-card tw:mb-4!">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-list-ul tw:text-(color:--nx-green)!"></i> No time limit (pick the class)</h5>
                        <p class="nx-card-subtitle">
                            Ignore the times above. A scan lists the student's classes today and the cashier picks one
                            (a single class today is marked straight away). Only today's classes.
                        </p>
                    </div>
                    <div class="ui-form-check ui-form-switch tw:text-[length:calc(1.275rem_+_0.3vw)]! tw:xl:text-[length:1.5rem]! tw:mb-0!">
                        <input type="hidden" name="no_time_limit" value="0">
                        <input class="ui-form-check-input" type="checkbox" role="switch" name="no_time_limit" value="1"
                            id="no_time_limit" {{ (old('no_time_limit', $attendance['no_time_limit'])) ? 'checked' : '' }}>
                    </div>
                </div>
            </div>

            <button type="submit" class="nx-btn nx-btn-primary tw:mt-2!">
                <i class="bi bi-check-circle"></i> Save
            </button>
        </form>
    </div>
@endsection
