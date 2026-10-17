@php
    $scheduleData = $classSchedule ?? null;
    $isEdit       = !empty($scheduleData);
    $currentScheduleType = $scheduleType ?? request('type', 'recurring');

    $formAction = $isEdit
        ? route('class-schedules.update', $scheduleData)
        : route('class-schedules.store');

    $selectedClassValue = old('student_class_id', $selectedClassId ?? optional($scheduleData)->student_class_id);
    $selectedCategoryValue = old('class_category_id', $selectedCategoryId ?? optional($scheduleData)->class_category_id);
    $selectedHallValue = old('class_hall_id', optional($scheduleData)->class_hall_id);

    $classDateValue = '';
    if (!empty(optional($scheduleData)->class_date)) {
        $classDateValue = \Carbon\Carbon::parse(optional($scheduleData)->class_date)->format('Y-m-d');
    }

    $startDateValue = old('start_date', '');
    $endDateValue   = old('end_date', '');

    $startTimeValue = '';
    if (!empty(optional($scheduleData)->start_time)) {
        $startTimeValue = \Carbon\Carbon::parse(optional($scheduleData)->start_time)->format('H:i');
    }

    $endTimeValue = '';
    if (!empty(optional($scheduleData)->end_time)) {
        $endTimeValue = \Carbon\Carbon::parse(optional($scheduleData)->end_time)->format('H:i');
    }

    $statusValue = old('status', optional($scheduleData)->status ?? 'scheduled');

    $isActiveValue = old('is_active', $isEdit ? (optional($scheduleData)->is_active ?? true) : true);

    $cancelReasonValue = old('cancel_reason', optional($scheduleData)->cancel_reason ?? '');
    $noteValue = old('note', optional($scheduleData)->note ?? '');
@endphp

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

