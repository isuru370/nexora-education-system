{{-- Branch (only when more than one), year and month --}}
<div class="nx-card tw:mb-6!">
    <div class="tw:p-4!">
        <form method="GET" action="{{ $action }}" class="tw:flex tw:flex-wrap tw:-mt-4 tw:-mx-2 tw:items-end!">
            @if ($branches->count() > 1)
                <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                    <label class="nx-label" for="wf-branch">Branch</label>
                    <select name="branch_id" id="wf-branch" class="ui-form-select nx-input">
                        @foreach ($branches as $branchOption)
                            <option value="{{ $branchOption->id }}" {{ (int) $branchId === (int) $branchOption->id ? 'selected' : '' }}>
                                {{ $branchOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full" type="hidden" name="branch_id" value="{{ $branchId }}">
            @endif

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                <label class="nx-label" for="wf-year">Year</label>
                <select name="year" id="wf-year" class="ui-form-select nx-input">
                    @for ($y = now()->year; $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[25%]">
                <label class="nx-label" for="wf-month">Month</label>
                <select name="month" id="wf-month" class="ui-form-select nx-input">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
            </div>

            <div class="tw:shrink-0 tw:max-w-full tw:px-2 tw:mt-4 tw:w-full tw:md:flex-[0_0_auto] tw:md:w-[50%] tw:lg:flex-[0_0_auto] tw:lg:w-[16.66666667%]">
                <button type="submit" class="nx-btn nx-btn-primary tw:w-full!"><i class="bi bi-funnel"></i> Show</button>
            </div>
        </form>
    </div>
</div>
