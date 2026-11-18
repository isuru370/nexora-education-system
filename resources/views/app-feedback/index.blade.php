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
        <div class="hero-section tw:mb-6!">
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
            <div class="nx-alert nx-alert-green tw:mb-4!"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
        @endif

        <div class="tw:flex! tw:flex-wrap! tw:gap-2! tw:mb-4!">
            <a href="{{ $link(['status' => null]) }}" class="nx-btn ui-btn-sm {{ !$status ? 'nx-btn-primary' : 'nx-btn-light' }}">All</a>
            @foreach (\App\Models\AppFeedback::STATUSES as $key => $label)
                <a href="{{ $link(['status' => $key]) }}" class="nx-btn ui-btn-sm {{ $status === $key ? 'nx-btn-primary' : 'nx-btn-light' }}">
                    {{ $label }} <strong>{{ (int) ($counts[$key] ?? 0) }}</strong>
                </a>
            @endforeach
            <span class="tw:mx-2!"></span>
            @foreach (\App\Models\AppFeedback::TYPES as $key => $label)
                <a href="{{ $link(['type' => $type === $key ? null : $key]) }}" class="nx-btn ui-btn-sm {{ $type === $key ? 'nx-btn-primary' : 'nx-btn-light' }}">
                    <i class="bi {{ $typeBadge[$key][1] }}"></i> {{ $label }}
                </a>
            @endforeach
        </div>

        @forelse ($feedback as $item)
            <div class="nx-card tw:mb-4!">
                <div class="tw:p-4!">
                    <div class="tw:flex! tw:flex-wrap! tw:justify-between! tw:gap-2! tw:mb-2!">
                        <div>
                            <span class="nx-badge {{ $typeBadge[$item->type][0] ?? 'nx-badge-blue' }}"><i class="bi {{ $typeBadge[$item->type][1] ?? 'bi-chat' }}"></i> {{ \App\Models\AppFeedback::TYPES[$item->type] ?? $item->type }}</span>
                            <span class="nx-badge {{ $statusBadge[$item->status] ?? '' }}">{{ \App\Models\AppFeedback::STATUSES[$item->status] ?? $item->status }}</span>
                            <strong class="tw:ml-1!">{{ $item->user->name }}</strong>
                            <small class="cell-sub">{{ optional($item->user->userType)->code }}</small>
                        </div>
                        <small class="cell-sub">
                            {{ $item->created_at->timezone('Asia/Colombo')->format('d M Y, h:i A') }}
                            &middot; v{{ $item->app_version ?: '?' }} &middot; {{ $item->device ?: '-' }}
                            @if ($item->emailed_at) &middot; <i class="bi bi-envelope-check" title="Emailed"></i> @endif
                        </small>
                    </div>

                    <div class="tw:mb-4!" style="white-space: pre-wrap;">{{ $item->message }}</div>

                    @if ($item->screenshot_path)
                        <a href="{{ asset('storage/' . $item->screenshot_path) }}" target="_blank" rel="noopener" class="tw:[display:inline-block]! tw:mb-4!">
                            <img src="{{ asset('storage/' . $item->screenshot_path) }}" alt="Screenshot" style="max-height: 180px; border-radius: 8px; border: 1px solid rgba(15,23,42,.12);">
                        </a>
                    @endif

                    <form method="POST" action="{{ route('app-feedback.update', $item) }}" class="tw:flex! tw:flex-wrap! tw:gap-2! tw:items-center!">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="ui-form-select ui-form-select-sm" style="width: 120px;" aria-label="Status">
                            @foreach (\App\Models\AppFeedback::STATUSES as $key => $label)
                                <option value="{{ $key }}" {{ $item->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input name="admin_note" value="{{ $item->admin_note }}" class="ui-form-control ui-form-control-sm tw:grow!" style="min-width: 200px;" maxlength="2000" placeholder="Note (what was done)" aria-label="Note">
                        <button class="nx-btn nx-btn-primary ui-btn-sm" type="submit">Save</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="nx-card tw:p-12! tw:text-center! tw:text-(color:--nx-muted)!"><i class="bi bi-chat-square-heart"></i> No feedback yet.</div>
        @endforelse

        @if ($feedback->hasPages())
            <div class="tw:mt-4!">{{ $feedback->links('pagination::bootstrap-4') }}</div>
        @endif
    </div>
@endsection
