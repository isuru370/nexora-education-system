@extends('layouts.app')

@section('title', 'Promote Class')
@section('page-title', 'Promote Class')

@section('content')
    @php
        $oldStudents = old('student_ids');
        $rows = old('schedules', $schedules->map(fn ($s) => [
            'include' => 1,
            'class_day' => $s->class_day,
            'start_time' => substr($s->start_time, 0, 5),
            'end_time' => substr($s->end_time, 0, 5),
            'class_hall_id' => $s->class_hall_id,
            'class_category_fee_id' => $s->class_category_fee_id,
        ])->all());
        $paidAhead = $students->filter(fn ($s) => $s->paid_ahead !== '');
    @endphp

    <div class="dashboard-page">

        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">Promote Class</span>
                    <h1 class="hero-title">{{ $class->class_name }}</h1>
                    <p class="hero-subtitle">
                        Now: grade {{ $class->grade_name }} · {{ $class->teacher }} · {{ $students->count() }} students
                    </p>
                </div>
                <div class="hero-right">
                    <a href="{{ route('class-promotions.index') }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('class-promotions.store') }}" id="promoteForm"
            data-confirm="Promote {{ $class->class_name }}? A new class is made and the ticked students move to it from the start date."
            onsubmit="return confirm(this.dataset.confirm);">
            @csrf
            <input type="hidden" name="from_class_id" value="{{ $class->id }}">

            {{-- NEW CLASS --}}
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-2-circle-fill text-primary"></i> New class</h5>
                        <p class="nx-card-subtitle">
                            Fees and the teacher % are copied from the old class{{ $has_payment_config ? '' : ' (this class has no teacher % set: add it after)' }}.
                        </p>
                    </div>
                </div>
                <div class="p-3 row g-3">
                    <div class="col-lg-6">
                        <label class="nx-label">New class name</label>
                        <input type="text" name="class_name" class="form-control nx-input" required maxlength="150"
                            value="{{ old('class_name', $suggested_name) }}">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">New grade</label>
                        <select name="grade_id" class="form-select nx-input" required>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->id }}" {{ (int) old('grade_id', $suggested_grade_id) === $grade->id ? 'selected' : '' }}>
                                    {{ $grade->grade_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Teacher</label>
                        <select name="teacher_id" class="form-select nx-input" required>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ (int) old('teacher_id', $class->teacher_id) === $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Start date</label>
                        <input type="date" name="start_date" id="startDate" class="form-control nx-input" required
                            value="{{ old('start_date', $start_date) }}">
                        <small class="text-muted">The old class goes on until the day before.</small>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="nx-label">Schedule until</label>
                        <input type="date" name="schedule_until" class="form-control nx-input" required
                            value="{{ old('schedule_until', $schedule_until) }}">
                    </div>
                    <div class="col-lg-6 d-flex align-items-end">
                        <a href="#" class="nx-btn nx-btn-light"
                            onclick="this.href='{{ route('class-promotions.create', ['class_id' => $class->id]) }}&start_date=' + document.getElementById('startDate').value;">
                            <i class="bi bi-arrow-repeat"></i> Check payments for this start date
                        </a>
                    </div>
                </div>
            </div>

            {{-- STUDENTS --}}
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-3-circle-fill text-primary"></i> Students</h5>
                        <p class="nx-card-subtitle">
                            Untick students who are leaving. Ticked students move with the same category, fee and free card;
                            their grade changes on the start date.
                        </p>
                    </div>
                    <span class="nx-badge nx-badge-blue"><span id="tickedCount">0</span> / {{ $students->count() }} ticked</span>
                </div>

                @if ($paidAhead->isNotEmpty())
                    <div class="alert alert-warning m-3 mb-0">
                        <strong>{{ $paidAhead->count() }} student(s) already paid the old class for a month from the start date.</strong>
                        That money stays with the old class; check it before promoting (see "Paid ahead" below).
                    </div>
                @endif

                <div class="p-3 pb-0 d-flex gap-2 flex-wrap">
                    <input type="search" class="form-control nx-input" style="max-width:320px" placeholder="Search ID or name"
                        oninput="filterRows(this, 'studentsTable')">
                    <button type="button" class="nx-btn nx-btn-light" onclick="tickAll(true)">Tick all</button>
                    <button type="button" class="nx-btn nx-btn-light" onclick="tickAll(false)">Untick all</button>
                </div>

                <div class="table-responsive" style="max-height:520px; overflow:auto">
                    <table class="table premium-table mb-0" id="studentsTable">
                        <thead>
                            <tr>
                                <th width="40"></th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Grade</th>
                                <th>Category · Fee</th>
                                <th>Paid ahead</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $s)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input student-tick" name="student_ids[]"
                                            value="{{ $s->id }}" onchange="countTicked()"
                                            {{ $oldStudents === null || in_array($s->id, $oldStudents) ? 'checked' : '' }}>
                                    </td>
                                    <td class="cell-title">{{ $s->custom_id }}</td>
                                    <td>
                                        {{ $s->name }}
                                        @unless ($s->is_active)
                                            <span class="nx-badge nx-badge-gray">Inactive student</span>
                                        @endunless
                                    </td>
                                    <td>{{ $s->grade_name }}</td>
                                    <td>{{ $s->enrollments }}</td>
                                    <td>
                                        @if ($s->paid_ahead !== '')
                                            <span class="nx-badge nx-badge-amber">{{ $s->paid_ahead }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No students in this class.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SCHEDULE --}}
            <div class="nx-card mb-4">
                <div class="nx-card-header">
                    <div>
                        <h5 class="nx-card-title"><i class="bi bi-4-circle-fill text-primary"></i> Schedule of the new class</h5>
                        <p class="nx-card-subtitle">
                            One row per day. Copied from the old class: change the day, time or hall, untick a row to leave it out,
                            or add a day. Leave all unticked to make the schedule later.
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table premium-table mb-0" id="scheduleTable">
                        <thead>
                            <tr>
                                <th width="40"></th>
                                <th>Day</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Hall</th>
                                <th>Category</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                @include('class-promotions.schedule-row', ['i' => $i, 'row' => $row])
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    @if ($categories->isEmpty())
                        <span class="text-muted">This class has no fee category, so no schedule can be added.</span>
                    @else
                        <button type="button" class="nx-btn nx-btn-light" onclick="addRow()">
                            <i class="bi bi-plus-lg"></i> Add a day
                        </button>
                    @endif
                </div>

                <template id="rowTemplate">
                    @include('class-promotions.schedule-row', ['i' => '__I__', 'row' => [
                        'include' => 1, 'class_day' => 'sunday', 'start_time' => '08:00', 'end_time' => '10:00',
                        'class_hall_id' => null, 'class_category_fee_id' => $categories->first()->id ?? null,
                    ]])
                </template>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="{{ route('class-promotions.index') }}" class="nx-btn nx-btn-light">Cancel</a>
                <button type="submit" class="nx-btn nx-btn-primary">
                    <i class="bi bi-arrow-up-circle-fill"></i> Promote
                </button>
            </div>
        </form>
    </div>

    <script>
        let nextRow = {{ count($rows) }};

        function addRow() {
            const html = document.getElementById('rowTemplate').innerHTML.replaceAll('__I__', nextRow++);
            document.querySelector('#scheduleTable tbody').insertAdjacentHTML('beforeend', html);
        }

        function countTicked() {
            document.getElementById('tickedCount').textContent = document.querySelectorAll('.student-tick:checked').length;
        }

        function tickAll(on) {
            document.querySelectorAll('#studentsTable tbody tr').forEach(function (row) {
                if (row.style.display === 'none') return;
                const box = row.querySelector('.student-tick');
                if (box) box.checked = on;
            });
            countTicked();
        }

        function filterRows(input, tableId) {
            const q = input.value.toLowerCase();
            document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }

        countTicked();
    </script>
@endsection
