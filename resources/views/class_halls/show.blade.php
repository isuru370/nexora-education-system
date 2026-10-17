@extends('layouts.app')

@section('title', 'Class Hall Details')
@section('page-title', 'Class Hall Details')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">
                    <i class="bi bi-door-open"></i> Class Hall
                </span>
                <h1 class="hero-title">{{ $classHall->hall_name }}</h1>
                <p class="hero-subtitle">{{ $classHall->code }}</p>

                <div class="hero-stats-mini">
                    <div class="mini-stat">
                        <span class="nx-badge {{ $classHall->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                            <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                            {{ $classHall->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    @if ((float) $classHall->hall_price == 0)
                        <div class="mini-stat-divider"></div>
                        <div class="mini-stat">
                            <span class="nx-badge nx-badge-green">Free</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-halls.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <a href="{{ route('class-halls.edit', $classHall) }}" class="nx-btn nx-btn-primary">
                        <i class="bi bi-pencil-square"></i> Edit
                    </a>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
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

    @if (session('error'))
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- INFO GRID --}}
    <div class="row g-4 mb-4">

        {{-- HALL INFO --}}
        <div class="col-md-6">
            <div class="nx-card h-100">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-info-circle-fill text-primary"></i> Hall Information</h5>
                        <p class="nx-card-subtitle">Details of this hall</p>
                    </div>
                </div>

                <div class="p-3">
                    <div class="detail-line">
                        <span class="detail-label">Code</span>
                        <span class="detail-value" style="font-family: ui-monospace, monospace;">{{ $classHall->code }}</span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Hall Name</span>
                        <span class="detail-value">{{ $classHall->hall_name }}</span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Hall Type</span>
                        <span class="detail-value">{{ $classHall->hall_type ?? '-' }}</span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Price</span>
                        <span class="detail-value">
                            @if ((float) $classHall->hall_price == 0)
                                <span class="nx-badge nx-badge-green">Free</span>
                            @else
                                Rs. {{ number_format($classHall->hall_price, 2) }}
                            @endif
                        </span>
                    </div>
                    <div class="detail-line" style="border-bottom: 0;">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="nx-badge {{ $classHall->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                                <i class="bi bi-circle-fill" style="font-size: .4rem;"></i>
                                {{ $classHall->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SYSTEM INFO --}}
        <div class="col-md-6">
            <div class="nx-card h-100">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-gear-fill text-primary"></i> System Information</h5>
                        <p class="nx-card-subtitle">Metadata for this record</p>
                    </div>
                </div>

                <div class="p-3">
                    <div class="detail-line">
                        <span class="detail-label">Created At</span>
                        <span class="detail-value">{{ $classHall->created_at?->format('Y-m-d h:i A') }}</span>
                    </div>
                    <div class="detail-line">
                        <span class="detail-label">Updated At</span>
                        <span class="detail-value">{{ $classHall->updated_at?->format('Y-m-d h:i A') }}</span>
                    </div>
                    <div class="detail-line" style="border-bottom: 0;">
                        <span class="detail-label">Schedules Count</span>
                        <span class="detail-value" style="font-family: ui-monospace, monospace;">
                            {{ $classHall->schedules()->count() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    .dashboard-page .detail-line {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: .7rem 0;
        border-bottom: 1px solid var(--nx-border);
        gap: 1rem;
    }
    .dashboard-page .detail-line:last-child { border-bottom: 0; }

    .dashboard-page .detail-label {
        flex-shrink: 0;
        font-size: .72rem;
        font-weight: 700;
        color: var(--nx-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .dashboard-page .detail-value {
        flex: 1;
        text-align: right;
        font-size: .85rem;
        font-weight: 600;
        color: var(--nx-text);
        word-break: break-word;
    }
</style>
@endpush