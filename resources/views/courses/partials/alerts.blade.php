{{-- success / error are shown by the layout --}}
@if (session('warning'))
    <div class="nx-alert nx-alert-amber tw:mb-6!">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span>{{ session('warning') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="nx-alert nx-alert-red tw:mb-6!">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif
