@extends('layouts.app')

@section('title', 'Bulk Update Schedule')
@section('page-title', 'Bulk Update Schedule')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Bulk Schedule</span>
                <h1 class="hero-title">{{ $studentClass->class_name ?? 'Class' }}</h1>
                <p class="hero-subtitle">
                    <i class="bi bi-person-badge"></i> {{ optional($studentClass->teacher)->full_name ?? 'N/A' }}
                    • <i class="bi bi-book"></i> {{ optional($studentClass->subject)->subject_name ?? 'N/A' }}
                    • <i class="bi bi-mortarboard"></i> {{ optional($studentClass->grade)->grade_name ?? 'N/A' }}
                </p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('class-schedules.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
            </div>
        </div>
    </div>

    {{-- CATEGORY INFO --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-tags text-primary"></i> Category Information</h5>
                <p class="nx-card-subtitle">Category and fee options</p>
            </div>
        </div>

        <div class="p-3">
            <div class="row g-4">

                {{-- CATEGORY --}}
                <div class="col-md-6">
                    <span class="cell-sub">Category</span>
                    <div class="cell-title d-flex align-items-center gap-2 mt-1">
                        {{ optional($categoryFee->category)->category_name ?? 'N/A' }}
                        @if (optional($categoryFee->category)->code)
                            <span class="id-badge">{{ $categoryFee->category->code }}</span>
                        @endif
                    </div>
                </div>

                {{-- FEE OPTIONS --}}
                <div class="col-md-6">
                    <span class="cell-sub">Fee Options</span>

                    @if ($categoryFee->activeFeeOptions->count())
                        <div class="fee-list mt-2">
                            @foreach ($categoryFee->activeFeeOptions as $option)
                                <div class="fee-item">
                                    <div>
                                        <div class="cell-title">{{ $option->label }}</div>
                                        @if ($option->is_default)
                                            <span class="nx-badge nx-badge-green mt-1">Default</span>
                                        @endif
                                        @if ($option->note)
                                            <small class="cell-sub d-block">{{ $option->note }}</small>
                                        @endif
                                    </div>
                                    <div class="cell-title" style="color: var(--nx-blue);">
                                        Rs. {{ number_format($option->fee, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="cell-sub mt-1">No active fee options</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- PATTERNS --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-calendar-range text-primary"></i> Schedule Patterns</h5>
                <p class="nx-card-subtitle">Recurring schedules defined for this category</p>
            </div>
            <span class="nx-badge nx-badge-blue">
                {{ $patterns->count() }} pattern(s)
            </span>
        </div>

        <div class="table-responsive">
            <table class="table premium-table mb-0">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Hall</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patterns as $pattern)
                        <tr>
                            <td class="cell-title text-capitalize">
                                <span class="nx-badge nx-badge-blue">{{ $pattern->class_day ?? 'N/A' }}</span>
                            </td>

                            <td class="cell-sub">
                                @if ($pattern->start_time && $pattern->end_time)
                                    {{ \Carbon\Carbon::parse($pattern->start_time)->format('h:i A') }}
                                    –
                                    {{ \Carbon\Carbon::parse($pattern->end_time)->format('h:i A') }}
                                @else
                                    N/A
                                @endif
                            </td>

                            <td class="cell-sub">{{ optional($pattern->hall)->hall_name ?? 'N/A' }}</td>

                            <td class="cell-sub">
                                {{ $pattern->start_date ? \Carbon\Carbon::parse($pattern->start_date)->format('Y-m-d') : 'N/A' }}
                            </td>

                            <td class="cell-sub">
                                {{ $pattern->end_date ? \Carbon\Carbon::parse($pattern->end_date)->format('Y-m-d') : 'N/A' }}
                            </td>

                            <td>
                                <span class="nx-badge {{ $pattern->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                                    {{ $pattern->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="text-end">
                                <div class="action-buttons">
                                    <a href="{{ route('class-schedules.editBulkSchedule', $pattern->id) }}"
                                        class="action-btn action-btn-warning"
                                        title="Edit" aria-label="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-table">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                <strong class="d-block">No Schedule Pattern Found</strong>
                                <span class="cell-sub">Create a recurring schedule pattern first</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    .dashboard-page .fee-list {
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
    .dashboard-page .fee-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: .6rem .75rem;
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
    }

    @media (max-width: 768px) {
        .dashboard-page .premium-table { min-width: 900px; }
    }
</style>
@endpush