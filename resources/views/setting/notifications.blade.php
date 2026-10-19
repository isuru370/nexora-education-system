@extends('layouts.app')

@section('title', 'Notification Settings')
@section('page-title', 'Notification Settings')

@php
    $icons = [
        'registration' => 'bi-person-plus-fill',
        'payment' => 'bi-cash-coin',
        'admission_payment' => 'bi-receipt',
        'attendance' => 'bi-clipboard-check-fill',
        'class_schedule' => 'bi-calendar-event-fill',
    ];
    $iconColors = ['icon-blue', 'icon-green', 'icon-violet', 'icon-amber', 'icon-red'];
@endphp

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge"><i class="bi bi-bell-fill"></i> Settings</span>
                <h1 class="hero-title">Parent Notifications</h1>
                <p class="hero-subtitle">Choose what parents are told, and whether it goes to the parent app or by SMS.</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('admin.setting.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Settings
                    </a>
                </div>
                <small class="hero-updated">Applies to all branches</small>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if (session('success'))
        <div class="nx-alert nx-alert-green mb-4">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.setting.notifications.update') }}">
        @csrf
        @method('PUT')

        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-sliders text-primary"></i> When to notify parents</h5>
                    <p class="nx-card-subtitle">Parent app = free push notification. SMS = reaches every parent, uses SMS credit.</p>
                </div>
                <button type="submit" class="nx-btn nx-btn-primary">
                    <i class="bi bi-save"></i> Save Settings
                </button>
            </div>

            @forelse ($settings as $setting)
                @php
                    $key = $setting->key;
                    $enabled = (bool) old("settings.{$key}.is_enabled", $setting->is_enabled);
                    $channel = old("settings.{$key}.channel", $setting->channel);
                @endphp

                <div class="notify-row {{ $enabled ? '' : 'is-off' }}" data-row>
                    <div class="stat-icon-wrapper {{ $iconColors[$loop->index % count($iconColors)] }} notify-icon">
                        <i class="bi {{ $icons[$key] ?? 'bi-bell-fill' }}"></i>
                    </div>

                    <div class="notify-text">
                        <div class="cell-title">{{ $setting->label }}</div>
                        <small class="cell-sub d-block">{{ $setting->description }}</small>
                        @if ($setting->updatedBy)
                            <small class="cell-sub notify-meta">
                                Changed by {{ $setting->updatedBy->name }}, {{ $setting->updated_at?->diffForHumans() }}
                            </small>
                        @endif
                    </div>

                    <div class="notify-channel">
                        @if ($setting->channel_locked)
                            <input type="hidden" name="settings[{{ $key }}][channel]" value="{{ $setting->channel }}">
                            <span class="nx-badge nx-badge-blue">
                                <i class="bi bi-chat-dots-fill"></i> SMS only
                            </span>
                            <small class="cell-sub d-block mt-1">Sends the portal login</small>
                        @else
                            <div class="channel-toggle" role="radiogroup" aria-label="{{ $setting->label }} channel">
                                <input type="radio" class="btn-check" name="settings[{{ $key }}][channel]"
                                    id="{{ $key }}_push" value="push" {{ $channel === 'push' ? 'checked' : '' }}>
                                <label for="{{ $key }}_push"><i class="bi bi-phone"></i> Parent app</label>

                                <input type="radio" class="btn-check" name="settings[{{ $key }}][channel]"
                                    id="{{ $key }}_sms" value="sms" {{ $channel === 'sms' ? 'checked' : '' }}>
                                <label for="{{ $key }}_sms"><i class="bi bi-chat-dots"></i> SMS</label>
                            </div>
                        @endif
                    </div>

                    <div class="notify-switch">
                        <input type="hidden" name="settings[{{ $key }}][is_enabled]" value="0">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" data-switch
                                id="{{ $key }}_enabled" name="settings[{{ $key }}][is_enabled]" value="1"
                                {{ $enabled ? 'checked' : '' }}>
                            <label class="form-check-label cell-sub" for="{{ $key }}_enabled" data-switch-label>
                                {{ $enabled ? 'On' : 'Off' }}
                            </label>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-table">
                    <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                    <strong class="d-block">No notification settings yet</strong>
                    <span class="cell-sub">Run <code>php artisan migrate</code> (or <code>php artisan nexora:deploy</code>).</span>
                </div>
            @endforelse
        </div>
    </form>

    <div class="nx-alert nx-alert-blue">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            <strong>Good to know</strong>
            <ul class="mb-0 mt-1 ps-3">
                <li>Parent app notifications only reach parents who installed the app and logged in.</li>
                <li>SMS is sent by the queue worker: <code>php artisan queue:work --queue=sms,default</code> must be running.</li>
                <li>A failed notification never stops a registration, payment or attendance from being saved.</li>
            </ul>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    .dashboard-page .notify-row {
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr) auto 120px;
        gap: 1rem;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--nx-border);
        transition: opacity .15s ease;
    }
    .dashboard-page .notify-row:last-child { border-bottom: 0; }
    .dashboard-page .notify-row.is-off .notify-text,
    .dashboard-page .notify-row.is-off .notify-channel,
    .dashboard-page .notify-row.is-off .notify-icon { opacity: .45; }

    .dashboard-page .notify-icon { width: 44px; height: 44px; font-size: 1.1rem; }
    .dashboard-page .notify-text .cell-title { font-size: .95rem; }
    .dashboard-page .notify-meta { display: block; margin-top: .25rem; font-size: .72rem; }

    .dashboard-page .channel-toggle {
        display: inline-flex;
        padding: 3px;
        border-radius: 10px;
        background: var(--nx-head-bg);
        border: 1px solid var(--nx-border);
    }
    .dashboard-page .channel-toggle label {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .8rem;
        border-radius: 8px;
        font-size: .8rem;
        font-weight: 700;
        color: var(--nx-muted);
        cursor: pointer;
        white-space: nowrap;
        margin: 0;
    }
    .dashboard-page .channel-toggle .btn-check:checked + label {
        background: var(--nx-card-bg);
        color: var(--nx-blue);
        box-shadow: var(--nx-shadow);
    }
    .dashboard-page .channel-toggle .btn-check:focus-visible + label {
        outline: 2px solid var(--nx-blue);
        outline-offset: 1px;
    }
    .dashboard-page .channel-toggle .btn-check:disabled + label { cursor: not-allowed; }

    .dashboard-page .notify-switch { justify-self: end; }
    .dashboard-page .notify-switch .form-check-input { width: 2.6em; height: 1.35em; cursor: pointer; }
    .dashboard-page .notify-switch .form-check-label { min-width: 26px; font-weight: 700; }

    @media (max-width: 768px) {
        .dashboard-page .notify-row {
            grid-template-columns: 44px minmax(0, 1fr) auto;
            grid-template-areas: "icon text switch" "icon channel channel";
        }
        .dashboard-page .notify-icon { grid-area: icon; align-self: start; }
        .dashboard-page .notify-text { grid-area: text; }
        .dashboard-page .notify-switch { grid-area: switch; align-self: start; }
        .dashboard-page .notify-channel { grid-area: channel; }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-row]').forEach(function (row) {
            var toggle = row.querySelector('[data-switch]');
            var label = row.querySelector('[data-switch-label]');
            if (!toggle) return;

            function refresh() {
                row.classList.toggle('is-off', !toggle.checked);
                label.textContent = toggle.checked ? 'On' : 'Off';
            }

            toggle.addEventListener('change', refresh);
            refresh();
        });
    });
</script>
@endpush
