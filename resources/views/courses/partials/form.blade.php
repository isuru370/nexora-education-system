@php
    $course = $course ?? null;
    $oldTeachers = old('teachers', $course
        ? $course->courseTeachers->map(fn ($t) => ['teacher_id' => $t->teacher_id, 'percentage' => (float) $t->percentage])->values()->all()
        : [['teacher_id' => '', 'percentage' => '']]);
    $oldDays = old('days', $course
        ? $course->days->map(fn ($d) => ['day' => $d->day, 'start_time' => substr($d->start_time, 0, 5), 'end_time' => substr($d->end_time, 0, 5), 'class_hall_id' => $d->class_hall_id])->values()->all()
        : [['day' => '', 'start_time' => '', 'end_time' => '', 'class_hall_id' => '']]);
    $value = fn ($field, $default = '') => old($field, $course ? $course->{$field} : $default);
    $date = fn ($field) => old($field, $course && $course->{$field} ? $course->{$field}->toDateString() : '');
@endphp

<form method="POST" action="{{ $course ? route('courses.update', $course) : route('courses.store') }}" id="courseForm" novalidate>
    @csrf
    @if ($course)
        @method('PUT')
    @endif

    <div class="nx-card mb-4">
        {{-- DETAILS --}}
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-mortarboard text-primary"></i> Course</div>
            <div class="form-section-sub">Name and branch</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="nx-label" for="name">Course name *</label>
                    <input id="name" name="name" class="form-control nx-input" maxlength="150" required value="{{ $value('name') }}" placeholder="e.g. Diploma in IT">
                </div>
                <div class="col-md-3">
                    <label class="nx-label" for="code">Code</label>
                    <input id="code" name="code" class="form-control nx-input" maxlength="50" value="{{ $value('code') }}" placeholder="e.g. DIT-2026">
                </div>
                <div class="col-md-3">
                    <label class="nx-label" for="branch_id">Branch *</label>
                    <select id="branch_id" name="branch_id" class="form-select nx-input" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) $value('branch_id', $branches->count() === 1 ? $branch->id : '') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="nx-label" for="description">Description</label>
                    <textarea id="description" name="description" rows="2" class="form-control nx-input" maxlength="2000">{{ $value('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- SCHEDULE --}}
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-calendar-week text-primary"></i> Schedule</div>
            <div class="form-section-sub">Each week day has its own time and hall. Every such day between the start and end date becomes a class day.</div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-lg-3">
                    <label class="nx-label" for="start_date">Start date *</label>
                    <input type="date" id="start_date" name="start_date" class="form-control nx-input" required value="{{ $date('start_date') }}">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="nx-label" for="end_date">End date *</label>
                    <input type="date" id="end_date" name="end_date" class="form-control nx-input" required value="{{ $date('end_date') }}">
                </div>
            </div>

            <div class="day-row day-head d-none d-md-grid">
                <span class="nx-label">Day *</span><span class="nx-label">Start *</span><span class="nx-label">End *</span><span class="nx-label">Hall</span><span></span>
            </div>
            <div id="dayRows">
                @foreach ($oldDays as $i => $row)
                    @include('courses.partials.day-row', ['i' => $i, 'row' => $row])
                @endforeach
            </div>
            <button type="button" class="nx-btn nx-btn-light mb-3" id="addDay"><i class="bi bi-plus-lg"></i> Add class day</button>

            <div class="preview-box" id="schedulePreview">Choose the dates and class days to see the number of classes and months.</div>
        </div>

        {{-- FEES --}}
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-cash-coin text-primary"></i> Fees</div>
            <div class="form-section-sub">Installments: the full fee split into one payment per course month. Paid at once: the one-time fee.</div>
            <div class="row g-3">
                <div class="col-sm-6 col-lg-4">
                    <label class="nx-label" for="full_fee">Full fee (installments) *</label>
                    <input type="number" step="0.01" min="0" id="full_fee" name="full_fee" class="form-control nx-input" required value="{{ $value('full_fee') }}" placeholder="12000">
                </div>
                <div class="col-sm-6 col-lg-4">
                    <label class="nx-label" for="one_time_fee">One-time fee (paid at once) *</label>
                    <input type="number" step="0.01" min="0" id="one_time_fee" name="one_time_fee" class="form-control nx-input" required value="{{ $value('one_time_fee') }}" placeholder="10000">
                </div>
                <div class="col-lg-4 d-flex align-items-end">
                    <div class="preview-box w-100" id="feePreview">—</div>
                </div>
            </div>
            @if ($course)
                <small class="cell-sub d-block mt-2">A fee change applies to new enrollments. Students already enrolled keep their fee.</small>
            @endif
        </div>

        {{-- TEACHERS --}}
        <div class="form-section">
            <div class="form-section-title"><i class="bi bi-people text-primary"></i> Teachers and income share</div>
            <div class="form-section-sub">Each teacher's % of every course payment. The rest goes to the institute. A change applies to new payments.</div>

            <div id="teacherRows">
                @foreach ($oldTeachers as $i => $row)
                    <div class="share-row">
                        <select name="teachers[{{ $i }}][teacher_id]" class="form-select nx-input" aria-label="Teacher">
                            <option value="">Select teacher</option>
                            @foreach ($allTeachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ (string) ($row['teacher_id'] ?? '') === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }} ({{ $teacher->custom_id }})</option>
                            @endforeach
                        </select>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="teachers[{{ $i }}][percentage]" class="form-control nx-input share-input" value="{{ $row['percentage'] ?? '' }}" placeholder="35" aria-label="Percentage">
                            <span class="input-group-text">%</span>
                        </div>
                        <button type="button" class="action-btn action-btn-danger remove-teacher" title="Remove" aria-label="Remove teacher"><i class="bi bi-x-lg"></i></button>
                    </div>
                @endforeach
            </div>

            <button type="button" class="nx-btn nx-btn-light mb-3" id="addTeacher"><i class="bi bi-plus-lg"></i> Add teacher</button>

            <div class="share-total" id="shareTotal">
                <span>Teachers: <strong id="teachersPct">0%</strong></span>
                <span>Institute: <strong id="institutePct">100%</strong></span>
            </div>
        </div>

        <div class="form-section d-flex gap-2 justify-content-end">
            <a href="{{ $course ? route('courses.show', $course) : route('courses.index') }}" class="nx-btn nx-btn-light">Cancel</a>
            <button type="submit" class="nx-btn nx-btn-primary"><i class="bi bi-check-lg"></i> {{ $course ? 'Save changes' : 'Create course' }}</button>
        </div>
    </div>
</form>

<template id="dayRowTemplate">
    @include('courses.partials.day-row', ['i' => '__d__', 'row' => []])
</template>

<template id="teacherRowTemplate">
    <div class="share-row">
        <select name="teachers[__i__][teacher_id]" class="form-select nx-input" aria-label="Teacher">
            <option value="">Select teacher</option>
            @foreach ($allTeachers as $teacher)
                <option value="{{ $teacher->id }}">{{ $teacher->full_name }} ({{ $teacher->custom_id }})</option>
            @endforeach
        </select>
        <div class="input-group">
            <input type="number" step="0.01" min="0" max="100" name="teachers[__i__][percentage]" class="form-control nx-input share-input" placeholder="35" aria-label="Percentage">
            <span class="input-group-text">%</span>
        </div>
        <button type="button" class="action-btn action-btn-danger remove-teacher" title="Remove" aria-label="Remove teacher"><i class="bi bi-x-lg"></i></button>
    </div>
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.getElementById('teacherRows');
    const template = document.getElementById('teacherRowTemplate').innerHTML;
    let next = rows.children.length;

    const money = (n) => 'Rs. ' + Number(n).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function shares() {
        let total = 0;
        rows.querySelectorAll('.share-input').forEach((input) => { total += parseFloat(input.value) || 0; });
        total = Math.round(total * 100) / 100;
        document.getElementById('teachersPct').textContent = total + '%';
        const institute = Math.round((100 - total) * 100) / 100;
        const box = document.getElementById('institutePct');
        box.textContent = institute + '%';
        box.style.color = institute < 0 ? 'var(--nx-red)' : '';
    }

    function monthCount(start, end) {
        const s = new Date(start + 'T00:00:00'), e = new Date(end + 'T00:00:00');
        e.setDate(e.getDate() + 1);
        let months = (e.getFullYear() - s.getFullYear()) * 12 + (e.getMonth() - s.getMonth());
        const probe = new Date(s); probe.setMonth(probe.getMonth() + months);
        if (probe < e) months++;
        return Math.max(1, months);
    }

    function preview() {
        const start = document.getElementById('start_date').value;
        const end = document.getElementById('end_date').value;
        const days = [...document.querySelectorAll('#dayRows select.day-select')].map((c) => c.value).filter(Boolean);
        const full = parseFloat(document.getElementById('full_fee').value) || 0;
        const once = parseFloat(document.getElementById('one_time_fee').value) || 0;
        const box = document.getElementById('schedulePreview');
        const feeBox = document.getElementById('feePreview');

        if (!start || !end || end < start) {
            box.textContent = 'Choose the dates and class days to see the number of classes and months.';
            feeBox.textContent = '—';
            return;
        }

        const names = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const counts = {};
        for (let d = new Date(start + 'T00:00:00'); d <= new Date(end + 'T00:00:00'); d.setDate(d.getDate() + 1)) {
            const name = names[d.getDay()];
            if (days.includes(name)) counts[name] = (counts[name] || 0) + 1;
        }
        const classes = Object.values(counts).reduce((a, b) => a + b, 0);
        const parts = days.map((d) => d.charAt(0).toUpperCase() + d.slice(1, 3) + ' ' + (counts[d] || 0));
        const dupes = days.filter((d, i) => days.indexOf(d) !== i);
        const months = monthCount(start, end);
        box.innerHTML = '<strong>' + months + '</strong> month(s) · <strong>' + classes + '</strong> class day(s)'
            + (parts.length ? ' (' + parts.join(' · ') + ')' : '')
            + (dupes.length ? '<br><span style="color:var(--nx-red)">' + dupes[0] + ' is added twice - one time per day.</span>' : '');
        feeBox.innerHTML = full > 0
            ? months + ' installments of about <strong>' + money(full / months) + '</strong>' + (once > 0 && once < full ? ' · saves ' + money(full - once) + ' when paid at once' : '')
            : '—';
    }

    function filterHalls() {
        const branch = document.getElementById('branch_id').value;
        document.querySelectorAll('#dayRows select.hall-select').forEach((hall) => {
            [...hall.options].forEach((o) => { if (o.dataset.branch) o.hidden = o.dataset.branch !== branch; });
            if (hall.selectedOptions[0] && hall.selectedOptions[0].hidden) hall.value = '';
        });
    }

    const dayRows = document.getElementById('dayRows');
    const dayTemplate = document.getElementById('dayRowTemplate').innerHTML;
    let nextDay = dayRows.children.length;

    document.getElementById('addDay').addEventListener('click', function () {
        dayRows.insertAdjacentHTML('beforeend', dayTemplate.replaceAll('__d__', nextDay++));
        filterHalls();
        preview();
    });

    dayRows.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-day');
        if (!btn) return;
        if (dayRows.children.length > 1) btn.closest('.day-row').remove();
        else btn.closest('.day-row').querySelectorAll('select, input').forEach((el) => el.value = '');
        preview();
    });

    document.getElementById('addTeacher').addEventListener('click', function () {
        rows.insertAdjacentHTML('beforeend', template.replaceAll('__i__', next++));
    });

    rows.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-teacher');
        if (!btn) return;
        if (rows.children.length > 1) btn.closest('.share-row').remove();
        else btn.closest('.share-row').querySelectorAll('select, input').forEach((el) => el.value = '');
        shares();
    });
    rows.addEventListener('input', shares);

    document.getElementById('courseForm').addEventListener('input', preview);
    document.getElementById('courseForm').addEventListener('change', preview);
    document.getElementById('branch_id').addEventListener('change', filterHalls);

    shares();
    preview();
    filterHalls();
});
</script>
@endpush