<form method="POST" action="{{ $formAction }}">
    @csrf

    @if ($isEdit)
        @method('PUT')
    @else
        <input type="hidden" name="schedule_type" value="{{ $currentScheduleType }}">
    @endif

    <div class="row g-3">

        {{-- ===================================================== --}}
        {{-- SECTION: Schedule Information --}}
        {{-- ===================================================== --}}
        <div class="col-12">
            <div class="section-header mb-3">
                <div class="section-icon icon-blue"><i class="bi bi-calendar2-week"></i></div>
                <div>
                    <h5 class="section-title">
                        @if ($isEdit)
                            Schedule Information
                        @elseif ($currentScheduleType === 'single')
                            Single Day Schedule Information
                        @else
                            Recurring Schedule Information
                        @endif
                    </h5>
                    <p class="section-subtitle">Fill the schedule details carefully before saving</p>
                </div>
                @if (!$isEdit)
                    <span class="nx-badge {{ $currentScheduleType === 'single' ? 'nx-badge-green' : 'nx-badge-blue' }}">
                        {{ ucfirst($currentScheduleType) }}
                    </span>
                @endif
            </div>

            <div class="row g-3 mb-4">

                {{-- CLASS --}}
                <div class="col-lg-6">
                    <label class="nx-label">Class <span style="color: var(--nx-red);">*</span></label>
                    <input type="hidden" name="student_class_id" value="{{ $selectedClassValue }}">

                    <div class="selected-box">
                        @if (!empty($selectedClass))
                            <div class="fw-bold">{{ $selectedClass->class_name }}</div>
                            <div class="cell-sub">
                                Grade: {{ optional($selectedClass->grade)->grade_name ?? 'N/A' }} |
                                Subject: {{ optional($selectedClass->subject)->subject_name ?? 'N/A' }}
                            </div>
                            <div class="cell-sub">
                                Teacher: {{ optional($selectedClass->teacher)->full_name ?? 'N/A' }}
                            </div>
                        @else
                            <div class="cell-sub">No class selected</div>
                        @endif
                    </div>
                    @error('student_class_id')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- CATEGORY --}}
                <div class="col-lg-6">
                    <label class="nx-label">Fee Category <span style="color: var(--nx-red);">*</span></label>
                    <input type="hidden" name="class_category_fee_id"
                        value="{{ $selectedCategoryFeeId ?? request('class_category_fee_id') }}">

                    <div class="selected-box">
                        @if (!empty($selectedCategory))
                            <div class="fw-bold">{{ $selectedCategory->category_name }}</div>
                            <div class="cell-sub">
                                @if (!empty($selectedCategory->code))
                                    Code: {{ $selectedCategory->code }}
                                @else
                                    No code
                                @endif
                            </div>
                        @else
                            <div class="cell-sub">No category selected</div>
                        @endif
                    </div>
                    @error('class_category_id')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- HALL --}}
                <div class="col-lg-6">
                    <label class="nx-label">Hall</label>
                    <select name="class_hall_id"
                        class="form-select nx-input @error('class_hall_id') is-invalid @enderror">
                        <option value="">No Hall / Not selected</option>
                        @foreach ($halls as $hall)
                            <option value="{{ $hall->id }}"
                                {{ (string) $selectedHallValue === (string) $hall->id ? 'selected' : '' }}>
                                {{ $hall->hall_name }}
                                @if (!empty($hall->code)) - {{ $hall->code }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('class_hall_id')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- DATE FIELDS --}}
                @if ($isEdit)
                    <div class="col-lg-6">
                        <label class="nx-label">Class Date <span style="color: var(--nx-red);">*</span></label>
                        <input type="date" name="class_date"
                            class="form-control nx-input @error('class_date') is-invalid @enderror"
                            value="{{ $classDateValue }}" required>
                        @error('class_date')
                            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                @else
                    @if ($currentScheduleType === 'single')
                        <div class="col-lg-6">
                            <label class="nx-label">Class Date <span style="color: var(--nx-red);">*</span></label>
                            <input type="date" name="class_date"
                                class="form-control nx-input @error('class_date') is-invalid @enderror"
                                value="{{ old('class_date') }}" required>
                            @error('class_date')
                                <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>
                    @else
                        <div class="col-lg-4">
                            <label class="nx-label">Start Date <span style="color: var(--nx-red);">*</span></label>
                            <input type="date" name="start_date"
                                class="form-control nx-input @error('start_date') is-invalid @enderror"
                                value="{{ old('start_date', $startDateValue) }}" required>
                            @error('start_date')
                                <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="nx-label">End Date <span style="color: var(--nx-red);">*</span></label>
                            <input type="date" name="end_date"
                                class="form-control nx-input @error('end_date') is-invalid @enderror"
                                value="{{ old('end_date', $endDateValue) }}" required>
                            @error('end_date')
                                <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="nx-label">Class Day <span style="color: var(--nx-red);">*</span></label>
                            <select name="class_day"
                                class="form-select nx-input @error('class_day') is-invalid @enderror" required>
                                <option value="">Select Day</option>
                                @foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                                    <option value="{{ $day }}" {{ old('class_day') === $day ? 'selected' : '' }}>
                                        {{ ucfirst($day) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_day')
                                <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                            @enderror
                        </div>
                    @endif
                @endif

                {{-- START TIME --}}
                <div class="col-lg-6">
                    <label class="nx-label">Start Time <span style="color: var(--nx-red);">*</span></label>
                    <input type="time" name="start_time"
                        class="form-control nx-input @error('start_time') is-invalid @enderror"
                        value="{{ old('start_time', $startTimeValue) }}" required>
                    @error('start_time')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- END TIME --}}
                <div class="col-lg-6">
                    <label class="nx-label">End Time <span style="color: var(--nx-red);">*</span></label>
                    <input type="time" name="end_time"
                        class="form-control nx-input @error('end_time') is-invalid @enderror"
                        value="{{ old('end_time', $endTimeValue) }}" required>
                    @error('end_time')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>

                {{-- STATUS --}}
                @if ($isEdit)
                    <div class="col-lg-6">
                        <label class="nx-label">Status <span style="color: var(--nx-red);">*</span></label>
                        <select name="status"
                            class="form-select nx-input @error('status') is-invalid @enderror" required>
                            @foreach (['scheduled','ongoing','completed','cancelled'] as $status)
                                <option value="{{ $status }}" {{ $statusValue === $status ? 'selected' : '' }}>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                @endif

                {{-- ACTIVE --}}
                <div class="col-lg-6">
                    <label class="nx-label">Active</label>
                    <input type="hidden" name="is_active" value="0">
                    <div class="toggle-switch">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                            class="form-check-input flex-shrink-0"
                            style="width: 3em; height: 1.5em; cursor: pointer;"
                            {{ $isActiveValue ? 'checked' : '' }}>
                        <label for="is_active" class="toggle-content">
                            <span class="toggle-label">Active</span>
                            <span class="toggle-description">Available for use</span>
                        </label>
                    </div>
                </div>

                {{-- CANCEL REASON (edit only) --}}
                @if ($isEdit)
                    <div class="col-12">
                        <label class="nx-label">Cancel Reason</label>
                        <textarea name="cancel_reason"
                            class="form-control nx-input @error('cancel_reason') is-invalid @enderror"
                            rows="2" placeholder="Reason if status is cancelled">{{ $cancelReasonValue }}</textarea>
                        <small class="cell-sub d-block mt-1">
                            This is used only when status is set to cancelled.
                        </small>
                        @error('cancel_reason')
                            <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>
                @endif

                {{-- NOTE --}}
                <div class="col-12">
                    <label class="nx-label">Note</label>
                    <textarea name="note" rows="3"
                        class="form-control nx-input @error('note') is-invalid @enderror"
                        placeholder="Optional note">{{ $noteValue }}</textarea>
                    @error('note')
                        <div class="nx-error"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- ACTIONS --}}
        {{-- ===================================================== --}}
        <div class="col-12">
            <div class="nx-form-actions">
                <a href="{{ route('class-schedules.index') }}" class="nx-btn nx-btn-light">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="nx-btn nx-btn-primary">
                    <i class="bi bi-check-lg"></i> {{ $buttonText ?? 'Save' }}
                </button>
            </div>
        </div>
    </div>
</form>


@push('styles')
<style>
    .dashboard-page .section-header {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
    }

    .dashboard-page .section-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        font-size: .9rem;
        flex-shrink: 0;
        background: var(--nx-blue-soft);
        color: var(--nx-blue);
    }

    .dashboard-page .section-title {
        font-size: .95rem;
        font-weight: 700;
        color: var(--nx-text);
        margin: 0 0 .15rem;
    }

    .dashboard-page .section-subtitle {
        font-size: .75rem;
        color: var(--nx-muted);
        margin: 0;
    }

    .dashboard-page .nx-error {
        font-size: .74rem;
        color: var(--nx-red);
        margin-top: .35rem;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    /* Selected box */
    .dashboard-page .selected-box {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-left: 4px solid var(--nx-blue);
        border-radius: 8px;
        padding: .85rem 1rem;
        min-height: 90px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: .25rem;
    }
    .dashboard-page .selected-box .cell-sub {
        font-size: .75rem;
    }

    /* Toggle switch */
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

    /* Form actions */
    .dashboard-page .nx-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--nx-border);
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .dashboard-page .section-header { flex-direction: column; align-items: stretch; }
        .dashboard-page .nx-form-actions { flex-direction: column-reverse; }
        .dashboard-page .nx-form-actions .nx-btn { width: 100%; }
    }
</style>
@endpush