{{-- ============================================================
     Exams Stats — nx-* design system
     ============================================================ --}}

<div class="stats-grid mb-4">

    {{-- TOTAL --}}
    <div class="nx-card stat-card">
        <div class="stat-icon-wrapper icon-blue"><i class="bi bi-calendar-check"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Exams</span>
            <h2 class="stat-number">{{ $stats['total'] ?? 0 }}</h2>
            <span class="stat-change neutral">All <span class="stat-change-note">exams</span></span>
        </div>
    </div>

    {{-- SCHEDULED --}}
    <div class="nx-card stat-card">
        <div class="stat-icon-wrapper icon-green"><i class="bi bi-clock-history"></i></div>
        <div class="stat-info">
            <span class="stat-label">Scheduled</span>
            <h2 class="stat-number">{{ $stats['scheduled'] ?? 0 }}</h2>
            <span class="stat-change up">Upcoming <span class="stat-change-note">exams</span></span>
        </div>
    </div>

    {{-- ONGOING --}}
    <div class="nx-card stat-card">
        <div class="stat-icon-wrapper icon-amber"><i class="bi bi-play-circle-fill"></i></div>
        <div class="stat-info">
            <span class="stat-label">Ongoing</span>
            <h2 class="stat-number">{{ $stats['ongoing'] ?? 0 }}</h2>
            <span class="stat-change neutral">Running <span class="stat-change-note">now</span></span>
        </div>
    </div>

    {{-- COMPLETED --}}
    <div class="nx-card stat-card">
        <div class="stat-icon-wrapper icon-violet"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-info">
            <span class="stat-label">Completed</span>
            <h2 class="stat-number">{{ $stats['completed'] ?? 0 }}</h2>
            <span class="stat-change up">Finished <span class="stat-change-note">exams</span></span>
        </div>
    </div>

    {{-- CANCELLED --}}
    <div class="nx-card stat-card">
        <div class="stat-icon-wrapper icon-red"><i class="bi bi-x-circle-fill"></i></div>
        <div class="stat-info">
            <span class="stat-label">Cancelled</span>
            <h2 class="stat-number">{{ $stats['cancelled'] ?? 0 }}</h2>
            <span class="stat-change down">Removed <span class="stat-change-note">exams</span></span>
        </div>
    </div>

    {{-- UPCOMING --}}
    <div class="nx-card stat-card">
        <div class="stat-icon-wrapper icon-blue"><i class="bi bi-calendar-event"></i></div>
        <div class="stat-info">
            <span class="stat-label">Upcoming</span>
            <h2 class="stat-number">{{ $stats['upcoming'] ?? 0 }}</h2>
            <span class="stat-change up">Soon <span class="stat-change-note">to come</span></span>
        </div>
    </div>

</div>