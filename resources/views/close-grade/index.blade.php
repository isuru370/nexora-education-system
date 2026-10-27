@extends('layouts.app')

@section('title', 'Close Batch (Grade)')
@section('page-title', 'Close Batch (Grade)')

@section('content')
    <div class="dashboard-page">

        {{-- HERO --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Close Batch (Grade)</h1>
                    <p class="hero-subtitle">
                        When a batch is over (e.g. 2026 A/L after the exam): close all its classes and deactivate its students.
                        Payments and attendance are kept, and a close can be undone.
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- STEP 1: GRADES --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-1-circle-fill text-primary"></i> Select grades</h5>
                    <p class="nx-card-subtitle">Preview first: nothing is changed until you press "Close".</p>
                </div>
            </div>

            <div class="p-3">
                <form method="GET" action="{{ route('close-grade.index') }}">
                    <div class="row g-2">
                        @foreach ($grades as $grade)
                            <div class="col-xl-3 col-lg-4 col-sm-6">
                                <label class="d-flex align-items-center gap-2 border rounded p-2 h-100" style="cursor:pointer">
                                    <input type="checkbox" class="form-check-input m-0" name="grade_ids[]"
                                        value="{{ $grade->id }}" {{ in_array($grade->id, $selected, true) ? 'checked' : '' }}>
                                    <span>
                                        <strong>{{ $grade->grade_name }}</strong><br>
                                        <small class="text-muted">
                                            {{ $grade->active_classes }} active classes · {{ $grade->active_students }} active students
                                        </small>
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('close-grade.index') }}" class="nx-btn nx-btn-light">Clear</a>
                        <button type="submit" class="nx-btn nx-btn-primary">
                            <i class="bi bi-eye"></i> Preview
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($previewError)
            <div class="alert alert-danger">{{ $previewError }}</div>
        @endif

        {{-- STEP 2: PREVIEW --}}
        @if ($preview)
            <div class="nx-card mb-4 border border-danger">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title">
                            <i class="bi bi-2-circle-fill text-danger"></i>
                            Preview: {{ implode(', ', $preview['grades']) }}
                        </h5>
                        <p class="nx-card-subtitle">This is what "Close" will change. Nothing is changed yet.</p>
                    </div>
                </div>

                <div class="p-3">
                    <div class="stats-grid stats-grid-3 mb-3">
                        @foreach ([
                            ['Classes made inactive', $preview['classes']->count(), 'bi-book', 'icon-blue'],
                            ['Enrollments ended (left today)', $preview['enrollments']->count(), 'bi-person-dash', 'icon-amber'],
                            ['Future sessions cancelled', $preview['schedules']->count(), 'bi-calendar-x', 'icon-amber'],
                            ['Schedule patterns made inactive', $preview['patterns']->count(), 'bi-calendar-week', 'icon-blue'],
                            ['Students deactivated', $preview['close']->count(), 'bi-person-x-fill', 'icon-red'],
                            ['Students kept active', $preview['keep']->count(), 'bi-person-check-fill', 'icon-green'],
                        ] as [$label, $value, $icon, $color])
                            <div class="nx-card stat-card">
                                <div class="stat-icon-wrapper {{ $color }}"><i class="bi {{ $icon }}"></i></div>
                                <div class="stat-info">
                                    <span class="stat-label">{{ $label }}</span>
                                    <h2 class="stat-number">{{ number_format($value) }}</h2>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <h6 class="mt-2">Classes ({{ $preview['classes']->count() }})</h6>
                    @if ($preview['classes']->isEmpty())
                        <p class="text-muted">No active classes (already inactive).</p>
                    @else
                        <ul class="mb-3">
                            @foreach ($preview['classes'] as $class)
                                <li>{{ $class->class_name }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($preview['keep']->isNotEmpty())
                        <h6 class="text-success">Kept active: still in an active class of another grade ({{ $preview['keep']->count() }})</h6>
                        <div class="table-responsive mb-3">
                            <table class="table premium-table mb-0">
                                <thead>
                                    <tr><th>Student ID</th><th>Name</th><th>Other classes</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview['keep'] as $s)
                                        <tr>
                                            <td class="cell-title">{{ $s['custom_id'] }}</td>
                                            <td>{{ $s['name'] }}</td>
                                            <td>{{ $s['other_classes'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <h6>Students deactivated ({{ $preview['close']->count() }})</h6>
                    @if ($preview['close']->isNotEmpty())
                        <input type="search" class="form-control nx-input mb-2" placeholder="Search ID or name"
                            oninput="filterRows(this, 'closeStudents')">
                        <div class="table-responsive mb-3" style="max-height:420px; overflow:auto">
                            <table class="table premium-table mb-0" id="closeStudents">
                                <thead>
                                    <tr><th>#</th><th>Student ID</th><th>Name</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview['close'] as $i => $s)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td class="cell-title">{{ $s->custom_id }}</td>
                                            <td>{{ $s->initial_name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('close-grade.store') }}" class="d-flex justify-content-end"
                        data-confirm="Close {{ implode(', ', $preview['grades']) }}? {{ $preview['classes']->count() }} classes will be inactive and {{ $preview['close']->count() }} students deactivated. (It can be undone from the history.)"
                        onsubmit="return confirm(this.dataset.confirm);">
                        @csrf
                        @foreach ($selected as $id)
                            <input type="hidden" name="grade_ids[]" value="{{ $id }}">
                        @endforeach
                        <button type="submit" class="nx-btn nx-btn-danger">
                            <i class="bi bi-archive-fill"></i> Close {{ implode(', ', $preview['grades']) }}
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- HISTORY --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-clock-history text-primary"></i> History</h5>
                    <p class="nx-card-subtitle">Every close, who did it, and undo</p>
                </div>
                <span class="nx-badge nx-badge-blue">{{ $history->total() }} Closes</span>
            </div>

            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Grades</th>
                            <th class="text-center">Classes</th>
                            <th class="text-center">Students</th>
                            <th>By</th>
                            <th>Status</th>
                            <th class="text-center" width="150">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $closure)
                            <tr>
                                <td>{{ $closure->id }}</td>
                                <td class="cell-title">{{ $closure->closed_at->format('Y-m-d h:i A') }}</td>
                                <td>{{ implode(', ', $closure->grade_names) }}</td>
                                <td class="text-center">{{ $closure->total('classes') }}</td>
                                <td class="text-center">{{ $closure->total('students') }}</td>
                                <td>
                                    {{ $closure->closedBy->name ?? ($closure->source === 'web' ? '-' : 'Command') }}
                                </td>
                                <td>
                                    @if ($closure->isUndone())
                                        <span class="nx-badge nx-badge-gray">Undone {{ $closure->undone_at->format('Y-m-d') }}</span>
                                    @else
                                        <span class="nx-badge nx-badge-red">Closed</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('close-grade.show', $closure) }}" class="nx-btn nx-btn-light">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No grade has been closed yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($history->hasPages())
                <div class="p-3">{{ $history->links() }}</div>
            @endif
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
