@extends('layouts.app')

@section('title', 'Tute Details - ' . ($data['student']->full_name ?? 'Student'))
@section('page-title', 'Tute Details')

@section('content')
<div class="details-page">

    <div class="top-card">
        <div>
            <div class="eyebrow"><i class="bi bi-journal-bookmark-fill"></i> Student Tute</div>
            <h3>{{ $data['student']->initial_name ?? $data['student']->full_name ?? 'Student' }}</h3>
            <p>
                {{ $data['class']->class_name ?? 'N/A' }}
                <span class="dot">•</span>
                {{ $data['category']->category_name ?? 'N/A' }}
            </p>
        </div>
        <a href="{{ route('admin.student-class-management.show', $data['student']->id) }}"
           class="btn btn-light border custom-btn">
            <i class="bi bi-arrow-left"></i> Back to Classes
        </a>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <span>Total Tutes</span>
            <strong>{{ $data['total_tutes'] ?? 0 }}</strong>
            <small>Issued records</small>
        </div>
        <div class="info-card success">
            <span>Issued</span>
            <strong>{{ $data['issued_tutes'] ?? 0 }}</strong>
            <small>Tutes marked as issued</small>
        </div>
        <div class="info-card warning">
            <span>Pending</span>
            <strong>{{ $data['pending_tutes'] ?? 0 }}</strong>
            <small>Not yet issued</small>
        </div>
        <div class="info-card">
            <span>Enrollment Date</span>
            <strong class="date-value">{{ $data['enrolled_at']?->format('Y-m-d') ?? 'N/A' }}</strong>
            <small>Enrollment start</small>
        </div>
    </div>

    <div class="main-card">
        <div class="section-header">
            <div>
                <h4>Tute History</h4>
                <p>Tute records for this student, class and enrollment</p>
            </div>
            <span class="count-badge">{{ $data['total_tutes'] ?? 0 }} Tutes</span>
        </div>

        @if (count($data['tutes'] ?? []) > 0)
            <div class="table-responsive">
                <table class="table details-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Month</th>
                            <th>Status</th>
                            <th>Issued At</th>
                            <th>Issued By</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($data['tutes'] as $index => $tute)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <span class="month-pill">
                                    <i class="bi bi-calendar3"></i>
                                    {{ $tute->issued_month?->format('F Y') ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                @if ($tute->is_issued)
                                    <span class="status-badge issued-badge">
                                        <i class="bi bi-check-circle-fill"></i> Issued
                                    </span>
                                @else
                                    <span class="status-badge pending-badge">
                                        <i class="bi bi-clock-fill"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td>{{ $tute->issued_at?->format('Y-m-d h:i A') ?? '—' }}</td>
                            <td>{{ $tute->issuedBy->name ?? $tute->issuedBy->full_name ?? '—' }}</td>
                            <td>{{ $tute->note ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-journal-x"></i>
                <h5>No Tute Records</h5>
                <p>No tute records were found for this student enrollment.</p>
            </div>
        @endif
    </div>

    <div class="main-card">
        <div class="section-header">
            <div>
                <h4>Enrollment Information</h4>
                <p>Current class context for these tute records</p>
            </div>
        </div>

        <div class="detail-grid">
            <div><span>Student ID</span><strong>{{ $data['student']->custom_id ?? 'N/A' }}</strong></div>
            <div><span>Class</span><strong>{{ $data['class']->class_name ?? 'N/A' }}</strong></div>
            <div><span>Category</span><strong>{{ $data['category']->category_name ?? 'N/A' }}</strong></div>
            <div><span>Grade</span><strong>{{ $data['grade']->grade_name ?? 'N/A' }}</strong></div>
            <div><span>Teacher</span><strong>{{ $data['teacher']->initials ?? 'N/A' }}</strong></div>
            <div><span>Enrollment</span><strong>#{{ $data['enrollment']->id ?? 'N/A' }}</strong></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.details-page{animation:fadeIn .35s ease}.top-card,.main-card,.info-card{background:#fff;border:1px solid #eef2f7;box-shadow:0 10px 30px rgba(0,0,0,.05)}
.top-card{border-radius:24px;padding:1.5rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem}.eyebrow{font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:700}.top-card h3{margin:.3rem 0;font-weight:800}.top-card p{margin:0;color:#64748b}.dot{margin:0 .35rem}.custom-btn{border-radius:13px;padding:.65rem 1rem;font-weight:600}
.info-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem}.info-card{border-radius:20px;padding:1.25rem}.info-card span{display:block;color:#64748b;font-size:.8rem}.info-card strong{display:block;font-size:1.5rem;margin:.35rem 0}.info-card small{color:#94a3b8}.info-card.success{border-left:4px solid #10b981}.info-card.warning{border-left:4px solid #f59e0b}.date-value{font-size:1.1rem!important}
.main-card{border-radius:24px;padding:1.5rem;margin-bottom:1rem}.section-header{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1.2rem}.section-header h4{margin:0;font-weight:750}.section-header p{margin:.2rem 0 0;color:#64748b}.count-badge,.month-pill{background:#f8fafc;border-radius:10px;padding:.45rem .7rem;color:#475569;font-size:.8rem}.month-pill{display:inline-flex;align-items:center;gap:.35rem}.details-table thead th{background:#f8fafc;border:0;color:#64748b;font-size:.76rem;text-transform:uppercase;padding:.9rem}.details-table td{padding:.9rem;border-color:#f1f5f9}.status-badge{display:inline-flex;align-items:center;gap:.35rem;border-radius:10px;padding:.45rem .7rem;font-size:.75rem;font-weight:700}.issued-badge{background:#ecfdf5;color:#059669}.pending-badge{background:#fff7ed;color:#d97706}.detail-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}.detail-grid>div{background:#f8fafc;border-radius:14px;padding:1rem}.detail-grid span{display:block;color:#64748b;font-size:.8rem}.detail-grid strong{display:block;margin-top:.25rem}.empty-state{text-align:center;padding:3rem 1rem;color:#64748b}.empty-state i{font-size:3rem;color:#cbd5e1}.empty-state h5{color:#334155;font-weight:700;margin:.8rem 0 .3rem}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}@media(max-width:992px){.info-grid{grid-template-columns:repeat(2,1fr)}.detail-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:576px){.top-card{flex-direction:column;align-items:stretch}.info-grid,.detail-grid{grid-template-columns:1fr}.main-card,.top-card{padding:1rem}}
</style>
@endpush
