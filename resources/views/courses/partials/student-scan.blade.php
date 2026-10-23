{{-- Scan / type a student ID; reloads the page with ?code= --}}
<div class="nx-card mb-4">
    <div class="p-3">
        <form method="GET" action="{{ $action }}" class="inline-form" autocomplete="off">
            <div class="flex-grow-1">
                <label class="nx-label" for="code">Scan QR or type student ID</label>
                <input id="code" name="code" class="form-control nx-input scan-box" placeholder="Student ID / QR" value="{{ request('code') }}" required {{ $student ? '' : 'autofocus' }}>
            </div>
            <div style="min-width:auto"><button class="nx-btn nx-btn-primary" type="submit"><i class="bi bi-search"></i> Find student</button></div>
            @if ($student)
                <div style="min-width:auto"><a href="{{ $action }}" class="nx-btn nx-btn-light">Next student</a></div>
            @endif
        </form>
    </div>
    @if ($error)
        <div class="px-3 pb-3"><div class="nx-alert nx-alert-red mb-0"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $error }}</span></div></div>
    @endif
    @if ($student)
        <div class="px-3 pb-3">
            <div class="student-strip">
                <div class="student-avatar">{{ strtoupper(substr($student->initial_name ?? 'S', 0, 1)) }}</div>
                <div>
                    <div class="cell-title">{{ $student->initial_name }}</div>
                    <small class="cell-sub">{{ $student->custom_id }} · {{ $student->mobile ?: ($student->guardian_mobile ?: 'no mobile') }}</small>
                </div>
            </div>
        </div>
    @endif
</div>
