@extends('layouts.app')

@section('title', 'Branches')

@section('content')

{{-- ============================================ --}}
{{-- HERO HEADER --}}
{{-- ============================================ --}}
<div class="card border-0 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 30%, #2563eb 100%); border-radius: 20px; overflow: hidden; position: relative;">
    {{-- Decoration --}}
    <div style="position:absolute; top:-60px; right:-40px; width:220px; height:220px; border-radius:50%; background:rgba(96,165,250,0.08);"></div>
    <div style="position:absolute; bottom:-80px; left:-40px; width:180px; height:180px; border-radius:50%; background:rgba(52,211,153,0.06);"></div>

    <div class="card-body p-4 p-md-5 position-relative">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge rounded-pill mb-3" style="background: rgba(255,255,255,0.12); backdrop-filter: blur(10px); padding: 8px 16px; font-weight: 600; color: #fff;">
                    <i class="bi bi-buildings me-1"></i> Branch Management
                </span>

                <h1 class="text-white fw-bold mb-2" style="font-size: 2rem;">
                    Institution <span style="background: linear-gradient(135deg,#60a5fa,#34d399); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">Branches</span>
                </h1>

                <p class="text-white-50 mb-0" style="font-size: 0.95rem;">
                    <i class="bi bi-arrow-right-circle me-1" style="color: #34d399;"></i>
                    Manage all your education institution branches from one centralized dashboard.
                </p>
            </div>

            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('branches.create') }}" class="btn btn-light btn-lg fw-bold shadow-sm px-4">
                    <i class="bi bi-plus-circle me-2"></i> Add New Branch
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ============================================ --}}
{{-- STAT CARDS --}}
{{-- ============================================ --}}
@php
    $totalBranches = $branches->count();
    $activeBranches = $branches->where('is_active', true)->count();
    $inactiveBranches = $branches->where('is_active', false)->count();
    $activePercentage = $totalBranches > 0 ? round(($activeBranches / $totalBranches) * 100) : 0;
@endphp

<div class="row g-3 mb-4">
    {{-- Total --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border-top: 4px solid #2563eb !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">Total Branches</p>
                        <h2 class="fw-bold mb-0" style="color: #0f172a; font-size: 2.2rem;">{{ $totalBranches }}</h2>
                        <p class="text-primary fw-semibold mb-0 mt-2" style="font-size: 0.75rem;">
                            <i class="bi bi-building"></i> All registered
                        </p>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:56px; height:56px; background: linear-gradient(135deg, #2563eb, #60a5fa); color: white;">
                        <i class="bi bi-buildings" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Active --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border-top: 4px solid #10b981 !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">Active Branches</p>
                        <h2 class="fw-bold mb-0" style="color: #10b981; font-size: 2.2rem;">{{ $activeBranches }}</h2>
                        <div class="progress mt-3" style="height: 6px; border-radius: 10px;">
                            <div class="progress-bar" style="width: {{ $activePercentage }}%; background: linear-gradient(90deg, #10b981, #34d399);"></div>
                        </div>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:56px; height:56px; background: linear-gradient(135deg, #10b981, #34d399); color: white;">
                        <i class="bi bi-check-circle-fill" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Inactive --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border-top: 4px solid #f59e0b !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 1px;">Inactive Branches</p>
                        <h2 class="fw-bold mb-0" style="color: #f59e0b; font-size: 2.2rem;">{{ $inactiveBranches }}</h2>
                        <p class="text-warning fw-semibold mb-0 mt-2" style="font-size: 0.75rem;">
                            <i class="bi bi-exclamation-triangle"></i> Needs attention
                        </p>
                    </div>
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:56px; height:56px; background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================ --}}
{{-- BRANCHES TABLE --}}
{{-- ============================================ --}}
<div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">

    {{-- Header --}}
    <div class="card-header bg-white border-bottom p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:44px; height:44px; background: #eff6ff; color: #2563eb;">
                    <i class="bi bi-list-ul" style="font-size: 1.2rem;"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0" style="color: #0f172a;">All Branches</h5>
                    <small class="text-muted">View and manage all your institution branches</small>
                </div>
            </div>

            <span class="badge rounded-pill" style="background: #eff6ff; color: #2563eb; padding: 8px 16px; font-weight: 600;">
                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                {{ $totalBranches }} {{ Str::plural('Branch', $totalBranches) }}
            </span>
        </div>
    </div>

    {{-- Body --}}
    <div class="card-body p-0">
        @if($branches->isEmpty())
            {{-- Empty State --}}
            <div class="text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; background: #eff6ff; color: #2563eb;">
                    <i class="bi bi-buildings" style="font-size: 2.5rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mt-3">No branches yet</h5>
                <p class="text-muted mb-4">Get started by creating your first branch.</p>
                <a href="{{ route('branches.create') }}" class="btn btn-primary px-4">
                    <i class="bi bi-plus-circle me-2"></i> Create First Branch
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th class="px-4 py-3 text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Branch</th>
                            <th class="py-3 text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Code</th>
                            <th class="py-3 text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Contact</th>
                            <th class="py-3 text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Status</th>
                            <th class="px-4 py-3 text-end text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branches as $branch)
                            <tr>
                                {{-- Branch --}}
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 d-flex align-items-center justify-content-center text-white fw-bold" style="width:44px; height:44px; background: linear-gradient(135deg, #2563eb, #60a5fa);">
                                            {{ strtoupper(substr($branch->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold" style="color: #0f172a;">{{ $branch->name }}</div>
                                            @if($branch->address1)
                                                <small class="text-muted">
                                                    <i class="bi bi-geo-alt"></i> {{ Str::limit($branch->address1, 40) }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Code --}}
                                <td class="py-3">
                                    <span class="badge bg-light text-dark border fw-bold" style="font-family: monospace;">
                                        <i class="bi bi-hash"></i>{{ $branch->code }}
                                    </span>
                                </td>

                                {{-- Contact --}}
                                <td class="py-3">
                                    @if($branch->mobile)
                                        <div class="small fw-semibold" style="color: #334155;">
                                            <i class="bi bi-telephone text-muted"></i> {{ $branch->mobile }}
                                        </div>
                                    @endif
                                    @if($branch->email)
                                        <div class="small text-muted">
                                            <i class="bi bi-envelope"></i> {{ Str::limit($branch->email, 25) }}
                                        </div>
                                    @endif
                                    @if(!$branch->mobile && !$branch->email)
                                        <small class="text-muted fst-italic">No contact info</small>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="py-3">
                                    @if($branch->is_active)
                                        <span class="badge rounded-pill" style="background: #ecfdf5; color: #10b981; padding: 8px 14px; font-weight: 600;">
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Active
                                        </span>
                                    @else
                                        <span class="badge rounded-pill bg-light text-secondary border" style="padding: 8px 14px; font-weight: 600;">
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> Inactive
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('branches.show', $branch->id) }}" class="btn btn-outline-secondary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('branches.edit', $branch->id) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        @if($branch->is_active)
                                            <form action="{{ route('branches.deactivate', $branch->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-warning" title="Deactivate" onclick="return confirm('Deactivate this branch?')">
                                                    <i class="bi bi-pause-circle"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('branches.activate', $branch->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-success" title="Activate" onclick="return confirm('Activate this branch?')">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete this branch permanently?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection