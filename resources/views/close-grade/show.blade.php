@extends('layouts.app')

@section('title', 'Close Batch #' . $closure->id)
@section('page-title', 'Close Batch (Grade)')

@section('content')
    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">Close #{{ $closure->id }}</span>
                    <h1 class="hero-title">{{ implode(', ', $closure->grade_names) }}</h1>
                    <p class="hero-subtitle">
                        Closed {{ $closure->closed_at->format('Y-m-d h:i A') }}
                        by {{ $closure->closedBy->name ?? ($closure->source === 'web' ? '-' : 'command') }}
                        @if ($closure->isUndone())
                            · <strong>Undone</strong> {{ $closure->undone_at->format('Y-m-d h:i A') }}
                            by {{ $closure->undoneBy->name ?? 'command' }}
                        @endif
                    </p>
                </div>
                <div class="hero-right">
                    <div class="hero-actions">
                        <a href="{{ route('close-grade.index') }}" class="nx-btn nx-btn-light">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        @unless ($closure->isUndone())
                            <form method="POST" action="{{ route('close-grade.undo', $closure) }}" class="d-inline"
                                data-confirm="Undo this close? The classes, enrollments, sessions and students are made active again."
                                onsubmit="return confirm(this.dataset.confirm);">
                                @csrf
                                <button type="submit" class="nx-btn nx-btn-amber">
                                    <i class="bi bi-arrow-counterclockwise"></i> Undo
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-grid stats-grid-3 mb-4">
            @foreach ([
                ['Classes made inactive', 'classes', 'bi-book', 'icon-blue'],
                ['Enrollments ended', 'enrollments', 'bi-person-dash', 'icon-amber'],
                ['Future sessions cancelled', 'sessions', 'bi-calendar-x', 'icon-amber'],
                ['Schedule patterns made inactive', 'patterns', 'bi-calendar-week', 'icon-blue'],
                ['Students deactivated', 'students', 'bi-person-x-fill', 'icon-red'],
                ['Students kept active', 'kept', 'bi-person-check-fill', 'icon-green'],
            ] as [$label, $key, $icon, $color])
                <div class="nx-card stat-card">
                    <div class="stat-icon-wrapper {{ $color }}"><i class="bi {{ $icon }}"></i></div>
                    <div class="stat-info">
                        <span class="stat-label">{{ $label }}</span>
                        <h2 class="stat-number">{{ number_format($closure->total($key)) }}</h2>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- CLASSES --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <h5 class="nx-card-title"><i class="bi bi-book text-primary"></i> Classes ({{ $details['classes']->count() }})</h5>
            </div>
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr><th>#</th><th>Class</th><th>Teacher</th><th>Now</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($details['classes'] as $i => $class)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="cell-title">{{ $class->class_name }}</td>
                                <td>{{ $class->teacher }}</td>
                                <td>
                                    <span class="nx-badge {{ $class->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                                        {{ $class->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No classes were active.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- KEPT --}}
        @if ($details['kept']->isNotEmpty())
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <h5 class="nx-card-title">
                        <i class="bi bi-person-check-fill text-success"></i>
                        Kept active: in an active class of another grade ({{ $details['kept']->count() }})
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr><th>Student ID</th><th>Name</th><th>Other classes</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($details['kept'] as $s)
                                <tr>
                                    <td class="cell-title">{{ $s['custom_id'] }}</td>
                                    <td>{{ $s['name'] }}</td>
                                    <td>{{ $s['other_classes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- STUDENTS --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <h5 class="nx-card-title">
                    <i class="bi bi-person-x-fill text-danger"></i> Students deactivated ({{ $details['students']->count() }})
                </h5>
            </div>
            <div class="p-3 pb-0">
                <input type="search" class="form-control nx-input mb-2" placeholder="Search ID, name or mobile"
                    oninput="filterRows(this, 'closedStudents')">
            </div>
            <div class="table-responsive" style="max-height:600px; overflow:auto">
                <table class="table premium-table mb-0" id="closedStudents">
                    <thead>
                        <tr><th>#</th><th>Student ID</th><th>Name</th><th>Grade</th><th>Mobile</th><th>Now</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($details['students'] as $i => $s)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="cell-title">{{ $s->custom_id }}</td>
                                <td>{{ $s->initial_name }}</td>
                                <td>{{ $s->grade_name }}</td>
                                <td>{{ $s->mobile ?: $s->guardian_mobile }}</td>
                                <td>
                                    <span class="nx-badge {{ $s->is_active ? 'nx-badge-green' : 'nx-badge-gray' }}">
                                        {{ $s->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No students.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterRows(input, tableId) {
            const q = input.value.toLowerCase();
            document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }
    </script>
@endsection
