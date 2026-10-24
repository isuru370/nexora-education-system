{{-- One weekly class day: day, start, end, hall --}}
<div class="day-row">
    <select name="days[{{ $i }}][day]" class="form-select nx-input day-select" aria-label="Day">
        <option value="">Day</option>
        @foreach (\App\Models\Course::DAYS as $day)
            <option value="{{ $day }}" {{ ($row['day'] ?? '') === $day ? 'selected' : '' }}>{{ ucfirst($day) }}</option>
        @endforeach
    </select>
    <input type="time" name="days[{{ $i }}][start_time]" class="form-control nx-input start-input" value="{{ $row['start_time'] ?? '' }}" aria-label="Start time">
    <input type="time" name="days[{{ $i }}][end_time]" class="form-control nx-input end-input" value="{{ $row['end_time'] ?? '' }}" aria-label="End time">
    <select name="days[{{ $i }}][class_hall_id]" class="form-select nx-input hall-select" aria-label="Hall">
        <option value="">No hall</option>
        @foreach ($halls as $hall)
            <option value="{{ $hall->id }}" data-branch="{{ $hall->branch_id }}" {{ (string) ($row['class_hall_id'] ?? '') === (string) $hall->id ? 'selected' : '' }}>{{ $hall->hall_name }}</option>
        @endforeach
    </select>
    <button type="button" class="action-btn action-btn-danger remove-day" title="Remove day" aria-label="Remove class day"><i class="bi bi-x-lg"></i></button>
</div>
