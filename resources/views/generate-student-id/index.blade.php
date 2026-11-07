@extends('layouts.app')

@section('title', 'Generate Student ID')
@section('page-title', 'Generate Student ID')

@section('content')
    @php
        // ==================== ✅ ADMIN CHECK ====================
        $user    = auth()->user();
        $isAdmin = ($user && (
            $user->email === 'admin@nexorait.lk' ||
            in_array($user->role ?? '', ['SUPER_ADMIN', 'ADMIN'])
        ));
    @endphp

    <div class="dashboard-page student-id-page">

        {{-- ==================== ALERTS ==================== --}}
        @if (session('error'))
            <div class="nx-alert nx-alert-red mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if (session('success'))
            <div class="nx-alert nx-alert-green mb-4">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- ==================== HERO SECTION ==================== --}}
        @include('generate-student-id.components.hero-section', [
            'totalStudents' => $students->total()
        ])

        {{-- ==================== SEARCH FORM ==================== --}}
        @include('generate-student-id.components.search-form', [
            'totalStudents' => $students->total()
        ])

        {{-- ==================== BULK ACTIONS ==================== --}}
        @include('generate-student-id.components.bulk-actions', [
            'isAdmin' => $isAdmin
        ])

        {{-- ==================== STUDENT CARDS GRID ==================== --}}
        @if ($students->count() > 0)
            <div class="row g-3" id="studentsGrid">
                @foreach ($students as $student)
                    @include('generate-student-id.components.student-card', [
                        'student' => $student,
                        'isAdmin' => $isAdmin
                    ])
                @endforeach
            </div>
        @else
            <div class="nx-card mb-4">
                <div class="empty-table py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
                    <strong class="d-block mb-2">No Students Found</strong>
                    <span class="cell-sub">
                        @if (request('search'))
                            No matching students found.
                        @elseif (($show ?? 'all') === 'pending')
                            Every active student already has a permanent ID card.
                        @else
                            There are no active students.
                        @endif
                    </span>
                </div>
            </div>
        @endif

        {{-- ==================== PAGINATION ==================== --}}
        @include('generate-student-id.components.pagination', [
            'students' => $students
        ])

    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Generate Student ID — page-level styles only.
       Component-level styles live in their own files.
       ID card design (mm-based) is preserved as-is.
       ============================================================ */

    .id-download-note {
        font-size: .78rem;
        color: #92400e;
        background: #fef3c7;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 6px 10px;
        line-height: 1.35;
    }

    /* Card fee popup (2nd download) */
    .cf-overlay {
        position: fixed; inset: 0; z-index: 2000; display: flex; align-items: center; justify-content: center;
        background: rgba(15, 23, 42, .55); padding: 16px;
    }
    .cf-box {
        background: #fff; color: #0f172a; border-radius: 16px; width: 100%; max-width: 440px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .3); padding: 22px;
    }
    .cf-box h5 { font-weight: 800; margin-bottom: 4px; }
    .cf-box .cf-sub { color: #64748b; font-size: .88rem; margin-bottom: 14px; }
    .cf-box label { font-weight: 600; font-size: .85rem; margin-bottom: 4px; }

    /* Download ID Card button on each student (was white text, no background) */
    .download-single-btn {
        background: linear-gradient(135deg, #1d4ed8, #2563eb);
        border: none;
        color: #fff !important;
        font-weight: 600;
        padding: 9px 12px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }

    .download-single-btn:hover:not(:disabled),
    .download-single-btn:focus-visible {
        background: linear-gradient(135deg, #1e40af, #1d4ed8);
        color: #fff !important;
        box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
    }

    .download-single-btn:disabled {
        opacity: 0.6;
        cursor: wait;
    }

    @font-face {
        font-family: 'Monbaiti';
        src: url('{{ asset('fonts/monbaiti.ttf') }}') format('truetype');
    }

    .dashboard-page.student-id-page {
        --id-blue: #0f4bb5;
        --id-blue-dark: #123a8a;
        --id-teal: #2ab5a5;
        --id-green: #17a34a;
        --id-muted: #64748b;
    }

    /* ---------- ID card print & capture rules ---------- */
    .dashboard-page .html2canvas-capture .card-inst-name,
    .dashboard-page .html2canvas-capture .card-inst-sub {
        background: none !important;
        -webkit-background-clip: unset !important;
        background-clip: unset !important;
        color: #1d4ed8 !important;
    }

    .dashboard-page .student-select:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .dashboard-page .btn-secondary:disabled {
        opacity: .6;
        cursor: not-allowed;
    }

    /* ---------- Print ---------- */
    @media print {
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .sidebar,
        .top-navbar,
        .hero-section,
        .nx-search,
        .bulk-actions,
        .download-btn,
        .pagination,
        .student-top,
        .student-action-area,
        .form-check,
        .student-id-badge,
        .row > .col-xl-4,
        .premium-student-card {
            display: none !important;
        }

        .student-card {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .student-id-card {
            box-shadow: none !important;
            border-radius: 0 !important;
            margin: 0 auto !important;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .id-card-preview-wrap,
        .id-scale-box {
            padding: 0 !important;
            margin: 0 !important;
            background: none !important;
            height: auto !important;
        }

        body {
            margin: 0;
            padding: 0;
        }
    }
</style>
@endpush


@push('scripts')
    @include('generate-student-id.components.scripts')
@endpush