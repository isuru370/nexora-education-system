@extends('layouts.app')

@section('title', 'Teacher App')
@section('page-title', 'Teacher App')

@section('content')
    @php
        $cards = [
            'attendance' => [
                'icon' => 'bi-qr-code-scan text-success',
                'title' => 'Mark attendance',
                'text' => 'Teachers mark attendance in their own classes and courses for today, by scanning the student QR or ticking names in the list. Parents get the usual attendance message.',
            ],
            'schedule' => [
                'icon' => 'bi-calendar2-week text-primary',
                'title' => 'Change class days',
                'text' => 'Teachers can change the date, time or hall of their own class day, add an extra day or cancel a day that has not started. Parents are told by SMS / notification.',
            ],
            'schedule_auto' => [
                'icon' => 'bi-lightning-charge text-warning',
                'title' => 'Apply class day changes at once',
                'text' => 'On: a teacher\'s change is done straight away. Off: it waits until an admin approves it under Teacher Requests.',
            ],
            'payments' => [
                'icon' => 'bi-wallet2 text-info',
                'title' => 'See own payments',
                'text' => 'Teachers see their earnings by class, salary, salary history, advances, deductions and welfare, course earnings, and which students paid or did not pay (no phone numbers).',
            ],
        ];
    @endphp

    <div class="dashboard-page">
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Teacher App</h1>
                    <p class="hero-subtitle">
                        What teachers can do in the staff app. Everything is off until you turn it on.
                        A teacher sees only their own classes, courses and money.
                    </p>
                </div>
                <div class="hero-right">
                    <a href="{{ route('admin.setting.teacher-requests') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-inbox"></i> Teacher Requests
                        @if ($pendingCount > 0)
                            <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.setting.teacher-app.update') }}">
            @csrf
            @method('PUT')

            @foreach ($cards as $key => $card)
                <div class="nx-card mb-3 {{ $key === 'schedule_auto' ? 'ms-md-4' : '' }}">
                    <div class="nx-card-header">
                        <div>
                            <h5 class="nx-card-title"><i class="bi {{ $card['icon'] }}"></i> {{ $card['title'] }}</h5>
                            <p class="nx-card-subtitle">{{ $card['text'] }}</p>
                        </div>
                        <div class="form-check form-switch fs-4 mb-0">
                            <input type="hidden" name="{{ $key }}" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="{{ $key }}" value="1"
                                id="{{ $key }}" {{ old($key, $rules[$key]) ? 'checked' : '' }}>
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
