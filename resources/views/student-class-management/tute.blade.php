@extends('layouts.app')

@section('title', 'Tute Details - ' . ($data['student']->full_name ?? 'Student'))
@section('page-title', 'Tute Details')

@section('content')
<div class="dashboard-page">

    {{-- HERO --}}
    <div class="hero-section mb-4">
        <div class="hero-content">
            <div class="hero-left">
                <span class="greeting-badge">nexoraEDU</span>
                <h1 class="hero-title">{{ $data['student']->initial_name ?? $data['student']->full_name ?? 'Student' }}</h1>
                <p class="hero-subtitle">{{ $data['class']->class_name ?? 'N/A' }} • {{ $data['category']->category_name ?? 'N/A' }}</p>
            </div>
            <div class="hero-right">
                <div class="hero-actions">
                    <a href="{{ route('student-class-management.show', $data['student']->id) }}" class="nx-btn nx-btn-light">
                        <i class="bi bi-arrow-left"></i> Back to Classes
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- SUMMARY --}}
    <div class="stats-grid mb-4">
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Total Tutes</span>
                <h2 class="stat-number">{{ $data['total_tutes'] ?? 0 }}</h2>
                <span class="stat-change neutral">Issued <span class="stat-change-note">records</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Issued</span>
                <h2 class="stat-number">{{ $data['issued_tutes'] ?? 0 }}</h2>
                <span class="stat-change up">Marked <span class="stat-change-note">as issued</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-amber"><i class="bi bi-clock-fill"></i></div>
            <div class="stat-info">
                <span class="stat-label">Pending</span>
                <h2 class="stat-number">{{ $data['pending_tutes'] ?? 0 }}</h2>
                <span class="stat-change down">Not yet <span class="stat-change-note">issued</span></span>
            </div>
        </div>
        <div class="nx-card stat-card">
            <div class="stat-icon-wrapper icon-violet"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-info">
                <span class="stat-label">Enrollment Date</span>
                <h2 class="stat-number" style="font-size: 1rem;">{{ $data['enrolled_at']?->format('Y-m-d') ?? 'N/A' }}</h2>
                <span class="stat-change neutral">Enrollment <span class="stat-change-note">start</span></span>
            </div>
        </div>
    </div>

    {{-- TUTE HISTORY --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-journal-bookmark-fill text-primary"></i> Tute History</h5>
                <p class="nx-card-subtitle">Tute records for this student, class and enrollment</p>
            </div>
            <span class="nx-badge nx-badge-blue">{{ $data['total_tutes'] ?? 0 }} Tutes</span>
        </div>

        @if (count($data['tutes'] ?? []) > 0)
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th><th>Month</th><th>Status</th><th>Issued At</th><th>Issued By</th><th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['tutes'] as $index => $tute)
                            <tr>
                                <td class="row-number">{{ $index + 1 }}</td>
                                <td>
                                    <span class="nx-badge nx-badge-gray">
                                        <i class="bi bi-calendar3"></i>
                                        {{ $tute->issued_month?->format('F Y') ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($tute->is_issued)
                                        <span class="nx-badge nx-badge-green"><i class="bi bi-check-circle-fill"></i> Issued</span>
                                    @else
                                        <span class="nx-badge nx-badge-amber"><i class="bi bi-clock-fill"></i> Pending</span>
                                    @endif
                                </td>
                                <td class="cell-sub">{{ $tute->issued_at?->format('Y-m-d h:i A') ?? '—' }}</td>
                                <td class="cell-sub">{{ $tute->issuedBy->name ?? $tute->issuedBy->full_name ?? '—' }}</td>
                                <td class="cell-sub">{{ $tute->note ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-table">
                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                <strong class="d-block">No Tute Records</strong>
                <span class="cell-sub">No tute records were found for this student enrollment.</span>
            </div>
        @endif
    </div>

    {{-- ENROLLMENT INFORMATION --}}
    <div class="nx-card mb-4">
        <div class="nx-card-header">
            <div>
                <h5 class="nx-card-title"><i class="bi bi-info-circle text-primary"></i> Enrollment Information</h5>
                <p class="nx-card-subtitle">Current class context for these tute records</p>
            </div>
        </div>
        <div class="p-3">
            <div class="detail-grid">
                <div class="detail-item"><span class="detail-label">Student ID</span><strong class="detail-value">{{ $data['student']->custom_id ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Class</span><strong class="detail-value">{{ $data['class']->class_name ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Category</span><strong class="detail-value">{{ $data['category']->category_name ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Grade</span><strong class="detail-value">{{ $data['grade']->grade_name ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Teacher</span><strong class="detail-value">{{ $data['teacher']->initials ?? 'N/A' }}</strong></div>
                <div class="detail-item"><span class="detail-label">Enrollment</span><strong class="detail-value">#{{ $data['enrollment']->id ?? 'N/A' }}</strong></div>
            </div>
        </div>
    </div>

</div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Tute Details — page-specific styles only.
       ============================================================ */

    .dashboard-page .detail-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    .dashboard-page .detail-item {
        background: #fafbfd;
        border: 1px solid var(--nx-border);
        border-radius: 8px;
        padding: .85rem 1rem;
    }
    .dashboard-page .detail-label {
        display: block;
        color: var(--nx-muted);
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .dashboard-page .detail-value {
        display: block;
        margin-top: .25rem;
        font-size: .88rem;
        font-weight: 600;
        color: var(--nx-text);
    }

    @media (max-width: 992px) {
        .dashboard-page .detail-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .dashboard-page .detail-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush