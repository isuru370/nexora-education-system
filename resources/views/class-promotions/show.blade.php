@extends('layouts.app')

@section('title', 'Promotion #' . $promotion->id)
@section('page-title', 'Promote Class')

@section('content')
    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">Promotion #{{ $promotion->id }}</span>
                    <h1 class="hero-title">{{ $promotion->from_class_name }} → {{ $promotion->to_class_name }}</h1>
                    <p class="hero-subtitle">
                        New class from {{ $promotion->start_date->format('Y-m-d') }}
                        (schedule until {{ $promotion->schedule_until->format('Y-m-d') }})
                        · by {{ $promotion->promotedBy->name ?? '-' }} on {{ $promotion->created_at->format('Y-m-d h:i A') }}
                        @if ($promotion->undone_at)
                            · <strong>Undone</strong> {{ $promotion->undone_at->format('Y-m-d h:i A') }} by {{ $promotion->undoneBy->name ?? '-' }}
                        @endif
                    </p>
                    <div class="mt-2">@include('class-promotions.status', ['promotion' => $promotion])</div>
                </div>
                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('class-promotions.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        @if ($promotion->status !== \App\Models\ClassPromotion::UNDONE)
                            @if ($details['undo_blocker'])
                                <span class="text-muted small">{{ $details['undo_blocker'] }}</span>
                            @else
                                <form method="POST" action="{{ route('class-promotions.undo', $promotion) }}" class="d-inline"
                                    data-confirm="Undo? The new class is removed and the old class, its students and schedule are as before."
                                    onsubmit="return confirm(this.dataset.confirm);">
                                    @csrf
                                    <button type="submit" class="nx-btn nx-btn-amber">
                                        <i class="bi bi-arrow-counterclockwise"></i> Undo
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($promotion->status === \App\Models\ClassPromotion::SCHEDULED)
            <div class="alert alert-info">
                Until {{ $promotion->start_date->copy()->subDay()->format('Y-m-d') }} the old class goes on as usual
                (attendance, payments). On {{ $promotion->start_date->format('Y-m-d') }} it closes by itself and the students' grade changes.
            </div>
        @endif

        <div class="stats-grid stats-grid-3 mb-4">
            @foreach ([
                ['Students moved', 'moved', 'bi-person-check-fill', 'icon-green'],
                ['Students left (not moved)', 'left', 'bi-person-dash', 'icon-amber'],
                ['Grades changed', 'grade_changes', 'bi-mortarboard', 'icon-blue'],
                ['Schedule days', 'schedules', 'bi-calendar-week', 'icon-blue'],
                ['New sessions', 'sessions', 'bi-calendar-plus', 'icon-green'],
                ['Old sessions cancelled', 'cancelled', 'bi-calendar-x', 'icon-red'],
            ] as [$label, $key, $icon, $color])
                <div class="nx-card stat-card">
                    <div class="stat-icon-wrapper {{ $color }}"><i class="bi {{ $icon }}"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">{{ $label }}</span>
                        <h2 class="stat-number">{{ number_format($promotion->total($key)) }}</h2>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- SCHEDULE --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <h5 class="nx-card-title"><i class="bi bi-calendar-week text-primary"></i> Schedule of the new class</h5>
            </div>
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr><th>Day</th><th>Time</th><th>Hall</th><th>Category</th><th>From</th><th>Until</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($details['schedules'] as $s)
                            <tr>
                                <td class="cell-title">{{ ucfirst($s->class_day) }}</td>
                                <td>{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}</td>
                                <td>{{ $s->hall_name ?? '-' }}</td>
                                <td>{{ $s->category_name }}</td>
                                <td>{{ substr($s->start_date, 0, 10) }}</td>
                                <td>{{ substr($s->end_date, 0, 10) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No schedule was made (add it on the Class Schedule page).</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- STUDENTS --}}
        @foreach ([['Moved to the new class', 'moved', 'text-success'], ['Left (not moved)', 'left', 'text-warning']] as [$title, $key, $color])
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <h5 class="nx-card-title"><i class="bi bi-people-fill {{ $color }}"></i> {{ $title }} ({{ $details[$key]->count() }})</h5>
                </div>
                <div class="table-responsive" style="max-height:480px; overflow:auto">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr><th>#</th><th>Student ID</th><th>Name</th><th>Grade now</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($details[$key]->values() as $i => $s)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="cell-title">{{ $s->custom_id }}</td>
                                    <td>{{ $s->initial_name }}</td>
                                    <td>{{ $s->grade_name }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">None.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endsection
