@extends('layouts.app')

@section('title', 'Create Branch')

@section('content')

{{-- ============================================ --}}
{{-- HERO HEADER --}}
{{-- ============================================ --}}
<div class="card border-0 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 30%, #2563eb 100%); border-radius: 20px; overflow: hidden; position: relative;">
    <div style="position:absolute; top:-60px; right:-40px; width:200px; height:200px; border-radius:50%; background:rgba(96,165,250,0.08);"></div>
    <div style="position:absolute; bottom:-70px; left:-40px; width:160px; height:160px; border-radius:50%; background:rgba(52,211,153,0.06);"></div>

    <div class="card-body p-4 p-md-5 position-relative">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <a href="{{ route('branches.index') }}"
                   class="btn btn-sm mb-3 fw-semibold"
                   style="background: rgba(255,255,255,0.12); backdrop-filter: blur(10px); color: #fff; border-radius: 30px; padding: 6px 16px;">
                    <i class="bi bi-arrow-left me-1"></i> Back to Branches
                </a>

                <h1 class="text-white fw-bold mb-2" style="font-size: 2rem;">
                    Create <span style="background: linear-gradient(135deg,#60a5fa,#34d399); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">New Branch</span>
                </h1>

                <p class="text-white-50 mb-0" style="font-size: 0.95rem;">
                    <i class="bi bi-info-circle me-1" style="color: #34d399;"></i>
                    Fill in the details below to add a new branch to your institution.
                </p>
            </div>

            <div class="col-lg-4 text-lg-end">
                <div class="d-inline-flex align-items-center justify-content-center rounded-4"
                     style="width: 90px; height: 90px; background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1);">
                    <i class="bi bi-building-add text-white" style="font-size: 2.5rem;"></i>
                </div>
            </div>
        </div>
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
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width: 44px; height: 44px; background: #eff6ff; color: #2563eb;">
                        <i class="bi bi-pencil-square" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #0f172a;">Branch Information</h5>
                        <small class="text-muted">All fields marked <span class="text-danger">*</span> are required</small>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <form action="{{ route('branches.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @include('branches.partials.form')
                </form>
            </div>
        </div>
    </div>

    {{-- Sidebar Tips --}}
    <div class="col-lg-4">

        {{-- Tips Card --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width: 36px; height: 36px; background: #fef3c7; color: #f59e0b;">
                        <i class="bi bi-lightbulb-fill"></i>
                    </div>
                    <h6 class="fw-bold mb-0" style="color: #0f172a;">Quick Tips</h6>
                </div>

                <ul class="list-unstyled mb-0">
                    <li class="d-flex gap-2 mb-3">
                        <i class="bi bi-check-circle-fill text-success mt-1" style="font-size: 0.9rem;"></i>
                        <span class="small text-muted">Use a <strong class="text-dark">unique branch code</strong> for easy identification.</span>
                    </li>
                    <li class="d-flex gap-2 mb-3">
                        <i class="bi bi-check-circle-fill text-success mt-1" style="font-size: 0.9rem;"></i>
                        <span class="small text-muted">Provide accurate <strong class="text-dark">contact details</strong> for communication.</span>
                    </li>
                    <li class="d-flex gap-2 mb-3">
                        <i class="bi bi-check-circle-fill text-success mt-1" style="font-size: 0.9rem;"></i>
                        <span class="small text-muted">Set the branch <strong class="text-dark">active</strong> to make it available immediately.</span>
                    </li>
                    <li class="d-flex gap-2">
                        <i class="bi bi-check-circle-fill text-success mt-1" style="font-size: 0.9rem;"></i>
                        <span class="small text-muted">You can <strong class="text-dark">edit details</strong> anytime after creation.</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Info Card --}}
        <div class="card border-0 shadow-sm" style="border-radius: 20px; background: linear-gradient(135deg, #eff6ff, #dbeafe);">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 40px; height: 40px; background: #2563eb; color: white;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1" style="color: #1e40af;">Secure & Organized</h6>
                        <p class="small mb-0" style="color: #3730a3;">
                            Your branch data is securely stored and easily accessible from the dashboard.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection