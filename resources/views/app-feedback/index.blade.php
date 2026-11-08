@extends('layouts.app')

@section('title', 'App Feedback')
@section('page-title', 'App Feedback')

@section('content')
    @php
        $typeBadge = ['bug' => ['nx-badge-red', 'bi-bug'], 'idea' => ['nx-badge-blue', 'bi-lightbulb'], 'other' => ['nx-badge-amber', 'bi-chat-dots']];
        $statusBadge = ['new' => 'nx-badge-red', 'seen' => 'nx-badge-amber', 'done' => 'nx-badge-green'];
        $link = fn (array $q) => route('app-feedback.index', array_filter(array_merge(['status' => $status, 'type' => $type], $q)));
    @endphp

    <div class="dashboard-page">
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">Staff app</span>
                    <h1 class="hero-title">App Feedback</h1>
                    <p class="hero-subtitle">
                        Sent from the staff app (menu &gt; Send Feedback). Each one is also emailed to Nexora IT
                        ({{ config('services.nexora.feedback_email') ?: 'email off' }}).
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">{{ (int) ($counts['new'] ?? 0) }} new</small>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="nx-alert nx-alert-green mb-3"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
        @endif

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ $link(['status' => null]) }}" class="nx-btn {{ !$status ? 'nx-btn-primary' : 'nx-btn-light' }} btn-sm">All</a>
            @foreach (\App\Models\AppFeedback::STATUSES as $key => $label)
                <a href="{{ $link(['status' => $key]) }}" class="nx-btn {{ $status === $key ? 'nx-btn-primary' : 'nx-btn-light' }} btn-sm">
                    {{ $label }} <strong>{{ (int) ($counts[$key] ?? 0) }}</strong>
                </a>
            @endforeach
            <span class="mx-2"></span>
            @foreach (\App\Models\AppFeedback::TYPES as $key => $label)
                <a href="{{ $link(['type' => $type === $key ? null : $key]) }}" class="nx-btn {{ $type === $key ? 'nx-btn-primary' : 'nx-btn-light' }} btn-sm">
                    <i class="bi {{ $typeBadge[$key][1] }}"></i> {{ $label }}
                </a>
            @endforeach
        </div>

        @forelse ($feedback as $item)
            <div class="nx-card mb-3">
                <div class="p-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <div>
                            <span class="nx-badge {{ $typeBadge[$item->type][0] ?? 'nx-badge-blue' }}"><i class="bi {{ $typeBadge[$item->type][1] ?? 'bi-chat' }}"></i> {{ \App\Models\AppFeedback::TYPES[$item->type] ?? $item->type }}</span>
                            <span class="nx-badge {{ $statusBadge[$item->status] ?? '' }}">{{ \App\Models\AppFeedback::STATUSES[$item->status] ?? $item->status }}</span>
                            <strong class="ms-1">{{ $item->user->name }}</strong>
                            <small class="cell-sub">{{ optional($item->user->userType)->code }}</small>
                        </div>
                        <small class="cell-sub">
                            {{ $item->created_at->timezone('Asia/Colombo')->format('d M Y, h:i A') }}
                            &middot; v{{ $item->app_version ?: '?' }} &middot; {{ $item->device ?: '-' }}
                            @if ($item->emailed_at) &middot; <i class="bi bi-envelope-check" title="Emailed"></i> @endif
                        </small>
                    </div>

                    <div class="mb-3" style="white-space: pre-wrap;">{{ $item->message }}</div>

                    @if ($item->screenshot_path)
                        <a href="{{ asset('storage/' . $item->screenshot_path) }}" target="_blank" rel="noopener" class="d-inline-block mb-3">
                            <img src="{{ asset('storage/' . $item->screenshot_path) }}" alt="Screenshot" style="max-height: 180px; border-radius: 8px; border: 1px solid rgba(15,23,42,.12);">
                        </a>
                    @endif

                    <form method="POST" action="{{ route('app-feedback.update', $item) }}" class="d-flex flex-wrap gap-2 align-items-center">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="form-select form-select-sm" style="width: 120px;" aria-label="Status">
                            @foreach (\App\Models\AppFeedback::STATUSES as $key => $label)
                                <option value="{{ $key }}" {{ $item->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input name="admin_note" value="{{ $item->admin_note }}" class="form-control form-control-sm flex-grow-1" style="min-width: 200px;" maxlength="2000" placeholder="Note (what was done)" aria-label="Note">
                        <button class="nx-btn nx-btn-primary btn-sm" type="submit">Save</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="nx-card p-5 text-center text-muted"><i class="bi bi-chat-square-heart"></i> No feedback yet.</div>
        @endforelse

        @if ($feedback->hasPages())
            <div class="mt-3">{{ $feedback->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>
@endsection
