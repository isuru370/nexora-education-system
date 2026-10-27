<tr>
    <td>
        <input type="checkbox" class="form-check-input" name="schedules[{{ $i }}][include]" value="1"
            {{ !empty($row['include']) ? 'checked' : '' }}>
    </td>
    <td>
        <select name="schedules[{{ $i }}][class_day]" class="form-select nx-input">
            @foreach ($days as $day)
                <option value="{{ $day }}" {{ ($row['class_day'] ?? '') === $day ? 'selected' : '' }}>{{ ucfirst($day) }}</option>
            @endforeach
        </select>
    </td>
    <td><input type="time" name="schedules[{{ $i }}][start_time]" class="form-control nx-input" value="{{ $row['start_time'] ?? '' }}"></td>
    <td><input type="time" name="schedules[{{ $i }}][end_time]" class="form-control nx-input" value="{{ $row['end_time'] ?? '' }}"></td>
    <td>
        <select name="schedules[{{ $i }}][class_hall_id]" class="form-select nx-input">
            <option value="">No hall</option>
            @foreach ($halls as $hall)
                <option value="{{ $hall->id }}" {{ (int) ($row['class_hall_id'] ?? 0) === $hall->id ? 'selected' : '' }}>{{ $hall->hall_name }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select name="schedules[{{ $i }}][class_category_fee_id]" class="form-select nx-input">
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ (int) ($row['class_category_fee_id'] ?? 0) === $category->id ? 'selected' : '' }}>{{ $category->category_name }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-outline-danger" title="Remove" onclick="this.closest('tr').remove()">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
