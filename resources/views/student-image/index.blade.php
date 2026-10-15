@extends('layouts.app')

@section('title', 'Student Images')
@section('page-title', 'Student Images')

@section('content')
    <div class="dashboard-page">

        {{-- ===================================================== --}}
        {{-- HERO --}}
        {{-- ===================================================== --}}
        <div class="hero-section mb-4">
            <div class="hero-content">
                <div class="hero-left">
                    <span class="greeting-badge">
                        <i class="bi bi-images"></i>
                        Student Images Gallery
                    </span>
                    <h1 class="hero-title">Student Image Management</h1>
                    <p class="hero-subtitle">
                        Manage and assign student images for identification cards and records.
                    </p>
                </div>

                <div class="hero-right">
                    <div class="hero-date-time">
                        <div class="date-box">
                            <i class="bi bi-calendar-event-fill"></i>
                            <span>{{ now()->format('d M Y') }}</span>
                        </div>
                    </div>
                    <small class="hero-updated">
                        <i class="bi bi-clock-fill"></i>
                        {{ now()->format('h:i A') }}
                    </small>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- KPI CARDS --}}
        {{-- ===================================================== --}}
        @php
            $totalImages    = $quickPhotos->count();
            $assignedImages = $quickPhotos->where('is_active', 0)->count();
            $pendingImages  = $quickPhotos->where('is_active', 1)->count();
        @endphp

        <div class="stats-grid stats-grid-3 mb-4">

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="bi bi-images"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Images</span>
                    <h2 class="stat-number">{{ $totalImages }}</h2>
                    <span class="stat-change neutral">All <span class="stat-change-note">student images</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Assigned Images</span>
                    <h2 class="stat-number">{{ $assignedImages }}</h2>
                    <span class="stat-change up">Linked <span class="stat-change-note">to students</span></span>
                </div>
            </div>

            <div class="nx-card stat-card">
                <div class="stat-icon-wrapper icon-amber"><i class="bi bi-clock-history"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Pending Assignment</span>
                    <h2 class="stat-number">{{ $pendingImages }}</h2>
                    <span class="stat-change down">Awaiting <span class="stat-change-note">student link</span></span>
                </div>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- FILTER --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">
            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-funnel text-primary"></i> Search Student Images</h5>
                    <p class="nx-card-subtitle">Search by name, mobile number, or QR code</p>
                </div>
            </div>

            <div class="p-3">
                <form method="GET" action="{{ route('admin.student-images.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="nx-label">
                            <i class="bi bi-search"></i> Search
                        </label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control nx-input"
                            placeholder="Search by name, mobile number, or QR code...">
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="nx-btn nx-btn-primary flex-grow-1">
                                <i class="bi bi-search"></i> Search
                            </button>
                            <a href="{{ route('admin.student-images.index') }}" class="nx-btn nx-btn-light">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ===================================================== --}}
        {{-- TABLE --}}
        {{-- ===================================================== --}}
        <div class="nx-card mb-4">

            <div class="nx-card-header">
                <div>
                    <h5 class="nx-card-title"><i class="bi bi-images text-primary"></i> Student Images List</h5>
                    <p class="nx-card-subtitle">All student images with assignment status</p>
                </div>
                <span class="nx-badge nx-badge-blue">
                    <i class="bi bi-list-ul"></i> {{ $totalImages }} Images
                </span>
            </div>

            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 10%;">Image</th>
                            <th style="width: 15%;">Photo ID</th>
                            <th style="width: 20%;">Student Name</th>
                            <th style="width: 15%;">Guardian Mobile</th>
                            <th style="width: 20%;">QR Code</th>
                            <th style="width: 15%;">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($quickPhotos as $key => $photo)
                            <tr>
                                <td class="row-number">{{ $loop->iteration }}</td>

                                {{-- IMAGE --}}
                                <td>
                                    <img src="{{ asset('storage/' . $photo->image_path) }}" alt="Student Image"
                                        class="student-img"
                                        onerror="this.src='{{ asset('storage/uploads/male.png') }}'">
                                </td>

                                {{-- PHOTO ID --}}
                                <td>
                                    <span class="id-badge">{{ $photo->custom_id }}</span>
                                </td>

                                {{-- STUDENT NAME --}}
                                <td>
                                    @if ($photo->is_active == 0)
                                        <span class="cell-title">{{ $photo->initial_name ?? 'N/A' }}</span>
                                    @else
                                        <span class="cell-sub fst-italic">Not Assigned</span>
                                    @endif
                                </td>

                                {{-- GUARDIAN MOBILE --}}
                                <td>
                                    @if ($photo->is_active == 0)
                                        <span class="cell-sub">{{ $photo->guardian_mobile ?? 'N/A' }}</span>
                                    @else
                                        <span class="cell-sub">—</span>
                                    @endif
                                </td>

                                {{-- QR CODE --}}
                                <td>
                                    @if ($photo->is_active == 0)
                                        <span class="id-badge">{{ $photo->student_qr ?? 'N/A' }}</span>
                                    @else
                                        <span class="cell-sub">—</span>
                                    @endif
                                </td>

                                {{-- STATUS --}}
                                <td>
                                    @if ($photo->is_active == 0)
                                        <span class="nx-badge nx-badge-green">
                                            <i class="bi bi-check-circle-fill"></i> Assigned
                                        </span>
                                    @else
                                        <button type="button" class="nx-btn nx-btn-primary assign-btn"
                                            data-bs-toggle="modal" data-bs-target="#assignModal"
                                            data-photo-id="{{ $photo->id }}"
                                            data-photo-code="{{ $photo->custom_id }}"
                                            data-photo-url="{{ asset('storage/' . $photo->image_path) }}"
                                            style="min-height: 32px; padding: .3rem .75rem; font-size: .75rem;">
                                            <i class="bi bi-link"></i> Assign
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-table">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    <strong class="d-block">No Records Found</strong>
                                    <span class="cell-sub">No student images available in the system.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($quickPhotos, 'links') && $quickPhotos->hasPages())
                <div class="p-3 border-top">
                    {{ $quickPhotos->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- ===================================================== --}}
    {{-- ASSIGN MODAL --}}
    {{-- ===================================================== --}}
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="assignForm" action="">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="assignModalLabel">
                            <i class="bi bi-person-badge-fill text-primary me-2"></i>
                            Assign Image to Student
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        {{-- PHOTO PREVIEW --}}
                        <div class="photo-preview">
                            <img id="modalPhotoPreview" src="{{ asset('storage/logo/black_logo.png') }}"
                                alt="Photo Preview" class="photo-preview-img">
                            <div class="mt-2">
                                <span id="modalPhotoCode" class="id-badge">-</span>
                            </div>
                        </div>

                        {{-- QR CODE INPUT --}}
                        <div class="mb-3">
                            <label class="nx-label">
                                <i class="bi bi-qr-code"></i> Student QR Code
                            </label>
                            <input type="text" name="qr_code" value="{{ old('qr_code') }}"
                                class="form-control nx-input @error('qr_code') is-invalid @enderror"
                                placeholder="Enter student custom_id or temporary QR code"
                                required>
                            @error('qr_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="cell-sub d-block mt-2">
                                <i class="bi bi-info-circle"></i>
                                Enter the student's custom ID or temporary QR code from their ID card.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="nx-btn nx-btn-light" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancel
                        </button>
                        <button type="submit" class="nx-btn nx-btn-primary">
                            <i class="bi bi-check-lg"></i> Assign Image
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


@push('styles')
<style>
    /* ============================================================
       Student Images — page-specific styles only.
       Shared nx-* theme from layouts/partials/nexora-theme.
       ============================================================ */

    /* Student image thumbnail */
    .dashboard-page .student-img {
        width: 52px;
        height: 52px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid var(--nx-border);
        transition: all .15s ease;
        display: block;
    }
    .dashboard-page .student-img:hover {
        border-color: var(--nx-blue);
        box-shadow: 0 4px 12px rgba(29, 78, 216, .15);
    }

    /* Photo preview in modal */
    .dashboard-page .photo-preview {
        background: #f4f6fa;
        border: 1px solid var(--nx-border);
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        margin-bottom: 1.25rem;
    }

    .dashboard-page .photo-preview-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 3px solid var(--nx-blue);
        box-shadow: 0 4px 12px rgba(15, 23, 42, .1);
    }

    /* Modal is teleported to body — scope with #assignModal */
    #assignModal .modal-content {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(15, 23, 42, .15);
        overflow: hidden;
    }

    #assignModal .modal-header {
        border-bottom: 1px solid #d1d8e3;
        padding: 1rem 1.25rem;
        background: #fafbfd;
    }

    #assignModal .modal-title {
        font-weight: 700;
        font-size: .95rem;
        color: #0f172a;
    }

    #assignModal .modal-body {
        padding: 1.25rem;
        background: #f4f6fa;
    }

    #assignModal .modal-footer {
        border-top: 1px solid #d1d8e3;
        padding: .85rem 1.25rem;
        background: #fafbfd;
    }

    /* Photo preview inside modal (scoped) */
    #assignModal .photo-preview {
        background: #fff;
        border: 1px solid #d1d8e3;
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        margin-bottom: 1.25rem;
    }

    #assignModal .photo-preview-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 10px;
        border: 3px solid #1d4ed8;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .1);
    }

    #assignModal .id-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .78rem;
        background: #e5eaf2;
        padding: .25rem .6rem;
        border-radius: 4px;
        color: #334155;
        display: inline-block;
    }

    #assignModal .nx-label {
        display: block;
        font-size: .75rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: .35rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    #assignModal .nx-input {
        min-height: 44px;
        border-radius: 8px !important;
        border: 1px solid #d1d8e3;
        font-size: .88rem;
        color: #0f172a;
        background: #fff;
    }

    #assignModal .nx-input:focus {
        border-color: #1d4ed8;
        box-shadow: 0 0 0 3px #dbe6ff;
    }

    #assignModal .cell-sub {
        font-size: .74rem;
        color: #475569;
    }

    #assignModal .nx-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        padding: .5rem .85rem;
        font-size: .82rem;
        font-weight: 600;
        border-radius: 8px;
        border: 1px solid #d1d8e3;
        background: #fff;
        color: #0f172a;
        text-decoration: none;
        min-height: 38px;
        transition: all .15s ease;
        cursor: pointer;
    }

    #assignModal .nx-btn-primary {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
    }

    #assignModal .nx-btn-light {
        background: #fff;
        border-color: #d1d8e3;
        color: #475569;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-page .premium-table { min-width: 900px; }
        .dashboard-page .student-img { width: 42px; height: 42px; }
    }
</style>
@endpush


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const assignButtons = document.querySelectorAll('.assign-btn');
        const assignForm = document.getElementById('assignForm');
        const modalPhotoCode = document.getElementById('modalPhotoCode');
        const modalPhotoPreview = document.getElementById('modalPhotoPreview');

        assignButtons.forEach(button => {
            button.addEventListener('click', function () {
                const photoId   = this.getAttribute('data-photo-id');
                const photoCode = this.getAttribute('data-photo-code');
                const photoUrl  = this.getAttribute('data-photo-url');

                assignForm.action = `/admin/student-images/${photoId}/assign`;
                modalPhotoCode.textContent = photoCode;
                if (photoUrl) {
                    modalPhotoPreview.src = photoUrl;
                }
            });
        });

        /* Auto-show modal if there are validation errors */
        @if ($errors->has('qr_code'))
            const assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
            assignModal.show();
        @endif

        /* Auto-hide alerts after 5 seconds */
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>
@endpush