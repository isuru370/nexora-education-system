@extends('layouts.app')

@section('title', 'Edit Branch')

@section('content')

{{-- ============================================ --}}
{{-- HERO HEADER --}}
{{-- ============================================ --}}
<div class="card border-0 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 30%, #2563eb 100%); border-radius: 20px; overflow: hidden; position: relative;">
    <div style="position:absolute; top:-60px; right:-40px; width:200px; height:200px; border-radius:50%; background:rgba(96,165,250,0.08);"></div>
    <div style="position:absolute; bottom:-70px; left:-40px; width:160px; height:160px; border-radius:50%; background:rgba(251,191,36,0.06);"></div>

    <div class="card-body p-4 p-md-5 position-relative">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <a href="{{ route('branches.index') }}"
                   class="btn btn-sm mb-3 fw-semibold"
                   style="background: rgba(255,255,255,0.12); backdrop-filter: blur(10px); color: #fff; border-radius: 30px; padding: 6px 16px;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Branches
                </a>

                <h1 class="text-white fw-bold mb-2" style="font-size: 2rem;">
                    Edit <span style="background: linear-gradient(135deg,#60a5fa,#fbbf24); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">{{ $branch->name }}</span>
                </h1>

                <p class="text-white-50 mb-3" style="font-size: 0.95rem;">
                    <i class="bi bi-pencil-square me-1" style="color: #fbbf24;"></i>
                    Update branch information and settings.
                </p>

                {{-- Branch Meta Badges --}}
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill" style="background: rgba(255,255,255,0.12); backdrop-filter: blur(10px); color: #fff; padding: 6px 14px; font-weight: 600;">
                        <i class="bi bi-hash me-1"></i>{{ $branch->code }}
                    </span>

                    @if($branch->is_active)
                        <span class="badge rounded-pill" style="background: rgba(16,185,129,0.2); color: #34d399; padding: 6px 14px; font-weight: 600;">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Active
                        </span>
                    @else
                        <span class="badge rounded-pill" style="background: rgba(148,163,184,0.2); color: #cbd5e1; padding: 6px 14px; font-weight: 600;">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Inactive
                        </span>
                    @endif

                    @if($branch->created_at)
                        <span class="badge rounded-pill" style="background: rgba(255,255,255,0.08); color: #cbd5e1; padding: 6px 14px; font-weight: 500;">
                            <i class="bi bi-calendar3 me-1"></i> Created {{ $branch->created_at->format('d M Y') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="col-lg-4 text-lg-end">
                <div class="d-inline-flex align-items-center justify-content-center rounded-4"
                     style="width: 90px; height: 90px; background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1);">
                    <i class="bi bi-pencil-square text-white" style="font-size: 2.5rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================ --}}
{{-- INFO ALERT --}}
{{-- ============================================ --}}
<div class="alert border-0 mb-4 d-flex align-items-start gap-3" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border-radius: 14px; border-left: 4px solid #2563eb !important;">
    <i class="bi bi-info-circle-fill mt-1" style="color: #2563eb; font-size: 1.1rem;"></i>
    <div class="flex-grow-1">
        <strong style="color: #1e40af;">Editing Mode</strong>
        <p class="mb-0 small" style="color: #3730a3;">
            Changes will be applied immediately after saving. Make sure all information is accurate before submitting.
        </p>
    </div>
</div>

{{-- ============================================ --}}
{{-- FORM CARD --}}
{{-- ============================================ --}}
<div class="row g-4">

    {{-- Main Form --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius: 20px;">
            <div class="card-header bg-white border-bottom p-4" style="border-radius: 20px 20px 0 0;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width: 44px; height: 44px; background: #eff6ff; color: #2563eb;">
                            <i class="bi bi-pencil-square" style="font-size: 1.2rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="color: #0f172a;">Branch Information</h5>
                            <small class="text-muted">Update the fields you want to change</small>
                        </div>
                    </div>

                    <span class="badge rounded-pill" style="background: #eff6ff; color: #2563eb; padding: 6px 14px; font-weight: 600; font-family: monospace;">
                        ID: #{{ $branch->id }}
                    </span>
                </div>
            </div>

            <div class="card-body p-4">
                <form action="{{ route('branches.update', $branch->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('branches.partials.form', ['branch' => $branch])
                </form>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">

        {{-- Branch Details Card --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width: 36px; height: 36px; background: #eff6ff; color: #2563eb;">
                        <i class="bi bi-info-circle-fill"></i>
                    </div>
                    <h6 class="fw-bold mb-0" style="color: #0f172a;">Branch Details</h6>
                </div>

                <div class="list-group list-group-flush">
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="bi bi-hash me-2"></i>Code
                        </span>
                        <span class="fw-bold small" style="font-family: monospace;">{{ $branch->code }}</span>
                    </div>

                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="bi bi-calendar-plus me-2"></i>Created
                        </span>
                        <span class="fw-semibold small">
                            {{ $branch->created_at ? $branch->created_at->format('d M Y') : '-' }}
                        </span>
                    </div>

                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="bi bi-clock-history me-2"></i>Updated
                        </span>
                        <span class="fw-semibold small">
                            {{ $branch->updated_at ? $branch->updated_at->diffForHumans() : 'Never' }}
                        </span>
                    </div>

                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="bi bi-toggles me-2"></i>Status
                        </span>
                        @if($branch->is_active)
                            <span class="badge rounded-pill" style="background: #ecfdf5; color: #10b981; font-weight: 600;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.4rem;"></i> Active
                            </span>
                        @else
                            <span class="badge rounded-pill bg-light text-secondary border" style="font-weight: 600;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.4rem;"></i> Inactive
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Warning Card --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px; background: linear-gradient(135deg, #fef3c7, #fde68a);">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 40px; height: 40px; background: #f59e0b; color: white;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1" style="color: #78350f;">Important</h6>
                        <p class="small mb-0" style="color: #92400e;">
                            Changing the <strong>branch code</strong> may affect existing records linked to this branch.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="card border-0 shadow-sm" style="border-radius: 20px; border: 1px solid #fecaca !important;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width: 36px; height: 36px; background: #fef2f2; color: #ef4444;">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                    <h6 class="fw-bold mb-0" style="color: #991b1b;">Danger Zone</h6>
                </div>

                <p class="small text-muted mb-3">
                    Once deleted, this branch cannot be recovered. Please be certain.
                </p>

                <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this branch permanently? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-semibold" style="border-radius: 10px;">
                        <i class="bi bi-trash3 me-1"></i> Delete This Branch
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection