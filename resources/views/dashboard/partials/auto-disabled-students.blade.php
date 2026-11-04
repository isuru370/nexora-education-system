{{-- Students made inactive by the nightly job (no attendance for 2 months) --}}
@php
    $autoDisabled = app(\App\Services\Web\Student\StudentAutoDisableService::class)->lastRun();
@endphp
<a href="{{ route('auto-disabled-students.index') }}"
    class="nx-alert {{ $autoDisabled && $autoDisabled['still_inactive'] > 0 ? 'nx-alert-red' : 'nx-alert-blue' }} mb-4 d-flex">
    <i class="bi bi-person-x"></i>
    <span>
        @if ($autoDisabled)
            Auto-disabled students ({{ \Carbon\Carbon::parse($autoDisabled['date'])->format('d M Y') }}):
            <strong>{{ $autoDisabled['count'] }}</strong>
            @if ($autoDisabled['still_inactive'] !== $autoDisabled['count'])
                ({{ $autoDisabled['still_inactive'] }} still inactive)
            @endif
            - no attendance for 2 months
        @else
            Auto-disabled students: none yet (no attendance for 2 months)
        @endif
    </span>
    <i class="bi bi-chevron-right ms-auto"></i>
</a>
