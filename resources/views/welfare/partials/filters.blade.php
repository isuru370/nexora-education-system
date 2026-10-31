{{-- Branch (only when more than one), year and month --}}
<div class="nx-card mb-4">
    <div class="p-3">
        <form method="GET" action="{{ $action }}" class="row g-3 align-items-end">
            @if ($branches->count() > 1)
                <div class="col-lg-3 col-md-6">
                    <label class="nx-label" for="wf-branch">Branch</label>
                    <select name="branch_id" id="wf-branch" class="form-select nx-input">
                        @foreach ($branches as $branchOption)
                            <option value="{{ $branchOption->id }}" {{ (int) $branchId === (int) $branchOption->id ? 'selected' : '' }}>
                                {{ $branchOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
            @endif

            <div class="col-lg-3 col-md-6">
                <label class="nx-label" for="wf-year">Year</label>
                <select name="year" id="wf-year" class="form-select nx-input">
                    @for ($y = now()->year; $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="nx-label" for="wf-month">Month</label>
                <select name="month" id="wf-month" class="form-select nx-input">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <button type="submit" class="nx-btn nx-btn-primary w-100"><i class="bi bi-funnel"></i> Show</button>
            </div>
        </form>
    </div>
</div>
