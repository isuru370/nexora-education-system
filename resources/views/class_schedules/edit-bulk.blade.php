@extends('layouts.app')

@section('title', 'Bulk Schedule Update')
@section('page-title', 'Bulk Schedule Update')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">Bulk Schedule Update</span>
                <h1 class="hero-title">{{ $studentClass->class_name ?? 'Class' }}</h1>
                <p class="hero-subtitle">
                    <i class="bi bi-book"></i> {{ optional($studentClass->subject)->subject_name ?? 'N/A' }}
                    • <i class="bi bi-mortarboard"></i> {{ optional($studentClass->grade)->grade_name ?? 'N/A' }}
                    • <i class="bi bi-person-badge"></i> {{ optional($studentClass->teacher)->full_name ?? 'N/A' }}
                </p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ url()->previous() }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
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

    @if ($errors->any())
        <div class="nx-alert nx-alert-red mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- FORM CARD --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-calendar-range text-primary"></i> Bulk Update Future Scheduled Classes</h5>
                <p class="nx-card-subtitle">Changes will be applied to all future scheduled classes in this pattern</p>
            </div>
        </div>

        <form method="POST" action="{{ route('class-schedules.updateBulkSchedule', $pattern->id) }}">
            @csrf
            @method('PUT')

            {{-- HIDDEN: Start/End dates (uneditable) --}}
            <input type="hidden" name="start_date"
                value="{{ $pattern->start_date ? \Carbon\Carbon::parse($pattern->start_date)->format('Y-m-d') : '' }}">

            <input type="hidden" name="end_date"
                value="{{ $pattern->end_date ? \Carbon\Carbon::parse($pattern->end_date)->format('Y-m-d') : '' }}">

            <div class="p-3 p-md-4">
                <div class="row g-3">

                    {{-- CLASS --}}
                    <div class="col-md-6">
                        <label class="nx-label">Class</label>
                        <input type="text" class="form-control nx-input readonly-box"
                            value="{{ $studentClass->class_name ?? 'N/A' }}" readonly>
                    </div>

                    {{-- CATEGORY --}}
                    <div class="col-md-6">
                        <label class="nx-label">Category</label>
                        <input type="text" class="form-control nx-input readonly-box"
                            value="{{ optional($categoryFee->category)->category_name ?? 'N/A' }}" readonly>
                    </div>

                    {{-- START DATE --}}
                    <div class="col-md-6">
                        <label class="nx-label">Start Date</label>
                        <input type="date" class="form-control nx-input readonly-box"
                            value="{{ $pattern->start_date ? \Carbon\Carbon::parse($pattern->start_date)->format('Y-m-d') : '' }}"
                            readonly>
                    </div>

                    {{-- END DATE --}}
                    <div class="col-md-6">
                        <label class="nx-label">End Date</label>
                        <input type="date" class="form-control nx-input readonly-box"
                            value="{{ $pattern->end_date ? \Carbon\Carbon::parse($pattern->end_date)->format('Y-m-d') : '' }}"
                            readonly>
                    </div>

                    {{-- CLASS DAY --}}
                    <div class="col-md-6">
                        <label class="nx-label">Class Day</label>
                        <select name="class_day"
                            class="form-select nx-input @error('class_day') is-invalid @enderror">
                            @foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                                <option value="{{ $day }}"
                                    {{ old('class_day', $pattern->class_day) == $day ? 'selected' : '' }}>
                                    {{ ucfirst($day) }}
                                </option>
                            @endforeach
                        </select>
                        @error('class_day')
                            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- HALL --}}
                    <div class="col-md-6">
                        <label class="nx-label">Hall</label>
                        <select name="class_hall_id"
                            class="form-select nx-input @error('class_hall_id') is-invalid @enderror">
                            <option value="">Select Hall</option>
                            @foreach ($halls as $hall)
                                <option value="{{ $hall->id }}"
                                    {{ (string) old('class_hall_id', $pattern->class_hall_id) === (string) $hall->id ? 'selected' : '' }}>
                                    {{ $hall->hall_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('class_hall_id')
                            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- START TIME --}}
                    <div class="col-md-6">
                        <label class="nx-label">Start Time</label>
                        <input type="time" name="start_time"
                            class="form-control nx-input @error('start_time') is-invalid @enderror"
                            value="{{ old('start_time', $pattern->start_time ? \Carbon\Carbon::parse($pattern->start_time)->format('H:i') : '') }}">
                        @error('start_time')
                            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- END TIME --}}
                    <div class="col-md-6">
                        <label class="nx-label">End Time</label>
                        <input type="time" name="end_time"
                            class="form-control nx-input @error('end_time') is-invalid @enderror"
                            value="{{ old('end_time', $pattern->end_time ? \Carbon\Carbon::parse($pattern->end_time)->format('H:i') : '') }}">
                        @error('end_time')
                            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ACTIVE --}}
                    <div class="col-md-6">
                        <label class="nx-label">Active</label>
                        <input type="hidden" name="is_active" value="0">
                        <div class="toggle-switch">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                class="form-check-input flex-shrink-0"
                                style="width: 3em; height: 1.5em; cursor: pointer;"
                                {{ old('is_active', $pattern->is_active) ? 'checked' : '' }}>
                            <label for="is_active" class="toggle-content">
                                <span class="toggle-label">Active</span>
                                <span class="toggle-description">Apply future changes</span>
                            </label>
                        </div>
                    </div>

                    {{-- NOTE --}}
                    <div class="col-md-12">
                        <label class="nx-label">Note</label>
                        <textarea name="note" rows="4"
                            class="form-control nx-input @error('note') is-invalid @enderror">{{ old('note', $pattern->note) }}</textarea>
                        @error('note')
                            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="p-3 border-top">
                <div class="nx-form-actions" style="margin: 0; padding: 0; border: 0;">
                    <a href="{{ url()->previous() }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="nx-btn nx-btn-primary">
                        <i class="bi bi-save"></i> Update Future Schedules
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection


@push('styles')
<style>
    .dashboard-page .readonly-box {
        background: #f4f6fa;
        color: var(--nx-muted);
        cursor: not-allowed;
    }

    .dashboard-page .nx-error {
        font-size: .74rem;
        color: var(--nx-red);
        margin-top: .35rem;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    .dashboard-page .toggle-switch {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .85rem 1rem;
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        margin-top: .35rem;
    }
    .dashboard-page .toggle-content { cursor: pointer; }
    .dashboard-page .toggle-label {
        display: block;
        font-size: .88rem;
        font-weight: 700;
        color: var(--nx-text);
        margin-bottom: .1rem;
    }
    .dashboard-page .toggle-description {
        font-size: .72rem;
        color: var(--nx-muted);
    }

    .dashboard-page .nx-form-actions {
        display: flex;
        justify-content: space-between;
        gap: .5rem;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .dashboard-page .nx-form-actions {
            flex-direction: column-reverse;
        }
        .dashboard-page .nx-form-actions .nx-btn { width: 100%; }
    }
</style>
@endpush