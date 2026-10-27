@extends('layouts.app')

@section('title', 'Promote Class')
@section('page-title', 'Promote Class')

@section('content')
    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">nexoraEDU</span>
                    <h1 class="hero-title">Promote Class</h1>
                    <p class="hero-subtitle">
                        New year: e.g. "Aa class - Grade 9" goes on as "Aa class - Grade 10".
                        A new class is made from a start date (same teacher, fees and schedule) with the students you tick;
                        the old class stays as it was, so old reports and receipts still say Grade 9.
                    </p>
                </div>
                <div class="hero-right">
                    <small class="hero-updated">{{ now()->format('D, d M Y') }}</small>
                </div>
            </div>
        </div>

        {{-- STEP 1: CLASS --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-1-circle-fill text-primary"></i> Which class?</h5>
                    <p class="nx-card-subtitle">Next you choose the new name, grade, start date, students and schedule.</p>
                </div>
            </div>

            <div class="p-3">
                <form method="GET" action="{{ route('class-promotions.create') }}" class="row g-3 align-items-end">
                    <div class="col-lg-9">
                        <label class="nx-label">Class</label>
                        <input type="search" class="form-control nx-input mb-2" placeholder="Search class, grade or teacher"
                            oninput="filterOptions(this)">
                        <select name="class_id" id="classSelect" class="form-select nx-input" size="8" required>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">
                                    {{ $class->class_name }} · {{ $class->grade_name }} · {{ $class->teacher }}
                                    · {{ $class->students }} students{{ $class->branch ? ' · ' . $class->branch : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 d-flex justify-content-end">
                        <button type="submit" class="nx-btn nx-btn-primary">
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- HISTORY --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-clock-history text-primary"></i> History</h5>
                    <p class="nx-card-subtitle">Every promotion, who did it, and undo</p>
                </div>
                <span class="nx-badge nx-badge-blue">{{ $history->total() }} Promotions</span>
            </div>

            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Old class</th>
                            <th>New class</th>
                            <th>Starts</th>
                            <th class="text-center">Students</th>
                            <th>By</th>
                            <th>Status</th>
                            <th class="text-center" width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->from_class_name }}</td>
                                <td class="cell-title">{{ $p->to_class_name }}</td>
                                <td>{{ $p->start_date->format('Y-m-d') }}</td>
                                <td class="text-center">{{ $p->total('moved') }}</td>
                                <td>{{ $p->promotedBy->name ?? '-' }}</td>
                                <td>@include('class-promotions.status', ['promotion' => $p])</td>
                                <td class="text-center">
                                    <a href="{{ route('class-promotions.show', $p) }}" class="nx-btn nx-btn-light">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No class has been promoted yet.</td>
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
        function filterOptions(input) {
            const q = input.value.toLowerCase();
            document.querySelectorAll('#classSelect option').forEach(function (option) {
                option.hidden = !option.textContent.toLowerCase().includes(q);
            });
        }
    </script>
@endsection
